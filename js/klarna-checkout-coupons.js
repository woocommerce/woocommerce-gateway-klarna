jQuery(document).ready(function($) {

	$('.klarna-checkout-coupons').append( '<div id="klarna_checkout_coupon_result"></div><form class="klarna_checkout_coupon" method="post"><p class="form-row form-row-first"><input type="text" name="coupon_code" class="input-text" placeholder="Coupon Code" id="coupon_code" value="" /></p><p class="form-row form-row-last"><input type="submit" class="button" name="apply_coupon" value="Apply Coupon" /></p><div class="clear"></div></form>' );

	$('.klarna_checkout_coupon').submit( function( event ) {

		event.preventDefault();

		window._klarnaCheckout(function (api) {
			api.suspend();
		});

		coupon = $( '.klarna_checkout_coupon .input-text' ).val();

		$.ajax(
			couponsAjax.ajaxurl,
			{
				type     : 'POST',
				dataType : 'json',
				data     : {
					action : 'klarna_checkout_coupons_callback', 
					coupon : coupon,
					nonce  : couponsAjax.klarna_checkout_coupons_nonce
				},
				success: function( response ) {
					console.log( 'success' );
					console.log( response.data );
					if ( response.data.coupon_success ) {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon added.</p>' );
					}
					else {
						$( '#klarna_checkout_coupon_result' ).html( '<p>Coupon could not be added.</p>' );
					}
				},
				error: function( response ) {
					console.log( 'error' );
					console.log( response );
				}
			}
		);

		window._klarnaCheckout(function (api) {
			api.resume();
		});

	});

});