<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Repositories\EmployeesRepository;
use Salon\Reservations\Utils\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmployeesPage {
	public function handle_actions() {
		if ( isset( $_POST['salon_employees_action'] ) && 'sync' === $_POST['salon_employees_action'] ) {
			$this->sync();
		}
	}

	private function sync() {
		if ( ! current_user_can( Capabilities::MANAGE_EMPLOYEES ) ) {
			return;
		}

		check_admin_referer( 'salon_employees_sync' );

		$repo = new EmployeesRepository();
		$employees = $this->get_employees();

		foreach ( $employees as $employee ) {
			$repo->upsert_for_user( $employee );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=zaposlenici&synced=1' ) );
		exit;
	}

	public function render( $embedded = false ) {
		if ( ! current_user_can( Capabilities::MANAGE_EMPLOYEES ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$employees = $this->get_employees();
		?>
		<div class="<?php echo esc_attr( $embedded ? 'salon-tabs__panel' : 'wrap' ); ?>">
			<?php if ( ! $embedded ) : ?>
				<h1><?php esc_html_e( 'Zaposlenici', 'salon-reservations' ); ?></h1>
			<?php endif; ?>
			<?php if ( isset( $_GET['synced'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Zaposlenici su sinkronizirani.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'salon_employees_sync' ); ?>
				<input type="hidden" name="salon_employees_action" value="sync" />
				<?php submit_button( __( 'Sinkroniziraj zaposlenike', 'salon-reservations' ), 'secondary' ); ?>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Korisnik', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Email', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $employees ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'Nema zaposlenika.', 'salon-reservations' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $employees as $employee ) : ?>
							<tr>
								<td><?php echo esc_html( $employee->display_name ); ?></td>
								<td><?php echo esc_html( $employee->user_email ); ?></td>
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
}
