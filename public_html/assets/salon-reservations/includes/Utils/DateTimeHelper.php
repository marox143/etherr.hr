<?php
namespace Salon\Reservations\Utils;

use DateTimeImmutable;
use DateTimeZone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DateTimeHelper {
	public static function wp_timezone() {
		return wp_timezone();
	}

	public static function utc_timezone() {
		return new DateTimeZone( 'UTC' );
	}

	public static function local_to_utc( $local_datetime ) {
		$tz = self::wp_timezone();
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $local_datetime, $tz );
		if ( ! $dt ) {
			$dt = new DateTimeImmutable( $local_datetime, $tz );
		}
		return $dt->setTimezone( self::utc_timezone() )->format( 'Y-m-d H:i:s' );
	}

	public static function local_date_range_to_utc( $start_date, $end_date ) {
		$tz = self::wp_timezone();
		$start = new DateTimeImmutable( $start_date . ' 00:00:00', $tz );
		$end = new DateTimeImmutable( $end_date . ' 23:59:59', $tz );

		return array(
			$start->setTimezone( self::utc_timezone() )->format( 'Y-m-d H:i:s' ),
			$end->setTimezone( self::utc_timezone() )->format( 'Y-m-d H:i:s' ),
		);
	}

	public static function utc_to_local( $utc_datetime, $format = 'Y-m-d H:i:s' ) {
		$dt = new DateTimeImmutable( $utc_datetime, self::utc_timezone() );
		return $dt->setTimezone( self::wp_timezone() )->format( $format );
	}

	public static function utc_to_local_iso( $utc_datetime ) {
		$dt = new DateTimeImmutable( $utc_datetime, self::utc_timezone() );
		return $dt->setTimezone( self::wp_timezone() )->format( DATE_ATOM );
	}

	public static function local_datetime_from_input( $input ) {
		$tz = self::wp_timezone();
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $input, $tz );
		if ( ! $dt ) {
			$dt = new DateTimeImmutable( $input, $tz );
		}
		return $dt;
	}

	public static function local_to_utc_from_input( $input ) {
		$dt = self::local_datetime_from_input( $input );
		return $dt->setTimezone( self::utc_timezone() )->format( 'Y-m-d H:i:s' );
	}

	public static function now_utc() {
		return gmdate( 'Y-m-d H:i:s' );
	}

	public static function round_up_to_interval( DateTimeImmutable $dt, $interval_minutes ) {
		$timestamp = $dt->getTimestamp();
		$interval = $interval_minutes * 60;
		$rounded = (int) ceil( $timestamp / $interval ) * $interval;
		return ( new DateTimeImmutable( '@' . $rounded ) )->setTimezone( $dt->getTimezone() );
	}
}
