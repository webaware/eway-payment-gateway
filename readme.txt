=== eWAY Payment Gateway ===
Contributors: webaware
Plugin Name: eWAY Payment Gateway
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/eway-payment-gateway/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CXNFEP4EAMTG6
Tags: eway, payment, ecommerce, e-commerce, credit cards, australia, wp e-commerce, woocommerce, events manager, events, booking
Requires at least: 3.2.1
Tested up to: 3.8
Stable tag: 3.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a credit card payment gateway for eWAY (Australia) to some popular WordPress plugins

== Description ==

The eWAY Payment Gateway adds a credit card payment gateway integration for [eWAY in Australia](http://www.eway.com.au/) [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments) and [Stored Payments API](http://www.eway.com.au/developers/api/stored-%28xml%29). These plugins are supported:

* [WP e-Commerce](http://wordpress.org/plugins/wp-e-commerce/) shopping cart plugin
* [WooCommerce](http://wordpress.org/plugins/woocommerce/) shopping cart plugin
* [Another WordPress Classifieds Plugin](http://wordpress.org/plugins/another-wordpress-classifieds-plugin/) classified ads plugin
* [Events Manager Pro](http://eventsmanagerpro.com/) event bookings plugin

Looking for a Gravity Forms integration? Try [Gravity Forms eWAY](http://wordpress.org/plugins/gravityforms-eway/).

### Features: ###

* card holder's name can be different to the purchaser's name
* basic data validation performed before submitting to eWAY
* eWAY transaction ID and bank authcode are recorded for successful payments
* supports Stored Payments for drop-ship merchants / delayed billing
* supports Beagle anti-fraud measures for Direct Payments (for supporting plugins)
* drop-in compatible with eWAY payment gateway from the WP e-Commerce Gold Cart plugin (except recurring billing -- see FAQ)
* it's free!

= Sponsorships =

* Another WordPress Classifieds Plugin integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)
* Events Manager Pro integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)

Thanks for sponsoring new features for eWAY Payment Gateway!

= Requirements: =
* you need to install a shopping cart plugin listed above
* you need an SSL certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments) and [Stored Payments API](http://www.eway.com.au/developers/api/stored-%28xml%29), but does not support eWAY's hosted payment form

== Installation ==

After uploading and activating this plugin, you need to configure it.

**WP e-Commerce**

1. Navigate to 'Settings->Store->Payments' on the menu
2. Activate the eWAY payment gateway and click the Update button
3. Edit the eWAY payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link
4. Edit the eWAY customer ID and select the appropriate settings, including which checkout fields map to eWAY fields

**WooCommerce**

1. Navigate to 'WooCommerce->Settings->Payment Gateways' on the menu
2. Select eWAY from the Payment Gateways menu
3. Tick the field 'Enable/Disable' to enable the gateway
4. Edit the eWAY customer ID and select the appropriate settings

**Another WordPress Classifieds Plugin**

1. Navigate to 'Classified->Settings->Payment' on the menu
2. Click the Activate eWAY checkbox
3. Edit the eWAY customer ID and select the appropriate settings

**Events Manager**

1. Navigate to 'Events->Payment Gateways' on the menu
2. Click the Activate link underneath the eWAY gateway name
3. Click the Settings link underneath the eWAY gateway name
4. Edit the eWAY customer ID and select the appropriate settings

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

== Frequently Asked Questions ==

= What is eWAY? =

eWAY is a leading provider of online payments solutions for Australia, New Zealand and the UK. This plugin integrates with the Australian Direct Payments and Stored Payments gateways, so that your website can safely accept credit card payments.

= Is recurring billing supported? =

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it. If you need recurring billing for WP e-Commerce, buy the [Gold Cart plugin](http://getshopped.org/premium-upgrades/premium-plugin/gold-cart-plugin/).

If you just need a simple way to record recurring payments such as donations, you might want to try [Gravity Forms](http://www.gravityforms.com/) and [Gravity Forms eWAY](http://wordpress.org/extend/plugins/gravityforms-eway/) which does support recurring payments.

= Can I use other eWAY gateways, outside of Australia? =

Not yet. There are plans to integrate eWAY's Rapid Payments API sometime in 2013, so check back in a while.

= Can I use the eWAY hosted payment form with this plugin? =

No, this plugin only supports the [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments).

= What is Stored Payments? =

Like Direct Payments, the purchase information is sent to eWAY for processing, but with [Stored Payments](http://www.eway.com.au/how-it-works/payment-products#stored-payments) it isn't processed right away. The merchant needs to login to their eWAY Business Centre to complete each transaction. It's useful for shops that do drop-shipping and want to delay billing. Most websites should have this option set to No.

= What is Beagle? =

[Beagle](http://www.eway.com.au/how-it-works/payment-products#beagle-%28free%29) is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure [Beagle rules](http://www.eway.com.au/developers/resources/beagle-%28free%29-rules) in your MYeWAY console before enabling Beagle in this plugin.

**NB**: You will also need to add a Country field to your checkout form. Beagle works by comparing the country of the address with the country where the purchaser is using the Internet; Beagle won't be used when checking out without a country selected.

**NB**: Beagle isn't available for Another WordPress Classifieds Plugin due to the way that plugin collects billing information.

= Where do I find the eWAY transaction number? =

* **WP e-Commerce**: the eWAY transaction number and the bank authcode are shown under Billing Details when you view the sales log for a purchase in the WordPress admin.
* **WooCommerce**: the eWAY transaction number and the bank authcode are shown in the Custom Fields block when you view the order in the WordPress admin.
* **Events Manager**: from the Payment Gateways menu item or the Bookings menu item, you can view a list of transactions; the eWAY transaction ID is shown in the Transaction ID column, and the authcode in the Notes column.
* **Another WordPress Classifieds Plugin**: not available in v2.x of the plugin.

= Can I use this plugin with the WP e-Commerce Gold Cart? =

Yes, if you deactivate the Gold Cart's eWAY payment gateway and activate this one. The settings from the Gold Cart payment gateway will be picked up by this gateway automatically (they are stored in the same places).

= I get an SSL error when my checkout attempts to connect with eWAY =

This is a common problem in local testing environments. Please [read this post](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

== Filter hooks ==

Developers can use these filter hooks to modify some eWAY invoice properties. Each filter receives a string for the field value.

**WP e-Commerce**

* `wpsc_merchant_eway_invoice_desc` for modifying the invoice description
* `wpsc_merchant_eway_invoice_ref` for modifying the invoice reference
* `wpsc_merchant_eway_option1` for setting the option1 field
* `wpsc_merchant_eway_option2` for setting the option2 field
* `wpsc_merchant_eway_option3` for setting the option3 field

**WooCommerce**

* `woocommerce_eway_invoice_desc` for modifying the invoice description
* `woocommerce_eway_invoice_ref` for modifying the invoice reference
* `woocommerce_eway_option1` for setting the option1 field
* `woocommerce_eway_option2` for setting the option2 field
* `woocommerce_eway_option3` for setting the option3 field
* `woocommerce_eway_icon` for changing the payment gateway icon

**Another WordPress Classifieds Plugin**

* `awpcp_eway_invoice_desc` for modifying the invoice description
* `awpcp_eway_invoice_ref` for modifying the invoice reference
* `awpcp_eway_option1` for setting the option1 field
* `awpcp_eway_option2` for setting the option2 field
* `awpcp_eway_option3` for setting the option3 field

**Events Manager**

* `em_eway_invoice_desc` for modifying the invoice description
* `em_eway_invoice_ref` for modifying the invoice reference
* `em_eway_option1` for setting the option1 field
* `em_eway_option2` for setting the option2 field
* `em_eway_option3` for setting the option3 field

== Screenshots ==

1. WP e-Commerce payments settings
2. WP e-Commerce Sales Log with transaction ID and authcode
3. WooCommerce payments settings
4. WooCommerce order details with transaction ID and authcode
5. Events Manager payments settings
6. Events Manager transactions with transaction ID and authcode
7. Another WordPress Classifieds Plugin payments settings

== Changelog ==

= 3.1.2 [2014-01-03] =
* changed: credit card field now forces digits only so that number keyboard is used on iPad/iPhone
* added: filter `em_eway_amount` for changing the booking amount, e.g. adding fees

= 3.1.1 [2013-12-10] =
* fixed: doco / settings page didn't explain that Beagle requires an Address field
* changed: permit card numbers with spaces / dashes, but strip before submitting to eWAY
* changed: move some WooCommerce setting descriptions into tips to reduce screen clutter in admin
* added: HTML5 text field patterns for credit card number, CVV/CVN
* added: filter `woocommerce_eway_icon` for changing the payment gateway icon

= 3.1.0 [2013-11-21] =
* changed: support multiple bookings mode in Events Manager
* changed: some links to eWAY website
* added: support for v3 of Another WordPress Classifieds Plugin
* added: WooCommerce and WP e-Commerce payment method logos

= 3.0.1 [2013-03-07] =
* changed: update for WooCommerce v2.0.x compatibility

= 3.0.0 [2013-03-01] =
* added: WooCommerce integration
* added: Another WordPress Classifieds Plugin integration (sponsored by [Michael Major Media](http://michaelmajor.com.au/) -- thanks!)
* added: Events Manager integration (sponsored by [Michael Major Media](http://michaelmajor.com.au/) -- thanks!)
* changed: use WP e-Commerce 2.8.9+ hooks and functions
* changed: refactored for greater generalisation

= 2.4.0 [2013-01-23] =
* fixed: declined payments now record status as Payment Declined instead of Incomplete Sale
* added: record authcode for transactions, and show in Sales Log
* added: send WP e-Commerce transaction number as both customer reference and invoice reference (customer reference can be filtered)
* added: support for [Beagle (free)](http://www.eway.com.au/developers/resources/beagle-%28free%29-rules) anti-fraud using geo-IP (Direct Payments only)

= 2.3.1 [2013-01-20] =
* fixed: close table cell elements in form field template

= 2.3.0 [2013-01-20] =
* fixed: successful Direct transactions are now marked as Accepted Payment, not Order Received
* added: eWAY credit card form fields now in template, can be customised by theme
* added: can now use eWAY Stored Payments, e.g. for merchants who do drop-shipping
* changed: use WordPress function wp_remote_post() instead of directly calling curl functions

= 2.2.1 [2012-10-22] =
* fixed: address on eWAY invoice was getting "0, " prepended when PHP < 5.3

= 2.2.0 [2012-09-25] =
* fixed: country name used in eWAY address field, not country code
* changed: remote SSL certificate is verified (i.e. eWAY's certificate)
* added: prevent XML injection attacks when loading eWAY response (security hardening)
* added: filter hooks for invoice description and reference
* added: if customer name isn't mandatory and not given, will use cardholder's name

= 2.1.0 [2012-07-03] =
* changed: CVN is now a required field, no option to omit; Australian banks are all moving to require CVN and some already do
* added: customer name is now supported, if configured in WP e-Commerce payment admin; card holder name is not seen in eWAY notification emails, so customer name is required for showing who made the purchase

= 2.0.4 [2012-05-13] =
* fixed: invoice number recorded in eWAY invoice reference field

= 2.0.3 [2012-05-05] =
* fixed: optional fields for address, email are no longer required for eWAY payment

= 2.0.2 [2012-04-16] =
* fixed: undeclared array index errors

= 2.0.1 [2012-04-12] =
* fixed: admin transposed Use Testing Environment and Use CVN Security

= 2.0.0 [2012-04-08] =
* final cleanup and refactor for public release

= 1.0.0 [2011-09-15] =
* private version, not released to public

== Upgrade Notice ==

= 2.1.0 =
CVN is now a required field, no option to omit
