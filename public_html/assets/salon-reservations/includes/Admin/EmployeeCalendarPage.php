<?php
namespace Salon\Reservations\Admin;

use DateTimeImmutable;
use Salon\Reservations\Repositories\ShiftsRepository;
use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmployeeCalendarPage {
	public function register_ajax() {
		add_action( 'wp_ajax_salon_shift_save', array( $this, 'ajax_save_shift' ) );
		add_action( 'wp_ajax_salon_shift_color', array( $this, 'ajax_save_color' ) );
		add_action( 'wp_ajax_salon_shift_delete', array( $this, 'ajax_delete_shift' ) );
	}

	public function handle_actions() {
		$this->register_ajax();
	}

	public function ajax_save_shift() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_shift_calendar', 'nonce' );

		$shift_id = isset( $_POST['shift_id'] ) ? (int) $_POST['shift_id'] : 0;
		$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
		$start = sanitize_text_field( wp_unslash( $_POST['start'] ?? '' ) );
		$end = sanitize_text_field( wp_unslash( $_POST['end'] ?? '' ) );

		if ( empty( $start ) || empty( $end ) ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaju vremena.', 'salon-reservations' ) ), 400 );
		}

		$can_manage_all = current_user_can( Capabilities::MANAGE_SHIFTS_ALL );
		if ( ! $can_manage_all ) {
			$employee_id = get_current_user_id();
		}

		if ( ! $employee_id ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaje zaposlenik.', 'salon-reservations' ) ), 400 );
		}

		$start_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $start ) );
		$end_utc = DateTimeHelper::local_to_utc( $this->normalize_datetime_input( $end ) );

		if ( strtotime( $end_utc ) <= strtotime( $start_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Neispravan raspon.', 'salon-reservations' ) ), 400 );
		}

		if ( ! $this->is_within_opening_hours( $start_utc, $end_utc ) ) {
			wp_send_json_error( array( 'message' => __( 'Termin je izvan radnog vremena.', 'salon-reservations' ) ), 409 );
		}

		$repo = new ShiftsRepository();

		if ( $shift_id ) {
			$existing = $repo->get( $shift_id );
			if ( ! $existing ) {
				wp_send_json_error( array( 'message' => __( 'Smjena nije pronađena.', 'salon-reservations' ) ), 404 );
			}
			if ( ! $can_manage_all && (int) $existing->employee_id !== get_current_user_id() ) {
				wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
			}

			$conflict = $this->employee_has_shift_on_date( $employee_id, $start_utc, $shift_id );
			if ( $conflict ) {
				wp_send_json_error( array( 'message' => __( 'Zaposlenik već ima smjenu tog dana.', 'salon-reservations' ) ), 409 );
			}

			$repo->update(
				$shift_id,
				array(
					'employee_id' => $employee_id,
					'start_datetime' => $start_utc,
					'end_datetime' => $end_utc,
					'status' => 'approved',
				)
			);
		} else {
			$conflict = $this->employee_has_shift_on_date( $employee_id, $start_utc, 0 );
			if ( $conflict ) {
				wp_send_json_error( array( 'message' => __( 'Zaposlenik već ima smjenu tog dana.', 'salon-reservations' ) ), 409 );
			}

			$shift_id = $repo->create(
				array(
					'employee_id' => $employee_id,
					'start_datetime' => $start_utc,
					'end_datetime' => $end_utc,
					'status' => 'approved',
					'created_by' => get_current_user_id(),
				)
			);
		}

		$employee = get_user_by( 'id', $employee_id );

		wp_send_json_success(
			array(
				'id' => $shift_id,
				'employee_id' => $employee_id,
				'employee_name' => $employee ? $employee->display_name : '',
				'start' => $start,
				'end' => $end,
			)
		);
	}

	public function ajax_save_color() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_shift_calendar', 'nonce' );

		$employee_id = isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : 0;
		$color = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '' ) );

		if ( ! $employee_id || empty( $color ) ) {
			wp_send_json_error( array( 'message' => __( 'Nedostaju podaci.', 'salon-reservations' ) ), 400 );
		}

		$can_manage_all = current_user_can( Capabilities::MANAGE_SHIFTS_ALL );
		if ( ! $can_manage_all && $employee_id !== get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		update_user_meta( $employee_id, 'salon_shift_color', $color );

		wp_send_json_success( array( 'color' => $color ) );
	}

	public function ajax_delete_shift() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		check_ajax_referer( 'salon_shift_calendar', 'nonce' );

		$shift_id = isset( $_POST['shift_id'] ) ? (int) $_POST['shift_id'] : 0;
		if ( ! $shift_id ) {
			wp_send_json_error( array( 'message' => __( 'Smjena nije pronađena.', 'salon-reservations' ) ), 404 );
		}

		$repo = new ShiftsRepository();
		$shift = $repo->get( $shift_id );
		if ( ! $shift ) {
			wp_send_json_error( array( 'message' => __( 'Smjena nije pronađena.', 'salon-reservations' ) ), 404 );
		}

		$can_manage_all = current_user_can( Capabilities::MANAGE_SHIFTS_ALL );
		if ( ! $can_manage_all && (int) $shift->employee_id !== get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'Nemate dozvolu.', 'salon-reservations' ) ), 403 );
		}

		$repo->update( $shift_id, array( 'status' => 'cancelled' ) );
		wp_send_json_success( array( 'id' => $shift_id ) );
	}

	public function render( $embedded = false ) {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$can_manage_all = current_user_can( Capabilities::MANAGE_SHIFTS_ALL );
		$can_view_all = $can_manage_all || current_user_can( Capabilities::MANAGE_SHIFTS_OWN );
		$current_user_id = get_current_user_id();
		$employees = $can_view_all ? $this->get_employees() : array( get_user_by( 'id', $current_user_id ) );

		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$interval = isset( $settings['slot_interval_minutes'] ) ? (int) $settings['slot_interval_minutes'] : 15;
		$default_shift_minutes = 8 * 60;

		$selected_year = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) date_i18n( 'Y' );
		if ( $selected_year < 1970 || $selected_year > 2100 ) {
			$selected_year = (int) date_i18n( 'Y' );
		}
		$range = $this->current_range_local( $selected_year );
		list( $from_utc, $to_utc ) = DateTimeHelper::local_date_range_to_utc( $range['start'], $range['end'] );

		$repo = new ShiftsRepository();
		$shifts = $can_view_all
			? $repo->list_all( $from_utc, $to_utc )
			: $repo->list_by_employee( $current_user_id, $from_utc, $to_utc );

		$employee_map = $this->build_employee_map( $employees );
		$employee_colors = $this->build_employee_colors( $employees );
		$hours = $this->get_opening_hours();
		$grid = $this->build_shift_grid( $shifts, $range['days'], $employee_map, $employee_colors, $hours['start'], $hours['end'] );
		$nonce = wp_create_nonce( 'salon_shift_calendar' );
		$height_scale = 0.8;
		$grid_height = ( $hours['end'] - $hours['start'] ) * 60 * $height_scale;
		$total_minutes = max( 1, ( $hours['end'] - $hours['start'] ) * 60 );
		$px_per_minute = $grid_height / $total_minutes;
		$opening_settings = $this->get_opening_settings();

		$container_class = $embedded ? 'salon-tabs__panel' : 'wrap';
		?>
		<div class="<?php echo esc_attr( $container_class ); ?>">
			<?php $current_year = (int) date_i18n( 'Y' ); ?>
			<div class="salon-shifts__panel">
				<div class="salon-shifts__months">
				<span class="salon-shifts__year"><?php echo esc_html( $selected_year ); ?></span>
				<select class="salon-shifts__year-select" data-year-select>
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
						class="salon-shifts__month<?php echo $is_current_month ? ' is-current' : ''; ?>"
						data-month="<?php echo esc_attr( $month ); ?>"
						data-date="<?php echo esc_attr( $date_key ); ?>"
					>
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endfor; ?>
				<div class="salon-shifts__week-nav">
					<button type="button" class="salon-shifts__week-btn" data-salon-week-prev aria-label="<?php esc_attr_e( 'Prethodni tjedan', 'salon-reservations' ); ?>">&lsaquo;</button>
					<button type="button" class="salon-shifts__week-label" data-salon-week-label></button>
					<button type="button" class="salon-shifts__week-btn" data-salon-week-next aria-label="<?php esc_attr_e( 'Sljedeći tjedan', 'salon-reservations' ); ?>">&rsaquo;</button>
				</div>
				</div>
			<div class="salon-shifts__trash" data-salon-shifts-trash aria-hidden="true">
				<span class="dashicons dashicons-trash" aria-hidden="true"></span>
				<span class="salon-shifts__trash-label"><?php esc_html_e( 'Obriši', 'salon-reservations' ); ?></span>
			</div>

				<div class="salon-shifts__employees">
				<div class="salon-shifts__chips">
					<?php foreach ( $employees as $employee ) : ?>
						<?php if ( ! $employee ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php
							$color = $employee_colors[ (int) $employee->ID ]['color'] ?? '#0f172a';
							$bg = $employee_colors[ (int) $employee->ID ]['bg'] ?? 'rgba(15, 23, 42, 0.12)';
						?>
						<div
							class="salon-shifts__chip"
							draggable="true"
							data-employee-id="<?php echo esc_attr( $employee->ID ); ?>"
							data-color="<?php echo esc_attr( $color ); ?>"
							style="--salon-color: <?php echo esc_attr( $color ); ?>; --salon-color-bg: <?php echo esc_attr( $bg ); ?>;"
						>
							<span class="salon-shifts__chip-name"><?php echo esc_html( $employee->display_name ); ?></span>
							<?php if ( $can_manage_all || (int) $employee->ID === (int) $current_user_id ) : ?>
								<label class="salon-shifts__chip-color" aria-label="<?php esc_attr_e( 'Boja zaposlenika', 'salon-reservations' ); ?>">
									<input
										type="color"
										class="salon-shifts__chip-color-input"
										value="<?php echo esc_attr( $color ); ?>"
										data-employee-id="<?php echo esc_attr( $employee->ID ); ?>"
									/>
									<span class="salon-shifts__chip-color-bubble" aria-hidden="true"></span>
								</label>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				</div>

				<div
					class="salon-shifts__calendar"
					data-salon-shift-calendar
					data-start-hour="<?php echo esc_attr( $hours['start'] ); ?>"
					data-end-hour="<?php echo esc_attr( $hours['end'] ); ?>"
					data-interval="<?php echo esc_attr( $interval ); ?>"
					data-default-length="<?php echo esc_attr( $default_shift_minutes ); ?>"
					data-can-edit="<?php echo esc_attr( $can_manage_all || current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ); ?>"
					data-can-manage-all="<?php echo esc_attr( $can_manage_all ? '1' : '0' ); ?>"
					data-current-user-id="<?php echo esc_attr( $current_user_id ); ?>"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-current-week-start="<?php echo esc_attr( $range['current_week_start'] ); ?>"
					data-today="<?php echo esc_attr( $range['today'] ); ?>"
				>
				<div class="salon-shifts__times">
					<div class="salon-shifts__time-header"></div>
					<div class="salon-shifts__time-body" style="height: <?php echo esc_attr( $grid_height ); ?>px;">
						<?php for ( $hour = $hours['start']; $hour <= $hours['end']; $hour++ ) : ?>
							<?php
								$offset_minutes = ( $hour - $hours['start'] ) * 60;
								$top = $offset_minutes * $px_per_minute;
							?>
							<div class="salon-shifts__time-label" style="top: <?php echo esc_attr( $top ); ?>px;">
								<?php echo esc_html( sprintf( '%02d', $hour ) ); ?>
							</div>
						<?php endfor; ?>
					</div>
				</div>
				<div class="salon-shifts__scroll">
					<div class="salon-shifts__days">
						<?php foreach ( $grid as $day_key => $day ) : ?>
							<?php
								$opening_day = $this->opening_for_date( $day_key, $opening_settings );
								$is_holiday = $this->is_holiday_date( $day_key );
								$is_open = ! empty( $opening_day['open'] ) && ! $is_holiday;
								$open_start_minutes = $is_open ? $this->time_to_minutes( $opening_day['start'] ) : 0;
								$open_end_minutes = $is_open ? $this->time_to_minutes( $opening_day['end'] ) : 0;
								$open_start_offset = $is_open ? max( 0, $open_start_minutes - ( $hours['start'] * 60 ) ) : 0;
								$open_end_offset = $is_open ? min( $total_minutes, $open_end_minutes - ( $hours['start'] * 60 ) ) : 0;
								$day_date = DateTimeImmutable::createFromFormat( 'Y-m-d', $day_key, DateTimeHelper::wp_timezone() );
								$is_sunday = $day_date ? ( 7 === (int) $day_date->format( 'N' ) ) : false;
							?>
							<div class="salon-shifts__day<?php echo $is_open ? '' : ' is-closed'; ?><?php echo $is_sunday ? ' is-sunday' : ''; ?><?php echo $is_holiday ? ' is-holiday' : ''; ?>" data-date="<?php echo esc_attr( $day_key ); ?>">
								<div class="salon-shifts__day-header<?php echo $day_key === $range['today'] ? ' is-today' : ''; ?>">
									<span class="salon-shifts__day-name"><?php echo esc_html( $day['label'] ); ?></span>
									<span class="salon-shifts__day-date"><?php echo esc_html( $day['date_label'] ?? '' ); ?></span>
								</div>
								<div
									class="salon-shifts__day-body"
									style="height: <?php echo esc_attr( $grid_height ); ?>px;"
									data-open="<?php echo esc_attr( $is_open ? '1' : '0' ); ?>"
									data-open-start="<?php echo esc_attr( $open_start_offset ); ?>"
									data-open-end="<?php echo esc_attr( $open_end_offset ); ?>"
								>
									<?php if ( ! $is_open ) : ?>
										<div class="salon-shifts__closed" style="top: 0; height: <?php echo esc_attr( $grid_height ); ?>px;"></div>
									<?php else : ?>
										<?php if ( $open_start_offset > 0 ) : ?>
											<div class="salon-shifts__closed" style="top: 0; height: <?php echo esc_attr( $open_start_offset * $px_per_minute ); ?>px;"></div>
										<?php endif; ?>
										<?php if ( $open_end_offset < $total_minutes ) : ?>
											<div class="salon-shifts__closed" style="top: <?php echo esc_attr( $open_end_offset * $px_per_minute ); ?>px; height: <?php echo esc_attr( ( $total_minutes - $open_end_offset ) * $px_per_minute ); ?>px;"></div>
										<?php endif; ?>
									<?php endif; ?>
									<?php foreach ( $day['shifts'] as $shift ) : ?>
										<div
											class="salon-shifts__block"
											data-shift-id="<?php echo esc_attr( $shift['id'] ); ?>"
											data-employee-id="<?php echo esc_attr( $shift['employee_id'] ); ?>"
											data-start="<?php echo esc_attr( $shift['start'] ); ?>"
											data-end="<?php echo esc_attr( $shift['end'] ); ?>"
											style="top: <?php echo esc_attr( $shift['top'] ); ?>px; height: <?php echo esc_attr( $shift['height'] ); ?>px; --salon-color: <?php echo esc_attr( $shift['color'] ); ?>; --salon-color-bg: <?php echo esc_attr( $shift['bg'] ); ?>;"
										>
											<strong class="salon-shifts__block-name"><?php echo esc_html( $shift['employee_name'] ); ?></strong>
											<span class="salon-shifts__block-time"><?php echo esc_html( $shift['time'] ); ?></span>
											<span class="salon-shifts__block-handle salon-shifts__block-handle--start" aria-hidden="true"></span>
											<span class="salon-shifts__block-handle salon-shifts__block-handle--end" aria-hidden="true"></span>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				</div>
			</div>

			<div class="salon-shifts__modal" data-shift-modal hidden>
				<div class="salon-shifts__modal-overlay" data-shift-modal-cancel></div>
				<div class="salon-shifts__modal-card" role="dialog" aria-modal="true">
					<h3><?php esc_html_e( 'Potvrdi promjenu smjene', 'salon-reservations' ); ?></h3>
					<p><strong><?php esc_html_e( 'Stari termin:', 'salon-reservations' ); ?></strong> <span data-shift-old-time></span></p>
					<p><strong><?php esc_html_e( 'Stari zaposlenik:', 'salon-reservations' ); ?></strong> <span data-shift-old-employee></span></p>
					<p><strong><?php esc_html_e( 'Novi termin:', 'salon-reservations' ); ?></strong> <span data-shift-new-time></span></p>
					<p><strong><?php esc_html_e( 'Novi zaposlenik:', 'salon-reservations' ); ?></strong> <span data-shift-new-employee></span></p>
					<div class="salon-shifts__modal-actions">
						<button type="button" class="button" data-shift-modal-cancel><?php esc_html_e( 'Odustani', 'salon-reservations' ); ?></button>
						<button type="button" class="button button-primary" data-shift-modal-confirm><?php esc_html_e( 'Potvrdi', 'salon-reservations' ); ?></button>
					</div>
				</div>
			</div>
		</div>

		<style>
			.salon-shifts__months { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 6px 0 12px; padding-left: 34px; }
			.salon-shifts__week-nav { display: inline-flex; align-items: center; gap: 6px; margin-left: 6px; }
			.salon-shifts__week-btn { border: 1px solid rgba(148, 163, 184, 0.4); background: #fff; border-radius: 999px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #0f172a; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
			.salon-shifts__week-label { border: 1px solid rgba(148, 163, 184, 0.4); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; color: #0f172a; cursor: pointer; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08); }
			.salon-shifts__year { font-size: 18px; font-weight: 700; color: #0f172a; margin-right: 2px; }
			.salon-shifts__year-select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px 8px; font-size: 12px; background: #fff; color: #0f172a; }
			.salon-shifts__month { border: 1px solid rgba(148, 163, 184, 0.4); font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #334155; cursor: pointer; padding: 6px 10px; border-radius: 999px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0.15)); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), inset 0 -2px 6px rgba(15, 23, 42, 0.08), 0 6px 12px rgba(15, 23, 42, 0.08); backdrop-filter: blur(6px); }
			.salon-shifts__month.is-current { color: #0f172a; font-weight: 700; border-color: rgba(59, 130, 246, 0.45); background: linear-gradient(135deg, rgba(255,255,255,0.85), rgba(255,255,255,0.35)); box-shadow: inset 0 1px 2px rgba(255,255,255,0.85), inset 0 -2px 6px rgba(59,130,246,0.15), 0 8px 16px rgba(15,23,42,0.12); }
			.salon-shifts__employees { margin: 8px 0 16px; }
			.salon-shifts__chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; padding-left: 34px; }
			.salon-shifts__chips.is-dragging .salon-shifts__chip { opacity: 0; pointer-events: none; }
			.salon-shifts__chips.is-dragging .salon-shifts__chip.is-dragging { opacity: 1; pointer-events: auto; }
			.salon-shifts__chip { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: var(--salon-color, #0f172a); color: #fff; cursor: grab; font-size: 13px; }
			.salon-shifts__chip-color { position: relative; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
			.salon-shifts__chip-color-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
			.salon-shifts__chip-color-bubble { width: 20px; height: 20px; border-radius: 999px; background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.8), transparent 45%), var(--salon-color, #0f172a); box-shadow: inset 0 2px 6px rgba(255, 255, 255, 0.35), inset 0 -4px 8px rgba(0, 0, 0, 0.18), 0 2px 4px rgba(0, 0, 0, 0.12); }
			.salon-shifts__calendar { display: grid; grid-template-columns: 28px 1fr; gap: 6px; width: 100%; align-items: start; }
			.salon-shifts__times { display: flex; flex-direction: column; align-items: flex-end; }
			.salon-shifts__time-header { height: 48px; }
			.salon-shifts__time-body { position: relative; width: 100%; }
			.salon-shifts__time-label { position: absolute; right: 0; font-size: 10px; color: #94a3b8; transform: translateY(-6px); }
			.salon-shifts__scroll { position: relative; overflow-x: auto; overflow-y: hidden; padding-bottom: 4px; cursor: grab; }
			.salon-shifts__scroll.is-dragging { cursor: grabbing; }
			.salon-shifts__days { display: flex; gap: 8px; width: max-content; }
			.salon-shifts__day { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; min-width: 180px; }
			.salon-shifts__day.is-closed { min-width: 72px; width: 72px; flex: 0 0 72px; }
			.salon-shifts__day.is-sunday { min-width: 82px; width: 82px; flex: 0 0 82px; }
			.salon-shifts__day.is-holiday { min-width: 82px; width: 82px; flex: 0 0 82px; }
			.salon-shifts__day-header { height: 48px; display: flex; flex-direction: column; justify-content: center; padding: 6px 8px 4px; font-weight: 600; background: #f8fafc; border-bottom: 1px solid #e2e8f0; box-sizing: border-box; line-height: 1.1; gap: 2px; }
			.salon-shifts__day-header.is-today { background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(168,85,247,0.18)); border-bottom-color: rgba(168,85,247,0.35); box-shadow: inset 0 0 0 1px rgba(168,85,247,0.25); }
			.salon-shifts__day.is-sunday { background: #f1f5f9; }
			.salon-shifts__day.is-sunday .salon-shifts__day-body { background-color: rgba(148, 163, 184, 0.12); }
			.salon-shifts__day.is-sunday .salon-shifts__day-header { background: #e2e8f0; color: #64748b; }
			.salon-shifts__day.is-holiday { background: #f1f5f9; }
			.salon-shifts__day.is-holiday .salon-shifts__day-body { background-color: rgba(148, 163, 184, 0.12); }
			.salon-shifts__day.is-holiday .salon-shifts__day-header { background: #e2e8f0; color: #64748b; }
			.salon-shifts__trash { position: fixed; right: 24px; bottom: 24px; width: 70px; height: 70px; border-radius: 18px; background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(148, 163, 184, 0.4); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: #0f172a; box-shadow: 0 18px 30px rgba(15, 23, 42, 0.2); opacity: 0; pointer-events: none; transform: translateY(8px) scale(0.96); transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease; z-index: 9999; }
			.salon-shifts__trash.is-visible { opacity: 1; pointer-events: auto; transform: translateY(0) scale(1); }
			.salon-shifts__trash.is-active { background: rgba(254, 226, 226, 0.95); border-color: rgba(248, 113, 113, 0.6); color: #b91c1c; box-shadow: 0 20px 36px rgba(185, 28, 28, 0.25); }
			.salon-shifts__trash .dashicons { font-size: 24px; width: 24px; height: 24px; }
			.salon-shifts__trash-label { font-size: 11px; font-weight: 600; letter-spacing: 0.02em; }
			.salon-shifts__day-date { font-size: 11px; font-weight: 500; color: #64748b; }
			.salon-shifts__day-body { position: relative; background-image: repeating-linear-gradient(to bottom, #f1f5f9 0, #f1f5f9 1px, transparent 1px, transparent 48px); }
			.salon-shifts__closed { position: absolute; left: 0; right: 0; background: repeating-linear-gradient(45deg, rgba(148, 163, 184, 0.15) 0, rgba(148, 163, 184, 0.15) 6px, rgba(226, 232, 240, 0.35) 6px, rgba(226, 232, 240, 0.35) 12px); pointer-events: none; }
			.salon-shifts__day--blocked { outline: 2px dashed #ef4444; }
			.salon-shifts__block { position: absolute; left: 4px; width: calc(100% - 8px); padding: 28px 6px 6px; border-radius: 8px; background: var(--salon-color-bg, #e2e8f0); border-left: 4px solid var(--salon-color, #94a3b8); font-size: 12px; cursor: grab; box-sizing: border-box; color: #0f172a; }
			.salon-shifts__block.is-dragging { opacity: 0.85; cursor: grabbing; }
			.salon-shifts__block.is-readonly { cursor: default; }
			.salon-shifts__block.is-readonly .salon-shifts__block-handle { display: none; }
			.salon-shifts__block-name { display: block; font-weight: 600; margin-bottom: 2px; }
			.salon-shifts__block-time { display: block; color: #475569; }
			.salon-shifts__block.is-vertical .salon-shifts__block-name,
			.salon-shifts__block.is-vertical .salon-shifts__block-time { writing-mode: vertical-rl; text-orientation: mixed; }
			.salon-shifts__block-handle { position: absolute; width: 18px; height: 18px; border-radius: 999px; right: 6px; background: var(--salon-color, #94a3b8); box-shadow: inset 0 1px 3px rgba(255, 255, 255, 0.45), inset 0 -3px 6px rgba(0, 0, 0, 0.18); cursor: ns-resize; }
			.salon-shifts__block-handle::before,
			.salon-shifts__block-handle::after { content: ""; position: absolute; left: 50%; transform: translateX(-50%); border-left: 4px solid transparent; border-right: 4px solid transparent; }
			.salon-shifts__block-handle::before { top: 3px; border-bottom: 5px solid rgba(255, 255, 255, 0.95); }
			.salon-shifts__block-handle::after { bottom: 3px; border-top: 5px solid rgba(255, 255, 255, 0.95); }
			.salon-shifts__block-handle--start { top: 6px; }
			.salon-shifts__block-handle--end { bottom: 6px; }
			.salon-shifts__calendar.is-disabled { opacity: 0.6; pointer-events: none; }
			.salon-shifts__ghost { position: absolute; left: 4px; width: calc(100% - 8px); border-radius: 8px; border: 2px dashed rgba(148, 163, 184, 0.8); background: rgba(148, 163, 184, 0.12); display: flex; align-items: center; justify-content: center; color: #475569; font-weight: 700; font-size: 12px; z-index: 3; pointer-events: none; }
			.salon-shifts__modal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 9999; }
			.salon-shifts__modal[hidden] { display: none; }
			.salon-shifts__modal-overlay { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.4); }
			.salon-shifts__modal-card { position: relative; background: #fff; padding: 20px 22px; border-radius: 12px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2); max-width: 360px; width: 100%; z-index: 2; }
			.salon-shifts__modal-card h3 { margin-top: 0; margin-bottom: 12px; }
			.salon-shifts__modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
		</style>

		<script>
			(function() {
				const calendar = document.querySelector('[data-salon-shift-calendar]');
				if (!calendar) return;

				const canEdit = calendar.dataset.canEdit === '1' || calendar.dataset.canEdit === 'true';
				const canManageAll = calendar.dataset.canManageAll === '1' || calendar.dataset.canManageAll === 'true';
				const currentUserId = calendar.dataset.currentUserId || '';
				const startHour = parseInt(calendar.dataset.startHour || '8', 10);
				const endHour = parseInt(calendar.dataset.endHour || '20', 10);
				const interval = parseInt(calendar.dataset.interval || '15', 10);
				const resizeInterval = 30;
				const defaultLength = parseInt(calendar.dataset.defaultLength || '480', 10);
				const ajaxUrl = calendar.dataset.ajaxUrl || window.ajaxurl;
				const nonce = calendar.dataset.nonce;
				const dayBodies = calendar.querySelectorAll('.salon-shifts__day-body');
				const chips = document.querySelectorAll('.salon-shifts__chip');
				const chipWrapper = document.querySelector('.salon-shifts__chips');
				const colorInputs = document.querySelectorAll('.salon-shifts__chip-color-input');
				const scroll = calendar.querySelector('.salon-shifts__scroll');
				const currentWeekStart = calendar.dataset.currentWeekStart || '';
				const todayDate = calendar.dataset.today || '';
				const yearSelect = document.querySelector('[data-year-select]');
				const monthButtons = document.querySelectorAll('.salon-shifts__month');
				const weekPrev = document.querySelector('[data-salon-week-prev]');
				const weekNext = document.querySelector('[data-salon-week-next]');
				const weekLabel = document.querySelector('[data-salon-week-label]');
				const modal = document.querySelector('[data-shift-modal]');
				const modalOldTime = modal ? modal.querySelector('[data-shift-old-time]') : null;
				const modalOldEmployee = modal ? modal.querySelector('[data-shift-old-employee]') : null;
				const modalNewTime = modal ? modal.querySelector('[data-shift-new-time]') : null;
				const modalNewEmployee = modal ? modal.querySelector('[data-shift-new-employee]') : null;
				const modalCancel = modal ? modal.querySelectorAll('[data-shift-modal-cancel]') : [];
				const modalConfirm = modal ? modal.querySelector('[data-shift-modal-confirm]') : null;
				const trash = document.querySelector('[data-salon-shifts-trash]');
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
					const dayEl = scroll.querySelector(`.salon-shifts__day[data-date="${dateStr}"]`);
					if (dayEl) {
						scrollToDay(dayEl);
						return;
					}
					const days = scroll.querySelectorAll('.salon-shifts__day');
					if (!days.length) return;
					if (dateStr < days[0].dataset.date) {
						scrollToDay(days[0]);
						return;
					}
					scrollToDay(days[days.length - 1]);
				};
				const scrollToDateStart = (dateStr) => {
					if (!scroll || !dateStr) return;
					const dayEl = scroll.querySelector(`.salon-shifts__day[data-date="${dateStr}"]`);
					if (dayEl) {
						scrollToDayStart(dayEl);
						return;
					}
					const days = scroll.querySelectorAll('.salon-shifts__day');
					if (!days.length) return;
					if (dateStr < days[0].dataset.date) {
						scrollToDayStart(days[0]);
						return;
					}
					scrollToDayStart(days[days.length - 1]);
				};

				const getCenterDate = () => {
					if (!scroll) return todayDate;
					const days = Array.from(scroll.querySelectorAll('.salon-shifts__day'));
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
					const days = Array.from(scroll.querySelectorAll('.salon-shifts__day'));
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

				const formatEuDate = (dateStr) => {
					const parts = (dateStr || '').split('-');
					if (parts.length !== 3) return dateStr || '';
					return `${parts[2]}.${parts[1]}.${parts[0]}.`;
				};

				const formatRange = (start, end) => {
					if (!start || !end) return '-';
					const date = formatEuDate(start.slice(0, 10));
					const startTime = start.slice(11, 16);
					const endTime = end.slice(11, 16);
					return `${date} ${startTime} - ${endTime}`;
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

				const timeToMinutes = (dateTime) => {
					const time = dateTime.split(' ')[1] || dateTime.slice(11, 16);
					const parts = time.split(':');
					return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
				};

				const snapMinutes = (minutes) => Math.round(minutes / interval) * interval;
				const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
				const hexToRgba = (hex, alpha) => {
					const clean = hex.replace('#', '');
					if (clean.length !== 6) return `rgba(148, 163, 184, ${alpha})`;
					const r = parseInt(clean.slice(0, 2), 16);
					const g = parseInt(clean.slice(2, 4), 16);
					const b = parseInt(clean.slice(4, 6), 16);
					return `rgba(${r}, ${g}, ${b}, ${alpha})`;
				};

				const totalMinutes = (endHour - startHour) * 60;
				const pixelsPerMinute = dayBodies.length ? dayBodies[0].clientHeight / totalMinutes : 1;
				const minutesToPixels = (minutes) => minutes * pixelsPerMinute;
				const pixelsToMinutes = (pixels) => pixels / pixelsPerMinute;
				let ghostBlock = null;
				let draggingEmployeeId = '';

				const syncBlockPosition = (block) => {
					const startMinutes = timeToMinutes(block.dataset.start) - startHour * 60;
					const endMinutes = timeToMinutes(block.dataset.end) - startHour * 60;
					block.style.top = `${minutesToPixels(startMinutes)}px`;
					block.style.height = `${minutesToPixels(endMinutes - startMinutes)}px`;
				};

				const applyColor = (employeeId, color) => {
					const bg = hexToRgba(color, 0.18);
					document.querySelectorAll(`[data-employee-id="${employeeId}"]`).forEach((el) => {
						el.style.setProperty('--salon-color', color);
						el.style.setProperty('--salon-color-bg', bg);
						if (el.classList.contains('salon-shifts__chip')) {
							el.dataset.color = color;
						}
					});
				};

				const hasShiftForEmployeeOnDay = (employeeId, dayBody, excludeShiftId) => {
					const blocks = dayBody.querySelectorAll('.salon-shifts__block');
					for (const block of blocks) {
						if (excludeShiftId && block.dataset.shiftId === excludeShiftId) {
							continue;
						}
						if (block.dataset.employeeId === employeeId) {
							return true;
						}
					}
					return false;
				};

				const getOpenWindow = (dayBody) => {
					if (!dayBody) return null;
					const isOpen = dayBody.dataset.open === '1';
					const openStart = parseInt(dayBody.dataset.openStart || '0', 10);
					const openEnd = parseInt(dayBody.dataset.openEnd || '0', 10);
					if (!isOpen || openEnd <= openStart) {
						return null;
					}
					return { openStart, openEnd, duration: openEnd - openStart };
				};

				const layoutDay = (dayBody) => {
					const blocks = Array.from(dayBody.querySelectorAll('.salon-shifts__block'));
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
							if (columnCount >= 3) {
								item.block.classList.add('is-vertical');
							} else {
								item.block.classList.remove('is-vertical');
							}
						});
					});
				};

				const layoutAll = () => {
					dayBodies.forEach(layoutDay);
				};

				const updateBlockLabels = (block, startMinutes, endMinutes) => {
					const dayDate = block.closest('.salon-shifts__day').dataset.date;
					const start = `${dayDate} ${minutesToTime(startMinutes)}`;
					const end = `${dayDate} ${minutesToTime(endMinutes)}`;
					block.dataset.start = start;
					block.dataset.end = end;
					const timeLabel = block.querySelector('.salon-shifts__block-time');
					if (timeLabel) {
						timeLabel.textContent = `${minutesToTime(startMinutes)} - ${minutesToTime(endMinutes)}`;
					}
				};

				const showGhost = (dayBody, startOffset, durationMinutes, label) => {
					if (!dayBody) return;
					if (!ghostBlock) {
						ghostBlock = document.createElement('div');
						ghostBlock.className = 'salon-shifts__ghost';
					}
					if (ghostBlock.parentElement !== dayBody) {
						dayBody.appendChild(ghostBlock);
					}
					ghostBlock.style.top = `${minutesToPixels(startOffset)}px`;
					ghostBlock.style.height = `${minutesToPixels(durationMinutes)}px`;
					ghostBlock.textContent = label;
				};

				const hideGhost = () => {
					if (ghostBlock && ghostBlock.parentElement) {
						ghostBlock.parentElement.removeChild(ghostBlock);
					}
					ghostBlock = null;
				};

				const saveShift = async (block) => {
					const payload = new URLSearchParams();
					payload.set('action', 'salon_shift_save');
					payload.set('nonce', nonce);
					payload.set('shift_id', block.dataset.shiftId || '');
					payload.set('employee_id', block.dataset.employeeId || '');
					payload.set('start', block.dataset.start);
					payload.set('end', block.dataset.end);

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

					if (data.data && data.data.id) {
						block.dataset.shiftId = data.data.id;
					}
				};

				const deleteShift = async (block) => {
					const shiftId = block.dataset.shiftId || '';
					if (!shiftId) {
						block.remove();
						layoutAll();
						return;
					}
					const payload = new URLSearchParams();
					payload.set('action', 'salon_shift_delete');
					payload.set('nonce', nonce);
					payload.set('shift_id', shiftId);

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
					block.remove();
					layoutAll();
				};

				if (canEdit) {
					chips.forEach((chip) => {
						chip.addEventListener('dragstart', (event) => {
							if (event.target && event.target.closest('.salon-shifts__chip-color')) {
								event.preventDefault();
								return;
							}
							if (!canManageAll && chip.dataset.employeeId !== currentUserId) {
								event.preventDefault();
								return;
							}
							chip.classList.add('is-dragging');
							if (chipWrapper) {
								chipWrapper.classList.add('is-dragging');
							}
							draggingEmployeeId = chip.dataset.employeeId || '';
							event.dataTransfer.setData('text/plain', chip.dataset.employeeId);
						});
						chip.addEventListener('dragend', () => {
							chip.classList.remove('is-dragging');
							if (chipWrapper) {
								chipWrapper.classList.remove('is-dragging');
							}
							draggingEmployeeId = '';
							hideGhost();
						});
					});

					dayBodies.forEach((dayBody) => {
						dayBody.addEventListener('dragover', (event) => {
							event.preventDefault();
							if (!draggingEmployeeId) return;
							if (!canManageAll && draggingEmployeeId !== currentUserId) {
								hideGhost();
								return;
							}
							const openWindow = getOpenWindow(dayBody);
							if (!openWindow || openWindow.duration < interval) {
								hideGhost();
								return;
							}
							const rect = dayBody.getBoundingClientRect();
							const offsetY = clamp(event.clientY - rect.top, 0, rect.height);
							let startOffset = snapMinutes(pixelsToMinutes(offsetY));
							let durationMinutes = defaultLength;
							if (openWindow.duration < defaultLength) {
								startOffset = openWindow.openStart;
								durationMinutes = openWindow.duration;
							} else {
								startOffset = clamp(startOffset, openWindow.openStart, openWindow.openEnd - defaultLength);
							}
							const startMinutes = startHour * 60 + startOffset;
							const endMinutes = startMinutes + durationMinutes;
							const label = `${minutesToTime(startMinutes)} - ${minutesToTime(endMinutes)}`;
							showGhost(dayBody, startOffset, durationMinutes, label);
						});
						dayBody.addEventListener('dragleave', (event) => {
							if (event.relatedTarget && dayBody.contains(event.relatedTarget)) {
								return;
							}
							hideGhost();
						});
						dayBody.addEventListener('drop', async (event) => {
							event.preventDefault();
							const employeeId = event.dataTransfer.getData('text/plain');
							if (!employeeId) return;
							if (!canManageAll && employeeId !== currentUserId) {
								hideGhost();
								return;
							}

							const rect = dayBody.getBoundingClientRect();
							const offsetY = clamp(event.clientY - rect.top, 0, rect.height);
							const minutesFromStart = snapMinutes(pixelsToMinutes(offsetY));
							const openWindow = getOpenWindow(dayBody);
							if (!openWindow || openWindow.duration < interval) {
								return;
							}
							let startOffset = clamp(minutesFromStart, openWindow.openStart, openWindow.openEnd - interval);
							let startMinutes = startHour * 60 + startOffset;
							let endMinutes = startMinutes + defaultLength;
							if (openWindow.duration < defaultLength) {
								startOffset = openWindow.openStart;
								startMinutes = startHour * 60 + startOffset;
								endMinutes = startHour * 60 + openWindow.openEnd;
							} else {
								const maxEnd = startHour * 60 + openWindow.openEnd;
								endMinutes = Math.min(endMinutes, maxEnd);
							}

							if (hasShiftForEmployeeOnDay(employeeId, dayBody)) {
								return;
							}

							const block = document.createElement('div');
							block.className = 'salon-shifts__block';
							block.dataset.shiftId = '';
							block.dataset.employeeId = employeeId;
							const chipColor = chipColorFor(employeeId);
							if (chipColor) {
								block.style.setProperty('--salon-color', chipColor);
								block.style.setProperty('--salon-color-bg', hexToRgba(chipColor, 0.18));
							}
							block.style.top = `${minutesToPixels(startMinutes - startHour * 60)}px`;
							block.style.height = `${minutesToPixels(endMinutes - startMinutes)}px`;
							block.innerHTML = `\
								<strong class="salon-shifts__block-name">${chipName(employeeId)}</strong>\
								<span class="salon-shifts__block-time"></span>\
								<span class="salon-shifts__block-handle salon-shifts__block-handle--start" aria-hidden="true"></span>\
								<span class="salon-shifts__block-handle salon-shifts__block-handle--end" aria-hidden="true"></span>\
							`;

							dayBody.appendChild(block);
							updateBlockLabels(block, startMinutes, endMinutes);
							attachBlockEvents(block);
							layoutDay(dayBody);
							hideGhost();

							const employeeName = chipName(employeeId);
							openModal(
								{
									oldTime: '-',
									oldEmployee: '-',
									newTime: formatRange(block.dataset.start, block.dataset.end),
									newEmployee: employeeName,
								},
								async () => {
									try {
										await saveShift(block);
									} catch (error) {
										block.remove();
									}
								},
								() => {
									block.remove();
								}
							);
						});
					});
				}

				const chipName = (employeeId) => {
					const chip = document.querySelector(`.salon-shifts__chip[data-employee-id="${employeeId}"]`);
					const name = chip ? chip.querySelector('.salon-shifts__chip-name') : null;
					return name ? name.textContent.trim() : '';
				};

				const chipColorFor = (employeeId) => {
					const chip = document.querySelector(`.salon-shifts__chip[data-employee-id="${employeeId}"]`);
					return chip ? chip.dataset.color : '';
				};

				const attachBlockEvents = (block) => {
					if (!canEdit) return;
					if (!canManageAll && block.dataset.employeeId !== currentUserId) {
						block.classList.add('is-readonly');
						return;
					}
					const handle = block.querySelector('.salon-shifts__block-handle--end');
					const handleStart = block.querySelector('.salon-shifts__block-handle--start');

					const startDrag = (event) => {
						if (event.target === handle || event.target === handleStart) return;
						event.preventDefault();
						const originDayBody = block.closest('.salon-shifts__day-body');
						const initialTop = parseFloat(block.style.top || '0');
						const originalStart = block.dataset.start;
						const originalEnd = block.dataset.end;
						const duration = timeToMinutes(block.dataset.end) - timeToMinutes(block.dataset.start);
						const blockRect = block.getBoundingClientRect();
						const clickOffsetY = event.clientY - blockRect.top;
						const blockHeightPx = minutesToPixels(duration);

						let dragOverTrash = false;
						block.classList.add('is-dragging');
						showTrash();

						const revert = () => {
							if (originDayBody && block.parentElement !== originDayBody) {
								originDayBody.appendChild(block);
							}
							block.style.top = `${initialTop}px`;
							block.dataset.start = originalStart;
							block.dataset.end = originalEnd;
							updateBlockLabels(block, timeToMinutes(originalStart), timeToMinutes(originalEnd));
							layoutAll();
						};

						const onMove = (moveEvent) => {
							dragOverTrash = updateTrashHover(moveEvent);
							const targetDayBody = document.elementFromPoint(moveEvent.clientX, moveEvent.clientY)?.closest('.salon-shifts__day-body') || originDayBody;
							const openWindow = getOpenWindow(targetDayBody);
							if (!openWindow || duration > openWindow.duration) {
								if (targetDayBody) {
									targetDayBody.classList.add('salon-shifts__day--blocked');
								}
								return;
							}
							if (targetDayBody && targetDayBody !== block.parentElement) {
								if (hasShiftForEmployeeOnDay(block.dataset.employeeId, targetDayBody, block.dataset.shiftId)) {
									targetDayBody.classList.add('salon-shifts__day--blocked');
									return;
								}
								targetDayBody.classList.remove('salon-shifts__day--blocked');
								targetDayBody.appendChild(block);
							}
							const rect = (targetDayBody || originDayBody).getBoundingClientRect();
							const offsetY = clamp(moveEvent.clientY - rect.top - clickOffsetY, 0, rect.height - blockHeightPx);
							const offsetMinutes = pixelsToMinutes(offsetY);
							const snappedTop = clamp(snapMinutes(offsetMinutes), openWindow.openStart, openWindow.openEnd - duration);
							block.style.top = `${minutesToPixels(snappedTop)}px`;

							const startMinutes = startHour * 60 + snappedTop;
							updateBlockLabels(block, startMinutes, startMinutes + duration);
						};

						const onUp = async (upEvent) => {
							document.removeEventListener('mousemove', onMove);
							document.removeEventListener('mouseup', onUp);
							block.classList.remove('is-dragging');
							document.querySelectorAll('.salon-shifts__day--blocked').forEach((el) => el.classList.remove('salon-shifts__day--blocked'));
							layoutAll();
							const droppedOnTrash = dragOverTrash || updateTrashHover(upEvent);
							hideTrash();
							if (droppedOnTrash) {
								if (!window.confirm('<?php echo esc_js( __( 'Obrisati smjenu?', 'salon-reservations' ) ); ?>')) {
									revert();
									return;
								}
								try {
									await deleteShift(block);
								} catch (error) {
									revert();
									window.alert(error.message || '<?php echo esc_js( __( 'Neuspješno brisanje smjene.', 'salon-reservations' ) ); ?>');
								}
								return;
							}

							if (block.dataset.start === originalStart && block.dataset.end === originalEnd && block.parentElement === originDayBody) {
								return;
							}

							const employeeName = chipName(block.dataset.employeeId);
							openModal(
								{
									oldTime: formatRange(originalStart, originalEnd),
									oldEmployee: employeeName,
									newTime: formatRange(block.dataset.start, block.dataset.end),
									newEmployee: employeeName,
								},
								async () => {
									try {
										await saveShift(block);
									} catch (error) {
										revert();
									}
								},
								() => {
									revert();
								}
							);
						};

						document.addEventListener('mousemove', onMove);
						document.addEventListener('mouseup', onUp);
					};

					const startResize = (event) => {
						event.preventDefault();
						event.stopPropagation();
						const originHeightPx = parseFloat(block.style.height || '0');
						const originTopPx = parseFloat(block.style.top || '0');
						const originHeight = pixelsToMinutes(originHeightPx);
						const originTop = pixelsToMinutes(originTopPx);
						const originalStart = block.dataset.start;
						const originalEnd = block.dataset.end;
						const startY = event.clientY;
						const minHeight = resizeInterval;
						const openWindow = getOpenWindow(block.closest('.salon-shifts__day-body'));

						let dragOverTrash = false;
						block.classList.add('is-dragging');
						showTrash();

						const revert = () => {
							block.style.height = `${originHeightPx}px`;
							block.dataset.start = originalStart;
							block.dataset.end = originalEnd;
							updateBlockLabels(block, timeToMinutes(originalStart), timeToMinutes(originalEnd));
							layoutAll();
						};

						const onMove = (moveEvent) => {
							dragOverTrash = updateTrashHover(moveEvent);
							const delta = moveEvent.clientY - startY;
							let height = originHeight + pixelsToMinutes(delta);
							const maxHeight = openWindow ? openWindow.openEnd - originTop : totalMinutes - originTop;
							height = clamp(height, minHeight, maxHeight);
							height = Math.round(height / resizeInterval) * resizeInterval;
							block.style.height = `${minutesToPixels(height)}px`;

							const startMinutes = startHour * 60 + originTop;
							updateBlockLabels(block, startMinutes, startMinutes + height);
						};

						const onUp = async (upEvent) => {
							document.removeEventListener('mousemove', onMove);
							document.removeEventListener('mouseup', onUp);
							block.classList.remove('is-dragging');
							layoutAll();
							const droppedOnTrash = dragOverTrash || updateTrashHover(upEvent);
							hideTrash();
							if (droppedOnTrash) {
								if (!window.confirm('<?php echo esc_js( __( 'Obrisati smjenu?', 'salon-reservations' ) ); ?>')) {
									revert();
									return;
								}
								try {
									await deleteShift(block);
								} catch (error) {
									revert();
									window.alert(error.message || '<?php echo esc_js( __( 'Neuspješno brisanje smjene.', 'salon-reservations' ) ); ?>');
								}
								return;
							}
							if (block.dataset.start === originalStart && block.dataset.end === originalEnd) {
								return;
							}

							const employeeName = chipName(block.dataset.employeeId);
							openModal(
								{
									oldTime: formatRange(originalStart, originalEnd),
									oldEmployee: employeeName,
									newTime: formatRange(block.dataset.start, block.dataset.end),
									newEmployee: employeeName,
								},
								async () => {
									try {
										await saveShift(block);
									} catch (error) {
										revert();
									}
								},
								() => {
									revert();
								}
							);
						};

						document.addEventListener('mousemove', onMove);
						document.addEventListener('mouseup', onUp);
					};

					const startResizeStart = (event) => {
						event.preventDefault();
						event.stopPropagation();
						const originHeightPx = parseFloat(block.style.height || '0');
						const originTopPx = parseFloat(block.style.top || '0');
						const originHeight = pixelsToMinutes(originHeightPx);
						const originTop = pixelsToMinutes(originTopPx);
						const originalStart = block.dataset.start;
						const originalEnd = block.dataset.end;
						const startY = event.clientY;
						const minHeight = resizeInterval;
						const openWindow = getOpenWindow(block.closest('.salon-shifts__day-body'));

						let dragOverTrash = false;
						block.classList.add('is-dragging');
						showTrash();

						const revert = () => {
							block.style.top = `${originTopPx}px`;
							block.style.height = `${originHeightPx}px`;
							block.dataset.start = originalStart;
							block.dataset.end = originalEnd;
							updateBlockLabels(block, timeToMinutes(originalStart), timeToMinutes(originalEnd));
							layoutAll();
						};

						const onMove = (moveEvent) => {
							dragOverTrash = updateTrashHover(moveEvent);
							const delta = moveEvent.clientY - startY;
							let newTop = originTop + pixelsToMinutes(delta);
							const minTop = openWindow ? openWindow.openStart : 0;
							const maxTop = openWindow ? openWindow.openEnd - minHeight : totalMinutes - minHeight;
							newTop = clamp(newTop, minTop, maxTop);
							newTop = Math.round(newTop / resizeInterval) * resizeInterval;
							const height = originHeight - (newTop - originTop);
							const safeHeight = Math.max(height, minHeight);
							block.style.top = `${minutesToPixels(newTop)}px`;
							block.style.height = `${minutesToPixels(safeHeight)}px`;

							const startMinutes = startHour * 60 + newTop;
							updateBlockLabels(block, startMinutes, startMinutes + safeHeight);
						};

						const onUp = async (upEvent) => {
							document.removeEventListener('mousemove', onMove);
							document.removeEventListener('mouseup', onUp);
							block.classList.remove('is-dragging');
							layoutAll();
							const droppedOnTrash = dragOverTrash || updateTrashHover(upEvent);
							hideTrash();
							if (droppedOnTrash) {
								if (!window.confirm('<?php echo esc_js( __( 'Obrisati smjenu?', 'salon-reservations' ) ); ?>')) {
									revert();
									return;
								}
								try {
									await deleteShift(block);
								} catch (error) {
									revert();
									window.alert(error.message || '<?php echo esc_js( __( 'Neuspješno brisanje smjene.', 'salon-reservations' ) ); ?>');
								}
								return;
							}
							if (block.dataset.start === originalStart && block.dataset.end === originalEnd) {
								return;
							}

							const employeeName = chipName(block.dataset.employeeId);
							openModal(
								{
									oldTime: formatRange(originalStart, originalEnd),
									oldEmployee: employeeName,
									newTime: formatRange(block.dataset.start, block.dataset.end),
									newEmployee: employeeName,
								},
								async () => {
									try {
										await saveShift(block);
									} catch (error) {
										revert();
									}
								},
								() => {
									revert();
								}
							);
						};

						document.addEventListener('mousemove', onMove);
						document.addEventListener('mouseup', onUp);
					};

					block.addEventListener('mousedown', startDrag);
					if (handle) {
						handle.addEventListener('mousedown', startResize);
					}
					if (handleStart) {
						handleStart.addEventListener('mousedown', startResizeStart);
					}
				};

				calendar.querySelectorAll('.salon-shifts__block').forEach((block) => {
					syncBlockPosition(block);
					attachBlockEvents(block);
				});
				layoutAll();
				if (scroll) {
					const target = scroll.querySelector(`.salon-shifts__day[data-date="${currentWeekStart}"]`)
						|| scroll.querySelector(`.salon-shifts__day[data-date="${todayDate}"]`)
						|| scroll.querySelector('.salon-shifts__day');
					if (target) {
						scroll.scrollLeft = Math.max(0, target.offsetLeft);
					}
				}

				if (scroll) {
					let isDown = false;
					let startX = 0;
					let scrollLeft = 0;
					let scrollRaf = null;

					scroll.addEventListener('mousedown', (event) => {
						if (event.target && event.target.closest('.salon-shifts__block')) {
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
								const todayEl = scroll.querySelector(`.salon-shifts__day[data-date="${todayDate}"]`);
								scrollToDay(todayEl);
								return;
							}
							const dayEl = scroll.querySelector(`.salon-shifts__day[data-date="${date}"]`);
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

				colorInputs.forEach((input) => {
					input.dataset.original = input.value;
					input.addEventListener('change', async () => {
						const employeeId = input.dataset.employeeId;
						if (!employeeId) return;
						const original = input.dataset.original || input.defaultValue || '';
						const color = input.value;
						applyColor(employeeId, color);

						const payload = new URLSearchParams();
						payload.set('action', 'salon_shift_color');
						payload.set('nonce', nonce);
						payload.set('employee_id', employeeId);
						payload.set('color', color);

						try {
							const response = await fetch(ajaxUrl, {
								method: 'POST',
								credentials: 'same-origin',
								headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
								body: payload.toString(),
							});
							const data = await response.json().catch(() => ({}));
							if (!response.ok || !data.success) {
								throw new Error('error');
							}
							input.dataset.original = color;
						} catch (error) {
							if (original) {
								input.value = original;
								applyColor(employeeId, original);
							}
						}
					});
				});
			})();
		</script>
		<?php
	}

	private function current_range_local( $year ) {
		$tz = DateTimeHelper::wp_timezone();
		$now = new DateTimeImmutable( 'now', $tz );
		$current_week_start = $now->modify( 'monday this week' );
		$year = (int) $year;
		$start = new DateTimeImmutable( $year . '-01-01', $tz );
		$end = new DateTimeImmutable( $year . '-12-31', $tz );
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
		$date = new DateTimeImmutable( $date_key, DateTimeHelper::wp_timezone() );
		$weekday = (int) $date->format( 'N' );
		$key = $map[ $weekday ] ?? 'mon';
		return $opening_settings[ $key ] ?? array( 'open' => false, 'start' => '', 'end' => '' );
	}

	private function time_to_minutes( $value ) {
		if ( ! preg_match( '/^(\\d{2}):(\\d{2})$/', $value, $matches ) ) {
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

	private function is_within_opening_hours( $start_utc, $end_utc ) {
		$start_local = DateTimeHelper::utc_to_local( $start_utc, 'Y-m-d H:i' );
		$end_local = DateTimeHelper::utc_to_local( $end_utc, 'Y-m-d H:i' );
		$start_date = substr( $start_local, 0, 10 );
		$end_date = substr( $end_local, 0, 10 );

		if ( $start_date !== $end_date ) {
			return false;
		}

		$opening = $this->get_opening_settings();
		$day = $this->opening_for_date( $start_date, $opening );
		if ( empty( $day['open'] ) ) {
			return false;
		}

		$open_start = $this->time_to_minutes( $day['start'] );
		$open_end = $this->time_to_minutes( $day['end'] );
		$start_minutes = $this->time_to_minutes( substr( $start_local, 11, 5 ) );
		$end_minutes = $this->time_to_minutes( substr( $end_local, 11, 5 ) );

		if ( $start_minutes < $open_start ) {
			return false;
		}
		if ( $end_minutes > $open_end ) {
			return false;
		}

		return true;
	}

	private function build_shift_grid( $shifts, $days, $employee_map, $employee_colors, $start_hour, $end_hour ) {
		$day_map = array();
		foreach ( $days as $day ) {
			$day_map[ $day['key'] ] = array(
				'label' => $day['label'],
				'date_label' => $day['date_label'] ?? '',
				'date_label' => $day['date_label'] ?? '',
				'shifts' => array(),
			);
		}

		$base_minutes = $start_hour * 60;
		$max_minutes = $end_hour * 60;

		foreach ( $shifts as $shift ) {
			if ( 'cancelled' === $shift->status ) {
				continue;
			}

			$local_start = DateTimeHelper::utc_to_local( $shift->start_datetime, 'Y-m-d H:i:s' );
			$local_end = DateTimeHelper::utc_to_local( $shift->end_datetime, 'Y-m-d H:i:s' );

			$start_dt = new DateTimeImmutable( $local_start, DateTimeHelper::wp_timezone() );
			$end_dt = new DateTimeImmutable( $local_end, DateTimeHelper::wp_timezone() );

			foreach ( $this->split_by_day( $start_dt, $end_dt ) as $segment ) {
				$day_key = $segment['day'];
				if ( ! isset( $day_map[ $day_key ] ) ) {
					continue;
				}

				$block = $this->build_block_position( $segment['start'], $segment['end'], $base_minutes, $max_minutes );
				if ( ! $block ) {
					continue;
				}

				$employee_name = $employee_map[ (int) $shift->employee_id ] ?? __( 'Zaposlenik', 'salon-reservations' );
				$color = $employee_colors[ (int) $shift->employee_id ]['color'] ?? '#94a3b8';
				$bg = $employee_colors[ (int) $shift->employee_id ]['bg'] ?? 'rgba(148, 163, 184, 0.18)';

				$day_map[ $day_key ]['shifts'][] = array(
					'id' => $shift->id,
					'employee_id' => $shift->employee_id,
					'employee_name' => $employee_name,
					'top' => $block['top'],
					'height' => $block['height'],
					'time' => $block['time_label'],
					'start' => $block['start_label'],
					'end' => $block['end_label'],
					'color' => $color,
					'bg' => $bg,
				);
			}
		}

		return $day_map;
	}

	private function split_by_day( DateTimeImmutable $start, DateTimeImmutable $end ) {
		$segments = array();
		$current = $start;

		while ( $current->format( 'Y-m-d' ) < $end->format( 'Y-m-d' ) ) {
			$segment_end = $current->setTime( 23, 59, 59 );
			$segments[] = array(
				'day' => $current->format( 'Y-m-d' ),
				'start' => $current,
				'end' => $segment_end,
			);
			$current = $segment_end->modify( '+1 second' );
		}

		$segments[] = array(
			'day' => $current->format( 'Y-m-d' ),
			'start' => $current,
			'end' => $end,
		);

		return $segments;
	}

	private function build_block_position( DateTimeImmutable $start, DateTimeImmutable $end, $base_minutes, $max_minutes ) {
		$start_minutes = ( (int) $start->format( 'H' ) * 60 ) + (int) $start->format( 'i' );
		$end_minutes = ( (int) $end->format( 'H' ) * 60 ) + (int) $end->format( 'i' );

		$start_minutes = max( $start_minutes, $base_minutes );
		$end_minutes = min( $end_minutes, $max_minutes );

		if ( $end_minutes <= $start_minutes ) {
			return null;
		}

		$top = $start_minutes - $base_minutes;
		$height = max( $end_minutes - $start_minutes, 16 );

		return array(
			'top' => $top,
			'height' => $height,
			'time_label' => $start->format( 'H:i' ) . ' - ' . $end->format( 'H:i' ),
			'start_label' => $start->format( 'Y-m-d H:i' ),
			'end_label' => $end->format( 'Y-m-d H:i' ),
		);
	}

	private function normalize_datetime_input( $input ) {
		$input = trim( $input );
		if ( strlen( $input ) === 16 ) {
			return $input . ':00';
		}
		return $input;
	}

	private function employee_has_shift_on_date( $employee_id, $start_utc, $exclude_shift_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'salon_shifts';
		$local_date = DateTimeHelper::utc_to_local( $start_utc, 'Y-m-d' );
		$start_day_utc = DateTimeHelper::local_to_utc( $local_date . ' 00:00:00' );
		$end_day_utc = DateTimeHelper::local_to_utc( $local_date . ' 23:59:59' );

		$query = "SELECT id FROM {$table} WHERE employee_id = %d AND status != %s AND start_datetime >= %s AND start_datetime <= %s";
		$params = array( (int) $employee_id, 'cancelled', $start_day_utc, $end_day_utc );
		if ( $exclude_shift_id ) {
			$query .= ' AND id != %d';
			$params[] = (int) $exclude_shift_id;
		}

		$sql = $wpdb->prepare( $query, $params );
		$existing = $wpdb->get_var( $sql );
		return ! empty( $existing );
	}
}
