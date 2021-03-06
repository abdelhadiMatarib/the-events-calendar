<?php
// Don't load directly
defined( 'WPINC' ) or die;

class Tribe__Events__Aggregator__API__Import extends Tribe__Events__Aggregator__API__Abstract {
	public $google_maps_enabled;

	public $event_field_map = array(
		'title'          => 'post_title',
		'description'    => 'post_content',
		'start_date'     => 'EventStartDate',
		'start_hour'     => 'EventStartHour',
		'start_minute'   => 'EventStartMinute',
		'start_meridian' => 'EventStartMeridian',
		'end_date'       => 'EventEndDate',
		'end_hour'       => 'EventEndHour',
		'end_minute'     => 'EventEndMinute',
		'end_meridian'   => 'EventEndMeridian',
		'url'            => 'EventURL',
		'parent_id'      => 'parent_id',
		'uid'            => 'uid',
		'facebook_id'    => 'facebook_id',
		'dev_start'      => 'dev_start',
		'dev_end'        => 'dev_end',
		'all_day'        => 'EventAllDay',
		'timezone'       => 'EventTimezone',
		'recurrence'     => 'recurrence',
		'categories'     => 'categories',
	);

	public $organizer_field_map = array(
		'facebook_id' => 'FacebookID',
		'organizer'   => 'Organizer',
		'phone'       => 'Phone',
		'website'     => 'Website',
		'email'       => 'Email',
	);

	public $venue_field_map = array(
		'facebook_id'           => 'FacebookID',
		'venue'                 => 'Venue',
		'address'               => 'Address',
		'city'                  => 'City',
		'stateprovince'         => 'StateProvince',
		'country'               => 'Country',
		'zip'                   => 'Zip',
		'phone'                 => 'Phone',
		'overwrite_coordinates' => 'OverwriteCoords',
		'latitude'              => 'Lat',
		'longitude'             => 'Lng',
	);

	public function __construct() {
		parent::__construct();

		// @TODO: migrate the ical settings to the-events-calendar
		$this->google_maps_enabled = tribe_get_option( 'ical_import_enable_GoogleMaps', false );
	}

	/**
	 * Gets the status (and possibly the results) of an import
	 *
	 * @param string $import_id Event Aggregator import id
	 *
	 * @return stdClass|WP_Error
	 */
	public function get( $import_id ) {
		$response = $this->service->get_import( $import_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 'success_import-complete' !== $response->message_code ) {
			// @TODO: maybe update the import record with the current status
			return $response;
		}

		$events = array();

		foreach ( $response->data->events as $event ) {
			$events[] = $this->translate_json_to_event( $event );
		}

		// @TODO: do something with the events - determine if they need to be inserted
		//        disable pseudo cron for polling for updates
	}

	/**
	 * Creates an import
	 *
	 * @param array $args Array of arguments for event creation
	 *
	 * @return stdClass|WP_Error
	 */
	public function create( $args ) {
		$response = $this->service->post_import( $args );
		// @TODO: create import record
		//        store import_id on the import record
		//        set the import record status
		//        create pseudo cron to check status of import

		return $response;
	}

	/**
	 * Creates an import record post based on data from the Event Aggregator service
	 *
	 * @param object $data Event data
	 */
	public function create_record( $data ) {
	}

	/**
	 * Translates event JSON to the-events-calendar Event array format
	 *
	 * @param object $json Event data in Event Aggregator format
	 *
	 * @return array
	 */
	public function translate_json_to_event( $json ) {
		if (
			empty( $json->title )
			|| empty( $json->start_date )
			|| empty( $json->start_hour )
			|| empty( $json->start_minute )
			|| empty( $json->end_date )
			|| empty( $json->end_hour )
			|| empty( $json->end_minute )
		) {
			return new WP_Error( 'invalid-event-json', __( 'Could not convert event JSON to an event array because it is missing required fields', 'the-events-calendar' ) );
		}

		$event = array();

		$event['post_type'] = Tribe__Events__Main::POSTTYPE;

		// @TODO: get the desired post status for the import
		$event['post_status'] = 'publish';

		// translate json key/value pairs to event array key/value pairs
		foreach ( get_object_vars( $json ) as $key => $value ) {
			// venues and organizers are a bit more complex. We'll handle those later
			if ( 'venue' === $key || 'organizer' === $key ) {
				continue;
			}

			if ( isset( $this->event_field_map[ $key ] ) ) {
				$key = $this->event_field_map[ $key ];
			}

			$event[ $key ] = $value;
		}

		if ( ! empty( $json->venue ) ) {
			$event['Venue'] = array();

			if ( ! is_array( $json->venue ) ) {
				$json->venue = array( $json->venue );
			}

			foreach ( $json->venue as $venue ) {
				$venue_data = array();

				if ( empty( $venue->venue ) ) {
					continue;
				}

				foreach ( get_object_vars( $venue ) as $key => $value ) {
					if ( isset( $this->venue_field_map[ $key ] ) ) {
						$key = $this->venue_field_map[ $key ];
					}

					$venue_data[ $key ] = $value;
				}

				$event['Venue'][] = $venue_data;
			}
		}

		if ( ! empty( $json->organizer ) ) {
			$event['Organizer'] = array();

			if ( ! is_array( $json->organizer ) ) {
				$json->organizer = array( $json->organizer );
			}

			foreach ( $json->organizer as $organizer ) {
				$organizer_data = array();

				if ( empty( $organizer->organizer ) ) {
					continue;
				}

				foreach ( get_object_vars( $organizer ) as $key => $value ) {
					if ( isset( $this->organizer_field_map[ $key ] ) ) {
						$key = $this->organizer_field_map[ $key ];
					}

					$organizer_data[ $key ] = $value;
				}

				$event['Organizer'][] = $organizer_data;
			}
		}

		$event['EventShowMap']     = $this->google_maps_enabled;
		$event['EventShowMapLink'] = $this->google_maps_enabled;

		return $event;
	}
}
