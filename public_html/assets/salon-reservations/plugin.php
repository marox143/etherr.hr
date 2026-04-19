<?php
/**
 * Plugin Name: Salon Reservations
 * Description: Reservation system backbone for a hair salon.
 * Version: 0.1.0
 * Author: Codex
 * Text Domain: salon-reservations
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SALON_RESERVATIONS_VERSION', '0.1.0' );
define( 'SALON_RESERVATIONS_SLUG', 'salon-reservations' );
define( 'SALON_RESERVATIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SALON_RESERVATIONS_URL', plugin_dir_url( __FILE__ ) );
define( 'SALON_RESERVATIONS_BASENAME', plugin_basename( __FILE__ ) );

define( 'SALON_RESERVATIONS_DB_VERSION', '2' );

define( 'SALON_RESERVATIONS_OPTION_SETTINGS', 'salon_reservations_settings' );
define( 'SALON_RESERVATIONS_OPTION_DB_VERSION', 'salon_reservations_db_version' );
define( 'SALON_RESERVATIONS_OPTION_UNINSTALL_CLEANUP', 'salon_reservations_uninstall_cleanup' );

require_once SALON_RESERVATIONS_DIR . 'includes/Core/Plugin.php';
require_once SALON_RESERVATIONS_DIR . 'includes/Core/Activator.php';

register_activation_hook( __FILE__, array( 'Salon\\Reservations\\Core\\Activator', 'activate' ) );

Salon\Reservations\Core\Plugin::instance()->init();
