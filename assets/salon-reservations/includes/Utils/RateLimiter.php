<?php
namespace Salon\Reservations\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RateLimiter {
	const WINDOW_SECONDS = 600;
	const MAX_ATTEMPTS = 3;

	public static function is_allowed( $ip, $email ) {
		$key = self::key( $ip, $email );
		$data = get_transient( $key );

		if ( false === $data ) {
			set_transient( $key, array( 'count' => 1, 'start' => time() ), self::WINDOW_SECONDS );
			return true;
		}

		if ( ! isset( $data['count'] ) ) {
			$data = array( 'count' => 0, 'start' => time() );
		}

		$data['count']++;

		if ( $data['count'] > self::MAX_ATTEMPTS ) {
			return false;
		}

		set_transient( $key, $data, self::WINDOW_SECONDS );

		return true;
	}

	private static function key( $ip, $email ) {
		$raw = strtolower( trim( $ip . '|' . $email ) );
		return 'salon_rl_' . md5( $raw );
	}
}
