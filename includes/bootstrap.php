<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

// special test customer ID for sandbox
const EWAY_PAYMENTS_TEST_CUSTOMER		= '87654321';

/**
* custom exceptons
*/
class EwayPaymentsException extends \Exception {}

/**
* kick start the plugin
*/
add_action('plugins_loaded', function () {
	require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/class.Plugin.php';
	$plugin = Plugin::getInstance();
	$plugin->pluginStart();
}, 5);

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

		'woocommerce\CompatibleOrder'		=> 'includes/integrations/class.WooCommerce.CompatibleOrder.php',
	];

	if (strpos($class_name, __NAMESPACE__) === 0) {
		$class_name = substr($class_name, strlen(__NAMESPACE__) + 1);
		if (isset($classMap[$class_name])) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . $classMap[$class_name];
		}
	}
});

/**
* load template from theme or plugin
* @param string $template name of template file
* @param array $variables an array of variables that should be accessible by the template
*/
function eway_load_template($template, $variables) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

	// make variables available to the template
	extract($variables);

	// can't use locate_template() because WP eCommerce is _doing_it_wrong() again!
	// (STYLESHEETPATH and TEMPLATEPATH are both undefined when this function called for wpsc)

	// check in theme / child theme folder
	$templatePath = get_stylesheet_directory() . "/$template";
	if (!file_exists($templatePath)) {
		// check in parent theme folder
		$templatePath = get_template_directory() . "/$template";
		if (!file_exists($templatePath)) {
			// not found in theme, use plugin's template
			$templatePath = EWAY_PAYMENTS_PLUGIN_ROOT . "templates/$template";
		}
	}

	require $templatePath;
}

/**
* get the customer's IP address dynamically from server variables
* @param bool $isLiveSite
* @return string
*/
function get_customer_IP($isLiveSite) {
	$ip = '';

	if (isset($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = is_IP_address($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
	}

	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$proxies = preg_split('/[,:]/', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip = trim(current($proxies));
		$ip = is_IP_address($ip) ? $ip : '';
	}

	elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = is_IP_address($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	}

	// if test mode and running on localhost, then kludge to an Aussie IP address
	if ($ip === '127.0.0.1' && !$isLiveSite) {
		$ip = '210.1.199.10';
	}

	// allow hookers to override for network-specific fixes
	$ip = apply_filters('eway_payment_customer_ip', $ip);

	return $ip;
}

/**
* check whether a given string is an IP address
* @param string $maybeIP
* @return bool
*/
function is_IP_address($maybeIP) {
	if (function_exists('inet_pton')) {
		// check for IPv4 and IPv6 addresses
		return !!inet_pton($maybeIP);
	}

	// just check for IPv4 addresses
	return !!ip2long($maybeIP);
}
