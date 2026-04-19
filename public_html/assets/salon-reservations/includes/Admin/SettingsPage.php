<?php
namespace Salon\Reservations\Admin;

use Salon\Reservations\Utils\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsPage {
	public function handle_actions() {
		if ( isset( $_POST['salon_settings_action'] ) && 'save' === $_POST['salon_settings_action'] ) {
			$section = sanitize_text_field( wp_unslash( $_POST['settings_section'] ?? 'general' ) );
			if ( 'addons' === $section ) {
				return;
			}
			$this->save();
		}
	}

	private function save() {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			return;
		}

		check_admin_referer( 'salon_settings_save' );

		$current = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$section = sanitize_text_field( wp_unslash( $_POST['settings_section'] ?? 'general' ) );
		$settings = array();
		if ( 'general' === $section && isset( $_POST['buffer_minutes'] ) ) {
			$settings['buffer_minutes'] = (int) $_POST['buffer_minutes'];
		}
		if ( 'general' === $section && isset( $_POST['lead_time_hours'] ) ) {
			$settings['lead_time_hours'] = (int) $_POST['lead_time_hours'];
		}
		if ( 'general' === $section && isset( $_POST['slot_interval_minutes'] ) ) {
			$settings['slot_interval_minutes'] = (int) $_POST['slot_interval_minutes'];
		}
		if ( 'general' === $section ) {
			$settings['employee_pre_approval'] = isset( $_POST['employee_pre_approval'] );
		}
		if ( 'general' === $section && isset( $_POST['salon_contact_email'] ) ) {
			$settings['salon_contact_email'] = sanitize_email( wp_unslash( $_POST['salon_contact_email'] ) );
		}
		if ( 'opening_hours' === $section ) {
			$opening = array();
			$days = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
			foreach ( $days as $day ) {
				$open = isset( $_POST['opening_open'][ $day ] );
				$start = sanitize_text_field( wp_unslash( $_POST['opening_start'][ $day ] ?? '' ) );
				$end = sanitize_text_field( wp_unslash( $_POST['opening_end'][ $day ] ?? '' ) );

				if ( ! $open ) {
					$opening[ $day ] = array( 'open' => false, 'start' => '', 'end' => '' );
					continue;
				}

				if ( ! $this->is_valid_time( $start ) || ! $this->is_valid_time( $end ) ) {
					$opening[ $day ] = array( 'open' => false, 'start' => '', 'end' => '' );
					continue;
				}

				if ( strtotime( '1970-01-01 ' . $end ) <= strtotime( '1970-01-01 ' . $start ) ) {
					$opening[ $day ] = array( 'open' => false, 'start' => '', 'end' => '' );
					continue;
				}

				$opening[ $day ] = array(
					'open' => true,
					'start' => $start,
					'end' => $end,
				);
			}

			$settings['opening_hours'] = $opening;
		}

		if ( 'holidays' === $section ) {
			$country = sanitize_text_field( wp_unslash( $_POST['holiday_country'] ?? 'HR' ) );
			$year = (int) ( $_POST['holiday_year'] ?? (int) date_i18n( 'Y' ) );
			if ( $year < 1970 || $year > 2100 ) {
				$year = (int) date_i18n( 'Y' );
			}

			$selected = isset( $_POST['holiday_dates'] ) ? (array) $_POST['holiday_dates'] : array();
			$selected = array_map( 'sanitize_text_field', $selected );
			$selected = array_values( array_unique( array_filter( $selected, array( $this, 'is_valid_date' ) ) ) );

			$manual = isset( $_POST['holiday_manual_dates'] ) ? (array) $_POST['holiday_manual_dates'] : array();
			$manual = array_map( 'sanitize_text_field', $manual );
			$manual = array_values( array_unique( array_filter( $manual, array( $this, 'is_valid_date' ) ) ) );
			$new_manual = sanitize_text_field( wp_unslash( $_POST['holiday_manual_new'] ?? '' ) );
			if ( $this->is_valid_date( $new_manual ) && ! in_array( $new_manual, $manual, true ) ) {
				$manual[] = $new_manual;
			}

			$settings['holiday_country'] = $country;
			$settings['holiday_year'] = $year;
			$settings['holiday_dates'] = $selected;
			$settings['holiday_manual_dates'] = $manual;
		}

		update_option( SALON_RESERVATIONS_OPTION_SETTINGS, array_merge( $current, $settings ) );

		wp_safe_redirect( admin_url( 'admin.php?page=salon-reservations&tab=postavke&updated=1' ) );
		exit;
	}

	public function render( $embedded = false ) {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'Nemate dozvolu za pristup ovoj stranici.', 'salon-reservations' ) );
		}

		$settings = get_option( SALON_RESERVATIONS_OPTION_SETTINGS, array() );
		$holiday_country = $settings['holiday_country'] ?? 'HR';
		if ( isset( $_GET['holiday_country'] ) ) {
			$holiday_country = sanitize_text_field( wp_unslash( $_GET['holiday_country'] ) );
		}
		$holiday_year = (int) ( $settings['holiday_year'] ?? (int) date_i18n( 'Y' ) );
		if ( isset( $_GET['holiday_year'] ) ) {
			$holiday_year = (int) $_GET['holiday_year'];
		}
		if ( $holiday_year < 1970 || $holiday_year > 2100 ) {
			$holiday_year = (int) date_i18n( 'Y' );
		}
		$holiday_dates = isset( $settings['holiday_dates'] ) ? (array) $settings['holiday_dates'] : array();
		$holiday_dates = array_map( 'sanitize_text_field', $holiday_dates );
		$manual_dates = isset( $settings['holiday_manual_dates'] ) ? (array) $settings['holiday_manual_dates'] : array();
		$manual_dates = array_map( 'sanitize_text_field', $manual_dates );
		$holiday_list = $this->fetch_public_holidays( $holiday_country, $holiday_year );
		?>
		<div class="<?php echo esc_attr( $embedded ? 'salon-tabs__panel' : 'wrap' ); ?>">
			<?php if ( ! $embedded ) : ?>
				<h1><?php esc_html_e( 'Postavke rezervacija', 'salon-reservations' ); ?></h1>
			<?php endif; ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Postavke su spremljene.', 'salon-reservations' ); ?></p></div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'salon_settings_save' ); ?>
				<input type="hidden" name="salon_settings_action" value="save" />
				<input type="hidden" name="settings_section" value="general" />

				<table class="form-table">
					<tr>
						<th scope="row"><label for="buffer_minutes"><?php esc_html_e( 'Buffer (minute)', 'salon-reservations' ); ?></label></th>
						<td><input type="number" id="buffer_minutes" name="buffer_minutes" value="<?php echo esc_attr( $settings['buffer_minutes'] ?? 10 ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="lead_time_hours"><?php esc_html_e( 'Minimalno vrijeme unaprijed (sati)', 'salon-reservations' ); ?></label></th>
						<td><input type="number" id="lead_time_hours" name="lead_time_hours" value="<?php echo esc_attr( $settings['lead_time_hours'] ?? 2 ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="slot_interval_minutes"><?php esc_html_e( 'Interval termina (minute)', 'salon-reservations' ); ?></label></th>
						<td><input type="number" id="slot_interval_minutes" name="slot_interval_minutes" value="<?php echo esc_attr( $settings['slot_interval_minutes'] ?? 15 ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="salon_contact_email"><?php esc_html_e( 'Kontakt email salona', 'salon-reservations' ); ?></label></th>
						<td><input type="email" id="salon_contact_email" name="salon_contact_email" value="<?php echo esc_attr( $settings['salon_contact_email'] ?? get_option( 'admin_email' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pre-odobravanje zaposlenika', 'salon-reservations' ); ?></th>
						<td><label><input type="checkbox" name="employee_pre_approval" <?php checked( ! empty( $settings['employee_pre_approval'] ) ); ?> /> <?php esc_html_e( 'Dopusti zaposlenicima da odobre rezervacije.', 'salon-reservations' ); ?></label></td>
					</tr>
				</table>

				<?php submit_button( __( 'Spremi postavke', 'salon-reservations' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Radno vrijeme (24-satni format)', 'salon-reservations' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'salon_settings_save' ); ?>
				<input type="hidden" name="salon_settings_action" value="save" />
				<input type="hidden" name="settings_section" value="opening_hours" />
				<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Dan', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Status', 'salon-reservations' ); ?></th>
						<th><?php esc_html_e( 'Vrijeme', 'salon-reservations' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$days = array(
						'mon' => __( 'Ponedjeljak', 'salon-reservations' ),
						'tue' => __( 'Utorak', 'salon-reservations' ),
						'wed' => __( 'Srijeda', 'salon-reservations' ),
						'thu' => __( 'Četvrtak', 'salon-reservations' ),
						'fri' => __( 'Petak', 'salon-reservations' ),
						'sat' => __( 'Subota', 'salon-reservations' ),
						'sun' => __( 'Nedjelja', 'salon-reservations' ),
					);
					$opening = $settings['opening_hours'] ?? array();
					if ( empty( $opening ) ) {
						$opening = array(
							'mon' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
							'tue' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
							'wed' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
							'thu' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
							'fri' => array( 'open' => true, 'start' => '08:00', 'end' => '20:00' ),
							'sat' => array( 'open' => true, 'start' => '08:00', 'end' => '13:00' ),
							'sun' => array( 'open' => false, 'start' => '', 'end' => '' ),
						);
					}
					foreach ( $days as $key => $label ) :
						$day = $opening[ $key ] ?? array();
						$is_open = ! empty( $day['open'] );
						$start = $day['start'] ?? '';
						$end = $day['end'] ?? '';
						?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td>
								<label>
									<input type="checkbox" name="opening_open[<?php echo esc_attr( $key ); ?>]" <?php checked( $is_open ); ?> />
									<?php echo esc_html( __( 'Otvoreno', 'salon-reservations' ) ); ?>
								</label>
							</td>
							<td>
								<input type="time" name="opening_start[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $start ); ?>" />
								<span>–</span>
								<input type="time" name="opening_end[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $end ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				</table>
				<?php submit_button( __( 'Spremi radno vrijeme', 'salon-reservations' ) ); ?>
			</form>

			<style>
				.salon-holidays__panel { background: var(--salon-glass-bg); border: 1px solid var(--salon-glass-border); border-radius: var(--salon-glass-radius); box-shadow: var(--salon-glass-shadow); padding: 12px; position: relative; z-index: 1; overflow: visible; margin-bottom: 16px; }
				.salon-holidays__panel .form-table { background: transparent; border: 0; box-shadow: none; overflow: visible; margin-bottom: 10px; }
				.salon-holidays__panel .form-table th,
				.salon-holidays__panel .form-table td { padding: 10px !important; }
				.salon-holidays__list { max-height: 240px; overflow: auto; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 12px; padding: 10px; background: rgba(255, 255, 255, 0.75); box-shadow: inset 0 1px 2px rgba(255,255,255,0.6); display: grid; gap: 6px; }
				.salon-holidays__item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 13px; }
				.salon-holidays__manual-list { margin-top: 8px; display: grid; gap: 6px; }
			</style>
			<h2><?php esc_html_e( 'Javni praznici', 'salon-reservations' ); ?></h2>
			<?php
			$year_options = range( (int) date_i18n( 'Y' ) - 1, (int) date_i18n( 'Y' ) + 2 );
			if ( ! in_array( $holiday_year, $year_options, true ) ) {
				$year_options[] = $holiday_year;
				sort( $year_options );
			}
			?>
			<div class="salon-holidays__panel">
				<form method="get" class="salon-holidays__filters">
					<input type="hidden" name="page" value="salon-reservations" />
					<input type="hidden" name="tab" value="postavke" />
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Država', 'salon-reservations' ); ?></th>
							<td>
								<select name="holiday_country">
									<?php foreach ( $this->get_holiday_countries() as $code => $label ) : ?>
										<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $holiday_country, $code ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Godina', 'salon-reservations' ); ?></th>
							<td>
								<select name="holiday_year">
									<?php foreach ( $year_options as $year ) : ?>
										<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $holiday_year, $year ); ?>>
											<?php echo esc_html( $year ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Učitaj praznike', 'salon-reservations' ), 'secondary', 'submit', false ); ?>
				</form>

				<form method="post" class="salon-holidays">
					<?php wp_nonce_field( 'salon_settings_save' ); ?>
					<input type="hidden" name="salon_settings_action" value="save" />
					<input type="hidden" name="settings_section" value="holidays" />
					<input type="hidden" name="holiday_country" value="<?php echo esc_attr( $holiday_country ); ?>" />
					<input type="hidden" name="holiday_year" value="<?php echo esc_attr( $holiday_year ); ?>" />
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Javni praznici', 'salon-reservations' ); ?></th>
							<td>
								<div class="salon-holidays__list">
									<?php if ( empty( $holiday_list ) ) : ?>
										<p><?php esc_html_e( 'Nema dostupnih praznika za odabranu godinu.', 'salon-reservations' ); ?></p>
									<?php else : ?>
										<?php foreach ( $holiday_list as $holiday ) : ?>
											<?php
												$date = $holiday['date'] ?? '';
												$label = $holiday['name'] ?? $date;
												if ( ! $this->is_valid_date( $date ) ) {
													continue;
												}
											?>
											<label class="salon-holidays__item">
												<input type="checkbox" name="holiday_dates[]" value="<?php echo esc_attr( $date ); ?>" <?php checked( in_array( $date, $holiday_dates, true ) ); ?> />
												<span><?php echo esc_html( $date . ' – ' . $label ); ?></span>
											</label>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
								<p class="description"><?php esc_html_e( 'Odabrani praznici će biti neradni dani u kalendaru.', 'salon-reservations' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Dodatni neradni dan', 'salon-reservations' ); ?></th>
							<td>
								<input type="date" name="holiday_manual_new" />
								<?php if ( ! empty( $manual_dates ) ) : ?>
									<div class="salon-holidays__manual-list">
										<?php foreach ( $manual_dates as $manual_date ) : ?>
											<?php if ( ! $this->is_valid_date( $manual_date ) ) : ?>
												<?php continue; ?>
											<?php endif; ?>
											<label class="salon-holidays__item">
												<input type="checkbox" name="holiday_manual_dates[]" value="<?php echo esc_attr( $manual_date ); ?>" checked />
												<span><?php echo esc_html( $manual_date ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Spremi praznike', 'salon-reservations' ) ); ?>
				</form>
			</div>

		</div>
		<?php
	}

	private function is_valid_time( $value ) {
		return (bool) preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value );
	}

	private function is_valid_date( $value ) {
		return (bool) preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', (string) $value );
	}

	private function get_holiday_countries() {
		return array(
			'HR' => __( 'Hrvatska', 'salon-reservations' ),
			'SI' => __( 'Slovenija', 'salon-reservations' ),
			'BA' => __( 'Bosna i Hercegovina', 'salon-reservations' ),
			'RS' => __( 'Srbija', 'salon-reservations' ),
			'ME' => __( 'Crna Gora', 'salon-reservations' ),
			'IT' => __( 'Italija', 'salon-reservations' ),
			'AT' => __( 'Austrija', 'salon-reservations' ),
			'DE' => __( 'Njemačka', 'salon-reservations' ),
			'GB' => __( 'Ujedinjeno Kraljevstvo', 'salon-reservations' ),
			'US' => __( 'Sjedinjene Američke Države', 'salon-reservations' ),
		);
	}

	private function fetch_public_holidays( $country, $year ) {
		$country = strtoupper( sanitize_text_field( $country ) );
		$year = (int) $year;
		if ( $year < 1970 || $year > 2100 ) {
			return array();
		}

		$transient_key = 'salon_holidays_' . strtolower( $country ) . '_' . $year;
		$cached = get_transient( $transient_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$url = sprintf( 'https://date.nager.at/api/v3/PublicHolidays/%d/%s', $year, rawurlencode( $country ) );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$holidays = array();
		foreach ( $data as $item ) {
			if ( empty( $item['date'] ) ) {
				continue;
			}
			$holidays[] = array(
				'date' => sanitize_text_field( $item['date'] ),
				'name' => sanitize_text_field( $item['localName'] ?? ( $item['name'] ?? '' ) ),
			);
		}

		set_transient( $transient_key, $holidays, DAY_IN_SECONDS );
		return $holidays;
	}
}
