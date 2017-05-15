=== eWAY Payment Gateway ===
Contributors: webaware
Plugin Name: eWAY Payment Gateway
Plugin URI: https://shop.webaware.com.au/downloads/eway-payment-gateway/
Author URI: https://shop.webaware.com.au/
Donate link: https://shop.webaware.com.au/donations/?donation_for=eWAY+Payment+Gateway
Tags: eway, payment, credit cards, woocommerce, wp e-commerce, events manager, awpcp
Requires at least: 4.2
Tested up to: 4.7
Stable tag: 4.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate some popular WordPress plugins with the eWAY credit card payment gateway

== Description ==

The eWAY Payment Gateway adds integrations for the [eWAY credit card payment gateway](https://eway.io/) through [Rapid API Direct Payments](https://eway.io/features/api-rapid-api), with legacy XML API support. These plugins are supported:

* [WP eCommerce](https://wordpress.org/plugins/wp-e-commerce/) shopping cart plugin
* [WooCommerce](https://wordpress.org/plugins/woocommerce/) shopping cart plugin
* [Another WordPress Classifieds Plugin](https://wordpress.org/plugins/another-wordpress-classifieds-plugin/) classified ads plugin
* [Events Manager Pro](https://eventsmanagerpro.com/) event bookings plugin

Looking for a Gravity Forms integration? Try [Gravity Forms eWAY](https://gfeway.webaware.net.au/).

= Features =

* card holder's name can be different to the purchaser's name
* basic data validation performed before submitting to eWAY
* eWAY transaction ID and bank authcode are recorded for successful payments
* supports Authorize (PreAuth) for drop-ship merchants / delayed billing
* supports Beagle anti-fraud measures (for supporting plugins)
* it's free!

= Requirements =

* you need to install one of the ecommerce plugins listed above
* you need an SSL/TLS certificate for your hosting account
* you need an account with eWAY Australia
* this plugin uses eWAY's [Rapid API Direct Payments](https://eway.io/features/api-rapid-api), and does not support eWAY's Responsive Shared Page

= Translations =

If you'd like to help out by translating this plugin, please [sign up for an account and dig in](https://translate.wordpress.org/projects/wp-plugins/eway-payment-gateway).

= Contributions =

* [Fork me on GitHub](https://github.com/webaware/eway-payment-gateway/)

= Sponsorships =

* Another WordPress Classifieds Plugin integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)
* Events Manager Pro integration generously sponsored by [Michael Major Media](http://michaelmajor.com.au/)

Thanks for sponsoring new features for eWAY Payment Gateway!

== Frequently Asked Questions ==

= Configuring for WP eCommerce =

1. Navigate to 'Settings > Store > Payments' on the menu
2. Activate the eWAY payment gateway and click the Update button
3. Edit the eWAY payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site, including which checkout fields map to eWAY fields

= Configuring for WooCommerce =

1. Navigate to 'WooCommerce > Settings > Payment Gateways' on the menu
2. Select eWAY from the Payment Gateways menu
3. Tick the field 'Enable/Disable' to enable the gateway
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site

= Configuring for Another WordPress Classifieds Plugin =

1. Navigate to 'Classified > Settings > Payment' on the menu
2. Click the Activate eWAY checkbox
3. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
4. Select the appropriate settings for your site

= Configuring for Events Manager =

1. Navigate to 'Events > Payment Gateways' on the menu
2. Click the Activate link underneath the eWAY gateway name
3. Click the Settings link underneath the eWAY gateway name
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site

= How do I test payments with eWAY? =

You should always test your payments first in the eWAY sandbox. You will need to sign up for a sandbox account, and copy your Rapid API key/password and Client Side Encryption key from the sandbox MYeWAY. When you go to pay, only use dummy card numbers like 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

* [What is the sandbox and how do I get it?](https://go.eway.io/s/article/ka828000000L1ZTAA0/What-is-the-Sandbox-and-how-do-I-get-it)
* [Test Credit Card Numbers](https://go.eway.io/s/article/ka828000000L1PdAAK/Test-Credit-Card-Numbers)

= What is eWAY? =

eWAY is a leading provider of online payments solutions with a presence in Australia, New Zealand, and Asia. This plugin integrates with eWAY so that your website can safely accept credit card payments.

= Is recurring billing supported? =

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it.

If you just need a simple way to record recurring payments such as donations, you might want to try [Gravity Forms](https://webaware.com.au/get-gravity-forms) and [Gravity Forms eWAY](https://gfeway.webaware.net.au/) which does support recurring payments.

= Do I need an SSL/TLS certificate for my website? =

Yes. This plugin uses the Direction Connection method to process transactions, so you must have HTTPS encryption for your website.

= What's the difference between the Capture and Authorize payment methods? =

Capture charges the customer's credit card immediately. This is the default payment method, and is the method most websites will use for credit card payments.

Authorize checks to see that the transaction would be approved, but does not process it. eWAY calls this method [PreAuth](https://eway.io/features/payments-pre-auth) (or Stored Payments in the old XML API). Once the transaction has been authorized, you can complete it manually in your MYeWAY console. You cannot complete PreAuth transactions from WordPress.

You need to add your eWAY API key and password to see PreAuth transactions in the sandbox, so that the Rapid API is used. The old Stored Payments XML API does not have a sandbox.

= Do I need to set the Client-Side Encryption Key? =

Client-Side Encryption is required for websites that are not PCI certified. It encrypts sensitive credit card details in the browser, so that only eWAY can see them. All websites are encouraged to set the Client-Side Encryption Key for improved security of credit card details.

If you get the following error, you *must* add your Client-Side Encryption key:

> V6111: Unauthorized API Access, Account Not PCI Certified

You will find your Client-Side Encryption key in MYeWAY where you created your API key and password. Copy it from MYeWAY and paste into the eWAY Payments settings page.

= Why do I get an error "Invalid TransactionType"? =

> V6010: Invalid TransactionType, account not certified for eCome only MOTO or Recurring available

It probably means you need to set your Client-Side Encryption key; see above. It can also indicate that your website has JavaScript errors, which can prevent Client-Side Encryption from working. Check for errors in your browser's developer console.

If your website is PCI Certified and you don't want to use Client-Side Encryption for some reason, then you will still get this error in the sandbox until you enable PCI for Direct Connections in MYeWAY:

Settings > Sandbox > Direction Connection > PCI

= What is Beagle Lite? =

[Beagle Lite](https://eway.io/features/antifraud-beagle-lite) is a service from eWAY that provides fraud protection for your transactions. It uses information about the purchaser to suggest whether there is a risk of fraud. Configure Beagle Lite rules in your MYeWAY console.

**NB**: Beagle Lite fraud detection requires an address for each transaction. Be sure to add an Address field to your forms, and make it a required field. The minimum address part required is the Country, so you can just enable that subfield if you don't need a full address.

= Where do I find the eWAY transaction number? =

* **WP eCommerce**: the eWAY transaction number and the bank authcode are shown under Billing Details when you view the sales log for a purchase in the WordPress admin.
* **WooCommerce**: the eWAY transaction number and the bank authcode are shown in the Custom Fields block when you view the order in the WordPress admin.
* **Events Manager**: from the Payment Gateways menu item or the Bookings menu item, you can view a list of transactions; the eWAY transaction ID is shown in the Transaction ID column, and the authcode in the Notes column.
* **Another WordPress Classifieds Plugin**: not available yet

= Can I use this plugin with the WP eCommerce Gold Cart? =

Yes, if you deactivate the Gold Cart's eWAY payment gateway and activate this one.

= I get an SSL error when my checkout attempts to connect with eWAY =

This is a common problem in local testing environments. Please [read this post](https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* libxml
* XMLWriter
* SimpleXML

= WP eCommerce filter hooks =

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.EwayPaymentsWpsc.php) for filter hook parameters.

* `wpsc_merchant_eway_invoice_desc` for modifying the invoice description
* `wpsc_merchant_eway_invoice_ref` for modifying the invoice reference
* `wpsc_merchant_eway_option1` for setting the option1 field
* `wpsc_merchant_eway_option2` for setting the option2 field
* `wpsc_merchant_eway_option3` for setting the option3 field
* `wpsc_eway_credentials` for modifying the eWAY credentials used in the transaction

= WooCommerce filter hooks =

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.EwayPaymentsWoo.php) for filter hook parameters.

* `woocommerce_eway_invoice_desc` for modifying the invoice description
* `woocommerce_eway_invoice_ref` for modifying the invoice reference
* `woocommerce_eway_option1` for setting the option1 field
* `woocommerce_eway_option2` for setting the option2 field
* `woocommerce_eway_option3` for setting the option3 field
* `woocommerce_eway_icon` for changing the payment gateway icon
* `woocommerce_eway_credentials` for modifying the eWAY credentials used in the transaction

= Another WordPress Classifieds Plugin filter hooks =

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.EwayPaymentsAWPCP3.php) for filter hook parameters.

* `awpcp_eway_invoice_desc` for modifying the invoice description
* `awpcp_eway_invoice_ref` for modifying the invoice reference
* `awpcp_eway_option1` for setting the option1 field
* `awpcp_eway_option2` for setting the option2 field
* `awpcp_eway_option3` for setting the option3 field
* `awpcp_eway_icon` for changing the payment gateway icon
* `awpcp_eway_checkout_message` for changing the message above the checkout form
* `awpcp_eway_credentials` for modifying the eWAY credentials used in the transaction

= Events Manager filter hooks =

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.EwayPaymentsEventsManager.php) for filter hook parameters.

* `em_eway_invoice_desc` for modifying the invoice description
* `em_eway_invoice_ref` for modifying the invoice reference
* `em_eway_option1` for setting the option1 field
* `em_eway_option2` for setting the option2 field
* `em_eway_option3` for setting the option3 field
* `em_eway_amount` for changing the billed amount (e.g. adding fees)
* `em_eway_credentials` for modifying the eWAY credentials used in the transaction

== Screenshots ==

1. WP eCommerce payments settings
2. WP eCommerce Sales Log with transaction ID and authcode
3. WooCommerce payments settings
4. WooCommerce order details with transaction ID and authcode
5. Events Manager payments settings
6. Events Manager transactions with transaction ID and authcode
7. Another WordPress Classifieds Plugin payments settings

== Upgrade Notice ==

= 4.0.2 =

fixed WooCommerce custom credit card fields not using Client Side Encryption

== Changelog ==

The full changelog can be found [on GitHub](https://github.com/webaware/eway-payment-gateway/blob/master/changelog.md). Recent entries:

### 4.0.2, 2017-05-15

* fixed: WooCommerce custom credit card fields not using Client Side Encryption

### 4.0.1, 2017-04-19

* changed: WooCommerce 3.0 support better accommodates other plugins and their abstractions for WooCommerce 2.6
* changed: dropped support for WooCommerce 2.0 and earlier

### 4.0.0, 2017-03-13

* changed: uses eWAY Rapid API if API key and password are set
* changed: WooCommerce 3.0 compatibility, with fallback support to previous version
* changed: AWPCP minimum version now 3.0
* changed: sandbox always uses customer ID 87654321 if no Rapid API key/password are set for the sandbox
* changed: currency is no longer limited to AUD; currency always passed from shop settings
* changed: always send data for Beagle Lite and Beagle Enterprise support
* changed: improved error reporting and logging
* removed: `*_eway_customer_id` filters; these have been replaced by `*_eway_credentials` filters that accommodate Rapid API credentials
* added: capture and report HTTP errors communicating with the gateway
* added: strings are localized and ready for [translation](https://translate.wordpress.org/projects/wp-plugins/eway-payment-gateway)
