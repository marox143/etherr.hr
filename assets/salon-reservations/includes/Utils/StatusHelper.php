<?php
namespace Salon\Reservations\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StatusHelper {
	public static function label( $status ) {
		switch ( $status ) {
			case 'pending':
				return __( 'Na čekanju', 'salon-reservations' );
			case 'approved':
				return __( 'Odobreno', 'salon-reservations' );
			case 'denied':
				return __( 'Odbijeno', 'salon-reservations' );
			case 'cancelled':
				return __( 'Otkazano', 'salon-reservations' );
			case 'inactive':
				return __( 'Neaktivno', 'salon-reservations' );
			case 'active':
				return __( 'Aktivno', 'salon-reservations' );
			default:
				return ucfirst( (string) $status );
		}
	}
}
