<?php
/**
 * Plugin Name:  WooCommerce AdWords Dynamic Remarketing
 * Plugin URI:   https://wordpress.org/plugins/woocommerce-google-dynamic-retargeting-tag/
 * Description:  Google Dynamic Retargeting Tracking Tag
 * Author:       Wolf+Bär Agency
 * Author URI:   https://wolfundbaer.ch
 * Version:      1.4.6
 * License:      GPLv2 or later
 * Text Domain:  woocommerce-google-dynamic-retargeting-tag
 * WC requires at least: 2.6.0
 * WC tested up to: 3.4.0
 */

// TODO add validation for the input fields. Try to use jQuery validation in the form.
// TODO add sanitization to the output
// TODO in case Google starts to use alphabetic characters in the conversion ID, output the conversion ID with ''


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WGDR {

	public $conversion_id;
	public $mc_prefix;
	public $product_identifier;
	public $autoptimize_active;

	public function __construct() {

		// preparing the DB check and upgrade routine
		// require_once plugin_dir_path( __FILE__ ) . 'includes/db_upgrade.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-db-upgrade.php';

		// running the DB updater
		// add_action( 'plugins_loaded', 'db_upgrade' );
		$db_upgrade = new WGDR_DB_Upgrade();
		$db_upgrade->run_options_db_upgrade();

		// startup main plugin functions
		$this->init();
	}

	public function init() {

		// load the options
		$this->wgdr_options_init();

		// add the admin options page
		add_action( 'admin_menu', array( $this, 'wgdr_plugin_admin_add_page' ), 100 );

		// add the admin settings and such
		add_action( 'admin_init', array( $this, 'wgdr_plugin_admin_init' ) );

		// add a settings link on the plugins page
		add_filter( 'plugin_action_links', array( $this, 'wgdr_settings_link' ), 10, 2 );

		// load textdomain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// recquired to check if Autoptimize is active 
		add_action( 'plugins_loaded', array( $this, 'include_plugin_php_for_visitors' ) );

		// insert the retargeting code only for visitors of the site
		add_action( 'plugins_loaded', array( $this, 'run_retargeting_for_visitor' ) );

		// ask for a rating in a plugin notice
		add_action( 'admin_head', array( $this, 'ask_for_rating_js' ) );
		add_action( 'wp_ajax_wgdr_dismissed_notice_handler', array( $this, 'ajax_rating_notice_handler' ) );
		add_action( 'admin_notices', array( $this, 'ask_for_rating_notices_if_not_asked_before' ) );

		// Register style sheet
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );

	}

    // Register css styles for the frontend
	public function register_plugin_styles(){

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
		);

		return $options;
	}


	// client side ajax js handler for the admin rating notice
	public function ask_for_rating_js() {

		?>
        <script type="text/javascript">
            jQuery(document).on('click', '.notice-success.wgdr-rating-success-notice, .wgdr-rating-link, .wgdr-rating-support', function ($) {

                var data = {
                    'action': 'wgdr_dismissed_notice_handler',
                };

                jQuery.post(ajaxurl, data);
                jQuery('.wgdr-rating-success-notice').remove();

            });
        </script> <?php

	}


	// server side php ajax handler for the admin rating notice
	public function ajax_rating_notice_handler() {

	    error_log( ' running php handler ');

		// prepare the data that needs to be written into the user meta
		$wgdr_admin_notice_user_meta = array(
			'date-dismissed' => date( 'Y-m-d' ),
		);

		// update the user meta
		update_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', $wgdr_admin_notice_user_meta );

		wp_die(); // this is required to terminate immediately and return a proper response
	}



	// only ask for rating if not asked before or longer than a year
	public function ask_for_rating_notices_if_not_asked_before() {

		// get user meta data for this plugin
		$user_meta = get_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta' );

		// check if there is already a saved value in the user meta
		if ( isset( $user_meta[0]['date-dismissed'] ) ) {

			$date_1 = date_create( $user_meta[0]['date-dismissed'] );
			$date_2 = date_create( date( 'Y-m-d' ) );

			// calculate day difference between the dates
			$interval = date_diff( $date_1, $date_2 );

			// check if the date difference is more than 360 days
			if ( 360 < $interval->format( '%a' ) ) {
				$this->ask_for_rating_notices();
			}

		} else {

			$this->ask_for_rating_notices();
		}
	}



	// show an admin notice to ask for a plugin rating
	public function ask_for_rating_notices() {

		// source: https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/
		// source: https://wordpress.stackexchange.com/questions/191479/how-to-save-dismissable-notice-state-in-wp-4-2
		// source: https://codex.wordpress.org/AJAX_in_Plugins
		// source: http://api.jquery.com/jquery.ajax/
		// https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices

		$current_user = wp_get_current_user();

		?>
        <div class="notice notice-success is-dismissible wgdr-rating-success-notice">
            <p>
                <span><?php _e( 'Hi ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
                <span><?php echo( $current_user->user_firstname ? $current_user->user_firstname : $current_user->nickname ); ?></span>
                <span><?php _e( '! ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
                <span><?php _e( 'You\'ve been using the ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
                <span><b><?php _e( 'WGDR AdWords Dynamic Retargeting Plugin', 'woocommerce-google-dynamic-retargeting-tag' ); ?></b></span>
                <span><?php _e( ' for a while now. If you like the plugin please support our development by leaving a ★★★★★ rating: ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
                <span class="wgdr-rating-link">
                    <a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-google-dynamic-retargeting-tag?rate=5#postform"
                       target="_blank"><?php _e( 'Rate it!', 'woocommerce-google-dynamic-retargeting-tag' ); ?></a>
                </span>
            </p>
            <p>
                <span><?php _e( 'Or else, please leave us a support question in the forum. We\'ll be happy to assist you: ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
                <span class="wgdr-rating-support">
                    <a href="https://wordpress.org/support/plugin/woocommerce-google-dynamic-retargeting-tag"
                       target="_blank"><?php _e( 'Get support', 'woocommerce-google-dynamic-retargeting-tag' ); ?></a>
                </span>
            </p>
        </div>
		<?php

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
			add_action( 'wp_footer', array( $this, 'google_dynamic_retargeting_code' ) );
		}
	}


	// Load text domain function
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-google-dynamic-retargeting-tag', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	// adds a link on the plugins page for the wgdr settings
	function wgdr_settings_link( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$links[] = '<a href="' . admin_url( "admin.php?page=wgdr" ) . '">' . __( 'Settings' ) . '</a>';
		}

		return $links;
	}


	/**
	 * GDR plugin settings page
	 **/

	// add the admin options page
	function wgdr_plugin_admin_add_page() {
		add_submenu_page(
			'woocommerce',                                                                              // $page_title
			esc_html__( 'AdWords Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),  // $menu_title
			esc_html__( 'AdWords Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),  // $menu_title
			'manage_options',                                                                           // $capability
			'wgdr',                                                                                     // $menu_slug
			array(
				$this,
				'wgdr_plugin_options_page',                                                             // callback
			) );
	}

	// display the admin options page
	function wgdr_plugin_options_page() {

		// Throw a warning if WooCommerce is disabled.
		//if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		//	echo '<div><h1><font color="red"><b>WooCommerce not active -> tag insertion disabled !</b></font></h1></div>';
		//}

		?>

        <br>
        <div style="width:980px; float: left; margin: 5px">
            <div style="float:left; margin: 5px; margin-right:20px; width:750px">
                <div
                        style="background: #0073aa; padding: 10px; font-weight: bold; color: white; border-radius: 2px"><?php esc_html_e( 'Google AdWords Dynamic Retargeting Tag Settings', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
                </div>
                <form action="options.php" method="post">
					<?php settings_fields( 'wgdr_plugin_options_settings_fields' ); ?>
					<?php do_settings_sections( 'wgdr' ); ?>
                    <br>
                    <table class="form-table" style="margin: 10px">
                        <tr>
                            <th scope="row" style="white-space: nowrap">
                                <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"
                                       class="button button-primary"/>
                            </th>
                        </tr>
                    </table>
                </form>
                <br>
                <div
                        style="background: #0073aa; padding: 10px; font-weight: bold; color: white; margin-bottom: 20px; border-radius: 2px">
					<span>
						<?php esc_html_e( 'Profit Driven Marketing by Wolf+Bär', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
					</span>
                    <span style="float: right;">
						<a href="https://wolfundbaer.ch/"
                           target="_blank" style="color: white">
							<?php esc_html_e( 'Visit us here: https://wolfundbaer.ch', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
						</a>
					</span>
                </div>
            </div>
            <div style="float: left; margin: 5px">
                <a href="https://wordpress.org/plugins/woocommerce-google-adwords-conversion-tracking-tag/"
                   target="_blank">
                    <img src="<?php echo( plugins_url( 'images/wgact-icon-256x256.png', __FILE__ ) ) ?>" width="150px"
                         height="150px">
                </a>
            </div>
            <div style="float: left; margin: 5px">
                <a href="https://wordpress.org/plugins/woocommerce-google-dynamic-retargeting-tag/" target="_blank">
                    <img src="<?php echo( plugins_url( 'images/wgdr-icon-256x256.png', __FILE__ ) ) ?>" width="150px"
                         height="150px">
                </a>
            </div>
        </div>


		<?php
	}

	// add the admin settings and such
	function wgdr_plugin_admin_init() {

		// register settings
		register_setting( 'wgdr_plugin_options_settings_fields', 'wgdr_plugin_options' );

		// add settings section
		add_settings_section( 'wgdr_plugin_main', esc_html__( 'Settings', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_section_text',
		), 'wgdr' );

		// add settings fields

		// settings field for the conversion ID
		add_settings_field( 'wgdr_plugin_option_conversion_id', esc_html__( 'Conversion ID', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_option_conversion_id',
		), 'wgdr', 'wgdr_plugin_main' );

		// settings field for the Google Merchant Center Prefix
		add_settings_field( 'wgdr_plugin_option_mc_prefix', esc_html__( 'Google Merchant Center Prefix', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_option_mc_prefix',
		), 'wgdr', 'wgdr_plugin_main' );

		// add fields for the product identifier
		add_settings_field(
			'wgdr_plugin_option_product_identifier',
			esc_html__(
				'Product Identifier',
				'woocommerce-google-dynamic-retargeting-tag'
			),
			array(
				$this,
				'wgdr_plugin_option_product_identifier',
			),
			'wgdr',
			'wgdr_plugin_main'
		);
	}

	public function wgdr_plugin_section_text() {
		// echo '<p>WooCommerce Google Dynamic Retargeting tag settings.</p>';
	}

	public function wgdr_plugin_option_conversion_id() {
		$options = get_option( 'wgdr_plugin_options' );
		echo "<input id='wgdr_plugin_option_conversion_id' name='wgdr_plugin_options[conversion_id]' size='40' type='text' value='{$options['conversion_id']}' /><br>" . esc_html__( 'Under the following link you will find instructions how to get the Conversion ID: ', 'woocommerce-google-dynamic-retargeting-tag' ) . "<a href=\"https://support.google.com/adwords/answer/2476688\" target=\"_blank\">" . esc_html__( 'Get your remarketing tag code', 'woocommerce-google-dynamic-retargeting-tag' ) . "</a>";
		//esc_html_e( '', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	public function wgdr_plugin_option_mc_prefix() {
		$options = get_option( 'wgdr_plugin_options' );
		echo "<input id='wgdr_plugin_option_mc_prefix' name='wgdr_plugin_options[mc_prefix]' size='40' type='text' value='{$options['mc_prefix']}' /><br>" . esc_html__( 'If you use the WooCommerce Google Product Feed Plugin from WooThemes the value here should be "woocommerce_gpf_"', 'woocommerce-google-dynamic-retargeting-tag' ) . " (<a href='http://www.woothemes.com/products/google-product-feed/' target='_blank'>WooCommerce Google Product Feed Plugin</a>). " . esc_html__( 'If you use any other plugin for the feed you can leave this field empty.', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	public function wgdr_plugin_option_product_identifier() {
		$options = get_option( 'wgdr_plugin_options' );
		?>
		<input type='radio' id='wgdr_plugin_option_product_identifier_0' name='wgdr_plugin_options[product_identifier]' value='0' <?php echo( checked( 0, $options['product_identifier'], false ) ) ?>/><?php _e( 'post id (default)', 'woocommerce-google-dynamic-retargeting-tag') ?><br>
		<input type='radio' id='wgdr_plugin_option_product_identifier_1' name='wgdr_plugin_options[product_identifier]' value='1' <?php echo( checked( 1, $options['product_identifier'], false ) ) ?>/><?php _e('SKU', 'woocommerce-google-dynamic-retargeting-tag') ?><br><br>
		<?php echo( esc_html__( 'Choose a product identifier.', 'woocommerce-google-dynamic-retargeting-tag' ) ); ?>
        <?php
	}


	// Google Dynamic Retargeting tag
	public function google_dynamic_retargeting_code() {

		global $woocommerce;

		// get options from db and save them into variables available to this instance
		$this->get_options_from_db();

		// insert noptimize tag if Autoptimze is active 
		if ( $this->autoptimize_active == true ) {
			echo "<!--noptimize-->";
		}
		?>


        <!-- START Google Code for Dynamic Retargeting --><?php

		// Check if is homepage and set home paramters.
		// is_home() doesn't work in my setup. I don't know why. I'll use is_front_page() as workaround
		if ( is_front_page() ) {
			?>

            <script type="text/javascript">
                var google_tag_params = {
                    ecomm_pagetype: 'home'
                };
            </script>
			<?php
		} // Check if it is a product category page and set the category parameters.
        elseif ( is_product_category() ) {
			$product_id = get_the_ID();
			?>

            <script type="text/javascript">
                var google_tag_params = {
                    ecomm_pagetype: 'category',
                    ecomm_category: <?php echo( json_encode( $this->get_product_category( $product_id ) ) ); ?>
                };
            </script>
			<?php
		} // Check if it a search results page and set the searchresults parameters.
        elseif ( is_search() ) {
			?>

            <script type="text/javascript">
                var google_tag_params = {
                    ecomm_pagetype: 'searchresults'
                };
            </script>
			<?php
		} // Check if it is a product page and set the product parameters.
        elseif ( is_product() ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );

			if( is_bool( $product ) ){
			    error_log( 'WooCommerce detects the page ID ' . $product_id . ' as product, but when invoked by wc_get_product( ' . $product_id . ' ) it returns no product object');
			    return;
            }

			$product_id_code = '
		<script type="text/javascript">
			var google_tag_params = {
				ecomm_prodid: ' . json_encode( $this->mc_prefix . ( 0 == $this->product_identifier ? get_the_ID() : $product->get_sku() ) ) . ',
				ecomm_category: ' . json_encode( $this->get_product_category( $product_id ) ) . ',
				ecomm_pagetype: \'product\',
				ecomm_totalvalue: ' . $product->get_price() . '
			};
		</script>';

			// apply filter to product id
			$product_id_code = apply_filters( 'wgdr_filter', $product_id_code, 'product_id_code', $product_id );

			echo $product_id_code;


		} // Check if it is the cart page and set the cart parameters.
        elseif ( is_cart() ) {
			$cartprods = $woocommerce->cart->get_cart();
			?>

            <script type="text/javascript">
                var google_tag_params = {
                    ecomm_prodid: <?php echo( json_encode( $this->get_cart_product_ids( $cartprods ) ) );?>,
                    ecomm_pagetype: 'cart',
                    ecomm_totalvalue: <?php echo WC()->cart->get_cart_contents_total() ; ?>
                };
            </script>
			<?php
		} // Check if it the order received page and set the according parameters
        elseif ( is_order_received_page() ) {

	        $order_key      = $_GET['key'];
	        $order          = new WC_Order( wc_get_order_id_by_order_key( $order_key ) );
	        $order_subtotal = $order->get_subtotal();
	        $order_subtotal = $order_subtotal - $order->get_total_discount();

			// Only run conversion script if the payment has not failed. (has_status('completed') is too restrictive)
			// And use the order meta to check if the conversion code has already run for this order ID. If yes, don't run it again.
			if ( ! $order->has_status( 'failed' ) && ( ( get_post_meta( $order->get_order_number(), '_WGDR_conversion_pixel_fired', true ) == "true" ) ) ) {

					?>

                <script type="text/javascript">
                    var google_tag_params = {
                        ecomm_prodid: <?php echo( json_encode( $this->get_content_ids( $order ) ) ); ?>,
                        ecomm_pagetype: 'purchase',
                        ecomm_totalvalue: <?php echo $order_subtotal; ?>

                    };
                </script>
				<?php
				update_post_meta( $order->get_order_number(), '_WGDR_conversion_pixel_fired', 'true' );
			} // end if order status
		} // For all other pages set the parameters for other.
		else {
			?>

            <script type="text/javascript">
                var google_tag_params = {
                    ecomm_pagetype: 'other'
                };
            </script>
			<?php
		}

		?>

        <script type="text/javascript">
            /* <![CDATA[ */
            var google_conversion_id = <?php echo json_encode( $this->conversion_id, JSON_NUMERIC_CHECK ); ?>;
            var google_custom_params = window.google_tag_params;
            var google_remarketing_only = true;
            /* ]]> */
        </script>
        <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
        </script>
        <noscript>
            <div style="display:inline;">
                <img height="1" width="1" style="border-style:none;" alt=""
                     src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/<?php echo $this->conversion_id; ?>/?value=0&guid=ON&script=0"/>
            </div>
        </noscript>
        <!-- END Google Code for Dynamic Retargeting -->

		<?php

		if ( $this->autoptimize_active == true ) {
			echo "<!--/noptimize-->";
		}
	}


	public function get_options_from_db() {
		// get options from db
		$options = get_option( 'wgdr_plugin_options' );

		// set options variables
		$this->conversion_id      = $options['conversion_id'];
		$this->mc_prefix          = $options['mc_prefix'];
		$this->product_identifier = $options['product_identifier'];
	}

	// get an array with all product categories

	public function get_product_category( $product_id ) {

		$prod_cats        = get_the_terms( $product_id, 'product_cat' );
		$prod_cats_output = array();

        // only continue with the loop if one or more product categories have been set for the product
		if ( ! empty( $prod_cats ) ) {
			foreach ( (array) $prod_cats as $k1 ) {
				array_push( $prod_cats_output, $k1->name );
			}

			// apply filter to the $prod_cats_output array
			$prod_cats_output = apply_filters( 'wgdr_filter', $prod_cats_output, 'prod_cats_output' );
		}

		return $prod_cats_output;
	}

	// get an array with all cart product ids
	public function get_cart_product_ids( $cartprods ) {

		// initiate product identifier array
		$cartprods_items = array();

		// go through the array and get all product identifiers
		foreach ( (array) $cartprods as $entry ) {

			// depending on setting use product IDs or SKUs
			if ( 0 == $this->product_identifier ) {

				// fill the array with all product IDs
				array_push( $cartprods_items, $this->mc_prefix . $entry['product_id'] );

			} else {

				// fill the array with all product SKUs
				$product = wc_get_product( $entry['product_id'] );
				array_push( $cartprods_items, $this->mc_prefix . $product->get_sku() );

			}
		}


		// apply filter to the $cartprods_items array
		$cartprods_items = apply_filters( 'wgdr_filter', $cartprods_items, 'cartprods_items' );

		return $cartprods_items;
	}

	// get an array with all product ids in the order
	public function get_content_ids( $order ) {

		$order_items       = $order->get_items();
		$order_items_array = array();

		foreach ( (array) $order_items as $item ) {
			//array_push( $order_items_array, $this->mc_prefix . $item['product_id'] );

			// depending on setting use product IDs or SKUs
			if ( 0 == $this->product_identifier ) {

				// fill the array with all product IDs
				array_push( $order_items_array, $this->mc_prefix . $item['product_id'] );

			} else {

				// fill the array with all product SKUs
				$product = wc_get_product( $item['product_id'] );
				array_push( $order_items_array, $this->mc_prefix . $product->get_sku() );

			}
		}

		// apply filter to the $order_items_array array
		$order_items_array = apply_filters( 'wgdr_filter', $order_items_array, 'order_items_array' );

		return $order_items_array;
	}
}

$wgdr = new WGDR();