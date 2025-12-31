<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 */
namespace WidgetForEventbriteAPI\Admin;

defined( 'ABSPATH' ) || exit;
class Admin {
    /**
     * The ID of this plugin.
     *
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles( $hook ) {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            $this->version,
            'all'
        );
        if ( $hook != 'settings_page_widget-for-eventbrite-api-settings' ) {
            return;
        }
    }

    public function enqueue_scripts() {
        /** @var \Freemius $wfea_fs Freemius global object. */
        global $wfea_fs;
        $plan = 'Free&layout=widget';
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array('jquery', 'wp-i18n'),
            $this->version,
            false
        );
        // Create a nonce and pass it to the script.
        $nonce = wp_create_nonce( 'wfea-script-nonce' );
        $data = array(
            'ajaxurl'             => admin_url( 'admin-ajax.php' ),
            'StringInvalidAPI'    => esc_html__( 'Not Connected (try again)', 'widget-for-eventbrite-api' ),
            'StringConnected'     => esc_html__( 'Connected! Let\'s go : make sure you have [Saved] ->', 'widget-for-eventbrite-api' ),
            'StringConnecting'    => esc_html__( 'Connecting.....', 'widget-for-eventbrite-api' ),
            'StringGoToSettings'  => esc_html__( 'go to other settings', 'widget-for-eventbrite-api' ),
            'URLSettings'         => admin_url( 'options-general.php?page=widget-for-eventbrite-api-settings' ),
            'StringOrganisations' => esc_html__( 'Your Organizations', 'widget-for-eventbrite-api' ),
            'StringGetStarted'    => esc_html__( 'Start Displaying Events', 'widget-for-eventbrite-api' ),
            'StringInstructions1' => esc_html__( 'Create a page (or post) and use your favorite editor / page builder to add this shortcode [wfea] and you have started.', 'widget-for-eventbrite-api' ),
            'StringInstructions2' => esc_html__( 'Then start adjusting it for your needs, the best way is to visit our dynamic shortcode builder.', 'widget-for-eventbrite-api' ),
            'redirectURL'         => admin_url( 'options-general.php?page=widget-for-eventbrite-api-settings' ),
            'nonce'               => $nonce,
        );
        wp_localize_script( $this->plugin_name . '-admin', 'wfea_data', $data );
    }

    /**
     * Enqueues js and css required for widget preview.
     *
     * @return void
     */
    public function enqueue_widget_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-frontend',
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'frontend/js/frontend.js',
            array('jquery', 'jquery-ui-dialog'),
            $this->version . '0000',
            false
        );
        wp_localize_script( $this->plugin_name . '-frontend', 'data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'spinner'  => includes_url( 'images/spinner-2x.gif' ),
        ) );
        wp_enqueue_script( 'moment' );
        // Use WordPress bundled moment.js
        wp_enqueue_script( 'widget-for-eventbrite-api-moment-tz' );
        wp_enqueue_script( 'widget-for-eventbrite-api-fullcalendar' );
        wp_enqueue_script( 'widget-for-eventbrite-api-locale' );
        wp_enqueue_script( 'widget-for-eventbrite-api-eb-script' );
        wp_enqueue_script(
            $this->plugin_name . '-eb-popup',
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'frontend/js/eb_popup.js',
            array('jquery'),
            $this->version,
            false
        );
        do_action( 'wfea_popup_scripts_enqueued' );
        wp_enqueue_style(
            $this->plugin_name . '-frontend',
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'frontend/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style( 'widget-for-eventbrite-api-fullcalendar-css' );
        wp_enqueue_style( 'widget-for-eventbrite-api-fullcalendar-print-css' );
    }

    public function set_options() {
        // options set up
        if ( false === get_option( 'widget-for-eventbrite-api-settings' ) ) {
            add_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        }
        $version_history = get_option( 'widget-for-eventbrite-api-version' );
        if ( false === $version_history ) {
            add_option( 'widget-for-eventbrite-api-version', array(
                $this->version => array(
                    'time' => time(),
                ),
            ) );
        } else {
            if ( !isset( $version_history[$this->version] ) ) {
                $version_history[$this->version] = array(
                    'time' => time(),
                );
                update_option( 'widget-for-eventbrite-api-version', $version_history );
            }
        }
    }

    public function wfea_dismiss_notice() {
        $user_id = get_current_user_id();
        // Check the nonce.
        check_ajax_referer( 'wfea-script-nonce', 'nonce' );
        if ( !current_user_can( 'manage_options' ) || !isset( $_POST['id'] ) ) {
            return;
        }
        $um = get_user_meta( $user_id, 'wfea_dismissed_notices', true );
        if ( !is_array( $um ) ) {
            $um = array();
        }
        $um[sanitize_text_field( wp_unslash( $_POST['id'] ) )] = true;
        update_user_meta( $user_id, 'wfea_dismissed_notices', $um );
        wp_die();
    }

    public function display_admin_notice() {
        if ( !$this->can_display_admin_notice() ) {
            return;
        }
        $user_id = get_current_user_id();
        $um = get_user_meta( $user_id, 'wfea_dismissed_notices', true );
        if ( !isset( $um['wfea_notice_1'] ) || true !== $um['wfea_notice_1'] ) {
            global $wpdb;
            // gets database variable
            $like = 'max_allowed_packet';
            $results = $wpdb->get_results( $wpdb->prepare( "SHOW VARIABLES LIKE %s", $like ) );
            // Output notice HTML.
            if ( is_array( $results ) && !empty( $results ) ) {
                $size = $results[0]->Value / (1024 * 1024);
                if ( $size < 4 ) {
                    $notice = sprintf(
                        // translators:  placeholder is alink
                        esc_html__( 'Display Eventbrite Plugin says: your database \'max_allowed_packet\' is very low at %1$sMB and may cause issues, consider increasing this, see %2$sthis article%3$s.', 'widget-for-eventbrite-api' ),
                        $size,
                        '<a href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/troubleshooting/database-limits-too-small/" target="_blank">',
                        '</a>'
                    );
                    printf( '<div id="wfea_notice_1" class="wfea_notice notice is-dismissible notice-warning"><p>%s</p></div>', wp_kses_post( $notice ) );
                }
            }
        }
    }

    public static function can_display_admin_notice() {
        // Don't display notices to users that can't do anything about it.
        if ( !function_exists( 'wp_get_current_user' ) ) {
            include ABSPATH . 'wp-includes/pluggable.php';
        }
        if ( !current_user_can( 'install_plugins' ) ) {
            return false;
        }
        // Notices are only displayed on the dashboard, plugins, tools, and settings admin pages.
        $page = get_current_screen()->base;
        $display_on_pages = array(
            'dashboard',
            'plugins',
            'tools',
            'options-general',
            'settings_page_widget-for-eventbrite-api-settings'
        );
        if ( !in_array( $page, $display_on_pages ) ) {
            return false;
        }
        return true;
    }

    public function site_status_tests( $tests ) {
        if ( is_plugin_active( WIDGET_FOR_EVENTBRITE_API_PLUGIN_NAME . '/widget-for-eventbrite-api.php' ) ) {
            $tests['direct']['widget-for-eventbrite-api'] = array(
                'label' => __( 'max allowed packet', 'widget-for-eventbrite-api' ),
                'test'  => array($this, 'database_test'),
            );
        }
        return $tests;
    }

    public function database_test() {
        global $wpdb;
        // gets database variable
        $like = 'max_allowed_packet';
        $results = $wpdb->get_results( $wpdb->prepare( "SHOW VARIABLES LIKE %s", $like ) );
        // Output notice HTML.
        if ( is_array( $results ) && !empty( $results ) ) {
            $size = $results[0]->Value / (1024 * 1024);
        }
        $result = array(
            'label'       => __( 'max allowed packet is large enough ', 'widget-for-eventbrite-api' ),
            'status'      => 'good',
            'badge'       => array(
                'label' => __( 'Display Eventbrite Events', 'widget-for-eventbrite-api' ),
                'color' => 'green',
            ),
            'description' => '<p>' . sprintf( 
                // translators: placeholder is a number  in meg  e.g  24M
                esc_html__( 'max allowed packet can impact whether the database can read large stored elements. Current setting %sM', 'widget-for-eventbrite-api' ),
                $size
             ) . '</p>',
            'actions'     => '',
            'test'        => 'widget-for-eventbrite-api',
        );
        if ( $size < 4 ) {
            $result['status'] = 'recommended';
            $result['label'] = __( 'max allowed packet is not very large', 'widget-for-eventbrite-api' );
            $result['description'] = '<p>' . sprintf( 
                // translators: placeholder is a number  in meg  e.g  24M
                esc_html__( 'max allowed packet is not very big, this can impact all sorts of things, including the number of events the plugin can handle. Current setting %sM', 'widget-for-eventbrite-api' ),
                $size
             ) . '</p>';
            $result['actions'] .= sprintf( '<p><a target="_blank" href="%s">%s</a></p>', esc_url( 'https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/troubleshooting/database-limits-too-small/' ), __( 'Read details here', 'widget-for-eventbrite-api' ) );
            $result['badge']['color'] = 'orange';
        }
        if ( $size < 1.5 ) {
            $result['status'] = 'critical';
            $result['label'] = __( 'max allowed packet is very small', 'widget-for-eventbrite-api' );
            $result['description'] = '<p>' . sprintf( 
                // translators: placeholder is a number  in meg  e.g  24M
                __( 'max allowed packet is very small, the Display Eventbrite plugin may not be able to handle many events, other WP features may also have issues. Current setting %sM', 'widget-for-eventbrite-api' ),
                $size
             ) . '</p>';
            $result['actions'] .= sprintf( '<p><a target="_blank" href="%s">%s</a></p>', esc_url( 'https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/troubleshooting/database-limits-too-small/' ), __( 'Read details here', 'widget-for-eventbrite-api' ) );
            $result['badge']['color'] = 'red';
        }
        return $result;
    }

}
