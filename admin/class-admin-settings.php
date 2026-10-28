<?php

/**
 * Created
 * User: alan
 * Date: 04/04/18
 * Time: 13:45
 */
namespace WidgetForEventbriteAPI\Admin;

use WidgetForEventbriteAPI\Includes\Eventbrite_Manager;
use WidgetForEventbriteAPI\Includes\EventKiln_Source;
use WidgetForEventbriteAPI\Includes\Utilities;
defined( 'ABSPATH' ) || exit;
class Admin_Settings extends Admin_Pages {
    protected $settings_page;

    protected $settings_page_id = 'settings_page_widget-for-eventbrite-api-settings';

    protected $option_group = 'widget-for-eventbrite-api';

    protected $settings_title;

    /**
     * Settings constructor.
     *
     * @param string $plugin_name
     * @param string $version plugin version.
     * @param \Freemius $freemius Freemius SDK.
     */
    public function __construct( $plugin_name, $version, $freemius ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->freemius = $freemius;
        parent::__construct();
    }

    public static function option_defaults( $option ) {
        switch ( $option ) {
            case 'widget-for-eventbrite-api-settings':
                return array(
                    'cache_clear'     => 0,
                    'cache_duration'  => 86400,
                    'plugin-css'      => 1,
                    'background_api'  => 0,
                    'consent_gate'    => 0,
                    'key'             => array(array(
                        'key'   => '',
                        'label' => 'API Key 1',
                    )),
                    'webhook'         => '',
                    'webhook_dir'     => '',
                    'webhook_version' => '',
                    'account_type'    => 'standard',
                    'eventkiln_url'   => '',
                    'eventkiln_key'   => '',
                );
            default:
                return false;
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'info',
            /* Meta Box ID */
            esc_html__( 'Information', 'widget-for-eventbrite-api' ),
            /* Title */
            array($this, 'meta_box_1'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        add_meta_box(
            'api',
            /* Meta Box ID */
            esc_html__( 'Eventbrite API Key', 'widget-for-eventbrite-api' ),
            /* Title */
            array($this, 'meta_box_4'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        add_meta_box(
            'eventkiln',
            /* Meta Box ID */
            '<span><img class="wfea-ek-logo" src="' . esc_url( WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL . 'admin/images/eventkiln-logo.png' ) . '" width="160" height="27" alt="EventKiln">' . esc_html__( '(optional)', 'widget-for-eventbrite-api' ) . '</span>',
            /* Title */
            array($this, 'meta_box_eventkiln'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        add_meta_box(
            'cache',
            /* Meta Box ID */
            esc_html__( 'Manage Cache', 'widget-for-eventbrite-api' ),
            /* Title */
            array($this, 'meta_box_2'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        $meta_box_webhooks = 'meta_box_webhooks';
        add_meta_box(
            'webhooks',
            /* Meta Box ID */
            esc_html__( 'Webhook Notifications ( optional )', 'widget-for-eventbrite-api' ),
            /* Title */
            array($this, $meta_box_webhooks),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        add_meta_box(
            'Styles',
            /* Meta Box ID */
            esc_html__( 'Style Settings', 'widget-for-eventbrite-api' ),
            /* Title */
            array($this, 'meta_box_5'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default'
        );
        if ( !is_plugin_active( 'elementor/elementor.php' ) ) {
            add_meta_box(
                'shortcode',
                /* Meta Box ID */
                esc_html__( 'Shortcode Syntax', 'widget-for-eventbrite-api' ),
                /* Title */
                array($this, 'meta_box_3'),
                /* Function Callback */
                $this->settings_page_id,
                /* Screen: Our Settings Page */
                'normal',
                /* Context */
                'default'
            );
        }
        if ( !is_plugin_active( 'elementor/elementor.php' ) ) {
            if ( $this->is_block_editor_active() ) {
                add_meta_box(
                    'blockdiv',
                    /* Meta Box ID */
                    esc_html__( 'How to Use', 'widget-for-eventbrite-api' ),
                    /* Title */
                    array($this, 'demo_block_box'),
                    /* Function Callback */
                    $this->settings_page_id,
                    /* Screen: Our Settings Page */
                    'side',
                    /* Context */
                    'high'
                );
            }
            $box = esc_html__( 'Shortcode Builder', 'widget-for-eventbrite-api' );
        } else {
            $box = esc_html__( 'Elementor Usage', 'widget-for-eventbrite-api' );
        }
        add_meta_box(
            'demodiv',
            /* Meta Box ID */
            $box,
            /* Title */
            array($this, 'demo_meta_box'),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'side',
            /* Context */
            'high'
        );
    }

    private function is_block_editor_active() {
        // Gutenberg plugin is installed and activated.
        $gutenberg = !(false === has_filter( 'replace_editor', 'gutenberg_init' ));
        // Block editor since 5.0.
        $block_editor = version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' );
        if ( !$gutenberg && !$block_editor ) {
            return false;
        }
        if ( has_filter( 'use_block_editor_for_post_type' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook
            return apply_filters( 'use_block_editor_for_post_type', true, null );
        }
        return true;
    }

    /**
     * @param $settings
     *
     * @return array
     */
    public function clear_cache( $settings ) {
        $eventbrite_manager = new Eventbrite_Manager();
        $eventbrite_manager->flush_transients( 'eventbrite' );
        $settings['cache_clear'] = 0;
        add_settings_error(
            'wfea-cache',
            esc_attr( 'cache_cleared' ),
            esc_html__( 'The Plugin Cache has been reset - If you have a CDN or Host Cache or Caching Plugin you may need to clear those caches manually', 'widget-for-eventbrite-api' ),
            'updated'
        );
        return array($eventbrite_manager, $settings);
    }

    public function delete_options() {
        update_option( 'widget-for-eventbrite-api-settings', self::option_defaults( 'widget-for-eventbrite-api-settings' ) );
    }

    public function demo_meta_box() {
        ?>
        <div id="demopost" class="submitbox">

            <div id="major-demo-actions">

                <div id="demo-action">
					<?php 
        $this->display_demo_button();
        ?>
                </div>

            </div>
        </div>

		<?php 
    }

    public function demo_block_box() {
        ?>
        <div id="demopost" class="submitbox">

            <div id="major-demo-actions">

                <div id="demo-action">
                    <p><?php 
        esc_html_e( 'In the post or page editor is a block for the Eventbrite layouts, add the block and adjust the options to see the results.', 'widget-for-eventbrite-api' );
        ?></p>
                    <p><?php 
        esc_html_e( 'You can also use a Shortcode if preferred.', 'widget-for-eventbrite-api' );
        ?></p>
                </div>

            </div>
        </div>

		<?php 
    }

    public function meta_box_1() {
        $infomsg = '<p>' . sprintf( 
            // translators: placeholder leave as links
            __( '<p>Welcome. To use this plugin add the widget to your website</p><p>For more detailed setup instructions visit <a target= "_blank" href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/installation-display-eventbrite-events-in-wordpress/" >the knowledgebase.</a></p>
            <p>Support for the <strong>free</strong> version is provided <a class="button-secondary" href="https://wordpress.org/support/plugin/widget-for-eventbrite-api" target="_blank">here on WordPress.org.</a></p>
			<p>Get a FREE trial of the Pro version - <a href="%1$s">click here</a> 
			<h2>Pro Version Benefits</h2>
			<ul >
			    <li>14 day free trial</li>
			    <li>Keep visitors on your site, with integrated checkout popup</li>
			    <li>Show the full event details in a popup too</li>
				<li>Let your users see your events on full pages and post</li>
				<li>Show your events off on many different layouts</li>
				<li>Need a calendar layout? We have one of those</li>
				<li>Like a grid layout? We have one of those too</li>
				<li>Full page? of course - why not browse the <a href="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo/" target="_blank">demo shortcode builder page</a> to see powerful options</li>
				<li>Want to show off invite only events? the pro version can</li>
				<li>Do you have lots of events and would like to filter them down? The shortcode has sophisticated filters</li>
			</ul>
			</p>
			', 'widget-for-eventbrite-api' ),
            $this->freemius->get_trial_url()
         );
        echo wp_kses_post( $infomsg );
    }

    public function meta_box_2() {
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        if ( !isset( $options['cache_clear'] ) ) {
            $options['cache_clear'] = 0;
        }
        if ( !isset( $options['cache_duration'] ) ) {
            $options['cache_duration'] = 86400;
        }
        $units = array(
            array(604800, esc_html__( '1 Week', 'widget-for-eventbrite-api' )),
            array(172800, esc_html__( '2 Days', 'widget-for-eventbrite-api' )),
            array(86400, esc_html__( '1 Day - recommended', 'widget-for-eventbrite-api' )),
            array(43200, esc_html__( '12 hours', 'widget-for-eventbrite-api' )),
            array(14400, esc_html__( '4 hours', 'widget-for-eventbrite-api' )),
            array(3600, esc_html__( '1 hour ', 'widget-for-eventbrite-api' )),
            array(1800, esc_html__( '30 Minutes', 'widget-for-eventbrite-api' )),
            array(900, esc_html__( '15 Minutes - use on low volume sites only', 'widget-for-eventbrite-api' )),
            array(60, esc_html__( 'No Cache - use for development & testing only ', 'widget-for-eventbrite-api' ))
        );
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php 
        esc_html_e( 'Clear Cache', 'widget-for-eventbrite-api' );
        ?></th>
                <td>
                    <label for="widget-for-eventbrite-api-settings[cache_clear]"><input
                                type="checkbox"
                                name="widget-for-eventbrite-api-settings[cache_clear]"
                                id="widget-for-eventbrite-api-settings[cache_clear]"
                                value="1"
							<?php 
        checked( '1', $options['cache_clear'] );
        ?>>
						<?php 
        esc_html_e( 'Tick and [save] to clear', 'widget-for-eventbrite-api' );
        ?></label>
                    <p>
                        <span
                                class="description"><?php 
        esc_html_e( 'Clear the cache now, use for testing or if you have published or changed events and you want to refresh now', 'widget-for-eventbrite-api' );
        ?></span>
                    </p>
					<?php 
        ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <p>
						<?php 
        $infomsg = esc_html__( 'The following features are only available on the pro plan or trial', 'widget-for-eventbrite-api' );
        $disabled = ' disabled="disabled" ';
        echo wp_kses_post( $infomsg );
        ?></p>
            </tr>
            <tr valign="top" class="alternate">
                <th scope="row"><?php 
        esc_html_e( 'Cache Duration', 'widget-for-eventbrite-api' );
        ?></th>
                <td>
                    <select <?php 
        echo esc_attr( $disabled );
        ?>
                            name="widget-for-eventbrite-api-settings[cache_duration]"
                            id="widget-for-eventbrite-api-settings[cache_duration]"
                            class="small-text">
						<?php 
        foreach ( $units as $unit ) {
            ?>
                            <option value="<?php 
            echo (int) $unit[0];
            ?>"
								<?php 
            echo ( $options['cache_duration'] == $unit[0] ? " selected" : "" );
            ?>><?php 
            echo esc_html( $unit[1] );
            ?></option>
						<?php 
        }
        ?>
                    </select>
                    <p>
                        <span
                                class="description"><?php 
        printf( 
            // translators: placeholders are a link
            esc_html__( 'Set the cache period for the Eventbrite API. Read %1$sthis article%2$s before changing the default of once per day', 'widget-for-eventbrite-api' ),
            '<a href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/usage/understanding-the-cache/" target="_blank">',
            '</a>'
         );
        ?></span>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php 
        esc_html_e( 'Background API Processing', 'widget-for-eventbrite-api' );
        ?></th>
                <td>
                    <label for="widget-for-eventbrite-api-settings[background_api]"><input <?php 
        echo esc_attr( $disabled );
        ?>
                                type="checkbox"
                                name="widget-for-eventbrite-api-settings[background_api]"
                                id="widget-for-eventbrite-api-settings[background_api]"
                                value="1"
							<?php 
        checked( '1', $options['background_api'] );
        ?>>
						<?php 
        esc_html_e( 'Tick for background processing', 'widget-for-eventbrite-api' );
        ?>
                    </label>
                    <p>
                        <span
                                class="description"><?php 
        esc_html_e( 'Background API processing should ONLY be used when you have many hundreds of events, please contact support for advice before using.', 'widget-for-eventbrite-api' );
        ?></span>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>
		<?php 
    }

    public function meta_box_3() {
        $infomsg = '<p>' . sprintf( 
            // translators: leave place holders they are links
            __( 'Shortcode usage e.g. [wfea] [wfea limit=10 ] etc.<br>
		See your own events on the shortcode builder site to select your desired options
<a href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/usage/using-the-shortcode/#free" target="_blank">all free options detailed here</a>
<br><br>
Additional shortcode options are available in the  paid for version<br><br>
			Get a FREE trial of the Pro version - <a href="%1$s">click here</a> 
			<h2>Pro Version Benefits</h2>
			<ul>
				<li>14 day free trial</li>
				<li>Keep visitors on your site, with integrated checkout popup</li>
				<li>Let your users see your events on full pages and post</li>
				<li>Show your events off on layouts including styles for Divi, Genesis and WP default themes</li>
				<li>Need a calendar layout? We have one of those</li>
				<li>Like a grid or card layout? We have one of those too</li>
				<li>Are you and Eventbrite Music Promoter, we have all the extras like door times</li>
				<li>Full page? of course - why not browse the <a href="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo/" target="_blank">demo shortcode builder page</a> to see the powerful options</li>
				<li>Want to show off invite only events? the pro version can</li>
				<li>Do you have lots of events and would like to filter them down? The shortcode has sophisticated filters</li>
				<li>Need to see your events during development or quickly? The pro version has cache management</li>		
			</ul>
			Available when you go pro - layouts like  [wfea layout="cal" ] or [wfea layout="grid" popup=true ] etc - see  <a href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/usage/using-the-shotcode/" target="_blank"> this page</a> for many optional arguments
			', 'widget-for-eventbrite-api' ),
            $this->freemius->get_trial_url()
         );
        echo wp_kses_post( $infomsg );
        $this->display_demo_button();
    }

    public function meta_box_4() {
        $options = get_option( 'widget-for-eventbrite-api-settings', array(
            'key'     => array(array(
                'key'   => '',
                'label' => __( 'API Key 1', 'widget-for-eventbrite-api' ),
            )),
            'webhook' => '',
        ) );
        // Handle different formats of API key storage
        if ( !isset( $options['key'] ) || empty( $options['key'] ) ) {
            // No key set yet
            $api_keys = array(array(
                'key'   => '',
                'label' => __( 'API Key 1', 'widget-for-eventbrite-api' ),
            ));
        } elseif ( is_string( $options['key'] ) ) {
            // Old format: plain string
            $api_keys = array(array(
                'key'   => $options['key'],
                'label' => __( 'API Key 1', 'widget-for-eventbrite-api' ),
            ));
        } elseif ( is_array( $options['key'] ) && isset( $options['key']['key'] ) ) {
            // Single array format (not nested)
            $api_keys = array($options['key']);
        } elseif ( is_array( $options['key'] ) && isset( $options['key'][0] ) ) {
            // New format: array of arrays
            $api_keys = $options['key'];
        } else {
            // Fallback for any other format
            $api_keys = array(array(
                'key'   => '',
                'label' => __( 'API Key 1', 'widget-for-eventbrite-api' ),
            ));
        }
        $account_type = ( isset( $options['account_type'] ) ? $options['account_type'] : 'standard' );
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php 
        esc_html_e( 'Your API Key - Private Token', 'widget-for-eventbrite-api' );
        ?></th>
                <td class="wfea-api-keys">
					<?php 
        foreach ( $api_keys as $index => $api_key ) {
            $key = ( isset( $api_key['key'] ) ? $api_key['key'] : '' );
            $key_label = ( isset( $api_key['label'] ) ? $api_key['label'] : sprintf( 
                // translators:  placeholver is a number
                __( 'API Key %d', 'widget-for-eventbrite-api' ),
                $index + 1
             ) );
            ?>
                        <div class="wfea-api-key-row"
                             style="<?php 
            echo ( $this->freemius->is_free_plan() && $index > 0 ? 'display:none;' : '' );
            ?>"
                             data-row="<?php 
            echo esc_attr( $index );
            ?>">
                            <input
                                    type="password"
                                    name="widget-for-eventbrite-api-settings[key]<?php 
            echo '[' . esc_attr( $index ) . ']';
            ?>[key]"
                                    id="widget-for-eventbrite-api-settings-api-key_<?php 
            echo esc_attr( $index );
            ?>"
                                    class="wfea-key regular-text"
                                    value="<?php 
            echo esc_attr( $key );
            ?>"
                            />
                            </br><br>
                            <input
                                    type="text"
                                    name="widget-for-eventbrite-api-settings[key]<?php 
            echo '[' . esc_attr( $index ) . ']';
            ?>[label]"
                                    class="wfea-key-label regular-text required"
                                    required
                                    value="<?php 
            echo esc_attr( $key_label );
            ?>"
                            />
							<?php 
            ?>
                            <p class="api-key-status"></p>
                            <p class="api-key-result"></p>
                        </div>
						<?php 
        }
        ?>
                    <p class="wfea-api-key-notice">
						<span
                                class="description"><?php 
        esc_html_e( 'The key is required to connect to Eventbrite. Leave it empty if you only show EventKiln events.', 'widget-for-eventbrite-api' );
        ?>
								- <a href="https://www.eventbrite.co.uk/platform/api-keys/"
                                     target="_blank"><?php 
        esc_html_e( 'Click here to get your free API Key from Eventbrite', 'widget-for-eventbrite-api' );
        ?></a></span>
                    </p>
					<?php 
        ?>
                </td>
            </tr>
			<?php 
        ?>
            </tbody>
        </table>
		<?php 
    }

    public function meta_box_eventkiln() {
        $options = get_option( 'widget-for-eventbrite-api-settings', Admin_Settings::option_defaults( 'widget-for-eventbrite-api-settings' ) );
        $url = ( isset( $options['eventkiln_url'] ) ? $options['eventkiln_url'] : '' );
        $key = ( isset( $options['eventkiln_key'] ) ? $options['eventkiln_key'] : '' );
        $source = EventKiln_Source::get_instance();
        $status = $source->get_status();
        $cache = get_option( EventKiln_Source::CACHE_OPTION );
        // Events fetched from a different EventKiln are not shown, so don't report them either.
        if ( is_array( $cache ) && isset( $cache['source'] ) && $cache['source'] !== $source->get_settings()['url'] ) {
            $cache = false;
        }
        $datetime = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $ek_link = function ( $path ) {
            return add_query_arg( array(
                'utm_source'   => 'dee',
                'utm_medium'   => 'plugin',
                'utm_campaign' => 'settings',
            ), EventKiln_Source::DEFAULT_URL . $path );
        };
        $is_pro = $this->freemius->can_use_premium_code();
        if ( !$source->is_configured() ) {
            ?>
            <div class="wfea-ek-pitch">
                <h3><?php 
            esc_html_e( 'Sell tickets on your own website', 'widget-for-eventbrite-api' );
            ?></h3>
                <p><?php 
            esc_html_e( 'EventKiln is ticketing from Fullworks, the team behind Display Eventbrite Events. Connect it below and your EventKiln events appear in the layouts and calendar you already use, next to your Eventbrite events.', 'widget-for-eventbrite-api' );
            ?></p>
                <ul>
                    <li><strong><?php 
            esc_html_e( 'One list, in date order.', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'Visitors see Eventbrite and EventKiln events together.', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Built for gigs and comedy nights.', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'Doors and show times, line-up, age limits and "presented by" on every event.', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Repeating events.', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'Set up a weekly or monthly night once. Each date sells its own tickets.', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Selling some tickets somewhere else?', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'List those events too, with a link to wherever the tickets are sold (Pro plan).', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Your money, your Stripe.', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'Payouts go straight to your own Stripe account. 2.5% + 30p per paid ticket in the UK (the fixed part varies by currency), and nothing on free tickets. Stripe\'s card fees are separate.', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Starter plan is free,', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'with unlimited paid events.', 'widget-for-eventbrite-api' );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'Pop-up checkout on your page,', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            ( $is_pro ? esc_html_e( 'included in your Pro plan.', 'widget-for-eventbrite-api' ) : esc_html_e( 'with Display Eventbrite Events Pro.', 'widget-for-eventbrite-api' ) );
            ?></li>
                    <li><strong><?php 
            esc_html_e( 'QR check-in', 'widget-for-eventbrite-api' );
            ?></strong> <?php 
            esc_html_e( 'from any phone.', 'widget-for-eventbrite-api' );
            ?></li>
                </ul>
                <p class="wfea-ek-actions">
                    <a class="button button-primary" href="<?php 
            echo esc_url( $ek_link( '/register' ) );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'Create your free account', 'widget-for-eventbrite-api' );
            ?></a>
                    <a href="<?php 
            echo esc_url( $ek_link( '/wordpress' ) );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'Find out more', 'widget-for-eventbrite-api' );
            ?></a>
                    <a href="<?php 
            echo esc_url( $ek_link( '/pricing' ) );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'Pricing', 'widget-for-eventbrite-api' );
            ?></a>
                    <a href="<?php 
            echo esc_url( $ek_link( '/venues' ) );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'For venues', 'widget-for-eventbrite-api' );
            ?></a>
                </p>
            </div>
			<?php 
        }
        ?>
        <p><?php 
        ( $source->is_configured() ? esc_html_e( 'Show events from EventKiln alongside your Eventbrite events, or on their own. Leave the API key empty to turn EventKiln off.', 'widget-for-eventbrite-api' ) : esc_html_e( 'Leave the API key empty to turn EventKiln off.', 'widget-for-eventbrite-api' ) );
        ?></p>
        <table class="form-table">
            <tbody>
			<?php 
        if ( EventKiln_Source::is_development_site() ) {
            ?>
                <tr valign="top">
                    <th scope="row"><label for="wfea-eventkiln-url"><?php 
            esc_html_e( 'EventKiln site URL', 'widget-for-eventbrite-api' );
            ?></label></th>
                    <td>
                        <input type="url" class="regular-text" id="wfea-eventkiln-url"
                               name="widget-for-eventbrite-api-settings[eventkiln_url]"
                               placeholder="<?php 
            echo esc_attr( EventKiln_Source::DEFAULT_URL );
            ?>"
                               value="<?php 
            echo esc_attr( $url );
            ?>"/>
                        <p class="description"><?php 
            printf( 
                /* translators: %s: the EventKiln address live sites use */
                esc_html__( 'Development sites only, for testing against another EventKiln. Leave empty to use %s, which is what live sites always use.', 'widget-for-eventbrite-api' ),
                '<code>' . esc_html( EventKiln_Source::DEFAULT_URL ) . '</code>'
             );
            ?></p>
                    </td>
                </tr>
			<?php 
        }
        ?>
            <tr valign="top">
                <th scope="row"><label for="wfea-eventkiln-key"><?php 
        esc_html_e( 'EventKiln API key', 'widget-for-eventbrite-api' );
        ?></label></th>
                <td>
                    <input type="password" class="regular-text" id="wfea-eventkiln-key" autocomplete="off"
                           name="widget-for-eventbrite-api-settings[eventkiln_key]"
                           value="<?php 
        echo esc_attr( $key );
        ?>"/>
                    <p class="description"><?php 
        ( $is_pro ? esc_html_e( 'In EventKiln, open your organisation, go to API tokens and create a token with only the events:read and webhooks permissions.', 'widget-for-eventbrite-api' ) : esc_html_e( 'In EventKiln, open your organisation, go to API tokens and create a token with only the events:read permission.', 'widget-for-eventbrite-api' ) );
        ?>
						<?php 
        if ( !$source->is_configured() ) {
            ?>
							<?php 
            esc_html_e( 'No account yet?', 'widget-for-eventbrite-api' );
            ?>
                            <a href="<?php 
            echo esc_url( $ek_link( '/register' ) );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'Create your free account', 'widget-for-eventbrite-api' );
            ?></a>
						<?php 
        }
        ?></p>
					<?php 
        $this->eventkiln_banner( $source, $status, $cache );
        ?>
                </td>
            </tr>
			<?php 
        if ( $source->is_configured() ) {
            ?>
                <tr valign="top">
                    <th scope="row"><?php 
            esc_html_e( 'Status', 'widget-for-eventbrite-api' );
            ?></th>
                    <td>
						<?php 
            if ( is_array( $cache ) && !empty( $cache['fetched_at'] ) ) {
                printf(
                    /* translators: 1: organisation name 2: number of events 3: date and time */
                    esc_html__( 'Connected to %1$s. %2$d upcoming events, last fetched %3$s.', 'widget-for-eventbrite-api' ),
                    '<strong>' . esc_html( $cache['organisation'] ) . '</strong>',
                    (int) count( $cache['events'] ),
                    esc_html( wp_date( $datetime, $cache['fetched_at'] ) )
                );
            } else {
                esc_html_e( 'No events fetched yet.', 'widget-for-eventbrite-api' );
            }
            if ( '' !== $status['last_error'] && $status['last_error_at'] >= $status['last_success'] ) {
                echo '<br><span style="color:#b32d2e">' . esc_html( $status['last_error'] ) . '</span>';
            }
            ?>
                        <p class="description"><?php 
            esc_html_e( 'EventKiln events are refreshed in the background as often as your Eventbrite events are cached, once a day unless changed. Save these settings to refresh them now.', 'widget-for-eventbrite-api' );
            ?></p>
                        <p><a href="<?php 
            echo esc_url( $source->get_settings()['url'] . '/manage' );
            ?>" target="_blank" rel="noopener"><?php 
            esc_html_e( 'Manage your events in EventKiln', 'widget-for-eventbrite-api' );
            ?> &rarr;</a></p>
                        <p class="description"><?php 
            printf( 
                /* translators: %s: this website's address */
                esc_html__( 'To let visitors buy tickets in a pop-up without leaving this website (Pro, popup option), add %s in EventKiln under Settings, Checkout on your own website. If your site sends a Content Security Policy, allow the EventKiln site in script-src and frame-src.', 'widget-for-eventbrite-api' ),
                '<code>' . esc_html( untrailingslashit( home_url( '', ( is_ssl() ? 'https' : 'http' ) ) ) ) . '</code>'
             );
            ?></p>
                    </td>
                </tr>
				<?php 
            /** Pro adds its EventKiln rows here. */
            do_action( 'wfea_eventkiln_settings_rows', $source );
        }
        ?>
            </tbody>
        </table>
		<?php 
    }

    /**
     * The same green / red banner the Eventbrite key gets, for the EventKiln connection.
     *
     * A failed save keeps the previous settings, so its error (from this request's settings
     * errors) is shown ahead of the stored status.
     */
    private function eventkiln_banner( $source, $status, $cache ) {
        $message = '';
        foreach ( get_settings_errors( 'wfea-eventkiln' ) as $error ) {
            if ( 'error' === $error['type'] ) {
                $message = $error['message'];
            }
        }
        if ( '' === $message && $source->is_configured() && '' !== $status['last_error'] && $status['last_error_at'] >= $status['last_success'] ) {
            $message = $status['last_error'];
        }
        if ( '' !== $message ) {
            echo '<p class="api-key-status error">' . esc_html( wp_strip_all_tags( $message ) ) . '</p>';
        } elseif ( $source->is_configured() && is_array( $cache ) && !empty( $cache['fetched_at'] ) ) {
            printf( '<p class="api-key-status connected">%s</p>', sprintf( 
                /* translators: 1: organisation name 2: number of events */
                esc_html__( 'Connected to %1$s. %2$d upcoming events.', 'widget-for-eventbrite-api' ),
                esc_html( $cache['organisation'] ),
                (int) count( $cache['events'] )
             ) );
        }
    }

    public function meta_box_5() {
        $options = get_option( 'widget-for-eventbrite-api-settings' );
        if ( !isset( $options['plugin-css'] ) ) {
            $options['plugin-css'] = 1;
        }
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php 
        esc_html_e( 'Output Default CSS', 'widget-for-eventbrite-api' );
        ?></th>
                <td>
                    <label for="widget-for-eventbrite-api-settings[plugin-css]"><input type="checkbox"
                                                                                       name="widget-for-eventbrite-api-settings[plugin-css]"
                                                                                       id="widget-for-eventbrite-api-settings[plugin-css]"
                                                                                       value="1"
							<?php 
        checked( '1', $options['plugin-css'] );
        ?>>
						<?php 
        esc_html_e( 'Enable this option to output the default CSS. Disable it if you plan to create your own CSS in a child theme or customizer additional CSS.', 'widget-for-eventbrite-api' );
        ?>
                    </label>
					<?php 
        if ( !$this->freemius->can_use_premium_code() ) {
            ?>
                        <p>
                        <span
                                class="description"><?php 
            esc_html_e( 'Need help with styling to your theme? Upgrade to a premium plan to request personal CSS support', 'widget-for-eventbrite-api' );
            ?>
                            <a class="button-secondary"
                               href="<?php 
            echo esc_url( $this->freemius->get_upgrade_url() );
            ?>"><?php 
            esc_html_e( 'Upgrade now', 'widget-for-eventbrite-api' );
            ?></a>
                        </span>
                        </p>
					<?php 
        }
        ?>
                </td>
            </tr>
            </tbody>
        </table>

		<?php 
    }

    public function meta_box_webhooks() {
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <td colspan="100">
                    <p>
						<?php 
        esc_html_e( '[Optional] you can set up WebHooks to notify this website that an event has changed, this will speed up changes appearing', 'widget-for-eventbrite-api' );
        ?>
                    </p>
                    <p>
                        <span
                                class="description"><?php 
        esc_html_e( 'Upgrade to a premium plan to enable WebHook handling', 'widget-for-eventbrite-api' );
        ?>
                            <a class="button-secondary"
                               href="<?php 
        echo esc_url( $this->freemius->get_upgrade_url() );
        ?>"><?php 
        esc_html_e( 'Upgrade now', 'widget-for-eventbrite-api' );
        ?></a>
                        </span>
                    </p>

                </td>
            </tr>
            </tbody>
        </table>
		<?php 
    }

    public function register_settings() {
        /* Register our setting. */
        register_setting( 
            $this->option_group,
            /* Option Group */
            'widget-for-eventbrite-api-settings',
            /* Option Name */
            array($this, 'sanitize_settings_1')
         );
        /* Add settings menu page */
        $this->settings_page = add_submenu_page(
            'widget-for-eventbrite-api',
            'Settings',
            /* Page Title */
            'Settings',
            /* Menu Title */
            'manage_options',
            /* Capability */
            'widget-for-eventbrite-api',
            /* Page Slug */
            array($this, 'settings_page')
        );
        register_setting( 
            $this->option_group,
            /* Option Group */
            "{$this->option_group}-reset",
            /* Option Name */
            array($this, 'reset_sanitize')
         );
    }

    public function sanitize_settings_1( $settings ) {
        if ( empty( $settings ) ) {
            return $settings;
        }
        $options = get_option( 'widget-for-eventbrite-api-settings' );
        // Preserve webhook_dir from existing options (set via AJAX, not in form)
        if ( !empty( $options['webhook_dir'] ) && !isset( $settings['webhook_dir'] ) ) {
            $settings['webhook_dir'] = $options['webhook_dir'];
        }
        // Preserve webhook_version from existing options (set via upgrade, not in form)
        if ( !empty( $options['webhook_version'] ) && !isset( $settings['webhook_version'] ) ) {
            $settings['webhook_version'] = $options['webhook_version'];
        }
        if ( !isset( $settings['plugin-css'] ) ) {
            $settings['plugin-css'] = 0;
            // always set checkboxes of they dont exist
        }
        if ( !isset( $settings['cache_duration'] ) ) {
            $settings['cache_duration'] = 86400;
            // always set if they dont exist
        }
        if ( !isset( $settings['cache_clear'] ) ) {
            $settings['cache_clear'] = 0;
            // always set if they dont exist
        }
        $settings['background_api'] = 0;
        $settings['consent_gate'] = 0;
        if ( 1 == $settings['cache_clear'] ) {
            list( $display_eventbrite, $settings ) = $this->clear_cache( $settings );
        }
        if ( isset( $settings['cache_duration'] ) ) {
            $settings['cache_duration'] = (int) $settings['cache_duration'];
        }
        flush_rewrite_rules();
        $options = get_option( 'widget-for-eventbrite-api-settings' );
        $settings = $this->sanitize_eventkiln( $settings, $options );
        if ( isset( $settings['key'] ) && $this->has_no_eventbrite_key( $settings['key'] ) ) {
            // An EventKiln-only site needs no Eventbrite key, so there is nothing to validate.
            if ( !empty( $settings['eventkiln_key'] ) ) {
                if ( !empty( $options['key'] ) && !$this->has_no_eventbrite_key( $options['key'] ) ) {
                    list( $display_eventbrite, $settings ) = $this->clear_cache( $settings );
                }
                $settings['key'] = array(array(
                    'key'   => '',
                    'label' => 'API Key 1',
                ));
                return $settings;
            }
            add_settings_error(
                'wfea-api-key',
                'wfea-api-key',
                esc_html__( 'An Eventbrite API key or an EventKiln connection is required', 'widget-for-eventbrite-api' ),
                'error'
            );
            $settings['key'] = ( isset( $options['key'] ) ? $options['key'] : $settings['key'] );
            return $settings;
        }
        if ( isset( $settings['key'] ) ) {
            if ( empty( $settings['key'] ) || empty( $options['key'] ) ) {
                add_settings_error(
                    'wfea-api-key',
                    'wfea-api-key',
                    esc_html__( 'An API Private Token is required', 'widget-for-eventbrite-api' ),
                    'error'
                );
                $settings['key'] = $options['key'];
                return $settings;
            } elseif ( $settings['key'] != $options['key'] ) {
                // not empty and changed
                // flush transients
                list( $display_eventbrite, $settings ) = $this->clear_cache( $settings );
                $validation_error = false;
                // test the new keys
                if ( is_array( $settings['key'] ) ) {
                    $registered_keys = array();
                    foreach ( $settings['key'] as $index => $api_key ) {
                        if ( !in_array( $api_key['key'], $registered_keys, true ) ) {
                            $registered_keys[] = $api_key['key'];
                        } else {
                            unset($settings['key'][$index]);
                            continue;
                        }
                        $organizations = $display_eventbrite->request(
                            'organizations',
                            array(
                                'token' => $api_key['key'],
                            ),
                            false,
                            true
                        );
                        if ( is_wp_error( $organizations ) ) {
                            $validation_error = true;
                            break;
                        }
                    }
                    $settings['key'] = array_values( $settings['key'] );
                } else {
                    $organizations = $display_eventbrite->request(
                        'organizations',
                        array(
                            'token' => $settings['key'],
                        ),
                        false,
                        true
                    );
                    if ( is_wp_error( $organizations ) ) {
                        $validation_error = true;
                    }
                }
                if ( $validation_error ) {
                    $msg = $organizations->get_error_message();
                    if ( is_array( $msg ) ) {
                        $text = json_decode( $msg['body'] );
                        $msg = $text->error_description;
                        if ( 'INVALID_AUTH' == $text->error ) {
                            $msg .= esc_html__( ' : instructions on how to find your key are <a href="https://fullworksplugins.com/docs/display-eventbrite-events-in-wordpress/installation-display-eventbrite-events-in-wordpress/connect-to-eventbrite/" target="_blank">here</a> (note a change of Eventbrite password requires a new key)', 'widget-for-eventbrite-api' );
                        }
                        add_settings_error(
                            'wfea-api-key-fail',
                            'wfea-api-key-fail',
                            sprintf( 
                                // translators:  placeholder is an API key
                                esc_html__( 'Something failed with the key: %1$s', 'widget-for-eventbrite-api' ),
                                $msg
                             ),
                            'error'
                        );
                        $settings['key'] = $options['key'];
                    }
                    if ( is_wp_error( $msg ) ) {
                        $msg = $organizations->get_error_message();
                        if ( !is_string( $msg ) ) {
                            $msg = sprintf( 
                                // translators:  placeholder is an API key
                                esc_html__( 'A very unexpected error with the API call to check the key happened: %1$s', 'widget-for-eventbrite-api' ),
                                print_r( $msg, true )
                             );
                        }
                        add_settings_error(
                            'wfea-api-key-fail',
                            'wfea-api-key-fail',
                            sprintf( 
                                // translators:  placeholder is an API key
                                esc_html__( 'Something failed with the key: %1$s', 'widget-for-eventbrite-api' ),
                                $msg
                             ),
                            'error'
                        );
                        $settings['key'] = $options['key'];
                    }
                    return $settings;
                }
            }
        }
        return $settings;
    }

    /**
     * True when no Eventbrite key has been entered, in any of the stored formats.
     */
    private function has_no_eventbrite_key( $keys ) {
        if ( is_string( $keys ) ) {
            return '' === trim( $keys );
        }
        if ( !is_array( $keys ) ) {
            return true;
        }
        foreach ( $keys as $key ) {
            if ( is_array( $key ) && isset( $key['key'] ) && '' !== trim( $key['key'] ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check the EventKiln connection on save and fetch its events straight away.
     *
     * A connection that fails its check is not saved, so a typo never replaces a working setup.
     * Saving again with a working connection refreshes the events, which doubles as "refresh now".
     */
    private function sanitize_eventkiln( $settings, $options ) {
        // The URL field only exists on development sites. Everywhere else nothing is stored and
        // EventKiln_Source::effective_url() gives eventkiln.com.
        $old_stored = ( isset( $options['eventkiln_url'] ) ? $options['eventkiln_url'] : '' );
        $old_key = ( isset( $options['eventkiln_key'] ) ? $options['eventkiln_key'] : '' );
        $submitted = ( EventKiln_Source::is_development_site() && isset( $settings['eventkiln_url'] ) ? trim( $settings['eventkiln_url'] ) : '' );
        $stored = EventKiln_Source::sanitize_url( $submitted );
        $old_url = EventKiln_Source::effective_url( $old_stored );
        $url = EventKiln_Source::effective_url( $stored );
        $key = ( isset( $settings['eventkiln_key'] ) ? trim( sanitize_text_field( $settings['eventkiln_key'] ) ) : '' );
        $source = EventKiln_Source::get_instance();
        if ( '' !== $submitted && '' === $stored ) {
            add_settings_error(
                'wfea-eventkiln',
                'wfea-eventkiln-url',
                esc_html__( 'The EventKiln site URL is not a valid address. The EventKiln settings were not saved.', 'widget-for-eventbrite-api' ),
                'error'
            );
            $settings['eventkiln_url'] = $old_stored;
            $settings['eventkiln_key'] = $old_key;
            return $settings;
        }
        // Pro's EventKiln webhooks checkbox: on unless turned off.
        if ( isset( $settings['eventkiln_webhooks'] ) ) {
            $settings['eventkiln_webhooks'] = (int) (bool) $settings['eventkiln_webhooks'];
        } else {
            $settings['eventkiln_webhooks'] = ( isset( $options['eventkiln_webhooks'] ) ? (int) $options['eventkiln_webhooks'] : 1 );
        }
        if ( '' === $key ) {
            if ( '' !== $old_key ) {
                $source->forget();
                Utilities::get_instance()->clear_all_caches_for_posts_with_shortcode();
            }
            $settings['eventkiln_url'] = $stored;
            $settings['eventkiln_key'] = '';
            /** Fires after the EventKiln settings are checked, before they are stored. */
            do_action(
                'wfea_eventkiln_settings_saved',
                $url,
                '',
                $settings
            );
            return $settings;
        }
        $organisation = $source->get_organisation( $url, $key );
        if ( is_wp_error( $organisation ) ) {
            add_settings_error(
                'wfea-eventkiln',
                'wfea-eventkiln-failed',
                sprintf( 
                    /* translators: %s: reason from EventKiln */
                    esc_html__( 'The EventKiln settings were not saved: %s', 'widget-for-eventbrite-api' ),
                    $organisation->get_error_message()
                 ),
                'error'
            );
            $settings['eventkiln_url'] = $old_stored;
            $settings['eventkiln_key'] = $old_key;
            return $settings;
        }
        $settings['eventkiln_url'] = $stored;
        $settings['eventkiln_key'] = $key;
        $abilities = ( isset( $organisation['token_abilities'] ) && is_array( $organisation['token_abilities'] ) ? $organisation['token_abilities'] : array() );
        if ( array_diff( $abilities, array('events:read', 'webhooks') ) ) {
            add_settings_error(
                'wfea-eventkiln',
                'wfea-eventkiln-abilities',
                esc_html__( 'This EventKiln API key can do more than read events. It works, but for safety create one with only the events:read permission (and webhooks, in Pro).', 'widget-for-eventbrite-api' ),
                'warning'
            );
        }
        if ( $url !== $old_url ) {
            $source->forget();
        }
        $refreshed = $source->refresh( $url, $key );
        if ( is_wp_error( $refreshed ) ) {
            add_settings_error(
                'wfea-eventkiln',
                'wfea-eventkiln-fetch',
                sprintf( 
                    /* translators: %s: reason from EventKiln */
                    esc_html__( 'Connected to EventKiln, but fetching events failed: %s', 'widget-for-eventbrite-api' ),
                    $refreshed->get_error_message()
                 ),
                'warning'
            );
        } else {
            $cache = get_option( EventKiln_Source::CACHE_OPTION );
            add_settings_error(
                'wfea-eventkiln',
                'wfea-eventkiln-ok',
                sprintf( 
                    /* translators: 1: organisation name 2: number of events */
                    esc_html__( 'Connected to EventKiln as %1$s and fetched %2$d upcoming events.', 'widget-for-eventbrite-api' ),
                    esc_html( ( isset( $organisation['name'] ) ? $organisation['name'] : '' ) ),
                    ( is_array( $cache ) ? count( $cache['events'] ) : 0 )
                 ),
                'success'
            );
        }
        do_action(
            'wfea_eventkiln_settings_saved',
            $url,
            $key,
            $settings
        );
        return $settings;
    }

    private function display_demo_button() {
        $plan = 'Free&layout=widget';
        $layout = 'widget';
        $options = get_option( 'widget-for-eventbrite-api-settings' );
        if ( is_plugin_active( 'elementor/elementor.php' ) ) {
            echo esc_html__( 'Go to your page, in Elementor add the Display Eventbrite widget and select options', 'widget-for-eventbrite-api' );
        } elseif ( is_array( $options ) && isset( $options['key'] ) && is_array( $options['key'] ) && isset( $options['key'][0] ) && is_array( $options['key'][0] ) && isset( $options['key'][0]['key'] ) ) {
            ?>
            <button class="button button-primary"
                    onclick="wfea_demo_form()"><?php 
            esc_html_e( 'Go to Shortcode Builder', 'widget-for-eventbrite-api' );
            ?>
            </button>
            <script>
                function wfea_demo_form() {
                    document.body.innerHTML += '<form id="dynForm" action="https://fullworksplugins.com/products/widget-for-eventbrite/eventbrite-shortcode-demo" method="post" target="_blank"><input type="hidden" name="layout" value="<?php 
            echo esc_attr( $layout );
            ?>"><input type="hidden" name="plan" value="<?php 
            echo esc_attr( $plan );
            ?>"><input type="hidden" name="apikey" value="<?php 
            echo esc_attr( $options['key'][0]['key'] );
            ?>"><input type="hidden" name="useapi" value="Own API Private Token"></form>';
                    document.getElementById("dynForm").submit();
                }
            </script>
			<?php 
        }
    }

}
