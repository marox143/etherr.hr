<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( 'yes' !== get_option( 'salon_reservations_uninstall_cleanup' ) ) {
	return;
}

require_once __DIR__ . '/includes/DB/Schema.php';

Salon\Reservations\DB\Schema::drop();

delete_option( 'salon_reservations_settings' );
delete_option( 'salon_reservations_db_version' );
delete_option( 'salon_reservations_uninstall_cleanup' );
