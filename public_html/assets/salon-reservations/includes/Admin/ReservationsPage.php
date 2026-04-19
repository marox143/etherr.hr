<?php
namespace Salon\Reservations\Admin;

use DateTimeImmutable;
use Salon\Reservations\Repositories\ReservationsRepository;
use Salon\Reservations\Repositories\ServicesRepository;
use Salon\Reservations\Repositories\ShiftsRepository;
use Salon\Reservations\Email\Notifier;
use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Utils\DateTimeHelper;
use Salon\Reservations\Utils\StatusHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReservationsPage {
	public function handle_actions() {
		$this->register_ajax();

		if ( ! isset( $_GET['salon_action'], $_GET['reservation_id'] ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::MANAGE_RESERVATIONS ) ) {
			// TODO: Allow employee pre-approval when enabled in settings.
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['salon_action'] ) );
		$reservation_id = (int) $_GET['reservation_id'];

		if ( ! in_array( $action, array( 'approve', 'cancel' ), true ) ) {
			return;
		}

		check_admin_referer( 'salon_reservation_action_' . $reservation_id );

		$repo = new ReservationsRepository();
		$repo->update_status( $reservation_id, $action === 'approve' ? 'approved' : 'cancelled' );

		$reservation = $repo->get( $reservation_id );
		if ( $reservation ) {
			$notifier = new Notifier();
			$notifier->notify_customer_status( $reservation );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=rezervacije' ) );
		exit;
	}

	public function register_ajax() {
		add_action( 'wp_ajax_salon_reservation_move', array( $this, 'ajax_move_reservation' ) );
		add_action( 'wp_ajax_salon_reservation_create', array( $this, 'ajax_create_reservation' ) );
		add_action( 'wp_ajax_salon_reservation_update', array( $this, 'ajax_update_reservation' ) );
		add_action( 'wp_ajax_salon_reservation_delete', array( $this, 'ajax_delete_reservation' ) );
	}

	public function ajax_move_reservation() {
		if ( ! $this->can_edit_reservations() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_reservations_calendar', 'nonce' );

		$reservation_id = isset( $_POST['reservation_id'] ) ? (int) $_POST['reservation_id'] : 0;
		$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
		$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
		$end = sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) );

		if ( ! $reservation_id || ! $employee_id || empty( $start ) || empty( $end ) ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaju podaci.', 'salon-reservations' ) ), 400 );
		}

		$start_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $start ) );
		$end_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $end ) );

		if ( strtotime( $end_utc ) <= strtotime( $start_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Neispravan raspon.', 'salon-reservations' ) ), 400 );
		}

		$repo = new ReservationsRepository();
		$existing = $repo->get( $reservation_id );
		if ( ! $existing ) {
			wp_send_json_error( array( 'message' => __( 'Rezervacija nije pronađena.', 'salon-reservations' ) ), 404 );
		}

		$shift_repo = new ShiftsRepository();
		if ( ! $shift_repo->has_shift_covering( $employee_id, $start_utc, $end_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Odabrani termin nije unutar smjene.', 'salon-reservations' ) ), 409 );
		}

		if ( $repo->has_overlap( $employee_id, $start_utc, $end_utc, $reservation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Zaposlenik već ima rezervaciju u tom terminu.', 'salon-reservations' ) ), 409 );
		}

		$repo->update_schedule(
			$reservation_id,
			array(
				'employee_id' => $employee_id,
				'start_datetime' => $start_utc,
				'end_datetime' => $end_utc,
			)
		);

		wp_send_json_success(
			array(
				'id' => $reservation_id,
				'employee_id' => $employee_id,
				'start' => $start,
				'end' => $end,
			)
		);
	}

	public function ajax_create_reservation() {
		if ( ! $this->can_edit_reservations() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_reservations_calendar', 'nonce' );

		$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
		$service_id = isset( $_POST['service_id'] ) ? (int) $_POST['service_id'] : 0;
		$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
		$end = sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) );
		$customer_name = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
		$customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
		$customer_phone = sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'pending' ) );
		$customer_user_id = isset( $_POST['customer_user_id'] ) ? (int) $_POST['customer_user_id'] : 0;

		if ( strpos( $customer_name, '|' ) !== false ) {
			$customer_name = trim( strstr( $customer_name, '|', true ) );
		}

		if ( ! $employee_id || ! $service_id || empty( $start ) || empty( $end ) || empty( $customer_name ) || empty( $customer_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaju obavezna polja.', 'salon-reservations' ) ), 400 );
		}

		$allowed_statuses = array( 'pending', 'approved', 'cancelled' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'pending';
		}

		$start_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $start ) );
		$end_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $end ) );

		if ( strtotime( $end_utc ) <= strtotime( $start_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Neispravan raspon.', 'salon-reservations' ) ), 400 );
		}

		$services_repo = new ServicesRepository();
		$service = $services_repo->get( $service_id );
		if ( ! $service ) {
			wp_send_json_error( array( 'message' => __( 'Neispravna usluga.', 'salon-reservations' ) ), 400 );
		}

		$shift_repo = new ShiftsRepository();
		if ( ! $shift_repo->has_shift_covering( $employee_id, $start_utc, $end_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Odabrani termin nije unutar smjene.', 'salon-reservations' ) ), 409 );
		}

		$reservations_repo = new ReservationsRepository();
		if ( $reservations_repo->has_overlap( $employee_id, $start_utc, $end_utc, 0 ) ) {
			wp_send_json_error( array( 'message' => __( 'Zaposlenik već ima rezervaciju u tom terminu.', 'salon-reservations' ) ), 409 );
		}

		$reservation_id = $reservations_repo->create(
			array(
				'employee_id' => $employee_id,
				'service_id' => $service_id,
				'customer_user_id' => $customer_user_id ?: null,
				'customer_name' => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'start_datetime' => $start_utc,
				'end_datetime' => $end_utc,
				'status' => $status,
				'notes' => $notes,
				'addons' => '',
			)
		);

		wp_send_json_success(
			array(
				'id' => $reservation_id,
			)
		);
	}

	public function ajax_update_reservation() {
		if ( ! $this->can_edit_reservations() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_reservations_calendar', 'nonce' );

		$reservation_id = isset( $_POST['reservation_id'] ) ? (int) $_POST['reservation_id'] : 0;
		$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
		$service_id = isset( $_POST['service_id'] ) ? (int) $_POST['service_id'] : 0;
		$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
		$end = sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) );
		$customer_name = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
		$customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
		$customer_phone = sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'pending' ) );
		$customer_user_id = isset( $_POST['customer_user_id'] ) ? (int) $_POST['customer_user_id'] : 0;

		if ( strpos( $customer_name, '|' ) !== false ) {
			$customer_name = trim( strstr( $customer_name, '|', true ) );
		}

		if ( ! $reservation_id || ! $employee_id || ! $service_id || empty( $start ) || empty( $end ) || empty( $customer_name ) || empty( $customer_email ) ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaju obavezna polja.', 'salon-reservations' ) ), 400 );
		}

		$allowed_statuses = array( 'pending', 'approved', 'cancelled' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'pending';
		}

		$start_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $start ) );
		$end_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $end ) );

		if ( strtotime( $end_utc ) <= strtotime( $start_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Neispravan raspon.', 'salon-reservations' ) ), 400 );
		}

		$services_repo = new ServicesRepository();
		$service = $services_repo->get( $service_id );
		if ( ! $service ) {
			wp_send_json_error( array( 'message' => __( 'Neispravna usluga.', 'salon-reservations' ) ), 400 );
		}

		$shift_repo = new ShiftsRepository();
		if ( ! $shift_repo->has_shift_covering( $employee_id, $start_utc, $end_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Odabrani termin nije unutar smjene.', 'salon-reservations' ) ), 409 );
		}

		$reservations_repo = new ReservationsRepository();
		if ( $reservations_repo->has_overlap( $employee_id, $start_utc, $end_utc, $reservation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Zaposlenik već ima rezervaciju u tom terminu.', 'salon-reservations' ) ), 409 );
		}

		$reservations_repo->update_details(
			$reservation_id,
			array(
				'employee_id' => $employee_id,
				'service_id' => $service_id,
				'customer_user_id' => $customer_user_id ?: null,
				'customer_name' => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'start_datetime' => $start_utc,
				'end_datetime' => $end_utc,
				'status' => $status,
				'notes' => $notes,
			)
		);

		wp_send_json_success( array( 'id' => $reservation_id ) );
	}

	public function ajax_delete_reservation() {
		if ( ! $this->can_edit_reservations() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_reservations_calendar', 'nonce' );

		$reservation_id = isset( $_POST['reservation_id'] ) ? (int) $_POST['reservation_id'] : 0;
		if ( ! $reservation_id ) {
			wp_send_json_error( array( 'message' => __( 'Rezervacija nije pronađena.', 'salon-reservations' ) ), 404 );
		}

		$repo = new ReservationsRepository();
		$reservation = $repo->get( $reservation_id );
		if ( ! $reservation ) {
			wp_send_json_error( array( 'message' => __( 'Rezervacija nije pronađena.', 'salon-reservations' ) ), 404 );
		}

		$repo->update_status( $reservation_id, 'cancelled' );
		wp_send_json_success( array( 'id' => $reservation_id ) );
	}

	public function render( $embedded = false, $show_calendar = true, $show_list = true ) {
		if ( ! $this->can_view_reservations() ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$filters = array();
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$employee_id = isset( $_GET['employee_id'] ) ? (int) $_GET['employee_id'] : 0;
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$dates_param = isset( $_GET['dates'] ) ? sanitize_text_field( wp_unslash( $_GET['dates'] ) ) : '';
		$selected_dates = array();
		if ( $dates_param ) {
			foreach ( array_filter( array_map( 'trim', explode( ',', $dates_param ) ) ) as $date_value ) {
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_value ) ) {
					$selected_dates[] = $date_value;
				}
			}
			$selected_dates = array_values( array_unique( $selected_dates ) );
		}
		$order_by = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'date';
		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		if ( $status ) {
			$filters['status'] = $status;
		}
		if ( $employee_id ) {
			$filters['employee_id'] = $employee_id;
		}
		if ( $date_from && $date_to ) {
			list( $from, $to ) = DateTimeHelper::local_date_range_to_utc( $date_from, $date_to );
			$filters['from'] = $from;
			$filters['to'] = $to;
		}
		if ( $order_by ) {
			$filters['orderby'] = $order_by;
			$filters['order'] = $order;
		}

		$repo = new ReservationsRepository();
		$reservations = $repo->list( $filters );
		if ( ! empty( $selected_dates ) ) {
			$dates_lookup = array_fill_keys( $selected_dates, true );
			$reservations = array_values(
				array_filter(
					$reservations,
					static function( $reservation ) use ( $dates_lookup ) {
						$local_date = DateTimeHelper::utc_to_local( $reservation->start_datetime, 'Y-m-d' );
						return isset( $dates_lookup[ $local_date ] );
					}
				)
			);
		}
		$services = new ServicesRepository();
		$employees = $this->get_employees();

		$selected_year = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) date_i18n( 'Y' );
		if ( $selected_year < 1970 || $selected_year > 2100 ) {
			$selected_year = (int) date_i18n( 'Y' );
		}

		$range = $this->current_range_local( $selected_year );
		list( $from_utc, $to_utc ) = DateTimeHelper::local_date_range_to_utc( $range['start'], $range['end'] );
		$calendar_reservations = $repo->list( array( 'from' => $from_utc, 'to' => $to_utc ) );
		$shift_repo = new ShiftsRepository();
		$calendar_shifts = $shift_repo->list_all( $from_utc, $to_utc );
		$employee_map = $this->build_employee_map( $employees );
		$employee_colors = $this->build_employee_colors( $employees );
		$service_map = $this->build_services_map( $services->all() );
		$now_local = new DateTimeImmutable( 'now', DateTimeHelper::wp_timezone() );
		$upcoming_reservations = array();
		$past_reservations = array();
		foreach ( $reservations as $reservation ) {
			$end_local = new DateTimeImmutable(
				DateTimeHelper::utc_to_local( $reservation->end_datetime, 'Y-m-d H:i:s' ),
				DateTimeHelper::wp_timezone()
			);
			if ( $end_local < $now_local ) {
				$past_reservations[] = $reservation;
			} else {
				$upcoming_reservations[] = $reservation;
			}
		}
		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$interval = isset( $settings['slot_interval_minutes'] ) ? (int) $settings['slot_interval_minutes'] : 15;
		$hours = $this->get_opening_hours();
		$opening_settings = $this->get_opening_settings();
		$height_scale = 0.8;
		$grid_height = ( $hours['end'] - $hours['start'] ) * 60 * $height_scale;
		$total_minutes = max( 1, ( $hours['end'] - $hours['start'] ) * 60 );
		$px_per_minute = $grid_height / $total_minutes;
		$nonce = wp_create_nonce( 'salon_reservations_calendar' );
		$employee_color_map = array();
		foreach ( $employee_colors as $employee_key => $data ) {
			$employee_color_map[ $employee_key ] = $data['color'] ?? '#94a3b8';
		}
		$calendar = $this->build_calendar_grid(
			$range['days'],
			$calendar_reservations,
			$calendar_shifts,
			$employee_map,
			$employee_colors,
			$service_map,
			$hours['start'],
			$total_minutes,
			$px_per_minute
		);

		$container_class = $embedded ? 'salon-tabs__panel' : 'wrap';
		$sort_base = array(
			'page' => 'salon-reservations',
			'tab' => 'rezervacije',
		);
		if ( $status ) {
			$sort_base['status'] = $status;
		}
		if ( $employee_id ) {
			$sort_base['employee_id'] = $employee_id;
		}
		if ( $date_from ) {
			$sort_base['date_from'] = $date_from;
		}
		if ( $date_to ) {
			$sort_base['date_to'] = $date_to;
		}
		if ( ! empty( $selected_dates ) ) {
			$sort_base['dates'] = implode( ',', $selected_dates );
		}
		if ( isset( $_GET['year'] ) ) {
			$sort_base['year'] = $selected_year;
		}
		$build_sort_link = function( $key, $label ) use ( $sort_base, $order_by, $order ) {
			$is_current = $order_by === $key;
			$next_order = ( $is_current && $order === 'ASC' ) ? 'DESC' : 'ASC';
			$url = add_query_arg(
				array_merge(
					$sort_base,
					array(
						'orderby' => $key,
						'order' => $next_order,
					)
				),
				admin_url( 'admin.php' )
			);
			$indicator = $is_current ? ( $order === 'ASC' ? '▲' : '▼' ) : '';
			return '<a class="salon-sort-link" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ( $indicator ? ' <span class="salon-sort-indicator">' . esc_html( $indicator ) . '</span>' : '' ) . '</a>';
		};
		?>
		<div class="<?php echo esc_attr( $container_class ); ?>">
			<?php if ( $show_calendar ) : ?>
			<div class="salon-reservations-calendar">
				<?php $current_year = (int) date_i18n( 'Y' ); ?>
				<div class="salon-reservations-calendar__months">
					<span class="salon-reservations-calendar__year"><?php echo esc_html( $selected_year ); ?></span>
					<select class="salon-reservations-calendar__year-select" data-year-select>
						<?php
						$years = range( $current_year - 2, $current_year + 2 );
						if ( ! in_array( $selected_year, $years, true ) ) {
							$years[] = $selected_year;
							sort( $years );
						}
						foreach ( $years as $year ) :
							?>
							<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>>
								<?php echo esc_html( $year ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php
					for ( $month = 1; $month <= 12; $month++ ) :
						$date_key = sprintf( '%04d-%02d-01', $selected_year, $month );
						$label = DateTimeImmutable::createFromFormat( 'Y-m-d', $date_key, DateTimeHelper::wp_timezone() )->format( 'M' );
						$is_current_month = (int) date_i18n( 'n' ) === $month && $selected_year === $current_year;
						?>
						<button
							type="button"
							class="salon-reservations-calendar__month<?php echo $is_current_month ? ' is-current' : ''; ?>"
							data-date="<?php echo esc_attr( $date_key ); ?>"
						>
							<?php echo esc_html( $label ); ?>
						</button>
					<?php endfor; ?>
					<div class="salon-reservations-calendar__week-nav">
						<button type="button" class="salon-reservations-calendar__week-btn" data-salon-week-prev aria-label="<?php esc_attr_e( 'Prethodni tjedan', 'salon-reservations' ); ?>">&lsaquo;</button>
						<button type="button" class="salon-reservations-calendar__week-label" data-salon-week-label></button>
						<button type="button" class="salon-reservations-calendar__week-btn" data-salon-week-next aria-label="<?php esc_attr_e( 'Sljedeći tjedan', 'salon-reservations' ); ?>">&rsaquo;</button>
					</div>
				</div>

				<div class="salon-reservations-calendar__filters">
					<div class="salon-reservations-calendar__search">
						<input
							type="search"
							class="salon-reservations-calendar__search-input"
							placeholder="<?php echo esc_attr__( 'Pretraži klijenta', 'salon-reservations' ); ?>"
							data-reservation-search
						/>
					</div>
					<button type="button" class="salon-reservations-calendar__new" data-reservation-new>
						<?php esc_html_e( 'Nova rezervacija', 'salon-reservations' ); ?>
					</button>
					<div class="salon-reservations-calendar__chips">
						<?php foreach ( $employees as $employee ) : ?>
							<?php if ( ! $employee ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<?php
								$color = $employee_colors[ (int) $employee->ID ]['color'] ?? '#0f172a';
							?>
							<label class="salon-reservations-calendar__chip" style="--salon-color: <?php echo esc_attr( $color ); ?>;">
								<input type="checkbox" value="<?php echo esc_attr( $employee->ID ); ?>" checked data-reservation-employee-filter />
								<span class="salon-reservations-calendar__chip-name"><?php echo esc_html( $employee->display_name ); ?></span>
								<span class="salon-reservations-calendar__chip-check" aria-hidden="true"></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div
					class="salon-reservations-calendar__grid"
					data-salon-reservations-calendar
					data-start-hour="<?php echo esc_attr( $hours['start'] ); ?>"
					data-end-hour="<?php echo esc_attr( $hours['end'] ); ?>"
					data-interval="<?php echo esc_attr( $interval ); ?>"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-current-week-start="<?php echo esc_attr( $range['current_week_start'] ); ?>"
					data-today="<?php echo esc_attr( $range['today'] ); ?>"
				>
					<div class="salon-reservations-calendar__times">
						<div class="salon-reservations-calendar__time-header"></div>
						<div class="salon-reservations-calendar__time-body" style="height: <?php echo esc_attr( $grid_height ); ?>px;">
							<?php for ( $hour = $hours['start']; $hour <= $hours['end']; $hour++ ) : ?>
								<?php
									$offset_minutes = ( $hour - $hours['start'] ) * 60;
									$top = $offset_minutes * $px_per_minute;
								?>
								<div class="salon-reservations-calendar__time-label" style="top: <?php echo esc_attr( $top ); ?>px;">
									<?php echo esc_html( sprintf( '%02d', $hour ) ); ?>
								</div>
							<?php endfor; ?>
						</div>
					</div>

					<div class="salon-reservations-calendar__scroll">
						<div class="salon-reservations-calendar__days">
							<?php foreach ( $calendar as $day_key => $day ) : ?>
								<?php
									$is_holiday = $this->is_holiday_date( $day_key );
									$opening_day = $this->opening_for_date( $day_key, $opening_settings );
									$is_open = ! empty( $opening_day['open'] ) && ! $is_holiday;
									$open_start_minutes = $is_open ? $this->time_to_minutes( $opening_day['start'] ) : 0;
									$open_end_minutes = $is_open ? $this->time_to_minutes( $opening_day['end'] ) : 0;
									$open_start_offset = $is_open ? max( 0, $open_start_minutes - ( $hours['start'] * 60 ) ) : 0;
									$open_end_offset = $is_open ? min( $total_minutes, $open_end_minutes - ( $hours['start'] * 60 ) ) : 0;
									$day_date = DateTimeImmutable::createFromFormat( 'Y-m-d', $day_key, DateTimeHelper::wp_timezone() );
									$is_sunday = $day_date ? ( 7 === (int) $day_date->format( 'N' ) ) : false;
								?>
						<div class="salon-reservations-calendar__day<?php echo $is_open ? '' : ' is-closed'; ?><?php echo $is_sunday ? ' is-sunday' : ''; ?><?php echo $is_holiday ? ' is-holiday' : ''; ?>" data-date="<?php echo esc_attr( $day_key ); ?>">
								<div class="salon-reservations-calendar__day-header<?php echo $day_key === $range['today'] ? ' is-today' : ''; ?>">
									<span class="salon-reservations-calendar__day-name"><?php echo esc_html( $day['label'] ); ?></span>
									<span class="salon-reservations-calendar__day-date"><?php echo esc_html( $day['date_label'] ?? '' ); ?></span>
								</div>
									<div
										class="salon-reservations-calendar__day-body"
										style="height: <?php echo esc_attr( $grid_height ); ?>px;"
									>
										<?php if ( ! $is_open ) : ?>
											<div class="salon-reservations-calendar__closed" style="top: 0; height: <?php echo esc_attr( $grid_height ); ?>px;"></div>
										<?php else : ?>
											<?php if ( $open_start_offset > 0 ) : ?>
												<div class="salon-reservations-calendar__closed" style="top: 0; height: <?php echo esc_attr( $open_start_offset * $px_per_minute ); ?>px;"></div>
											<?php endif; ?>
											<?php if ( $open_end_offset < $total_minutes ) : ?>
												<div class="salon-reservations-calendar__closed" style="top: <?php echo esc_attr( $open_end_offset * $px_per_minute ); ?>px; height: <?php echo esc_attr( ( $total_minutes - $open_end_offset ) * $px_per_minute ); ?>px;"></div>
											<?php endif; ?>
										<?php endif; ?>

										<?php foreach ( $day['shifts'] as $shift ) : ?>
											<div
												class="salon-reservations-calendar__shift"
												data-employee-id="<?php echo esc_attr( $shift['employee_id'] ); ?>"
												data-start="<?php echo esc_attr( $shift['start'] ); ?>"
												data-end="<?php echo esc_attr( $shift['end'] ); ?>"
												style="top: <?php echo esc_attr( $shift['top'] ); ?>px; height: <?php echo esc_attr( $shift['height'] ); ?>px; --salon-color: <?php echo esc_attr( $shift['color'] ); ?>; --salon-color-bg: <?php echo esc_attr( $shift['bg'] ); ?>;"
												title="<?php echo esc_attr( $shift['employee_name'] ); ?>"
											></div>
										<?php endforeach; ?>

									<?php foreach ( $day['reservations'] as $reservation ) : ?>
										<div
											class="salon-reservations-calendar__block is-<?php echo esc_attr( $reservation['status'] ); ?>"
											data-reservation-id="<?php echo esc_attr( $reservation['id'] ); ?>"
											data-employee-id="<?php echo esc_attr( $reservation['employee_id'] ); ?>"
											data-service-id="<?php echo esc_attr( $reservation['service_id'] ); ?>"
											data-customer-name="<?php echo esc_attr( $reservation['customer_name'] ); ?>"
											data-customer-email="<?php echo esc_attr( $reservation['customer_email'] ); ?>"
											data-customer-phone="<?php echo esc_attr( $reservation['customer_phone'] ); ?>"
											data-customer-user-id="<?php echo esc_attr( $reservation['customer_user_id'] ); ?>"
											data-notes="<?php echo esc_attr( $reservation['notes'] ); ?>"
											data-addons="<?php echo esc_attr( $reservation['addons'] ?? '' ); ?>"
											data-status="<?php echo esc_attr( $reservation['status'] ); ?>"
											data-start="<?php echo esc_attr( $reservation['start'] ); ?>"
											data-end="<?php echo esc_attr( $reservation['end'] ); ?>"
											data-is-dark="<?php echo ! empty( $reservation['is_dark'] ) ? '1' : '0'; ?>"
											style="top: <?php echo esc_attr( $reservation['top'] ); ?>px; height: <?php echo esc_attr( $reservation['height'] ); ?>px; --salon-color: <?php echo esc_attr( $reservation['color'] ); ?>; --salon-color-bg: <?php echo esc_attr( $reservation['bg'] ?? $reservation['color'] ); ?>; --salon-color-border: <?php echo esc_attr( $reservation['border'] ?? $reservation['color'] ); ?>; --salon-color-border-expanded: <?php echo esc_attr( $reservation['border_expanded'] ?? $reservation['color'] ); ?>;"
										>
											<span class="salon-reservations-calendar__block-time"><?php echo esc_html( $reservation['time'] ); ?></span>
											<span class="salon-reservations-calendar__block-client"><?php echo esc_html( $reservation['customer_name'] ); ?></span>
											<span class="salon-reservations-calendar__block-service"><?php echo esc_html( $reservation['service_name'] ); ?></span>
											<?php if ( ! empty( $reservation['notes'] ) ) : ?>
												<span class="salon-reservations-calendar__block-notes"><?php esc_html_e( 'Napomena:', 'salon-reservations' ); ?> <?php echo esc_html( $reservation['notes'] ); ?></span>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<?php endif; ?>

			<?php if ( $show_calendar || $show_list ) : ?>
			<div class="salon-reservations-calendar__modal" data-reservation-modal hidden>
				<div class="salon-reservations-calendar__modal-overlay" data-reservation-modal-cancel></div>
				<div class="salon-reservations-calendar__modal-card" role="dialog" aria-modal="true">
					<h3><?php esc_html_e( 'Potvrdi promjenu rezervacije', 'salon-reservations' ); ?></h3>
					<p><strong><?php esc_html_e( 'Stari termin:', 'salon-reservations' ); ?></strong> <span data-reservation-old-time></span></p>
					<p><strong><?php esc_html_e( 'Stari zaposlenik:', 'salon-reservations' ); ?></strong> <span data-reservation-old-employee></span></p>
					<p><strong><?php esc_html_e( 'Novi termin:', 'salon-reservations' ); ?></strong> <span data-reservation-new-time></span></p>
					<p><strong><?php esc_html_e( 'Novi zaposlenik:', 'salon-reservations' ); ?></strong> <span data-reservation-new-employee></span></p>
					<div class="salon-reservations-calendar__modal-actions">
						<button type="button" class="button" data-reservation-modal-cancel><?php esc_html_e( 'Odustani', 'salon-reservations' ); ?></button>
						<button type="button" class="button button-primary" data-reservation-modal-confirm><?php esc_html_e( 'Potvrdi', 'salon-reservations' ); ?></button>
					</div>
				</div>
			</div>

			<div class="salon-reservations-calendar__modal" data-reservation-create-modal hidden>
				<div class="salon-reservations-calendar__modal-overlay" data-reservation-create-cancel></div>
				<div class="salon-reservations-calendar__modal-card" role="dialog" aria-modal="true">
					<h3><?php esc_html_e( 'Nova rezervacija', 'salon-reservations' ); ?></h3>
					<form data-reservation-create-form>
						<input type="hidden" name="reservation_id" id="salon-create-reservation-id" />
						<div class="salon-reservations-calendar__field">
							<label for="salon-create-employee"><?php esc_html_e( 'Zaposlenik', 'salon-reservations' ); ?></label>
							<select id="salon-create-employee" name="employee_id" required>
								<?php foreach ( $employees as $employee ) : ?>
									<?php if ( ! $employee ) : ?>
										<?php continue; ?>
									<?php endif; ?>
									<option value="<?php echo esc_attr( $employee->ID ); ?>"><?php echo esc_html( $employee->display_name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="salon-reservations-calendar__field">
							<label for="salon-create-service"><?php esc_html_e( 'Vrsta usluge', 'salon-reservations' ); ?></label>
							<select id="salon-create-service" name="service_id" required>
								<?php foreach ( $services->all() as $service ) : ?>
									<option value="<?php echo esc_attr( $service->id ); ?>" data-duration="<?php echo esc_attr( (int) $service->duration_minutes ); ?>">
										<?php echo esc_html( $service->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="salon-reservations-calendar__field-row">
							<div class="salon-reservations-calendar__field">
								<label for="salon-create-date"><?php esc_html_e( 'Datum', 'salon-reservations' ); ?></label>
								<input type="date" id="salon-create-date" name="date" required />
							</div>
							<div class="salon-reservations-calendar__field">
								<label for="salon-create-start"><?php esc_html_e( 'Početak', 'salon-reservations' ); ?></label>
								<input type="time" id="salon-create-start" name="start_time" required />
							</div>
							<div class="salon-reservations-calendar__field">
								<label for="salon-create-end"><?php esc_html_e( 'Kraj', 'salon-reservations' ); ?></label>
								<input type="time" id="salon-create-end" name="end_time" required />
							</div>
						</div>
						<div class="salon-reservations-calendar__field">
							<label for="salon-create-name"><?php esc_html_e( 'Klijent', 'salon-reservations' ); ?></label>
							<input type="text" id="salon-create-name" name="customer_name" list="salon-reservation-subscribers" required />
							<datalist id="salon-reservation-subscribers">
								<?php
								$subscribers = get_users(
									array(
										'orderby' => 'display_name',
										'order' => 'ASC',
									)
								);
								foreach ( $subscribers as $subscriber ) :
									$phone = get_user_meta( $subscriber->ID, 'salon_phone', true );
									if ( ! $phone ) {
										$phone = get_user_meta( $subscriber->ID, 'phone', true );
									}
									if ( ! $phone ) {
										$phone = get_user_meta( $subscriber->ID, 'billing_phone', true );
									}
									$label = $subscriber->display_name . ' | ' . $subscriber->user_email;
									?>
									<option value="<?php echo esc_attr( $label ); ?>" data-user-id="<?php echo esc_attr( $subscriber->ID ); ?>" data-email="<?php echo esc_attr( $subscriber->user_email ); ?>" data-phone="<?php echo esc_attr( $phone ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<input type="hidden" name="customer_user_id" id="salon-create-user-id" />
						</div>
						<div class="salon-reservations-calendar__field-row">
							<div class="salon-reservations-calendar__field">
								<label for="salon-create-email"><?php esc_html_e( 'Email', 'salon-reservations' ); ?></label>
								<input type="email" id="salon-create-email" name="customer_email" required />
							</div>
							<div class="salon-reservations-calendar__field">
								<label for="salon-create-phone"><?php esc_html_e( 'Telefon', 'salon-reservations' ); ?></label>
								<input type="text" id="salon-create-phone" name="customer_phone" />
							</div>
						</div>
						<div class="salon-reservations-calendar__field">
							<label for="salon-create-notes"><?php esc_html_e( 'Napomena', 'salon-reservations' ); ?></label>
							<textarea id="salon-create-notes" name="notes" rows="2"></textarea>
						</div>
						<div class="salon-reservations-calendar__field">
							<label for="salon-create-status"><?php esc_html_e( 'Status', 'salon-reservations' ); ?></label>
							<select id="salon-create-status" name="status">
								<option value="pending"><?php echo esc_html( StatusHelper::label( 'pending' ) ); ?></option>
								<option value="approved"><?php echo esc_html( StatusHelper::label( 'approved' ) ); ?></option>
								<option value="cancelled"><?php echo esc_html( StatusHelper::label( 'cancelled' ) ); ?></option>
							</select>
						</div>
						<div class="salon-reservations-calendar__create-message" data-reservation-create-message></div>
						<div class="salon-reservations-calendar__modal-actions">
							<button type="button" class="button" data-reservation-create-cancel><?php esc_html_e( 'Odustani', 'salon-reservations' ); ?></button>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Spremi', 'salon-reservations' ); ?></button>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $show_calendar ) : ?>
			<div class="salon-reservations-calendar__trash" data-salon-reservations-trash aria-hidden="true">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<span class="salon-reservations-calendar__trash-label"><?php esc_html_e( 'Obriši', 'salon-reservations' ); ?></span>
			</div>
			<?php endif; ?>

			<?php if ( $show_list ) : ?>
			<?php
			$render_reservations_table = function( $rows, $empty_message ) use ( $build_sort_link, $services ) {
				?>
				<table class="widefat striped salon-reservations-list__table">
					<colgroup>
						<col style="width: 4%;" />
						<col style="width: 8%;" />
						<col style="width: 9%;" />
						<col style="width: 12%;" />
						<col style="width: 20%;" />
						<col style="width: 14%;" />
						<col style="width: 13%;" />
						<col style="width: 20%;" />
					</colgroup>
					<thead>
						<tr>
							<th><?php echo $build_sort_link( 'id', __( 'ID', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'employee', __( 'Zaposlenik', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'service', __( 'Vrsta usluge', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'client', __( 'Klijent', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'options', __( 'Opcije', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'date', __( 'Datum/Vrijeme', 'salon-reservations' ) ); ?></th>
							<th><?php echo $build_sort_link( 'status', __( 'Status', 'salon-reservations' ) ); ?></th>
							<th><?php esc_html_e( 'Akcije', 'salon-reservations' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="8"><?php echo esc_html( $empty_message ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $rows as $reservation ) : ?>
								<?php
									$service = $services->get( $reservation->service_id );
									$employee = get_user_by( 'id', $reservation->employee_id );
									$start_local = DateTimeHelper::utc_to_local( $reservation->start_datetime, 'Y-m-d H:i' );
									$end_local = DateTimeHelper::utc_to_local( $reservation->end_datetime, 'Y-m-d H:i' );
									$start_display = DateTimeHelper::utc_to_local( $reservation->start_datetime, 'd.m.Y. H:i' );
									$action_url = wp_nonce_url(
										admin_url( 'admin.php?page=salon-reservations&tab=rezervacije&reservation_id=' . $reservation->id ),
										'salon_reservation_action_' . $reservation->id
									);
								?>
								<tr data-reservation-row="<?php echo esc_attr( $reservation->id ); ?>">
									<td><?php echo esc_html( $reservation->id ); ?></td>
									<td><?php echo esc_html( $employee ? $employee->display_name : __( 'Zaposlenik', 'salon-reservations' ) ); ?></td>
									<td><?php echo esc_html( $service ? $service->name : __( 'Vrsta usluge', 'salon-reservations' ) ); ?></td>
									<td>
										<?php echo esc_html( $reservation->customer_name ); ?><br />
										<?php echo esc_html( $reservation->customer_email ); ?>
									</td>
									<td><?php echo esc_html( $reservation->addons ? $reservation->addons : '-' ); ?></td>
									<td><?php echo esc_html( $start_display ); ?></td>
									<td>
										<?php
											$status_value = $reservation->status === 'denied' ? 'cancelled' : $reservation->status;
											$status_label = StatusHelper::label( $status_value );
											$status_class = 'salon-status-badge--' . sanitize_html_class( $status_value );
										?>
										<span class="salon-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
									</td>
									<td>
										<button
											type="button"
											class="button button-small salon-reservation-edit"
											data-reservation-id="<?php echo esc_attr( $reservation->id ); ?>"
											data-employee-id="<?php echo esc_attr( $reservation->employee_id ); ?>"
											data-service-id="<?php echo esc_attr( $reservation->service_id ); ?>"
											data-start="<?php echo esc_attr( $start_local ); ?>"
											data-end="<?php echo esc_attr( $end_local ); ?>"
											data-customer-name="<?php echo esc_attr( $reservation->customer_name ); ?>"
											data-customer-email="<?php echo esc_attr( $reservation->customer_email ); ?>"
											data-customer-phone="<?php echo esc_attr( $reservation->customer_phone ); ?>"
											data-customer-user-id="<?php echo esc_attr( $reservation->customer_user_id ); ?>"
											data-notes="<?php echo esc_attr( $reservation->notes ); ?>"
											data-status="<?php echo esc_attr( $reservation->status === 'denied' ? 'cancelled' : $reservation->status ); ?>"
										><?php esc_html_e( 'Uredi', 'salon-reservations' ); ?></button>
										<a
											class="button button-small salon-reservation-status"
											href="<?php echo esc_url( $action_url . '&salon_action=approve' ); ?>"
											data-confirm="<?php echo esc_attr( sprintf( __( 'Mijenjate status rezervacije na %s', 'salon-reservations' ), StatusHelper::label( 'approved' ) ) ); ?>"
										><?php esc_html_e( 'Odobri', 'salon-reservations' ); ?></a>
										<a
											class="button button-small salon-reservation-status"
											href="<?php echo esc_url( $action_url . '&salon_action=cancel' ); ?>"
											data-confirm="<?php echo esc_attr( sprintf( __( 'Mijenjate status rezervacije na %s', 'salon-reservations' ), StatusHelper::label( 'cancelled' ) ) ); ?>"
										><?php esc_html_e( 'Otkaži', 'salon-reservations' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				<?php
			};
			?>
			<form method="get" class="salon-admin-filters" data-reset-url="<?php echo esc_url( admin_url( 'admin.php?page=salon-reservations&tab=rezervacije' ) ); ?>">
				<input type="hidden" name="page" value="salon-reservations" />
				<input type="hidden" name="tab" value="rezervacije" />
				<select name="status">
					<option value=""><?php esc_html_e( 'Svi statusi', 'salon-reservations' ); ?></option>
					<?php foreach ( array( 'pending', 'approved', 'cancelled' ) as $status_option ) : ?>
						<option value="<?php echo esc_attr( $status_option ); ?>" <?php selected( $status, $status_option ); ?>><?php echo esc_html( StatusHelper::label( $status_option ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="employee_id">
					<option value="0"><?php esc_html_e( 'Svi zaposlenici', 'salon-reservations' ); ?></option>
					<?php foreach ( $employees as $employee ) : ?>
						<option value="<?php echo esc_attr( $employee->ID ); ?>" <?php selected( $employee_id, $employee->ID ); ?>><?php echo esc_html( $employee->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="salon-admin-filters__dates" data-date-filter>
					<input
						type="text"
						class="salon-admin-filters__date-input"
						readonly
						placeholder="<?php esc_attr_e( 'Odaberi datume', 'salon-reservations' ); ?>"
						aria-label="<?php esc_attr_e( 'Odaberi datume', 'salon-reservations' ); ?>"
						data-date-trigger
					/>
					<input
						type="hidden"
						name="dates"
						value="<?php echo esc_attr( implode( ',', $selected_dates ) ); ?>"
						data-date-values
					/>
					<div class="salon-admin-filters__calendar" data-date-calendar hidden>
						<div class="salon-admin-filters__calendar-header">
							<button type="button" class="salon-admin-filters__calendar-nav" data-calendar-prev aria-label="<?php esc_attr_e( 'Prethodni mjesec', 'salon-reservations' ); ?>">‹</button>
							<span class="salon-admin-filters__calendar-label" data-calendar-label></span>
							<button type="button" class="salon-admin-filters__calendar-nav" data-calendar-next aria-label="<?php esc_attr_e( 'Sljedeći mjesec', 'salon-reservations' ); ?>">›</button>
						</div>
						<div class="salon-admin-filters__calendar-weekdays">
							<span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
						</div>
						<div class="salon-admin-filters__calendar-grid" data-calendar-grid></div>
					</div>
				</div>
				<button type="button" class="button salon-admin-filters__reset"><?php esc_html_e( 'Resetiraj', 'salon-reservations' ); ?></button>
			</form>

			<h3 class="salon-reservations-list__heading"><?php esc_html_e( 'Aktivne i buduće rezervacije', 'salon-reservations' ); ?></h3>
			<?php $render_reservations_table( $upcoming_reservations, __( 'Nema aktivnih ili budućih rezervacija.', 'salon-reservations' ) ); ?>
			<h3 class="salon-reservations-list__heading salon-reservations-list__heading--past"><?php esc_html_e( 'Prošle rezervacije', 'salon-reservations' ); ?></h3>
			<?php $render_reservations_table( $past_reservations, __( 'Nema prošlih rezervacija.', 'salon-reservations' ) ); ?>
			<?php endif; ?>
		</div>

		<style>
			.salon-reservations-calendar { margin: 12px 0 24px; }
			.salon-reservations-calendar__months { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 8px 0 14px; padding-left: 34px; }
			.salon-reservations-calendar__week-nav { display: inline-flex; align-items: center; gap: 6px; margin-left: 6px; }
			.salon-reservations-calendar__week-btn { border: 1px solid rgba(148, 163, 184, 0.4); background: #fff; border-radius: 999px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #0f172a; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
			.salon-reservations-calendar__week-label { border: 1px solid rgba(148, 163, 184, 0.4); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; color: #0f172a; cursor: pointer; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
			.salon-reservations-calendar__year { font-size: 18px; font-weight: 700; color: #0f172a; margin-right: 2px; }
			.salon-reservations-calendar__year-select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px 8px; font-size: 12px; background: #fff; color: #0f172a; }
			.salon-reservations-calendar__month { border: 1px solid rgba(148, 163, 184, 0.4); font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; cursor: pointer; padding: 6px 10px; border-radius: 999px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0.15)); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), inset 0 -2px 6px rgba(15, 23, 42, 0.08), 0 6px 12px rgba(15, 23, 42, 0.08); backdrop-filter: blur(6px); }
			.salon-reservations-calendar__month.is-current { color: #0f172a; font-weight: 700; border-color: rgba(59, 130, 246, 0.45); background: linear-gradient(135deg, rgba(255,255,255,0.85), rgba(255,255,255,0.35)); box-shadow: inset 0 1px 2px rgba(255,255,255,0.85), inset 0 -2px 6px rgba(59,130,246,0.15), 0 8px 16px rgba(15,23,42,0.12); }
			.salon-reservations-calendar__filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; padding-left: 34px; }
			.salon-reservations-calendar__chips { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
			.salon-reservations-calendar__chip { position: relative; display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: var(--salon-color, #0f172a); color: #fff; cursor: pointer; font-size: 13px; box-shadow: 0 6px 12px rgba(15, 23, 42, 0.08); }
			.salon-reservations-calendar__chip input { position: absolute; opacity: 0; pointer-events: none; }
			.salon-reservations-calendar__chip-name { font-weight: 600; }
			.salon-reservations-calendar__chip-check { width: 20px; height: 20px; border-radius: 999px; background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), transparent 45%), rgba(255, 255, 255, 0.25); display: inline-flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 6px rgba(255, 255, 255, 0.35), inset 0 -4px 8px rgba(0, 0, 0, 0.18); font-size: 12px; }
			.salon-reservations-calendar__chip input:checked ~ .salon-reservations-calendar__chip-check::after { content: "✓"; color: #fff; font-weight: 700; }
			.salon-reservations-calendar__new { border: 1px solid rgba(148, 163, 184, 0.4); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #0f172a; cursor: pointer; padding: 8px 14px; border-radius: 999px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.25)); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.7), inset 0 -2px 6px rgba(15, 23, 42, 0.08), 0 6px 12px rgba(15, 23, 42, 0.08); backdrop-filter: blur(6px); }
			.salon-reservations-calendar__search { display: flex; align-items: center; }
			.salon-reservations-calendar__search-input { border: 1px solid rgba(148, 163, 184, 0.4); border-radius: 999px; padding: 8px 14px; font-size: 12px; min-width: 220px; background: #fff; box-shadow: none; color: #0f172a; outline: none; }
			.salon-reservations-calendar__search-input::placeholder { color: rgba(15, 23, 42, 0.55); }
			.salon-reservations-calendar__day.is-search-target { transform: scaleX(1.03); box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12); z-index: 3; }
			.salon-reservations-calendar__block.is-search-hit { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.45), 0 16px 30px rgba(15, 23, 42, 0.25); }
			.salon-reservations-calendar__block.is-search-glow { animation: salon-search-glow 1.2s ease-in-out 3; }
			.salon-sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
			.salon-sort-link:hover { text-decoration: underline; }
			.salon-sort-indicator { font-size: 10px; opacity: 0.7; }
			.salon-tabs .salon-admin-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; overflow: visible !important; position: relative; z-index: 30; }
			.salon-admin-filters__dates { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; position: relative; }
			.salon-admin-filters__date-input { min-width: 180px; cursor: pointer; }
			.salon-admin-filters__calendar { position: absolute; top: calc(100% + 6px); left: 0; z-index: 1000; background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(148, 163, 184, 0.4); border-radius: 14px; padding: 10px; box-shadow: 0 16px 24px rgba(15, 23, 42, 0.16); width: 250px; }
			.salon-admin-filters__calendar-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
			.salon-admin-filters__calendar-label { font-weight: 600; font-size: 12px; color: #0f172a; }
			.salon-admin-filters__calendar-nav { border: 1px solid rgba(148, 163, 184, 0.4); background: #fff; border-radius: 999px; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: inset 0 1px 2px rgba(255,255,255,0.7); }
			.salon-admin-filters__calendar-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 6px; text-align: center; }
			.salon-admin-filters__calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
			.salon-admin-filters__calendar-day { border: 1px solid rgba(148, 163, 184, 0.35); background: #fff; border-radius: 8px; font-size: 11px; padding: 6px 0; cursor: pointer; color: #0f172a; }
			.salon-admin-filters__calendar-day.is-selected { background: rgba(59, 130, 246, 0.16); border-color: rgba(59, 130, 246, 0.45); }
			.salon-admin-filters__calendar-day.is-preview { background: rgba(59, 130, 246, 0.12); border-color: rgba(59, 130, 246, 0.3); }
			.salon-admin-filters__calendar-empty { display: block; height: 26px; }
			.salon-admin-filters__chips { display: none; }
			.salon-admin-filters__chip { display: none !important; }
			.salon-admin-filters__reset { margin-left: auto; }
			.salon-reservations-list__heading { margin: 20px 0 10px; font-size: 15px; font-weight: 700; color: #0f172a; }
			.salon-reservations-list__heading--past { margin-top: 26px; }
			.salon-reservations-list__table { width: 100%; table-layout: fixed; }
			.salon-reservations-list__table th,
			.salon-reservations-list__table td { overflow-wrap: anywhere; }
			.salon-reservations-list__table td:last-child { white-space: nowrap; }
			.salon-reservations-list__table td:last-child .button { margin-right: 4px; }
			.salon-reservations-list__table td:last-child .button:last-child { margin-right: 0; }
			.salon-status-badge { display: inline-flex; align-items: center; justify-content: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; border: 1px solid transparent; width: 120px; text-align: center; }
			.salon-status-badge--approved { background: rgba(34, 197, 94, 0.15); color: #166534; border-color: rgba(34, 197, 94, 0.45); }
			.salon-status-badge--pending { background: rgba(250, 204, 21, 0.2); color: #854d0e; border-color: rgba(250, 204, 21, 0.6); }
			.salon-status-badge--cancelled { background: rgba(239, 68, 68, 0.15); color: #991b1b; border-color: rgba(239, 68, 68, 0.45); }
			@keyframes salon-search-glow {
				0% { box-shadow: 0 0 0 2px var(--salon-color-border-expanded, rgba(59, 130, 246, 0.4)), 0 10px 18px rgba(15, 23, 42, 0.2); }
				50% { box-shadow: 0 0 0 6px var(--salon-color-border-expanded, rgba(59, 130, 246, 0.45)), 0 18px 32px rgba(15, 23, 42, 0.28); }
				100% { box-shadow: 0 0 0 2px var(--salon-color-border-expanded, rgba(59, 130, 246, 0.4)), 0 10px 18px rgba(15, 23, 42, 0.2); }
			}
			.salon-reservations-calendar__grid { display: grid; grid-template-columns: 28px 1fr; gap: 6px; width: 100%; align-items: start; }
			.salon-reservations-calendar__times { display: flex; flex-direction: column; align-items: flex-end; }
			.salon-reservations-calendar__time-header { height: 48px; }
			.salon-reservations-calendar__time-body { position: relative; width: 100%; }
			.salon-reservations-calendar__time-label { position: absolute; right: 0; font-size: 10px; color: #94a3b8; transform: translateY(-6px); }
			.salon-reservations-calendar__scroll { position: relative; overflow-x: auto; overflow-y: hidden; padding-bottom: 4px; cursor: grab; }
			.salon-reservations-calendar__scroll.is-dragging { cursor: grabbing; }
			.salon-reservations-calendar__days { display: flex; gap: 8px; width: max-content; }
			.salon-reservations-calendar__day { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; min-width: 180px; transition: transform 180ms ease, box-shadow 180ms ease; transform-origin: center top; }
			.salon-reservations-calendar__day:hover { transform: scaleX(1.02); box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08); z-index: 2; }
			.salon-reservations-calendar__day.is-closed { min-width: 72px; width: 72px; flex: 0 0 72px; }
			.salon-reservations-calendar__day.is-sunday { min-width: 82px; width: 82px; flex: 0 0 82px; }
			.salon-reservations-calendar__day.is-holiday { min-width: 82px; width: 82px; flex: 0 0 82px; }
			.salon-reservations-calendar__day-header { height: 48px; display: flex; flex-direction: column; justify-content: center; padding: 6px 8px 4px; font-weight: 600; background: #f8fafc; border-bottom: 1px solid #e2e8f0; box-sizing: border-box; line-height: 1.1; gap: 2px; }
			.salon-reservations-calendar__day-header.is-today { background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(168,85,247,0.18)); border-bottom-color: rgba(168,85,247,0.35); box-shadow: inset 0 0 0 1px rgba(168,85,247,0.25); }
			.salon-reservations-calendar__day.is-sunday { background: #f1f5f9; }
			.salon-reservations-calendar__day.is-sunday .salon-reservations-calendar__day-body { background-color: rgba(148, 163, 184, 0.12); }
			.salon-reservations-calendar__day.is-sunday .salon-reservations-calendar__day-header { background: #e2e8f0; color: #64748b; }
			.salon-reservations-calendar__day.is-holiday { background: #f1f5f9; }
			.salon-reservations-calendar__day.is-holiday .salon-reservations-calendar__day-body { background-color: rgba(148, 163, 184, 0.12); }
			.salon-reservations-calendar__day.is-holiday .salon-reservations-calendar__day-header { background: #e2e8f0; color: #64748b; }
			.salon-reservations-calendar__trash { position: fixed; right: 24px; bottom: 24px; width: 70px; height: 70px; border-radius: 18px; background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(148, 163, 184, 0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: #0f172a; box-shadow: 0 18px 30px rgba(15, 23, 42, 0.2); opacity: 0; pointer-events: none; transform: translateY(8px) scale(0.96); transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; z-index: 9999; }
			.salon-reservations-calendar__trash.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0) scale(1); }
			.salon-reservations-calendar__trash.is-active { background: rgba(254, 226, 226, 0.95); border-color: rgba(248, 113, 113, 0.6); color: #b91c1c; box-shadow: 0 20px 36px rgba(185, 28, 28, 0.25); }
			.salon-reservations-calendar__trash .dashicons { font-size: 24px; width: 24px; height: 24px; }
			.salon-reservations-calendar__trash-label { font-size: 11px; font-weight: 600; letter-spacing: 0.02em; }
			.salon-reservations-calendar__day-date { font-size: 11px; font-weight: 500; color: #64748b; }
			.salon-reservations-calendar__day-body { position: relative; background-image: repeating-linear-gradient(to bottom, #f1f5f9 0, #f1f5f9 1px, transparent 1px, transparent 48px); overflow: visible; }
			.salon-reservations-calendar__closed { position: absolute; left: 0; right: 0; background: repeating-linear-gradient(45deg, rgba(148, 163, 184, 0.12) 0, rgba(148, 163, 184, 0.12) 6px, rgba(226, 232, 240, 0.3) 6px, rgba(226, 232, 240, 0.3) 12px); pointer-events: none; }
			.salon-reservations-calendar__shift { position: absolute; left: 4px; width: calc(100% - 8px); border-radius: 8px; background: var(--salon-color-bg, rgba(148, 163, 184, 0.14)); border: 1px solid var(--salon-color, #94a3b8); opacity: 0.35; z-index: 1; box-sizing: border-box; transition: transform 150ms ease, box-shadow 150ms ease, opacity 150ms ease; transform-origin: center; }
			.salon-reservations-calendar__shift.is-highlighted { opacity: 0.6; box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.12); }
			.salon-reservations-calendar__block { position: absolute; left: 4px; width: calc(100% - 8px); padding: 8px 8px 6px; border-radius: 8px; border: 1px solid var(--salon-color-border, var(--salon-color, #94a3b8)); border-left: 4px solid var(--salon-color-border, var(--salon-color, #94a3b8)); font-size: 12px; box-sizing: border-box; color: var(--salon-color-border, var(--salon-color, #94a3b8)); z-index: 2; transition: transform 150ms ease, box-shadow 150ms ease, height 150ms ease; transform-origin: center; background: var(--salon-color-bg, rgba(148, 163, 184, 0.18)); background-clip: padding-box; overflow: hidden; }
			.salon-reservations-calendar__block.is-expanded-height { padding-bottom: 24px; }
			.salon-reservations-calendar__block { cursor: grab; }
			.salon-reservations-calendar__block.is-dragging { opacity: 0.85; cursor: grabbing; }
			.salon-reservations-calendar__block.is-expanded { opacity: 1; background: var(--salon-color, #94a3b8); border-color: var(--salon-color-border-expanded, rgba(255, 255, 255, 0.75)); border-left-color: var(--salon-color-border-expanded, rgba(255, 255, 255, 0.75)); }
			.salon-reservations-calendar__block.is-expanded-height { overflow: visible; }
			.salon-reservations-calendar__block-time { display: block; color: inherit; font-weight: 700; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
			.salon-reservations-calendar__block-client { display: block; font-size: 12px; font-weight: 700; color: inherit; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
			.salon-reservations-calendar__block-service { display: block; font-size: 10.8px; font-weight: 400; color: inherit; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
			.salon-reservations-calendar__block-notes { display: block; font-size: 9.6px; font-weight: 400; font-style: italic; color: inherit; line-height: 1.2; white-space: normal; overflow: hidden; text-overflow: clip; margin-top: 4px; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-notes { padding-bottom: 10px; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-service:last-child { padding-bottom: 10px; }
			.salon-reservations-calendar__block.is-compact { padding: 4px; }
			.salon-reservations-calendar__block.is-compact .salon-reservations-calendar__block-time { font-size: 11px; }
			.salon-reservations-calendar__block.is-tight { padding: 2px 4px; }
			.salon-reservations-calendar__block.is-tight .salon-reservations-calendar__block-time { font-size: 10px; }
			.salon-reservations-calendar__shift.is-expanded { z-index: 6; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22); }
			.salon-reservations-calendar__block.is-expanded { z-index: 6; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22); opacity: 0.95; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-time { font-size: 18px; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-client { font-size: 18px; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-service { font-size: 16.2px; }
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-notes { font-size: 14.4px; }
			.salon-reservations-calendar__shift.is-shrunk,
			.salon-reservations-calendar__block.is-shrunk { transform: scaleX(0.92); }
			.salon-reservations-calendar__block.is-expanded-height,
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-time,
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-service,
			.salon-reservations-calendar__block.is-expanded-height .salon-reservations-calendar__block-client { white-space: normal; overflow: visible; text-overflow: initial; }
			.salon-reservations-calendar__ghost { position: absolute; left: 4px; width: calc(100% - 8px); border-radius: 8px; border: 2px dashed rgba(148, 163, 184, 0.8); background: rgba(148, 163, 184, 0.12); display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 700; font-size: 16px; z-index: 3; pointer-events: none; box-sizing: border-box; }
			.salon-reservations-calendar__field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; }
			.salon-reservations-calendar__field-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
			.salon-reservations-calendar__field input,
			.salon-reservations-calendar__field select,
			.salon-reservations-calendar__field textarea { padding: 6px 8px; border: 1px solid #e2e8f0; border-radius: 6px; }
			.salon-reservations-calendar__create-message { min-height: 18px; color: #dc2626; font-size: 12px; margin-top: 6px; }
			.salon-reservations-calendar__modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 9999; }
			.salon-reservations-calendar__modal[hidden] { display: none; }
			.salon-reservations-calendar__modal-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4); }
			.salon-reservations-calendar__modal-card { position: relative; background: #fff; padding: 20px 22px; border-radius: 12px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2); max-width: 360px; width: 100%; z-index: 2; }
			.salon-reservations-calendar__modal-card h3 { margin-top: 0; margin-bottom: 12px; }
			.salon-reservations-calendar__modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
		</style>

		<script>
			(function() {
				const calendar = document.querySelector('[data-salon-reservations-calendar]');
				const hasCalendar = !!calendar;

				const scroll = calendar ? calendar.querySelector('.salon-reservations-calendar__scroll') : null;
				const dayBodies = calendar ? calendar.querySelectorAll('.salon-reservations-calendar__day-body') : [];
				const reservationBlocks = calendar ? calendar.querySelectorAll('.salon-reservations-calendar__block') : [];
				const shiftBlocks = calendar ? calendar.querySelectorAll('.salon-reservations-calendar__shift') : [];
				const monthButtons = document.querySelectorAll('.salon-reservations-calendar__month');
				const todayDate = calendar ? (calendar.dataset.today || '') : '';
				const currentWeekStart = calendar ? (calendar.dataset.currentWeekStart || '') : '';
				const yearSelect = document.querySelector('[data-year-select]');
				const employeeFilters = document.querySelectorAll('[data-reservation-employee-filter]');
				const searchInput = document.querySelector('[data-reservation-search]');
				const weekPrev = document.querySelector('[data-salon-week-prev]');
				const weekNext = document.querySelector('[data-salon-week-next]');
				const weekLabel = document.querySelector('[data-salon-week-label]');
				const startHour = parseInt((calendar && calendar.dataset.startHour) ? calendar.dataset.startHour : '8', 10);
				const endHour = parseInt((calendar && calendar.dataset.endHour) ? calendar.dataset.endHour : '20', 10);
				const interval = parseInt((calendar && calendar.dataset.interval) ? calendar.dataset.interval : '15', 10);
				const ajaxUrl = (calendar && calendar.dataset.ajaxUrl) ? calendar.dataset.ajaxUrl : (window.ajaxurl || '');
				const nonce = (calendar && calendar.dataset.nonce) ? calendar.dataset.nonce : '';
				const modal = document.querySelector('[data-reservation-modal]');
				const modalOldTime = modal ? modal.querySelector('[data-reservation-old-time]') : null;
				const modalOldEmployee = modal ? modal.querySelector('[data-reservation-old-employee]') : null;
				const modalNewTime = modal ? modal.querySelector('[data-reservation-new-time]') : null;
				const modalNewEmployee = modal ? modal.querySelector('[data-reservation-new-employee]') : null;
				const modalCancel = modal ? modal.querySelectorAll('[data-reservation-modal-cancel]') : [];
				const modalConfirm = modal ? modal.querySelector('[data-reservation-modal-confirm]') : null;
				const trash = document.querySelector('[data-salon-reservations-trash]');
				const createModal = document.querySelector('[data-reservation-create-modal]');
				const createForm = createModal ? createModal.querySelector('[data-reservation-create-form]') : null;
				const createMessage = createModal ? createModal.querySelector('[data-reservation-create-message]') : null;
				const createCancel = createModal ? createModal.querySelectorAll('[data-reservation-create-cancel]') : [];
				const createTitle = createModal ? createModal.querySelector('h3') : null;
				const createEmployee = createModal ? createModal.querySelector('#salon-create-employee') : null;
				const createService = createModal ? createModal.querySelector('#salon-create-service') : null;
				const createDate = createModal ? createModal.querySelector('#salon-create-date') : null;
				const createStart = createModal ? createModal.querySelector('#salon-create-start') : null;
				const createEnd = createModal ? createModal.querySelector('#salon-create-end') : null;
				const createName = createModal ? createModal.querySelector('#salon-create-name') : null;
				const createEmail = createModal ? createModal.querySelector('#salon-create-email') : null;
				const createPhone = createModal ? createModal.querySelector('#salon-create-phone') : null;
				const createNotes = createModal ? createModal.querySelector('#salon-create-notes') : null;
				const createStatus = createModal ? createModal.querySelector('#salon-create-status') : null;
				const createUserId = createModal ? createModal.querySelector('#salon-create-user-id') : null;
				const createReservationId = createModal ? createModal.querySelector('#salon-create-reservation-id') : null;
				const subscriberOptions = Array.from(document.querySelectorAll('#salon-reservation-subscribers option'));
				let suppressAutoEnd = false;
				const newReservationButton = document.querySelector('[data-reservation-new]');
				const employeeNames = <?php echo wp_json_encode( $employee_map ); ?>;
				const employeeColors = <?php echo wp_json_encode( $employee_color_map ); ?>;
				const totalMinutes = (endHour - startHour) * 60;
				const pixelsPerMinute = dayBodies.length ? dayBodies[0].clientHeight / totalMinutes : 1;
				const minutesToPixels = (minutes) => minutes * pixelsPerMinute;
				const pixelsToMinutes = (pixels) => pixels / pixelsPerMinute;
				const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
				const minutesToTime = (minutes) => {
					const h = String(Math.floor(minutes / 60)).padStart(2, '0');
					const m = String(minutes % 60).padStart(2, '0');
					return `${h}:${m}`;
				};

				const parseDate = (value) => {
					const parts = (value || '').split('-').map((part) => parseInt(part, 10));
					if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
						return null;
					}
					return new Date(parts[0], parts[1] - 1, parts[2]);
				};

				const formatDate = (date) => {
					const year = date.getFullYear();
					const month = String(date.getMonth() + 1).padStart(2, '0');
					const day = String(date.getDate()).padStart(2, '0');
					return `${year}-${month}-${day}`;
				};

				const weekStartForDate = (date) => {
					const day = date.getDay();
					const diff = (day + 6) % 7;
					const monday = new Date(date);
					monday.setDate(date.getDate() - diff);
					return monday;
				};

				const isoWeekNumber = (date) => {
					const temp = new Date(date);
					temp.setHours(0, 0, 0, 0);
					temp.setDate(temp.getDate() + 3 - ((temp.getDay() + 6) % 7));
					const week1 = new Date(temp.getFullYear(), 0, 4);
					return 1 + Math.round(((temp - week1) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7);
				};

				const scrollToDate = (dateStr) => {
					if (!scroll || !dateStr) return;
					const dayEl = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${dateStr}"]`);
					if (dayEl) {
						scrollToDay(dayEl);
						return;
					}
					const days = scroll.querySelectorAll('.salon-reservations-calendar__day');
					if (!days.length) return;
					if (dateStr < days[0].dataset.date) {
						scrollToDay(days[0]);
						return;
					}
					scrollToDay(days[days.length - 1]);
				};
				const scrollToDateStart = (dateStr) => {
					if (!scroll || !dateStr) return;
					const dayEl = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${dateStr}"]`);
					if (dayEl) {
						scrollToDayStart(dayEl);
						return;
					}
					const days = scroll.querySelectorAll('.salon-reservations-calendar__day');
					if (!days.length) return;
					if (dateStr < days[0].dataset.date) {
						scrollToDayStart(days[0]);
						return;
					}
					scrollToDayStart(days[days.length - 1]);
				};

				const getCenterDate = () => {
					if (!scroll) return todayDate;
					const days = Array.from(scroll.querySelectorAll('.salon-reservations-calendar__day'));
					if (!days.length) return todayDate;
					const center = scroll.scrollLeft + scroll.clientWidth / 2;
					let closest = days[0];
					let min = Math.abs((closest.offsetLeft + closest.offsetWidth / 2) - center);
					days.forEach((day) => {
						const distance = Math.abs((day.offsetLeft + day.offsetWidth / 2) - center);
						if (distance < min) {
							min = distance;
							closest = day;
						}
					});
					return closest.dataset.date;
				};

				const getLeftDate = () => {
					if (!scroll) return todayDate;
					const days = Array.from(scroll.querySelectorAll('.salon-reservations-calendar__day'));
					if (!days.length) return todayDate;
					const leftEdge = scroll.scrollLeft + 1;
					let candidate = days[0];
					for (const day of days) {
						if (day.offsetLeft <= leftEdge) {
							candidate = day;
						} else {
							break;
						}
					}
					return candidate.dataset.date;
				};

				const updateWeekLabel = () => {
					if (!weekLabel) return;
					const baseDate = parseDate(getLeftDate()) || parseDate(currentWeekStart) || parseDate(todayDate);
					if (baseDate) {
						weekLabel.textContent = `Week ${isoWeekNumber(baseDate)}`;
					} else {
						weekLabel.textContent = '<?php echo esc_js( __( 'Week', 'salon-reservations' ) ); ?>';
					}
				};

				updateWeekLabel();

				const normalizeSearchTerm = (value) => (value || '').toString().trim().toLowerCase();
				const reservationTimestamp = (block) => {
					const start = block.dataset.start || '';
					if (!start) return 0;
					const parsed = Date.parse(start.replace(' ', 'T'));
					return Number.isNaN(parsed) ? 0 : parsed;
				};
				let searchState = { term: '', index: -1, matches: [] };
				let activeSearchHit = null;
				let activeSearchDay = null;
				const clearSearchHighlight = () => {
					if (activeSearchHit) {
						activeSearchHit.classList.remove('is-search-hit');
						collapseBlockHeight(activeSearchHit);
					}
					if (activeSearchDay) {
						activeSearchDay.classList.remove('is-search-target');
						const dayBody = activeSearchDay.querySelector('.salon-reservations-calendar__day-body');
						clearHoverFocus(dayBody);
					}
					activeSearchHit = null;
					activeSearchDay = null;
				};
				const focusSearchResult = (block) => {
					if (!block) return;
					clearSearchHighlight();
					activeSearchHit = block;
					activeSearchDay = block.closest('.salon-reservations-calendar__day');
					const dayBody = block.closest('.salon-reservations-calendar__day-body');
					block.classList.add('is-search-hit');
					block.classList.remove('is-search-glow');
					void block.offsetWidth;
					block.classList.add('is-search-glow');
					if (activeSearchDay) {
						activeSearchDay.classList.add('is-search-target');
						const date = activeSearchDay.dataset.date;
						if (date) {
							const dayEl = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${date}"]`);
							if (dayEl) {
								scrollToDay(dayEl);
							}
						}
					}
					if (dayBody) {
						setHoverFocus(dayBody, block.dataset.employeeId);
					}
					expandBlockHeight(block);
				};
				const runSearch = () => {
					if (!searchInput) return;
					const term = normalizeSearchTerm(searchInput.value);
					if (!term) {
						searchState = { term: '', index: -1, matches: [] };
						clearSearchHighlight();
						return;
					}
					if (term !== searchState.term) {
						searchState.term = term;
						searchState.index = -1;
						searchState.matches = Array.from(reservationBlocks)
							.filter((block) => block.style.display !== 'none')
							.filter((block) => normalizeSearchTerm(block.dataset.customerName).includes(term))
							.sort((a, b) => reservationTimestamp(a) - reservationTimestamp(b));
					}
					if (!searchState.matches.length) {
						clearSearchHighlight();
						return;
					}
					searchState.index = (searchState.index + 1) % searchState.matches.length;
					focusSearchResult(searchState.matches[searchState.index]);
				};

				const timeToMinutes = (dateTime) => {
					const time = dateTime.split(' ')[1] || dateTime.slice(11, 16);
					const parts = time.split(':');
					return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
				};

				const updateBlockLabels = (block, startMinutes, endMinutes) => {
					const dayDate = block.closest('.salon-reservations-calendar__day').dataset.date;
					const start = `${dayDate} ${minutesToTime(startMinutes)}`;
					const end = `${dayDate} ${minutesToTime(endMinutes)}`;
					block.dataset.start = start;
					block.dataset.end = end;
					const timeLabel = block.querySelector('.salon-reservations-calendar__block-time');
					if (timeLabel) {
						timeLabel.textContent = `${minutesToTime(startMinutes)} - ${minutesToTime(endMinutes)}`;
					}
				};

				const hexToTint = (hex, alpha) => {
					if (!hex) {
						return '#d5dbe3';
					}
					let normalized = hex.replace('#', '').trim();
					if (normalized.length === 3) {
						normalized = `${normalized[0]}${normalized[0]}${normalized[1]}${normalized[1]}${normalized[2]}${normalized[2]}`;
					}
					if (normalized.length !== 6) {
						return '#d5dbe3';
					}
					const r = parseInt(normalized.slice(0, 2), 16);
					const g = parseInt(normalized.slice(2, 4), 16);
					const b = parseInt(normalized.slice(4, 6), 16);
					const mix = (channel) => Math.round(255 * (1 - alpha) + channel * alpha);
					const toHex = (value) => value.toString(16).padStart(2, '0');
					return `#${toHex(mix(r))}${toHex(mix(g))}${toHex(mix(b))}`;
				};

				const applyEmployeeColor = (block, employeeId) => {
					const color = employeeColors[employeeId] || '#94a3b8';
					block.style.setProperty('--salon-color', color);
					block.style.setProperty('--salon-color-border', color);
					block.style.setProperty('--salon-color-border-expanded', hexToTint(color, 0.18));
				};

				const deleteReservation = async (block) => {
					const reservationId = block.dataset.reservationId || '';
					if (!reservationId) {
						block.remove();
						layoutAll();
						return;
					}
					const payload = new URLSearchParams();
					payload.set('action', 'salon_reservation_delete');
					payload.set('nonce', nonce);
					payload.set('reservation_id', reservationId);

					const response = await fetch(ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: payload.toString(),
					});

					const data = await response.json().catch(() => ({}));
					if (!response.ok || !data.success) {
						throw new Error(data.data && data.data.message ? data.data.message : 'error');
					}

					const row = document.querySelector(`[data-reservation-row="${reservationId}"]`);
					if (row) {
						row.remove();
					}
					block.remove();
					layoutAll();
				};

				const findShiftForEmployeeRange = (dayBody, employeeId, startMinutes, endMinutes) => {
					const shifts = Array.from(dayBody.querySelectorAll('.salon-reservations-calendar__shift'))
						.filter((shift) => shift.style.display !== 'none' && shift.dataset.employeeId === employeeId);
					for (const shift of shifts) {
						const shiftStart = timeToMinutes(shift.dataset.start);
						const shiftEnd = timeToMinutes(shift.dataset.end);
						if (startMinutes >= shiftStart && endMinutes <= shiftEnd) {
							return shift;
						}
					}
					return shifts[0] || null;
				};

				let activeShiftHighlight = null;
				const setShiftHighlight = (shiftEl) => {
					if (activeShiftHighlight && activeShiftHighlight !== shiftEl) {
						activeShiftHighlight.classList.remove('is-highlighted');
					}
					activeShiftHighlight = shiftEl;
					if (activeShiftHighlight) {
						activeShiftHighlight.classList.add('is-highlighted');
					}
				};

				const findShiftForRange = (dayBody, startMinutes, endMinutes, pointerX, pointerY) => {
					if (!dayBody) return null;
					const dayEl = dayBody.closest('.salon-reservations-calendar__day');
					if (dayEl && (dayEl.classList.contains('is-closed') || dayEl.classList.contains('is-holiday'))) {
						return null;
					}
					let preferredShift = null;
					if (typeof document.elementsFromPoint === 'function') {
						const elements = document.elementsFromPoint(pointerX, pointerY);
						preferredShift = elements.find((el) => el.classList && el.classList.contains('salon-reservations-calendar__shift')) || null;
					}

					const shifts = Array.from(dayBody.querySelectorAll('.salon-reservations-calendar__shift'))
						.filter((shift) => shift.style.display !== 'none');
					const matchesRange = (shift) => {
						const shiftStart = timeToMinutes(shift.dataset.start);
						const shiftEnd = timeToMinutes(shift.dataset.end);
						return startMinutes >= shiftStart && endMinutes <= shiftEnd;
					};

					if (preferredShift && dayBody.contains(preferredShift) && preferredShift.style.display !== 'none' && matchesRange(preferredShift)) {
						return preferredShift;
					}

					for (const shift of shifts) {
						if (matchesRange(shift)) {
							return shift;
						}
					}

					return null;
				};

				let modalState = null;
				const openModal = (data, onConfirm, onCancel) => {
					if (!modal) {
						onConfirm();
						return;
					}
					if (modalOldTime) modalOldTime.textContent = data.oldTime;
					if (modalOldEmployee) modalOldEmployee.textContent = data.oldEmployee;
					if (modalNewTime) modalNewTime.textContent = data.newTime;
					if (modalNewEmployee) modalNewEmployee.textContent = data.newEmployee;
					modal.hidden = false;
					modalState = { onConfirm, onCancel };
				};

				const closeModal = () => {
					if (!modal) return;
					modal.hidden = true;
					modalState = null;
				};

				if (modalConfirm) {
					modalConfirm.addEventListener('click', () => {
						if (!modalState) return;
						const handler = modalState.onConfirm;
						closeModal();
						handler();
					});
				}

				modalCancel.forEach((button) => {
					button.addEventListener('click', () => {
						if (!modalState) return;
						const handler = modalState.onCancel;
						closeModal();
						handler();
					});
				});

				const showTrash = () => {
					if (trash) {
						trash.classList.add('is-visible');
					}
				};

				const hideTrash = () => {
					if (trash) {
						trash.classList.remove('is-visible', 'is-active');
					}
				};

				const updateTrashHover = (event) => {
					if (!trash || !event) return false;
					const rect = trash.getBoundingClientRect();
					const over = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
					trash.classList.toggle('is-active', over);
					return over;
				};

				const showCreateModal = () => {
					if (!createModal) return;
					if (createMessage) createMessage.textContent = '';
					createModal.hidden = false;
				};

				const hideCreateModal = () => {
					if (!createModal) return;
					createModal.hidden = true;
				};

				const resetCreateForm = () => {
					if (!createForm) return;
					createForm.reset();
					if (createReservationId) createReservationId.value = '';
					if (createUserId) createUserId.value = '';
				};

				const updateEndTime = () => {
					if (suppressAutoEnd) return;
					if (!createService || !createStart || !createEnd) return;
					const duration = parseInt(createService.selectedOptions[0]?.dataset.duration || '30', 10);
					const startParts = (createStart.value || '').split(':');
					if (startParts.length !== 2) return;
					const startMinutes = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
					const endMinutes = startMinutes + duration;
					const endTime = `${String(Math.floor(endMinutes / 60)).padStart(2, '0')}:${String(endMinutes % 60).padStart(2, '0')}`;
					createEnd.value = endTime;
				};

				const openCreateFromSlot = (slot) => {
					if (!slot || !createModal) return;
					const dayDate = slot.dayBody.closest('.salon-reservations-calendar__day')?.dataset.date || '';
					const startTime = minutesToTime(slot.startMinutes);
					resetCreateForm();
					if (createTitle) createTitle.textContent = '<?php echo esc_js( __( 'Nova rezervacija', 'salon-reservations' ) ); ?>';
					if (createEmployee) createEmployee.value = slot.shiftEl.dataset.employeeId || '';
					if (createDate) createDate.value = dayDate;
					if (createStart) createStart.value = startTime;
					updateEndTime();
					showCreateModal();
				};

				const openEditModal = (data) => {
					if (!createModal || !data) return;
					resetCreateForm();
					suppressAutoEnd = true;
					if (createTitle) createTitle.textContent = '<?php echo esc_js( __( 'Uredi rezervaciju', 'salon-reservations' ) ); ?>';
					if (createReservationId) createReservationId.value = data.id || '';
					if (createEmployee) createEmployee.value = data.employeeId || '';
					if (createService) createService.value = data.serviceId || '';
					if (createDate) createDate.value = (data.start || '').slice(0, 10);
					if (createStart) createStart.value = (data.start || '').slice(11, 16);
					if (createEnd) createEnd.value = (data.end || '').slice(11, 16);
					if (createName) createName.value = data.customerName || '';
					if (createEmail) createEmail.value = data.customerEmail || '';
					if (createPhone) createPhone.value = data.customerPhone || '';
					if (createNotes) createNotes.value = data.notes || '';
					if (createStatus) createStatus.value = data.status || 'pending';
					if (createUserId) createUserId.value = data.customerUserId || '';
					suppressAutoEnd = false;
					showCreateModal();
				};

				const extractReservationData = (element) => ({
					id: element.dataset.reservationId || '',
					employeeId: element.dataset.employeeId || '',
					serviceId: element.dataset.serviceId || '',
					start: element.dataset.start || '',
					end: element.dataset.end || '',
					customerName: element.dataset.customerName || '',
					customerEmail: element.dataset.customerEmail || '',
					customerPhone: element.dataset.customerPhone || '',
					customerUserId: element.dataset.customerUserId || '',
					notes: element.dataset.notes || '',
					status: element.dataset.status || 'pending',
				});

				const resolveSubscriber = (value) => {
					const lookup = (value || '').trim().toLowerCase();
					const match = subscriberOptions.find((option) => (option.value || '').trim().toLowerCase() === lookup);
					if (!match) {
						if (createUserId) createUserId.value = '';
						return;
					}
					if (createUserId) createUserId.value = match.dataset.userId || '';
					if (createEmail) createEmail.value = match.dataset.email || '';
					if (createPhone) createPhone.value = match.dataset.phone || '';
				};

				if (createCancel.length) {
					createCancel.forEach((button) => {
						button.addEventListener('click', () => {
							hideCreateModal();
						});
					});
				}

				if (newReservationButton) {
					newReservationButton.addEventListener('click', () => {
						resetCreateForm();
						if (createTitle) createTitle.textContent = '<?php echo esc_js( __( 'Nova rezervacija', 'salon-reservations' ) ); ?>';
						showCreateModal();
					});
				}

				if (createService) {
					createService.addEventListener('change', updateEndTime);
				}
				if (createStart) {
					createStart.addEventListener('change', updateEndTime);
				}
				if (createName) {
					createName.addEventListener('change', () => resolveSubscriber(createName.value));
				}

				const scrollToDay = (dayEl) => {
					if (!scroll || !dayEl) return;
					const center = dayEl.offsetLeft + dayEl.offsetWidth / 2;
					const targetLeft = Math.max(0, center - scroll.clientWidth / 2);
					scroll.scrollTo({ left: targetLeft, behavior: 'smooth' });
				};
				const scrollToDayStart = (dayEl) => {
					if (!scroll || !dayEl) return;
					const targetLeft = Math.max(0, dayEl.offsetLeft);
					scroll.scrollTo({ left: targetLeft, behavior: 'smooth' });
				};

				const layoutDay = (dayBody, selector) => {
					const blocks = Array.from(dayBody.querySelectorAll(selector)).filter((block) => block.style.display !== 'none');
					if (!blocks.length) return;

					const events = blocks
						.map((block) => {
							const start = timeToMinutes(block.dataset.start);
							const end = timeToMinutes(block.dataset.end);
							return { block, start, end, column: 0 };
						})
						.sort((a, b) => a.start - b.start);

					const groups = [];
					let group = [];
					let groupEnd = -1;

					events.forEach((event) => {
						if (!group.length || event.start < groupEnd) {
							group.push(event);
							groupEnd = Math.max(groupEnd, event.end);
						} else {
							groups.push(group);
							group = [event];
							groupEnd = event.end;
						}
					});

					if (group.length) {
						groups.push(group);
					}

					groups.forEach((items) => {
						const columns = [];
						items.forEach((item) => {
							let colIndex = columns.findIndex((colEnd) => colEnd <= item.start);
							if (colIndex === -1) {
								colIndex = columns.length;
								columns.push(item.end);
							} else {
								columns[colIndex] = item.end;
							}
							item.column = colIndex;
						});

						const columnCount = columns.length || 1;
						items.forEach((item) => {
							const width = 100 / columnCount;
							const left = item.column * width;
							item.block.style.width = `calc(${width}% - 8px)`;
							item.block.style.left = `calc(${left}% + 4px)`;
						});
					});
				};

				const layoutAll = () => {
					dayBodies.forEach((dayBody) => {
						layoutDay(dayBody, '.salon-reservations-calendar__shift');
						alignReservationsToShifts(dayBody);
					});
					adjustBlockSizing();
				};

				const applyFilters = () => {
					if (!calendar) return;
					const selected = new Set(
						Array.from(employeeFilters)
							.filter((input) => input.checked)
							.map((input) => input.value)
					);
					calendar.querySelectorAll('[data-employee-id]').forEach((block) => {
						block.style.display = selected.has(block.dataset.employeeId) ? '' : 'none';
					});
					layoutAll();
				};

				let ghostBlock = null;
				const clearGhost = () => {
					if (ghostBlock && ghostBlock.parentElement) {
						ghostBlock.parentElement.removeChild(ghostBlock);
					}
					ghostBlock = null;
					setShiftHighlight(null);
				};

				const showGhost = (dayBody, shiftEl, startMinutes, endMinutes) => {
					if (!dayBody || !shiftEl) return;
					if (!ghostBlock) {
						ghostBlock = document.createElement('div');
						ghostBlock.className = 'salon-reservations-calendar__ghost';
						ghostBlock.textContent = '+';
					}
					if (ghostBlock.parentElement !== dayBody) {
						dayBody.appendChild(ghostBlock);
					}
					const computed = window.getComputedStyle(shiftEl);
					ghostBlock.style.left = shiftEl.style.left || computed.left;
					ghostBlock.style.width = shiftEl.style.width || computed.width;
					const offsetStart = startMinutes - startHour * 60;
					const heightMinutes = Math.max(interval, endMinutes - startMinutes);
					ghostBlock.style.top = `${minutesToPixels(offsetStart)}px`;
					ghostBlock.style.height = `${minutesToPixels(heightMinutes)}px`;
					ghostBlock.dataset.employeeId = shiftEl.dataset.employeeId || '';
					ghostBlock.dataset.date = dayBody.closest('.salon-reservations-calendar__day')?.dataset.date || '';
					ghostBlock.dataset.startMinutes = String(startMinutes);
					setShiftHighlight(shiftEl);
				};

				const resolveSlotFromEvent = (event) => {
					const dayBody = event.currentTarget;
					const rect = dayBody.getBoundingClientRect();
					let offsetY = clamp(event.clientY - rect.top, 0, rect.height);
					let startOffset = pixelsToMinutes(offsetY);
					startOffset = Math.round(startOffset / interval) * interval;
					startOffset = clamp(startOffset, 0, totalMinutes - interval);
					const startMinutes = startHour * 60 + startOffset;
					const endMinutes = startMinutes + interval;
					const shiftEl = findShiftForRange(dayBody, startMinutes, endMinutes, event.clientX, event.clientY);
					if (!shiftEl) return null;
					const hasOverlap = Array.from(dayBody.querySelectorAll('.salon-reservations-calendar__block'))
						.filter((block) => block.style.display !== 'none' && block.dataset.employeeId === shiftEl.dataset.employeeId)
						.some((block) => {
							const blockStart = timeToMinutes(block.dataset.start);
							const blockEnd = timeToMinutes(block.dataset.end);
							return startMinutes < blockEnd && endMinutes > blockStart;
						});
					if (hasOverlap) {
						return null;
					}
					return { dayBody, shiftEl, startMinutes, endMinutes };
				};

				const alignReservationsToShifts = (dayBody) => {
					const blocks = Array.from(dayBody.querySelectorAll('.salon-reservations-calendar__block'))
						.filter((block) => block.style.display !== 'none');
					blocks.forEach((block) => {
						const employeeId = block.dataset.employeeId;
						const startMinutes = timeToMinutes(block.dataset.start);
						const endMinutes = timeToMinutes(block.dataset.end);
						const shift = findShiftForEmployeeRange(dayBody, employeeId, startMinutes, endMinutes);
						if (shift) {
							const computed = window.getComputedStyle(shift);
							block.style.left = shift.style.left || computed.left;
							block.style.width = shift.style.width || computed.width;
						} else {
							block.style.left = '4px';
							block.style.width = 'calc(100% - 8px)';
						}
					});
				};

				const adjustBlockSizing = () => {
					if (!calendar) return;
					calendar.querySelectorAll('.salon-reservations-calendar__block').forEach((block) => {
						block.classList.remove('is-compact', 'is-tight');
						const height = block.clientHeight;
						if (height <= 22) {
							block.classList.add('is-tight');
						} else if (height <= 32) {
							block.classList.add('is-compact');
						}
					});
				};

				const formatEuDate = (dateStr) => {
					const parts = (dateStr || '').split('-');
					if (parts.length !== 3) return dateStr || '';
					return `${parts[2]}.${parts[1]}.${parts[0]}.`;
				};

				const formatRange = (start, end) => {
					if (!start || !end) return '';
					const date = formatEuDate(start.slice(0, 10));
					const startTime = start.slice(11, 16);
					const endTime = end.slice(11, 16);
					return `${date} ${startTime} - ${endTime}`;
				};

				const attachReservationDrag = (block) => {
					let origin = null;
					let grabOffset = 0;
					let duration = 0;
					let dragOverTrash = false;

					const onMove = (moveEvent) => {
						if (!origin) return;
						dragOverTrash = updateTrashHover(moveEvent);
						const elements = document.elementsFromPoint(moveEvent.clientX, moveEvent.clientY);
						const targetBody = elements.find((el) => el.classList && el.classList.contains('salon-reservations-calendar__day-body')) || origin.dayBody;
						const dayBody = targetBody || origin.dayBody;
						if (!dayBody) return;
						if (block.parentElement !== dayBody) {
							dayBody.appendChild(block);
						}

						const rect = dayBody.getBoundingClientRect();
						const maxTop = Math.max(0, dayBody.clientHeight - minutesToPixels(duration));
						let topPx = moveEvent.clientY - rect.top - grabOffset;
						topPx = clamp(topPx, 0, maxTop);
						let startOffset = pixelsToMinutes(topPx);
						startOffset = Math.round(startOffset / interval) * interval;
						startOffset = clamp(startOffset, 0, totalMinutes - duration);
						const startMinutes = startHour * 60 + startOffset;
						const endMinutes = startMinutes + duration;
						block.style.top = `${minutesToPixels(startOffset)}px`;
						updateBlockLabels(block, startMinutes, endMinutes);

						const shiftEl = findShiftForRange(dayBody, startMinutes, endMinutes, moveEvent.clientX, moveEvent.clientY);
						if (shiftEl) {
							setShiftHighlight(shiftEl);
							const computed = window.getComputedStyle(shiftEl);
							block.style.left = shiftEl.style.left || computed.left;
							block.style.width = shiftEl.style.width || computed.width;
						} else {
							setShiftHighlight(null);
							block.style.left = '4px';
							block.style.width = 'calc(100% - 8px)';
						}
					};

					const revert = () => {
						if (!origin) return;
						if (origin.dayBody && block.parentElement !== origin.dayBody) {
							origin.dayBody.appendChild(block);
						}
						block.dataset.employeeId = origin.employeeId;
						block.dataset.start = origin.start;
						block.dataset.end = origin.end;
						block.style.top = origin.top;
						block.style.left = origin.left;
						block.style.width = origin.width;
						applyEmployeeColor(block, origin.employeeId);
						const startMinutes = timeToMinutes(origin.start);
						const endMinutes = timeToMinutes(origin.end);
						updateBlockLabels(block, startMinutes, endMinutes);
						setShiftHighlight(null);
						layoutAll();
						block.dataset.origLeft = block.style.left || '';
						block.dataset.origWidth = block.style.width || '';
					};

					const onUp = (upEvent) => {
						document.removeEventListener('mousemove', onMove);
						document.removeEventListener('mouseup', onUp);
						block.classList.remove('is-dragging');
						setShiftHighlight(null);
						const droppedOnTrash = dragOverTrash || updateTrashHover(upEvent);
						hideTrash();
						if (droppedOnTrash) {
							if (!window.confirm('<?php echo esc_js( __( 'Obrisati rezervaciju?', 'salon-reservations' ) ); ?>')) {
								revert();
								return;
							}
							deleteReservation(block).catch((error) => {
								revert();
								window.alert(error.message || '<?php echo esc_js( __( 'Neuspješno brisanje rezervacije.', 'salon-reservations' ) ); ?>');
							});
							return;
						}

						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						if (!dayBody) {
							revert();
							return;
						}

						const newStart = block.dataset.start;
						const newEnd = block.dataset.end;
						const startMinutes = timeToMinutes(newStart);
						const endMinutes = timeToMinutes(newEnd);
						const shiftEl = findShiftForRange(dayBody, startMinutes, endMinutes, upEvent.clientX, upEvent.clientY);
						if (!shiftEl) {
							revert();
							window.alert('<?php echo esc_js( __( 'Odabrani termin nije unutar smjene.', 'salon-reservations' ) ); ?>');
							return;
						}

						const newEmployeeId = shiftEl.dataset.employeeId;
						block.dataset.employeeId = newEmployeeId;
						applyEmployeeColor(block, newEmployeeId);
						const computed = window.getComputedStyle(shiftEl);
						block.style.left = shiftEl.style.left || computed.left;
						block.style.width = shiftEl.style.width || computed.width;
						layoutAll();
						block.dataset.origLeft = block.style.left || '';
						block.dataset.origWidth = block.style.width || '';

						const oldEmployee = employeeNames[origin.employeeId] || '';
						const newEmployee = employeeNames[newEmployeeId] || '';
						const oldTime = formatRange(origin.start, origin.end);
						const newTime = formatRange(newStart, newEnd);
						if (newStart === origin.start && newEnd === origin.end && newEmployeeId === origin.employeeId) {
							return;
						}

						openModal(
							{
								oldTime,
								oldEmployee,
								newTime,
								newEmployee,
							},
							async () => {
								const payload = new URLSearchParams();
								payload.set('action', 'salon_reservation_move');
								payload.set('nonce', nonce);
								payload.set('reservation_id', block.dataset.reservationId || '');
								payload.set('employee_id', newEmployeeId);
								payload.set('start', newStart);
								payload.set('end', newEnd);

								try {
									const response = await fetch(ajaxUrl, {
										method: 'POST',
										credentials: 'same-origin',
										body: payload,
									});
									const data = await response.json();
									if (!data || !data.success) {
										throw new Error((data && data.data && data.data.message) ? data.data.message : 'Error');
									}
									origin = null;
								} catch (error) {
									revert();
									window.alert(error.message || '<?php echo esc_js( __( 'Neuspješno spremanje promjene.', 'salon-reservations' ) ); ?>');
								}
							},
							() => {
								revert();
							}
						);
					};

					block.addEventListener('mousedown', (event) => {
						if (event.button !== 0) return;
						event.preventDefault();
						event.stopPropagation();
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						showTrash();
						dragOverTrash = false;
						origin = {
							dayBody,
							start: block.dataset.start,
							end: block.dataset.end,
							employeeId: block.dataset.employeeId,
							top: block.style.top,
							left: block.style.left,
							width: block.style.width,
						};
						const blockRect = block.getBoundingClientRect();
						grabOffset = event.clientY - blockRect.top;
						duration = timeToMinutes(origin.end) - timeToMinutes(origin.start);
						block.classList.add('is-dragging');
						document.addEventListener('mousemove', onMove);
						document.addEventListener('mouseup', onUp);
					});
				};

				dayBodies.forEach((dayBody) => {
					dayBody.addEventListener('click', (event) => {
						if (event.target && event.target.closest('.salon-reservations-calendar__block')) {
							return;
						}
						const slot = resolveSlotFromEvent(event);
						if (!slot) {
							clearGhost();
							return;
						}
						showGhost(slot.dayBody, slot.shiftEl, slot.startMinutes, slot.endMinutes);
					});

					dayBody.addEventListener('dblclick', (event) => {
						if (event.target && event.target.closest('.salon-reservations-calendar__block')) {
							return;
						}
						const slot = resolveSlotFromEvent(event);
						if (!slot) return;
						showGhost(slot.dayBody, slot.shiftEl, slot.startMinutes, slot.endMinutes);
						openCreateFromSlot(slot);
					});

					dayBody.addEventListener('mouseleave', () => {
						clearHoverFocus(dayBody);
					});
				});

				document.addEventListener('click', (event) => {
					const inCreateModal = createModal ? createModal.contains(event.target) : false;
					if (!calendar || (!calendar.contains(event.target) && !inCreateModal)) {
						clearGhost();
					}
				});

				if (scroll) {
					const target = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${currentWeekStart}"]`)
						|| scroll.querySelector(`.salon-reservations-calendar__day[data-date="${todayDate}"]`)
						|| scroll.querySelector('.salon-reservations-calendar__day');
					if (target) {
						scroll.scrollLeft = Math.max(0, target.offsetLeft);
					}

					let isDown = false;
					let startX = 0;
					let scrollLeft = 0;
					let scrollRaf = null;

					scroll.addEventListener('mousedown', (event) => {
						if (event.target && event.target.closest('.salon-reservations-calendar__block')) {
							return;
						}
						isDown = true;
						scroll.classList.add('is-dragging');
						startX = event.pageX - scroll.offsetLeft;
						scrollLeft = scroll.scrollLeft;
					});
					scroll.addEventListener('mouseleave', () => {
						isDown = false;
						scroll.classList.remove('is-dragging');
					});
					scroll.addEventListener('mouseup', () => {
						isDown = false;
						scroll.classList.remove('is-dragging');
					});
					scroll.addEventListener('mousemove', (event) => {
						if (!isDown) return;
						event.preventDefault();
						const x = event.pageX - scroll.offsetLeft;
						const walk = x - startX;
						scroll.scrollLeft = scrollLeft - walk;
						if (!scrollRaf) {
							scrollRaf = window.requestAnimationFrame(() => {
								updateWeekLabel();
								scrollRaf = null;
							});
						}
					});
					scroll.addEventListener('scroll', () => {
						if (!scrollRaf) {
							scrollRaf = window.requestAnimationFrame(() => {
								updateWeekLabel();
								scrollRaf = null;
							});
						}
					});
				}

				if (monthButtons.length && scroll) {
					monthButtons.forEach((button) => {
						button.addEventListener('click', () => {
							const date = button.dataset.date;
							if (!date) return;
							const isCurrent = button.classList.contains('is-current');
							if (isCurrent && todayDate) {
								const todayEl = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${todayDate}"]`);
								scrollToDay(todayEl);
								return;
							}
							const dayEl = scroll.querySelector(`.salon-reservations-calendar__day[data-date="${date}"]`);
							if (dayEl) {
								scrollToDayStart(dayEl);
							}
						});
					});
				}

				if (weekPrev) {
					weekPrev.addEventListener('click', () => {
						const centerDate = parseDate(getCenterDate()) || parseDate(currentWeekStart) || parseDate(todayDate);
						if (!centerDate) return;
						const target = weekStartForDate(centerDate);
						target.setDate(target.getDate() - 7);
						scrollToDateStart(formatDate(target));
						updateWeekLabel();
					});
				}

				if (weekNext) {
					weekNext.addEventListener('click', () => {
						const centerDate = parseDate(getCenterDate()) || parseDate(currentWeekStart) || parseDate(todayDate);
						if (!centerDate) return;
						const target = weekStartForDate(centerDate);
						target.setDate(target.getDate() + 7);
						scrollToDateStart(formatDate(target));
						updateWeekLabel();
					});
				}

				if (weekLabel) {
					weekLabel.addEventListener('click', () => {
						if (!currentWeekStart) return;
						scrollToDateStart(currentWeekStart);
						updateWeekLabel();
					});
				}

				if (yearSelect) {
					yearSelect.addEventListener('change', () => {
						const value = yearSelect.value;
						const url = new URL(window.location.href);
						url.searchParams.set('year', value);
						window.location.assign(url.toString());
					});
				}

				if (createForm) {
					createForm.addEventListener('submit', async (event) => {
						event.preventDefault();
						if (!createEmployee || !createService || !createDate || !createStart || !createEnd) return;
						const date = createDate.value;
						const startTime = createStart.value;
						const endTime = createEnd.value;
						const startMinutes = timeToMinutes(`${date} ${startTime}`);
						const endMinutes = timeToMinutes(`${date} ${endTime}`);
						if (!date || !startTime || !endTime || endMinutes <= startMinutes) {
							if (createMessage) {
								createMessage.textContent = '<?php echo esc_js( __( 'Provjerite datum i vrijeme.', 'salon-reservations' ) ); ?>';
							}
							return;
						}

						const reservationId = createReservationId ? createReservationId.value : '';
						const payload = new URLSearchParams();
						payload.set('action', reservationId ? 'salon_reservation_update' : 'salon_reservation_create');
						payload.set('nonce', nonce);
						if (reservationId) {
							payload.set('reservation_id', reservationId);
						}
						payload.set('employee_id', createEmployee.value);
						payload.set('service_id', createService.value);
						payload.set('start', `${date} ${startTime}`);
						payload.set('end', `${date} ${endTime}`);
						payload.set('customer_name', createName ? createName.value : '');
						payload.set('customer_email', createEmail ? createEmail.value : '');
						payload.set('customer_phone', createPhone ? createPhone.value : '');
						payload.set('notes', createNotes ? createNotes.value : '');
						payload.set('status', createStatus ? createStatus.value : 'pending');
						payload.set('customer_user_id', createUserId ? createUserId.value : '');

						if (createMessage) {
							createMessage.textContent = '';
						}

						try {
							const response = await fetch(ajaxUrl, {
								method: 'POST',
								credentials: 'same-origin',
								headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
								body: payload.toString(),
							});
							const data = await response.json().catch(() => ({}));
							if (!response.ok || !data.success) {
								throw new Error(data.data && data.data.message ? data.data.message : 'error');
							}
							window.location.reload();
						} catch (error) {
							if (createMessage) {
								createMessage.textContent = error.message || '<?php echo esc_js( __( 'Neuspješno spremanje rezervacije.', 'salon-reservations' ) ); ?>';
							}
						}
					});
				}

				employeeFilters.forEach((input) => {
					input.addEventListener('change', applyFilters);
				});
				if (searchInput) {
					searchInput.addEventListener('keydown', (event) => {
						if (event.key === 'Enter') {
							event.preventDefault();
							runSearch();
						}
					});
					searchInput.addEventListener('search', runSearch);
					searchInput.addEventListener('input', () => {
						if (!searchInput.value) {
							searchState = { term: '', index: -1, matches: [] };
							clearSearchHighlight();
						}
					});
				}
				const listFilters = document.querySelector('.salon-admin-filters');
				if (listFilters) {
					const statusSelect = listFilters.querySelector('select[name="status"]');
					const employeeSelect = listFilters.querySelector('select[name="employee_id"]');
					[statusSelect, employeeSelect].forEach((select) => {
						if (select) {
							select.addEventListener('change', () => listFilters.submit());
						}
					});
					const dateInput = listFilters.querySelector('[data-date-trigger]');
					const dateValues = listFilters.querySelector('[data-date-values]');
					const dateChips = null;
					const dateCalendar = listFilters.querySelector('[data-date-calendar]');
					const calendarLabel = listFilters.querySelector('[data-calendar-label]');
					const calendarGrid = listFilters.querySelector('[data-calendar-grid]');
					const calendarPrev = listFilters.querySelector('[data-calendar-prev]');
					const calendarNext = listFilters.querySelector('[data-calendar-next]');
					const resetButton = listFilters.querySelector('.salon-admin-filters__reset');
					const parseDates = (value) => (value || '').split(',').map((item) => item.trim()).filter(Boolean);
					const formatChipDate = (value) => {
						const parts = value.split('-');
						if (parts.length !== 3) return value;
						return `${parts[2]}.${parts[1]}.${parts[0]}.`;
					};
					let selectedDates = dateValues ? parseDates(dateValues.value) : [];
					let currentMonth = null;
					let currentYear = null;
					let isDragging = false;
					let dragStart = null;
					let dragEnd = null;

					const normalizeDates = (dates) => {
						return Array.from(new Set(dates)).sort();
					};

					const setInputLabel = () => {
						if (!dateInput) {
							return;
						}
						if (!selectedDates.length) {
							dateInput.value = '';
							return;
						}
						const start = selectedDates[0];
						const end = selectedDates[selectedDates.length - 1];
						dateInput.value = start === end ? formatChipDate(start) : `${formatChipDate(start)} – ${formatChipDate(end)}`;
					};

					const syncDateChips = () => {
						selectedDates = normalizeDates(selectedDates);
						if (dateValues) {
							dateValues.value = selectedDates.join(',');
						}
						setInputLabel();
						return;
					};

					const buildDateRange = (start, end) => {
						const result = [];
						if (!start || !end) {
							return result;
						}
						const startDate = new Date(start);
						const endDate = new Date(end);
						const min = startDate <= endDate ? startDate : endDate;
						const max = startDate <= endDate ? endDate : startDate;
						const cursor = new Date(min);
						while (cursor <= max) {
							result.push(cursor.toISOString().slice(0, 10));
							cursor.setDate(cursor.getDate() + 1);
						}
						return result;
					};

					const updateRangeHighlight = () => {
						if (!calendarGrid) {
							return;
						}
						const range = buildDateRange(dragStart, dragEnd);
						calendarGrid.querySelectorAll('[data-date]').forEach((cell) => {
							const value = cell.dataset.date;
							cell.classList.toggle('is-selected', selectedDates.includes(value));
							cell.classList.toggle('is-preview', range.includes(value));
						});
					};

					const renderCalendar = (year, month) => {
						if (!calendarGrid || !calendarLabel) {
							return;
						}
						const monthStart = new Date(year, month, 1);
						const monthName = monthStart.toLocaleString('en-US', { month: 'long' });
						calendarLabel.textContent = `${monthName} ${year}`;
						const startDay = (monthStart.getDay() + 6) % 7;
						const daysInMonth = new Date(year, month + 1, 0).getDate();
						const cells = [];
						for (let i = 0; i < startDay; i += 1) {
							cells.push('<span class="salon-admin-filters__calendar-empty"></span>');
						}
						for (let day = 1; day <= daysInMonth; day += 1) {
							const date = new Date(year, month, day);
							const iso = date.toISOString().slice(0, 10);
							cells.push(`<button type="button" class="salon-admin-filters__calendar-day" data-date="${iso}">${day}</button>`);
						}
						calendarGrid.innerHTML = cells.join('');
						calendarGrid.querySelectorAll('.salon-admin-filters__calendar-day').forEach((dayButton) => {
							dayButton.addEventListener('mousedown', (event) => {
								event.preventDefault();
								isDragging = true;
								dragStart = dayButton.dataset.date;
								dragEnd = dragStart;
								updateRangeHighlight();
							});
							dayButton.addEventListener('mouseenter', () => {
								if (!isDragging) return;
								dragEnd = dayButton.dataset.date;
								updateRangeHighlight();
							});
						});
						updateRangeHighlight();
					};

					const openCalendar = () => {
						if (!dateCalendar) {
							return;
						}
						if (currentMonth === null || currentYear === null) {
							const baseDate = selectedDates.length ? new Date(selectedDates[0]) : new Date();
							currentMonth = baseDate.getMonth();
							currentYear = baseDate.getFullYear();
						}
						dateCalendar.hidden = false;
						renderCalendar(currentYear, currentMonth);
					};

					const closeCalendar = () => {
						if (!dateCalendar) {
							return;
						}
						dateCalendar.hidden = true;
					};

					syncDateChips();
					if (dateInput) {
						dateInput.addEventListener('click', (event) => {
							event.preventDefault();
							if (dateCalendar && !dateCalendar.hidden) {
								closeCalendar();
							} else {
								openCalendar();
							}
						});
					}
					if (calendarPrev) {
						calendarPrev.addEventListener('click', () => {
							if (currentMonth === null || currentYear === null) {
								return;
							}
							currentMonth -= 1;
							if (currentMonth < 0) {
								currentMonth = 11;
								currentYear -= 1;
							}
							renderCalendar(currentYear, currentMonth);
						});
					}
					if (calendarNext) {
						calendarNext.addEventListener('click', () => {
							if (currentMonth === null || currentYear === null) {
								return;
							}
							currentMonth += 1;
							if (currentMonth > 11) {
								currentMonth = 0;
								currentYear += 1;
							}
							renderCalendar(currentYear, currentMonth);
						});
					}
					document.addEventListener('mouseup', () => {
						if (!isDragging) {
							return;
						}
						isDragging = false;
						if (!dragStart || !dragEnd) {
							return;
						}
						selectedDates = buildDateRange(dragStart, dragEnd);
						dragStart = null;
						dragEnd = null;
						syncDateChips();
						listFilters.submit();
					});
					document.addEventListener('click', (event) => {
						if (!dateCalendar || dateCalendar.hidden) {
							return;
						}
						if (listFilters.contains(event.target)) {
							if (dateCalendar.contains(event.target) || dateInput === event.target) {
								return;
							}
						}
						closeCalendar();
					});
					if (resetButton) {
						resetButton.addEventListener('click', () => {
							const resetUrl = listFilters.dataset.resetUrl || 'admin.php?page=salon-reservations&tab=rezervacije';
							window.location.href = resetUrl;
						});
					}
				}

				document.querySelectorAll('.salon-reservation-status').forEach((link) => {
					link.addEventListener('click', (event) => {
						const message = link.getAttribute('data-confirm');
						if (message && !window.confirm(message)) {
							event.preventDefault();
						}
					});
				});

				const setHoverFocus = (dayBody, employeeId) => {
					if (!dayBody) return;
					const items = dayBody.querySelectorAll('.salon-reservations-calendar__shift, .salon-reservations-calendar__block');
					const dayWidth = dayBody.clientWidth;
					items.forEach((item) => {
						if (item.dataset.origLeft === undefined) {
							item.dataset.origLeft = item.style.left || '';
						}
						if (item.dataset.origWidth === undefined) {
							item.dataset.origWidth = item.style.width || '';
						}
						if (item.dataset.origHeight === undefined) {
							item.dataset.origHeight = item.style.height || `${item.offsetHeight}px`;
						}
						if (item.classList.contains('salon-reservations-calendar__block')) {
							item.classList.remove('is-expanded-height');
							item.style.height = item.dataset.origHeight || `${item.offsetHeight}px`;
						}
						const matches = item.dataset.employeeId === employeeId;
						item.classList.toggle('is-expanded', matches);
						item.classList.toggle('is-shrunk', !matches);
						if (!matches) {
							item.classList.remove('is-expanded-height');
							if (item.dataset.origHeight !== undefined) {
								item.style.height = item.dataset.origHeight;
							}
						}
						if (matches) {
							const targetWidth = dayWidth;
							const itemWidth = item.offsetWidth;
							const currentLeft = item.offsetLeft;
							const desiredLeft = Math.max(0, Math.min(dayWidth - targetWidth, currentLeft - (targetWidth - itemWidth) / 2));
							item.style.width = `${targetWidth}px`;
							item.style.left = `${desiredLeft}px`;
							if (item.classList.contains('salon-reservations-calendar__shift')) {
								item.style.opacity = '0.95';
							}
							if (item.classList.contains('salon-reservations-calendar__block')) {
								item.style.color = item.style.getPropertyValue('--salon-color-border-expanded') || item.style.getPropertyValue('--salon-color-border') || item.style.getPropertyValue('--salon-color');
							}
						}
					});
				};

				const clearHoverFocus = (dayBody) => {
					if (!dayBody) return;
					const items = dayBody.querySelectorAll('.salon-reservations-calendar__shift, .salon-reservations-calendar__block');
					items.forEach((item) => {
						item.classList.remove('is-expanded', 'is-shrunk');
						item.classList.remove('is-expanded-height');
						item.style.removeProperty('opacity');
						item.style.removeProperty('color');
						if (item.dataset.origWidth !== undefined) {
							item.style.width = item.dataset.origWidth;
							delete item.dataset.origWidth;
						}
						if (item.dataset.origLeft !== undefined) {
							item.style.left = item.dataset.origLeft;
							delete item.dataset.origLeft;
						}
						if (item.dataset.origHeight !== undefined) {
							item.style.height = item.dataset.origHeight || `${item.offsetHeight}px`;
							delete item.dataset.origHeight;
						}
					});
				};

				const expandBlockHeight = (block) => {
					if (!block) return;
					block.classList.add('is-expanded-height');
					const contentHeight = block.scrollHeight;
					const currentHeight = block.offsetHeight;
					const extraPadding = 10;
					if (contentHeight + extraPadding > currentHeight) {
						block.style.height = `${contentHeight + extraPadding}px`;
					}
				};

				const collapseBlockHeight = (block) => {
					if (!block) return;
					if (block.dataset.origHeight !== undefined) {
						block.style.height = block.dataset.origHeight;
					}
					block.classList.remove('is-dark');
					block.classList.remove('is-expanded-height');
				};

				shiftBlocks.forEach((block) => {
					block.addEventListener('mouseenter', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						setHoverFocus(dayBody, block.dataset.employeeId);
					});
					block.addEventListener('mouseleave', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						clearHoverFocus(dayBody);
					});
					block.addEventListener('pointerleave', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						clearHoverFocus(dayBody);
					});
				});

				reservationBlocks.forEach((block) => {
					attachReservationDrag(block);
					block.addEventListener('mouseenter', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						setHoverFocus(dayBody, block.dataset.employeeId);
						expandBlockHeight(block);
					});
					block.addEventListener('mouseleave', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						clearHoverFocus(dayBody);
						collapseBlockHeight(block);
					});
					block.addEventListener('pointerleave', () => {
						const dayBody = block.closest('.salon-reservations-calendar__day-body');
						clearHoverFocus(dayBody);
						collapseBlockHeight(block);
					});
					block.addEventListener('dblclick', (event) => {
						event.preventDefault();
						event.stopPropagation();
						openEditModal(extractReservationData(block));
					});
				});

				document.querySelectorAll('.salon-reservation-edit').forEach((button) => {
					button.addEventListener('click', () => {
						openEditModal(extractReservationData(button));
					});
				});
				layoutAll();
				applyFilters();
			})();
		</script>
		<?php
	}

	private function can_view_reservations() {
		return current_user_can( Capabilities::MANAGE_RESERVATIONS ) || current_user_can( Capabilities::VIEW_RESERVATIONS_OWN );
	}

	private function can_edit_reservations() {
		return current_user_can( Capabilities::MANAGE_RESERVATIONS ) || current_user_can( Capabilities::VIEW_RESERVATIONS_OWN );
	}

	private function get_employees() {
		return get_users(
			array(
				'role__in' => array( 'editor' ),
				'orderby' => 'display_name',
				'order' => 'ASC',
			)
		);
	}

	private function build_employee_map( $employees ) {
		$map = array();
		foreach ( $employees as $employee ) {
			if ( ! $employee ) {
				continue;
			}
			$map[ (int) $employee->ID ] = $employee->display_name;
		}
		return $map;
	}

	private function build_employee_colors( $employees ) {
		$colors = array();
		foreach ( $employees as $employee ) {
			if ( ! $employee ) {
				continue;
			}
			$color = $this->get_employee_color( $employee->ID );
			$colors[ (int) $employee->ID ] = array(
				'color' => $color,
				'bg' => $this->color_to_rgba( $color, 0.18 ),
			);
		}
		return $colors;
	}

	private function build_services_map( $services ) {
		$map = array();
		foreach ( $services as $service ) {
			$map[ (int) $service->id ] = $service->name;
		}
		return $map;
	}

	private function get_employee_color( $user_id ) {
		$stored = get_user_meta( $user_id, 'salon_shift_color', true );
		$color = sanitize_hex_color( $stored );
		if ( ! $color ) {
			$palette = array( '#0ea5e9', '#10b981', '#f97316', '#a855f7', '#ef4444', '#14b8a6', '#f59e0b' );
			$color = $palette[ $user_id % count( $palette ) ];
		}
		return $color;
	}

	private function color_to_rgba( $hex, $alpha ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return sprintf( 'rgba(148, 163, 184, %.2f)', $alpha );
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return sprintf( 'rgba(%d, %d, %d, %.2f)', $r, $g, $b, $alpha );
	}

	private function color_to_tint( $hex, $alpha ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#d5dbe3';
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		$mix = function( $channel ) use ( $alpha ) {
			return (int) round( 255 * ( 1 - $alpha ) + $channel * $alpha );
		};
		return sprintf( '#%02x%02x%02x', $mix( $r ), $mix( $g ), $mix( $b ) );
	}

	private function is_dark_color( $hex ) {
		$hex = ltrim( trim( (string) $hex ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return false;
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		$luma = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b ) / 255;
		return $luma < 0.5;
	}

	private function build_calendar_grid( $days, $reservations, $shifts, $employee_map, $employee_colors, $service_map, $start_hour, $total_minutes, $px_per_minute ) {
		$grid = array();
		foreach ( $days as $day ) {
			$grid[ $day['key'] ] = array(
				'label' => $day['label'],
				'date_label' => $day['date_label'] ?? '',
				'reservations' => array(),
				'shifts' => array(),
			);
		}

		foreach ( $shifts as $shift ) {
			if ( 'cancelled' === $shift->status ) {
				continue;
			}
			$start_local = DateTimeHelper::utc_to_local( $shift->start_datetime, 'Y-m-d H:i' );
			$end_local = DateTimeHelper::utc_to_local( $shift->end_datetime, 'Y-m-d H:i' );
			$date_key = substr( $start_local, 0, 10 );
			if ( ! isset( $grid[ $date_key ] ) ) {
				continue;
			}
			if ( substr( $end_local, 0, 10 ) !== $date_key ) {
				continue;
			}
			$start_time = substr( $start_local, 11, 5 );
			$end_time = substr( $end_local, 11, 5 );
			$start_minutes = $this->time_to_minutes( $start_time );
			$end_minutes = $this->time_to_minutes( $end_time );
			$offset_start = max( 0, min( $total_minutes, $start_minutes - ( $start_hour * 60 ) ) );
			$offset_end = max( 0, min( $total_minutes, $end_minutes - ( $start_hour * 60 ) ) );
			if ( $offset_end <= $offset_start ) {
				continue;
			}

			$employee_id = (int) $shift->employee_id;
			$color = $employee_colors[ $employee_id ]['color'] ?? '#94a3b8';
			$bg = $employee_colors[ $employee_id ]['bg'] ?? 'rgba(148, 163, 184, 0.18)';
			$border_expanded = $this->color_to_tint( $color, 0.18 );

			$grid[ $date_key ]['shifts'][] = array(
				'id' => $shift->id,
				'employee_id' => $employee_id,
				'employee_name' => $employee_map[ $employee_id ] ?? __( 'Zaposlenik', 'salon-reservations' ),
				'start' => $date_key . ' ' . $start_time,
				'end' => $date_key . ' ' . $end_time,
				'top' => $offset_start * $px_per_minute,
				'height' => ( $offset_end - $offset_start ) * $px_per_minute,
				'color' => $color,
				'bg' => $bg,
				'border' => $color,
				'border_expanded' => $border_expanded,
				'time' => $start_time . ' - ' . $end_time,
			);
		}

		foreach ( $reservations as $reservation ) {
			if ( $reservation->status !== 'approved' ) {
				continue;
			}
			$start_local = DateTimeHelper::utc_to_local( $reservation->start_datetime, 'Y-m-d H:i' );
			$end_local = DateTimeHelper::utc_to_local( $reservation->end_datetime, 'Y-m-d H:i' );
			$date_key = substr( $start_local, 0, 10 );
			if ( ! isset( $grid[ $date_key ] ) ) {
				continue;
			}
			if ( substr( $end_local, 0, 10 ) !== $date_key ) {
				continue;
			}
			$start_time = substr( $start_local, 11, 5 );
			$end_time = substr( $end_local, 11, 5 );
			$start_minutes = $this->time_to_minutes( $start_time );
			$end_minutes = $this->time_to_minutes( $end_time );
			$offset_start = max( 0, min( $total_minutes, $start_minutes - ( $start_hour * 60 ) ) );
			$offset_end = max( 0, min( $total_minutes, $end_minutes - ( $start_hour * 60 ) ) );
			if ( $offset_end <= $offset_start ) {
				continue;
			}

			$employee_id = (int) $reservation->employee_id;
			$color = $employee_colors[ $employee_id ]['color'] ?? '#94a3b8';
			$bg = $employee_colors[ $employee_id ]['bg'] ?? 'rgba(148, 163, 184, 0.18)';
			$border_expanded = $this->color_to_tint( $color, 0.18 );
			$service_name = $service_map[ (int) $reservation->service_id ] ?? __( 'Vrsta usluge', 'salon-reservations' );
			$is_dark = $this->is_dark_color( $color );

			$grid[ $date_key ]['reservations'][] = array(
				'id' => $reservation->id,
				'employee_id' => $employee_id,
				'employee_name' => $employee_map[ $employee_id ] ?? __( 'Zaposlenik', 'salon-reservations' ),
				'customer_name' => $reservation->customer_name,
				'customer_email' => $reservation->customer_email,
				'customer_phone' => $reservation->customer_phone,
				'customer_user_id' => $reservation->customer_user_id,
				'notes' => $reservation->notes,
				'addons' => $reservation->addons,
				'service_id' => $reservation->service_id,
				'service_name' => $service_name,
				'start' => $date_key . ' ' . $start_time,
				'end' => $date_key . ' ' . $end_time,
				'top' => $offset_start * $px_per_minute,
				'height' => ( $offset_end - $offset_start ) * $px_per_minute,
				'time' => $start_time . ' - ' . $end_time,
				'color' => $color,
				'bg' => $bg,
				'border' => $color,
				'border_expanded' => $border_expanded,
				'is_dark' => $is_dark,
				'status' => $this->normalize_status_class( $reservation->status ),
			);
		}

		return $grid;
	}

	private function normalize_status_class( $status ) {
		$status = sanitize_text_field( $status );
		$allowed = array( 'approved', 'pending', 'cancelled' );
		return in_array( $status, $allowed, true ) ? $status : 'pending';
	}

	private function current_range_local( $year ) {
		$tz = DateTimeHelper::wp_timezone();
		$now = new \DateTimeImmutable( 'now', $tz );
		$current_week_start = $now->modify( 'monday this week' );
		$year = (int) $year;
		$start = new \DateTimeImmutable( $year . '-01-01', $tz );
		$end = new \DateTimeImmutable( $year . '-12-31', $tz );
		$days_total = (int) $start->diff( $end )->days + 1;

		$days = array();
		$weekday_map = array(
			1 => 'MON',
			2 => 'TUE',
			3 => 'WED',
			4 => 'THU',
			5 => 'FRI',
			6 => 'SAT',
			7 => 'SUN',
		);
		for ( $i = 0; $i < $days_total; $i++ ) {
			$day = $start->modify( '+' . $i . ' days' );
			$weekday = (int) $day->format( 'N' );
			$days[] = array(
				'key' => $day->format( 'Y-m-d' ),
				'label' => $weekday_map[ $weekday ] ?? $day->format( 'D' ),
				'date_label' => $day->format( 'd.m.Y.' ),
			);
		}

		return array(
			'start' => $start->format( 'Y-m-d' ),
			'end' => $end->format( 'Y-m-d' ),
			'days' => $days,
			'current_week_start' => $current_week_start->format( 'Y-m-d' ),
			'today' => $now->format( 'Y-m-d' ),
		);
	}

	private function get_opening_settings() {
		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$opening = $settings['opening_hours'] ?? array();
		$default_open = array(
			'mon' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
			'tue' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
			'wed' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
			'thu' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
			'fri' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
			'sat' => array( 'open' => true, 'start' => '08:00', 'end' => '13:00' ),
			'sun' => array( 'open' => false, 'start' => '', 'end' => '' ),
		);

		if ( empty( $opening ) ) {
			return $default_open;
		}

		return array_merge( $default_open, $opening );
	}

	private function get_opening_hours() {
		$opening = $this->get_opening_settings();

		$earliest = 8;
		$latest = 20;
		foreach ( $opening as $day ) {
			if ( empty( $day['open'] ) ) {
				continue;
			}
			$start = isset( $day['start'] ) ? (int) substr( $day['start'], 0, 2 ) : 8;
			$end = isset( $day['end'] ) ? (int) substr( $day['end'], 0, 2 ) : 20;
			$earliest = min( $earliest, $start );
			$latest = max( $latest, $end );
		}

		return array(
			'start' => $earliest,
			'end' => $latest,
		);
	}

	private function opening_for_date( $date_key, $opening_settings ) {
		if ( $this->is_holiday_date( $date_key ) ) {
			return array( 'open' => false, 'start' => '', 'end' => '' );
		}
		$map = array(
			1 => 'mon',
			2 => 'tue',
			3 => 'wed',
			4 => 'thu',
			5 => 'fri',
			6 => 'sat',
			7 => 'sun',
		);
		$date = new \DateTimeImmutable( $date_key, DateTimeHelper::wp_timezone() );
		$weekday = (int) $date->format( 'N' );
		$key = $map[ $weekday ] ?? 'mon';
		return $opening_settings[ $key ] ?? array( 'open' => false, 'start' => '', 'end' => '' );
	}

	private function time_to_minutes( $value ) {
		if ( ! preg_match( '/^(\d{2}):(\d{2})$/', $value, $matches ) ) {
			return 0;
		}
		return ( (int) $matches[1] * 60 ) + (int) $matches[2];
	}

	private function get_holiday_dates() {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$holiday_dates = isset( $settings['holiday_dates'] ) ? (array) $settings['holiday_dates'] : array();
		$manual_dates = isset( $settings['holiday_manual_dates'] ) ? (array) $settings['holiday_manual_dates'] : array();
		$dates = array_merge( $holiday_dates, $manual_dates );
		$dates = array_map( 'sanitize_text_field', $dates );
		$dates = array_filter(
			$dates,
			function ( $date ) {
				return $this->is_valid_date( $date );
			}
		);

		$cached = array_values( array_unique( $dates ) );
		return $cached;
	}

	private function is_holiday_date( $date_key ) {
		return in_array( $date_key, $this->get_holiday_dates(), true );
	}

	private function is_valid_date( $value ) {
		return (bool) preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', (string) $value );
	}

	private function normalize_datetime_input( $value ) {
		$value = trim( (string) $value );
		$value = str_replace( 'T', ' ', $value );
		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})\s(\d{2}:\d{2})/', $value, $matches ) ) {
			return $matches[1] . ' ' . $matches[2];
		}
		return $value;
	}
}
