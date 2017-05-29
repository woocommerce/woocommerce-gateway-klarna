<?php
/**
 * Klarna Template Functions
 *
 * Functions for the templating system.
 *
 * @author  Krokedil
 * @package Klarna/Functions
 * @since   2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gets cart contents as formatted HTML.
 * Used in KCO widget.
 *
 * @since  2.4.0
 */

if ( ! function_exists( 'klarna_checkout_template_get_cart_contents_html' ) ) {
	/**
	 * Display cart contents.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	function klarna_checkout_template_get_cart_contents_html( $atts ) {
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$hide_columns = array();
		if ( '' !== $atts['hide_columns'] ) {
			$hide_columns = explode( ',', $atts['hide_columns'] );
		}
		?>
		<div>
			<div id="klarna_checkout_coupon_result"></div>
			<table id="klarna-checkout-cart">
				<tbody>
				<tr>
					<?php if ( ! in_array( 'remove', $hide_columns, true ) ) { ?>
						<th class="product-remove kco-leftalign"></th>
					<?php } ?>
					<th class="product-name kco-leftalign"><?php _e( 'Product', 'woocommerce-gateway-klarna' ); ?></th>
					<?php if ( ! in_array( 'price', $hide_columns, true ) ) { ?>
						<th class="product-price kco-centeralign"><?php _e( 'Price', 'woocommerce-gateway-klarna' ); ?></th>
					<?php } ?>
					<th class="product-quantity kco-centeralign"><?php _e( 'Quantity', 'woocommerce-gateway-klarna' ); ?></th>
					<th class="product-total kco-rightalign"><?php _e( 'Total', 'woocommerce-gateway-klarna' ); ?></th>
				</tr>
				<?php
				// Cart items.
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product = $cart_item['data'];
					echo '<tr>';

					if ( ! in_array( 'remove', $hide_columns, true ) ) {
						echo '<td class="kco-product-remove kco-leftalign"><a href="#">x</a></td>';
					}

					echo '<td class="product-name kco-leftalign">';

					if ( apply_filters( 'kco_show_cart_widget_thumbnails', false ) ) {
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
						if ( ! $_product->is_visible() ) {
							echo $thumbnail;
						} else {
							printf( '<a href="%s">%s</a>', $_product->get_permalink( $cart_item ), $thumbnail );
						}
					}

					if ( ! $_product->is_visible() ) {
						echo apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;';
					} else {
						echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s </a>', $_product->get_permalink( $cart_item ), $_product->get_title() ), $cart_item, $cart_item_key );
					}

					// Meta data.
					echo WC()->cart->get_item_data( $cart_item );
					echo '</td>';

					if ( ! in_array( 'price', $hide_columns, true ) ) {
						echo '<td class="product-price kco-centeralign"><span class="amount">';
						echo WC()->cart->get_product_price( $_product );
						echo '</span></td>';
					}

					echo '<td class="product-quantity kco-centeralign" data-cart_item_key="' . $cart_item_key . '">';

					if ( $_product->is_sold_individually() ) {
						$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', esc_attr( $cart_item_key ) );
					} else {
						$product_quantity = woocommerce_quantity_input( array(
							'input_name'  => "cart[{$cart_item_key}][qty]",
							'input_value' => $cart_item['quantity'],
							'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
							'min_value'   => '1'
						), $_product, false );
					}

					echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
					echo '</td>';
					echo '<td class="product-total kco-rightalign"><span class="amount">';
					echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
					echo '</span></td>';
					echo '</tr>';
				} // End foreach().
				?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
