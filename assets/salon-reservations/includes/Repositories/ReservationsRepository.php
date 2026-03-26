<?php
namespace Salon\Reservations\Repositories;

use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReservationsRepository {
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'salon_reservations';
	}

	public function create( $data ) {
		global $wpdb;
		$now = DateTimeHelper::now_utc();

		$insert = array(
			'employee_id' => (int) $data['employee_id'],
			'service_id' => (int) $data['service_id'],
			'customer_user_id' => $data['customer_user_id'] ? (int) $data['customer_user_id'] : null,
			'customer_name' => sanitize_text_field( $data['customer_name'] ),
			'customer_email' => sanitize_email( $data['customer_email'] ),
			'customer_phone' => isset( $data['customer_phone'] ) ? sanitize_text_field( $data['customer_phone'] ) : null,
			'start_datetime' => $data['start_datetime'],
			'end_datetime' => $data['end_datetime'],
			'status' => sanitize_text_field( $data['status'] ),
			'notes' => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
			'addons' => isset( $data['addons'] ) ? sanitize_textarea_field( $data['addons'] ) : null,
			'created_at' => $now,
			'updated_at' => $now,
		);

		$wpdb->insert(
			$this->table,
			$insert,
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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

	public function update_schedule( $id, $data ) {
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

		if ( empty( $update ) ) {
			return false;
		}

		$update['updated_at'] = DateTimeHelper::now_utc();
		$formats[] = '%s';

		return $wpdb->update( $this->table, $update, array( 'id' => (int) $id ), $formats, array( '%d' ) );
	}

	public function update_details( $id, $data ) {
		global $wpdb;
		$update = array();
		$formats = array();

		if ( isset( $data['employee_id'] ) ) {
			$update['employee_id'] = (int) $data['employee_id'];
			$formats[] = '%d';
		}
		if ( isset( $data['service_id'] ) ) {
			$update['service_id'] = (int) $data['service_id'];
			$formats[] = '%d';
		}
		if ( array_key_exists( 'customer_user_id', $data ) ) {
			$update['customer_user_id'] = $data['customer_user_id'] ? (int) $data['customer_user_id'] : null;
			$formats[] = '%d';
		}
		if ( isset( $data['customer_name'] ) ) {
			$update['customer_name'] = sanitize_text_field( $data['customer_name'] );
			$formats[] = '%s';
		}
		if ( isset( $data['customer_email'] ) ) {
			$update['customer_email'] = sanitize_email( $data['customer_email'] );
			$formats[] = '%s';
		}
		if ( array_key_exists( 'customer_phone', $data ) ) {
			$update['customer_phone'] = $data['customer_phone'] ? sanitize_text_field( $data['customer_phone'] ) : null;
			$formats[] = '%s';
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
		if ( array_key_exists( 'notes', $data ) ) {
			$update['notes'] = $data['notes'] ? sanitize_textarea_field( $data['notes'] ) : null;
			$formats[] = '%s';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$update['updated_at'] = DateTimeHelper::now_utc();
		$formats[] = '%s';

		return $wpdb->update( $this->table, $update, array( 'id' => (int) $id ), $formats, array( '%d' ) );
	}

	public function has_overlap( $employee_id, $start_utc, $end_utc, $exclude_id = 0 ) {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$this->table}
			WHERE employee_id = %d
			AND id != %d
			AND status IN ('pending', 'approved')
			AND start_datetime < %s
			AND end_datetime > %s";
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, (int) $employee_id, (int) $exclude_id, $end_utc, $start_utc ) );
		return $count > 0;
	}

	public function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
	}

	public function list( $filters = array() ) {
		global $wpdb;
		$where = array( '1=1' );
		$args = array();
		$order_by = 'start_datetime';
		$order = 'DESC';

		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'status = %s';
			$args[] = $filters['status'];
		}
		if ( ! empty( $filters['employee_id'] ) ) {
			$where[] = 'employee_id = %d';
			$args[] = (int) $filters['employee_id'];
		}
		if ( ! empty( $filters['from'] ) ) {
			$where[] = 'end_datetime >= %s';
			$args[] = $filters['from'];
		}
		if ( ! empty( $filters['to'] ) ) {
			$where[] = 'start_datetime <= %s';
			$args[] = $filters['to'];
		}

		if ( ! empty( $filters['orderby'] ) ) {
			$allowed = array(
				'id' => 'id',
				'employee' => 'employee_id',
				'service' => 'service_id',
				'client' => 'customer_name',
				'options' => 'addons',
				'date' => 'start_datetime',
				'status' => 'status',
			);
			if ( isset( $allowed[ $filters['orderby'] ] ) ) {
				$order_by = $allowed[ $filters['orderby'] ];
			}
		}
		if ( ! empty( $filters['order'] ) && strtoupper( $filters['order'] ) === 'ASC' ) {
			$order = 'ASC';
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$order_by} {$order}";
		if ( ! empty( $args ) ) {
			return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		}
		return $wpdb->get_results( $sql );
	}

	public function list_for_employee( $employee_id, $status = null ) {
		global $wpdb;
		$where = array( 'employee_id = %d' );
		$args = array( (int) $employee_id );

		if ( $status ) {
			$where[] = 'status = %s';
			$args[] = $status;
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_datetime DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}
}
