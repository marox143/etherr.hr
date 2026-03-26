<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Repositories\ShiftChangeRequestsRepository;
use Salon\Reservations\Repositories\ShiftsRepository;
use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Utils\DateTimeHelper;
use Salon\Reservations\Utils\StatusHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShiftChangeRequestsPage {
	public function handle_actions() {
		if ( isset( $_POST['salon_shift_change_action'] ) && 'create' === $_POST['salon_shift_change_action'] ) {
			$this->handle_create();
		}

		if ( isset( $_GET['salon_shift_change_action'], $_GET['request_id'] ) ) {
			$this->handle_update();
		}
	}

	private function handle_create() {
		if ( ! current_user_can( Capabilities::REQUEST_SHIFT_CHANGE ) ) {
			return;
		}

		check_admin_referer( 'salon_shift_change_create' );

		$shift_id = (int) $_POST['shift_id'];
		$start_input = sanitize_text_field( wp_unslash( $_POST['requested_start'] ?? '' ) );
		$end_input = sanitize_text_field( wp_unslash( $_POST['requested_end'] ?? '' ) );
		$reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $shift_id || empty( $start_input ) || empty( $end_input ) ) {
			return;
		}

		$requested_start = DateTimeHelper::local_to_utc_from_input( $start_input );
		$requested_end = DateTimeHelper::local_to_utc_from_input( $end_input );

		if ( strtotime( $requested_start ) >= strtotime( $requested_end ) ) {
			return;
		}

		$repo = new ShiftChangeRequestsRepository();
		$repo->create(
			array(
				'shift_id' => $shift_id,
				'requested_start' => $requested_start,
				'requested_end' => $requested_end,
				'reason' => $reason,
				'status' => 'pending',
				'created_by' => get_current_user_id(),
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations-shift-changes' ) );
		exit;
	}

	private function handle_update() {
		if ( ! current_user_can( Capabilities::APPROVE_SHIFT_CHANGES ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['salon_shift_change_action'] ) );
		$request_id = (int) $_GET['request_id'];

		if ( ! in_array( $action, array( 'approve', 'deny' ), true ) ) {
			return;
		}

		check_admin_referer( 'salon_shift_change_action_' . $request_id );

		$repo = new ShiftChangeRequestsRepository();
		$request = $repo->get( $request_id );
		if ( ! $request ) {
			return;
		}

		$repo->update_status( $request_id, $action === 'approve' ? 'approved' : 'denied' );

		if ( 'approve' === $action ) {
			$shifts = new ShiftsRepository();
			$shifts->update(
				$request->shift_id,
				array(
					'start_datetime' => $request->requested_start,
					'end_datetime' => $request->requested_end,
					'status' => 'approved',
				)
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations-shift-changes' ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( Capabilities::REQUEST_SHIFT_CHANGE ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$is_admin = current_user_can( Capabilities::APPROVE_SHIFT_CHANGES );
		$repo = new ShiftChangeRequestsRepository();
		$requests = $is_admin ? $repo->list() : $repo->list( array( 'created_by' => get_current_user_id() ) );

		$shifts_repo = new ShiftsRepository();
		$own_shifts = $shifts_repo->list_by_employee( get_current_user_id() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Zahtjevi za promjenu smjene', 'salon-reservations' ); ?></h1>

			<form method="post" class="salon-admin-form">
				<?php wp_nonce_field( 'salon_shift_change_create' ); ?>
				<input type="hidden" name="salon_shift_change_action" value="create" />
				<select name="shift_id" required>
					<?php foreach ( $own_shifts as $shift ) : ?>
						<option value="<?php echo esc_attr( $shift->id ); ?>">
							<?php echo esc_html( DateTimeHelper::utc_to_local( $shift->start_datetime, 'd.m.Y. H:i' ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="datetime-local" name="requested_start" required />
				<input type="datetime-local" name="requested_end" required />
				<input type="text" name="reason" placeholder="<?php esc_attr_e( 'Razlog', 'salon-reservations' ); ?>" />
				<button class="button button-primary"><?php esc_html_e( 'Pošalji zahtjev', 'salon-reservations' ); ?></button>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Smjena', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Traženi početak', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Traženi kraj', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Status', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Akcije', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $requests ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'Nema zahtjeva.', 'salon-reservations' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $requests as $request ) : ?>
							<?php $action_url = wp_nonce_url( admin_url( 'admin.php?page=salon-reservations-shift-changes&request_id=' . $request->id ), 'salon_shift_change_action_' . $request->id ); ?>
							<tr>
								<td><?php echo esc_html( $request->shift_id ); ?></td>
								<td><?php echo esc_html( DateTimeHelper::utc_to_local( $request->requested_start, 'd.m.Y. H:i' ) ); ?></td>
								<td><?php echo esc_html( DateTimeHelper::utc_to_local( $request->requested_end, 'd.m.Y. H:i' ) ); ?></td>
								<td><?php echo esc_html( StatusHelper::label( $request->status ) ); ?></td>
								<td>
									<?php if ( $is_admin && 'pending' === $request->status ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_shift_change_action=approve' ); ?>"><?php esc_html_e( 'Odobri', 'salon-reservations' ); ?></a>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_shift_change_action=deny' ); ?>"><?php esc_html_e( 'Odbij', 'salon-reservations' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
