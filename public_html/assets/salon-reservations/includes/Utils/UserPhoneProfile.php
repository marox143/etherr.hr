<?php
namespace Salon\Reservations\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UserPhoneProfile {
	public function register() {
		add_action( 'show_user_profile', array( $this, 'render_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_field' ) );
	}

	public function render_field( $user ) {
		if ( ! $user instanceof \WP_User || ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$phone = (string) get_user_meta( $user->ID, 'salon_phone', true );
		wp_nonce_field( 'salon_save_user_phone', 'salon_user_phone_nonce' );
		?>
		<h2><?php esc_html_e( 'Salon podaci', 'salon-reservations' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="salon_phone"><?php esc_html_e( 'Telefon', 'salon-reservations' ); ?></label></th>
				<td>
					<input
						type="text"
						name="salon_phone"
						id="salon_phone"
						value="<?php echo esc_attr( $phone ); ?>"
						class="regular-text"
					/>
					<p class="description"><?php esc_html_e( 'Koristi se za automatsko popunjavanje rezervacija.', 'salon-reservations' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_field( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( ! isset( $_POST['salon_user_phone_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['salon_user_phone_nonce'] ) ), 'salon_save_user_phone' ) ) {
			return;
		}

		$phone = '';
		if ( isset( $_POST['salon_phone'] ) ) {
			$phone = sanitize_text_field( wp_unslash( $_POST['salon_phone'] ) );
		}

		if ( '' === $phone ) {
			delete_user_meta( $user_id, 'salon_phone' );
			return;
		}

		update_user_meta( $user_id, 'salon_phone', $phone );
	}
}
