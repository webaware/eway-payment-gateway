<?php
/*
Plugin Name: eWAY Payment Gateway
Plugin URI: http://shop.webaware.com.au/downloads/eway-payment-gateway/
Description: Integrate some popular WordPress plugins with the eWAY credit card payment gateway
Version: 3.5.0
Author: WebAware
Author URI: http://webaware.com.au/
*/

/*
copyright (c) 2011-2015 WebAware Pty Ltd (email : support@webaware.com.au)

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
	define('EWAY_PAYMENTS_VERSION', '3.5.0');

	// special test customer ID for sandbox
	define('EWAY_PAYMENTS_TEST_CUSTOMER', '87654321');
}

/**
* custom exceptons
*/
class EwayPaymentsException extends Exception {}

// initialise plugin
require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/class.EwayPaymentsPlugin.php';
EwayPaymentsPlugin::getInstance();
