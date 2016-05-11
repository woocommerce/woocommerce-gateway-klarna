<?php
/**
 * Klarna get_addresses
 *
 * The Klarna get_addresses class displays a form field above the billing form on the WooCommerce Checkout page.
 * The Get Addresses form only displays if Klarna Account or Invoice Payment are enabled and active.
 * The customer enters their personal identity number/organisation number and then retrieves a getAddresses response from Klarna.
 * The response from Klarna contains the registered address for the individual/orgnaisation.
 * If a company uses the Get Addresses function the answer could contain several addresses. The customer can then select wich one to use.
 * When a retrieved address is selected, several checkout form fields are being changed to readonly and can after this not be edited.
 *
 *
 * @class        WC_Klarna_Get_Address
 * @version        1.0
 * @category    Class
 * @author        Krokedil
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Klarna_Get_Address {

	public function __construct() {
		$invo_settings          = get_option( 'woocommerce_klarna_invoice_settings' );
		$this->invo_eid         = $invo_settings['eid_se'];
		$this->invo_secret      = $invo_settings['secret_se'];
		$this->invo_testmode    = $invo_settings['testmode'];
		$this->invo_enabled     = $invo_settings['enabled'];
		$this->invo_dob_display = 'description_box';

		$partpay_settings          = get_option( 'woocommerce_klarna_part_payment_settings' );
		$this->partpay_eid         = $partpay_settings['eid_se'];
		$this->partpay_secret      = $partpay_settings['secret_se'];
		$this->partpay_testmode    = $partpay_settings['testmode'];
		$this->partpay_enabled     = $partpay_settings['enabled'];
		$this->partpay_dob_display = 'description_box';

		add_action( 'wp_ajax_ajax_request', array( $this, 'ajax_request' ) );
		add_action( 'wp_ajax_nopriv_ajax_request', array( $this, 'ajax_request' ) );

		add_action( 'wp_footer', array( $this, 'js' ) );
		add_action( 'wp_footer', array( $this, 'checkout_restore_customer_defaults' ) );

		// GetAddresses response above the checkout billing form
		add_action( 'woocommerce_before_checkout_form', array( $this, 'get_address_response' ) );
	} // End constructor


	/**
	 * JS restoring the default checkout field values if user switch from
	 * Klarna (invoice, account or campaign) to another payment method.
	 *
	 * This is to prevent that customers use Klarnas Get Address feature
	 * and in the end use another payment method than Klarna.
	 */
	public function checkout_restore_customer_defaults() {
		if ( is_checkout() && 'SE' == $this->get_shop_country() && ( $this->partpay_enabled || $this->invo_enabled ) && ! is_klarna_checkout() ) {

			if ( defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
				return;
			}

			global $woocommerce, $current_user;

			$original_customer = array();
			$original_customer = $woocommerce->session->get( 'customer' );

			$original_billing_first_name  = '';
			$original_billing_last_name   = '';
			$original_shipping_first_name = '';
			$original_shipping_last_name  = '';
			$original_billing_company     = '';
			$original_shipping_company    = '';

			$original_billing_first_name  = $current_user->billing_first_name;
			$original_billing_last_name   = $current_user->billing_last_name;
			$original_shipping_first_name = $current_user->shipping_first_name;
			$original_shipping_last_name  = $current_user->shipping_last_name;
			$original_billing_company     = $current_user->billing_company;
			$original_shipping_company    = $current_user->shipping_company;
			?>

			<script type="text/javascript">
				var getAddressCompleted = 'no';

				jQuery(document).ajaxComplete(function () {

					// On switch of payment method
					jQuery('input[name="payment_method"]').on('change', function () {
						if ('yes' == getAddressCompleted) {
							var selected_paytype = jQuery('input[name=payment_method]:checked').val();
							if (selected_paytype !== 'klarna_invoice' && selected_paytype !== 'klarna_part_payment') {

								jQuery(".klarna-response").hide();

								// Replace fetched customer values from Klarna with the original customer values
								jQuery("#billing_first_name").val('<?php echo $original_billing_first_name;?>');
								jQuery("#billing_last_name").val('<?php echo $original_billing_last_name;?>');
								jQuery("#billing_company").val('<?php echo $original_billing_company;?>');
								jQuery("#billing_address_1").val('<?php echo $original_customer['address_1'];?>');
								jQuery("#billing_address_2").val('<?php echo $original_customer['address_2'];?>');
								jQuery("#billing_postcode").val('<?php echo $original_customer['postcode'];?>');
								jQuery("#billing_city").val('<?php echo $original_customer['city'];?>');

								jQuery("#shipping_first_name").val('<?php echo $original_shipping_first_name;?>');
								jQuery("#shipping_last_name").val('<?php echo $original_shipping_last_name;?>');
								jQuery("#shipping_company").val('<?php echo $original_shipping_company;?>');
								jQuery("#shipping_address_1").val('<?php echo $original_customer['shipping_address_1'];?>');
								jQuery("#shipping_address_2").val('<?php echo $original_customer['shipping_address_2'];?>');
								jQuery("#shipping_postcode").val('<?php echo $original_customer['shipping_postcode'];?>');
								jQuery("#shipping_city").val('<?php echo $original_customer['shipping_city'];?>');


							}
						}
						// console.log( getAddressCompleted );
					});
				});
			</script>
			<?php
		}
	} // End function


	/**
	 * JS for fetching the personal identity number before the call to Klarna
	 * and populating the checkout fields after the call to Klarna.
	 */
	function js() {
		if ( is_checkout() && $this->get_shop_country() == 'SE' && ( $this->partpay_enabled || $this->invo_enabled ) && ! is_klarna_checkout() ) {

			if ( defined( 'WOOCOMMERCE_KLARNA_CHECKOUT' ) ) {
				return;
			}
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {

					var pno_getadress = '';

					$(document).on('click', '.compadress', function () {
						var value = $(this).attr("id");

						var json = $("#h" + value).val();
						var info = JSON.parse(json);

						klarnainfo("company", info, value);
					});

					function klarnainfo(type, info, value) {

						if (type == 'company') {
							var adress = info[0][value];
							var orgno_getadress = "";
							/*
							 if(jQuery('#klarna_pno').val() != ''){
							 orgno_getadress = jQuery('#klarna_pno').val();
							 }
							 */
							jQuery("#billing_first_name").val(adress['fname']);
							jQuery("#billing_last_name").val(adress['lname']);
							jQuery("#billing_company").val(adress['company']); //.prop( "readonly", true );
							jQuery("#billing_address_1").val(adress['street']); //.prop( "readonly", true );
							jQuery("#billing_address_2").val(adress['careof']); //.prop( "readonly", true );
							jQuery("#billing_postcode").val(adress['zip']); //.prop( "readonly", true );
							jQuery("#billing_city").val(adress['city']); //.prop( "readonly", true );

							jQuery("#shipping_first_name").val(adress['fname']);
							jQuery("#shipping_last_name").val(adress['lname']);
							jQuery("#shipping_company").val(adress['company']); //.prop( "readonly", true );
							jQuery("#shipping_address_1").val(adress['street']); //.prop( "readonly", true );
							jQuery("#shipping_address_2").val(adress['careof']); //.prop( "readonly", true );
							jQuery("#shipping_postcode").val(adress['zip']); //.prop( "readonly", true );
							jQuery("#shipping_city").val(adress['city']); //.prop( "readonly", true );

							jQuery("#phone_number").val(adress['cellno']);
							// jQuery("#klarna_pno").val(orgno_getadress);
							getAddressCompleted = 'yes';
						}

						if (type == 'private') {
							if (value == 0) {

								var adress = info[0][value];
								var pno_getadress = "";

								/*
								 if(jQuery('#klarna_pno').val() != ''){
								 pno_getadress = jQuery('#klarna_pno').val();
								 }
								 */
								jQuery("#billing_first_name").val(adress['fname']); //.prop( "readonly", true );
								jQuery("#billing_last_name").val(adress['lname']); //.prop( "readonly", true );
								jQuery("#billing_address_1").val(adress['street']); //.prop( "readonly", true );
								jQuery("#billing_address_2").val(adress['careof']);
								jQuery("#billing_postcode").val(adress['zip']); //.prop( "readonly", true );
								jQuery("#billing_city").val(adress['city']); //.prop( "readonly", true );

								jQuery("#shipping_first_name").val(adress['fname']); //.prop( "readonly", true );
								jQuery("#shipping_last_name").val(adress['lname']); //.prop( "readonly", true );
								jQuery("#shipping_address_1").val(adress['street']); //.prop( "readonly", true );
								jQuery("#shipping_address_2").val(adress['careof']);
								jQuery("#shipping_postcode").val(adress['zip']); //.prop( "readonly", true );
								jQuery("#shipping_city").val(adress['city']); //.prop( "readonly", true );

								jQuery("#phone_number").val(adress['cellno']);
								// jQuery("#klarna_pno").val(pno_getadress);
								getAddressCompleted = 'yes';
							}
						}
					}


					jQuery(document).on('click', '.klarna-push-pno', function () {
						jQuery('.klarna-push-pno').prop('disabled', true);

						if (jQuery('#klarna_invoice_pno').length && jQuery('#klarna_invoice_pno').val() != '') {
							pno_getadress = jQuery('#klarna_invoice_pno').val();
						} else if (jQuery('#klarna_part_payment_pno').length && jQuery('#klarna_part_payment_pno').val() != '') {
							pno_getadress = jQuery('#klarna_part_payment_pno').val();
						}

						if (pno_getadress == '') {
							jQuery(".klarna-get-address-message").show();
							jQuery(".klarna-get-address-message").html('<span style="clear:both; margin: 5px 2px; padding: 4px 8px; background:#ffecec"><?php _e( 'Be kind and enter a date of birth!', 'woocommerce-gateway-klarna' );?></span>');
							// jQuery('.klarna-push-pno').prop('disabled', false);
						} else {
							jQuery.post(
								'<?php echo site_url() . '/wp-admin/admin-ajax.php' ?>',
								{
									action: 'ajax_request',
									pno_getadress: pno_getadress,
									_wpnonce: '<?php echo wp_create_nonce( 'nonce-register_like' ); ?>',
								},
								function (response) {
									// console.log(response);

									if (response.get_address_message == "" || (typeof response.get_address_message === 'undefined')) {
										$(".klarna-get-address-message").hide();

										//if(klarna_client_type == "company"){
										var adresses = new Array();
										adresses.push(response);

										var res = "";
										//console.log(adresses[0].length);

										if (adresses[0].length < 2) {

											// One address found
											$(".klarna-response").show();
											res += '<ul class="woocommerce-message klarna-get-address-found"><li><?php _e( 'Address found and added to the checkout form.', 'woocommerce-gateway-klarna' );?></li></ul>';
											klarnainfo('private', adresses, 0);

										} else {

											// Multiple addresses found
											$(".klarna-response").show();

											res += '<ul class="woocommerce-message klarna-get-address-found multiple"><li><?php _e( 'Multiple addresses found. Select one address to add it to the checkout form.', 'woocommerce-gateway-klarna' );?></li><li>';
											for (var a = 0; a <= adresses.length; a++) {

												res += '<div id="adress' + a + '" class="adressescompanies">' +
													'<input type="radio" id="' + a + '" name="klarna-selected-company" value="klarna-selected-company' + a + '" class="compadress"  /><label for="klarna-selected-company' + a + '">' + adresses[0][a]['company'];
												if (adresses[0][a]['street'] != null) {
													res += ', ' + adresses[0][a]['street'];
												}

												if (adresses[0][a]['careof'] != '') {
													res += ', ' + adresses[0][a]['careof'];
												}

												res += ', ' + adresses[0][a]['zip'] + ' ' + adresses[0][a]['city'] + '</label>';
												res += "<input type='hidden' id='h" + a + "' value='" + JSON.stringify(adresses) + "' />";
												res += '</div>';

											}

											res += '</li></ul>';

										}

										jQuery(".klarna-response").html(res);

										// Scroll to .klarna-response
										$("html, body").animate({
												scrollTop: $(".klarna-response").offset().top
											},
											'slow');

										/*}
										 else{
										 klarnainfo(klarna_client_type, response, 0);
										 }*/

										jQuery('.klarna-push-pno').prop('disabled', false);
									}
									else {
										$(".klarna-get-address-message").show();
										$(".klarna-response").hide();

										jQuery(".klarna-get-address-message").html('<span style="clear:both;margin:5px 2px;padding:4px 8px;background:#ffecec">' + response.get_address_message + '</span>');

										$(".checkout .input-text").each(function (index) {
											$(this).val("");
											$(this).prop("readonly", false);
										});
										jQuery('.klarna-push-pno').prop('disabled', false);
									}
								}
							);
						}
					});

					$('body').on('moved_get_address_form', function() {
						if ('' != pno_getadress) {
							$('#klarna_part_payment_pno').val(pno_getadress);
							$('#klarna_invoice_pno').val(pno_getadress);
						}
					});
				});
			</script>
			<?php
		}
	} // End function


	/**
	 * Display the GetAddress fields
	 */
	public function get_address_button( $country ) {
		if ( ( $this->invo_enabled && $this->invo_dob_display == 'description_box' ) || ( $this->partpay_enabled && $this->partpay_dob_display == 'description_box' ) ) {
			ob_start();

			// Only display GetAddress button for Sweden
			if ( $country == 'SE' ) { ?>
				<button type="button" style="margin-top:0.5em" class="klarna-push-pno get-address-button button alt"><?php _e( 'Fetch', 'woocommerce-gateway-klarna' ); ?></button>
				<p class="form-row">
				<div class="klarna-get-address-message"></div>
				</p>
				<?php
			}

			return ob_get_clean();
		}
	} // End function


	/**
	 * Display the GetAddress response above the billing form on checkout
	 */
	public function get_address_response() {
		if ( ( $this->invo_enabled && $this->invo_dob_display == 'description_box' ) || ( $this->partpay_enabled && $this->partpay_dob_display == 'description_box' ) ) {

			?>
			<div class="klarna-response"></div>
			<?php
		}
	} // End function


	/**
	 * Ajax request callback function
	 */
	function ajax_request() {
		// The $_REQUEST contains all the data sent via ajax
		if ( isset( $_REQUEST ) ) {
			if ( '' != $this->partpay_eid && '' != $this->partpay_secret ) {
				$klarna_eid      = $this->partpay_eid;
				$klarna_secret   = $this->partpay_secret;
				$klarna_testmode = $this->partpay_testmode;
			} elseif ( '' != $this->invo_eid && '' != $this->invo_secret ) {
				$klarna_eid      = $this->invo_eid;
				$klarna_secret   = $this->invo_secret;
				$klarna_testmode = $this->invo_testmode;
			}

			// Test mode or Live mode		
			if ( $klarna_testmode == 'yes' ) {
				// Disable SSL if in testmode
				$klarna_ssl  = 'false';
				$klarna_mode = Klarna::BETA;
			} else {
				// Set SSL if used in webshop
				if ( is_ssl() ) {
					$klarna_ssl = 'true';
				} else {
					$klarna_ssl = 'false';
				}
				$klarna_mode = Klarna::LIVE;
			}

			$k = new Klarna();

			$k->config( $klarna_eid,                                            // EID
				$klarna_secret,                                        // Secret
				'SE',                                                    // Country
				'SE',                                                    // Language
				get_woocommerce_currency(),                            // Currency
				$klarna_mode,                                            // Live or test
				$pcStorage = 'json',                                    // PClass storage
				$pcURI = '/srv/pclasses.json'                            // PClass storage URI path
			);

			$pno_getadress = $_REQUEST['pno_getadress'];
			$return        = array();

			$k->setCountry( 'SE' ); // Sweden only
			try {
				$addrs = $k->getAddresses( $pno_getadress );

				foreach ( $addrs as $addr ) {

					//$return[] = $addr->toArray();
					$return[] = array(
						'email'   => utf8_encode( $addr->getEmail() ),
						'telno'   => utf8_encode( $addr->getTelno() ),
						'cellno'  => utf8_encode( $addr->getCellno() ),
						'fname'   => utf8_encode( $addr->getFirstName() ),
						'lname'   => utf8_encode( $addr->getLastName() ),
						'company' => utf8_encode( $addr->getCompanyName() ),
						'careof'  => utf8_encode( $addr->getCareof() ),
						'street'  => utf8_encode( $addr->getStreet() ),
						'zip'     => utf8_encode( $addr->getZipCode() ),
						'city'    => utf8_encode( $addr->getCity() ),
						'country' => utf8_encode( $addr->getCountry() ),
					);

				}

			} catch ( Exception $e ) {
				// $message = "{$e->getMessage()} (#{$e->getCode()})\n";
				$return = $e;
				$return = array(
					'get_address_message' => __( 'No address found', 'woocommerce-gateway-klarna' )
				);

			}

			wp_send_json( $return );

			// If you're debugging, it might be useful to see what was sent in the $_REQUEST
			// print_r($_REQUEST);
		} else {
			echo '';
			die();
		}

		die();
	} // End function

	// Helper function - get_shop_country
	public function get_shop_country() {
		$klarna_default_country = get_option( 'woocommerce_default_country' );

		// Check if woocommerce_default_country includes state as well. If it does, remove state
		if ( strstr( $klarna_default_country, ':' ) ) {
			$klarna_shop_country = current( explode( ':', $klarna_default_country ) );
		} else {
			$klarna_shop_country = $klarna_default_country;
		}

		return apply_filters( 'klarna_shop_country', $klarna_shop_country );
	}

} // End Class
$wc_klarna_get_address = new WC_Klarna_Get_Address;