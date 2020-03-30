<?php
/**
 * Plugin Name:  WooCommerce AdWords Dynamic Remarketing
 * Plugin URI:   https://wordpress.org/plugins/woocommerce-google-dynamic-retargeting-tag/
 * Description:  Google Dynamic Retargeting Tracking Tag
 * Author:       Wolf + Bär GmbH
 * Author URI:   http://www.wolfundbaer.ch
 * Version:      1.0.9
 * License:      GPLv2 or later
 * Text Domain:  woocommerce-google-dynamic-retargeting-tag
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WGDR {

	public $conversion_id;
	public $conversion_label;
	public $mc_prefix;

	public function __construct() {

		// startup all functions 
		$this->init();
	}

	public function init() {

		// load the options
		add_action( 'activate_wgdr/wgdr.php', array( 'wgdr', 'wgdr_options_init' ) );

		// add the admin options page
		add_action( 'admin_menu', array( $this, 'wgdr_plugin_admin_add_page' ), 100 );

		// add the admin settings and such
		add_action( 'admin_init', array( $this, 'wgdr_plugin_admin_init' ) );

		// add a settings link on the plugins page
		add_filter( 'plugin_action_links', array( $this, 'wgdr_settings_link' ), 10, 2 );

		// Load textdomain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// insert the retargeting code only for visitors of the site
		add_action( 'plugins_loaded', array( $this, 'run_retargeting_for_visitor' ) );
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
			$links[] = '<a href="' . admin_url( "admin.php?page=do_wgdr" ) . '">' . __( 'Settings' ) . '</a>';
		}

		return $links;
	}

	// get the default options for the plugin
	public function wgdr_get_default_options() {
		// default options settings
		$options = array(
			'conversion_ID'            => 'test_id',
			'conversion_label'         => 'test_label',
			'GMC_prefix'               => 'test_prefix',
			'custom_parameters_switch' => true,
		);

		return $options;
	}

	// set default options at initialization of the plugin
	function wgdr_options_init() {
		// set options equal to defaults
		global $wgdr_options;
		$wgdr_options = get_option( 'wgdr_options' );
		if ( false === $wgdr_options ) {
			$wgdr_options = wgdr_get_default_options();
		}
		update_option( 'wgdr_options', $wgdr_options );
	}


	/**
	 * GDR plugin settings page
	 **/

	// add the admin options page
	function wgdr_plugin_admin_add_page() {
		add_submenu_page(
			'woocommerce',                                // $page_title
			esc_html__( 'AdWords Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),            // $menu_title
			esc_html__( 'AdWords Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),            // $menu_title
			'manage_options',                            // $capability
			'do_wgdr',                                    // $menu_slug
			array(
				$this,
				'wgdr_plugin_options_page',    // callback
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
		<div style="background: #eee; width: 772px">
			<div
				style="background: #ccc; padding: 10px; font-weight: bold"><?php esc_html_e( 'AdWords Dynamic Retargeting Tag Settings', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
				Google
			</div>
			<form action="options.php" method="post">
				<?php settings_fields( 'wgdr_plugin_options' ); ?>
				<?php do_settings_sections( 'do_wgdr' ); ?>
				<br>
				<table class="form-table" style="margin: 10px">
					<tr>
						<th scope="row" style="white-space: nowrap">
							<input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" class="button"/>
						</th>
					</tr>
				</table>
			</form>
		</div>
		<br>

		<div style="background: #eee; width: 772px">
			<div
				style="background: #ccc; padding: 10px; font-weight: bold"><?php esc_html_e( 'Donation', 'woocommerce-google-dynamic-retargeting-tag' ) ?></div>
			<table class="form-table" style="margin: 10px">
				<tr>
					<th scope="row">
						<div
							style="padding: 10px"><?php esc_html_e( 'This plugin was developed by', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
							<a href="http://www.wolfundbaer.ch" target="_blank">Wolf + Bär GmbH</a>
							<p><?php esc_html_e( 'Buy me a beer if you like the plugin.', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
								<br>
								<?php esc_html_e( 'If you want me to continue developing the plugin buy me a few more beers. Although, I probably will continue to develop the plugin anyway. It would be just much more fun if I had a few beers to celebrate my milestones.', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
						</div>

						<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
							<input type="hidden" name="cmd" value="_s-xclick">
							<input type="hidden" name="hosted_button_id" value="UE3D2AW8YTML8">
							<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif"
							       border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
							<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1"
							     height="1">
						</form>
					</th>
				</tr>
			</table>
		</div>
		<?php
	}

	// add the admin settings and such
	function wgdr_plugin_admin_init() {
		//register_setting( 'plugin_options', 'plugin_options', array($this,'wgdr_plugin_options_validate' ) );

		register_setting( 'wgdr_plugin_options', 'wgdr_plugin_options_1' );
		//register_setting( 'wgdr_plugin_options', 'wgdr_plugin_options_2');
		register_setting( 'wgdr_plugin_options', 'wgdr_plugin_options_3' );
		add_settings_section( 'wgdr_plugin_main', esc_html__( 'Main Settings', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_section_text'
		), 'do_wgdr' );
		//add_settings_section( 'wgdr_plugin_main', 'WGDR Main Settings', 'wgdr_plugin_section_text', 'do_wgdr' );
		add_settings_field( 'wgdr_plugin_text_string_1', esc_html__( 'Conversion ID', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_setting_string_1'
		), 'do_wgdr', 'wgdr_plugin_main' );
		//add_settings_field( 'wgdr_plugin_text_string_2', 'Conversion label', array($this,'wgdr_plugin_setting_string_2'), 'do_wgdr', 'wgdr_plugin_main');
		add_settings_field( 'wgdr_plugin_text_string_3', esc_html__( 'Google Merchant Center prefix', 'woocommerce-google-dynamic-retargeting-tag' ), array(
			$this,
			'wgdr_plugin_setting_string_3'
		), 'do_wgdr', 'wgdr_plugin_main' );
	}

	function wgdr_plugin_section_text() {
		// echo '<p>WooCommerce Google Dynamic Retargeting tag settings.</p>';
	}

	/*
	function wgdr_plugin_setting_string_1() {
		$options = get_option('wgdr_plugin_options_1');
		echo "<input id='wgdr_plugin_text_string_1' name='wgdr_plugin_options_1[text_string]' size='40' type='text' value='{$options['text_string']}' />";
	}
	*/

	function wgdr_plugin_setting_string_1() {
		$options = get_option( 'wgdr_plugin_options_1' );
		echo "<input id='wgdr_plugin_text_string_1' name='wgdr_plugin_options_1[text_string]' size='40' type='text' value='{$options['text_string']}' /><br>" . esc_html__( 'Under the following link you will find instructions how to get the Conversion ID: ', 'woocommerce-google-dynamic-retargeting-tag' ) . "<a href=\"https://support.google.com/adwords/answer/2476688\" target=\"_blank\">" . esc_html__( 'Get your remarketing tag code', 'woocommerce-google-dynamic-retargeting-tag' ) . "</a>";
		//esc_html_e( '', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	function wgdr_plugin_setting_string_2() {
		$options = get_option( 'wgdr_plugin_options_2' );
		echo "<input id='wgdr_plugin_text_string_2' name='wgdr_plugin_options_2[text_string]' size='40' type='text' value='{$options['text_string']}' /><br>This field is <u>optional</u>. Leave it empty if you don't use a customized AdWords retargeting tag.";
	}

	function wgdr_plugin_setting_string_3() {
		$options = get_option( 'wgdr_plugin_options_3' );
		echo "<input id='wgdr_plugin_text_string_3' name='wgdr_plugin_options_3[text_string]' size='40' type='text' value='{$options['text_string']}' /><br>" . esc_html__( 'If you use the WooCommerce Google Product Feed Plugin from WooThemes the value here should be "woocommerce_gpf_"', 'woocommerce-google-dynamic-retargeting-tag' ) . " (<a href='http://www.woothemes.com/products/google-product-feed/' target='_blank'>WooCommerce Google Product Feed Plugin</a>). " . esc_html__( 'If you use any other plugin for the feed you can leave this field empty.', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	// validate our options
	function wgdr_plugin_options_validate( $input ) {
		$newinput['text_string'] = trim( $input['text_string'] );
		if ( ! preg_match( '/^[a-z0-9]{32}$/i', $newinput['text_string'] ) ) {
			$newinput['text_string'] = '';
		}

		return $newinput;
	}

	private function get_conversion_id() {
		$opt           = get_option( 'wgdr_plugin_options_1' );
		$conversion_id = $opt['text_string'];

		return $conversion_id;
	}

	private function get_conversion_label() {
		$opt              = get_option( 'wgdr_plugin_options_2' );
		$conversion_label = $opt['text_string'];

		return $conversion_label;
	}

	private function get_mc_prefix() {
		$opt       = get_option( 'wgdr_plugin_options_3' );
		$mc_prefix = $opt['text_string'];

		return $mc_prefix;
	}

	// Google Dynamic Retargeting tag
	public function google_dynamic_retargeting_code() {

		global $woocommerce;

		$opt                    = get_option( 'wgdr_plugin_options_1' );
		$this->conversion_id    = $opt['text_string'];
		$this->conversion_label = $this->get_conversion_label();
		$this->mc_prefix        = $this->get_mc_prefix();

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
			$product_category_id = get_the_ID();
			?>

			<script type="text/javascript">
				var google_tag_params = {
					ecomm_pagetype: 'category',
					ecomm_category: <?php echo( json_encode( $this->get_product_category( $product_category_id ) ) ); ?>
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
			$product    = get_product( $product_id );
			?>

			<script type="text/javascript">
				var google_tag_params = {
					ecomm_prodid: <?php echo( json_encode( $this->mc_prefix . get_the_ID() ) ); ?>,
					ecomm_category: <?php echo( json_encode( $this->get_product_category( $product_id ) ) ); ?>,
					ecomm_pagetype: 'product',
					ecomm_totalvalue: <?php echo( $product->get_price() ); ?>
				};
			</script>
			<?php
		} // Check if it is the cart page and set the cart parameters.
		elseif ( is_cart() ) {
			$cartprods = $woocommerce->cart->get_cart();
			?>

			<script type="text/javascript">
				var google_tag_params = {
					ecomm_prodid: <?php echo( json_encode( $this->get_cart_product_ids( $cartprods ) ) );?>,
					ecomm_pagetype: 'cart',
					ecomm_totalvalue: <?php echo $woocommerce->cart->cart_contents_total; ?>
				};
			</script>
			<?php
		} // Check if it the order received page and set the according parameters
		elseif ( is_order_received_page() ) {

			$order_key   = $_GET['key'];
			$order       = new WC_Order( wc_get_order_id_by_order_key( $order_key ) );
			$order_total = $order->get_total();
			$items       = $order->get_items();

			$order_items = array();

			// Only run conversion script if the payment has not failed. (has_status('completed') is too restrictive)
			// And use the order meta to check if the conversion code has already run for this order ID. If yes, don't run it again.
			if ( ! $order->has_status( 'failed' ) && ( ( get_post_meta( $order->id, '_WGDR_conversion_pixel_fired', true ) != "true" ) ) ) {
				?>

				<script type="text/javascript">
					var google_tag_params = {
						ecomm_prodid: <?php echo( json_encode( $this->get_content_ids( $order ) ) ); ?>,
						ecomm_pagetype: 'purchase',
						ecomm_totalvalue: <?php echo $order_total; ?>

					};
				</script>
				<?php
				update_post_meta( $order->id, '_WGDR_conversion_pixel_fired', 'true' );
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
			var google_conversion_id = <?php echo $this->conversion_id; ?>;
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
	}

	// get an array with all product categories
	public function get_product_category( $product_id ) {

		$prod_cats        = get_the_terms( $product_id, 'product_cat' );
		$prod_cats_output = array();

		// add all categories to an array
		foreach ( (array) $prod_cats as $k1 ) {
			array_push( $prod_cats_output, $k1->name );
		}

		return $prod_cats_output;
	}

	// get an array with all cart product ids
	public function get_cart_product_ids( $cartprods ) {

		$cartprods_items = array();

		foreach ( (array) $cartprods as $entry ) {
			array_push( $cartprods_items, $this->mc_prefix . $entry['product_id'] );
		}

		return $cartprods_items;
	}

	// get an array with all product ids in the order
	public function get_content_ids( $order ) {

		$order_items       = $order->get_items();
		$order_items_array = array();

		foreach ( (array) $order_items as $item ) {
			array_push( $order_items_array, $this->mc_prefix . $item['product_id'] );
		}

		return $order_items_array;
	}
}

$wgdr = new WGDR();
