<?php
/*
Plugin Name: eWAY Payment Gateway
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/eway-payment-gateway/
Description: Add a credit card payment gateway for eWAY (Australia) to some popular WordPress plugins
Version: 3.1.3
Author: WebAware
Author URI: http://www.webaware.com.au/
*/

/*
copyright (c) 2011-2014 WebAware Pty Ltd (email : rmckay@webaware.com.au)

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

if (!defined('EWAY_PAYMENTS_PLUGIN_ROOT')) {
	define('EWAY_PAYMENTS_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('EWAY_PAYMENTS_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));

	// special test customer ID for sandbox
	define('EWAY_PAYMENTS_TEST_CUSTOMER', '87654321');

	// wp-e-commerce gateway name
	define('EWAY_PAYMENTS_WPSC_NAME', 'wpsc_merchant_eway');
}

/**
* autoload classes as/when needed
* @param string $class_name name of class to attempt to load
*/
function eway_payments_autoload($class_name) {
	static $classMapPlugin = array (
		// application classes
		'EwayPaymentsPlugin'				=> 'class.EwayPaymentsPlugin.php',
		'EwayPaymentsPayment'				=> 'class.EwayPaymentsPayment.php',
		'EwayPaymentsStoredPayment'			=> 'class.EwayPaymentsStoredPayment.php',

		// integrations
		'EwayPaymentsAWPCP'					=> 'class.EwayPaymentsAWPCP.php',
		'EwayPaymentsAWPCP3'				=> 'class.EwayPaymentsAWPCP3.php',
		'EwayPaymentsEventsManager'			=> 'class.EwayPaymentsEventsManager.php',
		'EwayPaymentsWoo'					=> 'class.EwayPaymentsWoo.php',
		'EwayPaymentsWpsc'					=> 'class.EwayPaymentsWpsc.php',
	);

	if (isset($classMapPlugin[$class_name])) {
		require EWAY_PAYMENTS_PLUGIN_ROOT . $classMapPlugin[$class_name];
	}
}
spl_autoload_register('eway_payments_autoload');

/**
* custom exceptons
*/
class EwayPaymentsException extends Exception {}

// initialise plugin
EwayPaymentsPlugin::getInstance();
