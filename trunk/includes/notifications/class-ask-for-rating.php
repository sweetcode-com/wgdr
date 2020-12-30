<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WgdrAskForRating {

	public static function init() {
		// ask for a rating in a plugin notice
		add_action( 'admin_head', 'WgdrAskForRating::ask_for_rating_js' );
		add_action( 'wp_ajax_wgdr_dismissed_notice_handler', 'WgdrAskForRating::ajax_rating_notice_handler' );
		add_action( 'admin_notices', 'WgdrAskForRating::ask_for_rating_notices_if_not_asked_before' );
	}

	// client side ajax js handler for the admin rating notice
	public static function ask_for_rating_js() {

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
	public static function ajax_rating_notice_handler() {

		// error_log( 'running php handler' );

		$user_meta = get_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', true );

		// prepare the data that needs to be written into the user meta
		$user_meta['date-dismissed'] = date( 'Y-m-d' );

		// update the user meta
		update_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', $user_meta );

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	// only ask for rating if not asked before or longer than a year
	public static function ask_for_rating_notices_if_not_asked_before() {

	    // error_log('run ask');

		// get user meta data for this plugin
		$user_meta = get_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', true );

		if(! isset($user_meta['first-check'])){

		    $user_meta = [];
			$user_meta['first-check'] = date( 'Y-m-d' );

			update_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', $user_meta );
		}

		// check if there is already a saved value in the user meta
		if ( ! isset( $user_meta['date-dismissed'] ) && self::is_installation_older_than_30_days($user_meta)) {

			self::ask_for_rating_notices();
		}
	}

	public static function is_installation_older_than_30_days($user_meta) {

	    $date_1 = date_create( $user_meta['first-check'] );
	    $date_2 = date_create( date( 'Y-m-d' ) );

	    // calculate day difference between the dates
        $interval = date_diff( $date_1, $date_2 );

        if ($interval->format( '%a' ) > 30) {
            return true;
        } else {
            return false;
        }
	}

	// show an admin notice to ask for a plugin rating
	public static function ask_for_rating_notices() {

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
				<span><b><?php _e( 'WGDR Google Ads Dynamic Retargeting Plugin', 'woocommerce-google-dynamic-retargeting-tag' ); ?></b></span>
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
}