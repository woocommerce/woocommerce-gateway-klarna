<?php
/**
 * Klarna Checkout cross-sells
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class that handles Klarna Checkout cross-sells.
 */
class WC_Gateway_Klarna_Cross_Sells {

	public function __construct() {
		add_action( 'klarna_after_kco_checkout', array( $this, 'add_cross_sells_ids_to_session' ) );
		add_action( 'klarna_after_kco_confirmation', array( $this, 'display_cross_sells' ), 10, 2 );
		add_action( 'klarna_after_kco_confirmation', array( $this, 'remove_cross_sells_ids_from_session' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'klarna_checkout_cross_sells_enqueuer' ) );
		add_action( 'wp_ajax_klarna_checkout_cross_sells_add', array( $this, 'cross_sells_process' ) );
		add_action( 'wp_ajax_nopriv_klarna_checkout_cross_sells_add', array( $this, 'cross_sells_process' ) );
	} // End constructor

	function add_cross_sells_ids_to_session() {
		if ( $crosssells = WC()->cart->get_cross_sells() ) {
			WC()->session->set( 'klarna_cross_sells', $crosssells );
		}
	}

	function remove_cross_sells_ids_from_session() {
		// @TODO: Uncomment it
		// WC()->session->__unset( 'klarna_cross_sells' );
	}

	function klarna_checkout_cross_sells_enqueuer() {
		if ( is_page() ) {
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			$thank_you_pages   = array();

			// Clean request URI to remove all parameters
			$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
			$clean_req_uri = $clean_req_uri[0];
			$clean_req_uri = trailingslashit( $clean_req_uri );
			$length        = strlen( $clean_req_uri );

			// Get arrays of checkout and thank you pages for all countries
			if ( is_array( $checkout_settings ) ) {
				foreach ( $checkout_settings as $cs_key => $cs_value ) {
					if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
						$thank_you_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
					}
				}
			}

			if ( in_array( $clean_req_uri, $thank_you_pages ) && strlen( $clean_req_uri ) > 1 ) {
				wp_register_script( 'klarna_checkout_cross_sells', KLARNA_URL . 'assets/js/cross-sells.js', array(), false, true );
				wp_localize_script( 'klarna_checkout_cross_sells', 'kcoCrossSells', array(
					'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
					'klarna_cross_sells_nonce' => wp_create_nonce( 'klarna_cross_sells_nonce' ),
					'added_to_order_text'      => __( 'Item added to order', 'woocommerce-gateway-klarna' )
				) );
				wp_enqueue_script( 'klarna_checkout_cross_sells' );
			}
		}
	}

	/**
	 * @param $output
	 * @param $product
	 *
	 * @return string
	 */
	function filter_add_to_cart_button( $output, $product ) {
		return sprintf( '<a rel="nofollow" href="#" data-product_id="%s" class="button klarna-cross-sells-button">%s</a>', esc_attr( $product->id ), __( 'Add to order', 'woocommerce-gateway-klarna' ) );
	}

	function display_cross_sells( $order_id, $klarna_order ) {
		do_action( 'klarna_before_kco_cross_sells', $order_id, $klarna_order );

		// Check if cart update is allowed by Klarna
		if ( $klarna_order['cart_update_allowed'] ) {
			// Change the button
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_add_to_cart_button' ), 10, 2 );
			if ( $crosssells = WC()->session->get( 'klarna_cross_sells' ) ) {
				$args                        = array(
					'post_type'           => 'product',
					'ignore_sticky_posts' => 1,
					'no_found_rows'       => 1,
					'posts_per_page'      => apply_filters( 'woocommerce_cross_sells_total', 2 ),
					'orderby'             => 'rand',
					'post__in'            => $crosssells,
					'meta_query'          => WC()->query->get_meta_query(),
				);
				$products                    = new WP_Query( $args );
				$woocommerce_loop['name']    = 'cross-sells';
				$woocommerce_loop['columns'] = apply_filters( 'woocommerce_cross_sells_columns', 2 );
				if ( $products->have_posts() ) : ?>

					<div class="cross-sells" id="klarna-checkout-cross-sells">
						<h2><?php _e( 'You may be interested in&hellip;', 'woocommerce' ) ?></h2>
						<?php woocommerce_product_loop_start(); ?>
						<?php while ( $products->have_posts() ) : $products->the_post(); ?>
							<?php wc_get_template_part( 'content', 'product' ); ?>
						<?php endwhile; // end of the loop. ?>
						<?php woocommerce_product_loop_end(); ?>
					</div>

				<?php endif;
				wp_reset_query();
				// Remove change the button filter
				remove_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_add_to_cart_button' ) );
			}
		}

		do_action( 'klarna_after_kco_cross_sells', $order_id, $klarna_order );
	}

	/**
	 * Processes the cross-sell item
	 */
	function cross_sells_process() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'klarna_cross_sells_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}

		// This is an AJAX request, so we need to retrieve thank you page URL to grab Klarna order and WC order
		$parsed_url = parse_url( $_SERVER['HTTP_REFERER'] );
		parse_str( $parsed_url['query'], $query_params );

		$wc_order = wc_get_order( $query_params['order-received'] );
		$product = wc_get_product( $_REQUEST['product_id'] );

		do_action( 'klarna_before_adding_kco_cross_sell', $wc_order->id, $product->id );

		// Add to WooCommerce order first, so in next step we can use WC_Gateway_Klarna_Order
		$this->cross_sells_add_woocommerce( $wc_order, $product );

		// Add to Klarna order
		$this->cross_sells_add_klarna( $wc_order );

		do_action( 'klarna_after_adding_kco_cross_sell', $wc_order->id, $product->id );
	}

	/**
	 * Add the item to WooCommerce order
	 *
	 * @param $wc_order
	 * @param $product
	 */
	function cross_sells_add_woocommerce( $wc_order, $product ) {
		$wc_order->add_product( $product );
		$wc_order->add_order_note(
			sprintf(
				__( '%s added to order.', 'woocommerce-gateway-klarna' ),
				$product->get_title()
			)
		);
		$wc_order->calculate_totals();
	}

	/**
	 * Add the item to Klarna order
	 *
	 * @param $wc_order
	 */
	function cross_sells_add_klarna( $wc_order ) {
		$klarna_order = new WC_Gateway_Klarna_Order();
		if ( 'rest' == get_post_meta( $wc_order->id, '_klarna_api', true ) ) {
			$klarna_order->update_order_rest( $wc_order->id );
		} else {
			$klarna_order->update_order( $wc_order->id );
		}
	}

}
$wc_gateway_klarna_cross_sells = new WC_Gateway_Klarna_Cross_Sells();