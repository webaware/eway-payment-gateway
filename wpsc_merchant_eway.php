<?php
/*
Plugin Name: eWay Payment Gateway
Description: eWay payment gateway for wp-e-commerce
Version: 2.0.0
Author: WebAware
Author URI: http://www.webaware.com.au/
*/

/*
copyright (c) 2011-2012 WebAware Pty Ltd (email : rmckay@webaware.com.au)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('WPSC_MERCH_EWAY_PLUGIN_ROOT')) {
	define('WPSC_MERCH_EWAY_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('WPSC_MERCH_EWAY_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
}

/**
* autoload classes as/when needed
* @param string $class_name name of class to attempt to load
*/
function wpsc_merchant_eway_autoload($class_name) {
	static $classMapPlugin = array (
		// application classes
		'wpsc_merchant_eway'				=> 'class.wpsc_merchant_eway.php',
		'wpsc_merchant_eway_admin'			=> 'class.wpsc_merchant_eway_admin.php',
		'wpsc_merchant_eway_payment'		=> 'class.wpsc_merchant_eway_payment.php',
		'wpsc_merchant_eway_response'		=> 'class.wpsc_merchant_eway_response.php',
	);

	if (isset($classMapPlugin[$class_name])) {
		require WPSC_MERCH_EWAY_PLUGIN_ROOT . $classMapPlugin[$class_name];
	}
}
spl_autoload_register('wpsc_merchant_eway_autoload');

// hook wp-e-commerce to extend purchase logs display
add_action('wpsc_billing_details_bottom', array('wpsc_merchant_eway_admin', 'actionBillingDetailsBottom'));

// hook for adding links to plugin info
add_filter('plugin_row_meta', array('wpsc_merchant_eway_admin', 'addPluginDetailsLinks'), 10, 2);

/**
* filter for registering new wp-e-commerce payment gateways
* @param array $gateways array of registered gateways
* @return array
*/
function wpsc_merchant_eway_register($gateways) {
	global $gateway_checkout_form_fields;

	$gateway_name = 'wpsc_merchant_eway';

	// register the gateway class and additional functions
	$gateways[] = array(
			'name' => 'eWay payment gateway',
			'api_version' => 2.0,
			'internalname' => $gateway_name,
			'class_name' => 'wpsc_merchant_eway',
			'has_recurring_billing' => FALSE,
			'wp_admin_cannot_cancel' => FALSE,
			'display_name' => 'eWay Credit Card Payment',
			'form' => 'wpsc_merchant_eway_admin_configForm',	// must call proxy to admin method due to wp-e-commerce shortcomings
			'submit_function' => array('wpsc_merchant_eway_admin', 'saveConfig'),
			'payment_type' => 'credit_card',
			'requirements' => array(
				'php_version' => 5.0,
			),
		);

	// register extra fields we require on the checkout form
	// (but only if this gateway is selected for checkout payments)
	if (in_array($gateway_name, (array) get_option('custom_gateway_options'))) {
		$gateway_checkout_form_fields[$gateway_name] = wpsc_merchant_eway::getCheckoutFields();
	}

	return $gateways;
}
add_filter('wpsc_gateway_modules', 'wpsc_merchant_eway_register');

/**
* proxy to admin method due to wp-e-commerce shortcomings
* TODO: get wp-e-commerce to use call_user_func() for this call, then can remove proxy function
*/
function wpsc_merchant_eway_admin_configForm() {
	return wpsc_merchant_eway_admin::configForm();
}
