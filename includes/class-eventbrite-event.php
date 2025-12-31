<?php
namespace WidgetForEventbriteAPI\Includes;

defined( 'ABSPATH' ) || exit;

class Eventbrite_Event {

	/**
	 * Event ID.
	 *
	 * @var int
	 */
	public $ID; // id

	/**
	 * The id  sames as ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The name object text and html
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The event's title.
	 *
	 * @var string
	 */
	public $post_title; // name->text

	/**
	 * The post type.
	 *
	 * @var string
	 */
	public $post_type = 'wfea'; // type->text

	/**
	 * The event's content.
	 *
	 * @var string
	 */
	public $post_content; // description->html

	/**
	 * Date on which the event was created.
	 *
	 * @var string
	 */
	public $post_date; // created

	/**
	 * The event's Eventbrite.com URL.
	 *
	 * @var string
	 */
	public $url; // url

	/**
	 * The event's logo URL.
	 *
	 * @var string
	 */
	public $logo_url; // logo_url

	/**
	 * The event's UTC start time.
	 *
	 * @var string
	 */
	public $start; // start->utc

	/**
	 * The event's UTC end time.
	 *
	 * @var string
	 */
	public $end;  // start->utc

	/**
	 * The event organizer's name.
	 *
	 * @var string
	 */
	public $post_author; // organizer->name

	/**
	 * The event organizer's ID.
	 *
	 * @var int
	 */
	public $organizer_id; // organizer->id

	/**
	 * The event's venue.
	 *
	 * @var string
	 */
	public $venue;  // venue->name

	/**
	 * The venue's ID.
	 *
	 * @var int
	 */
	public $venue_id; // venue->id

	/**
	 * The event's subcategory.
	 *
	 * @var string
	 */
	public $subcategory;  // subcategory->name

	/**
	 * The event's category.
	 *
	 * @var string
	 */
	public $category;  // category->name

	/**
	 * The event's format.
	 *
	 * @var string
	 */
	public $format;  // format->name

	public $summary;
	public $post_excerpt;
	public $created;
	public $post_date_gmt;
	public $logo;
	public $eb_url;
	public $status;
	public $public;
	public $eb_published;
	public $organizer;
	public $tickets;
	public $series_id;
	public $is_series;
	public $is_free;
	public $is_series_parent;
	public $is_externally_ticketed;
	public $online_event;
	public $ticket_availability;
	public $capacity;
	public $event_sales_status;
	public $series_dates;
	public $music_properties;
	public $filter;
	public $long_description;

	/**
	 * The text to use for excerpt display.
	 * Populated based on long_description setting - contains either summary or full description (HTML stripped).
	 *
	 * @var string
	 */
	public $wfea_excerpt_text;

	public $original_system;

	public $external_ticketing;

	public $tags;



		/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @param object $event An event object from the API results.
	 */
	public function __construct( $event ) {
		foreach ( get_object_vars( $event ) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Retrieve Eventbrite_Event instance.
	 *
	 * @static
	 * @access public
	 *
	 * @param int $event_id Event ID on eventbrite.com (commonly ten digits).
	 *
	 * @return Eventbrite_Event|bool Eventbrite_Event object, false otherwise.
	 */
	public static function get_instance( $event_id ) {
		// We can bail if no event ID was passed, or it wasn't an integer.
		if ( ! $event_id || ! is_int( $event_id ) ) {
			return false;
		}

		// Get the raw event.
		$event = Eventbrite_Query::eventbrite_get_event( $event_id );

		// Return false if the ID was invalid or we got an error from the API call.
		if ( ! $event || ! empty( $event->error ) ) {
			return false;
		}

		// We've got an event, let's dress it up.
		return new Eventbrite_Event( $event->events[0] );
	}
}
