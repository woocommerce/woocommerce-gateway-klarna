<?php
/**
 * Klarna plugin shortcodes
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
 * Class for Klarna shortodes.
 */
class WC_Gateway_Klarna_Shortcodes {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_shortcode( 'woocommerce_klarna_checkout', array( $this, 'klarna_checkout_page' ) );
		add_shortcode( 'woocommerce_klarna_checkout_order_note', array( $this, 'klarna_checkout_order_note' ) );
		add_shortcode( 'woocommerce_klarna_country', array( $this, 'klarna_checkout_country' ) );
		add_shortcode( 'woocommerce_klarna_checkout_widget', array( $this, 'klarna_checkout_widget' ) );
	}


	// Shortcode KCO page
	function klarna_checkout_page( $atts ) {
		if ( ! WC()->cart ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'col' => '',
			), $atts
		);

		$widget_class = '';

		if ( 'left' == $atts['col'] ) {
			$widget_class .= ' kco-left-col';
		} elseif ( 'right' == $atts['col'] ) {
			$widget_class .= ' kco-right-col';
		}

		$checkout = WC()->checkout();
		if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
			echo '<div class="klarna-checkout-guest-notice">';
			echo apply_filters(
				'klarna_checkout_must_be_logged_in_message',
				sprintf(
					__( 'You must be logged in to checkout. %1$s or %2$s.', 'woocommerce-gateway-klarna' ),
					'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" title="' . __(
						'Login', 'woocommerce-gateway-klarna'
					) . '">' . __( 'Login', 'woocommerce-gateway-klarna' ) . '</a>',
					'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" title="' . __(
						'create an account',
						'woocommerce-gateway-klarna'
					) . '">' . __(
						'create an account', 'woocommerce-gateway-klarna'
					) . '</a>'
				)
			);
			echo '</div>';
		} else {
			if ( isset( $_GET['klarna_order'] ) ) { // Display Order response/thank you page via iframe from Klarna
				return '<div class="klarna_checkout ' . $widget_class . '">' . WC_Gateway_Klarna_Checkout::get_klarna_thank_you_page() . '</div>';
			} else {
				return '<div class="klarna_checkout ' . $widget_class . '">' . WC_Gateway_Klarna_Checkout::get_klarna_checkout_page() . '</div>';
			}
		}
	}

	// Shortcode Order note
	function klarna_checkout_order_note() {
		if ( ! WC()->cart ) {
			return;
		}

		$field = array(
			'type'        => 'textarea',
			'label'       => __( 'Order Notes', 'woocommerce-gateway-klarna' ),
			'placeholder' => _x( 'Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce-gateway-klarna' ),
			'class'       => array( 'notes' ),
		);
		if ( WC()->session->get( 'klarna_order_note' ) ) {
			$order_note = WC()->session->get( 'klarna_order_note' );
		} else {
			$order_note = '';
		}

		ob_start();

		if ( count( WC()->cart->get_cart() ) > 0 ) {
			echo '<div class="woocommerce"><form>';
			woocommerce_form_field( 'kco_order_note', $field, $order_note );
			echo '</form></div>';
		}

		return ob_get_clean();
	}

	/**
	 * Klarna Checkout country selector shortcode callback.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_country() {
		if ( ! WC()->cart ) {
			return;
		}

		if ( count( WC()->cart->get_cart() ) > 0 && 'EUR' === get_woocommerce_currency() ) {
			ob_start();

			$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );

			$authorized_countries = array();
			if ( ! empty( $checkout_settings['eid_se'] ) ) {
				$authorized_countries[] = 'SE';
			}
			if ( ! empty( $checkout_settings['eid_no'] ) ) {
				$authorized_countries[] = 'NO';
			}
			if ( ! empty( $checkout_settings['eid_fi'] ) ) {
				$authorized_countries[] = 'FI';
			}
			if ( ! empty( $checkout_settings['eid_de'] ) ) {
				$authorized_countries[] = 'DE';
			}
			if ( ! empty( $checkout_settings['eid_at'] ) ) {
				$authorized_countries[] = 'AT';
			}
			if ( ! empty( $checkout_settings['eid_uk'] ) ) {
				$authorized_countries[] = 'GB';
			}
			if ( ! empty( $checkout_settings['eid_us'] ) ) {
				$authorized_countries[] = 'US';
			}
			if ( ! empty( $checkout_settings['eid_dk'] ) ) {
				$authorized_countries[] = 'DK';
			}
			if ( ! empty( $checkout_settings['eid_nl'] ) ) {
				$authorized_countries[] = 'NL';
			}

			// Get array of Euro Klarna Checkout countries with Eid and secret defined
			$klarna_checkout_countries = array();
			if ( in_array( 'FI', $authorized_countries ) ) {
				$klarna_checkout_countries['FI'] = __( 'Finland', 'woocommerce-gateway-klarna' );
			}
			if ( in_array( 'DE', $authorized_countries ) ) {
				$klarna_checkout_countries['DE'] = __( 'Germany', 'woocommerce-gateway-klarna' );
			}
			if ( in_array( 'AT', $authorized_countries ) ) {
				$klarna_checkout_countries['AT'] = __( 'Austria', 'woocommerce-gateway-klarna' );
			}
			if ( in_array( 'NL', $authorized_countries ) ) {
				$klarna_checkout_countries['NL'] = __( 'Netherlands', 'woocommerce-gateway-klarna' );
			}

			/*
			$klarna_checkout_countries = array(
				'FI' => __( 'Finland', 'woocommerce-gateway-klarna' ),
				'DE' => __( 'Germany', 'woocommerce-gateway-klarna' ),
				'AT' => __( 'Austria', 'woocommerce-gateway-klarna' )
			);
			*/

			$klarna_checkout_enabled_countries = array();
			foreach ( $klarna_checkout_countries as $klarna_checkout_country_code => $klarna_checkout_country ) {
				$lowercase_country_code = strtolower( $klarna_checkout_country_code );
				if ( isset( $checkout_settings[ "eid_$lowercase_country_code" ] ) && isset( $checkout_settings[ "secret_$lowercase_country_code" ] ) ) {
					if ( array_key_exists( $klarna_checkout_country_code, WC()->countries->get_allowed_countries() ) ) {
						$klarna_checkout_enabled_countries[ $klarna_checkout_country_code ] = $klarna_checkout_country;
					}
				}
			}

			// If there's no Klarna enabled countries, or there's only one, bail
			if ( count( $klarna_checkout_enabled_countries ) < 2 ) {
				return;
			}

			if ( WC()->session->get( 'klarna_euro_country' ) ) {
				$kco_euro_country = WC()->session->get( 'klarna_euro_country' );
			} else {
				$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
				$kco_euro_country  = $checkout_settings['default_eur_country'];
			}

			echo '<div class="woocommerce"><p>';
			echo '<label for="klarna-checkout-euro-country">';
			echo __( 'Country:', 'woocommerce-gateway-klarna' );
			echo '<br />';
			echo '<select id="klarna-checkout-euro-country" name="klarna-checkout-euro-country">';
			foreach ( $klarna_checkout_enabled_countries as $klarna_checkout_enabled_country_code => $klarna_checkout_enabled_country ) {
				echo '<option value="' . $klarna_checkout_enabled_country_code . '"' . selected( strtoupper( $klarna_checkout_enabled_country_code ), strtoupper( $kco_euro_country ), false ) . '>' . $klarna_checkout_enabled_country . '</option>';
			}
			echo '</select>';
			echo '</label>';
			echo '</p></div>';

			return ob_get_clean();
		}
	}

	/**
	 * Klarna Checkout widget shortcode callback.
	 *
	 * Parameters:
	 * col            - whether to show it as left or right column in two column layout, options: 'left' and 'right'
	 * order_note     - whether to show order note or not, option: 'false' (to hide it)
	 * 'hide_columns' - select columns to hide, comma separated string, options: 'remove', 'price'
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_widget( $atts ) {
		if ( ! WC()->cart ) {
			return;
		}

		// Don't show on thank you page
		if ( isset( $_GET['thankyou'] ) && 'yes' == $_GET['thankyou'] ) {
			return;
		}

		// Check if widget needs to be displayed
		$checkout_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		if ( 'yes' != $checkout_settings['enabled'] ) {
			return;
		}

		$checkout = WC()->checkout();

		if ( ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
			return;
		}

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$atts = shortcode_atts(
			array(
				'col'           => '',
				'order_note'    => '',
				'coupon_form'   => '',
				'hide_columns'  => '',
				'only_shipping' => '',
				'no_shipping'   => '',
			), $atts
		);

		$widget_class = '';

		if ( 'left' == $atts['col'] ) {
			$widget_class .= ' kco-left-col';
		} elseif ( 'right' == $atts['col'] ) {
			$widget_class .= ' kco-right-col';
		}

		// Recheck cart items so that they are in stock
		$result = WC()->cart->check_cart_item_stock();

		if ( is_wp_error( $result ) ) {
			echo '<p>' . $result->get_error_message() . '</p>';
			// exit();
		}

		if ( count( WC()->cart->get_cart() ) > 0 ) {
			ob_start(); ?>

			<div id="klarna-checkout-widget" class="woocommerce <?php echo $widget_class; ?>">
				<?php echo $this->klarna_checkout_get_kco_widget_html( $atts ); ?>
			</div>

			<?php
			return ob_get_clean();
		}
	}


	/**
	 * Gets Klarna checkout widget HTML.
	 * Used in KCO widget.
	 *
	 * @param  $atts attributes passed to shortcode
	 *
	 * @since  2.0
	 * @return string
	 */
	function klarna_checkout_get_kco_widget_html( $atts = null ) {
		ob_start();

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		if ( $atts ) {
			// When the shortcode is first rendered we store attributes into session.
			WC()->session->set( 'kco_widget_atts', $atts );
		} elseif ( WC()->session->get( 'kco_widget_atts' ) ) {
			// Then we pull them from the session on widget refresh.
			$atts = WC()->session->get( 'kco_widget_atts' );
		} else {
			// Set empty defaults.
			$atts = array(
				'order_note'    => '',
				'coupon_form'   => '',
				'hide_columns'  => '',
				'only_shipping' => '',
				'no_shipping'   => '',
			);
		}
		do_action( 'kco_widget_before_calculation', $atts );
		WC()->cart->calculate_shipping();
		WC()->customer->set_calculated_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();
		?>

		<?php if ( 'yes' == $atts['only_shipping'] ) { ?>
			<div>
				<table id="kco-totals">
					<tbody>
					<?php echo $this->klarna_checkout_get_shipping_options_row_html(); // Shipping options ?>
					</tbody>
				</table>
			</div>
		<?php } else { ?>
			<?php do_action( 'kco_widget_before_coupon', $atts ); ?>

			<!-- Coupons -->
			<?php if ( 'hide' !== $atts['coupon_form'] ) { ?>
				<?php woocommerce_checkout_coupon_form(); ?>
			<?php }; ?>

			<?php if ( apply_filters( 'kco_cart_widget_use_woocommerce_order_review', false ) ) { ?>
				<?php woocommerce_order_review(); ?>
			<?php } else { ?>

				<?php do_action( 'kco_widget_before_cart_items', $atts ); ?>

				<!-- Cart items -->
				<?php do_action( 'klarna_checkout_get_cart_contents_html', $atts ); ?>

				<?php do_action( 'kco_widget_before_totals', $atts ); ?>

				<!-- Totals -->
				<div>
					<table id="kco-totals">
						<tbody>
						<tr id="kco-page-subtotal">
							<td class="kco-col-desc"><?php _e( 'Subtotal', 'woocommerce-gateway-klarna' ); ?></td>
							<td id="kco-page-subtotal-amount" class="kco-col-number kco-rightalign"><span
									class="amount"><?php echo WC()->cart->get_cart_subtotal(); ?></span></td>
						</tr>

						<?php echo $this->klarna_checkout_get_shipping_options_row_html( $atts ); // Shipping options ?>

						<?php echo $this->klarna_checkout_get_fees_row_html(); // Fees ?>

						<?php echo $this->klarna_checkout_get_coupon_rows_html(); // Coupons ?>

						<?php echo $this->klarna_checkout_get_taxes_rows_html(); // Taxes ?>

						<?php do_action( 'kco_widget_before_cart_total', $atts ); ?>

						<tr id="kco-page-total">
							<td class="kco-bold"><?php _e( 'Total', 'woocommerce-gateway-klarna' ); ?></a></td>
							<td id="kco-page-total-amount" class="kco-rightalign kco-bold"><span
									class="amount"><?php echo WC()->cart->get_total(); ?></span></td>
						</tr>
						</tbody>
					</table>
				</div>
			<?php } ?>

			<?php do_action( 'kco_widget_before_order_note', $atts ); ?>

			<!-- Order note -->
			<?php if ( 'hide' != $atts['order_note'] ) { ?>
				<div>
					<form>
						<?php
						if ( WC()->session->get( 'klarna_order_note' ) ) {
							$order_note = WC()->session->get( 'klarna_order_note' );
						} else {
							$order_note = '';
						}
						?>
						<textarea id="klarna-checkout-order-note" class="input-text" name="klarna-checkout-order-note"
								  placeholder="<?php _e( 'Notes about your order, e.g. special notes for delivery.', 'woocommerce-gateway-klarna' ); ?>"><?php echo $order_note; ?></textarea>
					</form>
				</div>
			<?php
}

			do_action( 'kco_widget_after', $atts );
			?>
		<?php
}

		return ob_get_clean();
	}


	/**
	 *
	 *
	 * /**
	 * Gets shipping options as formatted HTML.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_get_shipping_options_row_html( $atts ) {
		if ( 'yes' === $atts['no_shipping'] ) {
			return null;
		}
		ob_start();

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();
		?>

		<tr id="kco-page-shipping">
			<?php
			// if ( WC()->session->get( 'klarna_is_rest', false ) ) { // Just show shipping cost for Rest
			// Temporarily commented out while Klarna works on this feaure and replaced by the check below
			// that always returns true.
			?>
			<?php if ( 1 > 2 ) { // Just show shipping cost for Rest ?>
				<td class="kco-rightalign">
					<?php _e( 'Shipping', 'woocommerce-gateway-klarna' ); ?>
				</td>
				<td id="kco-page-shipping-total">
					<?php echo WC()->cart->get_cart_shipping_total(); ?>
				</td>
			<?php } else { ?>
				<td>
					<?php
					WC()->cart->calculate_shipping();
					$packages = WC()->shipping->get_packages();
					foreach ( $packages as $i => $package ) {
						$chosen_method        = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
						$available_methods    = $package['rates'];
						$show_package_details = count( $packages ) > 1;
						$index                = $i;
						?>
						<?php if ( ! empty( $available_methods ) ) { ?>

							<?php
							if ( apply_filters( 'kco_hide_singular_shipping_method', true ) && 1 === count( $available_methods ) ) {
								$method = current( $available_methods );
								echo wp_kses_post( $method->get_label() );
								?>
								<input type="hidden" name="shipping_method[<?php echo esc_attr( $index ); ?>]"
									   data-index="<?php echo esc_attr( $index ); ?>"
									   id="shipping_method_<?php echo esc_attr( $index ); ?>"
									   value="<?php echo esc_attr( $method->id ); ?>" class="shipping_method"/>
							<?php } else { ?>
								<p style="margin: 0 0 0.5em !important; padding: 0 !important;"><?php _e( 'Shipping', 'woocommerce-gateway-klarna' ); ?></p>
								<ul id="shipping_method">
									<?php foreach ( $available_methods as $method ) : ?>
										<li>
											<input style="margin-left:3px" type="radio"
												   name="shipping_method[<?php echo esc_attr( $index ); ?>]"
												   data-index="<?php echo esc_attr( $index ); ?>"
												   id="shipping_method_<?php echo esc_attr( $index ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>"
												   value="<?php echo esc_attr( $method->id ); ?>" <?php checked( $method->id, $chosen_method ); ?>
												   class="shipping_method"/>
											<label
												for="shipping_method_<?php echo esc_attr( $index ); ?>_<?php echo esc_attr( sanitize_title( $method->id ) ); ?>"><?php echo wp_kses_post( wc_cart_totals_shipping_method_label( $method ) ); ?></label>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php } ?>
						<?php } ?>
						<?php
					}
					?>
				</td>
				<td id="kco-page-shipping-total" class="kco-rightalign">
				<?php
					// @TODO: Check available methods in all packages, not just last one.
				if ( empty( $available_methods ) && WC()->cart->needs_shipping() ) {
					if ( '' === WC()->customer->get_shipping_country() || '' === WC()->customer->get_shipping_postcode() ) {
						WC()->customer->set_calculated_shipping( false );
					} else {
						WC()->customer->set_calculated_shipping( true );
					}

					if ( WC()->customer->has_calculated_shipping() ) {
						echo apply_filters( is_cart() ? 'woocommerce_cart_no_shipping_available_html' : 'woocommerce_no_shipping_available_html', wpautop( __( 'There are no shipping methods available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) );
					} else {
						echo wpautop( __( 'Enter your full address to see shipping costs.', 'woocommerce' ) );
					}
				} else {
					echo WC()->cart->get_cart_shipping_total();
				}
					?>
				</td>
			<?php } ?>
		</tr>
		<?php
		return ob_get_clean();
	}


	/**
	 * Gets shipping options as formatted HTML.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_get_fees_row_html() {
		ob_start();
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		// Fees
		foreach ( WC()->cart->get_fees() as $cart_fee ) {
			echo '<tr class="kco-fee">';
			echo '<td class="kco-col-desc">';
			echo strip_tags( $cart_fee->name );
			echo '</td>';

			echo '<td class="kco-col-number kco-rightalign"><span class="amount">';
			// echo wc_price( $cart_fee->amount + $cart_fee->tax );
			echo wc_cart_totals_fee_html( $cart_fee );
			echo '</span></td>';
			echo '</tr>';
		}

		return ob_get_clean();
	}


	/**
	 * Gets coupons as formatted HTML.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_get_coupon_rows_html() {
		ob_start();

		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
		?>
			<tr class="kco-applied-coupon">
				<td>
					<?php
					echo apply_filters(
						'woocommerce_cart_totals_coupon_label', esc_html(
							__(
								'Coupon:',
								'woocommerce-gateway-klarna'
							) . ' ' . klarna_wc_get_coupon_code( $coupon )
						), $coupon
					);
							?>
					<a class="kco-remove-coupon" data-coupon="<?php echo klarna_wc_get_coupon_code( $coupon ); ?>"
					   href="#"><?php _e( '(remove)', 'woocommerce-gateway-klarna' ); ?></a>
				</td>
				<td class="kco-rightalign">
					-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code, WC()->cart->display_cart_ex_tax ) ); ?></td>
			</tr>
		<?php
		}

		return ob_get_clean();
	}

	/**
	 * Gets coupons as formatted HTML.
	 *
	 * @since  2.0
	 **/
	function klarna_checkout_get_taxes_rows_html() {
		ob_start();
		if ( wc_tax_enabled() && 'excl' === WC()->cart->tax_display_cart ) {
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
				?>
					<tr class="kco-tax-rate kco-tax-rate-<?php echo sanitize_title( $code ); ?>">
						<td><?php echo esc_html( $tax->label ); ?></td>
						<td class="kco-rightalign"
							data-title="<?php echo esc_html( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php
				}
			} else {
			?>
				<tr class="tax-total">
					<td><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></td>
					<td class="kco-rightalign" data-title="
					<?php
					echo esc_html( WC()->countries->tax_or_vat() );
					?>
					"><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php
			}
		}

		return ob_get_clean();
	}
}

$wc_klarna_checkout_shortcodes = new WC_Gateway_Klarna_Shortcodes();
