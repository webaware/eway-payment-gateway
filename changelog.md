# eWAY Payment Gateway

## Changelog

### 3.5.1, soon...

* changed: remove dependency on WP eCommerce deprecated function for checkout field list

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

### 3.2.0, 2014-06-28

* fixed: WooCommerce 2.1 error messages use `wc_add_notice()` (fixes deprecated notice)
* fixed: Events Manager bookings admin was asking for credit card details when modifying a booking
* fixed: Another WordPress Classified Plugin hooks now pass `$transaction` as second argument
* fixed: undefined variable errors with Another WordPress Classified Plugin integration
* added: Events Manager bookings pages can be forced to SSL (new setting, defaults to Yes, can be turned off)
* added: optional credit card fields message, e.g. so can advise "Visa and Mastercard only" etc.
* changed: use standard WooCommerce credit card fields if setting selected (new default); old template is still available for sites that require it
* changed: some code refactoring

### 3.1.4, 2014-02-12

* fixed: WooCommerce 2.1 return page after checkout

### 3.1.3, 2014-01-12

* fixed: no function set_error_message() in EwayPaymentsEventsManager (bad copypasta in exception handler; thanks, [digitalblanket](https://profiles.wordpress.org/digitalblanket)!)
* fixed: was triggering an exception when Country field removed from Events Manager Pro bookings form

### 3.1.2, 2014-01-03

* changed: credit card field now forces digits only so that number keyboard is used on iPad/iPhone
* added: filter `em_eway_amount` for changing the booking amount, e.g. adding fees

### 3.1.1, 2013-12-10

* fixed: doco / settings page didn't explain that Beagle requires an Address field
* changed: permit card numbers with spaces / dashes, but strip before submitting to eWAY
* changed: move some WooCommerce setting descriptions into tips to reduce screen clutter in admin
* added: HTML5 text field patterns for credit card number, CVV/CVN
* added: filter `woocommerce_eway_icon` for changing the payment gateway icon

### 3.1.0, 2013-11-21

* changed: support multiple bookings mode in Events Manager
* changed: some links to eWAY website
* added: support for v3 of Another WordPress Classifieds Plugin
* added: WooCommerce and WP eCommerce payment method logos

### 3.0.1, 2013-03-07

* changed: update for WooCommerce v2.0.x compatibility

### 3.0.0, 2013-03-01

* added: WooCommerce integration
* added: Another WordPress Classifieds Plugin integration (sponsored by [Michael Major Media](http://michaelmajor.com.au/) -- thanks!)
* added: Events Manager integration (sponsored by [Michael Major Media](http://michaelmajor.com.au/) -- thanks!)
* changed: use WP eCommerce 2.8.9+ hooks and functions
* changed: refactored for greater generalisation

### 2.4.0, 2013-01-23

* fixed: declined payments now record status as Payment Declined instead of Incomplete Sale
* added: record authcode for transactions, and show in Sales Log
* added: send WP eCommerce transaction number as both customer reference and invoice reference (customer reference can be filtered)
* added: support for [Beagle (free)](https://www.eway.com.au/developers/api/beagle-lite) anti-fraud using geo-IP (Direct Payments only)

### 2.3.1, 2013-01-20

* fixed: close table cell elements in form field template

### 2.3.0, 2013-01-20

* fixed: successful Direct transactions are now marked as Accepted Payment, not Order Received
* added: eWAY credit card form fields now in template, can be customised by theme
* added: can now use eWAY Stored Payments, e.g. for merchants who do drop-shipping
* changed: use WordPress function wp_remote_post() instead of directly calling curl functions

### 2.2.1, 2012-10-22

* fixed: address on eWAY invoice was getting "0, " prepended when PHP < 5.3

### 2.2.0, 2012-09-25

* fixed: country name used in eWAY address field, not country code
* changed: remote SSL certificate is verified (i.e. eWAY's certificate)
* added: prevent XML injection attacks when loading eWAY response (security hardening)
* added: filter hooks for invoice description and reference
* added: if customer name isn't mandatory and not given, will use cardholder's name

### 2.1.0, 2012-07-03

* changed: CVN is now a required field, no option to omit; Australian banks are all moving to require CVN and some already do
* added: customer name is now supported, if configured in WP eCommerce payment admin; card holder name is not seen in eWAY notification emails, so customer name is required for showing who made the purchase

### 2.0.4, 2012-05-13

* fixed: invoice number recorded in eWAY invoice reference field

### 2.0.3, 2012-05-05

* fixed: optional fields for address, email are no longer required for eWAY payment

### 2.0.2, 2012-04-16

* fixed: undeclared array index errors

### 2.0.1, 2012-04-12

* fixed: admin transposed Use Testing Environment and Use CVN Security

### 2.0.0, 2012-04-08

* final cleanup and refactor for public release

### 1.0.0, 2011-09-15

* private version, not released to public
