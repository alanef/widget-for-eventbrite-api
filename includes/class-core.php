<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
namespace WidgetForEventbriteAPI\Includes;

use WidgetForEventbriteAPI\Admin\Admin;
use WidgetForEventbriteAPI\API\Endpoints;
use WidgetForEventbriteAPI\Blocks\Blocks;
use WidgetForEventbriteAPI\FrontEnd\FrontEnd;
use WidgetForEventbriteAPI\Admin\Admin_Settings;
use WidgetForEventbriteAPI\Admin\Admin_Setup_Wizard;
use WidgetForEventbriteAPI\Shortcodes\Shortcodes;
defined( 'ABSPATH' ) || exit;
/**
 * Class Core
 *
 * @package WidgetForEventbriteAPI\Includes
 */
class Core {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * @var \Freemius
     */
    private $freemius;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @param \Freemius $freemius Object for freemius.
     */
    public function __construct( $freemius ) {
        global $wfea_instance_counter;
        $wfea_instance_counter = 0;
        $this->plugin_name = 'widget-for-eventbrite-api';
        $this->version = WIDGET_FOR_EVENTBRITE_API_PLUGIN_VERSION;
        $this->freemius = $freemius;
        $this->utilities = new Utilities();
    }

    public function run() {
        $this->set_locale();
        $this->settings_pages();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // set up options
        $options = get_option( 'widget-for-eventbrite-api-settings' );
        if ( false === $options || !is_array( $options ) || empty( $options ) ) {
            $options = Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' );
            add_option( 'widget-for-eventbrite-api-settings', $options );
        }
        if ( is_array( $options ) && !isset( $options['webhook'] ) ) {
            $options['webhook'] = '';
            update_option( 'widget-for-eventbrite-api-settings', $options );
        }
        // upgrade options
        if ( $this->freemius->is_free_plan() || !empty( $options ) && is_array( $options ) && !isset( $options['background_api'] ) ) {
            $options['background_api'] = 0;
            update_option( 'widget-for-eventbrite-api-settings', $options );
        }
        if ( !empty( $options ) && is_array( $options ) && !isset( $options['key'] ) ) {
            $key = get_option( 'widget-for-eventbrite-api-settings-api' );
            if ( false !== $key ) {
                // Convert old string API key to new array format
                $options['key'] = array(array(
                    'key'   => $key['key'],
                    'label' => 'API Key 1',
                ));
                update_option( 'widget-for-eventbrite-api-settings', $options );
                delete_option( 'widget-for-eventbrite-api-settings-api' );
            }
        }
        // Fix for users who already have broken string format from previous migration
        if ( !empty( $options ) && is_array( $options ) && isset( $options['key'] ) && is_string( $options['key'] ) ) {
            // Convert string API key to new array format
            $api_key_value = $options['key'];
            $options['key'] = array(array(
                'key'   => $api_key_value,
                'label' => 'API Key 1',
            ));
            update_option( 'widget-for-eventbrite-api-settings', $options );
        }
        $this->define_component_hooks();
        $this->define_endpoints_hooks();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Since WordPress 4.6, translations are automatically loaded from WordPress.org.
     * No need to manually call load_plugin_textdomain() for plugins hosted on WordPress.org.
     */
    private function set_locale() {
        // WordPress automatically loads translations from wordpress.org since WP 4.6
        // No action needed for plugins hosted on wordpress.org
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function settings_pages() {
        $settings = new Admin_Settings($this->get_plugin_name(), $this->get_version(), $this->freemius);
        add_action( 'admin_menu', array($settings, 'settings_setup') );
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        if ( empty( $options['key'][0]['key'] ) ) {
            $setup = new Admin_Setup_Wizard();
            add_action( 'admin_menu', array($setup, 'settings_setup') );
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     *  Defining all actions that occur in the admin area.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Admin($this->get_plugin_name(), $this->get_version());
        $widget_helpers = new Widgets\Elementor\Eventbrite_Widget_Elementor_Helpers();
        add_action( 'admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles') );
        add_action( 'admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts') );
        add_action( 'enqueue_block_editor_assets', array($plugin_admin, 'enqueue_styles') );
        add_action( 'enqueue_block_editor_assets', array($plugin_admin, 'enqueue_scripts') );
        add_action( 'admin_notices', array($plugin_admin, 'display_admin_notice') );
        add_action( 'admin_init', array($plugin_admin, 'set_options') );
        add_action( 'wp_ajax_wfea_dismiss_notice', array($plugin_admin, 'wfea_dismiss_notice') );
        add_filter( 'site_status_tests', array($plugin_admin, 'site_status_tests') );
        add_action( 'wp_ajax_update_widget_content', array($widget_helpers, 'update_elementor_widget_content') );
        add_action( 'wp_ajax_fetch_organizations_for_key', array($widget_helpers, 'get_organizations_for_key') );
        add_action( 'wp_ajax_fetch_events_for_key', array($widget_helpers, 'send_events_for_key') );
        add_action( 'wp_ajax_fetch_organizers_for_key', array($widget_helpers, 'send_organizers_for_key') );
        add_action( 'wp_ajax_fetch_venues_options', array($widget_helpers, 'send_venues_options') );
        add_action( 'wp_ajax_fetch_api_key_options', array($widget_helpers, 'send_api_key_options') );
        add_action( 'wp_ajax_validate_date', array($widget_helpers, 'validate_date') );
        add_action( 'init', function () {
            new \WidgetForEventbriteAPI\Includes\Widgets();
        } );
        add_action( 'elementor/widgets/register', array($plugin_admin, 'enqueue_widget_scripts') );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        // allow iframe in post content as Eventbright uses it for videos
        add_filter(
            'wp_kses_allowed_html',
            function ( $tags, $context ) {
                if ( 'post' === $context ) {
                    $tags['iframe'] = array(
                        'src'             => true,
                        'height'          => true,
                        'width'           => true,
                        'frameborder'     => true,
                        'allowfullscreen' => true,
                    );
                }
                return $tags;
            },
            10,
            2
        );
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        $plugin_public = new FrontEnd($this->get_plugin_name(), $this->get_version(), $this->utilities);
        add_action( 'init', array($plugin_public, 'register_image_size') );
        add_filter(
            'jetpack_photon_skip_for_url',
            array($plugin_public, 'jetpack_photon_skip_for_url'),
            9,
            2
        );
        add_filter(
            'wfea_the_content',
            array($plugin_public, 'wfea_the_content'),
            10,
            2
        );
        add_action( 'wp_enqueue_scripts', array($plugin_public, 'enqueue_styles') );
        add_action( 'wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts') );
        // Prevent JS optimizers from deferring calendar scripts (runs late to override optimizer filters).
        add_filter(
            'script_loader_tag',
            array($plugin_public, 'prevent_defer_on_calendar_scripts'),
            999,
            3
        );
        add_action( 'wp_head', array($plugin_public, 'wfea_generate_meta_for_social_media'), -1 );
    }

    public function define_component_hooks() {
        add_action( 'widgets_init', array($this, 'register_widgets') );
        add_action( 'init', array($this, 'register_blocks') );
        add_action( 'init', array($this, 'register_shortcodes') );
    }

    public function define_endpoints_hooks() {
    }

    public function register_widgets() {
        register_widget( 'WidgetForEventbriteAPI\\Widgets\\Widget' );
    }

    public function register_blocks() {
        global $wp_version;
        if ( version_compare( $wp_version, '5.8', '<' ) ) {
            return;
        }
        $blocks = new Blocks(
            $this->plugin_name,
            $this->version,
            $this->utilities,
            $this->freemius
        );
        add_action( 'enqueue_block_assets', array($blocks, 'enqueue_block_assets') );
        $blocks->register_display_eventbrite();
        $blocks->localize_freemius_data();
    }

    public function register_shortcodes() {
        $shortcodes = new Shortcodes(
            $this->plugin_name,
            $this->version,
            $this->utilities,
            $this->freemius
        );
        add_filter( 'aioseo_conflicting_shortcodes', function ( $conflicting_shortcodes ) {
            $conflicting_shortcodes['Display Eventbrite Events'] = 'wfea';
            return $conflicting_shortcodes;
        } );
        add_shortcode( 'wfea', array($shortcodes, 'build_shortcode') );
    }

}
