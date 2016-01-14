=== eWAY Payment Gateway ===
Contributors: webaware
Plugin Name: eWAY Payment Gateway
Plugin URI: http://shop.webaware.com.au/downloads/eway-payment-gateway/
Author URI: http://webaware.com.au/
Donate link: http://shop.webaware.com.au/donations/?donation_for=eWAY+Payment+Gateway
Tags: eway, payment, ecommerce, e-commerce, credit cards, australia, wp e-commerce, woocommerce, events manager, events, booking
Requires at least: 3.6.1
Tested up to: 4.4.1
Stable tag: 3.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrate some popular WordPress plugins with the eWAY credit card payment gateway

== Description ==

The eWAY Payment Gateway adds a credit card payment gateway integration for [eWAY in Australia](https://www.eway.com.au/) [Direct Payments API](https://www.eway.com.au/developers/api/direct-payments) and Stored Payments API. These plugins are supported:

* [WP eCommerce](https://wordpress.org/plugins/wp-e-commerce/) shopping cart plugin
* [WooCommerce](https://wordpress.org/plugins/woocommerce/) shopping cart plugin
* [Another WordPress Classifieds Plugin](https://wordpress.org/plugins/another-wordpress-classifieds-plugin/) classified ads plugin
* [Events Manager Pro](https://eventsmanagerpro.com/) event bookings plugin

Looking for a Gravity Forms integration? Try [Gravity Forms eWAY](https://wordpress.org/plugins/gravityforms-eway/).

= Features =

* card holder's name can be different to the purchaser's name
* basic data validation performed before submitting to eWAY
* eWAY transaction ID and bank authcode are recorded for successful payments
* supports Stored Payments for drop-ship merchants / delayed billing
* supports Beagle anti-fraud measures for Direct Payments (for supporting plugins)
* drop-in compatible with eWAY payment gateway from the WP eCommerce Gold Cart plugin (except recurring billing -- see FAQ)
* it's free!

= Requirements =

* you need to install an e-commerce plugin listed above
* you need an SSL certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Direct Payments API](https://www.eway.com.au/developers/api/direct-payments) and Stored Payments API, but does not support eWAY's hosted payment form

== Installation ==

After uploading and activating this plugin, you need to configure it.

### WP eCommerce

1. Navigate to 'Settings->Store->Payments' on the menu
2. Activate the eWAY payment gateway and click the Update button
3. Edit the eWAY payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link
4. Edit the eWAY customer ID and select the appropriate settings, including which checkout fields map to eWAY fields

### WooCommerce

1. Navigate to 'WooCommerce->Settings->Payment Gateways' on the menu
2. Select eWAY from the Payment Gateways menu
3. Tick the field 'Enable/Disable' to enable the gateway
4. Edit the eWAY customer ID and select the appropriate settings

### Another WordPress Classifieds Plugin

1. Navigate to 'Classified->Settings->Payment' on the menu
2. Click the Activate eWAY checkbox
3. Edit the eWAY customer ID and select the appropriate settings

### Events Manager

1. Navigate to 'Events->Payment Gateways' on the menu
2. Click the Activate link underneath the eWAY gateway name
3. Click the Settings link underneath the eWAY gateway name
4. Edit the eWAY customer ID and select the appropriate settings

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

== Frequently Asked Questions ==

= What is eWAY? =

eWAY is a leading provider of online payments solutions for Australia, New Zealand and the UK. This plugin integrates with the Australian Direct Payments and Stored Payments gateways, so that your website can safely accept credit card payments.

= Is recurring billing supported? =

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it.

If you just need a simple way to record recurring payments such as donations, you might want to try [Gravity Forms](http://webaware.com.au/get-gravity-forms) and [Gravity Forms eWAY](https://wordpress.org/plugins/gravityforms-eway/) which does support recurring payments.

= Can I use other eWAY gateways, outside of Australia? =

Not yet. There are plans to integrate eWAY's Rapid Payments API sometime in 2013, so check back in a while.

= Can I use the eWAY hosted payment form with this plugin? =

No, this plugin only supports the [Direct Payments API](https://www.eway.com.au/developers/api/direct-payments).

= What is Stored Payments? =

Like Direct Payments, the purchase information is sent to eWAY for processing, but with Stored Payments it isn't processed right away. The merchant needs to login to their eWAY Business Centre to complete each transaction. It's useful for shops that do drop-shipping and want to delay billing. Most websites should have this option set to No.

= What is Beagle? =

[Beagle](https://www.eway.com.au/developers/api/beagle-lite) is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure Beagle rules in your MYeWAY console before enabling Beagle in this plugin.

**NB**: You will also need to add a Country field to your checkout form. Beagle works by comparing the country of the address with the country where the purchaser is using the Internet; Beagle won't be used when checking out without a country selected.

**NB**: Beagle isn't available for Another WordPress Classifieds Plugin due to the way that plugin collects billing information.

= Where do I find the eWAY transaction number? =

* **WP eCommerce**: the eWAY transaction number and the bank authcode are shown under Billing Details when you view the sales log for a purchase in the WordPress admin.
* **WooCommerce**: the eWAY transaction number and the bank authcode are shown in the Custom Fields block when you view the order in the WordPress admin.
* **Events Manager**: from the Payment Gateways menu item or the Bookings menu item, you can view a list of transactions; the eWAY transaction ID is shown in the Transaction ID column, and the authcode in the Notes column.
* **Another WordPress Classifieds Plugin**: not available in v2.x of the plugin.

= Can I use this plugin with the WP eCommerce Gold Cart? =

Yes, if you deactivate the Gold Cart's eWAY payment gateway and activate this one. The settings from the Gold Cart payment gateway will be picked up by this gateway automatically (they are stored in the same places).

= I get an SSL error when my checkout attempts to connect with eWAY =

This is a common problem in local testing environments. Please [read this post](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* libxml
* XMLWriter
* SimpleXML

== Contributions ==

* [Fork me on GitHub](https://github.com/webaware/eway-payment-gateway/)

== Filter hooks ==

Developers can use these filter hooks to modify some eWAY invoice properties. Each filter receives a string for the field value.

### WP eCommerce

* `wpsc_merchant_eway_invoice_desc` for modifying the invoice description
* `wpsc_merchant_eway_invoice_ref` for modifying the invoice reference
* `wpsc_merchant_eway_option1` for setting the option1 field
* `wpsc_merchant_eway_option2` for setting the option2 field
* `wpsc_merchant_eway_option3` for setting the option3 field
* `wpsc_merchant_eway_customer_id` for modifying the eWAY customer ID used in transaction

### WooCommerce

* `woocommerce_eway_invoice_desc` for modifying the invoice description
* `woocommerce_eway_invoice_ref` for modifying the invoice reference
* `woocommerce_eway_option1` for setting the option1 field
* `woocommerce_eway_option2` for setting the option2 field
* `woocommerce_eway_option3` for setting the option3 field
* `woocommerce_eway_icon` for changing the payment gateway icon
* `woocommerce_eway_customer_id` for modifying the eWAY customer ID used in transaction

### Another WordPress Classifieds Plugin

* `awpcp_eway_invoice_desc` for modifying the invoice description
* `awpcp_eway_invoice_ref` for modifying the invoice reference
* `awpcp_eway_option1` for setting the option1 field
* `awpcp_eway_option2` for setting the option2 field
* `awpcp_eway_option3` for setting the option3 field
* `awpcp_eway_icon` for changing the payment gateway icon
* `awpcp_eway_checkout_message` for changing the message above the checkout form
* `awpcp_eway_customer_id` for modifying the eWAY customer ID used in transaction

### Events Manager

* `em_eway_invoice_desc` for modifying the invoice description
* `em_eway_invoice_ref` for modifying the invoice reference
* `em_eway_option1` for setting the option1 field
* `em_eway_option2` for setting the option2 field
* `em_eway_option3` for setting the option3 field
* `em_eway_amount` for changing the billed amount (e.g. adding fees)
* `em_eway_customer_id` for modifying the eWAY customer ID used in transaction

== Sponsorships ==

* Another WordPress Classifieds Plugin integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)
* Events Manager Pro integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)

Thanks for sponsoring new features for eWAY Payment Gateway!

== Screenshots ==

1. WP eCommerce payments settings
2. WP eCommerce Sales Log with transaction ID and authcode
3. WooCommerce payments settings
4. WooCommerce order details with transaction ID and authcode
5. Events Manager payments settings
6. Events Manager transactions with transaction ID and authcode
7. Another WordPress Classifieds Plugin payments settings

== Upgrade Notice ==

= 3.5.0 =

Events Manager card validation fixes; AWPCP enhancements

== Changelog ==

The full changelog can be found [on GitHub](https://github.com/webaware/eway-payment-gateway/blob/master/changelog.md). Recent entries:

### 3.5.0, 2015-10-10

* fixed: PHP warning when save Events Manager settings
* fixed: pre-transaction validation for Events Manager was being skipped
* added: eWAY site seal support for AWPCP
* added: custom payment method icon setting for AWPCP
* added: filter `awpcp_eway_checkout_message` for changing the message above the checkout form in AWPCP
* added: filters for modifying the eWAY customer ID used in transaction
* changed: try harder to fill eWAY transaction contact details in AWPCP

### 3.4.0, 2015-06-21

* fixed: WP eCommerce sometimes loses the transaction authcode on sites with an object cache (like memcached)
* added: some precautionary XSS prevention
* changed: credit card fields all now have autocomplete disabled, for better card security
* changed: WooCommerce customer reference now accepts the filtered order number (`$order->get_order_number()`)
* changed: some code refactoring for easier maintenance

### 3.3.0, 2014-11-07

* fixed: force Events Manager bookings form AJAX url and form action to use HTTPS if forcing SSL for events with bookings
* fixed: WooCommerce 2.0.20 settings backwards compatibility
* added: eWAY site seal support for WooCommerce
* changed: updated screenshots
