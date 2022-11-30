<?php

/**
 * @package TemplateUtilities
 *
 * @copyright (c) 2019.
 * @author            Alan Fuller (support@fullworksplugins.com)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworksplugins.com
 *
 * This file is part of a Fullworks Plugin.
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with   Fullworks Security.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 */
namespace WidgetForEventbriteAPI\Includes;

use  DateTime ;
use  DateTimeZone ;
use  http\Url ;
class Utilities
{
    protected static  $instance ;
    private  $modal_setup = false ;
    private  $modal_id ;
    private  $modal_elements ;
    private  $plugin_name ;
    /**
     * @var \Freemius $freemius Object for freemius.
     */
    private  $freemius ;
    /**
     * Utilities constructor.
     */
    public function __construct()
    {
        /**
         * @var \Freemius $wfea_fs Object for freemius.
         */
        global  $wfea_fs ;
        $this->plugin_name = 'widget-for-eventbrite-api';
        $this->freemius = $wfea_fs;
    }
    
    /**
     * @return Utilities
     */
    public static function get_instance()
    {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Gets API error messages for logs and system display in human format
     *
     * @return string message to display
     * @internal
     */
    public function get_api_error_string( $error_string )
    {
        do {
            $error_string = $error_string->get_error_message();
        } while (is_wp_error( $error_string ));
        
        if ( is_array( $error_string ) ) {
            $text = json_decode( $error_string['body'] );
            
            if ( null !== $text ) {
                $error_string = $text->error_description;
            } else {
                if ( isset( $error_string['response'] ) ) {
                    $error_string = esc_html__( 'Error code from Eventbrite was' ) . ':' . $error_string['response']['code'] . ' ' . esc_html__( 'Error message' ) . ':' . $error_string['response']['message'];
                }
            }
        
        }
        
        return $error_string;
    }
    
    /**
     * Get the booknow link
     *
     * @param $args template $data->args
     *
     * @return Url  link to add to book now buttons
     * @api
     */
    public function get_booknow_link( $args )
    {
        return $this->format_booknow_link( $args, get_post()->url );
    }
    
    /**
     * Returns a Call to Action object for use in links
     * amount of detail varies between free and premium
     *
     * @param $args template $data->args
     *
     * @returUnsupported declare 'strict_typen object  containing ->text ->availability_class ->class
     * @api
     */
    public function get_cta( $args )
    {
        $cta = array();
        $type = 'available';
        $arg = explode( ',', $args['booknow_text'] );
        $cta['text'] = $arg[0];
        $cta['availability_class'] = 'event__available';
        $cta['class'] = ( isset( $arg[1] ) ? $arg[1] : 'book-now__link' );
        
        if ( 'completed' == get_post()->status || 'ended' == get_post()->status ) {
            $cta['availability_class'] = 'event__past';
            
            if ( $this->get_element( 'past_event_button', $args ) ) {
                $type = 'event_completed';
                $arg = explode( ',', $args['past_event_button'] );
                $cta['text'] = $arg[0];
                $cta['class'] = ( isset( $arg[1] ) ? $arg[1] : 'past_event__link disabled' );
            }
        
        }
        
        
        if ( 'started' == get_post()->status ) {
            $cta['availability_class'] = 'event__started';
            
            if ( $this->get_element( 'started_event_button', $args ) ) {
                $type = 'event_started';
                $arg = explode( ',', $args['started_event_button'] );
                $cta['text'] = $arg[0];
                $cta['class'] = ( isset( $arg[1] ) ? $arg[1] : 'started_event__link' );
            }
        
        }
        
        $cta = apply_filters( 'wfea_cta', $cta, $type );
        $cta['text'] = wp_kses_post( $cta['text'] );
        $cta['class'] = esc_attr( $cta['class'] );
        $cta['availability_class'] = esc_attr( $cta['availability_class'] );
        return (object) $cta;
    }
    
    /**
     * Generic get of element whether in an array or not to avoid templates having to code isset() etc
     *
     * @param $key
     * @param $array
     *
     * @return false|mixed value of specified element, false if not in an array
     * @api
     */
    public function get_element( $item, $source )
    {
        
        if ( is_object( $source ) ) {
            if ( property_exists( $source, $item ) ) {
                return $source->{$item};
            }
            return false;
        }
        
        
        if ( is_array( $source ) ) {
            if ( isset( $source[$item] ) ) {
                return $source[$item];
            }
            return false;
        }
        
        return false;
    }
    
    public function get_event_classes()
    {
        return esc_attr( apply_filters( 'wfea_event_classes', '' ) );
    }
    
    /**
     * Get the URL to an event's public viewing page on eventbrite.com.
     * @return Url URL for https://eventbrite.com event
     *
     * @param $ext  optional extenstion for url e.g. query args
     *
     * @api
     */
    public function get_event_eb_url( $ext = null )
    {
        return get_post()->url . $ext;
    }
    
    /**
     * Get event end date object
     * @filter wfea_eventbrite_event_end<br>
     *  <em>example<em><br>
     * <pre>add_filter( 'wfea_eventbrite_event_end',
     *   function( $enddate ) {
     *     // your code
     *     return $enddate;  // Object
     *   }
     * );</pre>
     *
     * @return  Object  Enddate object
     * @api
     */
    public function get_event_end()
    {
        return apply_filters( 'wfea_eventbrite_event_end', get_post()->end );
    }
    
    /**
     * Get event start date object
     * @filter wfea_eventbrite_event_start<br>
     *  <em>example<em><br>
     * <pre>add_filter( 'wfea_eventbrite_event_start',
     *   function( $startdate ) {
     *     // your code
     *     return $startdate;  // Object
     *   }
     * );</pre>
     *
     * @return  Object StartDate object
     * @api
     */
    public function get_event_start()
    {
        return apply_filters( 'wfea_eventbrite_event_start', get_post()->start );
    }
    
    /**
     * Returns formatted time string including start and end
     * @filter wfea_combined_date_time_date_format<br>
     *  <em>example<em><br>
     * <pre>add_filter( 'wfea_combined_date_time_date_format',
     *   function( $date_format ) {
     *     // your code
     *     return $date_format;  // default get_option( 'date_format' )
     *                           // so changes with site settings
     *   }
     * );</pre>
     * @filter wfea_combined_date_time_time_format<br>
     *  <em>example<em><br>
     * <pre>add_filter( 'wfea_combined_date_time_time_format',
     *   function( $time_format ) {
     *     // your code
     *     return $time_format; // get_option( 'time_format' )
     *                          // so changes with site settings
     *   }
     * );</pre>
     * @filter wfea_event_time<br>
     *  <em>example<em><br>
     * <pre>add_filter( 'wfea_event_time',
     *   function( $event_time, $event_start, $event_end ) {
     *     // your code
     *     return $event_time;
     *   }
     * );</pre>
     *
     *
     * @param $args template $data->args
     *
     * @return String formatted time
     * @api
     */
    function get_event_time( $args = false )
    {
        // Collect our formats from the admin.
        $date_format = apply_filters( 'wfea_combined_date_time_date_format', get_option( 'date_format' ) . ', ' );
        $time_format = apply_filters( 'wfea_combined_date_time_time_format', get_option( 'time_format' ) );
        $combined_format = $date_format . $time_format;
        
        if ( false === $args || isset( $args['show_end_time'] ) && $args['show_end_time'] ) {
            // Determine if the end time needs the date included (in the case of multi-day events).
            $end_time = ( $this->is_multiday_event() ? mysql2date( $combined_format, $this->get_event_end()->local ) : mysql2date( $time_format, $this->get_event_end()->local ) );
        } else {
            $end_time = '';
        }
        
        // Assemble the full event time string.
        $event_time = sprintf(
            _x( '%1$s %3$s %2$s', 'Event date and time. %1$s = start time, %2$s = end time %3$s is a separator', 'eventbrite_api' ),
            esc_html( mysql2date( $combined_format, $this->get_event_start()->local ) ),
            esc_html( $end_time ),
            ( empty($end_time) ? '' : apply_filters( 'wfea_event_time_separator', '-' ) )
        );
        return apply_filters(
            'wfea_event_time',
            $event_time,
            $this->get_event_start(),
            $this->get_event_end()
        );
    }
    
    /**
     * @deprecated
     */
    public function get_fb_share_href( $args )
    {
        return '';
    }
    
    /**
     * @deprecated
     */
    public function get_gcal_href( $args )
    {
        return '';
    }
    
    /**
     * Default templates will use URL which is modified when targeting a single page
     * this is for custom templates where part of the page wants to go direct to Evenbrite and part go to single page
     *
     * @param $args template $data->args
     *
     * @return Url  Eventbrite link unchanged
     * @api
     */
    public function get_original_booknow_link( $args )
    {
        return $this->format_booknow_link( $args, get_post()->eb_url );
    }
    
    /**
     * @deprecated
     */
    public function get_outlook_cal_href( $args )
    {
        return '';
    }
    
    /**
     * Section attributes to be used in templates to take classes / ID/ style overrides
     *
     * @param $data options data
     *
     * @return string
     * @api
     */
    public function get_section_attrs( $data )
    {
        return sprintf(
            '%1$s class="wfea %2$s %3$s %4$s %5$s %6$s"',
            ( !empty($this->get_element( 'cssid', $data->args )) ? 'id="' . esc_attr( $this->get_element( 'cssid', $data->args ) ) . '"' : '' ),
            ( !empty($this->get_element( 'css_class', $data->args )) ? '' . esc_attr( $this->get_element( 'css_class', $data->args ) ) . '' : '' ),
            ( !empty($this->get_element( 'style', $data->args )) ? '' . esc_attr( $this->get_element( 'style', $data->args ) ) . '' : '' ),
            ( !empty($data->template) ? '' . esc_attr( $data->template ) . '' : '' ),
            esc_attr( $data->event->layout_class ),
            esc_attr( $data->event->layout_name )
        );
    }
    
    /**
     * @deprecated
     */
    public function get_twitter_share_href( $args )
    {
        return '';
    }
    
    /**
     * @deprecated
     */
    public function get_yahoo_href( $args )
    {
        return '';
    }
    
    /**
     * check if a multiday event
     * @return bool
     * @internal
     */
    public function is_multiday_event()
    {
        // Set date variables for comparison.
        $start_date = mysql2date( 'Ymd', $this->get_event_start()->local );
        $end_date = mysql2date( 'Ymd', $this->get_event_end()->local );
        // Return true if they're different, false otherwise.
        return $start_date !== $end_date;
    }
    
    /**
     * @deprecated
     */
    public function is_popup_allowed( $args )
    {
        return false;
    }
    
    /**
     * This function is used when creating a plugin for a custom template
     *
     * @link https://github.com/alanef/custom-template-example/blob/master/index.php  for example use
     *
     * @param $dir
     *
     * @api
     * @return void
     */
    public function register_template_dirs( $dir )
    {
        add_filter( 'widget-for-eventbrite-api_template_paths', function ( $file_paths ) use( $dir ) {
            //  put this path as first so it will always override others template parts of the same name
            array_unshift( $file_paths, trailingslashit( $dir ) . 'templates/parts' );
            array_unshift( $file_paths, trailingslashit( $dir ) . 'templates/loops' );
            array_unshift( $file_paths, trailingslashit( $dir ) . 'templates' );
            return $file_paths;
        }, 90 );
    }
    
    /**
     * @param $args template $data->args
     * @param $url
     *
     * @return string
     * @internal
     */
    private function format_booknow_link( $args, $url )
    {
        $ext = ( $this->get_element( 'tickets', $args ) ? '#tickets' : '' );
        $link = 'href="' . esc_url( $url ) . $ext . '"';
        return $link;
    }
    
    /**
     * Formats display price when a price
     * @filter wfea_price_display $price_display,$prices->minimum_ticket_price->major_value,$prices->maximum_ticket_price->major_value,$prices->minimum_ticket_price->currency
     * @return string
     * @internal
     */
    private function format_display_price__premum_only( $prices )
    {
        if ( null === $prices->maximum_ticket_price ) {
            $prices->maximum_ticket_price = $prices->minimum_ticket_price;
        }
        
        if ( $prices->minimum_ticket_price->value == $prices->maximum_ticket_price->value ) {
            $price_display = sprintf( '%1$s%2$s', $this->get_currency_symbol__premium_only( $prices->minimum_ticket_price->currency ), $prices->minimum_ticket_price->major_value );
        } else {
            $price_display = sprintf(
                '%1$s%2$s - %1$s%3$s',
                $this->get_currency_symbol__premium_only( $prices->minimum_ticket_price->currency ),
                $prices->minimum_ticket_price->major_value,
                $prices->maximum_ticket_price->major_value
            );
        }
        
        /**
         * Filters the formatted display price.
         *
         * @param string $price_display formated output price.
         * @param number minimum_ticket_price lowest ticket price.
         * @param number maximum_ticket_price higest ticket price.
         * @param string currency currency code.
         *
         */
        return wp_kses_post( apply_filters(
            'wfea_price_display',
            $price_display,
            $prices->minimum_ticket_price->major_value,
            $prices->maximum_ticket_price->major_value,
            $prices->minimum_ticket_price->currency
        ) );
    }

}