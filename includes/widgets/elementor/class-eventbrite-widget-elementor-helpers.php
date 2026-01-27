<?php

namespace WidgetForEventbriteAPI\Includes\Widgets\Elementor;

use WidgetForEventbriteAPI\Admin\Admin_Settings;
use WidgetForEventbriteAPI\Includes\Eventbrite_Manager;
use WidgetForEventbriteAPI\Includes\Utilities;
defined( 'ABSPATH' ) || die;
/**
 * Eventbrite_Widget widget class.
 *
 * @since x.x
 */
class Eventbrite_Widget_Elementor_Helpers {
    /**
     * @var \Freemius $freemius Object for freemius.
     */
    private $freemius;

    /**
     * @var Utilities $utilities Object for utilities.
     */
    private $utilities;

    private static $event_list = false;

    public function __construct() {
        global $wfea_fs;
        $this->freemius = $wfea_fs;
        $this->utilities = Utilities::get_instance();
    }

    /**
     * Converts array to shortcode attributes.
     *
     * @param array $attributes
     *
     * @return string
     * @since x.x
     *
     * @access public
     *
     */
    public function array_to_shortcode_atts( $attributes ) {
        $atts = '';
        foreach ( $attributes as $key => $value ) {
            if ( in_array( $key, array('style', 'set_style_venue', 'set_style_slider'), true ) ) {
                continue;
            }
            $key = esc_attr( $key );
            $value = esc_attr( $value );
            if ( 'show_end_time' === $key ) {
                $value = ( 'true' === $value ? 'false' : 'true' );
            }
            $atts .= "{$key}=\"{$value}\" ";
        }
        return trim( $atts );
    }

    /**
     * Populates common Datetime control args.
     *
     * @access public
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     */
    public function get_common_datetime_control_args( $args = array() ) {
        $defaults = array(
            'type'           => \Elementor\Controls_Manager::DATE_TIME,
            'picker_options' => array(
                'enableTime' => false,
            ),
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    /**
     * Populates common Number control args.
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     * @access public
     *
     */
    public function get_common_number_control_args( $args = array() ) {
        $defaults = array(
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min'  => 1,
            'max'  => 9999,
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    /**
     * Populates common Select2 control args.
     *
     * @access public
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     */
    public function get_common_select2_control_args( $args = array() ) {
        $defaults = array(
            'type' => \Elementor\Controls_Manager::SELECT2,
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    /**
     * Populates common Switcher control args.
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     * @access public
     *
     */
    public function get_common_switcher_control_args( $args = array() ) {
        $defaults = array(
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'true',
            'default'      => 'true',
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    /**
     * Populates common text control args.
     *
     * @access public
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     */
    public function get_common_text_control_args( $args = array() ) {
        $defaults = array(
            'type' => \Elementor\Controls_Manager::TEXT,
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    /**
     * Populates common textarea control args.
     *
     * @access public
     *
     * @param array $args
     *
     * @return array
     * @since x.x
     *
     */
    public function get_common_textarea_control_args( $args = array() ) {
        $defaults = array(
            'type' => \Elementor\Controls_Manager::TEXTAREA,
        );
        if ( $args ) {
            return wp_parse_args( $args, $defaults );
        }
        return $defaults;
    }

    public function get_organizations_for_key() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        $token = ( !empty( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '' );
        $token = $this->utilities->map_api_index_to_key( $token );
        wp_send_json( $this->get_organizations_list( $token ) );
    }

    public function send_events_for_key() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        $token = ( !empty( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : $this->get_default_api_key() );
        $token = $this->utilities->map_api_index_to_key( $token );
        $args = array(
            'token' => $token,
        );
        if ( !empty( $_POST['organizationID'] ) ) {
            $args['organization_id'] = sanitize_text_field( wp_unslash( $_POST['organizationID'] ) );
        }
        //"{"status_code":400,"error_description":"There are errors with your arguments: organizer_id - Unknown parameter","error":"ARGUMENTS_ERROR"}"
        $events = Eventbrite_Manager::$instance->get_organizations_events( $args, false );
        if ( !is_wp_error( $events ) ) {
            wp_send_json( wp_list_pluck( $events->events ?? array(), 'post_title', 'ID' ) );
        }
        die;
    }

    public function send_organizers_for_key() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        $organizers_options = $this->get_options( 'organizer' );
        wp_send_json( $organizers_options );
        die;
    }

    private function get_options( $option_type ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in calling public methods (send_organizers_for_key, send_venues_options).
        $token = ( !empty( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '' );
        $token = $this->utilities->map_api_index_to_key( $token );
        $args = array();
        if ( !empty( $token ) ) {
            $args['token'] = $token;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in calling public methods.
        if ( !empty( $_POST['organizationID'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in calling public methods.
            $args['organization_id'] = sanitize_text_field( wp_unslash( $_POST['organizationID'] ) );
        }
        $events = $this->get_events_list( $args );
        $events_options = wp_list_pluck( $events ?? array(), $option_type );
        $dropdown_options = array();
        foreach ( $events_options as $event_option ) {
            if ( !in_array( $event_option->id, $events_options ) ) {
                $dropdown_options[$event_option->id] = $event_option->name;
            }
        }
        $dropdown_options = array(
            '' => esc_html__( 'Select', 'widget-for-eventbrite-api' ),
        ) + $dropdown_options;
        return $dropdown_options;
    }

    public function send_venues_options() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        $venue_options = $this->get_options( 'venue' );
        wp_send_json( $venue_options );
        die;
    }

    public function send_api_key_options() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        wp_send_json( $this->get_api_key_options() );
    }

    public function get_organizations_list( $token = '' ) {
        $args = array();
        if ( $token ) {
            $args['token'] = $token;
        }
        $response = Eventbrite_Manager::$instance->request(
            'organizations',
            $args,
            false,
            false
        );
        $organizations_list = wp_list_pluck( $response->organizations ?? array(), 'name', 'id' );
        $organizations_list = array(
            '' => __( 'Select', 'widget-for-eventbrite-api' ),
        ) + $organizations_list;
        return $organizations_list;
    }

    private function get_subcategories_list() {
        $subcats = Eventbrite_Manager::$instance->request(
            'subcategories',
            array(),
            false,
            false
        );
        if ( !is_wp_error( $subcats ) ) {
            return wp_list_pluck( $subcats->subcategories ?? array(), 'name', 'id' );
        }
        return array();
    }

    private function get_events_list( $args ) {
        $organizations = Eventbrite_Manager::$instance->request( 'organizations', $args );
        $events = array();
        foreach ( $organizations->organizations as $org ) {
            $org_events = Eventbrite_Manager::$instance->get_organizations_events( $args, false );
            if ( !is_wp_error( $org_events ) ) {
                $events += $org_events->events;
            }
        }
        return $events;
    }

    public function get_default_api_key() {
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        return ( is_string( $options['key'] ) ? $options['key'] : array_column( $options['key'], 'key' )[0] );
    }

    /**
     * Returns an array containing meta values for widget controls grouped by option type and plan.
     *
     * @return array
     * @since x.x
     *
     * @access public
     *
     */
    public function get_controls_meta() {
        $controls_meta = array(
            'common'    => array(
                'free' => array(array(
                    'name'  => 'wfea_limit',
                    'label' => esc_html( 'Number of Events to Display' ),
                    'type'  => 'number',
                    'args'  => array(
                        'default' => 5,
                    ),
                ), array(
                    'name'  => 'wfea_order_by',
                    'label' => __( 'Event Sort Order', 'widget-for-eventbrite-api' ),
                    'type'  => 'select2',
                    'args'  => array(
                        'options' => array(
                            'asc'            => __( 'Ascending Date', 'widget-for-eventbrite-api' ),
                            'desc'           => __( 'Descending Date', 'widget-for-eventbrite-api' ),
                            'created_asc'    => __( 'Created Date – ascending', 'widget-for-eventbrite-api' ),
                            'created_desc'   => __( 'Created Date – descending', 'widget-for-eventbrite-api' ),
                            'published_asc'  => __( 'EB Published Ascending', 'widget-for-eventbrite-api' ),
                            'published_desc' => __( 'EB Published Descending', 'widget-for-eventbrite-api' ),
                        ),
                    ),
                )),
            ),
            'display'   => array(
                'free' => array(
                    array(
                        'name'  => 'wfea_booknow',
                        'label' => __( 'Book Now button', 'widget-for-eventbrite-api' ),
                    ),
                    array(
                        'name'  => 'wfea_date',
                        'label' => __( 'Event Date/Time in Heading', 'widget-for-eventbrite-api' ),
                        'args'  => array(
                            'condition' => array(
                                'wfea_layout!' => 'short_date',
                            ),
                        ),
                    ),
                    array(
                        'name'  => 'wfea_excerpt',
                        'label' => __( 'Excerpt of Event Summary', 'widget-for-eventbrite-api' ),
                    ),
                    array(
                        'name'  => 'wfea_thumb',
                        'label' => __( 'Image Display', 'widget-for-eventbrite-api' ),
                    ),
                    array(
                        'name'  => 'wfea_thumb_original',
                        'label' => __( 'High Resolution Image', 'widget-for-eventbrite-api' ),
                        'args'  => array(
                            'condition' => array(
                                'wfea_thumb' => array('true'),
                            ),
                        ),
                    ),
                    array(
                        'name'  => 'wfea_readmore',
                        'label' => __( 'Read More Link on Excerpt', 'widget-for-eventbrite-api' ),
                        'args'  => array(
                            'condition' => array(
                                'wfea_layout!' => array('cal', 'cal_list'),
                            ),
                        ),
                    )
                ),
            ),
            'enabling'  => array(
                'free' => array(array(
                    'name'  => 'wfea_newtab',
                    'label' => __( 'Link to EB in New Tab', 'widget-for-eventbrite-api' ),
                    'args'  => array(
                        'default'   => '',
                        'condition' => array(
                            'wfea_layout!' => array('cal', 'cal_list'),
                        ),
                    ),
                )),
            ),
            'filtering' => array(),
            'selection' => array(
                'free' => array(array(
                    'name'  => 'wfea_status',
                    'label' => __( 'Event Status', 'widget-for-eventbrite-api' ),
                    'type'  => 'select2',
                    'args'  => array(
                        'options'  => array(
                            'live'     => __( 'Live', 'widget-for-eventbrite-api' ),
                            'started'  => __( 'Started', 'widget-for-eventbrite-api' ),
                            'ended'    => __( 'Ended', 'widget-for-eventbrite-api' ),
                            'canceled' => __( 'Canceled', 'widget-for-eventbrite-api' ),
                            'draft'    => __( 'Draft', 'widget-for-eventbrite-api' ),
                            'all'      => __( 'All', 'widget-for-eventbrite-api' ),
                        ),
                        'default'  => 'live',
                        'multiple' => 'true',
                    ),
                )),
            ),
            'settings'  => array(
                'free' => array(
                    array(
                        'name'  => 'wfea_booknow_text',
                        'label' => __( 'Book Now Wording', 'widget-for-eventbrite-api' ),
                        'type'  => 'text',
                        'args'  => array(
                            'default' => __( 'Register >>', 'widget-for-eventbrite-api' ),
                        ),
                    ),
                    array(
                        'name'  => 'wfea_css_class',
                        'label' => __( 'Custom CSS Class', 'widget-for-eventbrite-api' ),
                        'type'  => 'text',
                    ),
                    array(
                        'name'  => 'wfea_cssID',
                        'label' => __( 'Custom CSS ID', 'widget-for-eventbrite-api' ),
                        'type'  => 'text',
                    ),
                    array(
                        'name'  => 'wfea_thumb_align',
                        'label' => __( 'Image Alignment', 'widget-for-eventbrite-api' ),
                        'type'  => 'select2',
                        'args'  => array(
                            'options'   => array(
                                'eaw-alignleft'   => __( 'Left', 'widget-for-eventbrite-api' ),
                                'eaw-alignright'  => __( 'Right', 'widget-for-eventbrite-api' ),
                                'eaw-aligncenter' => __( 'Center', 'widget-for-eventbrite-api' ),
                            ),
                            'condition' => array(
                                'wfea_layout' => 'widget',
                            ),
                            'default'   => 'eaw-alignright',
                        ),
                    ),
                    array(
                        'name'  => 'wfea_length',
                        'label' => __( 'Length of Description', 'widget-for-eventbrite-api' ),
                        'type'  => 'number',
                    ),
                    array(
                        'name'  => 'wfea_thumb_width',
                        'label' => __( 'Image Width', 'widget-for-eventbrite-api' ),
                        'type'  => 'number',
                        'args'  => array(
                            'condition' => array(
                                'wfea_layout' => 'widget',
                            ),
                            'default'   => 350,
                            'max'       => '',
                        ),
                    ),
                    array(
                        'name'  => 'wfea_thumb_default',
                        'label' => __( 'Default Image', 'widget-for-eventbrite-api' ),
                        'type'  => 'text',
                    ),
                    array(
                        'name'  => 'wfea_readmore_text',
                        'label' => __( 'Read More Wording', 'widget-for-eventbrite-api' ),
                        'type'  => 'text',
                        'args'  => array(
                            'default'   => __( 'Read More >>', 'widget-for-eventbrite-api' ),
                            'condition' => array(
                                'wfea_layout!' => array('cal', 'cal_list'),
                            ),
                        ),
                    )
                ),
            ),
        );
        return $controls_meta;
    }

    /**
     * @return array
     * @since x.x
     *
     * @access public
     *
     */
    public function get_layout_options() {
        $options = array(
            'widget' => __( 'Widget', 'widget-for-eventbrite-api' ),
            'card'   => __( 'Card', 'widget-for-eventbrite-api' ),
        );
        return $options;
    }

    /**
     * Returns the list of api key options as api_key => organizations pairs.
     *
     * @return array<string,string>
     * @since x.x
     *
     * @access public
     *
     */
    public function get_api_key_options() {
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        $api_keys = $options['key'];
        if ( is_string( $options['key'] ) ) {
            return array();
        }
        $api_keys = $options['key'];
        if ( count( $api_keys ) < 2 ) {
            return array();
        }
        $eventbrite_manager = new Eventbrite_Manager();
        $api_options = array();
        foreach ( $api_keys as $index => $api_key ) {
            $organizations_for_key = $eventbrite_manager->request( 'organizations', array(
                'token' => $api_key['key'],
            ) );
            if ( !is_wp_error( $organizations_for_key ) ) {
                $api_options[$index] = $api_key['label'] . ' (' . implode( ',', array_column( $organizations_for_key->organizations, 'name' ) ) . ')';
            }
        }
        return array(
            '' => __( 'Default', 'widget-for-eventbrite-api' ),
        ) + $api_options;
    }

    /**
     * @param array
     *
     * @return string
     * @since x.x
     *
     * @access public
     *
     */
    public function get_style_string( $params ) {
        $style = '';
        $keys = array_keys( $params );
        $keys = array_map( function ( $key ) {
            return str_replace( 'wfea_', '', $key );
        }, $keys );
        $params = array_combine( $keys, array_values( $params ) );
        if ( isset( $params['set_style_venue'] ) && isset( $params['layout'] ) && 'venue' === $params['layout'] ) {
            $style = $params['set_style_venue'];
        } elseif ( isset( $params['set_style_slider'] ) && isset( $params['layout'] ) && 'slider' === $params['layout'] ) {
            $style = $params['set_style_slider'];
        }
        $add = ( isset( $params['style'] ) ? ' ' . $params['style'] : '' );
        $style .= $add;
        return $style;
    }

    /**
     * Sends shortcode content generated with the new params to the Widget editor window using an ajax request.
     *
     * @return void
     * @since x.x
     *
     * @access public
     *
     */
    public function update_elementor_widget_content() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $this->utilities->sanitize_text_or_array_field() sanitizes the input
        $params = ( isset( $_POST['params'] ) ? $this->utilities->sanitize_text_or_array_field( wp_unslash( $_POST['params'] ) ) : array() );
        // strip params that are conditional
        // we need to look at  the helper function  get_controls_meta()  and get that array  croos check param keys
        // loop through check name  less wfea_  is equal to param
        // if so  then we need to see if there is an arg condition
        //  and then cross check the nots e.g. wfea_layout!   and then text or array  check params layout not one of the content
        $controls_meta = $this->get_controls_meta();
        $args = array();
        foreach ( $controls_meta as $section => $plan ) {
            foreach ( $plan as $elements ) {
                foreach ( $elements as $element ) {
                    $args[ltrim( $element['name'], 'wfea_' )] = $element['args'] ?? array();
                }
            }
        }
        // now check negative conditions
        foreach ( $args as $key => $arg ) {
            if ( isset( $arg['condition'] ) ) {
                // check if negative, last character is !
                foreach ( $arg['condition'] as $check_param => $condition ) {
                    // if $condition is  atring convert to array
                    $condition = ( is_string( $condition ) ? array($condition) : $condition );
                    $negative_condition = substr( $check_param, -1 ) === '!';
                    $check_param = ltrim( rtrim( $check_param, '!' ), 'wfea_' );
                    if ( $negative_condition ) {
                        if ( in_array( $params[$check_param], $condition ) ) {
                            unset($params[$key]);
                        }
                    }
                }
            }
        }
        $shortcode_atts = '';
        foreach ( $params as $key => $param ) {
            if ( $param ) {
                if ( is_array( $param ) ) {
                    $param = implode( ',', $param );
                }
                $shortcode_atts .= $this->array_to_shortcode_atts( array(
                    $key => $param,
                ) ) . ' ';
            }
        }
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        if ( empty( $params['api_key'] ) ) {
            if ( is_array( $options['key'] ) && !empty( $options['key'][0] ) ) {
                $api_key = $this->utilities::map_api_index_to_key( 0, $options['key'] );
            } else {
                $api_key = $options['key'];
            }
        } else {
            $api_key = $this->utilities::map_api_index_to_key( $params['api_key'], $options['key'] );
        }
        $shortcode_atts .= ' api_key=' . $api_key;
        $style_set = $this->get_style_string( $params );
        echo do_shortcode( '[wfea style="' . $style_set . '" ' . $shortcode_atts . ']' );
        wp_die();
    }

    public function validate_date() {
        check_ajax_referer( 'wfea-nonce', 'nonce' );
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( -1 );
        }
        if ( isset( $_POST['wfea_date_value'] ) ) {
            $date = sanitize_text_field( wp_unslash( $_POST['wfea_date_value'] ) );
            if ( strtotime( $date ) === false ) {
                echo false;
            } else {
                echo true;
            }
        } else {
            echo false;
        }
        wp_die();
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @since x.x
     *
     * @access public
     *
     */
    private function merge_subarrays( $array1, $array2 ) {
        $plans = array('free');
        $options = array(
            'common',
            'display',
            'enabling',
            'filtering',
            'selection',
            'settings'
        );
        foreach ( $options as $option ) {
            foreach ( $plans as $plan ) {
                if ( isset( $array1[$option][$plan] ) && isset( $array2[$option][$plan] ) ) {
                    $array1[$option][$plan] = $array1[$option][$plan] + $array2[$option][$plan];
                } elseif ( isset( $array2[$option][$plan] ) ) {
                    $array1[$option][$plan] = $array2[$option][$plan];
                }
            }
        }
        return $array1;
    }

}
