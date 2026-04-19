<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Repositories\ServicesRepository;
use Salon\Reservations\Utils\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ServicesPage {
	public function handle_actions() {
		if ( isset( $_POST['salon_service_action'] ) && 'create' === $_POST['salon_service_action'] ) {
			$this->handle_create();
		}

		if ( isset( $_POST['salon_service_action'] ) && 'update' === $_POST['salon_service_action'] ) {
			$this->handle_update();
		}

		if ( isset( $_POST['salon_settings_action'], $_POST['settings_section'] )
			&& 'save' === $_POST['salon_settings_action']
			&& 'addons' === $_POST['settings_section'] ) {
			$this->handle_addons();
		}

		if ( isset( $_GET['salon_service_action'], $_GET['service_id'] ) ) {
			$this->handle_toggle();
		}
	}

	private function handle_create() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'salon_service_create' );

		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$duration = (int) ( $_POST['duration_minutes'] ?? 0 );

		if ( empty( $name ) || $duration <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&error=1' ) );
			exit;
		}

		$repo = new ServicesRepository();
		$repo->insert(
			array(
				'name' => $name,
				'duration_minutes' => $duration,
				'price' => null,
				'status' => 'active',
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&created=1' ) );
		exit;
	}

	private function handle_update() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'salon_service_update' );

		$service_id = (int) ( $_POST['service_id'] ?? 0 );
		$duration = (int) ( $_POST['duration_minutes'] ?? 0 );

		if ( ! $service_id || $duration <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&error=1' ) );
			exit;
		}

		$repo = new ServicesRepository();
		$repo->update( $service_id, array( 'duration_minutes' => $duration ) );

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&updated=1' ) );
		exit;
	}

	private function handle_toggle() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['salon_service_action'] ) );
		$service_id = (int) $_GET['service_id'];

		if ( ! in_array( $action, array( 'enable', 'disable' ), true ) ) {
			return;
		}

		check_admin_referer( 'salon_service_toggle_' . $service_id );

		$repo = new ServicesRepository();
		$status = $action === 'enable' ? 'active' : 'inactive';
		$repo->update( $service_id, array( 'status' => $status ) );

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&updated=1' ) );
		exit;
	}

	private function handle_addons() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'salon_settings_save' );

		$current = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$posted_addons = isset( $_POST['service_addons'] ) ? (array) $_POST['service_addons'] : array();
		$posted_addons = array_map( 'sanitize_text_field', $posted_addons );
		$existing_addons = $current['service_addons'] ?? array();
		$updated_addons = array();
		foreach ( $existing_addons as $addon ) {
			$label = isset( $addon['label'] ) ? $addon['label'] : '';
			if ( '' === $label ) {
				continue;
			}
			$updated_addons[] = array(
				'label' => $label,
				'active' => in_array( $label, $posted_addons, true ),
			);
		}
		$new_addon = sanitize_text_field( wp_unslash( $_POST['new_addon'] ?? '' ) );
		if ( '' !== $new_addon ) {
			$updated_addons[] = array( 'label' => $new_addon, 'active' => true );
		}
		if ( ! empty( $updated_addons ) ) {
			$current['service_addons'] = $updated_addons;
		}

		update_option( SALON_RESERVATIONS_OPTION_SETTINGS, $current );

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=usluge&addons_updated=1' ) );
		exit;
	}

	public function render( $embedded = false ) {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$repo = new ServicesRepository();
		$services = $repo->all();
		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		?>
		<div class="<?php echo esc_attr( $embedded ? 'salon-tabs__panel' : 'wrap' ); ?>">
			<?php if ( ! $embedded ) : ?>
				<h1><?php esc_html_e( 'Usluge', 'salon-reservations' ); ?></h1>
			<?php endif; ?>
			<?php if ( isset( $_GET['created'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Usluga je dodana.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Usluga je ažurirana.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['addons_updated'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Opcije su spremljene.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['error'] ) ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Provjerite unesene podatke.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Dodaj novu uslugu', 'salon-reservations' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'salon_service_create' ); ?>
				<input type="hidden" name="salon_service_action" value="create" />
				<input type="text" name="name" placeholder="<?php esc_attr_e( 'Vrsta usluge', 'salon-reservations' ); ?>" required />
				<input type="number" name="duration_minutes" placeholder="<?php esc_attr_e( 'Trajanje (min)', 'salon-reservations' ); ?>" required />
				<button class="button button-primary"><?php esc_html_e( 'Dodaj', 'salon-reservations' ); ?></button>
			</form>

			<h2><?php esc_html_e( 'Postojeće usluge', 'salon-reservations' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Vrsta usluge', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Trajanje (min)', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Status', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Akcije', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $services ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'Nema usluga.', 'salon-reservations' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $services as $service ) : ?>
							<?php
								$action_url = wp_nonce_url(
									admin_url( 'admin.php?page=salon-reservations&tab=usluge&service_id=' . $service->id ),
									'salon_service_toggle_' . $service->id
								);
							?>
							<tr>
								<td><?php echo esc_html( $service->name ); ?></td>
								<td>
									<form method="post" style="display:flex; gap:8px; align-items:center;">
										<?php wp_nonce_field( 'salon_service_update' ); ?>
										<input type="hidden" name="salon_service_action" value="update" />
										<input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>" />
										<input type="number" name="duration_minutes" value="<?php echo esc_attr( $service->duration_minutes ); ?>" style="width:100px;" />
										<button class="button button-small"><?php esc_html_e( 'Spremi', 'salon-reservations' ); ?></button>
									</form>
								</td>
								<td><?php echo esc_html( 'active' === $service->status ? __( 'Aktivno', 'salon-reservations' ) : __( 'Neaktivno', 'salon-reservations' ) ); ?></td>
								<td>
									<?php if ( 'active' === $service->status ) : ?>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_service_action=disable' ); ?>"><?php esc_html_e( 'Isključi', 'salon-reservations' ); ?></a>
									<?php else : ?>
										<a class="button button-small" href="<?php echo esc_url( $action_url . '&salon_service_action=enable' ); ?>"><?php esc_html_e( 'Uključi', 'salon-reservations' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Dodatne opcije usluge', 'salon-reservations' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'salon_settings_save' ); ?>
				<input type="hidden" name="salon_settings_action" value="save" />
				<input type="hidden" name="settings_section" value="addons" />
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Aktivne opcije', 'salon-reservations' ); ?></th>
						<td>
							<?php
							$addons = $settings['service_addons'] ?? array();
							if ( empty( $addons ) ) {
								$addons = array(
									array( 'label' => 'Pranje kose', 'active' => true ),
									array( 'label' => 'Ispiranje kose vodom', 'active' => true ),
									array( 'label' => 'Masaža vlasišta', 'active' => true ),
									array( 'label' => 'Dizajn', 'active' => true ),
									array( 'label' => 'Ulje za bradu', 'active' => true ),
									array( 'label' => 'Maska za kosu', 'active' => true ),
								);
							}
							foreach ( $addons as $addon ) :
								$label = $addon['label'] ?? '';
								if ( '' === $label ) {
									continue;
								}
								$active = ! empty( $addon['active'] );
								?>
								<label style="display:block; margin-bottom:6px;">
									<input type="checkbox" name="service_addons[]" value="<?php echo esc_attr( $label ); ?>" <?php checked( $active ); ?> />
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="new_addon"><?php esc_html_e( 'Dodaj novu opciju', 'salon-reservations' ); ?></label></th>
						<td><input type="text" id="new_addon" name="new_addon" placeholder="<?php esc_attr_e( 'Naziv opcije', 'salon-reservations' ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Spremi opcije', 'salon-reservations' ) ); ?>
			</form>
		</div>
		<?php
	}
}
