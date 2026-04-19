<?php
namespace Salon\Reservations\Repositories;

use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShiftChangeRequestsRepository {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'salon_shift_change_requests';
	}

	public function create( $data ) {
		global $wpdb;
		$now = DateTimeHelper::now_utc();

		$insert = array(
			'shift_id' => (int) $data['shift_id'],
			'requested_start' => $data['requested_start'],
			'requested_end' => $data['requested_end'],
			'reason' => isset( $data['reason'] ) ? sanitize_textarea_field( $data['reason'] ) : null,
			'status' => sanitize_text_field( $data['status'] ),
			'created_by' => (int) $data['created_by'],
			'created_at' => $now,
			'updated_at' => $now,
		);

		$wpdb->insert(
			$this->table,
			$insert,
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public function update_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update(
			$this->table,
			array(
				'status' => sanitize_text_field( $status ),
				'updated_at' => DateTimeHelper::now_utc(),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public function list( $filters = array() ) {
		global $wpdb;
		$where = array( '1=1' );
		$args = array();

		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'status = %s';
			$args[] = $filters['status'];
		}
		if ( ! empty( $filters['created_by'] ) ) {
			$where[] = 'created_by = %d';
			$args[] = (int) $filters['created_by'];
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';
		if ( ! empty( $args ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		}
		return $wpdb->get_results( $sql );
	}

	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}
}
