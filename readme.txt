=== eWAY Payment Gateway ===
Contributors: webaware
Plugin Name: eWAY Payment Gateway
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/eway-payment-gateway/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CXNFEP4EAMTG6
Tags: wp e-commerce, eway, payment, ecommerce, credit cards, australia
Requires at least: 3.2.1
Tested up to: 3.5
Stable tag: 2.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add a credit card payment gateway for eWAY to the WP e-Commerce shopping cart plugin

== Description ==

The eWAY Payment Gateway adds a credit card payment gateway for [eWAY in Australia](http://www.eway.com.au/) [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html) and [Stored Payments API](http://www.eway.com.au/developers/api/stored-(xml)) to the [WP e-Commerce](http://wordpress.org/extend/plugins/wp-e-commerce/) shopping cart plugin, without requiring the Gold Cart option.

**Features:**

* card holder's name can be different to the purchaser's name
* basic data validation performed before submitting to eWAY
* eWAY transaction ID is displayed on purchases log, for successful payments
* drop-in compatible with eWAY payment gateway from the Gold Cart plugin (except recurring billing -- see FAQ)
* supports Stored Payments for drop-ship merchants
* it's free!

= Requirements: =
* you need to install [WP e-Commerce](http://wordpress.org/extend/plugins/wp-e-commerce/)
* you need an SSL certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html) and [Stored Payments API](http://www.eway.com.au/developers/api/stored-(xml)), but does not support eWAY's hosted payment form

= Filter hooks =

Developers can use these filter hooks to modify some eWAY invoice properties. Each filter receives a string for the field value.

* `wpsc_merchant_eway_invoice_desc` for modifying the invoice description
* `wpsc_merchant_eway_invoice_ref` for modifying the invoice reference
* `wpsc_merchant_eway_option1` for setting the option1 field
* `wpsc_merchant_eway_option2` for setting the option2 field
* `wpsc_merchant_eway_option3` for setting the option3 field

== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Activate the eWAY payment gateway through the 'Settings->Store' menu (WP e-Commerce settings)
4. Edit the eWAY payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

== Frequently Asked Questions ==

= What is eWAY? =

eWAY is a leading provider of online payments solutions for Australia, New Zealand and the UK. This plugin integrates with the Australian Direct Payments and Stored Payments gateways, so that your website can safely accept credit card payments.

= Is recurring billing supported? =

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it. If you need recurring billing, buy the [Gold Cart plugin](http://getshopped.org/premium-upgrades/premium-plugin/gold-cart-plugin/) for WP e-Commerce.

= Can I use other eWAY gateways, outside of Australia? =

Not yet. There are plans to integrate eWAY's Rapid Payments API sometime in 2013, so check back in a while.

= Can I use the eWAY hosted payment form with this plugin? =

No, this plugin only supports the [Direct Payments API](http://www.eway.com.au/developers/api/direct-payments.html).

= What is Stored Payments? =

Like Direct Payments, the purchase information is sent to eWAY for processing, but with [Stored Payments](http://www.eway.com.au/how-it-works/what-products-are-included-#stored-payments) it isn't processed right away. The merchant needs to login to their eWAY Business Centre to complete each transaction. It's useful for shops that do drop-shipping and want to delay billing. Most websites should have this option set to No.

= Where do I find the eWAY transaction number? =

Successful transaction details including the eWAY transaction number are shown under Billing Details when you view the sales log for a purchase in the WordPress admin.

= Can I use this plugin with the Gold Cart? =

Yes, if you deactivate the Gold Cart's eWAY payment gateway and activate this one. The settings from the Gold Cart payment gateway will be picked up by this gateway automatically (they are stored in the same places).

= I get an SSL error when my checkout attempts to connect with eWAY =

This is a common problem in local testing environments. Please [read this post](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

== Screenshots ==

1. WP e-Commerce payments settings
2. Sales Log showing successful transaction ID

== Changelog ==

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
