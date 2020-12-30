<?php
/**
 * Plugin Name:  WooCommerce Google Ads Dynamic Remarketing
 * Description:  Google Dynamic Retargeting Tracking Tag
 * Author:       Wolf+Bär Agency
 * Plugin URI:   https://wordpress.org/plugins/woocommerce-google-dynamic-retargeting-tag/
 * Author URI:   https://wolfundbaer.ch
 * Version:      1.8.0
 * License:      GPLv2 or later
 * Text Domain:  woocommerce-google-dynamic-retargeting-tag
 * WC requires at least: 3.2.0
 * WC tested up to: 4.0
 */

// TODO add validation for the input fields. Try to use jQuery validation in the form.
// TODO add sanitization to the output
// TODO in case Google starts to use alphabetic characters in the conversion ID, output the conversion ID with ''
// TODO create unit tests


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'PLUGIN_PREFIX', 'wgdr_' );

class WGDR {

	public $conversion_id;
	public $mc_prefix;
	public $product_identifier;
	public $gtag_dactivation;
	public $autoptimize_active;

	const PLUGIN_PREFIX = 'wgdr_';

	public function __construct() {

		// preparing the DB check and upgrade routine
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-db-upgrade.php';

		// running the DB updater
		// add_action( 'plugins_loaded', 'db_upgrade' );
		WgdrDbUpgrade::run_options_db_upgrade();

		$this->runCookieConsentManagement();
	}

	public function runCookieConsentManagement(){

		require_once plugin_dir_path( __FILE__ ) . 'includes/class-cookie-consent-management.php';

		// load the cookie consent management functions
		WgdrCookieConsentManagement::setPluginPrefix( self::PLUGIN_PREFIX );

		// check if third party cookie prevention has been requested
		// if not, run the plugin
		if ( WgdrCookieConsentManagement::is_third_party_cookie_prevention_active() == false ) {

			// startup main plugin functions
			$this->init();

		} else {
			error_log( 'third party cookie prevention active' );
		}
	}


	public function init() {

		// load the options
		$this->wgdr_options_init();

		require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-gtag.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-pixel-v2.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/notifications/class-ask-for-rating.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/notifications/class-v2-pixel-info.php';

        // display admin views
		WgdrAdmin::init();

		// recquired to check if Autoptimize is active 
		add_action( 'plugins_loaded', array( $this, 'include_plugin_php_for_visitors' ) );

		// insert the retargeting code only for visitors of the site
		add_action( 'plugins_loaded', array( $this, 'run_retargeting_for_visitor' ) );

        // ask visitor for rating
		// error_log('init ask');
		WgdrAskForRating::init();

		// show v2 pixel info
		WgdrV2PixelInfo::init();

		// Register style sheet
		// was necessary to fix some styling issues caused by the pixel on some themes
		// removed it, as the new pixel (version Q4 2019) probably doesn't cause that issue anymore
		// add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
	}

	// validate our options
	public function wgdr_plugin_options_validate( $input ) {

		// Create our array for storing the validated options
		$output = $input;

		// validate and sanitize conversion_id

		$needles_cid      = array( 'AW-', '"', );
		$replacements_cid = array( '', '' );

		// clean
		$output['conversion_id'] = wp_strip_all_tags( str_ireplace( $needles_cid, $replacements_cid, $input['conversion_id'] ) );


		// Return the array processing any additional functions filtered by this action
		// return apply_filters( 'sandbox_theme_validate_input_examples', $output, $input );
		return $output;
	}


	// Register css styles for the frontend
	public function register_plugin_styles() {

		wp_register_style( 'wgdr', plugins_url( 'woocommerce-google-dynamic-retargeting-tag/public/css/wgdr-frontend.css' ) );
		wp_enqueue_style( 'wgdr' );
	}


	// set default options at initialization of the plugin
	public function wgdr_options_init() {

		// set options equal to defaults
		global $wgdr_plugin_options;
		$wgdr_plugin_options = get_option( 'wgdr_plugin_options' );


		if ( false === $wgdr_plugin_options ) {

			$wgdr_plugin_options = $this->wgdr_get_default_options();
			update_option( 'wgdr_plugin_options', $wgdr_plugin_options );
		} else {  // Check if each single option has been set. If not, set them. That is necessary when new options are introduced.

			// get default plugins options
			$wgdr_default_plugin_options = $this->wgdr_get_default_options();

			// go through all default options an find out if the key has been set in the current options already
			foreach ( $wgdr_default_plugin_options as $key => $value ) {

				// Test if the key has been set in the options already
				if ( ! array_key_exists( $key, $wgdr_plugin_options ) ) {

					// set the default key and value in the options table
					$wgdr_plugin_options[ $key ] = $value;

					// update the options table with the new key
					update_option( 'wgdr_plugin_options', $wgdr_plugin_options );

				}
			}
		}
	}


	// get the default options for the plugin
	public function wgdr_get_default_options() {
		// default options settings
		$options = array(
			'conversion_id'      => '',
			'mc_prefix'          => '',
			'product_identifier' => 0,
			'gtag_deactivation'  => 0,
		);

		return $options;
	}


	// only include wp-admin/includes/plugin.php for visitors of the site
	public function include_plugin_php_for_visitors() {

		// don't include the code if a shop manager or an admin is logged in
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$this->autoptimize_active = is_plugin_active( 'autoptimize/autoptimize.php' );
		}
	}


	// only run the retargeting code for visitors, not for the admin or shop managers
	public function run_retargeting_for_visitor() {

		// don't load the pixel if a shop manager oder the admin is logged in
		if ( ! current_user_can( 'edit_others_pages' ) ) {

			// get options from db and save them into variables available to this instance
			$this->get_options_from_db();

			// error_log( 'gtag: ' . $this->gtag_deactivation );
			if ( ! class_exists( 'wgact' ) && ( $this->gtag_deactivation == 0 ) ) {

			    WgdrGtag::set_conversion_id($this->conversion_id);
				add_action( 'wp_head', 'WgdrGtag::inject' );
			}

			add_action( 'wp_footer', 'WgdrPixelV2::google_dynamic_retargeting_code' );
		}
	}

	// get options from db
	public function get_options_from_db() {
		// get options from db
		$options = get_option( 'wgdr_plugin_options' );

		// set options variables
		$this->conversion_id      = $options['conversion_id'];
		$this->mc_prefix          = $options['mc_prefix'];
		$this->product_identifier = $options['product_identifier'];
		$this->gtag_deactivation  = $options['gtag_deactivation'];
	}
}

$wgdr = new WGDR();