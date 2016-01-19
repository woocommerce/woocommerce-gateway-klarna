jQuery(document).ready(function($) {

	// Update country
	$(document).on('change','select#klarna-checkout-euro-country', function( event ) {	
		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		new_country = $(this).val();

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_country_callback', 
					new_country : new_country,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );
					
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}

					location.reload();
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
		
	});

	// Update order note
	$(document).on('change','textarea#klarna-checkout-order-note, #kco_order_note', function( event ) {	
		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		order_note = $(this).val();

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_order_note_callback', 
					order_note : order_note,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );
					
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
		
	});

	// Old order note shortcode
	/* 
	$('#kco_order_note').blur(function () {
		var kco_order_note = '';
		
		if( $('#kco_order_note').val() != '' ) {
			var kco_order_note = $('#kco_order_note').val();
		}
		
		if(kco_order_note == '') {
		
		} else {
				
			$.post(
				kcoAjax.ajaxurl,
				{
					action			: 'customer_update_kco_order_note',
					kco_order_note	: kco_order_note,
					kco_order_id	: '<?php echo WC()->session->order_awaiting_payment;?>',
					_wpnonce		: kcoAjax.klarna_checkout_nonce,
				},
				function( response ) {
					console.log( response );
				}
			);
			
		}				
	});
	*/

	// Update shipping (v2)
	$(document).on('change','table#kco-totals #kco-page-shipping input[type="radio"]', function( event ) {
		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		new_method       = $(this).val();
		kco_widget       = $( '#klarna-checkout-widget' );	

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_shipping_callback', 
					new_method : new_method,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );
					
					$( kco_widget ).html( response.data.widget_html );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
		
	});

	// Update cart (v2)
	$(document).on('change','td.product-quantity input[type=number]', function( event ) {
		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		ancestor         = $( this ).closest( 'td.product-quantity' );
		cart_item_key    = $( ancestor ).data( 'cart_item_key' );
		new_quantity     = $( this ).val();
		kco_widget       = $( '#klarna-checkout-widget' );	
		
		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_cart_callback_update', 
					cart_item_key : cart_item_key,
					new_quantity : new_quantity,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );

					$( kco_widget ).html( response.data.widget_html );
					
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
	});

	// Remove cart item (v2)
	$(document).on('click','td.kco-product-remove a', function( event ) {
		event.preventDefault();

		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}
		
		ancestor             = $( this ).closest( 'tr' ).find( 'td.product-quantity' );
		item_row             = $( this ).closest( 'tr' );
		kco_widget           = $( '#klarna-checkout-widget' );	
		cart_item_key_remove = $( ancestor ).data( 'cart_item_key' );
		
		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_cart_callback_remove', 
					cart_item_key_remove : cart_item_key_remove,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					if ( 0 == response.data.item_count ) {
						// window.location.href = response.data.cart_url;
						location.reload();
					} else {
						$( kco_widget ).html( response.data.widget_html );
						$( item_row ).remove();
						
						if ( typeof window._klarnaCheckout == 'function') { 
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						} else {
							location.reload();
						}
					}
				},
				error: function( response ) {
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
	});

	// Add coupon (v2)
	$('#klarna-checkout-widget .checkout_coupon').on('submit', function( event ) {
		event.preventDefault();

		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}

		coupon = $( '#klarna-checkout-widget #coupon_code' ).val();
		kco_widget = $( '#klarna-checkout-widget' );	
		input_field = $( this ).find( '#coupon_code' );

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_coupons_callback', 
					coupon : coupon,
					nonce  : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );

					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
					
					if ( response.data.coupon_success ) {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon added.</p>' );
												
						html_string = '<tr class="kco-applied-coupon"><td class="kco-rightalign">Coupon: ' + response.data.coupon + ' <a class="kco-remove-coupon" data-coupon="' + response.data.coupon + '" href="#">(remove)</a></td><td class="kco-rightalign">-' + response.data.amount + '</td></tr>';
						
						$( 'tr#kco-page-total' ).before( html_string );					
						$( input_field ).val( '' );
						$( kco_widget ).html( response.data.widget_html );
					}
					else {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon could not be added.</p>' );
					}
				},
				error: function( response ) {
					$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon could not be added.</p>' );
					
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);

	});

	
	// Remove coupon (v2)
	$(document).on('click','table#kco-totals .kco-remove-coupon', function( event ) {
		event.preventDefault();

		if ( typeof window._klarnaCheckout == 'function') { 
			window._klarnaCheckout(function (api) {
				api.suspend();
			});
		}

		remove_coupon = $( this ).data( 'coupon' );
		clicked_el = $( this );
		kco_widget = $( '#klarna-checkout-widget' );	

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_remove_coupon_callback', 
					remove_coupon : remove_coupon,
					nonce  : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'remove-success' );
					// console.log( response.data );
					
					$( clicked_el ).closest( 'tr' ).remove();
					$( kco_widget ).html( response.data.widget_html );

					// Remove WooCommerce notification
					$( '#klarna-checkout-widget .woocommerce-info + .woocommerce-message' ).remove();
										
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				},
				error: function( response ) {
					console.log( 'remove-error' );
					console.log( response );
					
					if ( typeof window._klarnaCheckout == 'function') { 
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				}
			}
		);
	});
	
	// Address change (email, postal code) v2
	var isSubmitting = false;
	if ( typeof window._klarnaCheckout == 'function') {
	window._klarnaCheckout(function (api) {
		// For v2 use 'change' JS event to capture
		if ( 'v2' == kcoAjax.version ) {
			api.on( {
				'change': function(data) {
					// console.log(data);

					if ( '' != data.email && '' != data.postal_code ) {

						window._klarnaCheckout(function (api) {
							api.suspend();
						});

						// console.log('V2');

						// Check if email is not defined (AT and DE only) and set it to this value
						// For AT and DE, email field is not captured inside data object
						if ( data.email === undefined ) {
							data.email = 'guest_checkout@klarna.com';
						}

						if ( '' != data.email ) {
							kco_widget = $( '#klarna-checkout-widget' );
								
							if(isSubmitting) {
								return;
							}
							isSubmitting = true;
							
							$.ajax(
								kcoAjax.ajaxurl,
								{
									type     : 'POST',
									dataType : 'json',
									data     : {
										action      : 'kco_iframe_change_cb',
										email       : data.email,
										postal_code : data.postal_code,
										nonce       : kcoAjax.klarna_checkout_nonce
									},
									success: function( response ) {
										// Check if a product is out of stock
										if ( false === response.success ) {
											console.log( 'false' );
											location.reload();
											return;
										}

										$( kco_widget ).html( response.data.widget_html );

										window._klarnaCheckout(function (api) {
											api.resume();
										});
									},
									error: function( response ) {
										window._klarnaCheckout(function (api) {
											api.resume();
										});
									}
								}
							);
						}

					}
				}
			} );
		}
		
		// Address change (postal code, region) v3
		if ( 'v3' == kcoAjax.version ) {
			api.on( {
				'shipping_address_change': function (data) {
					// console.log('****** Parent Page Received shipping_address_change DATA ******');
					// console.log('V3');

					if ( '' != data.postal_code || '' != data.region ) {
						kco_widget = $( '#klarna-checkout-widget' );

						$.ajax(
							kcoAjax.ajaxurl,
							{
								type     : 'POST',
								dataType : 'json',
								data     : {
									action       : 'kco_iframe_shipping_address_change_cb',
									region       : data.region,
									postal_code  : data.postal_code,
									nonce        : kcoAjax.klarna_checkout_nonce
								},
								success: function( response ) {
									// console.log( response );
									$( kco_widget ).html( response.data.widget_html );

									window._klarnaCheckout(function (api) {
										api.resume();
									});
								},
								error: function( response ) {
									window._klarnaCheckout(function (api) {
										api.resume();
									});
								}
							}
						);
					}
				}
			} );
		}


		api.on( {
			'shipping_option_change': function (data) {
				new_method       = data.id;
				kco_widget       = $( '#klarna-checkout-widget' );

				$.ajax(
					kcoAjax.ajaxurl,
					{
						type     : 'POST',
						dataType : 'json',
						data     : {
							action :    'kco_iframe_shipping_option_change_cb',
							new_method : new_method,
							nonce :      kcoAjax.klarna_checkout_nonce
						},
						success: function( response ) {
							// console.log( 'success' );
							// console.log( response );

							$( kco_widget ).html( response.data.widget_html );
						},
						error: function( response ) {
							console.log( 'error' );
							console.log( response );
						}
					}
				);
			}
		} );
		
	});
	}

});