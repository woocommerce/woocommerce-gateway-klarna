=== WooCommerce Klarna Gateway ===
Contributors: krokedil, niklashogefjord
Tags: ecommerce, e-commerce, woocommerce, klarna
Requires at least: 3.8
Tested up to: 4.1
Requires WooCommerce at least: 2.1
Tested WooCommerce up to: 2.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

By Krokedil - http://krokedil.com/



== DESCRIPTION ==

Klarna Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Klarna later (http://www.klarna.com/). This plugin utilizes Klarna Invoice, Klarna Account, Klarna Special Campaign and Klarna Checkout (Advanced Integration type).

When the order is passed to Klarna a credit record of the customer is made. If the check turns out all right, Klarna creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Klarna to approve/send the invoice.

Klarna is a great payment alternative for merchants and customers in Sweden, Denmark, Finland, Norway, Germany, Austria and the Netherlands.



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
Kenneth Bårdseng - Norwegian translation


== Upgrade Notice ==
= 1.8 =
This version is a major upgrade. The plugin does now use Klarnas Advance Integration type. This may change how and where you activate invoices in Klarnas backoffice.
You NEED to visit the plugin settings page after the upgrade and re-save your settings. This is needed because EID and Secret is now separate for each country for Klarna Invoice, Account and Special Campaign.