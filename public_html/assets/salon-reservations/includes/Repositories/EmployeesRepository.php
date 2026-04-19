<?php
namespace Salon\Reservations\Repositories;

use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EmployeesRepository {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'salon_employees';
	}

	public function upsert_for_user( $user ) {
		global $wpdb;
		$now = DateTimeHelper::now_utc();

		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE wp_user_id = %d", $user->ID ) );
		$data = array(
			'wp_user_id' => (int) $user->ID,
			'display_name' => $user->display_name,
			'email' => $user->user_email,
			'phone' => null,
			'status' => 'active',
			'updated_at' => $now,
		);

		if ( $existing ) {
			$wpdb->update( $this->table, $data, array( 'id' => (int) $existing ), array( '%d', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
			return (int) $existing;
		}

		$data['created_at'] = $now;
		$wpdb->insert( $this->table, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );

		return $wpdb->insert_id;
	}

	public function get_by_user_id( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE wp_user_id = %d", $user_id ) );
	}
}
