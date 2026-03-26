<?php
namespace Salon\Reservations\Core;

use Salon\Reservations\Admin\AdminMenu;
use Salon\Reservations\Frontend\Shortcodes;
use Salon\Reservations\Rest\Endpoints;
use Salon\Reservations\Utils\UserPhoneProfile;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static $instance;
	private $loaded = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init() {
		if ( $this->loaded ) {
			return;
		}
		$this->loaded = true;

		spl_autoload_register( array( $this, 'autoload' ) );
		$this->maybe_upgrade();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			( new UserPhoneProfile() )->register();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'salon-reservations', false, dirname( SALON_RESERVATIONS_BASENAME ) . '/languages' );
	}

	public function register_shortcodes() {
		( new Shortcodes() )->register();
	}

	public function register_rest() {
		( new Endpoints() )->register();
	}

	public function register_admin_menu() {
		( new AdminMenu() )->register();
	}

	public function admin_init() {
		$menu = new AdminMenu();
		$menu->handle_actions();
	}

	private function maybe_upgrade() {
		$current = get_option( SALON_RESERVATIONS_OPTION_DB_VERSION );
		if ( $current !== SALON_RESERVATIONS_DB_VERSION ) {
			\Salon\Reservations\DB\Schema::create();
			update_option( SALON_RESERVATIONS_OPTION_DB_VERSION, SALON_RESERVATIONS_DB_VERSION );
		}
	}

	public function autoload( $class ) {
		$prefix = 'Salon\\Reservations\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = str_replace( $prefix, '', $class );
		$relative = str_replace( '\\', '/', $relative );
		$file = SALON_RESERVATIONS_DIR . 'includes/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
