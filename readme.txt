=== WooCommerce Klarna Gateway ===
Contributors: krokedil, niklashogefjord, slobodanmanic
Tags: ecommerce, e-commerce, woocommerce, klarna
Requires at least: 4.2
Tested up to: 4.9.8
WC requires at least: 3.0.0
WC tested up to: 3.5.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

By Krokedil - http://krokedil.com/



== DESCRIPTION ==

Klarna Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Klarna later (http://www.klarna.com/). This plugin utilizes Klarna Invoice, Klarna Part payment and Klarna Checkout.

When the order is passed to Klarna a credit record of the customer is made. If the check turns out all right, Klarna creates an invoice/order in their system. After you (as the merchant) completes the order in WooCommerce, the invoice/order can be automatically activated in Klarnas system.

Klarna is a great payment alternative for merchants and customers in Sweden, Norway, Finland, Denmark, Germany, Austria and the Netherlands.



== INSTALLATION	 ==

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go to --> WooCommerce --> Settings --> Checkout and configure your Klarna settings.

Documentation can be found at http://docs.woothemes.com/document/klarna/


== CREDITS	 ==

Huge thanks to: 
Vincent Suurenbroek - Dutch translation
Kenneth Bårdseng & Jarle Dahl Bergersen - Norwegian translation


== CHANGELOG ==

= 2018.11.12	- version 2.6.2 =
* Tweak			- Code cleaning.
* Fix			- Check if klarna_checkout exist in get_available_payment_gateways before change_checkout_url (URL to checkout page). Props to @jonathan-dejong.
* Fix			- Bug fix in product stock check in validate callback.
* Fix			- Bug fix in check_subscription_product_limit in validate callback.
* Fix			- Improved product_needs_shipping check in validate callback (allow virtual products + supscription products with free frial).
* Fix			- Add improved error message in checkout page for subscription_limit validation
* Fix			- Only send shipping line item to Klarna if one exist in cart.
* Fix			- Logging improvements.
* Fix			- PHP notice fix.

= 2018.10.29	- version 2.6.1 =
* Fix			- Reverted plugin version number in User agent sent to Klarna. Caused issues with Order Management API calls.
