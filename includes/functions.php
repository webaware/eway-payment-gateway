<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

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
* get a list of options for credit card Month dropdown list
* @param string $current_month
* @return string
*/
function get_month_options($current_month = '') {
	ob_start();

	foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
		printf('<option value="%1$s"%2$s>%1$s</option>', $month, selected($month, $current_month, false));
	}

	return ob_get_clean();
}

/**
* get a list of options for credit card Year dropdown list
* @param string $current_year
* @return string
*/
function get_year_options($current_year = '') {
	ob_start();

	$thisYear = (int) date('Y');
	foreach (range($thisYear, $thisYear + 15) as $year) {
		printf('<option value="%1$s"%2$s>%1$s</option>', $year, selected($year, $current_year, false));
	}

	return ob_get_clean();
}

/**
* get API wrapper, based on available credentials and settings
* @param array $creds
* @param bool $capture
* @param bool $useSandbox
* @return EwayRapidAPI|EwayLegacyAPI|EwayLegacyStoredAPI
*/
function get_api_wrapper($creds, $capture, $useSandbox) {
	if (!empty($creds['api_key']) && !empty($creds['password'])) {
		$eway = new EwayRapidAPI($creds['api_key'], $creds['password'], $useSandbox);
		$eway->capture = $capture;
	}
	elseif (!empty($creds['customerid'])) {
		if ($capture) {
			$eway = new EwayLegacyAPI($creds['customerid'], !$useSandbox);
		}
		else {
			$eway = new EwayLegacyStoredAPI($creds['customerid'], !$useSandbox);
		}
	}
	else {
		$eway = false;
	}

	return $eway;
}

/**
* get the customer's IP address dynamically from server variables
* @param bool $isLiveSite
* @return string
*/
function get_customer_IP($isLiveSite) {
	$ip = '';

	if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = is_IP_address($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
	}

	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$proxies = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip = trim(current($proxies));
		$ip = is_IP_address($ip) ? $ip : '';
	}

	elseif (!empty($_SERVER['REMOTE_ADDR'])) {
		$ip = is_IP_address($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	}

	// if test mode and running on localhost, then kludge to an Aussie IP address
	if ($ip === '127.0.0.1' && !$isLiveSite) {
		$ip = '103.29.100.101';
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
	// check for IPv4 and IPv6 addresses
	return !!inet_pton($maybeIP);
}

/**
* maybe show notice of minimum WooCommerce version failure
*/
function notice_woocommerce_version() {
	if (eway_payment_gateway_can_show_admin_notices()) {
		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-woocommerce-version.php';
	}
}
