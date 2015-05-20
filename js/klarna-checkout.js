jQuery(document).ready(function($) {

	// Update country
	$('select#klarna-checkout-euro-country').change( function() {
		
		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
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
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});

					location.reload();
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);
		
	});

	// Update order note
	$('textarea#klarna-checkout-order-note').change( function() {
		
		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
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
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);
		
	});

	// Update shipping
	$('table#kco-totals').on( 'change', '#kco-page-shipping input[type="radio"]', function( event ) {
		
		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
		new_method = $(this).val();
		shipping_total_field = $( '#kco-page-shipping-total' );
		total_field = $( '#kco-page-total-amount' );

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
					
					$( total_field ).html( response.data.cart_total );
					$( shipping_total_field ).html( response.data.cart_shipping_total );
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);
		
	});

	// Update cart
	$('td.product-quantity input[type=number]').change( function( event ) {
		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
		ancestor         = $( this ).closest( 'td.product-quantity' );
		total_field      = $( 'td#kco-page-total-amount' );
		subtotal_field   = $( 'td#kco-page-subtotal-amount' );
		line_total_field = $( this ).closest( 'tr' ).find( 'td.product-total' );
		shipping_row     = $( 'tr#kco-page-shipping' );
		cart_item_key    = $( ancestor ).data( 'cart_item_key' );
		new_quantity     = $( this ).val();
		
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

					$( total_field ).html( response.data.cart_total );
					$( subtotal_field ).html( response.data.cart_subtotal );
					$( line_total_field ).html( response.data.line_total );
					$( shipping_row ).replaceWith( response.data.shipping_row );
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);
	});

	// Remove cart item
	$('td.product-remove a').click( function( event ) {
		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
		ancestor             = $( this ).closest( 'tr' ).find( 'td.product-quantity' );
		total_field          = $( 'td#kco-page-total-amount' );
		subtotal_field       = $( 'td#kco-page-subtotal-amount' );
		item_row             = $( this ).closest( 'tr' );
		shipping_row         = $( 'tr#kco-page-shipping' );
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
					console.log( 'success' );
					console.log( response.data );

					if ( 0 == response.data.item_count ) {
						window.location.href = response.data.cart_url;
					} else {
						$( total_field ).html( response.data.cart_total );
						$( subtotal_field ).html( response.data.cart_subtotal );
						$( shipping_row ).replaceWith( response.data.shipping_row );
						$( item_row ).remove();
						
						window._klarnaCheckout(function (api) {
							api.resume();
						});
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);
	});

	// Add coupon
	$('.klarna_checkout_coupon').submit( function( event ) {

		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});

		coupon = $( '.klarna_checkout_coupon .input-text' ).val();		
		input_field = $( this ).find( '.input-text' );
		total_field = $( 'td#kco-page-total-amount' );
		subtotal_field = $( 'td#kco-page-subtotal-amount' );
		shipping_row = $( 'tr#kco-page-shipping' );

		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_coupons_callback_update', 
					coupon : coupon,
					nonce  : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					// console.log( 'success' );
					// console.log( response.data );
										
					window._klarnaCheckout(function (api) {
						api.resume();
					});
					
					if ( response.data.coupon_success ) {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon added.</p>' );
												
						html_string = '<tr class="kco-applied-coupon"><td class="kco-rightalign">Coupon: ' + response.data.coupon + ' <a class="kco-remove-coupon" data-coupon="' + response.data.coupon + '" href="#">(remove)</a></td><td class="kco-rightalign">-' + response.data.amount + '</td></tr>';
						
						$( 'tr#kco-page-total' ).before( html_string );					
						$( input_field ).val( '' );
						$( total_field ).html( response.data.cart_total );
						$( subtotal_field ).html( response.data.cart_subtotal );
						$( shipping_row ).replaceWith( response.data.shipping_row );

					}
					else {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon could not be added.</p>' );
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );

					$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon could not be added.</p>' );
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);

	});

	
	// Remove coupon
	$('table#kco-totals').on( 'click', '.kco-remove-coupon', function( event ) {

		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});

		remove_coupon = $( this ).data( 'coupon' );
		clicked_el = $( this );
		total_field = $( 'td#kco-page-total-amount' );
		subtotal_field = $( 'td#kco-page-subtotal-amount' );
		shipping_row = $( 'tr#kco-page-shipping' );

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
					$( total_field ).html( response.data.cart_total );
					$( subtotal_field ).html( response.data.cart_subtotal );
					$( shipping_row ).replaceWith( response.data.shipping_row );
										
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				},
				error: function( response ) {
					console.log( 'remove-error' );
					console.log( response );
					
					window._klarnaCheckout(function (api) {
						api.resume();
					});
				}
			}
		);

	});


});