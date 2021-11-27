<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

// special test customer ID for sandbox
const EWAY_PAYMENTS_TEST_CUSTOMER		= '87654321';

// prerequisites
const MIN_VERSION_WOOCOMMERCE			= '3.0';

/**
* custom exceptons
*/
class EwayPaymentsException extends \Exception {}

/**
* kick start the plugin
* needs to hook at priority 0 to beat Event Espresso's load_espresso_addons()
*/
add_action('plugins_loaded', function() {
	require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/functions.php';
	require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/class.Plugin.php';
	$plugin = Plugin::getInstance();
	$plugin->pluginStart();
}, 0);

/**
* autoload classes as/when needed
* @param string $class_name name of class to attempt to load
*/
spl_autoload_register(function($class_name) {
	static $classMap = [
		'FormPost'							=> 'includes/class.FormPost.php',
		'EwayLegacyAPI'						=> 'includes/class.EwayLegacyAPI.php',
		'EwayLegacyStoredAPI'				=> 'includes/class.EwayLegacyStoredAPI.php',
		'EwayRapidAPI'						=> 'includes/class.EwayRapidAPI.php',
		'EwayResponse'						=> 'includes/class.EwayResponse.php',
		'EwayResponseDirectPayment'			=> 'includes/class.EwayResponseDirectPayment.php',
		'Logging'							=> 'includes/class.Logging.php',

		'event_espresso\\BillingInfo'		=> 'includes/integrations/event_espresso_eway/class.BillingInfo.php',
	];

	if (strpos($class_name, __NAMESPACE__) === 0) {
		$class_name = substr($class_name, strlen(__NAMESPACE__) + 1);
		if (isset($classMap[$class_name])) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . $classMap[$class_name];
		}
	}
});
