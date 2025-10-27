<?php

namespace WidgetForEventbriteAPI\Includes\Widgets\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WidgetForEventbriteAPI\Admin\Admin_Settings;
use WidgetForEventbriteAPI\Includes\Utilities;
use WidgetForEventbriteAPI\Includes\Widgets\Elementor\Eventbrite_Widget_Elementor_Helpers;
defined( 'ABSPATH' ) || die;
/**
 * Eventbrite_Widget widget class.
 *
 * @since x.x
 */
class Eventbrite_Widget_Elementor extends Widget_Base {
    /**
     * @var \Freemius $freemius Object for freemius.
     */
    private $freemius;

    private $plugin_name;

    /**
     * Class constructor.
     *
     * @param array $data Widget data.
     * @param array $args Widget arguments.
     */
    public function __construct( $data = array(), $args = array() ) {
        parent::__construct( $data, $args );
        $this->plugin_name = 'widget-for-eventbrite-api';
        global $wfea_fs;
        $this->freemius = $wfea_fs;
        wp_enqueue_script( $this->plugin_name );
        wp_enqueue_style(
            $this->plugin_name . '-elementor-css',
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'frontend/css/elementor-widget.css',
            array(),
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_VERSION
        );
        $elementor_dependency = array();
        wp_enqueue_script(
            $this->plugin_name . '-elementor-js',
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'frontend/js/elementor-widget.js',
            $elementor_dependency,
            WIDGET_FOR_EVENTBRITE_API_PLUGIN_VERSION
        );
        wp_localize_script( $this->plugin_name . '-elementor-js', 'customAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wfea-nonce' ),
        ) );
    }

    /**
     * @param array $attributes
     *
     * @return string
     * @since x.x
     *
     * @access public
     *
     */
    public function array_to_shortcode_atts( $attributes ) {
        $attributes = $this->fill_defaults( $attributes );
        $atts = '';
        foreach ( $attributes as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = implode( ',', $value );
            }
            $key = esc_attr( $key );
            $key = str_replace( 'wfea_', '', $key );
            $value = esc_attr( $value );
            if ( 'show_end_time' === $key ) {
                $value = ( $value === 'true' ? 'false' : 'true' );
            }
            if ( 'api_key' === $key ) {
                $value = Utilities::map_api_index_to_key( $value );
            }
            if ( "style" != $key ) {
                // dont add style as it is added later
                $atts .= "{$key}=\"{$value}\" ";
            }
        }
        return trim( $atts );
    }

    /**
     * @param array $attributes
     *
     * @return array
     * @since x.x
     *
     * @access public
     *
     */
    public function fill_defaults( $attributes ) {
        $defaults = array(
            'wfea_limit' => 5,
        );
        foreach ( $attributes as $key => $value ) {
            if ( '' === $value ) {
                if ( 'wfea_layout' === $key || empty( $defaults[$key] ) ) {
                    unset($attributes[$key]);
                    continue;
                }
                $attributes[$key] = $defaults[$key];
            }
        }
        return $attributes;
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @return array Widget categories.
     * @since x.x
     *
     * @access public
     *
     */
    public function get_categories() {
        return array('general');
    }

    /**
     * Retrieve custom url that is linked to the "Need help?" button.
     *
     * @return string
     * @since x.x
     */
    public function get_custom_help_url() {
        return 'https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/usage/using-the-elementor-widget/';
    }

    /**
     * Retrieve the widget icon.
     *
     * @return string Widget icon.
     * @since x.x
     *
     * @access public
     *
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Retrieve the widget name.
     *
     * @return string Widget name.
     * @since x.x
     *
     * @access public
     *
     */
    public function get_name() {
        return 'eventbrite-widget';
    }

    /**
     * Retrieve the widget title.
     *
     * @return string Widget title.
     * @since x.x
     *
     * @access public
     *
     */
    public function get_title() {
        return __( 'Display Eventbrite', 'widget-for-eventbrite-api' );
    }

    public function get_upgrade_link( $message ) {
        $upgrade_html = '';
        $upgrade_html .= '<a target="_blank" href="' . esc_url( $this->freemius->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade ', 'widget-for-eventbrite-api' ) . ' <span class="dashicons dashicons-external"></span></a> ' . $message;
        $upgrade_html .= esc_html__( 'See all options in our ', 'widget-for-eventbrite-api' ) . '<a target="_blank" href="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo/?mtm_campaign=elementor&mtm_kwd=side%20link">' . esc_html__( 'demo page (opens in new tab)', 'widget-for-eventbrite-api' ) . '</a> <span class="dashicons dashicons-external"></span>';
        return $upgrade_html;
    }

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since x.x
     *
     * @access protected
     */
    protected function register_controls() {
        $widget_helpers = new Eventbrite_Widget_Elementor_Helpers();
        // Start General section
        $this->start_controls_section( 'general', array(
            'label' => __( 'General', 'widget-for-eventbrite-api' ),
        ) );
        $layout_options = array(
            'label'   => esc_html__( 'Theme', 'widget-for-eventbrite-api' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $widget_helpers->get_layout_options(),
        );
        $layout_options['description'] = $this->get_upgrade_link( esc_html__( ' to see more layouts. ', 'widget-for-eventbrite-api' ) );
        $this->add_control( 'wfea_layout', $layout_options );
        $this->add_control( 'wfea_widgetwrap', array(
            'label'        => esc_html__( 'Wrap Div', 'widget-for-eventbrite-api' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'condition'    => array(
                'wfea_layout' => 'widget',
            ),
            'default'      => 'true',
            'return_value' => 'true',
        ) );
        $this->end_controls_section();
        // End General section
        // Start Common section
        $this->start_controls_section( 'common', array(
            'label' => __( 'Common', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_widget_controls( 'common', 'free' );
        $this->end_controls_section();
        // End Common section
        // Start Dispay section
        $this->start_controls_section( 'display', array(
            'label' => __( 'Display', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_control( 'display_upgrade_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => $this->get_upgrade_link( esc_html__( ' for more options.', 'widget-for-eventbrite-api' ) ),
        ) );
        $this->add_widget_controls( 'display', 'free' );
        $this->end_controls_section();
        // End Display section
        // Start Enabling section
        $this->start_controls_section( 'enabling', array(
            'label' => __( 'Enabling', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_control( 'enabling_upgrade_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => $this->get_upgrade_link( esc_html__( ' for more options.', 'widget-for-eventbrite-api' ) ),
        ) );
        $this->add_widget_controls( 'enabling', 'free' );
        $this->end_controls_section();
        // End Enabling section
        // Start Filtering section
        $this->start_controls_section( 'filtering', array(
            'label' => __( 'Filtering', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_control( 'filtering_upgrade_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => $this->get_upgrade_link( esc_html__( ' for more options.', 'widget-for-eventbrite-api' ) ),
        ) );
        $this->end_controls_section();
        // End Filtering section
        // Start Selection section
        $this->start_controls_section( 'selection', array(
            'label' => __( 'Selection', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_control( 'selection_upgrade_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => $this->get_upgrade_link( esc_html__( ' for more options.', 'widget-for-eventbrite-api' ) ),
        ) );
        $this->add_widget_controls( 'selection', 'free' );
        $this->end_controls_section();
        // End Selection section
        // Start Settings section
        $this->start_controls_section( 'settings', array(
            'label' => __( 'Settings', 'widget-for-eventbrite-api' ),
        ) );
        $this->add_control( 'settings_upgrade_note', array(
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => $this->get_upgrade_link( esc_html__( ' for more options.', 'widget-for-eventbrite-api' ) ),
        ) );
        $this->add_widget_controls( 'settings', 'free' );
        $this->end_controls_section();
        // End Settings section
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
    }

    /**
     * Adds controls to the widget.
     *
     * @param string $option_type
     * @param string $plan
     *
     * @since x.x
     *
     */
    private function add_widget_controls( $option_type, $plan ) {
        $widget_helpers = new Eventbrite_Widget_Elementor_Helpers();
        $controls_meta = $widget_helpers->get_controls_meta();
        if ( isset( $controls_meta[$option_type][$plan] ) ) {
            $single_control_meta = $controls_meta[$option_type][$plan];
            foreach ( $single_control_meta as $single_control_param ) {
                if ( !isset( $single_control_param['type'] ) ) {
                    $args = $this->prepare_args_for_widget_control( $single_control_param );
                } else {
                    $args = $this->prepare_args_for_widget_control( $single_control_param, $single_control_param['type'] );
                }
                $this->add_control( $single_control_param['name'], $args );
            }
        }
    }

    /**
     * Prepares arguments used to add a new control.
     *
     * @param array $single_control_param
     * @param string $type
     *
     * @since x.x
     *
     * @access private
     *
     */
    private function prepare_args_for_widget_control( $single_control_param, $type = 'switcher' ) {
        $widget_helpers = new Eventbrite_Widget_Elementor_Helpers();
        if ( isset( $single_control_param['args'] ) ) {
            $args = call_user_func( array($widget_helpers, 'get_common_' . $type . '_control_args'), $single_control_param['args'] );
        } else {
            $args = call_user_func( array($widget_helpers, 'get_common_' . $type . '_control_args') );
        }
        $args['label'] = esc_html( $single_control_param['label'] );
        return $args;
    }

    /**
     * Returns true if Widget_Base::render should be called.
     *
     * We don't want to call this function in the editor since we are rendering there with ajax.
     *
     * @return bool
     */
    private function should_render_widget() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- elementor controlled
        if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'elementor' ) {
            return false;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- elementor controlled
        if ( isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'elementor_ajax' ) {
            return false;
        }
        return true;
    }

    /**
     * Render the widget output on the frontend.
     *
     * @since x.x
     *
     * @access protected
     */
    protected function render() {
        if ( !$this->should_render_widget() ) {
            return;
        }
        $widget_helpers = new Eventbrite_Widget_Elementor_Helpers();
        $settings = $this->get_settings_for_display();
        if ( empty( $settings['wfea_api_key'] ) ) {
            $settings['wfea_api_key'] = $widget_helpers->get_default_api_key();
        }
        if ( empty( $settings['wfea_display_private'] ) ) {
            $settings['wfea_display_private'] = 'false';
        }
        if ( empty( $settings['wfea_thumb'] ) ) {
            $settings['wfea_thumb'] = 'false';
        }
        $settings = array_filter( $settings, function ( $key ) {
            return strpos( $key, 'wfea_' ) !== false;
        }, ARRAY_FILTER_USE_KEY );
        $settings = array_filter( $settings );
        $shortcode_atts = $this->array_to_shortcode_atts( $settings );
        ob_start();
        ?>
        <div class="wfea-preview wfea-elementor-widget">
			<?php 
        $style = $widget_helpers->get_style_string( $settings );
        $shortcode_string = '[wfea css_class="wfea-blocks" style="' . $widget_helpers->get_style_string( $settings ) . '" ' . $shortcode_atts . ']';
        echo do_shortcode( $shortcode_string );
        ?>
        </div>
		<?php 
        echo wp_kses( ob_get_clean(), Utilities::get_allowed_html() );
    }

    /**
     * Render the widget output in the editor.
     *
     * Written as a Backbone JavaScript template and used to generate the live preview.
     *
     * @since x.x
     *
     * @access protected
     */
    protected function content_template() {
        ?>
        <div class="wfea-preview wfea-elementor-widget"></div>
		<?php 
    }

}
