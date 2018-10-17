jQuery(document).ready(function ($) {

	// AjaxQ jQuery Plugin
	// Copyright (c) 2012 Foliotek Inc.
	// MIT License
	// https://github.com/Foliotek/ajaxq
	var queues = {};
	var activeReqs = {};

	// Register an $.ajaxq function, which follows the $.ajax interface, but allows a queue name which will force only one request per queue to fire.
	$.ajaxq = function(qname, opts) {

		if (typeof opts === "undefined") {
			throw ("AjaxQ: queue name is not provided");
		}

		// Will return a Deferred promise object extended with success/error/callback, so that this function matches the interface of $.ajax
		var deferred = $.Deferred(),
			promise = deferred.promise();

		promise.success = promise.done;
		promise.error = promise.fail;
		promise.complete = promise.always;

		// Create a deep copy of the arguments, and enqueue this request.
		var clonedOptions = $.extend(true, {}, opts);
		enqueue(function() {
			// Send off the ajax request now that the item has been removed from the queue
			var jqXHR = $.ajax.apply(window, [clonedOptions]);

			// Notify the returned deferred object with the correct context when the jqXHR is done or fails
			// Note that 'always' will automatically be fired once one of these are called: http://api.jquery.com/category/deferred-object/.
			jqXHR.done(function() {
				deferred.resolve.apply(this, arguments);
			});
			jqXHR.fail(function() {
				deferred.reject.apply(this, arguments);
			});

			jqXHR.always(dequeue); // make sure to dequeue the next request AFTER the done and fail callbacks are fired
			return jqXHR;
		});

		return promise;


		// If there is no queue, create an empty one and instantly process this item.
		// Otherwise, just add this item onto it for later processing.
		function enqueue(cb) {
			if (!queues[qname]) {
				queues[qname] = [];
				var xhr = cb();
				activeReqs[qname] = xhr;
			}
			else {
				queues[qname].push(cb);
			}
		}

		// Remove the next callback from the queue and fire it off.
		// If the queue was empty (this was the last item), delete it from memory so the next one can be instantly processed.
		function dequeue() {
			if (!queues[qname]) {
				return;
			}
			var nextCallback = queues[qname].shift();
			if (nextCallback) {
				var xhr = nextCallback();
				activeReqs[qname] = xhr;
			}
			else {
				delete queues[qname];
				delete activeReqs[qname];
			}
		}
	};

	// Register a $.postq and $.getq method to provide shortcuts for $.get and $.post
	// Copied from jQuery source to make sure the functions share the same defaults as $.get and $.post.
	$.each( [ "getq", "postq" ], function( i, method ) {
		$[ method ] = function( qname, url, data, callback, type ) {

			if ( $.isFunction( data ) ) {
				type = type || callback;
				callback = data;
				data = undefined;
			}

			return $.ajaxq(qname, {
				type: method === "postq" ? "post" : "get",
				url: url,
				data: data,
				success: callback,
				dataType: type
			});
		};
	});

	var isQueueRunning = function(qname) {
		return queues.hasOwnProperty(qname);
	};

	var isAnyQueueRunning = function() {
		for (var i in queues) {
			if (isQueueRunning(i)) return true;
		}
		return false;
	};

	$.ajaxq.isRunning = function(qname) {
		if (qname) return isQueueRunning(qname);
		else return isAnyQueueRunning();
	};

	$.ajaxq.getActiveRequest = function(qname) {
		if (!qname) throw ("AjaxQ: queue name is required");

		return activeReqs[qname];
	};

	$.ajaxq.abort = function(qname) {
		if (!qname) throw ("AjaxQ: queue name is required");

		var current = $.ajaxq.getActiveRequest(qname);
		delete queues[qname];
		delete activeReqs[qname];
		if (current) current.abort();
	};

	$.ajaxq.clear = function(qname) {
		if (!qname) {
			for (var i in queues) {
				if (queues.hasOwnProperty(i)) {
					queues[i] = [];
				}
			}
		}
		else {
			if (queues[qname]) {
				queues[qname] = [];
			}
		}
	};


	var performingAjax = false;

	var blockCartWidget = function blockCartWidget() {
		$('#klarna-checkout-widget').fadeTo( '400', '0.6' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
	};

	var unblockCartWidget = function unblockCartWidget() {
		$('#klarna-checkout-widget').css( 'opacity', '1' ).unblock();
	};

	// Update country
	$(document).on('change', 'select#klarna-checkout-euro-country', function (event) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			new_country = $(this).val();

			$.ajaxq('KCOQueue', {
				url: kcoAjax.ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'klarna_checkout_country_callback',
					new_country: new_country,
					nonce: kcoAjax.klarna_checkout_nonce
				},
				success: function (response) {
					document.location.assign(response.data.new_url);
				},
				error: function (response) {
					console.log('select euro country AJAX error');
					console.log(response);
				},
				complete: function () {
					performingAjax = false;
					unblockCartWidget();
				}
			});
		}
	});

	// Update order note
	$(document).on('change', 'textarea#klarna-checkout-order-note, #kco_order_note', function (event) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			order_note = $(this).val();

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_order_note_callback',
						order_note: order_note,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
					},
					error: function (response) {
						console.log('order note AJAX error');
						console.log(response);
					},
					complete: function () {
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	});

	// Update shipping (v2)
	$(document).on('change', 'table#kco-totals #kco-page-shipping input[type="radio"], .woocommerce-checkout-review-order-table #shipping_method input[type="radio"]', function (event) {
		var id = $(this).val(); 
		update_klarna_shipping( id );
	});
	$(document.body).on('klarna_update_shipping', function (event, id) { 
		update_klarna_shipping( id );
	});
	function update_klarna_shipping( id ) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}
			new_method = id;
			kco_widget = $('#klarna-checkout-widget');

			$(document.body).trigger('kco_widget_update_shipping', new_method);

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_shipping_callback',
						new_method: new_method,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
						$('#klarna-checkout-widget').html(response.data.widget_html);
					},
					error: function (response) {
						console.log('update shipping AJAX error');
						console.log(response);
					},
					complete: function () {
						console.log( 'complete' );
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								console.log('klarnaCheckout window');
								api.resume();
							});
						}
						$(document.body).trigger('kco_widget_updated_shipping', new_method);
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	}

	// Update cart (v2)
	$(document).on('change', '.product-quantity input[type=number]', function (event) {
		var minMaxFlag = false;

		// Check max value
		if ($(this).attr('max')) {
			if($(this).val() > $(this).attr('max')) {
				minMaxFlag = true;
			}
		}

		// Check min value
		if ($(this).attr('min')) {
			if($(this).val() < $(this).attr('min')) {
				minMaxFlag = true;
			}
		}

		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			ancestor = $(this).closest('.product-quantity');
			cart_item_key = $(ancestor).data('cart_item_key');
			new_quantity = $(this).val();
			kco_widget = $('#klarna-checkout-widget');

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_cart_callback_update',
						cart_item_key: cart_item_key,
						new_quantity: new_quantity,
						min_max_flag: minMaxFlag,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
						if (response.success) {
							$('#klarna-checkout-widget').html(response.data.widget_html);
						} else {
							window.location.href = response.data.cart_url;
						}
					},
					error: function (response) {
						console.log('update cart item AJAX error');
						console.log(response);
					},
					complete: function () {
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	});

	// Remove cart item (v2)
	$(document).on('click', 'td.kco-product-remove a', function (event) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			event.preventDefault();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			ancestor = $(this).closest('tr').find('.product-quantity');
			item_row = $(this).closest('tr');
			kco_widget = $('#klarna-checkout-widget');
			cart_item_key_remove = $(ancestor).data('cart_item_key');

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_cart_callback_remove',
						cart_item_key_remove: cart_item_key_remove,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
						if (0 == response.data.item_count) {
							location.reload();
						} else {
							$('#klarna-checkout-widget').html(response.data.widget_html);
							$(item_row).remove();

							if (typeof window._klarnaCheckout != 'function') {
								location.reload();
							}
						}
					},
					error: function (response) {
						console.log('remove cart item AJAX error');
						console.log(response);
					},
					complete: function (response) {
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	});

	// Add coupon (v2)
	$('#klarna-checkout-widget .checkout_coupon').off(); // Remove WC built-in event handler
	$(document).on('submit', '#klarna-checkout-widget .checkout_coupon', function (event) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			event.preventDefault();

			$('#klarna_checkout_coupon_result').html('');

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			coupon = $('#klarna-checkout-widget #coupon_code').val();
			kco_widget = $('#klarna-checkout-widget');
			input_field = $(this).find('#coupon_code');

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_coupons_callback',
						coupon: coupon,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
						if (response.data.coupon_success) {
							$(input_field).val('');
							$('#klarna-checkout-widget').html(response.data.widget_html);
                            $('#klarna_checkout_coupon_result').html('<div class="woocommerce-message" role="alert">' + kcoAjax.coupon_success + '</div>');

							if (typeof window._klarnaCheckout != 'function') {
								location.reload();
							}
						}
						else {
							$('#klarna_checkout_coupon_result').html('<div class="woocommerce-error">' + kcoAjax.coupon_fail + '</div>');
						}
					},
					error: function (response) {
						$('#klarna_checkout_coupon_result').html('<div class="woocommerce-error">' + kcoAjax.coupon_fail + '</div>');
						console.log('add coupon AJAX error');
						console.log(response);
					},
					complete: function (response) {
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	});


	// Remove coupon (v2)
	$(document).on('click', 'table#kco-totals .kco-remove-coupon', function (event) {
		if (!performingAjax) {
			performingAjax = true;
			blockCartWidget();

			event.preventDefault();

			if (typeof window._klarnaCheckout == 'function') {
				window._klarnaCheckout(function (api) {
					api.suspend();
				});
			}

			remove_coupon = $(this).data('coupon');
			clicked_el = $(this);
			kco_widget = $('#klarna-checkout-widget');

			$.ajaxq('KCOQueue',
				{
					url: kcoAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'klarna_checkout_remove_coupon_callback',
						remove_coupon: remove_coupon,
						nonce: kcoAjax.klarna_checkout_nonce
					},
					success: function (response) {
						$(clicked_el).closest('tr').remove();
						$('#klarna-checkout-widget').html(response.data.widget_html);

						// Remove WooCommerce notification
						$('#klarna-checkout-widget .woocommerce-info + .woocommerce-message').remove();
					},
					error: function (response) {
						console.log('remove coupon AJAX error');
						console.log(response);
					},
					complete: function (response) {
						if (typeof window._klarnaCheckout == 'function') {
							window._klarnaCheckout(function (api) {
								api.resume();
							});
						}
						performingAjax = false;
						unblockCartWidget();
					}
				}
			);
		}
	});

	// End KCO widget

	var returned_data_v2 = '';
	var returned_shipping_data_v2 = '';
	// Address change (email, postal code) v2
	if (typeof window._klarnaCheckout == 'function') {
		window._klarnaCheckout(function (api) {
			// For v2 use 'change' JS event to capture
			if ('v2' == kcoAjax.version) {
				var customerEmail = '';
				var customerPostal = '';

				api.on({
					'change': function (data) {
						// Check if the data actually changed
						if ( returned_data_v2 !== JSON.stringify(data) ) {
							returned_data_v2 = JSON.stringify(data);

							if ('' != data.email && '' != data.postal_code) {
								// Check if email and postal code have changed since last 'change' event
								if (customerEmail != data.email || customerPostal != data.postal_code) {
									customerEmail = data.email;
									customerPostal = data.postal_code;

									blockCartWidget();

									window._klarnaCheckout(function (api) {
										api.suspend();
									});


									// Check if email is not defined (AT and DE only) and set it to this value
									// For AT and DE, email field is not captured inside data object
									if (data.email === undefined) {
										data.email = 'guest_checkout@klarna.com';
									}

									if ('' != data.email) {
										kco_widget = $('#klarna-checkout-widget');
										console.log( 'id ' + $('#klarna-checkout-widget') );
										console.log( 'var ' + kco_widget );

										$(document.body).trigger('kco_widget_update', data);


										$.ajaxq('KCOQueue', {
											url: kcoAjax.ajaxurl,
											type: 'POST',
											dataType: 'json',
											data: {
												action: 'kco_iframe_change_cb',
												email: data.email,
												postal_code: data.postal_code,
												country: data.country,
												nonce: kcoAjax.klarna_checkout_nonce
											}
										})
											.done(function (response) {
												// Check if a product is out of stock
												if (false === response.success) {
													location.reload();
													return;
												}

												$('#klarna-checkout-widget').html(response.data.widget_html);
												$(document.body).trigger('kco_widget_updated', response);
												unblockCartWidget();

												window._klarnaCheckout(function (api) {
													api.resume();
												});
											})
											.fail(function (response) {
												console.log('change AJAX error');
												console.log(response);

												window._klarnaCheckout(function (api) {
													api.resume();
												});
												unblockCartWidget();
											});
									}
								}
							}
						}

						$(document.body).trigger('kco_change', data);
					},
					shipping_address_change: function(data) {
						if ( returned_shipping_data_v2 !== JSON.stringify(data) ) {
							returned_shipping_data_v2 = JSON.stringify(data);

							// Check if email and postal code have changed since last 'change' event
							if ('' != data.email && '' != data.postal_code) {
								window._klarnaCheckout(function (api) {
									api.suspend();
								});

								$.ajaxq('KCOQueue', {
									url: kcoAjax.ajaxurl,
									type: 'POST',
									dataType: 'json',
									data: {
										action: 'kco_iframe_shipping_address_change_v2_cb',
										postal_code: data.postal_code,
										country: data.country,
										nonce: kcoAjax.klarna_checkout_nonce
									}
								})
									.done(function (response) {
										$('#klarna-checkout-widget').html(response.data.widget_html);

										window._klarnaCheckout(function (api) {
											api.resume();
										});
									})
									.fail(function (response) {
										console.log('shipping_address_change_v2 AJAX error');
										console.log(response);

										window._klarnaCheckout(function (api) {
											api.resume();
										});
									});
							}

							$(document.body).trigger('kco_shipping_address_change', data);
						}
					},
					order_total_change: function(data) {
						$(document.body).trigger('kco_order_total_change', data);
					}
				});
			}

			var returned_address_v3 = '';
			// Address change (postal code, region) v3
			if ('v3' == kcoAjax.version) {
				api.on({
					'shipping_address_change': function (data) {
						// Check if the address actually changed
						if ( returned_address_v3 !== JSON.stringify(data) ) {
							returned_address_v3 = JSON.stringify(data);

							if ('' != data.postal_code || '' != data.region) {
								window._klarnaCheckout(function (api) {
									api.suspend();
								});

								kco_widget = $('#klarna-checkout-widget');

								$.ajaxq('KCOQueue',
									{
										url: kcoAjax.ajaxurl,
										type: 'POST',
										dataType: 'json',
										data: {
											action: 'kco_iframe_shipping_address_change_cb',
											city: data.city,
											country: data.country,
											email: data.email,
											family_name: data.family_name,
											given_name: data.given_name,
											phone: data.phone,
											postal_code: data.postal_code,
											region: data.region,
											street_address: data.street_address,
											street_address2: data.street_address2,
											title: data.title,
											nonce: kcoAjax.klarna_checkout_nonce
										},
										success: function (response) {
											$('#klarna-checkout-widget').html(response.data.widget_html);
										},
										error: function (response) {
											console.log('shipping_address_change AJAX error');
											console.log(response);
										},
										complete: function (response) {
											window._klarnaCheckout(function (api) {
												api.resume();
											});
										}
									}
								);
							}
						}
					}
				});
			}


			api.on({
				'shipping_option_change': function (data) {
					new_method = data.id;
					kco_widget = $('#klarna-checkout-widget');

					$.ajaxq('KCOQueue',
						{
							url: kcoAjax.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'kco_iframe_shipping_option_change_cb',
								new_method: new_method,
								nonce: kcoAjax.klarna_checkout_nonce
							},
							success: function (response) {
								$('#klarna-checkout-widget').html(response.data.widget_html);
							},
							error: function (response) {
								console.log('shipping_option_change AJAX error');
								console.log(response);
							}
						}
					);
				}
			});

		});
	}

	// Check if error message is shown aswell as a loaded iframe.
	function checkForIframeAndError() {
		if( $('#klarna-checkout-container').length > -1 && $('.kco-error').length > -1  ) {
			return true;
		}
		return false;
	}

	// Remove any error message if iframe is loaded
	$('body').ajaxComplete( function() {
		if( checkForIframeAndError() ) {
			$('.kco-error').hide();
		}
	});
	$('body').change( function() {
		if( checkForIframeAndError() ) {
			$('.kco-error').hide();
		}
	});
	if( checkForIframeAndError() ) {
		$('.kco-error').hide();
	}
});