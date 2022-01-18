<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * load template from theme or plugin
 */
function eway_load_template(string $template, array $variables) : void {
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
 */
function get_month_options(string $current_month = '') : string {
	ob_start();

	foreach (['01','02','03','04','05','06','07','08','09','10','11','12'] as $month) {
		printf('<option value="%1$s"%2$s>%1$s</option>', $month, selected($month, $current_month, false));
	}

	return ob_get_clean();
}

/**
 * get a list of options for credit card Year dropdown list
 */
function get_year_options(string $current_year = '') : string {
	ob_start();

	$thisYear = (int) date('Y');
	foreach (range($thisYear, $thisYear + 15) as $year) {
		printf('<option value="%1$s"%2$s>%1$s</option>', $year, selected($year, $current_year, false));
	}

	return ob_get_clean();
}

/**
 * get the customer's IP address dynamically from server variables
 */
function get_customer_IP(bool $is_live_site) : string {
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
	if ($ip === '127.0.0.1' && !$is_live_site) {
		$ip = '103.29.100.101';
	}

	// allow hookers to override for network-specific fixes
	$ip = (string) apply_filters('eway_payment_customer_ip', $ip);

	return $ip;
}

/**
 * check whether a given string is an IP address
 */
function is_IP_address(string $maybeIP) : bool {
	// check for IPv4 and IPv6 addresses
	return !!inet_pton($maybeIP);
}

/**
 * maybe show notice of minimum WooCommerce version failure
 */
function notice_woocommerce_version() : void {
	if (eway_payment_gateway_can_show_admin_notices()) {
		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-woocommerce-version.php';
	}
}

/**
 * sanitise the customer title, to avoid error V6058: Invalid Customer Title
 */
function sanitise_customer_title(string $title) : string {
	$valid = [
		'mr'			=> 'Mr.',
		'master'		=> 'Mr.',
		'ms'			=> 'Ms.',
		'mrs'			=> 'Mrs.',
		'missus'		=> 'Mrs.',
		'miss'			=> 'Miss',
		'dr'			=> 'Dr.',
		'doctor'		=> 'Dr.',
		'sir'			=> 'Sir',
		'prof'			=> 'Prof.',
		'professor'		=> 'Prof.',
	];

	$simple = rtrim(strtolower(trim($title)), '.');

	return isset($valid[$simple]) ? $valid[$simple] : '';
}

/**
 * format amount per currency
 */
function format_currency(float $amount, string $currency_code) : string {
	if (currency_has_decimals($currency_code)) {
		$value = number_format($amount * 100, 0, '', '');
	}
	else {
		// currency already has no decimal fraction
		$value = number_format($amount, 0, '', '');
	}

	return $value;
}

/**
 * check for currency with decimal places (e.g. "cents")
 */
function currency_has_decimals(string $currency_code) : bool {
	$no_decimals = [
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'ISK',
		'JPY',
		'KMF',
		'KRW',
		'PYG',
		'RWF',
		'UGX',
		'UYI',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
	];
	return !in_array($currency_code, $no_decimals);
}

/**
 * distill an array of option values down to minimum set, and build an API array of options for Eway
 */
function get_api_options(array $input) : array {
	$output = [];

	if (!empty($input)) {
		foreach ($input as $option) {
			if (!empty($option)) {
				$output[] = ['Value' => substr($option, 0, 254)];
			}
		}
	}

	return $output;
}
