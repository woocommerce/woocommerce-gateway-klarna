jQuery(document).ready(function ($) {
	"use strict";

	// Update is only possible for 30 minutes, after that remove the cross-sell products from the page
	var update_possible = true;
	var countdown_timer = function countdown_timer() {
		update_possible = false;
		$('#klarna-checkout-cross-sells').remove();
	}
	var timer = setTimeout(countdown_timer, 30 * 60 * 60);

	$('.klarna-cross-sells-button:not(.disabled)').on('click', function(event) {
		event.preventDefault();
		$(this).addClass('disabled')

		if (update_possible) {
			var product_id = $(this).data('product_id');
			var clicked_button = $(this);
			var ancestor = $(clicked_button).closest('li.product')

			if (typeof window._klarnaCheckout === 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});

				$(ancestor).block({
					message: null,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					}
				});

				$.ajax(
					kcoCrossSells.ajaxurl,
					{
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'klarna_checkout_cross_sells_add',
							product_id: product_id,
							nonce: kcoCrossSells.klarna_cross_sells_nonce
						},
						success: function (response) {
							$(ancestor).unblock();
							$(clicked_button).addClass('disabled').text(kcoCrossSells.added_to_order_text);

							window._klarnaCheckout(function (api) {
								api.resume();
							});
						},
						error: function (response) {
							$(ancestor).unblock();
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
					}
				);
			}
		}
	})
});