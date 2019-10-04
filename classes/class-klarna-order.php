<?php
/**
 * Klarna order management
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
 * Class that handles Klarna orders.
 *
 * @property WC_Logger log
 */
class WC_Gateway_Klarna_Order {

	/**
	 * Class constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param bool|order  $order order object
	 * @param bool|Klarna $klarna Klarna object in V2, not needed for Rest
	 */
	public function __construct( $order = false, $klarna = false ) {
		$this->order  = $order;
		$this->klarna = $klarna;
		$this->log    = new WC_Logger();

		// Borrow debug setting from Klarna Checkout.
		$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		$this->debug     = isset( $klarna_settings['debug'] ) ? $klarna_settings['debug'] : '';

		// Cancel order.
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_klarna_order' ) );

		// Capture an order.
		add_action( 'woocommerce_order_status_completed', array( $this, 'activate_klarna_order' ) );

		// Add order item.
		add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'update_klarna_order_add_item' ), 10, 3 );

		// Remove order item.
		add_action( 'woocommerce_before_delete_order_item', array( $this, 'update_klarna_order_delete_item' ) );

		// Edit an order item and save.
		add_action( 'woocommerce_saved_order_items', array( $this, 'update_klarna_order_edit_item' ), 10, 2 );
	}

	/**
	 * Prepare Klarna order for creation.
	 *
	 * @since  2.0
	 **/
	function prepare_order( $klarna_billing, $klarna_shipping, $ship_to_billing_address ) {
		$this->process_order_items();
		$this->process_discount();
		$this->process_fees();
		$this->process_shipping();
		$this->set_addresses( $klarna_billing, $klarna_shipping, $ship_to_billing_address );
	}

		/**
		 * Add shipping and billing address to Klarna update order.
		 *
		 * @since  2.0
		 **/
	function add_addresses() {
		$order    = $this->order;
		$klarna   = $this->klarna;
		$order_id = klarna_wc_get_order_id( $order );

		if ( get_post_meta( $order_id, '_billing_address_2', true ) ) {
			$billing_address = get_post_meta( $order_id, '_billing_address_1', true ) . ' ' . get_post_meta( $order_id, '_billing_address_2', true );
		} else {
			$billing_address = get_post_meta( $order_id, '_billing_address_1', true );
		}

		if ( get_post_meta( $order_id, '_shipping_address_2', true ) ) {
			$shipping_address = get_post_meta( $order_id, '_shipping_address_1', true ) . ' ' . get_post_meta( $order_id, '_shipping_address_2', true );
		} else {
			$shipping_address = get_post_meta( $order_id, '_shipping_address_1', true );
		}

		$billing_addr = new Klarna\XMLRPC\Address(
			get_post_meta( $order_id, '_billing_email', true ), // Email address.
			'', // Telephone number, only one phone number is needed.
			utf8_decode( get_post_meta( $order_id, '_billing_phone', true ) ), // Cell phone number.
			utf8_decode( get_post_meta( $order_id, '_billing_first_name', true ) ), // First name (given name).
			utf8_decode( get_post_meta( $order_id, '_billing_last_name', true ) ), // Last name (family name).
			'', // No care of, C/O.
			utf8_decode( $billing_address ), // Street address.
			utf8_decode( get_post_meta( $order_id, '_billing_postcode', true ) ), // Zip code.
			utf8_decode( get_post_meta( $order_id, '_billing_city', true ) ), // City.
			utf8_decode( get_post_meta( $order_id, '_billing_country', true ) ), // Country.
			null, // House number (AT/DE/NL only).
			null // House extension (NL only).
		);

		$shipping_addr = new Klarna\XMLRPC\Address(
			get_post_meta( $order_id, '_shipping_email', true ), // Email address.
			'', // Telephone number, only one phone number is needed.
			utf8_decode( get_post_meta( $order_id, '_shipping_phone', true ) ), // Cell phone number.
			utf8_decode( get_post_meta( $order_id, '_shipping_first_name', true ) ), // First name (given name).
			utf8_decode( get_post_meta( $order_id, '_shipping_last_name', true ) ), // Last name (family name).
			'', // No care of, C/O.
			utf8_decode( $shipping_address ), // Street address.
			utf8_decode( get_post_meta( $order_id, '_shipping_postcode', true ) ), // Zip code.
			utf8_decode( get_post_meta( $order_id, '_shipping_city', true ) ), // City.
			utf8_decode( get_post_meta( $order_id, '_shipping_country', true ) ), // Country.
			null, // House number (AT/DE/NL only).
			null // House extension (NL only).
		);

		$klarna->setAddress( Klarna\XMLRPC\Flags::IS_BILLING, $billing_addr );
		$klarna->setAddress( Klarna\XMLRPC\Flags::IS_SHIPPING, $shipping_addr );
		$klarna->setEstoreInfo( $orderid1 = ltrim( $order->get_order_number(), '#' ), $orderid2 = $order_id );
	}

	/**
	 * Process cart contents.
	 *
	 * @param  $skip_item item ID to skip from adding, used when item is removed from cart widget
	 * @since  2.0
	 **/
	function process_order_items( $skip_item = null ) {
		$order  = $this->order;
		$klarna = $this->klarna;
		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item_key => $item ) {
				// Check if an item has been removed
				if ( $item_key != $skip_item ) {
					$_product = $order->get_product_from_item( $item );
					if ( $_product->exists() && $item['qty'] ) {
						// We manually calculate the tax percentage here
						$item_tax_percentage = 0;
						$item_total_tax      = is_callable( array( $item, 'get_total_tax' ) ) ? $item->get_total_tax() : 0;
						if ( $item_total_tax ) {
							// Calculate tax percentage
							$item_tax_percentage = @number_format( $item_total_tax / $order->get_line_total( $item, false, false ) * 100, 2, '.', '' );
						}
						// apply_filters to item price so we can filter this if needed
						$klarna_item_price_including_tax = $order->get_item_total( $item, true, false );
						$item_price                      = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
						// Get SKU or product id
						$reference = '';
						if ( $_product->get_sku() ) {
							$reference = $_product->get_sku();
						} elseif ( klarna_wc_get_product_variation_id( $_product ) ) {
							$reference = klarna_wc_get_product_variation_id( $_product );
						} else {
							$reference = klarna_wc_get_product_id( $_product );
						}

						$klarna->addArticle(
							$qty      = $item['qty'],                  // Quantity
							$artNo    = strval( $reference ),          // Article number
							$title    = utf8_decode( $item['name'] ),   // Article name/title
							$price    = $item_price,                   // Price including tax
							$vat      = round( $item_tax_percentage ), // Tax
							$discount = 0,                             // Discount is applied later
							$flags    = Klarna\XMLRPC\Flags::INC_VAT           // Price is including VAT.
						);
					}
				}
			}
		}
	}

	/**
	 * Process discount.
	 *
	 * @since  2.0
	 **/
	function process_discount() {
		$order  = $this->order;
		$klarna = $this->klarna;
		if ( WC()->cart->applied_coupons ) {
			foreach ( WC()->cart->applied_coupons as $code ) {
				$smart_coupon = new WC_Coupon( $code );
				if ( $smart_coupon->is_valid() && klarna_wc_get_coupon_discount_type( $smart_coupon ) === 'smart_coupon' ) {
					$klarna->addArticle( $qty = 1, $artNo = '', $title = __( 'Discount', 'woocommerce-gateway-klarna' ), $price = - WC()->cart->coupon_discount_amounts[ $code ], $vat = 0, $discount = 0, $flags = Klarna\XMLRPC\Flags::INC_VAT );
				}
			}
		}
		/*
		if ( $order->order_discount > 0 ) {
			// apply_filters to order discount so we can filter this if needed
			$klarna_order_discount = $order->order_discount;
			$order_discount = apply_filters( 'klarna_order_discount', $klarna_order_discount );

			$klarna->addArticle(
					$qty = 1,
					$artNo = '',
					$title = __( 'Discount', 'woocommerce-gateway-klarna' ),
					$price = -$order_discount,
					$vat = 0,
					$discount = 0,
					$flags = Klarna\XMLRPC\Flags::INC_VAT
			);
		}
		*/
	}

	/**
	 * Process fees.
	 *
	 * @since  2.0
	 **/
	function process_fees() {
		$order  = $this->order;
		$klarna = $this->klarna;

		if ( count( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
				if ( $order->get_total_tax() > 0 ) {
					$item_tax_percentage = number_format( ( $item['line_tax'] / $item['line_total'] ) * 100, 2, '.', '' );
				} else {
					$item_tax_percentage = 0.00;
				}

				$invoice_settings    = get_option( 'woocommerce_klarna_invoice_settings' );
				$invoice_fee_id      = $invoice_settings['invoice_fee_id'];
				$invoice_fee_product = wc_get_product( $invoice_fee_id );
				$invoice_art_no      = '';

				if ( $invoice_fee_product ) {
					$invoice_fee_name = $invoice_fee_product->get_title();
					if ( $invoice_fee_product->get_sku() ) {
						$invoice_art_no = $invoice_fee_product->get_sku();
					}
				} else {
					$invoice_fee_name = '';
				}

				// Invoice fee or regular fee.
				if ( $invoice_fee_name === $item['name'] ) {
					$klarna_flags = Klarna\XMLRPC\Flags::INC_VAT + Klarna\XMLRPC\Flags::IS_HANDLING; // Price is including VAT and is handling/invoice fee.
				} else {
					$klarna_flags = Klarna\XMLRPC\Flags::INC_VAT; // Price is including VAT.
				}

				// apply_filters to item price so we can filter this if needed.
				$klarna_item_price_including_tax = $item['line_total'] + $item['line_tax'];
				$item_price                      = apply_filters( 'klarna_fee_price_including_tax', $klarna_item_price_including_tax );
				$klarna->addArticle( 1, $invoice_art_no, utf8_decode( $item['name'] ), $item_price, round( $item_tax_percentage ), 0, $klarna_flags );
			} // End foreach().
		} // End if().
	}

	/**
	 * Process shipping.
	 *
	 * @since  2.0
	 **/
	function process_shipping() {
		$order  = $this->order;
		$klarna = $this->klarna;
		if ( $order->get_total_shipping() > 0 ) {
			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ( $order->get_shipping_tax() / $order->get_total_shipping() ) * 100; // 25.00
			$calculated_shipping_tax_decimal    = ( $order->get_shipping_tax() / $order->get_total_shipping() ) + 1; // 0.25
			// apply_filters to Shipping so we can filter this if needed
			$klarna_shipping_price_including_tax = $order->get_total_shipping() * $calculated_shipping_tax_decimal;
			$shipping_price                      = apply_filters( 'klarna_shipping_price_including_tax', $klarna_shipping_price_including_tax );
			$klarna->addArticle(
				$qty                             = 1,
				$artNo                           = 'SHIPPING',
				$title                           = $order->get_shipping_method(),
				$price                           = $shipping_price,
				$vat                             = round( $calculated_shipping_tax_percentage ),
				$discount                        = 0,
				$flags                           = Klarna\XMLRPC\Flags::INC_VAT + Klarna\XMLRPC\Flags::IS_SHIPMENT // Price is including VAT and is shipment fee
			);
		}
	}

	/**
	 * Set shipping and billing address.
	 *
	 * @since  2.0
	 **/
	function set_addresses( $klarna_billing, $klarna_shipping, $ship_to_billing_address ) {
		$order                           = $this->order;
		$klarna                          = $this->klarna;
		$klarna_billing_address          = $klarna_billing['address'];
		$klarna_billing_house_number     = $klarna_billing['house_number'];
		$klarna_billing_house_extension  = $klarna_billing['house_extension'];
		$klarna_shipping_address         = $klarna_shipping['address'];
		$klarna_shipping_house_number    = $klarna_shipping['house_number'];
		$klarna_shipping_house_extension = $klarna_shipping['house_extension'];
		// Billing address
		$addr_billing = new Klarna\XMLRPC\Address(
			$email    = $order->get_billing_email(),
			$telno    = '', // We skip the normal land line phone, only one is needed.
			$cellno   = $order->get_billing_phone(),
			$fname    = utf8_decode( $order->get_billing_first_name() ),
			$lname    = utf8_decode( $order->get_billing_last_name() ),
			$careof   = utf8_decode( $order->get_billing_address_2() ),  // No care of, C/O.
			$street   = utf8_decode( $klarna_billing_address ), // For DE and NL specify street number in houseNo.
			$zip      = utf8_decode( $order->get_billing_postcode() ),
			$city     = utf8_decode( $order->get_billing_city() ),
			$country  = utf8_decode( $order->get_billing_country() ),
			$houseNo  = utf8_decode( $klarna_billing_house_number ), // For DE and NL we need to specify houseNo.
			$houseExt = utf8_decode( $klarna_billing_house_extension ) // Only required for NL.
		);
		// Add Company if one is set
		if ( $order->get_billing_company() ) {
			$addr_billing->setCompanyName( utf8_decode( $order->get_billing_company() ) );
		}
		// Shipping address
		if ( $order->get_shipping_method() == '' || $ship_to_billing_address == 'yes' ) {
			// Use billing address if Shipping is disabled in Woocommerce
			$addr_shipping = new Klarna\XMLRPC\Address(
				$email     = $order->get_billing_email(),
				$telno     = '', // We skip the normal land line phone, only one is needed.
				$cellno    = $order->get_billing_phone(),
				$fname     = utf8_decode( $order->get_billing_first_name() ),
				$lname     = utf8_decode( $order->get_billing_last_name() ),
				$careof    = utf8_decode( $order->get_billing_address_2() ),  // No care of, C/O.
				$street    = utf8_decode( $klarna_billing_address ), // For DE and NL specify street number in houseNo.
				$zip       = utf8_decode( $order->get_billing_postcode() ),
				$city      = utf8_decode( $order->get_billing_city() ),
				$country   = utf8_decode( $order->get_billing_country() ),
				$houseNo   = utf8_decode( $klarna_billing_house_number ), // For DE and NL we need to specify houseNo.
				$houseExt  = utf8_decode( $klarna_billing_house_extension ) // Only required for NL.
			);
			// Add Company if one is set
			if ( $order->get_billing_company() ) {
				$addr_shipping->setCompanyName( utf8_decode( $order->get_billing_company() ) );
			}
		} else {
			$addr_shipping = new Klarna\XMLRPC\Address(
				$email     = $order->get_billing_email(),
				$telno     = '', // We skip the normal land line phone, only one is needed.
				$cellno    = $order->get_billing_phone(),
				$fname     = utf8_decode( $order->get_shipping_first_name() ),
				$lname     = utf8_decode( $order->get_shipping_last_name() ),
				$careof    = utf8_decode( $order->get_shipping_address_2() ),  // No care of, C/O.
				$street    = utf8_decode( $klarna_shipping_address ), // For DE and NL specify street number in houseNo.
				$zip       = utf8_decode( $order->get_shipping_postcode() ),
				$city      = utf8_decode( $order->get_shipping_city() ),
				$country   = utf8_decode( $order->get_shipping_country() ),
				$houseNo   = utf8_decode( $klarna_shipping_house_number ), // For DE and NL we need to specify houseNo.
				$houseExt  = utf8_decode( $klarna_shipping_house_extension ) // Only required for NL.
			);
			// Add Company if one is set
			if ( $order->get_shipping_company() ) {
				$addr_shipping->setCompanyName( utf8_decode( $order->get_shipping_company() ) );
			}
		}
		// Next we tell the Klarna instance to use the address in the next order.
		$klarna->setAddress( Klarna\XMLRPC\Flags::IS_BILLING, $addr_billing ); // Billing / invoice address
		$klarna->setAddress( Klarna\XMLRPC\Flags::IS_SHIPPING, $addr_shipping ); // Shipping / delivery address
	}

	/**
	 * Refunds a Klarna order
	 *
	 * @since  2.0
	 **/
	function refund_order( $amount, $reason = '', $invNo ) {
		$order     = $this->order;
		$klarna    = $this->klarna;
		$refund_id = self::get_refunded_order_id( $order->get_id() );

		if ( ! empty( $refund_id ) ) {
			$refund_order                = wc_get_order( $refund_id );
			$refunded_items              = $refund_order->get_items();
			$refund_items_got_full_price = self::check_refund_items_got_full_price( $refunded_items, $order );
		}

		/**
		 * Check if return amount is equal to order total, if yes
		 * refund entire order.
		 */
		if ( $order->get_total() == $amount ) {
			try {
				$ocr = $klarna->creditInvoice( $invNo ); // Invoice number
				if ( $ocr ) {
					$order->add_order_note( sprintf( __( 'Klarna order fully refunded.', 'woocommerce-gateway-klarna' ), $ocr ) );

					return true;
				}
			} catch ( Exception $e ) {
				if ( $this->debug == 'yes' ) {
					$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
				}
				$order->add_order_note( sprintf( __( 'Klarna order refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

				return false;
			}
		}

		/**
		 * If we got refunded items and all item prices are the same as in the original order - perform a line item refund.
		 */
		if ( ! empty( $refunded_items ) && $refund_items_got_full_price ) {
			// Products.
			foreach ( $refunded_items as $item ) {
				$product = $item->get_product();
				$klarna->addArtNo( abs( $item['qty'] ), self::get_item_reference( $product ) );
			}
			// Shipping.
			if ( $refund_order->get_shipping_total() < 0 ) {
				if ( abs( $refund_order->get_shipping_total() ) == $order->get_shipping_total() ) {
					// Full shipping refund.
					$klarna->addArtNo( 1, self::get_refund_shipping_reference( $refund_order, $order ) );
				} else {
					// Partial shipping refund.
					$shipping_price_incl_tax = abs( round( $refund_order->get_shipping_total() + $refund_order->get_shipping_tax(), 2 ) );
					if ( $refund_order->get_shipping_tax() < 0 ) {
						$shipping_tax = round( ( abs( $refund_order->get_shipping_tax() ) / abs( $refund_order->get_shipping_total() ) ) * 100, 2 );
					} else {
						$shipping_tax = 0;
					}
					$ocr_shipping = $klarna->returnAmount( // returns 1 on success
						$invNo,                // Invoice number
						$shipping_price_incl_tax,           // Amount given as a discount.
						$shipping_tax,             // VAT (%)
						Klarna\XMLRPC\Flags::INC_VAT,  // Amount including VAT.
						utf8_encode( sprintf( __( 'Shipping refund', 'woocommerce-gateway-klarna' ), $shipping_price_incl_tax ) ) // Description
					);
					if ( $ocr_shipping ) {
						$order->add_order_note( sprintf( __( 'Shipping cost partially refunded in Klarna. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $shipping_price_incl_tax, array( 'currency' => $order->get_currency() ) ) ) );
					}
				}
			}
			try {
				$ocr = $klarna->creditPart( $invNo ); // Invoice number
				if ( $ocr ) {
					$order->add_order_note( sprintf( __( 'Klarna order refunded.', 'woocommerce-gateway-klarna' ), $ocr ) );

					return true;
				}
			} catch ( Exception $e ) {
				if ( $this->debug == 'yes' ) {
					$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
				}
				$order->add_order_note( sprintf( __( 'Klarna order refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

				return false;
			}
		} else {
			/**
			 * If we got refunded items but prices differ from the original order.
			 * Use Klarnas returnAmount feature (good-will refunds).
			 */
			if ( ! empty( $refunded_items ) ) {
				// We got items in the refund order, lets add each item as a return amount.
				$refund_status = true;
				foreach ( $refunded_items as $item ) {
					try {
						$product    = $item->get_product();
						$item_price = abs( round( $item->get_total() + $item->get_total_tax(), 2 ) );
						$_tax       = new WC_Tax();
						$tmp_rates  = $_tax->get_rates( $product->get_tax_class() );
						$vat        = array_shift( $tmp_rates );
						if ( isset( $vat['rate'] ) ) {
							$item_tax_rate = round( $vat['rate'] );
						} else {
							$item_tax_rate = 0;
						}
						$ocr = $klarna->returnAmount( // returns 1 on success
							$invNo,                // Invoice number
							$item_price,           // Amount given as a discount.
							$item_tax_rate,             // VAT (%)
							Klarna\XMLRPC\Flags::INC_VAT,  // Amount including VAT.
							utf8_encode( sprintf( __( 'Refund for SKU %s.', 'woocommerce-gateway-klarna' ), self::get_item_reference( $product ) ) ) // Description
						);
						if ( $ocr ) {
							$order->add_order_note( sprintf( __( 'Klarna order partially refunded. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $item_price, array( 'currency' => $order->get_currency() ) ) ) );
						}
					} catch ( Exception $e ) {
						if ( $this->debug == 'yes' ) {
							$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
						}
						$order->add_order_note( sprintf( __( 'Klarna order refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
						$refund_status = false;
					}
				}

				// Maybe refund shipping.
				if ( $refund_order->get_shipping_total() < 0 ) {
					if ( abs( $refund_order->get_shipping_total() ) == $order->get_shipping_total() ) {
						// Full shipping refund.
						$klarna->addArtNo( 1, self::get_refund_shipping_reference( $refund_order, $order ) );
						try {
							$ocr = $klarna->creditPart( $invNo ); // Invoice number
							if ( $ocr ) {
								$order->add_order_note( sprintf( __( 'Klarna shipping refunded.', 'woocommerce-gateway-klarna' ), $ocr ) );
							}
						} catch ( Exception $e ) {
							if ( $this->debug == 'yes' ) {
								$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
							}
							$order->add_order_note( sprintf( __( 'Klarna shipping refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
						}
					} else {
						// Partial shipping refund.
						$shipping_price_incl_tax = abs( round( $refund_order->get_shipping_total() + $refund_order->get_shipping_tax(), 2 ) );
						if ( $refund_order->get_shipping_tax() < 0 ) {
							$shipping_tax = round( ( abs( $refund_order->get_shipping_tax() ) / abs( $refund_order->get_shipping_total() ) ) * 100, 2 );
						} else {
							$shipping_tax = 0;
						}
						$ocr_shipping = $klarna->returnAmount( // returns 1 on success
							$invNo,                // Invoice number
							$shipping_price_incl_tax,           // Amount given as a discount.
							$shipping_tax,             // VAT (%)
							Klarna\XMLRPC\Flags::INC_VAT,  // Amount including VAT.
							utf8_encode( sprintf( __( 'Shipping refund', 'woocommerce-gateway-klarna' ), $shipping_price_incl_tax ) ) // Description
						);
						if ( $ocr_shipping ) {
							$order->add_order_note( sprintf( __( 'Shipping cost partially refunded in Klarna. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $shipping_price_incl_tax, array( 'currency' => $order->get_currency() ) ) ) );
						}
					}
				}
				return $refund_status;
			} else {
				/**
				 * We don't have any refunded items.
				 * Use Klarnas returnAmount feature (good-will refunds) unless it is a full refund of shipping.
				 */

				// Check if the refund order include shipping.
				if ( $refund_order->get_shipping_total() < 0 ) {
					if ( abs( $refund_order->get_shipping_total() ) == $order->get_shipping_total() ) {
						// Full shipping refund.
						$klarna->addArtNo( 1, self::get_refund_shipping_reference( $refund_order, $order ) );
						try {
							$ocr = $klarna->creditPart( $invNo ); // Invoice number
							if ( $ocr ) {
								$order->add_order_note( sprintf( __( 'Klarna shipping refunded.', 'woocommerce-gateway-klarna' ), $ocr ) );

								return true;
							}
						} catch ( Exception $e ) {
							if ( $this->debug == 'yes' ) {
								$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
							}
							$order->add_order_note( sprintf( __( 'Klarna shipping refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

							return false;
						}
					} else {
						// Partial shipping refund.
						$shipping_price_incl_tax = abs( round( $refund_order->get_shipping_total() + $refund_order->get_shipping_tax(), 2 ) );
						if ( $refund_order->get_shipping_tax() < 0 ) {
							$shipping_tax = round( ( abs( $refund_order->get_shipping_tax() ) / abs( $refund_order->get_shipping_total() ) ) * 100, 2 );
						} else {
							$shipping_tax = 0;
						}
						$ocr_shipping = $klarna->returnAmount( // returns 1 on success
							$invNo,                // Invoice number
							$shipping_price_incl_tax,           // Amount given as a discount.
							$shipping_tax,             // VAT (%)
							Klarna\XMLRPC\Flags::INC_VAT,  // Amount including VAT.
							utf8_encode( sprintf( __( 'Shipping refund', 'woocommerce-gateway-klarna' ), $shipping_price_incl_tax ) ) // Description
						);
						if ( $ocr_shipping ) {
							$order->add_order_note( sprintf( __( 'Shipping cost partially refunded in Klarna. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $shipping_price_incl_tax, array( 'currency' => $order->get_currency() ) ) ) );
							return true;
						} else {
							return false;
						}
					}
				} else {
					// Merchant have only entered an amount in the Refund amount field.
					// Lets send the amount with 0 tax rate.
					try {
						$ocr = $klarna->returnAmount( // returns 1 on success
							$invNo,                // Invoice number
							$amount,               // Amount given as a discount.
							0,             // VAT (%)
							Klarna\XMLRPC\Flags::INC_VAT,  // Amount including VAT.
							utf8_encode( $reason ) // Description
						);
						if ( $ocr ) {
							$order->add_order_note( sprintf( __( 'Klarna order partially refunded. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ) );

							return true;
						}
					} catch ( Exception $e ) {
						if ( $this->debug == 'yes' ) {
							$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
						}
						$order->add_order_note( sprintf( __( 'Klarna order refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

						return false;
					}
				}
			}
			return false;
		}

		return false;
	}

	/**
	 * Refunds a Klarna order for Rest API
	 *
	 * @since  2.0
	 **/
	function refund_order_rest( $amount, $reason = '', $k_order ) {
		$order    = $this->order;
		$order_id = klarna_wc_get_order_id( $order );
		try {
			$k_order->refund(
				array(
					'refunded_amount' => $amount * 100,
					'description'     => utf8_encode( $reason ),
				)
			);
			$order->add_order_note( sprintf( __( 'Klarna order refunded. Refund amount: %s.', 'woocommerce-gateway-klarna' ), wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ) );

			return true;
		} catch ( Exception $e ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
			}
			$order->add_order_note( sprintf( __( 'Klarna order refund failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

			return false;
		}
	}

	/**
	 * Set up Klarna configuration.
	 *
	 * @since  2.0
	 **/
	function configure_klarna( $klarna, $country, $payment_method ) {
		if ( 'klarna_invoice' == $payment_method ) {
			$klarna_settings = get_option( 'woocommerce_klarna_invoice_settings' );
		} elseif ( 'klarna_part_payment' == $payment_method ) {
			$klarna_settings = get_option( 'woocommerce_klarna_part_payment_settings' );
		} elseif ( 'klarna_checkout' == $payment_method ) {
			$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		}
		// Country and language
		switch ( $country ) {
			case 'NO':
			case 'NB':
				$klarna_country  = 'NO';
				$klarna_language = 'nb-no';
				$klarna_currency = 'NOK';
				$klarna_eid      = $klarna_settings['eid_no'];
				$klarna_secret   = $klarna_settings['secret_no'];
				break;
			case 'FI':
				$klarna_country = 'FI';
				// Check if WPML is used and determine if Finnish or Swedish is used as language
				if ( class_exists( 'woocommerce_wpml' ) && defined( 'ICL_LANGUAGE_CODE' ) && strtoupper( ICL_LANGUAGE_CODE ) == 'SV' ) {
					$klarna_language = 'sv-fi'; // Swedish
				} else {
					$klarna_language = 'fi-fi'; // Finnish
				}
				$klarna_currency = 'EUR';
				$klarna_eid      = $klarna_settings['eid_fi'];
				$klarna_secret   = $klarna_settings['secret_fi'];
				break;
			case 'SE':
			case 'SV':
				$klarna_country  = 'SE';
				$klarna_language = 'sv-se';
				$klarna_currency = 'SEK';
				$klarna_eid      = $klarna_settings['eid_se'];
				$klarna_secret   = $klarna_settings['secret_se'];
				break;
			case 'DE':
				$klarna_country  = 'DE';
				$klarna_language = 'de-de';
				$klarna_currency = 'EUR';
				$klarna_eid      = $klarna_settings['eid_de'];
				$klarna_secret   = $klarna_settings['secret_de'];
				break;
			case 'AT':
				$klarna_country  = 'AT';
				$klarna_language = 'de-at';
				$klarna_currency = 'EUR';
				$klarna_eid      = $klarna_settings['eid_at'];
				$klarna_secret   = $klarna_settings['secret_at'];
				break;
			case 'GB':
				$klarna_country  = 'gb';
				$klarna_language = 'en-gb';
				$klarna_currency = 'gbp';
				$klarna_eid      = $klarna_settings['eid_uk'];
				$klarna_secret   = html_entity_decode( $klarna_settings['secret_uk'] );
				break;
			case 'US':
				$klarna_country  = 'us';
				$klarna_language = 'en-us';
				$klarna_currency = 'usd';
				$klarna_eid      = $klarna_settings['eid_us'];
				$klarna_secret   = html_entity_decode( $klarna_settings['secret_us'] );
				break;
			case 'NL':
				$klarna_country  = 'NL';
				$klarna_language = 'nl-NL';
				$klarna_currency = 'EUR';
				$klarna_eid      = $klarna_settings['eid_nl'];
				$klarna_secret   = $klarna_settings['secret_nl'];
				break;
			default:
				$klarna_country  = '';
				$klarna_language = '';
				$klarna_currency = '';
				$klarna_eid      = '';
				$klarna_secret   = '';
		}
		// Test mode or Live mode
		if ( $klarna_settings['testmode'] == 'yes' ) {
			// Disable SSL if in testmode
			$klarna_ssl  = 'false';
			$klarna_mode = Klarna\XMLRPC\Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$klarna_ssl = 'true';
			} else {
				$klarna_ssl = 'false';
			}
			$klarna_mode = Klarna\XMLRPC\Klarna::LIVE;
		}
		$klarna->config( $eid = $klarna_eid, $secret = $klarna_secret, $country = $country, $language = $klarna_language, $currency = $klarna_currency, $mode = $klarna_mode, $pcStorage = 'json', $pcURI = '/srv/pclasses.json', $ssl = $klarna_ssl, $candice = false );
	}

	/**
	 * Order activation wrapper function
	 *
	 * @since  2.0
	 **/
	function activate_klarna_order( $orderid ) {
		$order                      = wc_get_order( $orderid );
		$payment_method             = $this->get_order_payment_method( $order );
		$payment_method_option_name = 'woocommerce_' . $payment_method . '_settings';
		$payment_method_option      = get_option( $payment_method_option_name );
		// Check if option is enabled
		if ( isset( $payment_method_option['push_completion'] ) && 'yes' === $payment_method_option['push_completion'] ) {
			// If this reservation was already cancelled, do nothing.
			if ( get_post_meta( $orderid, '_klarna_order_activated', true ) && ! get_post_meta( $orderid, '_klarna_order_skip_activated_note', true ) ) {
				$order->add_order_note( __( 'Could not activate Klarna reservation, Klarna reservation is already activated.', 'woocommerce-gateway-klarna' ) );

				return;
			}
			// If this reservation was already cancelled, do nothing.
			if ( get_post_meta( $orderid, '_klarna_order_cancelled', true ) ) {
				$order->add_order_note( __( 'Could not activate Klarna reservation, Klarna reservation was previously cancelled.', 'woocommerce-gateway-klarna' ) );

				return;
			}
			// Check if this order hasn't been activated already
			if ( ! get_post_meta( $orderid, '_klarna_invoice_number', true ) ) {
				// Activation for orders created with KCO Rest
				if ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {
					$this->activate_order_rest( $orderid );
					// Activation for KCO V2 and KPM orders
				} else {
					$this->activate_order( $orderid );
				}
			}
		}
	}

	/**
	 * Activates a Klarna order for V2 API
	 *
	 * @since  2.0
	 **/
	function activate_order( $orderid ) {
		$order = wc_get_order( $orderid );
		if ( get_post_meta( $orderid, '_klarna_order_reservation', true ) && get_post_meta( $orderid, '_billing_country', true ) ) {
			$rno            = get_post_meta( $orderid, '_klarna_order_reservation', true );
			$payment_method = get_post_meta( $orderid, '_payment_method', true );
			// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
			if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
				$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
			} else {
				$country = get_post_meta( $orderid, '_billing_country', true );
			}
			// Check if this is a subscription order
			if ( class_exists( 'WC_Subscriptions_Renewal_Order' ) && WC_Subscriptions_Renewal_Order::is_renewal( $order ) ) {
				if ( ! get_post_meta( $orderid, '_klarna_order_reservation_recurring', true ) ) {
					return;
				}
				$rno = get_post_meta( $orderid, '_klarna_order_reservation_recurring', true );
			}
			$klarna = new Klarna\XMLRPC\Klarna();
			$this->configure_klarna( $klarna, $country, $payment_method );
			try {
				$result = $klarna->activate(
					$rno,
					null, // OCR Number
					Klarna\XMLRPC\Flags::RSRV_SEND_BY_EMAIL
				);
				$risk   = $result[0]; // returns 'ok' or 'no_risk'
				$invNo  = $result[1]; // returns invoice number
				$order->add_order_note( sprintf( __( 'Klarna order activated. Invoice number %1$s - risk status %2$s.', 'woocommerce-gateway-klarna' ), $invNo, $risk ) );
				update_post_meta( $orderid, '_klarna_order_activated', time() );
				update_post_meta( $orderid, '_klarna_invoice_number', $invNo );
				update_post_meta( $orderid, '_transaction_id', $invNo );
			} catch ( Exception $e ) {
				if ( $this->debug == 'yes' ) {
					$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
				}
				$order->add_order_note( sprintf( __( 'Klarna order activation failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
			}
		}
	}

	/**
	 * Activates a Klarna order for Rest API
	 *
	 * @since  2.0
	 **/
	function activate_order_rest( $orderid ) {
		$order           = wc_get_order( $orderid );
		$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
		if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
			$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
		} else {
			$country = get_post_meta( $orderid, '_billing_country', true );
		}
		/**
		 * Need to send local order to constructor and Klarna order to method
		 */
		if ( $klarna_settings['testmode'] == 'yes' ) {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
			}
		} else {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
			}
		}
		if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
			if ( 'gb' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_uk'];
				$secret = html_entity_decode( $klarna_settings['secret_uk'] );
			} elseif ( 'dk' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_dk'];
				$secret = html_entity_decode( $klarna_settings['secret_dk'] );
			} elseif ( 'nl' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_nl'];
				$secret = html_entity_decode( $klarna_settings['secret_nl'] );
			}
			$connector = Klarna\Rest\Transport\Connector::create( $eid, $secret, $klarna_server_url );
		} elseif ( 'us' === strtolower( $country ) ) {
			$connector = Klarna\Rest\Transport\Connector::create( $klarna_settings['eid_us'], html_entity_decode( $klarna_settings['secret_us'] ), $klarna_server_url );
		}
		$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
		$k_order         = new Klarna\Rest\OrderManagement\Order( $connector, $klarna_order_id );
		$k_order->fetch();
		// Capture full order amount on WooCommerce order completion
		$data = array(
			'captured_amount' => $k_order['order_amount'],
			'description'     => __( 'WooCommerce order marked complete', 'woocommerce-gateway-klarna' ),
			'order_lines'     => $k_order['order_lines'],
		);
		try {
			$k_order->createCapture( $data );
			$k_order->fetch();
			$order->add_order_note( sprintf( __( 'Klarna order captured. Invoice number %s.', 'woocommerce-gateway-klarna' ), $k_order['captures'][0]['capture_id'] ) );
			update_post_meta( $orderid, '_klarna_order_activated', time() );
			update_post_meta( $orderid, '_klarna_invoice_number', $k_order['captures'][0]['capture_id'] );
			update_post_meta( $orderid, '_transaction_id', $k_order['captures'][0]['capture_id'] );
		} catch ( Exception $e ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
			}
			$order->add_order_note( sprintf( __( 'Klarna order activation failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
		}
	}

	/**
	 * Order cancellation wrapper function
	 *
	 * @since  2.0
	 **/
	function cancel_klarna_order( $orderid ) {
		$order                      = wc_get_order( $orderid );
		$payment_method             = $this->get_order_payment_method( $order );
		$payment_method_option_name = 'woocommerce_' . $payment_method . '_settings';
		$payment_method_option      = get_option( $payment_method_option_name );
		// Check if option is enabled
		if ( 'yes' == $payment_method_option['push_cancellation'] ) {
			// Check if this order hasn't been activated already
			if ( ! get_post_meta( $orderid, '_klarna_order_cancelled', true ) ) {
				// Activation for orders created with KCO Rest
				if ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {
					$this->cancel_order_rest( $orderid );
					// Activation for KCO V2 and KPM orders
				} else {
					$this->cancel_order( $orderid );
				}
			} else {
				$order->add_order_note( __( 'Could not activate Klarna reservation, Klarna reservation is already cancelled.', 'woocommerce-gateway-klarna' ) );

				return;
			}
		}
	}

	/**
	 * Cancels a Klarna order for V2 API
	 *
	 * @since  2.0
	 **/
	function cancel_order( $orderid ) {
		$order = wc_get_order( $orderid );
		// Klarna reservation number and billing country must be set
		if ( get_post_meta( $orderid, '_klarna_order_reservation', true ) && get_post_meta( $orderid, '_billing_country', true ) && ! get_post_meta( $orderid, '_klarna_order_activated', true ) ) {
			$rno = get_post_meta( $orderid, '_klarna_order_reservation', true );
			// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
			if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
				$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
			} else {
				$country = get_post_meta( $orderid, '_billing_country', true );
			}
			$payment_method = get_post_meta( $orderid, '_payment_method', true );
			$klarna         = new Klarna\XMLRPC\Klarna();
			$this->configure_klarna( $klarna, $country, $payment_method );
			try {
				$klarna->cancelReservation( $rno );
				$order->add_order_note( __( 'Klarna order cancellation completed.', 'woocommerce-gateway-klarna' ) );
				add_post_meta( $orderid, '_klarna_order_cancelled', time() );
			} catch ( Exception $e ) {
				if ( $this->debug == 'yes' ) {
					$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
				}
				$order->add_order_note( sprintf( __( 'Klarna order cancellation failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
			}
		}
	}

	/**
	 * Cancels a Klarna order for Rest API
	 *
	 * @since  2.0
	 **/
	function cancel_order_rest( $orderid ) {
		$order           = wc_get_order( $orderid );
		$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
		if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
			$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
		} else {
			$country = get_post_meta( $orderid, '_billing_country', true );
		}
		/**
		 * Need to send local order to constructor and Klarna order to method
		 */
		if ( $klarna_settings['testmode'] == 'yes' ) {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
			}
		} else {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
			}
		}
		if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
			if ( 'gb' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_uk'];
				$secret = html_entity_decode( $klarna_settings['secret_uk'] );
			} elseif ( 'dk' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_dk'];
				$secret = html_entity_decode( $klarna_settings['secret_dk'] );
			} elseif ( 'nl' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_nl'];
				$secret = html_entity_decode( $klarna_settings['secret_nl'] );
			}

			$connector = Klarna\Rest\Transport\Connector::create( $eid, $secret, $klarna_server_url );
		} elseif ( 'us' === strtolower( $country ) ) {
			$connector = Klarna\Rest\Transport\Connector::create( $klarna_settings['eid_us'], html_entity_decode( $klarna_settings['secret_us'] ), $klarna_server_url );
		}
		$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
		$k_order         = new Klarna\Rest\OrderManagement\Order( $connector, $klarna_order_id );
		$k_order->fetch();
		try {
			$k_order->cancel();
			$order->add_order_note( __( 'Klarna order cancelled.', 'woocommerce-gateway-klarna' ) );
			add_post_meta( $orderid, '_klarna_order_cancelled', time() );
		} catch ( Exception $e ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
			}
			$order->add_order_note( sprintf( __( 'Klarna order cancelation failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
		}
	}

	/**
	 * Order update wrapper function
	 *
	 * @since  2.0
	 **/
	function update_klarna_order_add_item( $itemid, $item ) {
		// Get item row from the database table, needed for order id
		global $wpdb;
		$item_row = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT      order_id
				FROM        {$wpdb->prefix}woocommerce_order_items
				WHERE       order_item_id = %d
			",
				$itemid
			)
		);
		$orderid  = $item_row->order_id;
		$order    = wc_get_order( $orderid );
		if ( $this->order_is_updatable( $order ) ) {
			if ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {
				$this->update_order_rest( $orderid );
				// Activation for KCO V2 and KPM orders
			} else {
				$this->update_order( $orderid );
			}
		}
	}

	/**
	 * Update order in Klarna system, add new item
	 *
	 * @since  2.0.0
	 */
	function update_klarna_order_delete_item( $itemid ) {
		// Get item row from the database table, needed for order id
		global $wpdb;
		$item_row = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT      order_id
				FROM        {$wpdb->prefix}woocommerce_order_items
				WHERE       order_item_id = %d
			",
				$itemid
			)
		);
		$orderid  = $item_row->order_id;
		$order    = wc_get_order( $orderid );
		if ( $this->order_is_updatable( $order ) ) {
			if ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {
				$this->update_order_rest( $orderid, $itemid );
				// Activation for KCO V2 and KPM orders
			} else {
				$this->update_order( $orderid, $itemid );
			}
		}
	}

	/**
	 * Update order in Klarna system, add new item
	 *
	 * @since  2.0.0
	 */
	function update_klarna_order_edit_item( $orderid, $items ) {
		$order = wc_get_order( $orderid );
		// Check if option is enabled
		if ( $this->order_is_updatable( $order ) ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				// Check if order was created using this method
				if ( 'on-hold' == $order->get_status() ) {
					if ( 'rest' == get_post_meta( $orderid, '_klarna_api', true ) ) {
						$this->update_order_rest( $orderid );
						// Activation for KCO V2 and KPM orders
					} else {
						$this->update_order( $orderid );
					}
				}
			}
		}
	}

	protected function order_is_updatable( $order ) {
		$payment_method             = $this->get_order_payment_method( $order );
		$payment_method_option_name = 'woocommerce_' . $payment_method . '_settings';
		$payment_method_option      = get_option( $payment_method_option_name );
		$order_id                   = klarna_wc_get_order_id( $order );

		$updatable = false;
		// Check if option is enabled
		if ( isset( $payment_method_option['push_update'] ) && 'yes' === $payment_method_option['push_update'] ) {
			// Check if order is on hold so it can be edited, and if it hasn't been captured or cancelled
			if ( 'on-hold' == $order->get_status() && ! get_post_meta( $order_id, '_klarna_order_cancelled', true ) && ! get_post_meta( $order_id, '_klarna_order_activated', true ) ) {
				$updatable = true;
			}
		}
		// Allow themes and plugins to interact before returning
		return apply_filters( 'klarna_order_is_updatable', $updatable, $order );
	}

	/**
	 * Updates a Klarna order
	 *
	 * @since  2.0
	 *
	 * @param $orderid
	 * @param bool    $itemid
	 */
	function update_order( $orderid, $itemid = false ) {
		$order          = wc_get_order( $orderid );
		$this->order    = $order;
		$rno            = get_post_meta( $orderid, '_klarna_order_reservation', true );
		$payment_method = get_post_meta( $orderid, '_payment_method', true );
		// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
		if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
			$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
		} else {
			$country = get_post_meta( $orderid, '_billing_country', true );
		}
		$klarna = new Klarna\XMLRPC\Klarna();
		$this->configure_klarna( $klarna, $country, $payment_method );
		$this->klarna = $klarna;
		if ( 'AT' !== $country && 'DE' !== $country ) {
			$this->add_addresses();
		}
		$this->process_order_items( $itemid );
		$this->process_fees();
		$this->process_shipping();
		$this->process_discount();
		try {
			$result = $klarna->update( $rno );
			if ( $result ) {
				$order->add_order_note( sprintf( __( 'Klarna order updated.', 'woocommerce-gateway-klarna' ) ) );
			}
		} catch ( Exception $e ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', 'Klarna API error: ' . var_export( $e, true ) );
			}
			$order->add_order_note( sprintf( __( 'Klarna order update failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );
		}
	}

	/**
	 * Updates a Klarna order for Rest API
	 *
	 * @since  2.0
	 *
	 * @param $orderid
	 * @param bool    $itemid
	 *
	 * @return bool|WP_Error
	 */
	function update_order_rest( $orderid, $itemid = false ) {
		$order           = wc_get_order( $orderid );
		$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
		// Get country - either from stored _klarna_credentials_country or _billing_country (backwords compat)
		if ( get_post_meta( $orderid, '_klarna_credentials_country', true ) ) {
			$country = get_post_meta( $orderid, '_klarna_credentials_country', true );
		} else {
			$country = get_post_meta( $orderid, '_billing_country', true );
		}
		$updated_order_lines = array();
		$updated_order_total = 0;
		$updated_tax_total   = 0;
		// Tax is treated differently for US and UK
		$order_billing_country = $order->get_billing_country();
		// Process order items
		foreach ( $order->get_items() as $item_key => $order_item ) {
			if ( $order_item['qty'] && isset( $itemid ) && $item_key != $itemid ) {
				// Item name
				$item_name = $order_item['name'];

				if ( function_exists( 'wc_display_item_meta' ) ) {
					$meta_args = array(
						'echo'      => false,
						'before'    => '(',
						'after'     => ')',
						'separator' => ', ',
					);
					$item_meta = wc_display_item_meta( $order_item, $meta_args );

					$item_name .= ' ' . wp_strip_all_tags( $item_meta );
				} else {
					if ( isset( $order_item['item_meta'] ) ) { // Append item meta to the title, if it exists
						$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );
						if ( $meta = $item_meta->display( true, true ) ) {
							$item_name .= ' (' . $meta . ')';
						}
					}
				}

				// Item reference
				$item_reference = substr( (string) $order_item['product_id'], 0, 64 );

				// Item price
				$item_price = 'us' == strtolower( $order_billing_country ) ? round( number_format( ( $order_item['line_subtotal'] ) * 100, 0, '', '' ) / $order_item['qty'] ) : round( number_format( ( $order_item['line_subtotal'] + $order_item['line_subtotal_tax'] ) * 100, 0, '', '' ) / $order_item['qty'] );

				// Item quantity
				$item_quantity = (int) $order_item['qty'];

				// Item total amount
				$item_total_amount = 'us' == strtolower( $order_billing_country ) ? round( ( $order_item['line_total'] ) * 100 ) : round( ( $order_item['line_total'] + $order_item['line_tax'] ) * 100 );

				// Item discount
				if ( $order_item['line_subtotal'] > $order_item['line_total'] ) {
					$item_discount_amount = ( $order_item['line_subtotal'] + $order_item['line_subtotal_tax'] - $order_item['line_total'] - $order_item['line_tax'] ) * 100;
				} else {
					$item_discount_amount = 0;
				}

				// Item tax amount
				$item_tax_amount = 'us' == strtolower( $order_billing_country ) ? 0 : round( $order_item['line_tax'] * 100 );

				// Item tax rate
				$item_tax_rate         = 'us' == strtolower( $order_billing_country ) ? 0 : round( $order_item['line_subtotal_tax'] / $order_item['line_subtotal'], 2 ) * 100 * 100;
				$klarna_item           = array(
					'reference'             => $item_reference,
					'name'                  => $item_name,
					'quantity'              => $item_quantity,
					'unit_price'            => $item_price,
					'tax_rate'              => $item_tax_rate,
					'total_amount'          => $item_total_amount,
					'total_tax_amount'      => $item_tax_amount,
					'total_discount_amount' => $item_discount_amount,
				);
				$updated_order_lines[] = $klarna_item;
				$updated_order_total   = $updated_order_total + $item_total_amount;
				$updated_tax_total     = $updated_tax_total + $item_tax_amount;
			}
		}
		// Process fees
		if ( count( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
				// We manually calculate the tax percentage here
				if ( $order->get_total_tax() > 0 ) {
					// Calculate tax percentage
					$item_tax_percentage = number_format( ( $item['line_tax'] / $item['line_total'] ) * 100, 2, '.', '' );
				} else {
					$item_tax_percentage = 0.00;
				}
				$invoice_settings    = get_option( 'woocommerce_klarna_invoice_settings' );
				$invoice_fee_id      = $invoice_settings['invoice_fee_id'];
				$invoice_fee_product = wc_get_product( $invoice_fee_id );
				if ( $invoice_fee_product ) {
					$invoice_fee_name = $invoice_fee_product->get_title();
				} else {
					$invoice_fee_name = '';
				}
				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = ( $item['line_total'] + $item['line_tax'] ) * 100;
				$item_price                      = apply_filters( 'klarna_fee_price_including_tax', $klarna_item_price_including_tax );
				$item_price                      = 'us' == strtolower( $order_billing_country ) ? $item['line_total'] * 100 : $item_price;
				$tax_rate                        = 'us' == strtolower( $order_billing_country ) ? 0 : round( $item_tax_percentage );
				$tax_amount                      = 'us' == strtolower( $order_billing_country ) ? 0 : $item['line_tax'];
				$klarna_item                     = array(
					'reference'             => strval( $item['name'] ),
					'name'                  => $item['name'],
					'quantity'              => 1,
					'unit_price'            => $item_price,
					'tax_rate'              => $tax_rate,
					'total_amount'          => $item_price,
					'total_tax_amount'      => $tax_amount,
					'total_discount_amount' => 0,
				);
				$updated_order_lines[]           = $klarna_item;
				$updated_order_total             = $updated_order_total + $item_price;
				$updated_tax_total               = $updated_tax_total + $item['line_tax'];
			}
		}
		// Process shipping
		if ( $order->get_total_shipping() > 0 ) {
			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ( $order->get_shipping_tax() / $order->get_total_shipping() ) * 100;
			$tax_rate                           = 'us' == strtolower( $order_billing_country ) ? 0 : round( $calculated_shipping_tax_percentage ) * 100;
			$tax_amount                         = 'us' == strtolower( $order_billing_country ) ? 0 : round( $order->get_shipping_tax() ) * 100;
			$unit_price                         = 'us' == strtolower( $order_billing_country ) ? round( $order->get_total_shipping() * 100 ) : round( $order->get_total_shipping() + $order->get_shipping_tax() ) * 100;
			$klarna_item                        = array(
				'type'                  => 'shipping_fee',
				'reference'             => 'SHIPPING',
				'name'                  => strval( $order->get_shipping_method() ),
				'quantity'              => 1,
				'unit_price'            => $unit_price,
				'tax_rate'              => $tax_rate,
				'total_amount'          => $unit_price,
				'total_tax_amount'      => $tax_amount,
				'total_discount_amount' => 0,
			);
			$updated_order_lines[]              = $klarna_item;
			$updated_order_total                = $updated_order_total + $unit_price;
			$updated_tax_total                  = $updated_tax_total + $tax_amount;
		}
		// Process discount
		foreach ( $order->get_items( 'coupon' ) as $coupon_id => $coupon_data ) {
			$coupon = new WC_Coupon( $coupon_data['name'] );
			if ( ! $coupon->is_valid() ) {
				break;
			}
			$klarna_settings = get_option( 'woocommerce_klarna_checkout_settings' );
			if ( 'yes' !== $klarna_settings['send_discounts_separately'] && 'smart_coupon' !== klarna_wc_get_coupon_discount_type( $coupon ) ) {
				break;
			}
			$coupon_name           = $coupon_data['name'];
			$coupon_amount         = $coupon_data['discount_amount'] * 100;
			$klarna_item           = array(
				'type'                  => 'discount',
				'reference'             => 'DISCOUNT',
				'name'                  => strval( $coupon_name ),
				'quantity'              => 1,
				'unit_price'            => - $coupon_amount,
				'tax_rate'              => 0,
				'total_amount'          => - $coupon_amount,
				'total_tax_amount'      => 0,
				'total_discount_amount' => 0,
			);
			$updated_order_lines[] = $klarna_item;
			$updated_order_total   = $updated_order_total - $coupon_amount;
		}
		// Process sales tax for US
		if ( 'us' == strtolower( $order_billing_country ) ) {
			$sales_tax = round( ( $order->get_cart_tax() + $order->get_shipping_tax() ) * 100 );
			// Add sales tax line item
			$klarna_item           = array(
				'type'                  => 'sales_tax',
				'reference'             => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
				'name'                  => __( 'Sales Tax', 'woocommerce-gateway-klarna' ),
				'quantity'              => 1,
				'unit_price'            => $sales_tax,
				'tax_rate'              => 0,
				'total_amount'          => $sales_tax,
				'total_discount_amount' => 0,
				'total_tax_amount'      => 0,
			);
			$updated_order_lines[] = $klarna_item;
			$updated_order_total   = $updated_order_total + $sales_tax;
		}
		/**
		 * Need to send local order to constructor and Klarna order to method
		 */
		if ( $klarna_settings['testmode'] == 'yes' ) {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_TEST_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_TEST_BASE_URL;
			}
		} else {
			if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::EU_BASE_URL;
			} elseif ( 'us' === strtolower( $country ) ) {
				$klarna_server_url = Klarna\Rest\Transport\ConnectorInterface::NA_BASE_URL;
			}
		}
		if ( 'gb' === strtolower( $country ) || 'dk' === strtolower( $country ) || 'nl' === strtolower( $country ) ) {
			if ( 'gb' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_uk'];
				$secret = html_entity_decode( $klarna_settings['secret_uk'] );
			} elseif ( 'dk' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_dk'];
				$secret = html_entity_decode( $klarna_settings['secret_dk'] );
			} elseif ( 'nl' === strtolower( $country ) ) {
				$eid    = $klarna_settings['eid_nl'];
				$secret = html_entity_decode( $klarna_settings['secret_nl'] );
			}

			$connector = Klarna\Rest\Transport\Connector::create( $eid, $secret, $klarna_server_url );
		} elseif ( 'us' === strtolower( $country ) ) {
			$connector = Klarna\Rest\Transport\Connector::create( $klarna_settings['eid_us'], html_entity_decode( $klarna_settings['secret_us'] ), $klarna_server_url );
		}
		$klarna_order_id = get_post_meta( $orderid, '_klarna_order_id', true );
		$k_order         = new Klarna\Rest\OrderManagement\Order( $connector, $klarna_order_id );
		$k_order->fetch();
		try {
			$k_order->updateAuthorization(
				array(
					'order_amount'     => $updated_order_total,
					'order_tax_amount' => $updated_tax_total,
					'description'      => 'Updating WooCommerce order',
					'order_lines'      => $updated_order_lines,
				)
			);
			$order->add_order_note( sprintf( __( 'Klarna order updated.', 'woocommerce-gateway-klarna' ) ) );

			return true;
		} catch ( Exception $e ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'klarna', 'Klarna API error: ' . $e->getCode() . ' - ' . $e->getMessage() );
			}
			$order->add_order_note( sprintf( __( 'Klarna order update failed. Error code %1$s. Error message %2$s', 'woocommerce-gateway-klarna' ), $e->getCode(), utf8_encode( $e->getMessage() ) ) );

			return new WP_Error( $e->getCode(), utf8_encode( $e->getMessage() ) );
		}
	}

	/**
	 * Helper function, gets order payment method
	 *
	 * @since  2.0
	 *
	 * @param  $order object
	 *
	 * @return WC_Order
	 */
	function get_order_payment_method( $order ) {
		$payment_method = klarna_wc_get_order_payment_method( $order );

		return $payment_method;
	}

	/**
	 * Get item reference.
	 *
	 * Returns SKU or product ID.
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @param  object $_product WC_Product.
	 *
	 * @return string $item_reference Cart item reference.
	 */
	public static function get_item_reference( $_product ) {
		if ( $_product->get_sku() ) {
			$item_reference = $_product->get_sku();
		} elseif ( klarna_wc_get_product_variation_id( $_product ) ) {
			$item_reference = klarna_wc_get_product_variation_id( $_product );
		} else {
			$item_reference = klarna_wc_get_product_id( $_product );
		}

		return substr( (string) $item_reference, 0, 64 );
	}

	/**
	 * Gets refunded order
	 *
	 * @param int $order_id
	 * @return string
	 */
	public static function get_refunded_order_id( $order_id ) {
		$query_args = array(
			'fields'         => 'id=>parent',
			'post_type'      => 'shop_order_refund',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);
		$refunds    = get_posts( $query_args );
		$refund_id  = array_search( $order_id, $refunds );
		if ( is_array( $refund_id ) ) {
			foreach ( $refund_id as $key => $value ) {
				if ( ! get_post_meta( $value, '_krokedil_refunded' ) ) {
					$refund_id = $value;
					break;
				}
			}
		}
		return $refund_id;
	}

	/**
	 * Gets refunded shipping reference
	 *
	 * @param int $order_id
	 * @return string
	 */
	public static function get_refund_shipping_reference( $refund_order, $order ) {
		if ( $shipping_reference = get_post_meta( $order->get_id(), '_klarna_shipping_sku', true ) ) {
			return $shipping_reference;
		}
		$shipping_method_id   = reset( $refund_order->get_items( 'shipping' ) )->get_method_id();
		$shipping_instance_id = reset( $refund_order->get_items( 'shipping' ) )->get_instance_id();

		return $shipping_method_id . ':' . $shipping_instance_id;
	}

	/**
	 * Go through all refund items and check if the refunded price is
	 * the same as the order line price in the origianal order.
	 *
	 * @param array  $refunded_items refunded order items.
	 * @param object $original_order original Woocommerce order.
	 * @return bool
	 */
	public static function check_refund_items_got_full_price( $refunded_items, $original_order ) {
		if ( empty( $refunded_items ) ) {
			return false;
		}

		$refund_items_got_full_price = true;

		// Go through all refund items and check if the refunded price is the same as the order line price in the origianal order.
		foreach ( $refunded_items as $item ) {

			$item_price = round( ( $item->get_total() + $item->get_total_tax() ) / $item['qty'] );
			if ( $item['variation_id'] ) {
				$product_id = $item['variation_id'];
			} else {
				$product_id = $item['product_id'];
			}

			// For each refunded order line get the corresponding order line from the original order.
			$original_order_items = $original_order->get_items();
			foreach ( $original_order_items as $original_order_item ) {
				if ( $original_order_item['variation_id'] ) {
					$original_order_product_id = $original_order_item['variation_id'];
				} else {
					$original_order_product_id = $original_order_item['product_id'];
				}

				// When we get a match, get the price and break from the forech loop.
				if ( $original_order_product_id == $product_id ) {
					$original_order_item_price = round( ( $original_order_item->get_total() + $original_order_item->get_total_tax() ) / $original_order_item['qty'] );
					// If the price don't match, then we change $refund_items_got_full_price to false.
					if ( $original_order_item_price !== $item_price ) {
						$refund_items_got_full_price = false;
					}
					break;
				}
			}
		}
		return $refund_items_got_full_price;
	}
}

$wc_gateway_klarna_order = new WC_Gateway_Klarna_Order();
