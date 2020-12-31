<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WgdrAdmin{

	public static function init() {

		// add the admin options page
		add_action( 'admin_menu', 'WgdrAdmin::wgdr_plugin_admin_add_page', 100 );

		// add the admin settings and such
		add_action( 'admin_init', 'WgdrAdmin::wgdr_plugin_admin_init' );

		// add a settings link on the plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WgdrAdmin::wgdr_settings_link' );

		// load textdomain
		add_action( 'init', 'WgdrAdmin::load_plugin_textdomain' );

	}

	/**
	 * GDR plugin settings page
	 **/

	// add the admin options page
	public static function wgdr_plugin_admin_add_page() {
		add_submenu_page(
			'woocommerce',                                                                                  // $page_title
			esc_html__( 'Google Ads Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),   // $menu_title
			esc_html__( 'Google Ads Dynamic Retargeting', 'woocommerce-google-dynamic-retargeting-tag' ),   // $menu_title
			'manage_options',                                                                               // $capability
			'wgdr',                                                                                         // $menu_slug
            'WgdrAdmin::wgdr_plugin_options_page'                                                           // callback
        );
	}

	// display the admin options page
	public static function wgdr_plugin_options_page() {

		// Throw a warning if WooCommerce is disabled.
		//if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		//	echo '<div><h1><font color="red"><b>WooCommerce not active -> tag insertion disabled !</b></font></h1></div>';
		//}

		?>

		<br>
		<div style="width:980px; float: left; margin: 5px">
			<div style="float:left; margin: 5px; margin-right:20px; width:750px">
				<div
					style="background: #0073aa; padding: 10px; font-weight: bold; color: white; border-radius: 2px"><?php esc_html_e( 'Google Ads Dynamic Retargeting Tag Settings', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
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
					<img src="<?php echo( plugins_url( '../images/wgact-icon-256x256.png', __FILE__ ) ) ?>" width="150px"
					     height="150px">
				</a>
			</div>
			<div style="float: left; margin: 5px">
				<a href="https://wordpress.org/plugins/woocommerce-google-dynamic-retargeting-tag/" target="_blank">
					<img src="<?php echo( plugins_url( '../images/wgdr-icon-256x256.png', __FILE__ ) ) ?>" width="150px"
					     height="150px">
				</a>
			</div>
		</div>


		<?php
	}

	// add the admin settings and such
	public static function wgdr_plugin_admin_init() {

		// register settings
		// register_setting( 'wgdr_plugin_options_settings_fields', 'wgdr_plugin_options' );
		register_setting(
		        'wgdr_plugin_options_settings_fields',
                'wgdr_plugin_options',
                'WgdrAdmin::wgdr_plugin_options_validate'
		);

		// add settings section
		add_settings_section(
		        'wgdr_plugin_main',
                esc_html__( 'Settings', 'woocommerce-google-dynamic-retargeting-tag' ),
                'WgdrAdmin::wgdr_plugin_section_text',
                'wgdr'
        );

		// add settings fields

		// settings field for the conversion ID
		add_settings_field(
		        'wgdr_plugin_option_conversion_id',
                esc_html__( 'Conversion ID', 'woocommerce-google-dynamic-retargeting-tag' ),
                'WgdrAdmin::wgdr_plugin_option_conversion_id',
                'wgdr',
                'wgdr_plugin_main'
        );

		// settings field for the Google Merchant Center Prefix
		add_settings_field(
		        'wgdr_plugin_option_mc_prefix',
                esc_html__( 'Google Merchant Center Prefix', 'woocommerce-google-dynamic-retargeting-tag' ),
                'WgdrAdmin::wgdr_plugin_option_mc_prefix',
                'wgdr',
                'wgdr_plugin_main'
        );

		// add fields for the product identifier
		add_settings_field(
			'wgdr_plugin_option_product_identifier',
			esc_html__(
				'Product Identifier',
				'woocommerce-google-dynamic-retargeting-tag'
			),
            'WgdrAdmin::wgdr_plugin_option_product_identifier',
			'wgdr',
			'wgdr_plugin_main'
		);

		// add fields for the gtag deactivation
		add_settings_field(
			'wgdr_plugin_option_gtag_deactivation',
			esc_html__(
				'gtag Deactivation',
				'woocommerce-google-dynamic-retargeting-tag'
			),
            'WgdrAdmin::wgdr_plugin_option_gtag_deactivation',
			'wgdr',
			'wgdr_plugin_main'
		);
	}

	public static function wgdr_plugin_section_text() {
		// echo '<p>WooCommerce Google Dynamic Retargeting tag settings.</p>';
	}

	public static function wgdr_plugin_option_conversion_id() {
		$options = get_option( 'wgdr_plugin_options' );
		echo "<input id='wgdr_plugin_option_conversion_id' name='wgdr_plugin_options[conversion_id]' size='40' type='text' value='{$options['conversion_id']}' /><br>" . esc_html__( 'Under the following link you will find instructions how to get the Conversion ID: ', 'woocommerce-google-dynamic-retargeting-tag' ) . "<a href=\"https://support.google.com/adwords/answer/2476688\" target=\"_blank\">" . esc_html__( 'Get your remarketing tag code', 'woocommerce-google-dynamic-retargeting-tag' ) . "</a>";
		//esc_html_e( '', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	public static function wgdr_plugin_option_mc_prefix() {
		$options = get_option( 'wgdr_plugin_options' );
		echo "<input id='wgdr_plugin_option_mc_prefix' name='wgdr_plugin_options[mc_prefix]' size='40' type='text' value='{$options['mc_prefix']}' /><br>" . esc_html__( 'If you use the WooCommerce Google Product Feed Plugin from WooThemes the value here should be "woocommerce_gpf_"', 'woocommerce-google-dynamic-retargeting-tag' ) . " (<a href='http://www.woothemes.com/products/google-product-feed/' target='_blank'>WooCommerce Google Product Feed Plugin</a>). " . esc_html__( 'If you use any other plugin for the feed you can leave this field empty.', 'woocommerce-google-dynamic-retargeting-tag' );
	}

	public static function wgdr_plugin_option_product_identifier() {
		$options = get_option( 'wgdr_plugin_options' );
		?>
		<input type='radio' id='wgdr_plugin_option_product_identifier_0' name='wgdr_plugin_options[product_identifier]'
		       value='0' <?php echo( checked( 0, $options['product_identifier'], false ) ) ?>/><?php _e( 'post id (default)', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
		<br>
		<input type='radio' id='wgdr_plugin_option_product_identifier_1' name='wgdr_plugin_options[product_identifier]'
		       value='1' <?php echo( checked( 1, $options['product_identifier'], false ) ) ?>/><?php _e( 'SKU', 'woocommerce-google-dynamic-retargeting-tag' ) ?>
		<br><br>
		<?php echo( esc_html__( 'Choose a product identifier.', 'woocommerce-google-dynamic-retargeting-tag' ) ); ?>
		<?php
	}

	public static function wgdr_plugin_option_gtag_deactivation() {
		$options = get_option( 'wgdr_plugin_options' );
		?>
		<input type='checkbox' id='wgdr_plugin_option_gtag_deactivation' name='wgdr_plugin_options[gtag_deactivation]'
		       value='1' <?php checked( $options['gtag_deactivation'] ); ?> />
		<?php
		echo( esc_html__( 'Disable gtag.js insertion if another plugin is inserting it already.', 'woocommerce-google-dynamic-retargeting-tag' ) );
	}

	// adds a link on the plugins page for the wgdr settings
	public static function wgdr_settings_link( $links ) {

		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=wgdr' ) . '">Settings</a>',
		);

		return array_merge( $links, $mylinks );
	}


	// Load text domain function
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-google-dynamic-retargeting-tag', false, dirname( plugin_basename( __FILE__ ) ) . '../languages/' );
	}

}