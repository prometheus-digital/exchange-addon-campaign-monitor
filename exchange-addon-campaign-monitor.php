<?php
/**
 * iThemes Exchange - Campaign Monitor Add-on.
 *
 * @package   TGM_Exchange_Campaign_Monitor
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @link      http://thomasgriffinmedia.com/
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 *
 * @wordpress-plugin
 * Plugin Name:  iThemes Exchange - Campaign Monitor Add-on
 * Plugin URI:   http://ithemes.com/exchange/campaign-monitor/
 * Description:  Integrates Campaign Monitor into the iThemes Exchange plugin.
 * Version:      1.0.8
 * Author:       iThemes
 * Author URI:   http://ithemes.com/exchange/
 * Text Domain:  LION
 * Contributors: ithemes, griffinjt
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:  /lang
 * iThemes Package: exchange-addon-campaign-monitor
 *
 * This add-on was originally developed by Thomas Griffin <http://thomasgriffinmedia.com/>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

// Define constants.
define( 'TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE', __FILE__ );

// Register the plugin updater.
add_action( 'ithemes_updater_register', 'tgm_exchange_campaign_monitor_updater' );
/**
 * Registers the iThemes updater with the addon.
 *
 * @since 1.0.0
 *
 * @param object $updater The iThemes updater object.
 */
function tgm_exchange_campaign_monitor_updater( $updater ) {

    // Return early if not in the admin.
    if ( ! is_admin() ) return;

    // Load the updater class.
    require_once dirname( __FILE__ ) . '/lib/updater/load.php';

    // Register the addon with the updater.
    $updater->register( 'exchange-addon-campaign-monitor', __FILE__ );

}

// Register the addon with the Exchange engine.
add_action( 'it_exchange_register_addons', 'tgm_exchange_campaign_monitor_register' );
/**
 * Registers the Campaign Monitor addon with the Exchange addons engine.
 *
 * @since 1.0.0
 */
function tgm_exchange_campaign_monitor_register() {

    $versions         = get_option( 'it-exchange-versions', false );
    $current_version  = empty( $versions['current'] ) ? false : $versions['current'];

    if ( $current_version && version_compare( $current_version, '1.0.3', '>' ) ) {
        $options = array(
            'name'              => __( 'Campaign Monitor', 'tgm-exchange-campaign-monitor' ),
            'description'       => __( 'Adds a Campaign Monitor optin checkbox to the user registration form.', 'tgm-exchange-campaign-monitor' ),
            'author'            => 'iThemes',
            'author_url'        => 'http://ithemes.com/exchange/',
            'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/campaign-monitor50px.png' ),
            'file'              => dirname( __FILE__ ) . '/class-exchange-addon-campaign-monitor.php',
            'category'          => 'email',
            'settings-callback' => 'tgm_exchange_campaign_monitor_settings'
        );
        it_exchange_register_addon( 'campaign-monitor', $options );
    } else {
        add_action( 'admin_notices', 'tgm_exchange_campaign_monitor_nag' );
    }

}

/**
 * Callback function for outputting the addon settings view.
 *
 * @since 1.0.0
 */
function tgm_exchange_campaign_monitor_settings() {

    TGM_Exchange_Campaign_Monitor::get_instance()->settings();

}

/**
 * Callback function for displaying upgrade nag.
 *
 * @since 1.0.0
 */
function tgm_exchange_campaign_monitor_nag() {

    TGM_Exchange_Campaign_Monitor::get_instance()->nag();

}

register_activation_hook( __FILE__, 'tgm_exchange_campaign_monitor_activate' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version The current version of WP on this install.
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_campaign_monitor_activate( $network_wide ) {

    global $wp_version;

    // If not WP 3.5 or greater, bail.
    if ( version_compare( $wp_version, '3.5.1', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'Sorry, but your version of WordPress, <strong>' . $wp_version . '</strong>, does not meet the required version of <strong>3.5.1</strong> to run this plugin properly. The plugin has been deactivated. <a href="' . admin_url() . '">Click here to return to the Dashboard</a>.' );
    }

    // If our option does not exist, add it now.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            $settings = get_option( 'tgm_exchange_campaign_monitor' );
            if ( ! $settings )
                update_option( 'tgm_exchange_campaign_monitor', tgm_exchange_campaign_monitor_defaults() );
            restore_current_blog();
        endforeach;
    else :
        $settings = get_option( 'tgm_exchange_campaign_monitor' );
        if ( ! $settings )
            update_option( 'tgm_exchange_campaign_monitor', tgm_exchange_campaign_monitor_defaults() );
    endif;

}

register_uninstall_hook( __FILE__, 'tgm_exchange_campaign_monitor_uninstall' );
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_campaign_monitor_uninstall( $network_wide ) {

    // Remove any trace of our addon.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            delete_option( 'tgm_exchange_campaign_monitor' );
            restore_current_blog();
        endforeach;
    else :
        delete_option( 'tgm_exchange_campaign_monitor' );
    endif;

}

/**
 * Sets addon option defaults.
 *
 * @since 1.0.0
 *
 * @return array $defaults Default options.
 */
function tgm_exchange_campaign_monitor_defaults() {

    $defaults                              = array();
    $defaults['campaign-monitor-api-key']  = '';
    $defaults['campaign-monitor-client']   = '';
    $defaults['campaign-monitor-list']     = '';
    $defaults['campaign-monitor-label']    = __( 'Sign up to receive updates via email!', 'tgm-exchange-campaign-monitor' );
    $defaults['campaign-monitor-checked']  = 1;

    return $defaults;

}
