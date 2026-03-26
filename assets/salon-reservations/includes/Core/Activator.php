<?php
namespace Salon\Reservations\Core;

use Salon\Reservations\DB\Schema;
use Salon\Reservations\Utils\Capabilities;
use Salon\Reservations\Repositories\ServicesRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {
	public static function activate() {
		Schema::create();
		Capabilities::add_caps();

		if ( ! get_option( SALON_RESERVATIONS_OPTION_SETTINGS ) ) {
			update_option(
				SALON_RESERVATIONS_OPTION_SETTINGS,
				array(
					'buffer_minutes' => 10,
					'lead_time_hours' => 2,
					'slot_interval_minutes' => 15,
					'employee_pre_approval' => false,
					'salon_contact_email' => get_option( 'admin_email' ),
					'opening_hours' => array(
						'mon' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
						'tue' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
						'wed' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
						'thu' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
						'fri' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
						'sat' => array( 'open' => true, 'start' => '08:00', 'end' => '13:00' ),
						'sun' => array( 'open' => false, 'start' => '', 'end' => '' ),
					),
					'shift_templates' => array(
						array( 'label' => '08:00-16:00', 'start' => '08:00', 'end' => '16:00' ),
						array( 'label' => '14:00-20:00', 'start' => '14:00', 'end' => '20:00' ),
						array( 'label' => '10:00-18:00', 'start' => '10:00', 'end' => '18:00' ),
					),
					'service_addons' => array(
						array( 'label' => 'Pranje kose', 'active' => true ),
						array( 'label' => 'Ispiranje kose vodom', 'active' => true ),
						array( 'label' => 'Masaža vlasišta', 'active' => true ),
						array( 'label' => 'Dizajn', 'active' => true ),
						array( 'label' => 'Ulje za bradu', 'active' => true ),
						array( 'label' => 'Maska za kosu', 'active' => true ),
					),
				)
			);
		}

		if ( ! get_option( SALON_RESERVATIONS_OPTION_UNINSTALL_CLEANUP ) ) {
			update_option( SALON_RESERVATIONS_OPTION_UNINSTALL_CLEANUP, 'yes' );
		}

		update_option( SALON_RESERVATIONS_OPTION_DB_VERSION, SALON_RESERVATIONS_DB_VERSION );

		$services = new ServicesRepository();
		if ( 0 === $services->count() ) {
			$defaults = array(
				array( 'name' => 'Šišanje', 'duration' => 30 ),
				array( 'name' => 'Šišanje duge kose', 'duration' => 60 ),
				array( 'name' => 'Šišanje mašinicom na nulu', 'duration' => 20 ),
				array( 'name' => 'Pranje kose', 'duration' => 15 ),
				array( 'name' => 'Ispiranje kose vodom', 'duration' => 10 ),
				array( 'name' => 'Masaža vlasišta', 'duration' => 15 ),
				array( 'name' => 'Uređivanje brade', 'duration' => 30 ),
				array( 'name' => 'Brijanje brade', 'duration' => 30 ),
				array( 'name' => 'Brijanje glave', 'duration' => 30 ),
				array( 'name' => 'Dizajn', 'duration' => 15 ),
				array( 'name' => 'Bojanje kose', 'duration' => 120 ),
				array( 'name' => 'Dekoloracija kose', 'duration' => 150 ),
				array( 'name' => 'Bojanje brade', 'duration' => 45 ),
			);

			foreach ( $defaults as $service ) {
				$services->insert(
					array(
						'name' => $service['name'],
						'duration_minutes' => (int) $service['duration'],
						'price' => null,
						'status' => 'active',
					)
				);
			}
		}
	}
}
