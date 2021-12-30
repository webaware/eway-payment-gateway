# Eway Payment Gateway
Contributors: webaware
Plugin Name: Eway Payment Gateway
Plugin URI: https://shop.webaware.com.au/downloads/eway-payment-gateway/
Author URI: https://shop.webaware.com.au/
Donate link: https://shop.webaware.com.au/donations/?donation_for=Eway+Payment+Gateway
Tags: eway, payment, credit cards, woocommerce, wp e-commerce, event espresso, events manager, awpcp
Requires at least: 4.9
Tested up to: 5.8
Requires PHP: 7.4
Stable tag: 4.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take credit card payments via Eway in some popular WordPress plugins

## Description

The Eway Payment Gateway adds integrations for the [Eway credit card payment gateway](https://eway.io/) through [Rapid API Direct Payments](https://www.eway.com.au/features/api-rapid-api/). These plugins are supported:

* [WP eCommerce](https://wordpress.org/plugins/wp-e-commerce/) shopping cart plugin
* [WooCommerce](https://wordpress.org/plugins/woocommerce/) shopping cart plugin
* [WordPress Classifieds Plugin](https://wordpress.org/plugins/another-wordpress-classifieds-plugin/) classified ads plugin
* [Event Espresso 4](https://wordpress.org/plugins/event-espresso-decaf/)
* [Events Manager Pro](https://eventsmanagerpro.com/) event bookings plugin

Looking for a Gravity Forms integration? Try [Gravity Forms Eway](https://gfeway.webaware.net.au/).

### Features

* card holder's name can be different to the purchaser's name
* basic data validation performed before submitting to Eway
* Eway transaction ID and bank authcode are recorded for successful payments
* supports Authorize (PreAuth) for drop-ship merchants / delayed billing
* supports Beagle anti-fraud measures (for supporting plugins)
* it's free!

### Requirements

* you need to install one of the ecommerce plugins listed above
* you need an SSL/TLS certificate for your hosting account
* you need an account with Eway Australia
* this plugin uses Eway's [Rapid API Direct Payments](https://www.eway.com.au/features/api-rapid-api/), and does not support Eway's Responsive Shared Page

### Translations

Many thanks to the generous efforts of our translators:

* English (en_GB) -- [the English (British) translation team](https://translate.wordpress.org/locale/en-gb/default/wp-plugins/eway-payment-gateway)

If you'd like to help out by translating this plugin, please [sign up for an account and dig in](https://translate.wordpress.org/projects/wp-plugins/eway-payment-gateway).

### Sponsorships

* Another WordPress Classifieds Plugin integration generously sponsored by [Michael Major Media](https://michaelmajor.com.au/)
* Events Manager Pro integration generously sponsored by [Michael Major Media](https://michaelmajor.com.au/)
* Event Espresso 4 integration generously sponsored by [Rural Aid](https://www.ruralaid.org.au/)

Thanks for sponsoring new features for Eway Payment Gateway!

### Privacy

Information gathered for processing a credit card transaction is transmitted to Eway for processing, and in turn, Eway passes that information on to your bank. Please review [Eway's Privacy Policy](https://www.eway.com.au/legal#privacy) for information about how that affects your website's privacy policy. By using this plugin, you are agreeing to the terms of use for Eway.

## Frequently Asked Questions

### Configuring for WP eCommerce

1. Navigate to 'Settings > Store > Payments' on the menu
2. Activate the Eway payment gateway and click the Update button
3. Edit the Eway payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site, including which checkout fields map to Eway fields

### Configuring for WooCommerce

1. Navigate to 'WooCommerce > Settings > Payment Gateways' on the menu
2. Select Eway from the Payment Gateways menu
3. Tick the field 'Enable/Disable' to enable the gateway
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site

### Configuring for Another WordPress Classifieds Plugin

1. Navigate to 'Classified > Settings > Payment' on the menu
2. Click the Activate Eway checkbox
3. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
4. Select the appropriate settings for your site

### Configuring for Event Espresso

1. Navigate to 'Event Espresso > Payment Methods' on the menu
2. Select Eway from the Payment Methods menu
3. Click the Activate Eway Payments button
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site

### Configuring for Events Manager

1. Navigate to 'Events > Payment Gateways' on the menu
2. Click the Activate link underneath the Eway gateway name
3. Click the Settings link underneath the Eway gateway name
4. Enter your Rapid API key/password and Client Side Encryption keys for your live site and the sandbox
5. Select the appropriate settings for your site

### How do I test payments with Eway?

You should always test your payments first in the Eway sandbox. You will need to sign up for a sandbox account, and copy your Rapid API key/password and Client Side Encryption key from the sandbox MyEway. When you go to pay, only use dummy card numbers like 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

* [What is the sandbox and how do I get it?](https://go.eway.io/s/article/What-is-the-Sandbox-and-how-do-I-get-it)
* [Test Credit Card Numbers](https://go.eway.io/s/article/Test-Credit-Card-Numbers)

### What is Eway?

Eway is a leading provider of online payments solutions with a presence in Australia, New Zealand, and Asia. This plugin integrates with Eway so that your website can safely accept credit card payments.

### Is recurring billing supported?

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it.

If you just need a simple way to record recurring payments such as donations, you might want to try [Gravity Forms](https://webaware.com.au/get-gravity-forms) and [Gravity Forms Eway](https://gfeway.webaware.net.au/) which does support recurring payments.

### Do I need an SSL/TLS certificate for my website?

Yes. This plugin uses the Direction Connection method to process transactions, so you must have HTTPS encryption for your website.

### What's the difference between the Capture and Authorize payment methods?

Capture charges the customer's credit card immediately. This is the default payment method, and is the method most websites will use for credit card payments.

Authorize checks to see that the transaction would be approved, but does not process it. Eway calls this method [PreAuth](https://www.eway.com.au/features/payments/payments-pre-auth/). Once the transaction has been authorized, you can complete it manually in your MyEway console. You cannot complete PreAuth transactions from WordPress.

**NB: PreAuth is currently only available for Australian, Singapore, Malaysian, & Hong Kong merchants. Do not select Authorize if you are a New Zealand merchant!**

### Do I need to set the Client-Side Encryption Key?

Client-Side Encryption is required for websites that are not PCI certified. It encrypts sensitive credit card details in the browser, so that only Eway can see them. All websites are encouraged to set the Client-Side Encryption Key for improved security of credit card details.

If you get the following error, you *must* add your Client-Side Encryption key:

> V6111: Unauthorized API Access, Account Not PCI Certified

You will find your Client-Side Encryption key in MyEway where you created your API key and password. Copy it from MyEway and paste into the Eway Payments settings page.

### Why do I get an error "Invalid TransactionType"?

> V6010: Invalid TransactionType, account not certified for eCome only MOTO or Recurring available

It probably means you need to set your Client-Side Encryption key; see above. It can also indicate that your website has JavaScript errors, which can prevent Client-Side Encryption from working. Check for errors in your browser's developer console.

If your website is PCI Certified and you don't want to use Client-Side Encryption for some reason, then you will still get this error in the sandbox until you enable PCI for Direct Connections in MyEway:

Settings > Sandbox > Direction Connection > PCI

### What is Beagle Lite?

[Beagle Lite](https://www.eway.com.au/features/fraud-protection/fraud-lite/) is a service from Eway that provides fraud protection for your transactions. It uses information about the purchaser to suggest whether there is a risk of fraud. Configure Beagle Lite rules in your MyEway console.

**NB**: Beagle Lite fraud detection requires an address for each transaction. Be sure to add an Address field to your forms, and make it a required field. The minimum address part required is the Country, so you can just enable that subfield if you don't need a full address.

### Where do I find the Eway transaction number?

* **WP eCommerce**: the Eway transaction number and the bank authcode are shown under Billing Details when you view the sales log for a purchase in the WordPress admin.
* **WooCommerce**: the Eway transaction number and the bank authcode are shown in the Custom Fields block when you view the order in the WordPress admin.
* **Event Espresso**: the Eway transaction number and the bank authcode are shown in the Payment Details block when you view the transaction in the WordPress admin.
* **Events Manager**: from the Payment Gateways menu item or the Bookings menu item, you can view a list of transactions; the Eway transaction ID is shown in the Transaction ID column, and the authcode in the Notes column.
* **Another WordPress Classifieds Plugin**: not available yet

### Can I use this plugin with the WP eCommerce Gold Cart?

Yes, if you deactivate the Gold Cart's Eway payment gateway and activate this one.

### I get an SSL error when my checkout attempts to connect with Eway

This is a common problem in local testing environments. Please [read this post](https://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/) for more information.

### Can I use this plugin on any shared-hosting environment?

The plugin will run in shared hosting environments, but requires PHP 7.4 or later.

### WP eCommerce filter hooks

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.WPeCommerce.php) for filter hook parameters.

* `wpsc_merchant_eway_invoice_desc` for modifying the invoice description
* `wpsc_merchant_eway_invoice_ref` for modifying the invoice reference
* `wpsc_eway_credentials` for modifying the Eway credentials used in the transaction

### WooCommerce filter hooks

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.WooCommerce.php) for filter hook parameters.

* `woocommerce_eway_invoice_desc` for modifying the invoice description
* `woocommerce_eway_invoice_ref` for modifying the invoice reference
* `woocommerce_eway_icon` for changing the payment gateway icon
* `woocommerce_eway_credentials` for modifying the Eway credentials used in the transaction

### Another WordPress Classifieds Plugin filter hooks

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.AWPCP.php) for filter hook parameters.

* `awpcp_eway_invoice_desc` for modifying the invoice description
* `awpcp_eway_invoice_ref` for modifying the invoice reference
* `awpcp_eway_icon` for changing the payment gateway icon
* `awpcp_eway_checkout_message` for changing the message above the checkout form
* `awpcp_eway_credentials` for modifying the Eway credentials used in the transaction

### Events Manager filter hooks

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/class.EventsManager.php) for filter hook parameters.

* `em_eway_invoice_desc` for modifying the invoice description
* `em_eway_invoice_ref` for modifying the invoice reference
* `em_eway_amount` for changing the billed amount (e.g. adding fees)
* `em_eway_credentials` for modifying the Eway credentials used in the transaction

### Event Espresso 4 filter hooks

Developers can [refer to the code](https://github.com/webaware/eway-payment-gateway/blob/master/includes/integrations/event_espresso_eway/class.Gateway.php) for filter hook parameters.

* `event_espresso_eway_invoice_desc` for modifying the invoice description
* `event_espresso_eway_invoice_ref` for modifying the invoice reference

## Screenshots

1. WP eCommerce payments settings
2. WP eCommerce Sales Log with transaction ID and authcode
3. WooCommerce payments settings
4. WooCommerce order details with transaction ID and authcode
5. Events Manager payments settings
6. Events Manager transactions with transaction ID and authcode
7. Another WordPress Classifieds Plugin payments settings
8. Event Espresso 4 payments settings

## Upgrade Notice

### 4.5.0

updated eWAY to Eway and replaced logo images for new Eway branding; tested up to WooCommerce 5.9

## Changelog

[The full changelog can be found on GitHub](https://github.com/webaware/eway-payment-gateway/blob/master/changelog.md). Recent entries:

### 4.5.0

Released 2021-11-14

* changed: update eWAY to Eway and replaced logo images for new Eway branding
* changed: marked as tested up to WooCommerce 5.9
