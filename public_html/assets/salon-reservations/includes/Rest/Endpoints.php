<?php
namespace Salon\Reservations\Rest;

use WP_REST_Request;
use WP_REST_Response;
use Salon\Reservations\Services\AvailabilityService;
use Salon\Reservations\Repositories\ReservationsRepository;
use Salon\Reservations\Repositories\ServicesRepository;
use Salon\Reservations\Utils\DateTimeHelper;
use Salon\Reservations\Utils\RateLimiter;
use Salon\Reservations\Email\Notifier;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Endpoints {
	public function register() {
		register_rest_route(
			'salon/v1',
			'/slots',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_slots' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'salon/v1',
			'/first-available',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_first_available' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'salon/v1',
			'/availability',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_availability' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'salon/v1',
			'/reservations',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'create_reservation' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_slots( WP_REST_Request $request ) {
		$employee_id = (int) $request->get_param( 'employee_id' );
		$service_id = (int) $request->get_param( 'service_id' );
		$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
		$end_date = sanitize_text_field( $request->get_param( 'end_date' ) );

		if ( empty( $end_date ) ) {
			$end_date = $start_date;
		}

		if ( ! $service_id || ! $start_date ) {
			return new WP_REST_Response( array( 'message' => __( 'Nedostaju parametri.', 'salon-reservations' ) ), 400 );
		}

		$availability = new AvailabilityService();
		if ( $employee_id ) {
			$raw_slots = $availability->get_available_slots( $employee_id, $start_date, $end_date, $service_id );
			$employee = get_user_by( 'id', $employee_id );
			$slots = array();
			foreach ( $raw_slots as $slot ) {
				$slots[] = array(
					'start' => $slot,
					'employee_id' => $employee_id,
					'employee_name' => $employee ? $employee->display_name : '',
				);
			}
		} else {
			$slots = $availability->get_available_slots_for_any( $start_date, $end_date, $service_id );
		}

		return new WP_REST_Response( array( 'slots' => $slots ), 200 );
	}

	public function get_first_available( WP_REST_Request $request ) {
		$employee_id = (int) $request->get_param( 'employee_id' );
		$service_id = (int) $request->get_param( 'service_id' );
		$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
		$end_date = sanitize_text_field( $request->get_param( 'end_date' ) );

		if ( empty( $start_date ) ) {
			$start_date = current_time( 'Y-m-d' );
		}
		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-m-d', strtotime( $start_date . ' +30 days' ) );
		}

		if ( ! $service_id ) {
			return new WP_REST_Response( array( 'message' => __( 'Nedostaju parametri.', 'salon-reservations' ) ), 400 );
		}

		$availability = new AvailabilityService();
		$slot = $availability->get_first_available_slot( $employee_id, $start_date, $end_date, $service_id );

		return new WP_REST_Response( array( 'slot' => $slot ), 200 );
	}

	public function get_availability( WP_REST_Request $request ) {
		$employee_id = (int) $request->get_param( 'employee_id' );
		$service_id = (int) $request->get_param( 'service_id' );
		$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
		$end_date = sanitize_text_field( $request->get_param( 'end_date' ) );

		if ( empty( $end_date ) ) {
			$end_date = $start_date;
		}

		if ( ! $service_id || ! $start_date || ! $end_date ) {
			return new WP_REST_Response( array( 'message' => __( 'Nedostaju parametri.', 'salon-reservations' ) ), 400 );
		}

		$availability = new AvailabilityService();
		if ( $employee_id ) {
			$slots = $availability->get_available_slots( $employee_id, $start_date, $end_date, $service_id );
		} else {
			$slots = $availability->get_available_slots_for_any( $start_date, $end_date, $service_id );
		}

		$dates = array();
		foreach ( $slots as $slot ) {
			$slot_value = is_array( $slot ) ? ( $slot['start'] ?? '' ) : $slot;
			if ( empty( $slot_value ) ) {
				continue;
			}
			$date_key = substr( $slot_value, 0, 10 );
			$dates[ $date_key ] = true;
		}

		$available = array_keys( $dates );
		sort( $available );

		return new WP_REST_Response( array( 'dates' => $available ), 200 );
	}

	public function create_reservation( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x-salon-nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_header( 'x-wp-nonce' );
		}
		if ( empty( $nonce ) ) {
			$params = $request->get_json_params();
			if ( isset( $params['nonce'] ) ) {
				$nonce = $params['nonce'];
			}
		}
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( 'nonce' );
		}
		$nonce = sanitize_text_field( $nonce );
		if ( empty( $nonce ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Nedostaje sigurnosni token.', 'salon-reservations' ) ), 403 );
		}
		if ( ! wp_verify_nonce( $nonce, 'salon_reservation' ) && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Neispravna prijava obrasca.', 'salon-reservations' ) ), 403 );
		}

		$honeypot = $request->get_param( 'company' );
		if ( ! empty( $honeypot ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Detektiran spam.', 'salon-reservations' ) ), 400 );
		}

		$customer_email = sanitize_email( $request->get_param( 'customer_email' ) );
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( ! RateLimiter::is_allowed( $ip, $customer_email ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Previše zahtjeva. Pokušajte kasnije.', 'salon-reservations' ) ), 429 );
		}

		$employee_id = (int) $request->get_param( 'employee_id' );
		$service_id = (int) $request->get_param( 'service_id' );
		$start_datetime = sanitize_text_field( $request->get_param( 'start_datetime' ) );
		$customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
		$customer_phone = sanitize_text_field( $request->get_param( 'customer_phone' ) );
		$notes = sanitize_textarea_field( $request->get_param( 'notes' ) );

		$create_account = filter_var( $request->get_param( 'create_account' ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $employee_id || ! $service_id || empty( $start_datetime ) || empty( $customer_name ) || empty( $customer_email ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Nedostaju obavezna polja.', 'salon-reservations' ) ), 400 );
		}

		$services = new ServicesRepository();
		$service = $services->get( $service_id );
		if ( ! $service ) {
			return new WP_REST_Response( array( 'message' => __( 'Neispravna usluga.', 'salon-reservations' ) ), 400 );
		}

		$local_dt = DateTimeHelper::local_datetime_from_input( $start_datetime );
		$start_utc = $local_dt->setTimezone( DateTimeHelper::utc_timezone() )->format( 'Y-m-d H:i:s' );
		$end_utc = $local_dt->modify( '+' . (int) $service->duration_minutes . ' minutes' )
			->setTimezone( DateTimeHelper::utc_timezone() )
			->format( 'Y-m-d H:i:s' );

		$availability = new AvailabilityService();
		if ( ! $availability->is_slot_available( $employee_id, $local_dt, $service_id ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Odabrani termin više nije dostupan.', 'salon-reservations' ) ), 409 );
		}

		$customer_user_id = get_current_user_id();
		if ( ! $customer_user_id ) {
			$existing_user = get_user_by( 'email', $customer_email );
			if ( $existing_user ) {
				$customer_user_id = $existing_user->ID;
				if ( $create_account ) {
					return new WP_REST_Response( array( 'message' => __( 'Račun s ovom email adresom već postoji. Molimo prijavite se.', 'salon-reservations' ) ), 409 );
				}
			} elseif ( $create_account ) {
				$user_id = $this->create_account_for_customer( $customer_name, $customer_email );
				if ( is_wp_error( $user_id ) ) {
					return new WP_REST_Response( array( 'message' => $user_id->get_error_message() ), 400 );
				}
				$customer_user_id = $user_id;
			}
		}
		if ( $customer_user_id && '' !== $customer_phone ) {
			update_user_meta( $customer_user_id, 'salon_phone', $customer_phone );
		}

		$addons = $request->get_param( 'addons' );
		$addons_value = '';
		if ( is_array( $addons ) ) {
			$addons_value = implode( ', ', array_map( 'sanitize_text_field', $addons ) );
		} elseif ( is_string( $addons ) ) {
			$addons_value = sanitize_text_field( $addons );
		}

		$repo = new ReservationsRepository();
		$reservation_id = $repo->create(
			array(
				'employee_id' => $employee_id,
				'service_id' => $service_id,
				'customer_user_id' => $customer_user_id,
				'customer_name' => $customer_name,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'start_datetime' => $start_utc,
				'end_datetime' => $end_utc,
				'status' => 'pending',
				'notes' => $notes,
				'addons' => $addons_value,
			)
		);

		$reservation = $repo->get( $reservation_id );
		$notifier = new Notifier();
		$notifier->notify_admin_new_request( $reservation );
		$notifier->notify_customer_received( $reservation );

		return new WP_REST_Response( array( 'message' => __( 'Zahtjev za rezervaciju je poslan.', 'salon-reservations' ) ), 201 );
	}

	private function create_account_for_customer( $name, $email ) {
		$username_base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( '' === $username_base ) {
			$username_base = 'korisnik';
		}
		$username = $username_base;
		$counter = 1;
		while ( username_exists( $username ) ) {
			$username = $username_base . $counter;
			$counter++;
		}

		$password = wp_generate_password( 12, true );
		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		wp_update_user(
			array(
				'ID' => $user_id,
				'display_name' => $name,
				'role' => 'subscriber',
			)
		);

		if ( function_exists( 'wp_new_user_notification' ) ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}

		return $user_id;
	}
}
