<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WgdrPixelV2 {

	public static $autoptimize_active;
	public static $conversion_id;
	public static $mc_prefix;
	public static $product_identifier;
	public static $gtag_deactivation;
	public static $google_business_vertical;

	// Google Dynamic Retargeting tag
	public static function google_dynamic_retargeting_code() {

		global $woocommerce, $wp_query;

		self::$google_business_vertical = 'retail';

		self::get_options_from_db();

		// insert noptimize tag if Autoptimze is active 
		if ( self::$autoptimize_active == true ) {
			echo "<!--noptimize-->";
		}
		?>

		<!-- START Google Code for Dynamic Retargeting -->
        <?php

		self::inject_add_to_cart_script();

		if ( is_product_category() ) {
//			error_log(print_r($wp_query, true));
			?>

			<script type="text/javascript">
                gtag('event', 'view_item_list', { 'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>', 'items' : <?php echo json_encode(self::get_products_from_wp_query($wp_query)) ?>
                });
			</script>
			<?php
		}
		elseif ( is_search() ) {

			?>

			<script type="text/javascript">
                gtag('event', 'view_search_results', { 'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>', 'items': <?php echo json_encode(self::get_products_from_wp_query($wp_query)) ?>});
			</script>
			<?php
		} // Check if it is a product page and set the product parameters.
		elseif ( is_product() && (!isset($_POST['add-to-cart'])) ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );

			if ( is_bool( $product ) ) {
				error_log( 'WooCommerce detects the page ID ' . $product_id . ' as product, but when invoked by wc_get_product( ' . $product_id . ' ) it returns no product object' );
				return;
			}

			$product_details = self::get_product_details_from_product_id($product_id);

			?>

            <script type="text/javascript">
                gtag('event', 'view_item', {
                    'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                    'value': <?php echo $product_details['price'] ?>,
                    'items': [<?php echo(json_encode($product_details)) ?>]
                });
            </script>
            <?php

		} // Check if it is the cart page and set the cart parameters.
		elseif ( is_cart() ) {
			$cart = $woocommerce->cart->get_cart();
			?>

			<script type="text/javascript">
                gtag('event', 'add_to_cart', { 'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>', 'value': <?php echo WC()->cart->get_cart_contents_total(); ?>, 'items': <?php echo(json_encode(self::get_cart_items($cart))) ?>});
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
                    gtag('event', 'purchase', {
                        'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>',
                        'value': <?php echo $order_subtotal; ?>,
                        'items': <?php echo(json_encode(self::get_order_items($order))) ?>
                    });
				</script>
				<?php
				update_post_meta( $order->get_order_number(), '_WGDR_conversion_pixel_fired', 'true' );
			} // end if order status
		}

		?>

		<!-- END Google Code for Dynamic Retargeting -->

		<?php

		if ( self::$autoptimize_active == true ) {
			echo "<!--/noptimize-->";
		}
	}

	public static function get_product_details_from_product_id( $product_id ){

		$product    = wc_get_product( $product_id );

		$product_details['id'] = self::$mc_prefix . ( 0 == self::$product_identifier ? get_the_ID() : $product->get_sku() );
		$product_details['category'] = self::get_product_category( $product_id );
		// $product_details['list_position'] = 1;
		$product_details['quantity'] = 1;
		$product_details['price'] = (float)$product->get_price();
		$product_details['google_business_vertical'] = self::$google_business_vertical;

		return $product_details;
    }

    public static function inject_add_to_cart_script(){

	    // https://stackoverflow.com/questions/55139654/woocommerce-added-to-cart-delegated-event-in-single-product-pages
	    // https://stackoverflow.com/questions/60432117/woocommerce-trigger-added-to-cart-and-get-product-id
	    // https://stackoverflow.com/a/47463018/4688612
	    // https://diviengine.com/woocommerce-add-cart-ajax-single-variable-products-improve-ux/

	    if ( is_shop() || is_product_category() || is_product_tag() ){ ?>

        <script type="text/javascript">
            (function($){
                $( document.body ).on( 'added_to_cart', function(event, fragments, cart_hash, $button){
                    gtag('event', 'add_to_cart', {'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>','items': [{'id': '<?php echo self::$mc_prefix ?>' + $($button[0]).data('product_<?php echo ( 0 == self::$product_identifier ? 'id' : 'sku' ) ?>'),'google_business_vertical': '<?php echo self::$google_business_vertical ?>'}] });
                });
            })(jQuery);

        </script>

	    <?php
        } elseif( is_product() ){

		    if( isset($_POST['add-to-cart']) && isset($_POST['quantity']) ) {

			    $quantity   = $_POST['quantity'];
			    $product_id = isset($_POST['variation_id']) ? $_POST['variation_id'] : $_POST['add-to-cart'];

			    $product_details = self::get_product_details_from_product_id($product_id);
			    $product_details['quantity'] = $quantity;

			    ?>

                <script>
                    jQuery(function($){
                        gtag('event', 'add_to_cart', {'send_to': 'AW-<?php echo esc_html( self::$conversion_id ) ?>','items': [<?php echo json_encode($product_details) ?>]});
                    });
                </script>
			    <?php
		    }
        }
    }

	// get products from wp_query
    public static function get_products_from_wp_query($wp_query){

	    $items = array();

	    $posts = $wp_query->posts;

	    foreach ($posts as $key => $post) {

		    if($post->post_type == 'product') {
			    $item_details = array();

			    $product = wc_get_product( $post->ID );

			    $item_details['id'] = self::$mc_prefix . ( 0 == self::$product_identifier ? $post->ID : $product->get_sku() );
			    $item_details['google_business_vertical'] = self::$google_business_vertical;

			    array_push($items, $item_details);
		    }
	    }

	    return $items;
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
	public static function get_cart_items( $cart ) {

		// error_log(print_r($cart, true));
		// initiate product identifier array
		$cart_items = array();
		$item_details = array();

		// go through the array and get all product identifiers
		foreach ( (array) $cart as $item ) {

			$product = wc_get_product( $item['product_id'] );

			// depending on setting use product IDs or SKUs
			if ( 0 == self::$product_identifier ) {

				// fill the array with all product IDs
                $item_details['id'] = self::$mc_prefix . $item['product_id'];

			} else {

				// fill the array with all product SKUs
				$product = wc_get_product( $item['product_id'] );
				$item_details['id'] = self::$mc_prefix . $product->get_sku();
			}

			$item_details['quantity'] = (int)$item['quantity'];
			$item_details['price']    = (int)$product->get_price();
			$item_details['google_business_vertical'] = self::$google_business_vertical;

			array_push($cart_items, $item_details);
		}

		// apply filter to the $cartprods_items array
		$cart_items = apply_filters( 'wgdr_filter', $cart_items, 'cart_items' );

		return $cart_items;
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

	private static function get_order_items($order){

		$order_items       = $order->get_items();
		$order_items_array = array();

		foreach ( (array) $order_items as $item ) {
			//array_push( $order_items_array, self::$mc_prefix . $item['product_id'] );

			$product = wc_get_product( $item['product_id'] );

			$item_details_array = array();
			$identifier   = '';

			// depending on setting use product IDs or SKUs
			if ( 0 == self::$product_identifier ) {

				$item_details_array['id'] = self::$mc_prefix . $item['product_id'];
			} else {

				$product = wc_get_product( $item['product_id'] );
				$item_details_array['id'] = self::$mc_prefix . $product->get_sku();
			}

			$item_details_array['quantity'] = (int)$item['quantity'];
			$item_details_array['price']    = (int)$product->get_price();
			$item_details_array['google_business_vertical'] = self::$google_business_vertical;

			array_push($order_items_array, $item_details_array);
		}

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