<?php
namespace Salon\Reservations\Services;

use DateTimeImmutable;
use Salon\Reservations\Repositories\ReservationsRepository;
use Salon\Reservations\Repositories\ShiftsRepository;
use Salon\Reservations\Repositories\ServicesRepository;
use Salon\Reservations\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AvailabilityService {
	private $shifts;
	private $reservations;
	private $services;
	private $settings;
	private $employees;

	public function __construct() {
		$this->shifts = new ShiftsRepository();
		$this->reservations = new ReservationsRepository();
		$this->services = new ServicesRepository();
		$this->settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$this->employees = $this->load_employees();
	}

	public function get_available_slots( $employee_id, $start_date, $end_date, $service_id ) {
		$service = $this->services->get( $service_id );
		if ( ! $service ) {
			return array();
		}

		$duration = (int) $service->duration_minutes;
		$buffer = isset( $this->settings['buffer_minutes'] ) ? (int) $this->settings['buffer_minutes'] : 0;
		$lead_hours = isset( $this->settings['lead_time_hours'] ) ? (int) $this->settings['lead_time_hours'] : 0;
		$interval = isset( $this->settings['slot_interval_minutes'] ) ? (int) $this->settings['slot_interval_minutes'] : 15;

		list( $from_utc, $to_utc ) = DateTimeHelper::local_date_range_to_utc( $start_date, $end_date );

		$shifts = $this->shifts->list_by_employee( $employee_id, $from_utc, $to_utc, 'approved' );
		if ( empty( $shifts ) ) {
			return array();
		}

		$reservations = $this->reservations->list(
			array(
				'employee_id' => $employee_id,
				'from' => $from_utc,
				'to' => $to_utc,
			)
		);

		$blocked = $this->build_blocked_ranges( $reservations, $buffer );
		$lead_cutoff = $this->lead_time_cutoff_utc( $lead_hours );
		$holiday_dates = $this->get_holiday_dates();

		$slots = array();

		foreach ( $shifts as $shift ) {
			if ( $this->is_holiday_datetime( $shift->start_datetime, $holiday_dates ) ) {
				continue;
			}
			$slots = array_merge(
				$slots,
				$this->slots_for_shift( $shift->start_datetime, $shift->end_datetime, $duration, $interval, $blocked, $lead_cutoff )
			);
		}

		$slots = array_values( array_unique( $slots ) );

		return $this->format_slots_local_iso( $slots );
	}

	public function get_available_slots_for_any( $start_date, $end_date, $service_id ) {
		$slots = array();
		foreach ( $this->employees as $employee ) {
			$employee_slots = $this->get_available_slots( $employee->ID, $start_date, $end_date, $service_id );
			foreach ( $employee_slots as $slot ) {
				$slots[] = array(
					'start' => $slot,
					'employee_id' => $employee->ID,
					'employee_name' => $employee->display_name,
				);
			}
		}

		usort(
			$slots,
			function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);

		return $slots;
	}

	public function get_first_available_slot( $employee_id, $start_date, $end_date, $service_id ) {
		if ( $employee_id ) {
			$slots = $this->get_available_slots( $employee_id, $start_date, $end_date, $service_id );
			if ( empty( $slots ) ) {
				return null;
			}
			$employee = $this->find_employee( $employee_id );
			return array(
				'start' => $slots[0],
				'employee_id' => $employee_id,
				'employee_name' => $employee ? $employee->display_name : '',
			);
		}

		$slots = $this->get_available_slots_for_any( $start_date, $end_date, $service_id );
		if ( empty( $slots ) ) {
			return null;
		}

		return $slots[0];
	}

	public function is_slot_available( $employee_id, DateTimeImmutable $local_start, $service_id ) {
		$start_date = $local_start->format( 'Y-m-d' );
		$slots = $this->get_available_slots( $employee_id, $start_date, $start_date, $service_id );
		return in_array( $local_start->format( DATE_ATOM ), $slots, true );
	}

	private function slots_for_shift( $shift_start_utc, $shift_end_utc, $duration, $interval, $blocked, $lead_cutoff ) {
		$slots = array();

		$shift_start = new DateTimeImmutable( $shift_start_utc, DateTimeHelper::utc_timezone() );
		$shift_end = new DateTimeImmutable( $shift_end_utc, DateTimeHelper::utc_timezone() );
		$cursor = DateTimeHelper::round_up_to_interval( $shift_start, $interval );

		while ( $cursor < $shift_end ) {
			$slot_start = $cursor;
			$slot_end = $cursor->modify( '+' . (int) $duration . ' minutes' );

			if ( $slot_end > $shift_end ) {
				break;
			}

			if ( $slot_start < $lead_cutoff ) {
				$cursor = $cursor->modify( '+' . (int) $interval . ' minutes' );
				continue;
			}

			if ( ! $this->overlaps_blocked( $slot_start, $slot_end, $blocked ) ) {
				$slots[] = $slot_start->format( 'Y-m-d H:i:s' );
			}

			$cursor = $cursor->modify( '+' . (int) $interval . ' minutes' );
		}

		return $slots;
	}

	private function build_blocked_ranges( $reservations, $buffer_minutes ) {
		$blocked = array();
		$blocked_statuses = array( 'approved', 'pending' );

		foreach ( $reservations as $reservation ) {
			if ( empty( $reservation->status ) || ! in_array( $reservation->status, $blocked_statuses, true ) ) {
				continue;
			}
			$start = new DateTimeImmutable( $reservation->start_datetime, DateTimeHelper::utc_timezone() );
			$end = new DateTimeImmutable( $reservation->end_datetime, DateTimeHelper::utc_timezone() );

			if ( $buffer_minutes > 0 ) {
				$start = $start->modify( '-' . (int) $buffer_minutes . ' minutes' );
				$end = $end->modify( '+' . (int) $buffer_minutes . ' minutes' );
			}

			$blocked[] = array( $start, $end );
		}

		return $blocked;
	}

	private function overlaps_blocked( DateTimeImmutable $slot_start, DateTimeImmutable $slot_end, $blocked ) {
		foreach ( $blocked as $range ) {
			list( $start, $end ) = $range;
			if ( $slot_start < $end && $slot_end > $start ) {
				return true;
			}
		}

		return false;
	}

	private function lead_time_cutoff_utc( $lead_hours ) {
		$now = new DateTimeImmutable( 'now', DateTimeHelper::utc_timezone() );
		if ( $lead_hours <= 0 ) {
			return $now;
		}
		return $now->modify( '+' . (int) $lead_hours . ' hours' );
	}

	private function format_slots_local_iso( $slots ) {
		$formatted = array();
		foreach ( $slots as $slot ) {
			$formatted[] = DateTimeHelper::utc_to_local_iso( $slot );
		}
		return $formatted;
	}

	private function load_employees() {
		return get_users(
			array(
				'role__in' => array( 'editor' ),
				'orderby' => 'display_name',
				'order' => 'ASC',
			)
		);
	}

	private function find_employee( $employee_id ) {
		foreach ( $this->employees as $employee ) {
			if ( (int) $employee->ID === (int) $employee_id ) {
				return $employee;
			}
		}
		return null;
	}

	private function get_holiday_dates() {
		$holiday_dates = isset( $this->settings['holiday_dates'] ) ? (array) $this->settings['holiday_dates'] : array();
		$manual_dates = isset( $this->settings['holiday_manual_dates'] ) ? (array) $this->settings['holiday_manual_dates'] : array();
		$dates = array_merge( $holiday_dates, $manual_dates );
		$dates = array_map( 'sanitize_text_field', $dates );
		$dates = array_filter(
			$dates,
			function ( $date ) {
				return (bool) preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', (string) $date );
			}
		);

		return array_values( array_unique( $dates ) );
	}

	private function is_holiday_datetime( $utc_datetime, $holiday_dates ) {
		if ( empty( $holiday_dates ) ) {
			return false;
		}
		$local_date = DateTimeHelper::utc_to_local( $utc_datetime, 'Y-m-d' );
		return in_array( $local_date, $holiday_dates, true );
	}
}
