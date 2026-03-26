<?php
namespace Salon\Reservations\Repositories;

use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ServicesRepository {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'salon_services';
	}

	public function count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}

	public function all_active() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC", 'active' ) );
	}

	public function all() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY name ASC" );
	}

	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function insert( $data ) {
		global $wpdb;
		$now = DateTimeHelper::now_utc();

		$insert = array(
			'name' => sanitize_text_field( $data['name'] ),
			'duration_minutes' => (int) $data['duration_minutes'],
			'price' => isset( $data['price'] ) ? $data['price'] : null,
			'status' => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'active',
			'created_at' => $now,
			'updated_at' => $now,
		);

		$wpdb->insert( $this->table, $insert, array( '%s', '%d', '%f', '%s', '%s', '%s' ) );

		return $wpdb->insert_id;
	}

	public function update( $id, $data ) {
		global $wpdb;
		$update = array();
		$formats = array();

		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( $data['name'] );
			$formats[] = '%s';
		}

		if ( isset( $data['duration_minutes'] ) ) {
			$update['duration_minutes'] = (int) $data['duration_minutes'];
			$formats[] = '%d';
		}

		if ( array_key_exists( 'price', $data ) ) {
			$update['price'] = $data['price'];
			$formats[] = '%f';
		}

		if ( isset( $data['status'] ) ) {
			$update['status'] = sanitize_text_field( $data['status'] );
			$formats[] = '%s';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$update['updated_at'] = DateTimeHelper::now_utc();
		$formats[] = '%s';

		return $wpdb->update( $this->table, $update, array( 'id' => (int) $id ), $formats, array( '%d' ) );
	}
}
