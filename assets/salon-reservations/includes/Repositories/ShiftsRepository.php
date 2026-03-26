<?php
namespace Salon\Reservations\Repositories;

use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShiftsRepository {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'salon_shifts';
	}

	public function create( $data ) {
		global $wpdb;
		$now = DateTimeHelper::now_utc();

		$insert = array(
			'employee_id' => (int) $data['employee_id'],
			'start_datetime' => $data['start_datetime'],
			'end_datetime' => $data['end_datetime'],
			'status' => sanitize_text_field( $data['status'] ),
			'created_by' => (int) $data['created_by'],
			'created_at' => $now,
			'updated_at' => $now,
		);

		$wpdb->insert(
			$this->table,
			$insert,
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public function update( $id, $data ) {
		global $wpdb;
		$update = array();
		$formats = array();

		if ( isset( $data['employee_id'] ) ) {
			$update['employee_id'] = (int) $data['employee_id'];
			$formats[] = '%d';
		}
		if ( isset( $data['start_datetime'] ) ) {
			$update['start_datetime'] = $data['start_datetime'];
			$formats[] = '%s';
		}
		if ( isset( $data['end_datetime'] ) ) {
			$update['end_datetime'] = $data['end_datetime'];
			$formats[] = '%s';
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

	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function list_by_employee( $employee_id, $from = null, $to = null, $status = null ) {
		global $wpdb;
		$where = array( 'employee_id = %d' );
		$args = array( (int) $employee_id );

		if ( $from ) {
			$where[] = 'end_datetime >= %s';
			$args[] = $from;
		}
		if ( $to ) {
			$where[] = 'start_datetime <= %s';
			$args[] = $to;
		}
		if ( $status ) {
			$where[] = 'status = %s';
			$args[] = $status;
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_datetime ASC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	public function list_all( $from = null, $to = null, $status = null ) {
		global $wpdb;
		$where = array( '1=1' );
		$args = array();

		if ( $from ) {
			$where[] = 'end_datetime >= %s';
			$args[] = $from;
		}
		if ( $to ) {
			$where[] = 'start_datetime <= %s';
			$args[] = $to;
		}
		if ( $status ) {
			$where[] = 'status = %s';
			$args[] = $status;
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_datetime ASC';
		if ( ! empty( $args ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		}
		return $wpdb->get_results( $sql );
	}

	public function has_shift_covering( $employee_id, $start_utc, $end_utc ) {
		global $wpdb;
		$sql = "SELECT id FROM {$this->table}
			WHERE employee_id = %d
			AND status != %s
			AND start_datetime <= %s
			AND end_datetime >= %s
			LIMIT 1";
		$result = $wpdb->get_var( $wpdb->prepare( $sql, (int) $employee_id, 'cancelled', $start_utc, $end_utc ) );
		return ! empty( $result );
	}
}
