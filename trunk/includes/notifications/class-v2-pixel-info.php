<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WgdrV2PixelInfo {

	public static function init() {
		// ask for a rating in a plugin notice
		add_action( 'admin_head', 'WgdrV2PixelInfo::ask_for_rating_js' );
		add_action( 'wp_ajax_wgdr_v2_pixel_info_dismissed_notice_handler', 'WgdrV2PixelInfo::ajax_rating_notice_handler' );
		add_action( 'admin_notices', 'WgdrV2PixelInfo::run_v2_pixel_info' );
	}

	// client side ajax js handler for the admin rating notice
	public static function ask_for_rating_js() {

		?>
		<script type="text/javascript">
            jQuery(document).on('click', '.wgdr-v2-pixel-info-close-button', function ($) {

                var data = {
                    'action': 'wgdr_v2_pixel_info_dismissed_notice_handler',
                };

                jQuery.post(ajaxurl, data);
                jQuery('.wgdr-v2-pixel-info-notice').remove();

            });
		</script> <?php
	}

	// server side php ajax handler for the admin rating notice
	public static function ajax_rating_notice_handler() {

		// error_log( 'running php handler' );

		$user_meta = get_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', true );

		// prepare the data that needs to be written into the user meta
		$user_meta['v2-pixel-info-dismissed'] = date( 'Y-m-d' );

		// update the user meta
		update_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', $user_meta );

		wp_die(); // this is required to terminate immediately and return a proper response
	}

	// only ask for rating if not asked before or longer than a year
	public static function run_v2_pixel_info() {

	    // error_log('run pixel info');

		// get user meta data for this plugin
		$user_meta = get_user_meta( get_current_user_id(), 'wgdr_admin_notice_user_meta', true );

		if (! isset($user_meta['v2-pixel-info-dismissed'])){
			self::show_v2_pixel_info();
        }
	}

	// show an admin notice to ask for a plugin rating
	public static function show_v2_pixel_info() {

		// source: https://make.wordpress.org/core/2015/04/23/spinners-and-dismissible-admin-notices-in-4-2/
		// source: https://wordpress.stackexchange.com/questions/191479/how-to-save-dismissable-notice-state-in-wp-4-2
		// source: https://codex.wordpress.org/AJAX_in_Plugins
		// source: http://api.jquery.com/jquery.ajax/
		// https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices

		$current_user = wp_get_current_user();

		?>
		<div class="notice notice-success is-dismissible wgdr-v2-pixel-info-notice">
			<p>
				<span><?php _e( 'Hi ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
				<span><?php echo( $current_user->user_firstname ? $current_user->user_firstname : $current_user->nickname ); ?></span>
				<span><?php _e( '! ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>
				<span><?php _e( 'There has been an important change in the dynamic remarketing pixel that will impact how audiences are collected and will affect some of the audience statistics in Google Ads. Head over to our blog post where we explain why this change happened and how the audience statistics are affected: ', 'woocommerce-google-dynamic-retargeting-tag' ); ?></span>

				<span>
                    <button onclick="window.open('https://wolfundbaer.ch/de/?p=11884')" type="button">open the blog post</button>
                </span>
			</p>
            <p>
                <span>
                    <?php _e(
                            'I think a better way to inform you about this kind of change would have been in form of a newsletter. But since we have never collected email addresses, this notification is the only way to get the current info about the pixel change to you. For the future I would like to give you the opportunity to subscribe to our newsletter which will be specific to plugin updates like the current one.',
                            'woocommerce-google-dynamic-retargeting-tag'
                    ); ?>
                </span>
            </p>

            <div>
                <!-- Begin Mailchimp Signup Form -->
                <link href="//cdn-images.mailchimp.com/embedcode/horizontal-slim-10_7.css" rel="stylesheet" type="text/css">
                <style type="text/css">
                    #mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; width:100%;}
                    /* Add your own Mailchimp form style overrides in your site stylesheet or in this style block.
					   We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */

                    form#mc-embedded-subscribe-form {
                        text-align: left;
                    }

                </style>
                <div id="mc_embed_signup">
                    <form action="https://wolfundbaer.us11.list-manage.com/subscribe/post?u=2c026b156ecfeae9b24196de5&amp;id=a886effb9a" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                        <div id="mc_embed_signup_scroll">

                            <input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
                            <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                            <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_2c026b156ecfeae9b24196de5_a886effb9a" tabindex="-1" value=""></div>
                            <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
                        </div>
                    </form>
                </div>

                <!--End mc_embed_signup-->
            </div>

            <div>
                <p>
                    <div class="wgdr-v2-pixel-info-close-button">
                        <button type="button">Close this notification (forever)</button>
                    </div>
                </p>
            </div>

		</div>
		<?php

	}
}