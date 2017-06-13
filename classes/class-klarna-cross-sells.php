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

	private $cross_sells_enabled;

	public function __construct() {
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$this->cross_sells_enabled = ( isset( $checkout_settings['enable_cross_sells'] ) && 'yes' === $checkout_settings['enable_cross_sells'] ) ? true : false;

		if ( $this->cross_sells_enabled ) {
			add_action( 'klarna_after_kco_checkout', array( $this, 'add_cross_sells_ids_to_session' ) );
			add_action( 'klarna_after_kco_confirmation', array( $this, 'display_cross_sells' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'klarna_checkout_cross_sells_enqueuer' ) );
			add_action( 'wp_ajax_klarna_checkout_cross_sells_add', array( $this, 'cross_sells_process' ) );
			add_action( 'wp_ajax_nopriv_klarna_checkout_cross_sells_add', array( $this, 'cross_sells_process' ) );
		}
	} // End constructor

	function add_cross_sells_ids_to_session() {
		if ( WC()->cart->get_cross_sells() ) {
			// Change the button filter
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_add_to_cart_button' ), 10, 2 );

			ob_start();
			wc_get_template( 'cart/cross-sells.php', array(
				'posts_per_page' => apply_filters( 'klarna_checkout_cross_sells_per_page', 2 ),
				'columns'        => apply_filters( 'klarna_checkout_cross_sells_columns', 2 ),
				'orderby'        => apply_filters( 'klarna_checkout_cross_sells_orderby', 'rand' )
			) );
			$cross_sells_output = ob_get_contents();
			ob_end_clean();
			WC()->session->set( 'klarna_cross_sells', $cross_sells_output );

			// Remove change the button filter
			remove_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_add_to_cart_button' ) );
		}
	}

	function klarna_checkout_cross_sells_enqueuer() {
		if ( is_page() ) {
			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			$checkout_pages    = array();
			$thank_you_pages   = array();

			// Clean request URI to remove all parameters
			$clean_req_uri = explode( '?', $_SERVER['REQUEST_URI'] );
			$clean_req_uri = $clean_req_uri[0];
			$clean_req_uri = trailingslashit( $clean_req_uri );
			$length        = strlen( $clean_req_uri );

			// Get arrays of checkout and thank you pages for all countries
			if ( is_array( $checkout_settings ) ) {
				foreach ( $checkout_settings as $cs_key => $cs_value ) {
					if ( strpos( $cs_key, 'klarna_checkout_url_' ) !== false ) {
						$checkout_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
					}
					if ( strpos( $cs_key, 'klarna_checkout_thanks_url_' ) !== false ) {
						$thank_you_pages[ $cs_key ] = substr( $cs_value, 0 - $length );
					}
				}
			}

			if ( ( in_array( $clean_req_uri, $checkout_pages ) || in_array( $clean_req_uri, $thank_you_pages ) ) && strlen( $clean_req_uri ) > 1 ) {
				wp_register_script( 'klarna_checkout_cross_sells', KLARNA_URL . 'assets/js/cross-sells.js', array(), false, true );
				wp_localize_script( 'klarna_checkout_cross_sells', 'kcoCrossSells', array(
					'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
					'klarna_cross_sells_nonce' => wp_create_nonce( 'klarna_cross_sells_nonce' ),
					'added_to_order_text'      => __( 'Thanks! The product has been added to your order!', 'woocommerce-gateway-klarna' )
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
		$product_id = klarna_wc_get_product_id( $product );
		return sprintf( '<a rel="nofollow" href="#" data-product_id="%s" class="button klarna-cross-sells-button">%s</a>', esc_attr( $product_id ), __( 'Add to order', 'woocommerce-gateway-klarna' ) );
	}

	function display_cross_sells( $order_id, $klarna_order ) {
		do_action( 'klarna_before_kco_cross_sells', $order_id, $klarna_order );

		// Check if cart update is allowed by Klarna
		if (
			( isset( $klarna_order['cart_update_allowed'] ) && $klarna_order['cart_update_allowed'] )
			// || ( isset( $klarna_order['payment_type_allows_increase'] ) && $klarna_order['payment_type_allows_increase'] )
		) {
			echo WC()->session->get( 'klarna_cross_sells' );
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

		// This is an AJAX request, so we need to retrieve thank you page URL to grab Klarna order and WC order.
		$parsed_url = parse_url( $_SERVER['HTTP_REFERER'] );
		parse_str( $parsed_url['query'], $query_params );

		$wc_order = wc_get_order( $query_params['order-received'] );
		$product_id = (int) $_REQUEST['product_id'];
		$product = wc_get_product( $_REQUEST['product_id'] );

		do_action( 'klarna_before_adding_kco_cross_sell', klarna_wc_get_order_id( $wc_order ), klarna_wc_get_product_id( $product ) );

		// Add to WooCommerce order first, so in next step we can use WC_Gateway_Klarna_Order.
		$item_id = $this->cross_sells_add_woocommerce( $wc_order, $product );

		// Add to Klarna order.
		$result = $this->cross_sells_add_klarna( $wc_order );

		$data = array();
		if ( is_wp_error( $result ) ) {
			// Remove the item from WC order.
			wc_delete_order_item( $item_id );

			$data['message'] = __( "We're sorry, the item couldn't be added to your order. A new order will be created instead.", 'woocommerce-gateway-klarna' );
			global $klarna_checkout_url;
			$data['checkout_url'] = $klarna_checkout_url;
			WC()->cart->add_to_cart( $product_id );

			wp_send_json_error( $data );
		} else {
			do_action( 'klarna_after_adding_kco_cross_sell', klarna_wc_get_order_id( $wc_order ), klarna_wc_get_product_id( $product ) );
			wp_send_json_success();
		}

		wp_die();
	}

	/**
	 * Add the item to WooCommerce order
	 *
	 * @param $wc_order
	 * @param $product
	 *
	 * @return mixed
	 */
	function cross_sells_add_woocommerce( $wc_order, $product ) {
		$item_id = $wc_order->add_product( $product );

		$wc_order->add_order_note(
			sprintf(
				__( '%s added to order.', 'woocommerce-gateway-klarna' ),
				$product->get_title()
			)
		);
		$wc_order->calculate_totals();

		return $item_id;
	}

	/**
	 * Add the item to Klarna order
	 *
	 * @param $wc_order
	 *
	 * @return bool|void|WP_Error
	 */
	function cross_sells_add_klarna( $wc_order ) {
		$klarna_order = new WC_Gateway_Klarna_Order();
		if ( 'rest' == get_post_meta( $wc_order->id, '_klarna_api', true ) ) {
			$result = $klarna_order->update_order_rest( $wc_order->id );
		} else {
			$result = $klarna_order->update_order( $wc_order->id );
		}

		return $result;
	}

}
$wc_gateway_klarna_cross_sells = new WC_Gateway_Klarna_Cross_Sells();