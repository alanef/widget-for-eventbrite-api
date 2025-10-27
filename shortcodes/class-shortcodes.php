<?php

/**
 * The public-facing functionality of the plugin.
 *
 *
 */
namespace WidgetForEventbriteAPI\Shortcodes;

use WidgetForEventbriteAPI\FrontEnd\FrontEnd;
class Shortcodes {
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

    private $utilities;

    /**
     * @var \Freemius $freemius Object for freemius.
     */
    private $freemius;

    /**
     * Initialize the class and set its properties.
     *
     */
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

    public function add_shortcode() {
        add_shortcode( 'wfea', array($this, 'build_shortcode') );
    }

    public function build_shortcode( $initial_atts ) {
        // force default for short date to be true modal
        if ( isset( $initial_atts['layout'] ) && 'short_date' == $initial_atts['layout'] ) {
            if ( !isset( $initial_atts['long_description_modal'] ) ) {
                $initial_atts['long_description_modal'] = 'true';
            }
        }
        $debug_message = $this->check_valid_att( $initial_atts );
        $sc_atts = shortcode_atts( \WidgetForEventbriteAPI\FrontEnd\Frontend::default_args( $initial_atts ), $initial_atts, 'wfea' );
        $front_end = new Frontend($this->plugin_name, $this->version, $this->utilities);
        $atts = $front_end->sanitize_atts( $sc_atts );
        return $front_end->do_query_template( $atts ) . $debug_message;
    }

    private function check_valid_att( $atts ) {
        if ( !is_array( $atts ) ) {
            return '';
        }
        $defaults = \WidgetForEventbriteAPI\FrontEnd\Frontend::default_args( $atts );
        foreach ( $atts as $att => $value ) {
            if ( !isset( $defaults[$att] ) ) {
                $message = esc_html__( '[Display Eventbrite Plugin] Selected attribute: [', 'widget-for-eventbrite-api' ) . esc_attr( $att ) . esc_html__( '] is not valid - maybe a typo or maybe not included in your plan, refer to documentation', 'widget-for-eventbrite-api' );
                if ( isset( $atts['debug'] ) && $atts['debug'] ) {
                    trigger_error( esc_html( $message ), E_USER_NOTICE );
                    return '<div class="error">' . $message . '</div>';
                }
            }
        }
        return '';
    }

}
