<?php
/**
 * Klarna orders
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */


/**
 * Class that handles Klarna orders .
 */
class WC_Gateway_Klarna_Order {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $order, $klarna ) {

		$this->order = $order;
		$this->klarna = $klarna;

	}


	/**
	 * Prepare Klarna order for creation.
	 * 
	 * @since  2.0
	 **/
	function prepare_order(
		$klarna_billing,
		$klarna_shipping,
		$ship_to_billing_address
	) {

		$this->process_cart_contents( $this->order, $this->klarna );
		$this->process_discount( $this->order, $this->klarna );
		$this->process_fees( $this->order, $this->klarna );
		$this->process_shipping( $this->order, $this->klarna );
		$this->set_addresses(
			$klarna_billing,
			$klarna_shipping,
			$ship_to_billing_address
		);

	}

	/**
	 * Process cart contents.
	 * 
	 * @since  2.0
	 **/
	function process_cart_contents() {

		$order = $this->order;
		$klarna = $this->klarna;

		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				$_product = $order->get_product_from_item( $item );
				if ( $_product->exists() && $item['qty'] ) {
				
					// We manually calculate the tax percentage here
					if ( $order->get_line_tax( $item ) !== 0 ) {
						// Calculate tax percentage
						$item_tax_percentage = @number_format( ( $order->get_line_tax( $item ) / $order->get_line_total( $item, false ) ) * 100, 2, '.', '' );
					} else {
						$item_tax_percentage = 0.00;
					}
					
					// apply_filters to item price so we can filter this if needed
					$klarna_item_price_including_tax = $order->get_item_total( $item, true );
					$item_price = apply_filters( 'klarna_item_price_including_tax', $klarna_item_price_including_tax );
						
					// Get SKU or product id
					$reference = '';
					if ( $_product->get_sku() ) {
						$reference = $_product->get_sku();
					} elseif ( $_product->variation_id ) {
						$reference = $_product->variation_id;
					} else {
						$reference = $_product->id;
					}
					
					$klarna->addArticle(
						$qty      = $item['qty'],                  // Quantity
						$artNo    = strval( $reference ),          // Article number
						$title    = utf8_decode ($item['name']),   // Article name/title
						$price    = $item_price,                   // Price including tax
						$vat      = round( $item_tax_percentage ), // Tax
						$discount = 0,                             // Discount is applied later
						$flags    = KlarnaFlags::INC_VAT           // Price is including VAT.
					);
										
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

		$order = $this->order;
		$klarna = $this->klarna;

		if ( $order->order_discount > 0 ) {
			// apply_filters to order discount so we can filter this if needed
			$klarna_order_discount = $order->order_discount;
			$order_discount = apply_filters( 'klarna_order_discount', $klarna_order_discount );
		
			$klarna->addArticle(
			    $qty = 1,
			    $artNo = '',
			    $title = __( 'Discount', 'klarna' ),
			    $price = -$order_discount,
			    $vat = 0,
			    $discount = 0,
			    $flags = KlarnaFlags::INC_VAT
			);
		}

	}


	/**
	 * Process fees.
	 * 
	 * @since  2.0
	 **/
	function process_fees() {

		$order  = $this->order;
		$klarna = $this->klarna;

		if ( sizeof( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
				// We manually calculate the tax percentage here
				if ( $order->get_total_tax() > 0 ) {
					// Calculate tax percentage
					$item_tax_percentage = number_format( ( $item['line_tax'] / $item['line_total'] ) * 100, 2, '.', '' );
				} else {
					$item_tax_percentage = 0.00;
				}

				// Invoice fee or regular fee
				if( $invoice_fee_name == $item['name'] ) {
					$klarna_flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_HANDLING; // Price is including VAT and is handling/invoice fee
				} else {
					$klarna_flags = KlarnaFlags::INC_VAT; // Price is including VAT
				}

				// apply_filters to item price so we can filter this if needed
				$klarna_item_price_including_tax = $item['line_total'] + $item['line_tax'];
				$item_price = apply_filters( 'klarna_fee_price_including_tax', $klarna_item_price_including_tax );
				
				$item_loop++;

				$klarna->addArticle(
					$qty = 1,
					$artNo = '',
					$title = $item['name'],
					$price = $item_price,
					$vat = round( $item_tax_percentage ),
					$discount = 0,
					$flags = $klarna_flags
				);

			}

		}

	}


	/**
	 * Process shipping.
	 * 
	 * @since  2.0
	 **/
	function process_shipping() {

		$order = $this->order;
		$klarna = $this->klarna;

		if ( $order->get_total_shipping() > 0 ) {
			// We manually calculate the shipping tax percentage here
			$calculated_shipping_tax_percentage = ($order->order_shipping_tax/$order->get_total_shipping())*100; //25.00
			$calculated_shipping_tax_decimal = ($order->order_shipping_tax/$order->get_total_shipping())+1; //0.25
			
			// apply_filters to Shipping so we can filter this if needed
			$klarna_shipping_price_including_tax = $order->get_total_shipping()*$calculated_shipping_tax_decimal;
			$shipping_price = apply_filters( 'klarna_shipping_price_including_tax', $klarna_shipping_price_including_tax );
			
			$klarna->addArticle(
				$qty = 1,
				$artNo = '',
				$title = __( 'Shipping cost', 'klarna' ),
				$price = $shipping_price,
				$vat = round( $calculated_shipping_tax_percentage ),
				$discount = 0,
				$flags = KlarnaFlags::INC_VAT + KlarnaFlags::IS_SHIPMENT // Price is including VAT and is shipment fee
			);
		}

	}


	/**
	 * Set shipping and billing address.
	 * 
	 * @since  2.0
	 **/
	function set_addresses(
		$klarna_billing,
		$klarna_shipping,
		$ship_to_billing_address
	) {

		$order = $this->order;
		$klarna = $this->klarna;
		
		$klarna_billing_address = $klarna_billing['address'];
		$klarna_billing_house_number = $klarna_billing['house_number'];
		$klarna_billing_house_extension = $klarna_billing['house_extension'];

		$klarna_shipping_address = $klarna_shipping['address'];
		$klarna_shipping_house_number = $klarna_shipping['house_number'];
		$klarna_shipping_house_extension = $klarna_shipping['house_extension'];

		// Billing address
		$addr_billing = new KlarnaAddr(
			$email    = $order->billing_email,
			$telno    = '', // We skip the normal land line phone, only one is needed.
			$cellno   = $order->billing_phone,
			$fname    = utf8_decode( $order->billing_first_name ),
			$lname    = utf8_decode( $order->billing_last_name ),
			$careof   = utf8_decode( $order->billing_address_2 ),  // No care of, C/O.
			$street   = utf8_decode( $klarna_billing_address ), // For DE and NL specify street number in houseNo.
			$zip      = utf8_decode( $order->billing_postcode ),
			$city     = utf8_decode( $order->billing_city ),
			$country  = utf8_decode( $order->billing_country ),
			$houseNo  = utf8_decode( $klarna_billing_house_number ), // For DE and NL we need to specify houseNo.
			$houseExt = utf8_decode( $klarna_billing_house_extension ) // Only required for NL.
		);
		
		
		// Shipping address
		if ( $order->get_shipping_method() == '' || $ship_to_billing_address == 'yes' ) {

			// Use billing address if Shipping is disabled in Woocommerce
			$addr_shipping = new KlarnaAddr(
				$email     = $order->billing_email,
				$telno     = '', //We skip the normal land line phone, only one is needed.
				$cellno    = $order->billing_phone,
				$fname     = utf8_decode( $order->billing_first_name ),
				$lname     = utf8_decode( $order->billing_last_name ),
				$careof    = utf8_decode( $order->billing_address_2 ),  // No care of, C/O.
				$street    = utf8_decode( $klarna_billing_address ), // For DE and NL specify street number in houseNo.
				$zip       = utf8_decode( $order->billing_postcode ),
				$city      = utf8_decode( $order->billing_city ),
				$country   = utf8_decode( $order->billing_country ),
				$houseNo   = utf8_decode( $klarna_billing_house_number ), // For DE and NL we need to specify houseNo.
				$houseExt  = utf8_decode( $klarna_billing_house_extension ) // Only required for NL.
			);

		} else {

			$addr_shipping = new KlarnaAddr(
				$email     = $order->billing_email,
				$telno     = '', //We skip the normal land line phone, only one is needed.
				$cellno    = $order->billing_phone,
				$fname     = utf8_decode( $order->shipping_first_name ),
				$lname     = utf8_decode( $order->shipping_last_name ),
				$careof    = utf8_decode( $order->shipping_address_2 ),  // No care of, C/O.
				$street    = utf8_decode( $klarna_shipping_address ), // For DE and NL specify street number in houseNo.
				$zip       = utf8_decode( $order->shipping_postcode ),
				$city      = utf8_decode( $order->shipping_city ),
				$country   = utf8_decode( $order->shipping_country ),
				$houseNo   = utf8_decode( $klarna_shipping_house_number ), // For DE and NL we need to specify houseNo.
				$houseExt  = utf8_decode( $klarna_shipping_house_extension ) // Only required for NL.
			);

		}

		// Next we tell the Klarna instance to use the address in the next order.
		$klarna->setAddress( KlarnaFlags::IS_BILLING, $addr_billing ); // Billing / invoice address
		$klarna->setAddress( KlarnaFlags::IS_SHIPPING, $addr_shipping ); // Shipping / delivery address

	}


	/**
	 * Refunds a Klarna order
	 * 
	 * @since  2.0
	 **/
	function refund_order( $amount, $reason = '', $invNo ) {

		$order = $this->order;
		$klarna = $this->klarna;

		/**
		 * Check if return amount is equal to order total, if yes
		 * refund entire order.
		 */
		if ( $order->get_total() == $amount ) {

			try {


				$ocr = $klarna->creditInvoice( $invNo ); // Invoice number

				if ( $ocr ) {

					$order->add_order_note(
						sprintf(
							__( 'Klarna order fully refunded.', 'klarna' ),
							$ocr
						)
					);

					return true;

				}

			} catch( Exception $e ) {

				$order->add_order_note(
					sprintf(
						__( 'Klarna order refund failed. Error code %s. Error message %s', 'klarna' ),
						$e->getCode(),
						utf8_encode( $e->getMessage() )
					)					
				);

				return false;

			}

		/**
		 * If return amount is not equal to order total, maybe perform
		 * good-will partial refund.
		 */
		} else {

			/**
			 * Tax rate needs to be specified for good-will refunds.
			 * Check if there's only one tax rate in the entire order.
			 * If yes, go ahead with good-will refund.
			 */
			if ( 1 == count( $order->get_taxes() ) ) {

				$tax_rate = $order->get_cart_tax() / $order->get_total() * 100;

				try {

					$ocr = $klarna->returnAmount( // returns 1 on success
						$invNo,               // Invoice number
						$amount,              // Amount given as a discount.
						$tax_rate,            // VAT (%)
						KlarnaFlags::INC_VAT, // Amount including VAT.
						$reason               // Description
					);

					if ( $ocr ) {

						$order->add_order_note(
							sprintf(
								__( 'Klarna order partially refunded. Refund amount: %s.', 'klarna' ),
								$amount
							)
						);

						return true;

					}

				} catch( Exception $e ) {

					$order->add_order_note(
						sprintf(
							__( 'Klarna order refund failed. Error code %s. Error message %s', 'klarna' ),
							$e->getCode(),
							utf8_encode( $e->getMessage() )
						)					
					);

					return false;

				}

			/**
			 * If there are multiple tax rates, bail and leave order note.
			 */
			} else {

				$order->add_order_note( __( 'Refund failed. WooCommerce Klarna partial refund not possible for orders containing items with different tax rates.', 'klarna' ) );

				return false;

			}

		}

		return false;

	}


	/**
	 * Activates a Klarna order
	 * 
	 * @since  2.0
	 **/
	function activate_order( $rno ) {

		$order = $this->order;
		$klarna = $this->klarna;
		$orderid = $order->id;

		try {

			$result = $klarna->activate(
		    	$rno,
				null, // OCR Number
				KlarnaFlags::RSRV_SEND_BY_EMAIL
		    );
			$risk  = $result[0]; // "ok" or "no_risk"
			$invNo = $result[1]; // "9876451"

			$order->add_order_note(
				sprintf(
					__( 'Klarna order activated. Invoice number %s - risk status %s.', 'klarna' ),
					$invNo,
					$risk
				)
			);
			update_post_meta( $orderid, '_klarna_order_activated', time() );
			update_post_meta( $orderid, '_klarna_invoice_number', $invNo );
			update_post_meta( $orderid, '_transaction_id', $invNo );

		} catch( Exception $e ) {

			$order->add_order_note(
				sprintf(
					__( 'Klarna order activation failed. Error code %s. Error message %s', 'klarna' ),
					$e->getCode(),
					utf8_encode( $e->getMessage() )
				)					
			);

		}

	}


	/**
	 * Cancels a Klarna order
	 * 
	 * @since  2.0
	 **/
	function cancel_order( $rno ) {

		$order = $this->order;
		$klarna = $this->klarna;
		$orderid = $order->id;

		try {

		    $klarna->cancelReservation( $rno );
			$order->add_order_note(
				__( 'Klarna order cancellation completed.', 'klarna' )
			);
			add_post_meta( $orderid, '_klarna_order_cancelled', time() );

		} catch( Exception $e ) {

			$order->add_order_note(
				sprintf(
					__( 'Klarna order cancellation failed. Error code %s. Error message %s', 'klarna' ),
					$e->getCode(),
					utf8_encode( $e->getMessage() )
				)					
			);

		}

	}

}