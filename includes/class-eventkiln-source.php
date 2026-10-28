<?php
/**
 * EventKiln as a second source of events.
 *
 * EventKiln events are fetched in the background on a schedule, never during a page view, and the
 * last good copy is kept indefinitely so an EventKiln outage or a revoked key never empties a page.
 * At query time each event is adapted into the same shape map_event_keys() gives Eventbrite events,
 * so every layout, and any custom template, renders it without knowing where it came from.
 *
 * Deliberately not __premium_only: EventKiln support is part of the free plugin.
 */

namespace WidgetForEventbriteAPI\Includes;

use DateTimeImmutable;
use DateTimeZone;
use WidgetForEventbriteAPI\Admin\Admin;
use WidgetForEventbriteAPI\Admin\Admin_Settings;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class EventKiln_Source {

	/** Last good copy of the raw API events. Not autoloaded, as it can be large. */
	const CACHE_OPTION = 'wfea_eventkiln_events';

	/** Outcome of the most recent fetches, for the admin notice. */
	const STATUS_OPTION = 'wfea_eventkiln_status';

	const CRON_HOOK = 'wfea_eventkiln_refresh';

	/** One-off refresh, e.g. when a list first asks for past events. */
	const REFRESH_NOW_HOOK = 'wfea_eventkiln_refresh_now';

	/** When a list last asked for past events. Past events are fetched only while lists ask for them. */
	const PAST_WANTED_OPTION = 'wfea_eventkiln_past_wanted';

	/** Stop fetching past events when no list has asked for them for this long. */
	const PAST_WANTED_FOR = WEEK_IN_SECONDS;

	/** Cron schedule name; its interval follows the Eventbrite cache expiry. */
	const CRON_SCHEDULE = 'wfea_eventkiln';

	/** Never refresh more often than this, whatever the cache setting. */
	const MIN_INTERVAL = 300;

	/** Prefix that keeps EventKiln IDs apart from Eventbrite IDs everywhere an ID is used. */
	const ID_PREFIX = 'ek-';

	/** Safety stop on following `links.next`. 100 events a page, so 5,000 events. */
	const MAX_PAGES = 50;

	/** Where every live site gets its EventKiln events. */
	const DEFAULT_URL = 'https://eventkiln.com';

	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hooks() {
		add_action( self::CRON_HOOK, array( $this, 'refresh' ) );
		add_action( self::REFRESH_NOW_HOOK, array( $this, 'refresh' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'init', array( $this, 'maintain_schedule' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Seconds between refreshes: the same cache expiry as Eventbrite results. A day unless changed with
	 * the wfea_eventbrite_cache_expiry filter, which Pro sets from its Manage Cache setting.
	 */
	public static function refresh_interval() {
		$seconds = (int) apply_filters( 'wfea_eventbrite_cache_expiry', DAY_IN_SECONDS );
		if ( $seconds <= 0 ) {
			$seconds = DAY_IN_SECONDS;
		}

		return max( self::MIN_INTERVAL, $seconds );
	}

	public function cron_schedules( $schedules ) {
		$schedules[ self::CRON_SCHEDULE ] = array(
			'interval' => self::refresh_interval(),
			'display'  => esc_html__( 'EventKiln refresh', 'widget-for-eventbrite-api' ),
		);

		return $schedules;
	}

	/**
	 * Keep the refresh scheduled exactly while EventKiln is configured, at the current interval.
	 */
	public function maintain_schedule() {
		$event = wp_get_scheduled_event( self::CRON_HOOK );
		if ( $this->is_configured() ) {
			if ( $event && self::CRON_SCHEDULE === $event->schedule && (int) $event->interval === self::refresh_interval() ) {
				return;
			}
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::CRON_SCHEDULE, self::CRON_HOOK );
		} elseif ( $event ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	/**
	 * @return array{url: string, key: string}
	 */
	public function get_settings() {
		$options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );

		return array(
			'url' => self::effective_url( isset( $options['eventkiln_url'] ) ? (string) $options['eventkiln_url'] : '' ),
			'key' => isset( $options['eventkiln_key'] ) ? (string) $options['eventkiln_key'] : '',
		);
	}

	public function is_configured() {
		$settings = $this->get_settings();

		return '' !== $settings['key'];
	}

	/**
	 * Only non-production sites may point at another EventKiln (a local or staging copy).
	 */
	public static function is_development_site() {
		return in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true );
	}

	/**
	 * The EventKiln address to use: the stored one on a development site, otherwise always eventkiln.com.
	 */
	public static function effective_url( $stored ) {
		if ( self::is_development_site() && '' !== (string) $stored ) {
			return (string) $stored;
		}

		return self::DEFAULT_URL;
	}

	/**
	 * Normalise an owner-entered EventKiln address to its base URL, or '' if it isn't one.
	 */
	public static function sanitize_url( $url ) {
		$url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
		if ( '' === $url ) {
			return '';
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return '';
		}
		$base = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		// Allow EventKiln installed under a path, but not the API path pasted in by mistake.
		if ( ! empty( $parts['path'] ) ) {
			$path = preg_replace( '#/api(/v1)?(/.*)?$#', '', untrailingslashit( $parts['path'] ) );
			$base .= $path;
		}

		return untrailingslashit( $base );
	}

	/**
	 * Ask EventKiln who this key belongs to. Used to check the settings on save.
	 *
	 * @return array|WP_Error The organisation (name, currency, token_abilities, ...).
	 */
	public function get_organisation( $url = null, $key = null ) {
		list( $url, $key ) = $this->resolve_credentials( $url, $key );
		$response = $this->request( $url . '/api/v1/organisation', $key );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
	}

	/**
	 * Fetch every upcoming public event and keep it as the last good copy.
	 *
	 * On failure the previous copy is left untouched and the error is recorded for the admin.
	 *
	 * @return true|WP_Error
	 */
	public function refresh( $url = null, $key = null ) {
		list( $url, $key ) = $this->resolve_credentials( $url, $key );
		if ( '' === $url || '' === $key ) {
			return new WP_Error( 'wfea-eventkiln-not-configured', esc_html__( 'EventKiln is not set up.', 'widget-for-eventbrite-api' ) );
		}

		// Cancelled events are kept so lists can show them as cancelled, as Eventbrite's are.
		$now    = gmdate( 'Y-m-d\TH:i:s\Z' );
		$events = $this->fetch_events(
			$url,
			$key,
			array(
				'ends_after' => $now,
				'sort'       => 'starts_at',
			)
		);
		if ( is_wp_error( $events ) ) {
			return $events;
		}
		$past = null;
		if ( $this->past_wanted() ) {
			// Newest first, so the page limit drops the oldest.
			$past = $this->fetch_events(
				$url,
				$key,
				array(
					'ends_before' => $now,
					'sort'        => '-starts_at',
				)
			);
			if ( is_wp_error( $past ) ) {
				return $past;
			}
		}

		$organisation = $this->get_organisation( $url, $key );
		$org_name     = ( ! is_wp_error( $organisation ) && isset( $organisation['name'] ) ) ? $organisation['name'] : '';

		$previous = get_option( self::CACHE_OPTION );
		update_option(
			self::CACHE_OPTION,
			array(
				'source'       => $url,
				'fetched_at'   => time(),
				'organisation' => $org_name,
				'events'       => $events,
				// Null when no list has asked for past events.
				'past_events'  => $past,
			),
			false
		);

		// Purge page caches only when what visitors would see has actually changed.
		$previous_past = ( is_array( $previous ) && isset( $previous['past_events'] ) ) ? $previous['past_events'] : null;
		if ( ! is_array( $previous ) || ! isset( $previous['events'] ) || $previous['events'] !== $events || $previous_past !== $past || $previous['source'] !== $url ) {
			Utilities::get_instance()->clear_all_caches_for_posts_with_shortcode();
		}

		$status                 = $this->get_status();
		$status['last_success'] = time();
		$status['last_error']   = '';
		update_option( self::STATUS_OPTION, $status, false );

		return true;
	}

	/**
	 * Fetch every page of one events list.
	 *
	 * @param string $url  EventKiln address.
	 * @param string $key  API key.
	 * @param array  $args Query args added to the common ones.
	 *
	 * @return array|WP_Error
	 */
	private function fetch_events( $url, $key, $args ) {
		$next   = add_query_arg(
			array_merge(
				array(
					'status'     => 'published,cancelled',
					'visibility' => 'public',
					'per_page'   => 100,
				),
				$args
			),
			$url . '/api/v1/events'
		);
		$events = array();
		$pages  = 0;
		while ( $next && $pages < self::MAX_PAGES ) {
			$response = $this->request( $next, $key );
			if ( is_wp_error( $response ) ) {
				$this->record_failure( $response );

				return $response;
			}
			if ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
				$events = array_merge( $events, $response['data'] );
			}
			$next = isset( $response['links']['next'] ) ? (string) $response['links']['next'] : '';
			// The token goes with every page, so only ever follow a link back to the same server.
			if ( $next && ! $this->same_origin( $next, $url ) ) {
				$next = '';
			}
			$pages ++;
		}

		return $events;
	}

	/**
	 * Whether a list has asked for past events recently enough to keep fetching them.
	 */
	private function past_wanted() {
		return (int) get_option( self::PAST_WANTED_OPTION, 0 ) > time() - self::PAST_WANTED_FOR;
	}

	/**
	 * Whether a list's status filter can include events that have ended.
	 */
	private function asks_for_past( $query_vars ) {
		$status = isset( $query_vars['status'] ) ? (string) $query_vars['status'] : '';

		return 'all' === $status || false !== strpos( $status, 'ended' ) || false !== strpos( $status, 'completed' );
	}

	/**
	 * Note that a list asked for past events, and fetch them now if they aren't in the cache.
	 *
	 * @param array|false $cache The cached copy.
	 */
	private function note_past_wanted( $cache ) {
		// At most one write a day, as this runs on page views.
		if ( (int) get_option( self::PAST_WANTED_OPTION, 0 ) < time() - DAY_IN_SECONDS ) {
			update_option( self::PAST_WANTED_OPTION, time(), false );
		}
		if ( is_array( $cache ) && ! isset( $cache['past_events'] ) && ! wp_next_scheduled( self::REFRESH_NOW_HOOK ) ) {
			wp_schedule_single_event( time(), self::REFRESH_NOW_HOOK );
		}
	}

	/**
	 * Forget the fetched copy, e.g. when the EventKiln settings are removed or point elsewhere.
	 */
	public function forget() {
		delete_option( self::CACHE_OPTION );
		delete_option( self::STATUS_OPTION );
		wp_clear_scheduled_hook( self::CRON_HOOK );
		wp_clear_scheduled_hook( self::REFRESH_NOW_HOOK );
	}

	public function get_status() {
		$status = get_option( self::STATUS_OPTION );

		return wp_parse_args(
			is_array( $status ) ? $status : array(),
			array(
				'last_success'  => 0,
				'last_error'    => '',
				'last_error_at' => 0,
			)
		);
	}

	/**
	 * EventKiln events for one Eventbrite_Query, in the shape of map_event_keys().
	 *
	 * Queries that are tied to something only Eventbrite has - an organization, a collection, a
	 * series, or an Eventbrite key other than the default - get no EventKiln events.
	 *
	 * @param array $query_vars Eventbrite_Query query vars.
	 *
	 * @return array
	 */
	public function get_events_for_query( $query_vars ) {
		// source="eventbrite" leaves EventKiln out, and source="eventkiln" asks for it whatever
		// else the list is set to. With no source, lists tied to Eventbrite leave it out.
		$source = isset( $query_vars['source'] ) ? $query_vars['source'] : 'all';
		if ( 'eventbrite' === $source ) {
			$include = false;
		} elseif ( 'eventkiln' === $source ) {
			$include = $this->is_configured();
		} else {
			$include = $this->is_configured() && ! $this->is_eventbrite_specific( $query_vars );
		}
		/**
		 * Whether EventKiln events are merged into this query's results.
		 *
		 * @param bool  $include    Default decision.
		 * @param array $query_vars Eventbrite_Query query vars.
		 */
		if ( ! apply_filters( 'wfea_include_eventkiln_events', $include, $query_vars ) ) {
			return array();
		}

		$cache = get_option( self::CACHE_OPTION );
		if ( $this->asks_for_past( $query_vars ) ) {
			$this->note_past_wanted( $cache );
		}
		if ( ! is_array( $cache ) || ! isset( $cache['events'] ) || ! is_array( $cache['events'] ) ) {
			return array();
		}
		$settings = $this->get_settings();
		if ( isset( $cache['source'] ) && $cache['source'] !== $settings['url'] ) {
			return array();
		}

		$all = $cache['events'];
		if ( $this->asks_for_past( $query_vars ) && ! empty( $cache['past_events'] ) && is_array( $cache['past_events'] ) ) {
			$all = array_merge( $all, $cache['past_events'] );
		}

		$organisation = isset( $cache['organisation'] ) ? $cache['organisation'] : '';
		$events       = array();
		foreach ( $all as $raw ) {
			$event = $this->map_event( $raw, $query_vars, $organisation );
			if ( false !== $event && $this->matches_query( $event, $query_vars ) ) {
				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * Convert one EventKiln API event into the keys used by Eventbrite_Event.
	 *
	 * @param array  $raw          Event from the EventKiln API.
	 * @param array  $query_vars   Eventbrite_Query query vars.
	 * @param string $organisation Organisation name.
	 *
	 * @return object|false
	 */
	public function map_event( $raw, $query_vars = array(), $organisation = '' ) {
		if ( ! is_array( $raw ) || empty( $raw['id'] ) || empty( $raw['starts_at'] ) || empty( $raw['ends_at'] ) ) {
			return false;
		}
		try {
			$tz    = new DateTimeZone( ! empty( $raw['timezone'] ) ? $raw['timezone'] : 'UTC' );
			$start = new DateTimeImmutable( $raw['starts_at'] );
			$end   = new DateTimeImmutable( $raw['ends_at'] );
		} catch ( \Exception $e ) {
			return false;
		}

		$id          = self::ID_PREFIX . (int) $raw['id'];
		$title       = isset( $raw['title'] ) ? (string) $raw['title'] : '';
		$summary     = isset( $raw['summary'] ) ? (string) $raw['summary'] : '';
		$description = isset( $raw['description'] ) ? (string) $raw['description'] : '';
		// EventKiln sends description_html (bold, italics, lists, links) already cleaned, but it is
		// still a third party's HTML, so keep it to what a post may contain. Older EventKiln only
		// sends description, plain text with line breaks kept.
		if ( isset( $raw['description_html'] ) && is_string( $raw['description_html'] ) && '' !== trim( $raw['description_html'] ) ) {
			$description_html = wp_kses_post( $raw['description_html'] );
		} else {
			$description_html = wpautop( esc_html( $description ) );
		}
		$long             = ! empty( $query_vars['long_description'] );
		$checkout_url     = isset( $raw['checkout_url'] ) ? (string) $raw['checkout_url'] : '';
		$public_url       = isset( $raw['public_url'] ) ? (string) $raw['public_url'] : $checkout_url;
		$external         = isset( $raw['checkout_mode'] ) && 'external' === $raw['checkout_mode'];

		$event = array(
			'ID'                     => $id,
			'id'                     => $id,
			'post_title'             => $title,
			'name'                   => (object) array(
				'text' => $title,
				'html' => esc_html( $title ),
			),
			'post_type'              => 'wfea_eventbrite',
			// Templates pass events to the_title_attribute(), whose get_post() otherwise runs
			// sanitize_post() on the object in place and casts the ek- ID to 0.
			'filter'                 => 'raw',
			'post_content'           => $long ? $description_html : ( '' !== $summary ? $summary : $description ),
			'summary'                => $summary,
			'post_excerpt'           => $summary,
			'start'                  => $this->date_object( $start, $tz ),
			'end'                    => $this->date_object( $end, $tz ),
			'created'                => isset( $raw['created_at'] ) ? $raw['created_at'] : '',
			'eb_published'           => isset( $raw['created_at'] ) ? $raw['created_at'] : '',
			'eb_url'                 => $checkout_url,
			'url'                    => apply_filters( 'wfea_eventkiln_event_url', $checkout_url, $raw ),
			'status'                 => $this->event_status( $raw, $start, $end ),
			'public'                 => true,
			'organizer'              => $this->map_organiser( $raw, $organisation ),
			'venue'                  => $this->map_venue( $raw ),
			'category'               => empty( $raw['category']['id'] ) ? '' : (object) array(
				'id'         => self::ID_PREFIX . (int) $raw['category']['id'],
				'name'       => isset( $raw['category']['name'] ) ? $raw['category']['name'] : '',
				'short_name' => isset( $raw['category']['name'] ) ? $raw['category']['name'] : '',
			),
			'subcategory'            => '',
			'format'                 => '',
			'series_id'              => '',
			'is_series'              => false,
			'is_series_parent'       => false,
			'series_dates'           => false,
			'online_event'           => ! empty( $raw['is_online'] ),
			'tags'                   => array(),
			'music_properties'       => $this->map_music_properties( $raw ),
			'performances'           => $this->map_lineup( $raw ),
			'original_system'        => 'eventkiln',
			'eventkiln'              => (object) array(
				'id'                   => (int) $raw['id'],
				'public_url'           => $public_url,
				'checkout_url'         => $checkout_url,
				'embed_checkout_url'   => isset( $raw['embed_checkout_url'] ) ? $raw['embed_checkout_url'] : null,
				'checkout_mode'        => $external ? 'external' : 'eventkiln',
				// Ready-made links, all in UTC. Empty when this EventKiln doesn't send them yet.
				'calendar_url'         => isset( $raw['calendar_url'] ) ? (string) $raw['calendar_url'] : '',
				'google_calendar_url'  => isset( $raw['google_calendar_url'] ) ? (string) $raw['google_calendar_url'] : '',
				'outlook_calendar_url' => isset( $raw['outlook_calendar_url'] ) ? (string) $raw['outlook_calendar_url'] : '',
				'yahoo_calendar_url'   => isset( $raw['yahoo_calendar_url'] ) ? (string) $raw['yahoo_calendar_url'] : '',
				'map_url'              => isset( $raw['map_url'] ) ? (string) $raw['map_url'] : '',
			),
		);
		$event['post_date']     = str_replace( 'T', ' ', $event['start']->local );
		$event['post_date_gmt'] = str_replace( 'T', ' ', $event['start']->utc );

		$image = isset( $raw['image'] ) && is_array( $raw['image'] ) ? $raw['image'] : array();
		$thumb = ! empty( $image['thumbnail_url'] ) ? $image['thumbnail_url'] : ( ! empty( $image['url'] ) ? $image['url'] : '' );
		if ( '' !== $thumb ) {
			$event['logo_url'] = $thumb;
			$event['logo']     = (object) array(
				'url'          => $thumb,
				'aspect_ratio' => '2',
				'original'     => (object) array(
					'url' => ! empty( $image['url'] ) ? $image['url'] : $thumb,
				),
			);
		} else {
			$event['logo_url'] = '';
			$event['logo']     = '';
		}

		if ( $long ) {
			$event['long_description'] = $description_html;
		}

		$event = array_merge( $event, $this->map_tickets( $raw, $external, $checkout_url ) );
		if ( 'canceled' === $event['status'] ) {
			// Nothing is on sale, and the Canceled button (Pro) reads the message code.
			$event['event_sales_status']  = (object) array(
				'sales_status' => 'unavailable',
				'message_code' => 'event_cancelled',
			);
		}
		if ( in_array( $event['status'], array( 'canceled', 'ended' ), true ) && is_object( $event['ticket_availability'] ) ) {
			$event['ticket_availability']->has_available_tickets = false;
		}

		/**
		 * Filter one EventKiln event after mapping, before it is merged with Eventbrite events.
		 *
		 * @param object $event Event with Eventbrite_Event keys.
		 * @param array  $raw   The event as the EventKiln API returned it.
		 */
		return apply_filters( 'wfea_eventkiln_event', (object) $event, $raw );
	}

	/**
	 * Eventbrite's status for an EventKiln event: canceled, or live, started or ended by the time.
	 * The cached copy can be up to a day old, so the time decides, not the list it came from.
	 */
	private function event_status( $raw, DateTimeImmutable $start, DateTimeImmutable $end ) {
		if ( isset( $raw['status'] ) && 'cancelled' === $raw['status'] ) {
			return 'canceled';
		}
		if ( $start->getTimestamp() > time() ) {
			return 'live';
		}

		return ( $end->getTimestamp() > time() ) ? 'started' : 'ended';
	}

	private function date_object( DateTimeImmutable $date, DateTimeZone $tz ) {
		return (object) array(
			'timezone' => $tz->getName(),
			'local'    => $date->setTimezone( $tz )->format( 'Y-m-d\TH:i:s' ),
			'utc'      => $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * The event's organiser profile, in the shape of an Eventbrite organizer.
	 *
	 * EventKiln falls back to the organisation (id null) when the event has no profile. Older
	 * EventKiln sends no organiser, so use the organisation name. Templates echo the name and
	 * description as HTML, so both are made safe here.
	 *
	 * @param array  $raw          Event from the EventKiln API.
	 * @param string $organisation Organisation name.
	 *
	 * @return object
	 */
	private function map_organiser( $raw, $organisation ) {
		$org = ( isset( $raw['organiser'] ) && is_array( $raw['organiser'] ) && ! empty( $raw['organiser']['name'] ) ) ? $raw['organiser'] : array( 'name' => $organisation );

		if ( ! empty( $org['description_html'] ) && is_string( $org['description_html'] ) ) {
			$description = wp_kses_post( $org['description_html'] );
		} elseif ( ! empty( $org['description'] ) && is_string( $org['description'] ) ) {
			$description = wpautop( esc_html( $org['description'] ) );
		} else {
			$description = '';
		}
		$logo    = ( ! empty( $org['logo_url'] ) && is_string( $org['logo_url'] ) ) ? esc_url_raw( $org['logo_url'] ) : '';
		$website = ( ! empty( $org['website'] ) && is_string( $org['website'] ) ) ? esc_url_raw( $org['website'] ) : '';

		return (object) array(
			'id'               => empty( $org['id'] ) ? '' : self::ID_PREFIX . (int) $org['id'],
			'name'             => esc_html( (string) $org['name'] ),
			'url'              => ( ! empty( $org['url'] ) && is_string( $org['url'] ) ) ? esc_url_raw( $org['url'] ) : '',
			'website'          => $website,
			'logo'             => '' === $logo ? '' : (object) array( 'url' => $logo ),
			'description'      => (object) array(
				'text' => $description,
				'html' => $description,
			),
			'long_description' => (object) array(
				'text' => '',
				'html' => '',
			),
		);
	}

	private function map_venue( $raw ) {
		// Online events get DEE's usual online venue later in the query.
		if ( ! empty( $raw['is_online'] ) || empty( $raw['venue'] ) || ! is_array( $raw['venue'] ) ) {
			return '';
		}
		$v     = $raw['venue'];
		$get   = function ( $key ) use ( $v ) {
			return isset( $v[ $key ] ) && null !== $v[ $key ] ? (string) $v[ $key ] : '';
		};
		$lines = array_values( array_filter( array( $get( 'address_line1' ), $get( 'address_line2' ), $get( 'city' ), $get( 'postcode' ) ) ) );

		return (object) array(
			// Prefixed like event ids, so venue_id="ek-12" picks EventKiln venues only.
			'id'                  => isset( $v['id'] ) && null !== $v['id'] ? self::ID_PREFIX . (int) $v['id'] : '',
			'name'                => $get( 'name' ),
			'accessibility_notes' => $get( 'accessibility_notes' ),
			'travel_notes'        => $get( 'travel_notes' ),
			'latitude'            => $get( 'latitude' ),
			'longitude'           => $get( 'longitude' ),
			'address'             => (object) array(
				'address_1'                            => $get( 'address_line1' ),
				'address_2'                            => $get( 'address_line2' ),
				'city'                                 => $get( 'city' ),
				'region'                               => '',
				'postal_code'                          => $get( 'postcode' ),
				'country'                              => $get( 'country' ),
				'latitude'                             => $get( 'latitude' ),
				'longitude'                            => $get( 'longitude' ),
				'localized_address_display'            => implode( ', ', $lines ),
				'localized_area_display'               => $get( 'city' ),
				'localized_multi_line_address_display' => $lines,
			),
		);
	}

	/**
	 * Prices and availability, in the shapes the Eventbrite helpers read.
	 *
	 * Tickets sold on another site come back as `tickets: null`. They are mapped like Eventbrite's
	 * external ticketing, which shows no price, no availability and never "sold out".
	 */
	private function map_tickets( $raw, $external, $checkout_url ) {
		$no_sales_status = (object) array(
			'sales_status' => 'on_sale',
			'message_code' => '',
		);
		if ( $external || empty( $raw['tickets'] ) || ! is_array( $raw['tickets'] ) ) {
			return array(
				'is_externally_ticketed' => true,
				'external_ticketing'     => (object) array(
					'external_url'         => $checkout_url,
					'is_free'              => false,
					'minimum_ticket_price' => null,
					'maximum_ticket_price' => null,
				),
				'tickets'                => '',
				'ticket_availability'    => '',
				'is_free'                => false,
				'capacity'               => 9999999,
				'event_sales_status'     => $no_sales_status,
			);
		}

		$t        = $raw['tickets'];
		$currency = ! empty( $t['currency'] ) ? $t['currency'] : '';
		$state    = isset( $t['on_sale_state'] ) ? $t['on_sale_state'] : 'on_sale';
		$sold_out = ! empty( $t['sold_out'] ) || 'sold_out' === $state;
		$left     = isset( $t['total_available'] ) ? (int) $t['total_available'] : 0;
		$has_any  = isset( $t['min_price'] ) && null !== $t['min_price'];

		$sales_status = array(
			'on_sale'         => array( 'on_sale', '', 'AVAILABLE' ),
			'not_yet_on_sale' => array( 'not_yet_on_sale', 'tickets_not_yet_on_sale', 'NOT_YET_ON_SALE' ),
			'sales_ended'     => array( 'sales_ended', 'tickets_with_sales_ended', 'SALES_ENDED' ),
			'sold_out'        => array( 'sold_out', 'event_sold_out', 'SOLD_OUT' ),
			// Planned in EventKiln (EK-39): a postponed event stays published with nothing on sale.
			'postponed'       => array( 'postponed', 'event_postponed', 'UNAVAILABLE' ),
		);
		$mapped       = isset( $sales_status[ $state ] ) ? $sales_status[ $state ] : $sales_status['on_sale'];
		if ( $sold_out ) {
			$mapped = $sales_status['sold_out'];
		}

		return array(
			'is_externally_ticketed' => false,
			// One summary "ticket class": DEE's availability helpers only need sold and capacity.
			// Kept when sold out without a price, as the sold-out button reads it.
			'tickets'                => ( $has_any || $sold_out ) ? array(
				(object) array(
					'quantity_sold'  => 0,
					'capacity'       => $sold_out ? 0 : $left,
					'on_sale_status' => $mapped[2],
					'donation'       => false,
				),
			) : array(),
			'ticket_availability'    => (object) array(
				'has_available_tickets' => ! $sold_out && 'on_sale' === $state && $left > 0,
				'is_sold_out'           => $sold_out,
				'minimum_ticket_price'  => $has_any ? $this->price( $t['min_price'], $currency ) : null,
				'maximum_ticket_price'  => ( $has_any && isset( $t['max_price'] ) ) ? $this->price( $t['max_price'], $currency ) : null,
			),
			'is_free'                => $has_any && 0 === (int) $t['min_price'] && isset( $t['max_price'] ) && 0 === (int) $t['max_price'],
			'capacity'               => $sold_out ? 0 : $left,
			'event_sales_status'     => (object) array(
				'sales_status' => $mapped[0],
				'message_code' => $mapped[1],
			),
		);
	}

	private function price( $minor, $currency ) {
		$major = number_format( ( (int) $minor ) / 100, 2, '.', '' );

		return (object) array(
			'currency'    => $currency,
			'value'       => (int) $minor,
			'major_value' => $major,
			'display'     => $major . ' ' . $currency,
		);
	}

	private function map_music_properties( $raw ) {
		$door_time = null;
		if ( ! empty( $raw['doors_open_at'] ) ) {
			try {
				// get_door_time__premium_only() reads this with mysql2date( 'U' ), which treats it as UTC.
				$door_time = ( new DateTimeImmutable( $raw['doors_open_at'] ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				$door_time = null;
			}
		}

		return (object) array(
			'door_time'       => $door_time,
			'age_restriction' => ! empty( $raw['min_age'] ) ? (int) $raw['min_age'] . '+' : null,
			'presented_by'    => ! empty( $raw['presented_by'] ) ? (string) $raw['presented_by'] : null,
		);
	}

	private function map_lineup( $raw ) {
		$performances = array();
		if ( empty( $raw['lineup'] ) || ! is_array( $raw['lineup'] ) ) {
			return $performances;
		}
		foreach ( $raw['lineup'] as $act ) {
			if ( empty( $act['name'] ) ) {
				continue;
			}
			$performances[] = (object) array(
				'performer_role' => ( isset( $act['role'] ) && 'headliner' === $act['role'] ) ? 'headliner' : 'support',
				'artists'        => array(
					(object) array(
						'name' => (string) $act['name'],
						'url'  => isset( $act['url'] ) ? $act['url'] : null,
					),
				),
			);
		}

		return $performances;
	}

	/**
	 * Apply the filters that Eventbrite applies on its own server for Eventbrite events: status
	 * and the start date range.
	 */
	private function matches_query( $event, $query_vars ) {
		$status = isset( $query_vars['status'] ) && '' !== $query_vars['status'] ? $query_vars['status'] : 'live';
		// Lists may spell it "cancelled", as DEE accepts for Eventbrite events.
		$status = str_replace( 'cancelled', 'canceled', $status );
		if ( 'all' !== $status && false === strpos( $status, $event->status ) ) {
			return false;
		}

		$start_day = substr( $event->start->local, 0, 10 );
		if ( ! empty( $query_vars['start_date.range_start'] ) && $start_day < $query_vars['start_date.range_start'] ) {
			return false;
		}
		if ( ! empty( $query_vars['start_date.range_end'] ) && $start_day > $query_vars['start_date.range_end'] ) {
			return false;
		}

		return true;
	}

	private function is_eventbrite_specific( $query_vars ) {
		foreach ( array( 'organization_id', 'collection_id', 'series_id' ) as $var ) {
			if ( ! empty( $query_vars[ $var ] ) ) {
				return true;
			}
		}
		// Blocks and Elementor always pass the default key, so only a different key means
		// "this Eventbrite account only".
		if ( ! empty( $query_vars['token'] ) ) {
			$tokens  = is_array( $query_vars['token'] ) ? $query_vars['token'] : array( $query_vars['token'] );
			$default = self::default_eventbrite_key();
			foreach ( $tokens as $token ) {
				if ( $token !== $default ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * The first Eventbrite key from the settings, or '' when none is set.
	 */
	public static function default_eventbrite_key() {
		$options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
		if ( empty( $options['key'] ) ) {
			return '';
		}
		if ( is_string( $options['key'] ) ) {
			return $options['key'];
		}
		$first = reset( $options['key'] );

		return ( is_array( $first ) && ! empty( $first['key'] ) ) ? (string) $first['key'] : '';
	}

	private function resolve_credentials( $url, $key ) {
		$settings = $this->get_settings();

		return array(
			null === $url ? $settings['url'] : $url,
			null === $key ? $settings['key'] : $key,
		);
	}

	private function same_origin( $a, $b ) {
		$pa = wp_parse_url( $a );
		$pb = wp_parse_url( $b );

		return isset( $pa['scheme'], $pa['host'], $pb['scheme'], $pb['host'] )
		       && $pa['scheme'] === $pb['scheme']
		       && strtolower( $pa['host'] ) === strtolower( $pb['host'] )
		       && ( isset( $pa['port'] ) ? $pa['port'] : null ) === ( isset( $pb['port'] ) ? $pb['port'] : null );
	}

	/**
	 * @return array|WP_Error Decoded JSON body.
	 */
	private function request( $url, $key ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Accept'        => 'application/json',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'wfea-eventkiln-unreachable',
				sprintf(
					/* translators: %s: error from WordPress HTTP API */
					esc_html__( 'EventKiln could not be reached: %s', 'widget-for-eventbrite-api' ),
					$response->get_error_message()
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 === $code && is_array( $body ) ) {
			return $body;
		}

		$detail = ( is_array( $body ) && ! empty( $body['message'] ) ) ? $body['message'] : '';
		switch ( $code ) {
			case 401:
				$message = esc_html__( 'EventKiln rejected the API key. It may have been revoked, or its creator may no longer be an admin.', 'widget-for-eventbrite-api' );
				break;
			case 403:
				$message = esc_html__( 'The EventKiln API key is not allowed to read events (it needs events:read), or the organisation is suspended.', 'widget-for-eventbrite-api' );
				break;
			case 404:
				$message = esc_html__( 'No EventKiln API was found at that address. Check the EventKiln site URL.', 'widget-for-eventbrite-api' );
				break;
			case 429:
				$message = esc_html__( 'EventKiln is rate limiting requests. DEE will try again later.', 'widget-for-eventbrite-api' );
				break;
			default:
				$message = sprintf(
					/* translators: %d: HTTP status code */
					esc_html__( 'EventKiln returned an unexpected response (HTTP %d).', 'widget-for-eventbrite-api' ),
					$code
				);
		}
		if ( '' !== $detail ) {
			$message .= ' ' . esc_html( $detail );
		}

		return new WP_Error( 'wfea-eventkiln-http-' . $code, $message );
	}

	private function record_failure( WP_Error $error ) {
		$status                  = $this->get_status();
		$status['last_error']    = $error->get_error_message();
		$status['last_error_at'] = time();
		update_option( self::STATUS_OPTION, $status, false );
	}

	/**
	 * Tell the admin when the latest fetch failed, and what visitors are seeing instead.
	 */
	public function admin_notice() {
		if ( ! $this->is_configured() || ! Admin::can_display_admin_notice() ) {
			return;
		}
		$status = $this->get_status();
		if ( '' === $status['last_error'] || $status['last_error_at'] < $status['last_success'] ) {
			return;
		}
		$cache = get_option( self::CACHE_OPTION );
		if ( is_array( $cache ) && ! empty( $cache['fetched_at'] ) ) {
			$fallback = sprintf(
				/* translators: %s: date and time */
				esc_html__( 'Visitors are being shown the EventKiln events fetched on %s.', 'widget-for-eventbrite-api' ),
				esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cache['fetched_at'] ) )
			);
		} else {
			$fallback = esc_html__( 'No EventKiln events can be shown until a fetch succeeds.', 'widget-for-eventbrite-api' );
		}
		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s %3$s <a href="%4$s">%5$s</a></p></div>',
			esc_html__( 'Display Eventbrite Events: EventKiln events could not be refreshed.', 'widget-for-eventbrite-api' ),
			esc_html( $status['last_error'] ),
			$fallback, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
			esc_url( admin_url( 'options-general.php?page=widget-for-eventbrite-api-settings' ) ),
			esc_html__( 'Check the EventKiln settings', 'widget-for-eventbrite-api' )
		);
	}
}
