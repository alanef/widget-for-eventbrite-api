<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Display Eventbrite Events
 * Plugin URI:        https://fullworksplugins.com/products/widget-for-eventbrite/
 * Description:       Easily display Eventbrite events on your WordPress site
 * Version:           6.5.7
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Fullworks
 * Author URI:        https://fullworksplugins.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       widget-for-eventbrite-api
 * Domain Path:       /languages
 *
 *
 *
 * Acknowledgements:
 * Lots of code and coding ideas for the original widget have been from the GPL licenced Recent Posts Widget Extended by Satrya https://www.theme-junkie.com/
 *
 * This plugin used to depend on  https://wordpress.org/plugins/eventbrite-api/ by Automattic
 * However Automattic stopped supporting and maintaining it in July 2018, so I have taken onboard many GPL licenced classes and functions
 * directly within this code line, whilst many changes have been made some code originates from Automattic
 *
 */
namespace WidgetForEventbriteAPI;

// If this file is called directly, abort.
use Freemius;
use Fullworks_WP_Autoloader\AutoloaderPlugin;
use WidgetForEventbriteAPI\Includes\Core;
use WidgetForEventbriteAPI\Includes\Freemius_Config;
use WidgetForEventbriteAPI\Includes\Utilities;
if ( !defined( 'WPINC' ) ) {
    die;
}
// define some useful constants
define( 'WIDGET_FOR_EVENTBRITE_API_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WIDGET_FOR_EVENTBRITE_API_PLUGIN_NAME', basename( WIDGET_FOR_EVENTBRITE_API_PLUGIN_DIR ) );
define( 'WIDGET_FOR_EVENTBRITE_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WIDGET_FOR_EVENTBRITE_API_PLUGINS_TOP_DIR', plugin_dir_path( __DIR__ ) );
define( 'WIDGET_FOR_EVENTBRITE_API_PLUGIN_VERSION', '6.5.7' );
// Include the plugin autoloader, so we can dynamically include the classes.
require_once WIDGET_FOR_EVENTBRITE_API_PLUGIN_DIR . 'includes/vendor/autoload.php';
new AutoloaderPlugin(__NAMESPACE__, __DIR__);
/** @var \Freemius $wfea_fs Freemius global object. */
global $wfea_fs;
/**
 * @var Freemius $wfea_freemius freemius SDK.
 */
$wfea_freemius = new Freemius_Config();
$wfea_freemius->init();
if ( !function_exists( 'WidgetForEventbriteAPI\\run_wfea' ) ) {
    function run_wfea() {
        /** @var \Freemius $wfea_fs Freemius global object. */
        global $wfea_fs;
        // include legacy functions for backwards compatability
        require_once WIDGET_FOR_EVENTBRITE_API_PLUGIN_DIR . 'includes/legacy-functions.php';
        /**
         * The code that runs during plugin activation.
         * This action is documented in includes/class-activator.php
         */
        register_activation_hook( __FILE__, array('\\WidgetForEventbriteAPI\\Includes\\Activator', 'activate') );
        register_deactivation_hook( __FILE__, array('\\WidgetForEventbriteAPI\\Includes\\Deactivator', 'deactivate') );
        add_action( 'setup_theme', function () {
            global $wfea_fs;
            // run the plugin now
            $plugin = new Core($wfea_fs);
            $plugin->run();
        } );
        // Signal that SDK was initiated.
        do_action( 'wfea_fs_loaded' );
        $wfea_fs->add_action( 'after_uninstall', array('\\WidgetForEventbriteAPI\\Includes\\Uninstall', 'uninstall') );
    }

    run_wfea();
} else {
    $wfea_fs->set_basename( true, __FILE__ );
}