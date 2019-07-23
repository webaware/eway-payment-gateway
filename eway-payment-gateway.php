<?php
/*
Plugin Name: eWAY Payment Gateway
Plugin URI: https://shop.webaware.com.au/downloads/eway-payment-gateway/
Description: Take credit card payments via eWAY in some popular WordPress plugins
Version: 4.3.4
Author: WebAware
Author URI: https://shop.webaware.com.au/
Text Domain: eway-payment-gateway
WC requires at least: 2.6
WC tested up to: 3.6
*/

/*
copyright (c) 2011-2019 WebAware Pty Ltd (email : support@webaware.com.au)

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

define('EWAY_PAYMENTS_PLUGIN_FILE', __FILE__);
define('EWAY_PAYMENTS_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('EWAY_PAYMENTS_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
define('EWAY_PAYMENTS_MIN_PHP', '5.6');
define('EWAY_PAYMENTS_VERSION', '4.3.4');

require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/functions-global.php';

if (version_compare(PHP_VERSION, EWAY_PAYMENTS_MIN_PHP, '<')) {
	add_action('admin_notices', 'eway_payment_gateway_fail_php_version');
	return;
}

require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/bootstrap.php';
