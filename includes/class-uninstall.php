<?php


/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */

namespace WidgetForEventbriteAPI\Includes;


class Uninstall {

	/**
	 * Uninstall specific code
	 */
	public static function uninstall() {

		$eventbrite_manager = new Eventbrite_Manager();
		$eventbrite_manager->flush_transients( 'eventbrite' );
		delete_option( 'widget-for-eventbrite-api-settings' );
	}

}
