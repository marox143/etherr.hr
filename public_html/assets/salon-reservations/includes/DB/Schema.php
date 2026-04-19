<?php
namespace Salon\Reservations\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {
	public static function create() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$employees = $wpdb->prefix . 'salon_employees';
		$services = $wpdb->prefix . 'salon_services';
		$shifts = $wpdb->prefix . 'salon_shifts';
		$reservations = $wpdb->prefix . 'salon_reservations';
		$shift_changes = $wpdb->prefix . 'salon_shift_change_requests';

		$sql = array();

		$sql[] = "CREATE TABLE $employees (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) unsigned NOT NULL,
			display_name varchar(200) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY wp_user_id (wp_user_id),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE $services (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL,
			duration_minutes smallint(5) unsigned NOT NULL,
			price decimal(10,2) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE $shifts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			employee_id bigint(20) unsigned NOT NULL,
			start_datetime datetime NOT NULL,
			end_datetime datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'approved',
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY employee_id (employee_id),
			KEY start_datetime (start_datetime),
			KEY status (status)
		) $charset_collate;";

		$sql[] = "CREATE TABLE $reservations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			employee_id bigint(20) unsigned NOT NULL,
			service_id bigint(20) unsigned NOT NULL,
			customer_user_id bigint(20) unsigned DEFAULT NULL,
			customer_name varchar(200) NOT NULL,
			customer_email varchar(200) NOT NULL,
			customer_phone varchar(50) DEFAULT NULL,
			start_datetime datetime NOT NULL,
			end_datetime datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			notes text DEFAULT NULL,
			addons text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY employee_id (employee_id),
			KEY start_datetime (start_datetime),
			KEY status (status),
			KEY customer_email (customer_email)
		) $charset_collate;";

		$sql[] = "CREATE TABLE $shift_changes (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			shift_id bigint(20) unsigned NOT NULL,
			requested_start datetime NOT NULL,
			requested_end datetime NOT NULL,
			reason text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY shift_id (shift_id),
			KEY status (status)
		) $charset_collate;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	public static function drop() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'salon_employees',
			$wpdb->prefix . 'salon_services',
			$wpdb->prefix . 'salon_shifts',
			$wpdb->prefix . 'salon_reservations',
			$wpdb->prefix . 'salon_shift_change_requests',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}
}
