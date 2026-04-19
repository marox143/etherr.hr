<?php
namespace Salon\Reservations\Email;

use Salon\Reservations\Repositories\ServicesRepository;
use Salon\Reservations\Utils\DateTimeHelper;
use Salon\Reservations\Utils\StatusHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Notifier {
	private $services;
	private $settings;

	public function __construct() {
		$this->services = new ServicesRepository();
		$this->settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
	}

	public function notify_admin_new_request( $reservation ) {
		$to = get_option( 'admin_email' );
		$subject = __( 'Novi zahtjev za rezervaciju', 'salon-reservations' );
		$body = $this->build_summary( $reservation, __( 'Zaprimljen je novi zahtjev za rezervaciju.', 'salon-reservations' ) );

		$subject = apply_filters( 'salon_reservation_email_subject_admin_new', $subject, $reservation );
		$body = apply_filters( 'salon_reservation_email_body_admin_new', $body, $reservation );

		wp_mail( $to, $subject, $body );
	}

	public function notify_customer_status( $reservation ) {
		$status_label = StatusHelper::label( $reservation->status );
		$subject = sprintf(
			__( 'Status vaše rezervacije: %s', 'salon-reservations' ),
			$status_label
		);
		$intro = sprintf(
			__( 'Status vaše rezervacije je ažuriran na %s.', 'salon-reservations' ),
			$status_label
		);
		$body = $this->build_summary( $reservation, $intro );

		$subject = apply_filters( 'salon_reservation_email_subject_customer_status', $subject, $reservation );
		$body = apply_filters( 'salon_reservation_email_body_customer_status', $body, $reservation );

		wp_mail( $reservation->customer_email, $subject, $body );
	}

	public function notify_customer_received( $reservation ) {
		$subject = __( 'Zahtjev za rezervaciju je zaprimljen', 'salon-reservations' );
		$body = $this->build_summary( $reservation, __( 'Zaprimili smo vaš zahtjev za rezervaciju.', 'salon-reservations' ) );

		$subject = apply_filters( 'salon_reservation_email_subject_customer_received', $subject, $reservation );
		$body = apply_filters( 'salon_reservation_email_body_customer_received', $body, $reservation );

		wp_mail( $reservation->customer_email, $subject, $body );
	}

	private function build_summary( $reservation, $intro ) {
		$service = $this->services->get( $reservation->service_id );
		$employee_name = $this->employee_name( $reservation->employee_id );
		$start_local = DateTimeHelper::utc_to_local( $reservation->start_datetime, 'd.m.Y. H:i' );

		$lines = array(
			$intro,
			'',
			sprintf( __( 'Zaposlenik: %s', 'salon-reservations' ), $employee_name ),
			sprintf( __( 'Vrsta usluge: %s', 'salon-reservations' ), $service ? $service->name : __( 'Vrsta usluge', 'salon-reservations' ) ),
			sprintf( __( 'Datum/Vrijeme: %s', 'salon-reservations' ), $start_local ),
			sprintf( __( 'Status: %s', 'salon-reservations' ), StatusHelper::label( $reservation->status ) ),
		);

		if ( ! empty( $reservation->addons ) ) {
			$lines[] = sprintf( __( 'Opcije: %s', 'salon-reservations' ), $reservation->addons );
		}

		if ( ! empty( $this->settings['salon_contact_email'] ) ) {
			$lines[] = '';
			$lines[] = sprintf( __( 'Kontakt: %s', 'salon-reservations' ), $this->settings['salon_contact_email'] );
		}

		return implode( "\n", $lines );
	}

	private function employee_name( $employee_id ) {
		$user = get_user_by( 'id', $employee_id );
		if ( $user ) {
			return $user->display_name;
		}
		return __( 'Zaposlenik', 'salon-reservations' );
	}
}
