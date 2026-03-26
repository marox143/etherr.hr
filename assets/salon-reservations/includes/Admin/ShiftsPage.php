<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Repositories\ShiftsRepository;
use Salon\Reservations\Repositories\ReservationsRepository;
use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Utils\DateTimeHelper;
use Salon\Reservations\Utils\StatusHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShiftsPage {
	public function handle_actions() {
		if ( isset( $_POST['salon_shift_action'] ) && 'create' === $_POST['salon_shift_action'] ) {
			$this->handle_create();
		}

		if ( isset( $_GET['salon_shift_action'], $_GET['shift_id'] ) ) {
			$this->handle_update_status();
		}
	}

	private function handle_create() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			return;
		}

		check_admin_referer( 'salon_shift_create' );

		$employee_id = current_user_can( Capabilities::MANAGE_SHIFTS_ALL ) && isset( $_POST['employee_id'] ) ? (int) $_POST['employee_id'] : get_current_user_id();
		$template_index = sanitize_text_field( wp_unslash( $_POST['shift_template'] ?? '' ) );
		$template_date = sanitize_text_field( wp_unslash( $_POST['shift_date'] ?? '' ) );
		$start_input = sanitize_text_field( wp_unslash( $_POST['start_datetime'] ?? '' ) );
		$end_input = sanitize_text_field( wp_unslash( $_POST['end_datetime'] ?? '' ) );

		$templates = $this->get_shift_templates();
		if ( '' !== $template_index && '' !== $template_date && isset( $templates[ $template_index ] ) ) {
			$template = $templates[ $template_index ];
			$start_input = $template_date . 'T' . $template['start'];
			$end_input = $template_date . 'T' . $template['end'];
		}

		if ( empty( $start_input ) || empty( $end_input ) ) {
			return;
		}

		$start_utc = DateTimeHelper::local_to_utc_from_input( $start_input );
		$end_utc = DateTimeHelper::local_to_utc_from_input( $end_input );
		if ( strtotime( $end_utc ) <= strtotime( $start_utc ) ) {
			$end_utc = gmdate( 'Y-m-d H:i:s', strtotime( $end_utc . ' +1 day' ) );
		}

		if ( strtotime( $start_utc ) >= strtotime( $end_utc ) ) {
			return;
		}

		$status = current_user_can( Capabilities::MANAGE_SHIFTS_ALL ) ? 'approved' : 'pending';

		$repo = new ShiftsRepository();
		$repo->create(
			array(
				'employee_id' => $employee_id,
				'start_datetime' => $start_utc,
				'end_datetime' => $end_utc,
				'status' => $status,
				'created_by' => get_current_user_id(),
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations-shifts' ) );
		exit;
	}

	private function handle_update_status() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_ALL ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['salon_shift_action'] ) );
		$shift_id = (int) $_GET['shift_id'];

		if ( ! in_array( $action, array( 'approve', 'cancel' ), true ) ) {
			return;
		}

		check_admin_referer( 'salon_shift_action_' . $shift_id );

		$status = $action === 'approve' ? 'approved' : 'cancelled';
		$repo = new ShiftsRepository();
		$repo->update( $shift_id, array( 'status' => $status ) );

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations-shifts' ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( Capabilities::MANAGE_SHIFTS_OWN ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$is_admin = current_user_can( Capabilities::MANAGE_SHIFTS_ALL );
		$repo = new ShiftsRepository();
		$shifts = $is_admin ? $repo->list_all() : $repo->list_by_employee( get_current_user_id() );
		$templates = $this->get_shift_templates();

		$reservations_repo = new ReservationsRepository();
		$reservations = $is_admin
			? $reservations_repo->list( array( 'status' => 'approved' ) )
			: $reservations_repo->list_for_employee( get_current_user_id(), 'approved' );

		$employees = $this->get_employees();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Smjene', 'salon-reservations' ); ?></h1>

			<form method="post" class="salon-admin-form">
				<?php wp_nonce_field( 'salon_shift_create' ); ?>
				<input type="hidden" name="salon_shift_action" value="create" />

				<?php if ( $is_admin ) : ?>
					<select name="employee_id" required>
						<?php foreach ( $employees as $employee ) : ?>
							<option value="<?php echo esc_attr( $employee->ID ); ?>"><?php echo esc_html( $employee->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<?php if ( ! empty( $templates ) ) : ?>
					<select name="shift_template">
						<option value=""><?php esc_html_e( 'Predložak smjene (opcionalno)', 'salon-reservations' ); ?></option>
						<?php foreach ( $templates as $index => $template ) : ?>
							<option value="<?php echo esc_attr( $index ); ?>">
								<?php echo esc_html( $template['label'] ?? '' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="date" name="shift_date" />
				<?php endif; ?>
				<input type="datetime-local" name="start_datetime" />
				<input type="datetime-local" name="end_datetime" />
				<p class="description"><?php esc_html_e( 'Koristite predložak + datum ili unesite početak/kraj.', 'salon-reservations' ); ?></p>
				<button class="button button-primary"><?php esc_html_e( 'Dodaj smjenu', 'salon-reservations' ); ?></button>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Zaposlenik', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Početak', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Kraj', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Status', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Akcije', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $shifts ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'Nema smjena.', 'salon-reservations' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $shifts as $shift ) : ?>
							<?php
								$employee = get_user_by( 'id', $shift->employee_id );
								$action_url = wp_nonce_url( admin_url( 'admin.php?page=salon-reservations-shifts&shift_id=' . $shift->id ), 'salon_shift_action_' . $shift->id );
							?>
							<tr>
								<td><?php echo esc_html( $employee ? $employee->display_name : __( 'Zaposlenik', 'salon-reservations' ) ); ?></td>
								<td><?php echo esc_html( DateTimeHelper::utc_to_local( $shift->start_datetime, 'd.m.Y. H:i' ) ); ?></td>
								<td><?php echo esc_html( DateTimeHelper::utc_to_local( $shift->end_datetime, 'd.m.Y. H:i' ) ); ?></td>
								<td><?php echo esc_html( StatusHelper::label( $shift->status ) ); ?></td>
								<td>
									<?php if ( $is_admin && 'pending' === $shift->status ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_shift_action=approve' ); ?>"><?php esc_html_e( 'Odobri', 'salon-reservations' ); ?></a>
									<?php endif; ?>
									<?php if ( $is_admin ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_shift_action=cancel' ); ?>"><?php esc_html_e( 'Otkaži', 'salon-reservations' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Nadolazeće rezervacije', 'salon-reservations' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Zaposlenik', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Klijent', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Početak', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Status', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $reservations ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'Nema rezervacija.', 'salon-reservations' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $reservations as $reservation ) : ?>
							<?php $employee = get_user_by( 'id', $reservation->employee_id ); ?>
							<tr>
								<td><?php echo esc_html( $employee ? $employee->display_name : __( 'Zaposlenik', 'salon-reservations' ) ); ?></td>
								<td><?php echo esc_html( $reservation->customer_name ); ?></td>
								<td><?php echo esc_html( DateTimeHelper::utc_to_local( $reservation->start_datetime, 'd.m.Y. H:i' ) ); ?></td>
								<td><?php echo esc_html( StatusHelper::label( $reservation->status ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
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

	private function get_shift_templates() {
		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$templates = $settings['shift_templates'] ?? array();
		if ( ! is_array( $templates ) ) {
			return array();
		}
		if ( empty( $templates ) ) {
			$templates = array(
				array( 'label' => '08:00-16:00', 'start' => '08:00', 'end' => '16:00' ),
				array( 'label' => '14:00-20:00', 'start' => '14:00', 'end' => '20:00' ),
				array( 'label' => '10:00-18:00', 'start' => '10:00', 'end' => '18:00' ),
			);
		}
		return array_values( $templates );
	}
}
