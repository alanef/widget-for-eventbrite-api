<?php

namespace WidgetForEventbriteAPI\Blocks;

use WidgetForEventbriteAPI\FrontEnd\FrontEnd;
use WidgetForEventbriteAPI\Includes\Utilities;
use WidgetForEventbriteAPI\Includes\Widgets\Elementor\Eventbrite_Widget_Elementor_Helpers;
defined( 'ABSPATH' ) || exit;
class Blocks {
    private $plugin_name;

    private $version;

    private $utilities;

    /*
     * @param \Freemius $freemius Object for freemius.
     */
    private $freemius;

    private $widget_helpers;

    public function __construct(
        $plugin_name,
        $version,
        $utilities,
        $freemius
    ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->utilities = $utilities;
        $this->freemius = $freemius;
    }

    private static $localized_data = false;

    public function modify_block_attributes( $metadata ) {
        if ( 'widget-for-eventbrite-api/display-eventbrite-events' !== $metadata['name'] ) {
            return $metadata;
        }
        $metadata['attributes']['readmore_text']['default'] = __( 'Read More »', 'widget-for-eventbrite-api' );
        $metadata['attributes']['booknow_text']['default'] = __( 'Register »', 'widget-for-eventbrite-api' );
        return $metadata;
    }

    public function add_rest_method( $endpoints ) {
        if ( is_wp_version_compatible( '5.5' ) ) {
            return $endpoints;
        }
        foreach ( $endpoints as $route => $handler ) {
            if ( isset( $endpoints[$route][0] ) ) {
                $endpoints[$route][0]['methods'] = [WP_REST_Server::READABLE, WP_REST_Server::CREATABLE];
            }
        }
        return $endpoints;
    }

    public function register_display_eventbrite() {
        add_filter( 'block_type_metadata', array($this, 'modify_block_attributes') );
        add_filter( 'rest_endpoints', array($this, 'add_rest_method') );
        if ( $this->freemius->is_free_plan() ) {
            // strip out extra script to remove not found errors
            add_filter(
                'register_block_type_args',
                function ( $args, $name ) {
                    if ( 'widget-for-eventbrite-api/display-' !== $name ) {
                        return $args;
                    }
                    $args['script'] = null;
                    return $args;
                },
                10,
                2
            );
        }
        register_block_type( WIDGET_FOR_EVENTBRITE_API_PLUGIN_DIR . 'build/blocks/display-eventbrite-events', array(
            'render_callback' => array($this, 'render_block'),
        ) );
    }

    public function render_block( $atts, $content, $block ) {
        if ( !empty( $atts['eb_id'] ) ) {
            unset($atts['organization_id']);
        }
        if ( $atts['api_key'] === '' ) {
            $atts['api_key'] = $this->widget_helpers->get_default_api_key();
        } else {
            $options = get_option( 'widget-for-eventbrite-api-settings' );
            $atts['api_key'] = Utilities::map_api_index_to_key( $atts['api_key'], $options['key'] );
        }
        $front_end = new Frontend($this->plugin_name, $this->version, $this->utilities);
        $atts = $this->remove_non_eb_keys( $atts );
        $output = $front_end->do_query_template( $atts, $content, $block );
        return '<div ' . get_block_wrapper_attributes() . '>' . $output . '</div>';
    }

    public function remove_non_eb_keys( $atts ) {
        unset(
            $atts['apiKeyOptions'],
            $atts['organizationOptions'],
            $atts['eventOptions'],
            $atts['organizerOptions'],
            $atts['venueOptions']
        );
        /* remove non plan keys */
        $defaults = FrontEnd::default_args( $atts );
        foreach ( $atts as $key => $value ) {
            if ( !isset( $defaults[$key] ) ) {
                unset($atts[$key]);
            }
        }
        return $atts;
    }

    public function localize_freemius_data() {
        $data = array(
            'current_plan'         => $this->freemius->get_plan_name(),
            'can_use_premium_code' => $this->freemius->can_use_premium_code(),
            'is_plan_silver'       => $this->freemius->is_plan( 'silver' ),
            'is_plan_gold'         => Utilities::is_legacy_gold_plan(),
            'is_plan_platinum'     => $this->freemius->is_plan( 'platinum' ),
        );
        wp_localize_script( 'widget-for-eventbrite-api-display-eventbrite-events-script', 'wfea_freemius', $data );
        $this->widget_helpers = new Eventbrite_Widget_Elementor_Helpers();
        $controls_meta = $this->widget_helpers->get_controls_meta();
        $controls_meta['ajaxurl'] = admin_url( 'admin-ajax.php' );
        $controls_meta['nonce'] = wp_create_nonce( 'wfea-nonce' );
        wp_localize_script( 'widget-for-eventbrite-api-display-eventbrite-events-script', 'wfea_controls_meta', $controls_meta );
    }

    public function enqueue_block_assets() {
    }

}
