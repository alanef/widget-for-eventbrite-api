<?php

namespace WidgetForEventbriteAPI\Includes;


use WidgetForEventbriteAPI\Includes\Widgets\Elementor\Eventbrite_Widget_Elementor;

// Security Note: Blocks direct access to the plugin PHP files.
defined( 'ABSPATH' ) || die();

/**
 * Class Plugin
 *
 * Main Plugin class
 *
 * @since 1.0.0
 */
class Widgets {

	/**
	 * Instance
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @var Plugin The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Plugin An instance of the class.
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Register the plugin widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager The widgets manager.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function register_widgets( $widgets_manager ) {
		// Register the plugin widget classes.
		$widgets_manager->register( new Eventbrite_Widget_Elementor() );
	}

	/**
	 *  Plugin class constructor
	 *
	 * Register plugin action hooks and filters
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		// Register the widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

	}
}

