<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WgdrPixel {

	public static $autoptimize_active;
	public static $conversion_id;
	public static $mc_prefix;
	public static $product_identifier;
	public static $gtag_deactivation;

	// Google Dynamic Retargeting tag
	public static function google_dynamic_retargeting_code() {

		global $woocommerce;

		self::get_options_from_db();

		// insert noptimize tag if Autoptimze is active 
		if ( self::$autoptimize_active == true ) {
			echo "<!--noptimize-->";
		}
		?>


		<!-- START Google Code for Dynamic Retargeting --><?php

		// Check if is homepage and set home paramters.
		// is_home() doesn't work in my setup. I don't know why. I'll use is_front_page() as workaround
		if ( is_front_page() ) {
			?>

			<script type="text/javascript">
                gtag('event', 'page_view', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'ecomm_pagetype': 'home'
                });
			</script>
			<?php
		} // Check if it is a product category page and set the category parameters.
		elseif ( is_product_category() ) {
			$product_id = get_the_ID();
			?>

			<script type="text/javascript">
                gtag('event', 'page_view', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'ecomm_pagetype': 'category',
                    'ecomm_category': <?php echo( json_encode( self::get_product_category( $product_id ) ) ); ?>
                });
			</script>
			<?php
		} // Check if it a search results page and set the searchresults parameters.
		elseif ( is_search() ) {
			?>

			<script type="text/javascript">
                gtag('event', 'page_view', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'ecomm_pagetype': 'searchresults'
                });
			</script>
			<?php
		} // Check if it is a product page and set the product parameters.
		elseif ( is_product() ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );

			if ( is_bool( $product ) ) {
				error_log( 'WooCommerce detects the page ID ' . $product_id . ' as product, but when invoked by wc_get_product( ' . $product_id . ' ) it returns no product object' );

				return;
			}


			$product_id_code = '
		<script type="text/javascript">
			gtag(\'event\', \'page_view\', {
			    \'send_to\': \'AW-' . esc_html( self::$conversion_id ) . '\',
			    \'ecomm_pagetype\': \'product\',
			    \'ecomm_category\': ' . json_encode( self::get_product_category( $product_id ) ) . ',
				\'ecomm_prodid\': ' . json_encode( self::$mc_prefix . ( 0 == self::$product_identifier ? get_the_ID() : $product->get_sku() ) ) . ',
				\'ecomm_totalvalue\': ' . (float)$product->get_price() . '
			});
		</script>';


			// apply filter to product id
			$product_id_code = apply_filters( 'wgdr_filter', $product_id_code, 'product_id_code', $product_id );

			echo $product_id_code;


// testing different output
//			$product_array = array(
//			    'event',
//                'page_view', array(
//	                'send_to' => 'AW-1019198954adf',
//	                'ecomm_pagetype' => 'product',
//	                'ecomm_category' => array('Posters'),
//	                'ecomm_prodid' => 'AW-1019198954adf',
//	                'ecomm_totalvalue'=> 12.353
//                )
//            );
//
//	        echo ('
//	    <script type="text/javascript">
//	        var gtag2 = ' . json_encode($product_array) . ';
//	        gtag.apply(this, gtag2);
//	    </script>');


		} // Check if it is the cart page and set the cart parameters.
		elseif ( is_cart() ) {
			$cartprods = $woocommerce->cart->get_cart();
			?>

			<script type="text/javascript">
                gtag('event', 'page_view', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'ecomm_pagetype': 'cart',
                    'ecomm_prodid': <?php echo( json_encode( self::get_cart_product_ids( $cartprods ) ) );?>,
                    'ecomm_totalvalue': <?php echo WC()->cart->get_cart_contents_total(); ?>
                });
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
			if ( ! $order->has_status( 'failed' ) ) {
				//if ( ! $order->has_status( 'failed' ) && ( ( get_post_meta( $order->get_order_number(), '_WGDR_conversion_pixel_fired', true ) == "true" ) ) ) {


				?>

				<script type="text/javascript">
                    gtag('event', 'page_view', {
                        'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                        'ecomm_pagetype': 'purchase',
                        'ecomm_prodid': <?php echo( json_encode( self::get_content_ids( $order ) ) ); ?>,
                        'ecomm_totalvalue': <?php echo $order_subtotal; ?>

                    });
				</script>
				<?php
				update_post_meta( $order->get_order_number(), '_WGDR_conversion_pixel_fired', 'true' );
			} // end if order status
		} // For all other pages set the parameters for other.
		else {
			?>

			<script type="text/javascript">
                gtag('event', 'page_view', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'ecomm_pagetype': 'other'
                });
			</script>
			<?php
		}

		?>

		<!-- END Google Code for Dynamic Retargeting -->

		<?php

		if ( self::$autoptimize_active == true ) {
			echo "<!--/noptimize-->";
		}
	}

	// get an array with all product categories
	public static function get_product_category( $product_id ) {

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
	public static function get_cart_product_ids( $cartprods ) {

		// initiate product identifier array
		$cartprods_items = array();

		// go through the array and get all product identifiers
		foreach ( (array) $cartprods as $entry ) {

			// depending on setting use product IDs or SKUs
			if ( 0 == self::$product_identifier ) {

				// fill the array with all product IDs
				array_push( $cartprods_items, self::$mc_prefix . $entry['product_id'] );

			} else {

				// fill the array with all product SKUs
				$product = wc_get_product( $entry['product_id'] );
				array_push( $cartprods_items, self::$mc_prefix . $product->get_sku() );

			}
		}

		// apply filter to the $cartprods_items array
		$cartprods_items = apply_filters( 'wgdr_filter', $cartprods_items, 'cartprods_items' );

		return $cartprods_items;
	}

	// get an array with all product ids in the order
	public static function get_content_ids( $order ) {

		$order_items       = $order->get_items();
		$order_items_array = array();

		foreach ( (array) $order_items as $item ) {
			//array_push( $order_items_array, self::$mc_prefix . $item['product_id'] );

			// depending on setting use product IDs or SKUs
			if ( 0 == self::$product_identifier ) {

				// fill the array with all product IDs
				array_push( $order_items_array, self::$mc_prefix . $item['product_id'] );

			} else {

				// fill the array with all product SKUs
				$product = wc_get_product( $item['product_id'] );
				array_push( $order_items_array, self::$mc_prefix . $product->get_sku() );

			}
		}

		// apply filter to the $order_items_array array
		$order_items_array = apply_filters( 'wgdr_filter', $order_items_array, 'order_items_array' );

		return $order_items_array;
	}

	public static function get_options_from_db() {
		// get options from db
		$options = get_option( 'wgdr_plugin_options' );

		// set options variables
		self::$conversion_id      = $options['conversion_id'];
		self::$mc_prefix          = $options['mc_prefix'];
		self::$product_identifier = $options['product_identifier'];
		self::$gtag_deactivation  = $options['gtag_deactivation'];
	}

}