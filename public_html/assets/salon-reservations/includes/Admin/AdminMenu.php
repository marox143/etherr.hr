<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Admin\EmployeesPage;
use Salon\Reservations\Admin\EmployeeCalendarPage;
use Salon\Reservations\Admin\ServicesPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {
	public function register() {
		add_menu_page(
			__( 'Rezervacije', 'salon-reservations' ),
			__( 'Rezervacije', 'salon-reservations' ),
			Capabilities::MANAGE_SHIFTS_OWN,
			'salon-reservations',
			array( $this, 'render_entry' ),
			'dashicons-calendar-alt'
		);
		add_filter( 'admin_footer_text', array( $this, 'filter_admin_footer_text' ), 10, 1 );
		add_filter( 'update_footer', array( $this, 'filter_admin_footer_version' ), 10, 1 );
	}

	public function handle_actions() {
		( new ReservationsPage() )->handle_actions();
		( new EmployeesPage() )->handle_actions();
		( new EmployeeCalendarPage() )->handle_actions();
		( new ServicesPage() )->handle_actions();
		( new ShiftsPage() )->handle_actions();
		( new SettingsPage() )->handle_actions();
	}

	public function render_entry() {
		$tabs = $this->get_tabs();
		$available = array_filter(
			$tabs,
			static function( $tab ) {
				return ! empty( $tab['can'] );
			}
		);

		if ( empty( $available ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$requested = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) );
		$active = array_key_exists( $requested, $available ) ? $requested : array_key_first( $available );

		?>
		<div class="wrap salon-tabs">
			<style>
				.salon-tabs__bar { position: fixed; top: 32px; left: calc(160px + 20px + 10px); right: 20px; z-index: 1000; display: flex; flex-wrap: wrap; gap: 10px; margin: 0; padding: 10px 0 12px; background: transparent; }
				.salon-tabs__tab { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 999px; text-decoration: none; color: #0f172a; border: 1px solid rgba(148, 163, 184, 0.45); background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.55)); box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 8px 18px rgba(15,23,42,0.12); backdrop-filter: blur(12px); }
				.salon-tabs__tab:hover { transform: translateY(-1px); box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 10px 20px rgba(15,23,42,0.16); }
				.salon-tabs__tab.is-active { border-color: rgba(59,130,246,0.65); background: linear-gradient(135deg, rgba(255,255,255,1), rgba(191,219,254,0.55)); box-shadow: inset 0 1px 0 rgba(255,255,255,1), 0 14px 26px rgba(59,130,246,0.25); color: #0f172a; }
				.salon-tabs__content { margin-top: 70px; width: 100%; box-sizing: border-box; }
				.salon-tabs__panel { width: 100%; box-sizing: border-box; margin: 0; padding: 10px 10px 0; }
				.salon-tabs__panel > :first-child { margin-top: 0 !important; }
			</style>
			<nav class="salon-tabs__bar" aria-label="<?php esc_attr_e( 'Navigacija kartica', 'salon-reservations' ); ?>">
				<?php foreach ( $available as $key => $tab ) : ?>
					<?php
						$url = admin_url( 'admin.php?page=salon-reservations&tab=' . $key );
						$is_active = $key === $active;
					?>
					<a class="salon-tabs__tab<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<div class="salon-tabs__content">
				<?php
				$callback = $tabs[ $active ]['callback'] ?? null;
				if ( is_callable( $callback ) ) {
					call_user_func( $callback, true );
				}
				?>
			</div>
			<style>
				.salon-tabs { --salon-glass-bg: rgba(255,255,255,0.85); --salon-glass-border: rgba(148,163,184,0.4); --salon-glass-shadow: 0 18px 30px rgba(15,23,42,0.12); --salon-glass-radius: 16px; }
				.salon-tabs .button,
				.salon-tabs button,
				.salon-tabs input[type="submit"],
				.salon-tabs input[type="button"] { border-radius: 999px !important; border: 1px solid var(--salon-glass-border) !important; background-image: linear-gradient(135deg, rgba(255,255,255,0.35), rgba(255,255,255,0.08)) !important; box-shadow: inset 0 1px 2px rgba(255,255,255,0.7), 0 6px 14px rgba(15,23,42,0.12) !important; backdrop-filter: blur(8px); }
				.salon-tabs .button-primary { border-color: rgba(59,130,246,0.45) !important; }
				.salon-tabs input[type="text"],
				.salon-tabs input[type="email"],
				.salon-tabs input[type="tel"],
				.salon-tabs input[type="number"],
				.salon-tabs input[type="date"],
				.salon-tabs input[type="time"],
				.salon-tabs select,
				.salon-tabs textarea { border-radius: 12px; border: 1px solid rgba(148,163,184,0.35); background: rgba(255,255,255,0.9); box-shadow: inset 0 1px 2px rgba(255,255,255,0.6); }
				.salon-tabs .widefat,
				.salon-tabs .form-table,
				.salon-tabs .salon-admin-filters { background: var(--salon-glass-bg); border: 1px solid var(--salon-glass-border); border-radius: var(--salon-glass-radius); box-shadow: var(--salon-glass-shadow); overflow: hidden; }
				.salon-tabs .salon-admin-filters { padding: 10px; }
				.salon-tabs .widefat th,
				.salon-tabs .widefat td,
				.salon-tabs .form-table th,
				.salon-tabs .form-table td { padding: 10px !important; }
				.salon-tabs .widefat thead th,
				.salon-tabs .widefat tbody td { background: transparent; }
				.salon-tabs .salon-reservations-calendar,
				.salon-tabs .salon-shifts__panel { border-radius: var(--salon-glass-radius); background: var(--salon-glass-bg); border: 1px solid var(--salon-glass-border); box-shadow: var(--salon-glass-shadow); padding: 12px; }
				.salon-tabs .salon-reservations-calendar__modal-card,
				.salon-tabs .salon-shifts__modal-card { background: rgba(255,255,255,0.92); border-radius: 18px; border: 1px solid rgba(148,163,184,0.25); box-shadow: 0 24px 40px rgba(15,23,42,0.18); backdrop-filter: blur(12px); }
				.salon-tabs .salon-tabs__content,
				.salon-tabs .salon-tabs__panel { padding-left: 10px; padding-right: 10px; }
				.salon-tabs .salon-tabs__panel > form:first-child,
				.salon-tabs .salon-tabs__panel > table:first-child,
				.salon-tabs .salon-tabs__panel > .salon-reservations-calendar:first-child,
				.salon-tabs .salon-tabs__panel > .salon-shifts__panel:first-child { margin-top: 0; }
			</style>
		</div>
		<?php
	}

	public function render_shifts( $embedded = false ) {
		( new ShiftsPage() )->render();
	}

	public function render_calendar( $embedded = false ) {
		( new EmployeeCalendarPage() )->render( $embedded );
	}

	public function filter_admin_footer_text( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && strpos( (string) $screen->id, 'salon-reservations' ) !== false ) {
			return '';
		}
		return $text;
	}

	public function filter_admin_footer_version( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && strpos( (string) $screen->id, 'salon-reservations' ) !== false ) {
			return '';
		}
		return $text;
	}

	public function render_services( $embedded = false ) {
		( new ServicesPage() )->render( $embedded );
	}

	public function render_employees( $embedded = false ) {
		( new EmployeesPage() )->render( $embedded );
	}

	public function render_settings( $embedded = false ) {
		( new SettingsPage() )->render( $embedded );
	}

	public function render_reservations( $embedded = false ) {
		( new ReservationsPage() )->render( $embedded, false, true );
	}

	public function render_reservations_calendar( $embedded = false ) {
		( new ReservationsPage() )->render( $embedded, true, false );
	}

	private function get_tabs() {
		return array(
			'kalendar' => array(
				'label' => __( 'Kalendar', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::VIEW_RESERVATIONS_OWN ) || current_user_can( Capabilities::MANAGE_RESERVATIONS ),
				'callback' => array( $this, 'render_reservations_calendar' ),
			),
			'rezervacije' => array(
				'label' => __( 'Rezervacije', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::MANAGE_RESERVATIONS ) || current_user_can( Capabilities::VIEW_RESERVATIONS_OWN ),
				'callback' => array( $this, 'render_reservations' ),
			),
			'raspored' => array(
				'label' => __( 'Raspored', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::VIEW_RESERVATIONS_OWN ) || current_user_can( Capabilities::MANAGE_RESERVATIONS ),
				'callback' => array( $this, 'render_calendar' ),
			),
			'zaposlenici' => array(
				'label' => __( 'Zaposlenici', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::MANAGE_EMPLOYEES ),
				'callback' => array( $this, 'render_employees' ),
			),
			'usluge' => array(
				'label' => __( 'Usluge', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::MANAGE_SETTINGS ),
				'callback' => array( $this, 'render_services' ),
			),
			'postavke' => array(
				'label' => __( 'Postavke', 'salon-reservations' ),
				'can' => current_user_can( Capabilities::MANAGE_SETTINGS ),
				'callback' => array( $this, 'render_settings' ),
			),
		);
	}
}
