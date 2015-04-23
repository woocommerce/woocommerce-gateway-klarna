jQuery(document).ready(function($) {

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
					console.log( 'success' );
					console.log( response.data );
					
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
	$('#klarna-checkout-shipping input[type="radio"]').change( function() {
		
		window._klarnaCheckout(function (api) {
			api.suspend();
		});
		
		new_method = $(this).val();

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
					console.log( 'success' );
					console.log( response.data );
					
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
		
		ancestor = $( this ).closest( 'td.product-quantity' );
		subtotal_field = $( this ).closest( 'tr' ).find( 'td.product-subtotal' );
		cart_item_key = $( ancestor ).data( 'cart_item_key' );
		new_quantity = $( this ).val();
		
		$.ajax(
			kcoAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_cart_callback', 
					cart_item_key : cart_item_key,
					new_quantity : new_quantity,
					nonce : kcoAjax.klarna_checkout_nonce
				},
				success: function( response ) {
					console.log( 'success' );
					console.log( response.data );

					$( subtotal_field ).html( response.data.updated_line_total );
					
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



	// Add coupon form using JS, so it's only shown for JS users
	$('.klarna-checkout-coupons .klarna-checkout-coupons-form').append( '<div id="klarna_checkout_coupon_result"></div><form class="klarna_checkout_coupon" method="post"><p class="form-row form-row-first"><input type="text" name="coupon_code" class="input-text" placeholder="Coupon Code" id="coupon_code" value="" /></p><p class="form-row form-row-last"><input type="submit" class="button" name="apply_coupon" value="Apply Coupon" /></p><div class="clear"></div></form>' );

	$('.klarna_checkout_coupon').submit( function( event ) {

		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});

		coupon = $( '.klarna_checkout_coupon .input-text' ).val();
		
		input_field = $( this ).find( '.input-text' );

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
					console.log( 'success' );
					console.log( response.data );
										
					window._klarnaCheckout(function (api) {
						api.resume();
					});
					
					if ( response.data.coupon_success ) {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon added.</p>' );
						
						html_string = '<li><strong>' + response.data.coupon + ':</strong> ' + response.data.amount + ' <a class="klarna-checkout-remove-coupon" href="#" data-coupon="' + response.data.coupon + '">(Remove)</a>';
						$( '#klarna-checkout-applied-coupons' ).append( html_string );
						
						$( input_field ).val( '' );
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
	$('#klarna-checkout-applied-coupons').on( 'click', '.klarna-checkout-remove-coupon', function( event ) {

		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});

		remove_coupon = $( this ).data( 'coupon' );
		clicked_el = $( this );

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
					console.log( 'remove-success' );
					console.log( response.data );
					
					$( clicked_el ).closest( 'li' ).remove();
										
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