<?php
/*
Plugin Name: eWAY Payment Gateway
Plugin URI: http://shop.webaware.com.au/downloads/eway-payment-gateway/
Description: Integrate some popular WordPress plugins with the eWAY credit card payment gateway
Version: 3.3.0
Author: WebAware
Author URI: http://webaware.com.au/
*/

/*
copyright (c) 2011-2014 WebAware Pty Ltd (email : support@webaware.com.au)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('EWAY_PAYMENTS_PLUGIN_ROOT')) {
	define('EWAY_PAYMENTS_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('EWAY_PAYMENTS_PLUGIN_FILE', __FILE__);
	define('EWAY_PAYMENTS_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
	define('EWAY_PAYMENTS_VERSION', '3.3.0');

	// special test customer ID for sandbox
	define('EWAY_PAYMENTS_TEST_CUSTOMER', '87654321');
}

/**
* autoload classes as/when needed
* @param string $class_name name of class to attempt to load
*/
function eway_payments_autoload($class_name) {
	static $classMapPlugin = array (
		// application classes
		'EwayPaymentsPlugin'				=> 'includes/class.EwayPaymentsPlugin.php',
		'EwayPaymentsPayment'				=> 'includes/class.EwayPaymentsPayment.php',
		'EwayPaymentsStoredPayment'			=> 'includes/class.EwayPaymentsStoredPayment.php',

		// integrations
		'EwayPaymentsAWPCP'					=> 'includes/integrations/class.EwayPaymentsAWPCP.php',
		'EwayPaymentsAWPCP3'				=> 'includes/integrations/class.EwayPaymentsAWPCP3.php',
		'EwayPaymentsEventsManager'			=> 'includes/integrations/class.EwayPaymentsEventsManager.php',
		'EwayPaymentsWoo'					=> 'includes/integrations/class.EwayPaymentsWoo.php',
		'EwayPaymentsWpsc'					=> 'includes/integrations/class.EwayPaymentsWpsc.php',
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
