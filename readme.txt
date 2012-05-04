=== eWAY Payment Gateway ===
Contributors: webaware
Plugin Name: eWAY Payment Gateway
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/eway-payment-gateway/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CXNFEP4EAMTG6
Tags: wp e-commerce, eway
Requires at least: 3.0.1
Tested up to: 3.3.2
Stable tag: 2.0.3

Add a credit card payment gateway for eWAY to the wp-e-commerce shopping cart plugin

== Description ==

The eWAY Payment Gateway adds a credit card payment gateway for [eWAY in Australia](http://www.eway.com.au/) to the [wp-e-commerce](http://wordpress.org/extend/plugins/wp-e-commerce/) shopping cart plugin, without requiring the Gold Cart option.

**Features:**

* allows the card holder's name to be different to the purchaser's name
* performs some basic data validation before submitting to eWAY
* displays eWAY transaction ID on purchases log, for successful payments
* drop-in compatible with eWAY payment gateway from the Gold Cart plugin (except recurring billing -- see FAQ)
* it's free!

== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Activate the eWAY payment gateway through the 'Settings->Store' menu (wp-e-commerce settings)
4. Edit the eWAY payment gateway settings by hovering your mouse over the gateway's name and clicking the hidden 'edit' link

NB: you should always test your gateway first by using eWAY's test server. To do this, set your eWAY Customer ID to the special test ID 87654321 and select Use Test Environment. When you go to pay, the only card number that will be accepted by the test server is 4444333322221111. This allows you to make as many test purchases as you like, without billing a real credit card.

== Frequently Asked Questions ==

= Is recurring billing supported? =

Not yet. I know it can be done but I haven't had a website that needs it yet, so have not written the code for it. If you need recurring billing, buy the [Gold Cart plugin](http://getshopped.org/premium-upgrades/premium-plugin/gold-cart-plugin/) for wp-e-commerce.

= Can I use other eWAY gateways, outside of Australia? =

Not yet. Basically, I haven't even looked at the other eWAY gateways, so I have no idea what's involved in supporting them. I reckon I'll get around to them one day though, so check back in 2013 maybe.

= Can I use this plugin with the Gold Cart? =

I have not tried it myself, but you should be able to deactivate the Gold Cart's payment gateway and activate this one. The settings from the Gold Cart payment gateway will be picked up by this gateway automatically (they are stored in the same places). Let me know if you do, and I'll update this FAQ.

= Can I use this plugin on any shared-hosting environment? =

The plugin will run in shared hosting environments, but requires PHP 5 with the following modules enabled (talk to your host). Both are typically available because they are enabled by default in PHP 5, but may be disabled on some shared hosts.

* XMLWriter
* SimpleXML

== Changelog ==

= 2.0.3 [2012-05-05] =
* fixed: optional fields for address, email are no longer required for eway payment

= 2.0.2 [2012-04-16] =
* fixed: undeclared array index errors

= 2.0.1 [2012-04-12] =
* fixed: admin transposed Use Testing Environment and Use CVN Security

= 2.0.0 [2012-04-08] =
* final cleanup and refactor for public release

= 1.0.0 [2011-09-15] =
* private version, not released to public
