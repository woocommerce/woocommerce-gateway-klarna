=== WOOCOMMERCE KLARNA GATEWAY ===

By Niklas Högefjord - http://krokedil.com/


== DESCRIPTION ==

Klarna Gateway is a plugin that extends WooCommerce, allowing your customers to get their products first and pay by invoice to Klarna later (http://www.klarna.com/). This plugin utilizes Klarna Invoice and Klarna Account (Standard Integration type).

When the order is passed to Klarna a credit record of the customer is made. If the check turns out all right, Klarna creates an invoice in their system. After you (as the merchant) completes the order in WooCommerce, you need to log in to Klarna to approve/send the invoice.

Klarna is a great payment alternative for merchants and customers in Sweden, Denmark, Finland, Norway, Germany and the Nederlands.


INVOICE FEE HANDLING
Since of version 1.2 the Invoice Fee for Klarna Invoice are added as a simple (hidden) product. This is to match order total in WooCommerce and your Klarna account (in earlier versions the invoice fee only were added to Klarna).

To create a Invoice fee product: 
- Add a simple (hidden) product. Mark it as a taxable product.
- Go to the Klarna Gateway settings page and add the ID of the Invoice Fee product. The ID can be found by hovering the Invoice Fee product on the Products page in WooCommerce.


== IMPORTANT NOTE ==

This plugin does not currently support Campaigns or Mobile payments.

The plugin only works if the currency is set to Swedish Krona, Norwegian Krone, Danish Krone or Euros and the Base country is set to Sweden, Norway, Denmark, Finland, Germany or Netherlands.

Make sure that read and write permissions for the directory "srv" (located in woocommerce-gateway-klarna) and the containing file "pclasses.json" is set to 777 in order to fetch the available PClasses from Klarna. This is for use of Klarna Account.


== INSTALLATION	 ==

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go WooCommerce Settings --> Payment Gateways and configure your Klarna settings.
6. Make sure that read and write permissions for the directory "srv" (located in woocommerce-gateway-klarna) and the containing file "pclasses.json" is set to 777 in order to fetch the available PClasses from Klarna.