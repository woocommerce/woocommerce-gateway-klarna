<?php
/**
 * Klarna plugin shortcodes
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */


/**
 * Class for Klarna shortodes.
 */
class WC_Gateway_Klarna_Shortcodes {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() { 

		// add_shortcode( 'woocommerce_klarna_checkout_widget', array( $this, 'klarna_checkout_widget' ) );
		add_shortcode( 'woocommerce_klarna_checkout', array( $this, 'klarna_checkout_page') );
		add_shortcode( 'woocommerce_klarna_checkout_order_note', array( $this, 'klarna_checkout_order_note') );

	}

	/**
	 * Klarna Checkout widget shortcode callback.
	 * NOT USED ATM
	 * 
	 * Parameters:
	 * col            - whether to show it as left or right column in two column layout, options: 'left' and 'right'
	 * order_note     - whether to show order note or not, option: 'false' (to hide it)
	 * 'hide_columns' - select columns to hide, comma separated string, options: 'remove', 'price'
	 * 
	 * @since  2.0
	 **/
	function klarna_checkout_widget( $atts ) {

		// Don't show on thank you page
		if ( isset( $_GET['thankyou'] ) && 'yes' == $_GET['thankyou'] )
			return;

		// Check if iframe needs to be displayed
		if ( ! $this->show_kco() )
			return;

		global $woocommerce;

		$atts = shortcode_atts(
			array(
				'col' => '',
				'order_note' => '',
				'hide_columns' => ''
			),
			$atts
		);

		if ( '' != $atts['hide_columns'] ) {
			$hide_columns = explode( ',', $atts['hide_columns'] );
		}

		if ( 'left' == $atts['col'] ) {
			$widget_class .= ' kco-left-col';
		} elseif ( 'right' == $atts['col'] ) {
			$widget_class .= ' kco-right-col';			
		}

		// Recheck cart items so that they are in stock
		$result = $woocommerce->cart->check_cart_item_stock();
		if ( is_wp_error( $result ) ) {
			echo '<p>' . $result->get_error_message() . '</p>';
			// exit();
		}

		if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
			ob_start(); ?>
				
				<div id="klarna-checkout-widget" class="woocommerce <?php echo $widget_class; ?>">

					<?php woocommerce_checkout_coupon_form(); ?>

					<div>
					<table id="klarna-checkout-cart">
						<tbody>
							<tr>
								<?php if ( ! in_array( 'remove', $hide_columns ) ) { ?>
								<th class="product-remove kco-leftalign"></th>
								<?php } ?>
								<th class="product-name kco-leftalign"><?php _e( 'Product', 'klarna' ); ?></th>
								<?php if ( ! in_array( 'price', $hide_columns ) ) { ?>
								<th class="product-price kco-centeralign"><?php _e( 'Price', 'klarna' ); ?></th>
								<?php } ?>
								<th class="product-quantity kco-centeralign"><?php _e( 'Quantity', 'klarna' ); ?></th>
								<th class="product-total kco-rightalign"><?php _e( 'Total', 'klarna' ); ?></th>
							</tr>
							<?php
							foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {
								$_product = $cart_item['data'];
								$cart_item_product = wc_get_product( $cart_item['product_id'] );
								echo '<tr>';
									if ( ! in_array( 'remove', $hide_columns ) ) {
									echo '<td class="kco-product-remove kco-leftalign"><a href="#">x</a></td>';
									}
									echo '<td class="product-name kco-leftalign">';
										if ( ! $_product->is_visible() ) {
											echo apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key ) . '&nbsp;';
										} else { 
											echo apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s </a>', $_product->get_permalink( $cart_item ), $_product->get_title() ), $cart_item, $cart_item_key );
										}
										// Meta data
										echo $woocommerce->cart->get_item_data( $cart_item );
									echo '</td>';
									if ( ! in_array( 'price', $hide_columns ) ) {
									echo '<td class="product-price kco-centeralign"><span class="amount">';
										echo apply_filters( 'woocommerce_cart_item_price', $woocommerce->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
									echo '</span></td>';
									}
									echo '<td class="product-quantity kco-centeralign" data-cart_item_key="' . $cart_item_key .'">';
										if ( $_product->is_sold_individually() ) {
											$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
										} else {
											$product_quantity = woocommerce_quantity_input( array(
												'input_name'  => "cart[{$cart_item_key}][qty]",
												'input_value' => $cart_item['quantity'],
												'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
												'min_value'   => '1'
											), $_product, false );
										}
										echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key );
									echo '</td>';
									echo '<td class="product-total kco-rightalign"><span class="amount">';
										echo apply_filters( 'woocommerce_cart_item_subtotal', $woocommerce->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
									echo '</span></td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
					</div>

					<?php
					if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
						define( 'WOOCOMMERCE_CART', true );
					}
					$woocommerce->cart->calculate_shipping();
					$woocommerce->cart->calculate_totals();
					?>
					<div>
					<table id="kco-totals">
						<tbody>
							<tr id="kco-page-subtotal">
								<td class="kco-col-desc kco-rightalign"><?php _e( 'Subtotal', 'klarna' ); ?></td>
								<td id="kco-page-subtotal-amount" class="kco-col-number kco-rightalign"><span class="amount"><?php echo $woocommerce->cart->get_cart_subtotal(); ?></span></td>
							</tr>
							
							<?php echo $this->klarna_checkout_get_shipping_options_row_html(); ?>
							
							<?php foreach ( $woocommerce->cart->get_applied_coupons() as $coupon ) { ?>
								<tr class="kco-applied-coupon">
									<td class="kco-rightalign">
										Coupon: <?php echo $coupon; ?> 
										<a class="kco-remove-coupon" data-coupon="<?php echo $coupon; ?>" href="#">(remove)</a>
									</td>
									<td class="kco-rightalign">-<?php echo wc_price( $woocommerce->cart->get_coupon_discount_amount( $coupon, $woocommerce->cart->display_cart_ex_tax ) ); ?></td>
								</tr>
							<?php }	?>

							<tr id="kco-page-total">
								<td class="kco-rightalign kco-bold"><?php _e( 'Total', 'klarna' ); ?></a></td>
								<td id="kco-page-total-amount" class="kco-rightalign kco-bold"><span class="amount"><?php echo $woocommerce->cart->get_total(); ?></span></td>
							</tr>
						</tbody>
					</table>
					</div>

					<?php if ( 'false' != $atts['order_note'] ) { ?>
					<div>
						<form>
							<textarea id="klarna-checkout-order-note" class="input-text" name="klarna-checkout-order-note" placeholder="<?php _e( 'Notes about your order, e.g. special notes for delivery.', 'klarna' ); ?>"></textarea>
						</form>
					</div>
					<?php } ?>

				</div>

			<?php return ob_get_clean();
		}

	}

	// Shortcode KCO page
	function klarna_checkout_page( $atts ) {
		$atts = shortcode_atts(
			array(
				'col' => '',
			),
			$atts
		);

		$widget_class = '';

		if ( 'left' == $atts['col'] ) {
			$widget_class .= ' kco-left-col';
		} elseif ( 'right' == $atts['col'] ) {
			$widget_class .= ' kco-right-col';			
		}

		$checkout = WC()->checkout();
		if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
		} else {
			$data = new WC_Gateway_Klarna_Checkout;
			return '<div class="klarna_checkout ' . $widget_class . '">' . $data->get_klarna_checkout_page() . '</div>';
		}
	}

	// Shortcode Order note
	function klarna_checkout_order_note() {
		global $woocommerce;

		$field = array(
			'type'              => 'textarea',
			'label'             => __( 'Order Notes', 'woocommerce' ),
			'placeholder'       => _x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce'),
			'class'             => array('notes'),
		);
		if ( WC()->session->get( 'klarna_order_note' ) ) {
			$order_note = WC()->session->get( 'klarna_order_note' );
		} else {
			$order_note = '';
		}

		ob_start();
		
		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
			echo '<div class="woocommerce"><form>';
			woocommerce_form_field( 'kco_order_note', $field, $order_note );
			echo '</form></div>';
		}

		return ob_get_clean();
	}
}

$wc_klarna_checkout_shortcodes = new WC_Gateway_Klarna_Shortcodes;