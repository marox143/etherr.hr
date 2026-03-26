<?php
namespace Salon\Reservations\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Capabilities {
	const MANAGE_EMPLOYEES = 'salon_manage_employees';
	const MANAGE_RESERVATIONS = 'salon_manage_reservations';
	const VIEW_ALL_CALENDARS = 'salon_view_all_calendars';
	const MANAGE_SETTINGS = 'salon_manage_settings';
	const MANAGE_SHIFTS_ALL = 'salon_manage_shifts_all';
	const MANAGE_SHIFTS_OWN = 'salon_manage_shifts_own';
	const VIEW_RESERVATIONS_OWN = 'salon_view_reservations_own';
	const REQUEST_SHIFT_CHANGE = 'salon_request_shift_change';
	const APPROVE_SHIFT_CHANGES = 'salon_approve_shift_changes';
	const EMPLOYEE_PRE_APPROVAL = 'salon_employee_pre_approval';
	const APPROVE_RESERVATIONS_OWN = 'salon_approve_reservations_own';
	const CREATE_RESERVATIONS_OWN = 'salon_create_reservations_own';

	public static function all_caps() {
		return array(
			self::MANAGE_EMPLOYEES,
			self::MANAGE_RESERVATIONS,
			self::VIEW_ALL_CALENDARS,
			self::MANAGE_SETTINGS,
			self::MANAGE_SHIFTS_ALL,
			self::MANAGE_SHIFTS_OWN,
			self::VIEW_RESERVATIONS_OWN,
			self::REQUEST_SHIFT_CHANGE,
			self::APPROVE_SHIFT_CHANGES,
			self::EMPLOYEE_PRE_APPROVAL,
			self::APPROVE_RESERVATIONS_OWN,
			self::CREATE_RESERVATIONS_OWN,
		);
	}

	public static function add_caps() {
		$admin = get_role( 'administrator' );
		$editor = get_role( 'editor' );

		if ( $admin ) {
			foreach ( self::all_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		if ( $editor ) {
			$editor_caps = array(
				self::MANAGE_SHIFTS_OWN,
				self::VIEW_RESERVATIONS_OWN,
				self::REQUEST_SHIFT_CHANGE,
				self::APPROVE_RESERVATIONS_OWN,
				self::CREATE_RESERVATIONS_OWN,
			);
			foreach ( $editor_caps as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}
}
