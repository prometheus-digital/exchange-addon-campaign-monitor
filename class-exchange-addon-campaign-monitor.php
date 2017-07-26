<?php
/**
 * ExchangeWP - Campaign Monitor Add-on class.
 *
 * @package   TGM_Exchange_Campaign_Monitor
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 */

/**
 * Main plugin class.
 *
 * @package TGM_Exchange_Campaign_Monitor
 */
class TGM_Exchange_Campaign_Monitor {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'ExchangeWP - Campaign Monitor Add-on';

    /**
     * Unique plugin identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'exchange-addon-campaign-monitor';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $domain = 'LION';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance = null;

    /**
     * Holds any error messages.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $errors = array();

    /**
     * Flag to determine if form was saved.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $saved = false;

    /**
     * Initialize the plugin class object.
     *
     * @since 1.0.0
     */
    private function __construct() {

        // Load plugin text domain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ) );

        // Load ajax hooks.
        add_action( 'wp_ajax_tgm_exchange_campaign_monitor_update_clients', array( $this, 'clients' ) );
        add_action( 'wp_ajax_tgm_exchange_campaign_monitor_update_lists', array( $this, 'lists' ) );

        add_action( 'admin_notices', array( $this, 'exchange_campaignmonitor_admin_notices' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance )
            self::$instance = new self;

        return self::$instance;

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->domain;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    }

    /**
     * Loads the plugin.
     *
     * @since 1.0.0
     */
    public function init() {

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_content_checkout_logged_in_checkout_requirement_guest_checkout_end_form', array( $this, 'output_optin_guest' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );
        add_action( 'it_exchange_init_guest_checkout', array( $this, 'do_optin_guest' ) );

    }

    /**
     * Outputs update nag if the currently installed version does not meet the addon requirements.
     *
     * @since 1.0.0
     */
    public function nag() {

        ?>
        <div id="LION-nag" class="it-exchange-nag">
            <?php
            printf( __( 'To use the Campaign Monitor add-on for ExchangeWP, you must be using ExchangeWP version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
            ?>
        </div>
        <?php

    }

    /**
     * Register and enqueue admin-specific stylesheets.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_styles() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'lib/css/admin.css', __FILE__ ), array(), $this->version );

    }

    /**
     * Register and enqueue admin-specific JS.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_scripts() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'lib/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since 1.0.0
     */
    public function settings() {

        // Save form settings if necessary.
        if ( isset( $_POST['tgm-exchange-campaign-monitor-form'] ) && $_POST['tgm-exchange-campaign-monitor-form'] )
            $this->save_form();

        ?>
        <div class="wrap LION">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'Campaign Monitor Settings', 'LION' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'LION' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_campaign_monitor_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="LION-settings">
                <p><?php _e( 'To setup Campaign Monitor in Exchange, fill out the settings below.', 'LION' ); ?></p>
                <form class="tgm-exchange-campaign-monitor-form" action="admin.php?page=it-exchange-addons&add-on-settings=campaign-monitor" method="post">
                    <?php wp_nonce_field( 'LION-form' ); ?>
                    <input type="hidden" name="tgm-exchange-campaign-monitor-form" value="1" />
                    <?php
                       $exchangewp_campaignmonitor_options = get_option( 'it-storage-exchange_addon_campaignmonitor' );
                       $license = $exchangewp_campaignmonitor_options['campaignmonitor_license'];
                       // var_dump($license);
                       $exstatus = trim( get_option( 'exchange_campaignmonitor_license_status' ) );
                       // var_dump($exstatus);
                    ?>
                    <table class="form-table">
                        <tbody>
                            <tr valign="middle">
                                <th scope="row">
                                    <label class="description" for="exchange_campaignmonitor_license_key"><strong><?php _e('Enter your ExchangeWP Campaign Monitor license key'); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-campaignmonitor_license" name="_tgm_exchange_campaign_monitor[campaign-monitor-license-key]" type="text" value="<?php echo $this->get_setting( 'campaign-monitor-license-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your ExchangeWP License Key here.', 'LION' ); ?>" />
                                    <span>
                                        <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
                                            <span style="color:green;"><?php _e('active'); ?></span>
                    			            <?php wp_nonce_field( 'exchange_campaignmonitor_nonce', 'exchange_campaignmonitor_nonce' ); ?>
                    			            <input type="submit" class="button-secondary" name="exchange_campaignmonitor_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
                                        <?php } else {
                                            wp_nonce_field( 'exchange_campaignmonitor_nonce', 'exchange_campaignmonitor_nonce' ); ?>
                                            <input type="submit" class="button-secondary" name="exchange_campaignmonitor_license_activate" value="<?php _e('Activate License'); ?>"/>
                                        <?php } ?>
                                    </span>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-campaign-monitor-api-key"><strong><?php _e( 'Campaign Monitor API Key', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-campaign-monitor-api-key" type="password" name="_tgm_exchange_campaign_monitor[campaign-monitor-api-key]" value="<?php echo $this->get_setting( 'campaign-monitor-api-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Campaign Monitor API Key here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-campaign-monitor-clients"><strong><?php _e( 'Campaign Monitor Client', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-campaign-monitor-client-output">
                                        <?php echo $this->get_campaign_monitor_clients( $this->get_setting( 'campaign-monitor-api-key' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-campaign-monitor-lists"><strong><?php _e( 'Campaign Monitor List', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-campaign-monitor-list-output">
                                        <?php echo $this->get_campaign_monitor_lists( $this->get_setting( 'campaign-monitor-api-key' ), $this->get_setting( 'campaign-monitor-client' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-campaign-monitor-label"><strong><?php _e( 'Campaign Monitor Label', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-campaign-monitor-label" type="text" name="_tgm_exchange_campaign_monitor[campaign-monitor-label]" value="<?php echo $this->get_setting( 'campaign-monitor-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your Campaign Monitor checkbox label here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-campaign-monitor-checked"><strong><?php _e( 'Check Campaign Monitor box by default?', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-campaign-monitor-checked" type="checkbox" name="_tgm_exchange_campaign_monitor[campaign-monitor-checked]" value="<?php echo (bool) $this->get_setting( 'campaign-monitor-checked' ); ?>" <?php checked( $this->get_setting( 'campaign-monitor-checked' ), 1 ); ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Changes', 'LION' ), 'primary button-large', '_tgm_exchange_campaign_monitor[save]' ); ?>
                </form>
            </div>

            <?php do_action( 'it_exchange_campaign_monitor_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php

    }

    /**
    * This is a means of catching errors from the activation method above and displaying it to the customer
    *
    * @since 1.2.2
    */
    public function exchange_campaignmonitor_admin_notices() {
	    if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

		    switch ( $_GET['sl_activation'] ) {

			    case 'false':
				    $message = urldecode( $_GET['message'] );
				    ?>
                    <div class="error">
                        <p><?php echo $message; ?></p>
                    </div>
				    <?php
				    break;

			    case 'true':
			    default:
				    // Developers can put a custom success message here for when activation is successful if they way.
				    break;

		    }
	    }
    }


    /**
     * Saves form field settings for the addon.
     *
     * @since 1.0.0
     */
    public function save_form() {

        // If the nonce is not correct, return an error.
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'LION-form' ) ) {
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'LION' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_campaign_monitor' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_campaign_monitor'] );

	    $settings['campaign-monitor-license-key'] = isset( $new_settings['campaign-monitor-license-key'] ) ? trim( $new_settings['campaign-monitor-license-key'] ) : $settings['campaign-monitor-license-key'];
        $settings['campaign-monitor-api-key'] = isset( $new_settings['campaign-monitor-api-key'] ) ? trim( $new_settings['campaign-monitor-api-key'] ) : $settings['campaign-monitor-api-key'];
        $settings['campaign-monitor-client']  = isset( $new_settings['campaign-monitor-client'] ) ? esc_attr( $new_settings['campaign-monitor-client'] ) : $settings['campaign-monitor-client'];
        $settings['campaign-monitor-list']    = isset( $new_settings['campaign-monitor-list'] ) ? esc_attr( $new_settings['campaign-monitor-list'] ) : $settings['campaign-monitor-list'];
        $settings['campaign-monitor-label']   = isset( $new_settings['campaign-monitor-label'] ) ? esc_html( $new_settings['campaign-monitor-label'] ) : $settings['campaign-monitor-label'];
        $settings['campaign-monitor-checked'] = isset( $new_settings['campaign-monitor-checked'] ) ? 1 : 0;

        // Save the settings and set saved flag to true.
        update_option( 'tgm_exchange_campaign_monitor', $settings );


	    if( isset( $_POST['exchange_campaignmonitor_license_activate'] ) ) {

		    // run a quick security check
		    if( ! check_admin_referer( 'exchange_campaignmonitor_nonce', 'exchange_campaignmonitor_nonce' ) )
			    return; // get out if we didn't click the Activate button

		    // retrieve the license from the database
		    // $license = trim( get_option( 'exchange_campaignmonitor_license_key' ) );
		    $exchangewp_campaignmonitor_options = get_option( 'tgm_exchange_campaign_monitor' );
		    $license = trim( $exchangewp_campaignmonitor_options['campaign-monitor-license-key'] );

		    // data to send in our API request
		    $api_params = array(
			    'edd_action' => 'activate_license',
			    'license'    => $license,
			    'item_name'  => urlencode( 'campaign-monitor' ), // the name of our product in EDD
			    'url'        => home_url()
		    );

		    // Call the custom API.
		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		    // make sure the response came back okay
		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			    if ( is_wp_error( $response ) ) {
				    $message = $response->get_error_message();
			    } else {
				    $message = __( 'An error occurred, please try again.' );
			    }

		    } else {

			    $license_data = json_decode( wp_remote_retrieve_body( $response ) );

			    if ( false === $license_data->success ) {

				    switch( $license_data->error ) {

					    case 'expired' :

						    $message = sprintf(
							    __( 'Your license key expired on %s.' ),
							    date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						    );
						    break;

					    case 'revoked' :

						    $message = __( 'Your license key has been disabled.' );
						    break;

					    case 'missing' :

						    $message = __( 'Invalid license.' );
						    break;

					    case 'invalid' :
					    case 'site_inactive' :

						    $message = __( 'Your license is not active for this URL.' );
						    break;

					    case 'item_name_mismatch' :

						    $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'campaignmonitor' );
						    break;

					    case 'no_activations_left':

						    $message = __( 'Your license key has reached its activation limit.' );
						    break;

					    default :

						    $message = __( 'An error occurred, please try again.' );
						    break;
				    }

			    }

		    }

		    // Check if anything passed on a message constituting a failure
		    if ( ! empty( $message ) ) {
			    $base_url = admin_url( 'admin.php?page=' . 'it-exchange-addons&add-on-settings=campaignmonitor-license' );
			    $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			    wp_redirect( $redirect );
			    exit();
		    }

		    //$license_data->license will be either "valid" or "invalid"
		    update_option( 'exchange_campaignmonitor_license_status', $license_data->license );
            wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=campaignmonitor-license' ) );
		    exit();
	    }

	    // deactivate here
	    // listen for our activate button to be clicked
	    if( isset( $_POST['exchange_campaignmonitor_license_deactivate'] ) ) {

		    // run a quick security check
		    if( ! check_admin_referer( 'exchange_campaignmonitor_nonce', 'exchange_campaignmonitor_nonce' ) )
			    return; // get out if we didn't click the Activate button

		    // retrieve the license from the database
		    // $license = trim( get_option( 'exchange_campaignmonitor_license_key' ) );

		    $exchangewp_campaignmonitor_options = get_option( 'tgm_exchange_campaign_monitor' );
		    $license = $exchangewp_campaignmonitor_options['campaign-monitor-license-key'];


		    // data to send in our API request
		    $api_params = array(
			    'edd_action' => 'deactivate_license',
			    'license'    => $license,
			    'item_name'  => urlencode( 'campaign-monitor' ), // the name of our product in EDD
			    'url'        => home_url()
		    );
		    // Call the custom API.
		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		    // make sure the response came back okay
		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			    if ( is_wp_error( $response ) ) {
				    $message = $response->get_error_message();
			    } else {
				    $message = __( 'An error occurred, please try again.' );
			    }

			    // $base_url = admin_url( 'admin.php?page=' . 'campaignmonitor-license' );
			    // $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			    wp_redirect( admin_url('admin.php?page=it-exchange-addons&add-on-settings=campaignmonitor-license' ) );
			    exit();
		    }

		    // decode the license data
		    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
		    // $license_data->license will be either "deactivated" or "failed"
		    if( $license_data->license == 'deactivated' ) {
			    delete_option( 'exchange_campaignmonitor_license_status' );
		    }

            wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=campaignmonitor-license' ) );
		    exit();

	    }

	    return $this->saved = true;

    }

    /**
     * Ajax callback to retrieve clients for the specified account.
     *
     * @since 1.0.0
     */
    public function clients() {

        // Prepare and sanitize variables.
        $api_key = stripslashes( $_POST['api_key'] );

        // Retrieve the clients and die.
        die( $this->get_campaign_monitor_clients( $api_key ) );

    }

    /**
     * Ajax callback to retrieve lists for the specific account.
     *
     * @since 1.0.0
     */
    public function lists() {

        // Prepare and sanitize variables.
        $api_key   = stripslashes( $_POST['api_key'] );
        $client_id = stripslashes( $_POST['client_id'] );

        // Retrieve the lists and die.
        die( $this->get_campaign_monitor_lists( $api_key, $client_id ) );

    }

    /**
     * Helper flag function to determine if on the addon settings page.
     *
     * @since 1.0.0
     *
     * @return bool True if on the addon page, false otherwise.
     */
    public function is_settings_page() {

        return isset( $_GET['add-on-settings'] ) && 'campaign-monitor' == $_GET['add-on-settings'];

    }

    /**
     * Helper function for retrieving addon settings.
     *
     * @since 1.0.0
     *
     * @param string $setting The setting to look for.
     * @return mixed Addon setting if set, empty string otherwise.
     */
    public function get_setting( $setting = '' ) {

        $settings = get_option( 'tgm_exchange_campaign_monitor' );
        return isset( $settings[$setting] ) ? $settings[$setting] : '';

    }

    /**
     * Helper function to retrieve all available Campaign Monitor clients for the account.
     *
     * @since 1.0.0
     *
     * @param string $api_key The Campaign Monitor API key.
     * @return string An HTML string with clients or empty dropdown.
     */
    public function get_campaign_monitor_clients( $api_key = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no API key, send back an empty placeholder list.
        if ( '' === trim( $api_key ) ) {
            $html .= '<select id="tgm-exchange-campaign-monitor-clients" name="_tgm_exchange_campaign_monitor[campaign-monitor-client]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No clients to select from at this time.', 'LION' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the Campaign Monitor necessary library components.
            if ( ! class_exists( 'CS_Rest_General' ) )
                require_once plugin_dir_path( TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE ) . 'lib/campaign-monitor/csrest_general.php';

            // Load the Campaign Monitor API.
            $campaign_monitor = new CS_Rest_General( array( 'api_key' => $api_key ) );
            $clients          = $campaign_monitor->get_clients();

            // If no clients are returned, send back an error.
            if ( ! $clients ) {
                $html .= '<select id="tgm-exchange-campaign-monitor-clients" class="tgm-exchange-error" name="_tgm_exchange_campaign_monitor[campaign-monitor-client]" disabled="disabled">';
                    $html .= '<option value="none">' . __( 'Invalid credentials. Please try again.', 'LION' ) . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            } else {
                $html .= '<select id="tgm-exchange-campaign-monitor-clients" name="_tgm_exchange_campaign_monitor[campaign-monitor-client]">';
                    foreach ( (array) $clients->response as $client )
                        $html .= '<option value="' . $client->ClientID . '"' . selected( $client->ClientID, $this->get_setting( 'campaign-monitor-client' ), false ) . '>' . $client->Name . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            }
        }

        // Return the HTML string.
        return $html;

    }

    /**
     * Helper function to retrieve all available Campaign Monitor lists for the account.
     *
     * @since 1.0.0
     *
     * @param string $api_key The Campaign Monitor API key.
     * @param string $client_id The client ID in Campaign Monitor.
     * @return string An HTML string with lists or empty dropdown.
     */
    public function get_campaign_monitor_lists( $api_key = '', $client_id = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no API key, send back an empty placeholder list.
        if ( '' === trim( $api_key ) || '' === trim( $client_id ) ) {
            $html .= '<select id="tgm-exchange-campaign-monitor-lists" name="_tgm_exchange_campaign_monitor[campaign-monitor-list]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'LION' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the Campaign Monitor necessary library components.
            if ( ! class_exists( 'CS_Rest_Clients' ) )
                require_once plugin_dir_path( TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE ) . 'lib/campaign-monitor/csrest_clients.php';

            // Load the Campaign Monitor API.
            $client = new CS_Rest_Clients( $client_id, array( 'api_key' => $api_key ) );
            $lists  = $client->get_lists();

            // If no lists are returned, send back an error.
            if ( ! $lists ) {
                $html .= '<select id="tgm-exchange-campaign-monitor-lists" class="tgm-exchange-error" name="_tgm_exchange_campaign_monitor[campaign-monitor-list]" disabled="disabled">';
                    $html .= '<option value="none">' . __( 'Invalid credentials. Please try again.', 'LION' ) . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            } else {
                $html .= '<select id="tgm-exchange-campaign-monitor-lists" name="_tgm_exchange_campaign_monitor[campaign-monitor-list]">';
                    foreach ( (array) $lists->response as $list )
                        $html .= '<option value="' . $list->ListID . '"' . selected( $list->ListID, $this->get_setting( 'campaign-monitor-list' ), false ) . '>' . $list->Name . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            }
        }

        // Return the HTML string.
        return $html;

    }

    /**
     * Adds custom action links to the plugin page.
     *
     * @since 1.0.0
     *
     * @param array $links Default action links.
     * @return array $links Amended action links.
     */
    public function plugin_links( $links ) {

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=campaign-monitor' ) . '" title="' . esc_attr__( 'Setup Add-on', 'LION' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
        return $links;

    }

    /**
     * Outputs the optin checkbox on the appropriate checkout screens.
     *
     * @since 1.0.0
     *
     * @param string $res The password2 field.
     * @return string $res Password2 field with optin code appended.
     */
    public function output_optin( $res ) {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'campaign-monitor-api-key' ) ) || '' === trim( $this->get_setting( 'campaign-monitor-list' ) ) )
            return $res;

        // Build the HTML output of the optin.
        $output = $this->get_optin_output();

        // Append the optin output to the password2 field.
        return $res . $output;

    }

    /**
     * Outputs the optin checkbox on the appropriate guest checkout screens.
     *
     * @since 1.0.0
     */
    public function output_optin_guest() {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'campaign-monitor-api-key' ) ) || '' === trim( $this->get_setting( 'campaign-monitor-list' ) ) )
            return;

        // Build and echo the HTML output of the optin.
        echo $this->get_optin_output();

    }

    /**
     * Processes the optin to the email service.
     *
     * @since 1.0.0
     */
    public function do_optin() {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'campaign-monitor-api-key' ) ) || '' === trim( $this->get_setting( 'campaign-monitor-list' ) ) )
            return;

        // Return early if our $_POST key is not set, no email address is set or the email address is not valid.
        if ( ! isset( $_POST['tgm-exchange-campaign-monitor-signup-field'] ) || empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) )
            return;

        // Load the Campaign Monitor API.
        if ( ! class_exists( 'CS_Rest_Subscribers' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE ) . 'lib/campaign-monitor/csrest_subscribers.php';

        // Load the Campaign Monitor API.
        $campaign_monitor = new CS_Rest_Subscribers( $this->get_setting( 'campaign-monitor-list' ), array( 'api_key' => $this->get_setting( 'campaign-monitor-api-key' ) ) );

        // Prepare optin variables.
        $email      = trim( $_POST['email'] );
        $first_name = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
        $last_name  = ! empty( $_POST['last_name'] )  ? trim( $_POST['last_name'] )  : '';
        $data       = array( 'EmailAddress' => $email, 'Name' => $first_name . ' ' . $last_name, 'Resubscribe' => true );
        $data       = apply_filters( 'tgm_exchange_campaign_monitor_optin_data', $data );

        // Process the optin.
        if ( $data )
            $campaign_monitor->add( $data );

    }

    /**
     * Processes the optin to the email service in a guest checkout.
     *
     * @since 1.0.0
     *
     * @param string $email The guest checkout email address.
     */
    public function do_optin_guest( $email ) {

        // Return early if the appropriate settings are not filled out.
        if ( '' === trim( $this->get_setting( 'campaign-monitor-api-key' ) ) || '' === trim( $this->get_setting( 'campaign-monitor-list' ) ) )
            return;

        // Load the Campaign Monitor API.
        if ( ! class_exists( 'CS_Rest_Subscribers' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_CAMPAIGN_MONITOR_FILE ) . 'lib/campaign-monitor/csrest_subscribers.php';

        // Load the Campaign Monitor API.
        $campaign_monitor = new CS_Rest_Subscribers( $this->get_setting( 'campaign-monitor-list' ), array( 'api_key' => $this->get_setting( 'campaign-monitor-api-key' ) ) );

        // Prepare optin variables.
        $data = array( 'EmailAddress' => $email );
        $data = apply_filters( 'tgm_exchange_campaign_monitor_optin_data', $data );

        // Process the optin.
        if ( $data )
            $campaign_monitor->add( $data );

    }

    /**
     * Generates and returns the optin output.
     *
     * @since 1.0.0
     *
     * @return string $output HTML string of optin output.
     */
    public function get_optin_output() {

        $output  = '<div class="tgm-exchange-campaign-monitor-signup" style="clear:both;">';
            $output .= '<label for="tgm-exchange-campaign-monitor-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-campaign-monitor-signup-field" name="tgm-exchange-campaign-monitor-signup-field" value="' . $this->get_setting( 'campaign-monitor-checked' ) . '"' . checked( $this->get_setting( 'campaign-monitor-checked' ), 1, false ) . ' />' . $this->get_setting( 'campaign-monitor-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_campaign_monitor_output', $output );

        return $output;

    }

}

// Initialize the plugin.
global $tgm_exchange_campaign_monitor;
$tgm_exchange_campaign_monitor = TGM_Exchange_Campaign_Monitor::get_instance();
