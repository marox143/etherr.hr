<?php
namespace Salon\Reservations\Frontend;

use Salon\Reservations\Repositories\ServicesRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shortcodes {
	private $assets_enqueued = false;

	public function register() {
		add_shortcode( 'salon_reservations', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}

	public function maybe_enqueue_assets() {
		if ( $this->assets_enqueued ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post || false === strpos( $post->post_content, '[salon_reservations' ) ) {
			return;
		}

		$this->assets_enqueued = true;
		wp_enqueue_style( 'salon-reservations-frontend', SALON_RESERVATIONS_URL . 'assets/css/frontend.css', array(), SALON_RESERVATIONS_VERSION );
		wp_enqueue_script( 'salon-reservations-frontend', SALON_RESERVATIONS_URL . 'assets/js/frontend.js', array(), SALON_RESERVATIONS_VERSION, true );

		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );

		wp_localize_script(
			'salon-reservations-frontend',
			'SalonReservations',
			array(
				'restUrl' => esc_url_raw( rest_url( 'salon/v1' ) ),
				'nonce' => wp_create_nonce( 'salon_reservation' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'slotInterval' => isset( $settings['slot_interval_minutes'] ) ? (int) $settings['slot_interval_minutes'] : 15,
				'timezone' => wp_timezone_string(),
				'isLoggedIn' => is_user_logged_in(),
				'loginUrl' => wp_login_url( get_permalink() ),
				'i18n' => array(
					'loading' => __( 'Učitavanje termina...', 'salon-reservations' ),
					'noSlots' => __( 'Nema dostupnih termina.', 'salon-reservations' ),
					'error' => __( 'Neuspješno dohvaćanje termina. Pokušajte ponovno.', 'salon-reservations' ),
					'submitError' => __( 'Neuspješno slanje rezervacije.', 'salon-reservations' ),
					'submitSuccess' => __( 'Zahtjev za rezervaciju je poslan.', 'salon-reservations' ),
					'selectSlot' => __( 'Odaberite termin.', 'salon-reservations' ),
					'firstAvailable' => __( 'Prvi slobodan termin', 'salon-reservations' ),
				),
			)
		);
	}

	public function render() {
		$services_repo = new ServicesRepository();
		$services = $services_repo->all_active();
		$employees = $this->get_employees();

		if ( empty( $services ) || empty( $employees ) ) {
			return '<p>' . esc_html__( 'Nema dostupnih usluga ili zaposlenika.', 'salon-reservations' ) . '</p>';
		}

		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$addons = $settings['service_addons'] ?? array();
		if ( empty( $addons ) ) {
			$addons = array(
				array( 'label' => 'Pranje kose', 'active' => true ),
				array( 'label' => 'Ispiranje kose vodom', 'active' => true ),
				array( 'label' => 'Masaža vlasišta', 'active' => true ),
				array( 'label' => 'Dizajn', 'active' => true ),
			);
		}
		$addons = array_filter(
			$addons,
			function ( $addon ) {
				return ! empty( $addon['active'] );
			}
		);

		$current_user = wp_get_current_user();
		$prefill_name = $current_user && $current_user->ID ? $current_user->display_name : '';
		$prefill_email = $current_user && $current_user->ID ? $current_user->user_email : '';
		$prefill_phone = '';
		if ( $current_user && $current_user->ID ) {
			$prefill_phone = (string) get_user_meta( $current_user->ID, 'salon_phone', true );
			if ( '' === $prefill_phone ) {
				$prefill_phone = (string) get_user_meta( $current_user->ID, 'phone', true );
			}
			if ( '' === $prefill_phone ) {
				$prefill_phone = (string) get_user_meta( $current_user->ID, 'billing_phone', true );
			}
		}

		ob_start();
		?>
		<div class="salon-reservations">
			<form class="salon-reservations__form" data-salon-form="reservation">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'salon_reservation' ) ); ?>" />
				<input type="hidden" name="employee_id" value="" />
				<input type="hidden" name="start_datetime" value="" />

				<div class="salon-reservations__step">
					<h3><?php esc_html_e( 'Vrsta usluge', 'salon-reservations' ); ?></h3>
					<div class="salon-reservations__field">
						<label for="salon-service"><?php esc_html_e( 'Vrsta usluge', 'salon-reservations' ); ?></label>
						<select id="salon-service" name="service_id" required>
							<option value=""><?php esc_html_e( 'Odaberite uslugu', 'salon-reservations' ); ?></option>
							<?php foreach ( $services as $service ) : ?>
								<option value="<?php echo esc_attr( $service->id ); ?>" data-duration="<?php echo esc_attr( $service->duration_minutes ); ?>">
									<?php echo esc_html( $service->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="salon-reservations__step">
					<h3><?php esc_html_e( 'Dodatne opcije', 'salon-reservations' ); ?></h3>
					<details class="salon-reservations__addons">
						<summary><?php esc_html_e( 'Odaberite dodatne opcije', 'salon-reservations' ); ?></summary>
						<div class="salon-reservations__addons-list">
							<?php foreach ( $addons as $addon ) : ?>
								<label>
									<input type="checkbox" name="addons[]" value="<?php echo esc_attr( $addon['label'] ); ?>" />
									<?php echo esc_html( $addon['label'] ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</details>
				</div>

				<div class="salon-reservations__step">
					<div class="salon-reservations__field">
						<label for="salon-employee"><?php esc_html_e( 'Zaposlenik', 'salon-reservations' ); ?></label>
						<select id="salon-employee" name="employee_choice" required>
							<option value="0"><?php esc_html_e( 'Prvi slobodan zaposlenik', 'salon-reservations' ); ?></option>
							<?php foreach ( $employees as $employee ) : ?>
								<option value="<?php echo esc_attr( $employee->ID ); ?>"><?php echo esc_html( $employee->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="salon-reservations__step">
					<div class="salon-reservations__actions">
						<button type="button" class="salon-reservations__button" data-salon-first-available>
							<?php esc_html_e( 'Prvi slobodan termin', 'salon-reservations' ); ?>
						</button>
					</div>
					<input type="hidden" name="date" data-salon-date value="" />
					<div class="salon-reservations__field">
						<label><?php esc_html_e( 'Odaberite datum', 'salon-reservations' ); ?></label>
						<div class="salon-reservations__calendar" data-salon-calendar>
							<div class="salon-reservations__calendar-header">
								<button type="button" class="salon-reservations__calendar-nav" data-salon-calendar-prev aria-label="<?php esc_attr_e( 'Prethodni mjesec', 'salon-reservations' ); ?>">&lsaquo;</button>
								<span class="salon-reservations__calendar-month" data-salon-calendar-month></span>
								<button type="button" class="salon-reservations__calendar-nav" data-salon-calendar-next aria-label="<?php esc_attr_e( 'Sljedeći mjesec', 'salon-reservations' ); ?>">&rsaquo;</button>
							</div>
							<div class="salon-reservations__calendar-weekdays">
								<span><?php esc_html_e( 'MON', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'TUE', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'WED', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'THU', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'FRI', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'SAT', 'salon-reservations' ); ?></span>
								<span><?php esc_html_e( 'SUN', 'salon-reservations' ); ?></span>
							</div>
							<div class="salon-reservations__calendar-grid" data-salon-calendar-grid></div>
						</div>
					</div>
					<div class="salon-reservations__field">
						<label><?php esc_html_e( 'Dostupni termini', 'salon-reservations' ); ?></label>
						<div class="salon-reservations__slots" data-salon-slots></div>
					</div>
				</div>

				<div class="salon-reservations__step">
					<h3><?php esc_html_e( '5. Vaši podaci', 'salon-reservations' ); ?></h3>
					<?php if ( ! is_user_logged_in() ) : ?>
						<p class="salon-reservations__hint">
							<?php
							echo wp_kses_post(
								sprintf(
									__( 'Ako već imate račun, <a href="%s">prijavite se</a>.', 'salon-reservations' ),
									esc_url( wp_login_url( get_permalink() ) )
								)
							);
							?>
						</p>
					<?php endif; ?>
					<div class="salon-reservations__field">
						<label for="salon-name"><?php esc_html_e( 'Ime i prezime', 'salon-reservations' ); ?></label>
						<input id="salon-name" type="text" name="customer_name" value="<?php echo esc_attr( $prefill_name ); ?>" required />
					</div>
					<div class="salon-reservations__field">
						<label for="salon-email"><?php esc_html_e( 'Email', 'salon-reservations' ); ?></label>
						<input id="salon-email" type="email" name="customer_email" value="<?php echo esc_attr( $prefill_email ); ?>" required />
					</div>
					<div class="salon-reservations__field">
						<label for="salon-phone"><?php esc_html_e( 'Telefon', 'salon-reservations' ); ?></label>
						<input id="salon-phone" type="tel" name="customer_phone" value="<?php echo esc_attr( $prefill_phone ); ?>" />
					</div>
					<div class="salon-reservations__field">
						<label for="salon-notes"><?php esc_html_e( 'Napomena', 'salon-reservations' ); ?></label>
						<textarea id="salon-notes" name="notes" rows="3"></textarea>
					</div>
					<?php if ( ! is_user_logged_in() ) : ?>
						<div class="salon-reservations__field">
							<label><input type="checkbox" name="create_account" value="1" /> <?php esc_html_e( 'Kreiraj račun koristeći navedene podatke', 'salon-reservations' ); ?></label>
						</div>
					<?php endif; ?>
				</div>

				<input type="text" name="company" class="salon-reservations__hp" tabindex="-1" autocomplete="off" />

				<div class="salon-reservations__actions">
					<button type="submit" class="salon-reservations__submit"><?php esc_html_e( 'Pošalji zahtjev', 'salon-reservations' ); ?></button>
				</div>
				<div class="salon-reservations__message" data-salon-message></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_employees() {
		$users = get_users(
			array(
				'role__in' => array( 'editor' ),
				'orderby' => 'display_name',
				'order' => 'ASC',
			)
		);

		return $users;
	}
}
