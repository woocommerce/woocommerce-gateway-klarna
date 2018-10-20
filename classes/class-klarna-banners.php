<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WC_Klarna_Banners_V2' ) ) {
	/**
	 * Displays merchant information in the backend.
	 */
	class WC_Klarna_Banners_V2 {
		/**
		 * WC_Klarna_Banners_V2 constructor.
		 */
		public function __construct() {
			add_action( 'in_admin_header', array( $this, 'klarna_banner' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
			add_action( 'wp_ajax_hide_klarna_v2_banner', array( $this, 'hide_klarna_banner' ) );
			add_action( 'wp_ajax_nopriv_hide_klarna_v2_banner', array( $this, 'hide_klarna_banner' ) );
		}

		/**
		 * Loads admin CSS file, has to be done here instead of gateway class, because
		 * it is required in all admin pages.
		 */
		public function load_admin_css() {
			wp_enqueue_style(
				'klarna_payments_admin',
				plugins_url( 'assets/css/klarna-admin.css?v=20181003', KLARNA_MAIN_FILE )
			);
		}

		/**
		 * Loads Klarna banner in admin pages.
		 */
		public function klarna_banner() {

			// Always show banner in this plugin.
			$show_banner = true;

			if ( $show_banner  && false === get_transient( 'klarna_v2_hide_banner' ) ) {
				?>
				<div id="kb-spacer"></div>
				<div id="klarna-v2-banner">
					<div id="kb-left">
						<h1>Get ready to upgrade your product</h1>
						<p>Klarna is entering a new world of smooth. We would love for you to join us on the ride and to do so, you’ll need to upgrade your Klarna products to a new integration - You'll then always get the latest release that Klarna develops and you’ll keep your current agreement along with your price settings.</p>
						<a class="kb-button"
						   href="https://hello.klarna.com/product-upgrade.html?utm_source=woo&utm_medium=referral&utm_campaign=woo&utm_content=wooPU"
						   target="_blank">Make it happen!</a>
					</div>
					<div id="kb-right">
						<h1>If you want to make a switch</h1>
						<p>If you want to switch Klarna products you’ll need to sign a new contract with Klarna, for example from Klarna Payments to Klarna Checkout. Download the extension you want and retrieve credentials from Klarna and start selling!</p>
						<a class="kb-button"
						   href="https://www.klarna.com/international/business/woocommerce/?utm_source=woo&utm_medium=referral&utm_campaign=woo&utm_content=wooOPSU#section-10"
						   target="_blank">Sign up with Klarna</a>
					</div>
					<img id="kb-image"
						 src="<?php echo esc_url( KLARNA_URL ); ?>/assets/img/klarna_logo_white.png"
						 alt="Klarna logo" width="110"/>
						 <span class="kb-v2-dismiss dashicons dashicons-dismiss"></span>
				</div>

				<script type="text/javascript">
					jQuery(document).ready(function($){

						jQuery('.kb-v2-dismiss').click(function(){
							jQuery('#klarna-v2-banner').slideUp();
							jQuery.post(
								ajaxurl,
								{
									action		: 'hide_klarna_v2_banner',
									_wpnonce	: '<?php echo wp_create_nonce('hide-klarna-v2-banner'); ?>',
								},
								function(response){
									console.log('Success hide v2 banner');
									
								}
							);
											
						});
					});
					</script>
				<?php
			}
		}


		/**
		 * Hide Klarna banner in admin pages for.
		 */
		public function hide_klarna_banner() {
			set_transient( 'klarna_v2_hide_banner', '1', 5 * DAY_IN_SECONDS );
			wp_send_json_success( 'Hide Klarna V2 banner.' );
			wp_die();
		}
	}
}

new WC_Klarna_Banners_V2();