<?php
// NB: Minimum PHP version for this file is 5.3! No short array notation, no namespaces!

if (!defined('ABSPATH')) {
	exit;
}

/**
* maybe show notice of minimum PHP version failure
*/
function eway_payment_gateway_fail_php_version() {
	if (eway_payment_gateway_can_show_admin_notices()) {
		eway_payment_gateway_load_text_domain();
		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-php.php';
	}
}

/**
* test whether we can show admin-related notices
* @return bool
*/
function eway_payment_gateway_can_show_admin_notices() {
	global $pagenow, $hook_suffix;

	// only on specific pages
	$settings_pages = array(
		'settings_page_wpsc-settings',				// WP eCommerce
		'woocommerce_page_wc-settings',				// WooCommerce
		'event_page_events-manager-gateways',		// Events Manager
		'classifieds_page_awpcp-admin-settings',	// AWPCP
	);
	if ($pagenow !== 'plugins.php' && !in_array($hook_suffix, $settings_pages)) {
		return false;
	}

	// only bother admins / plugin installers / option setters with this stuff
	if (!current_user_can('activate_plugins') && !current_user_can('manage_options')) {
		return false;
	}

	return true;
}

/**
* load text translations
*/
function eway_payment_gateway_load_text_domain() {
	load_plugin_textdomain('eway-payment-gateway');
}

/**
* replace link placeholders with an external link
* @param string $template
* @param string $url
* @return string
*/
function eway_payment_gateway_external_link($template, $url) {
	$search = array(
		'{{a}}',
		'{{/a}}',
	);
	$replace = array(
		sprintf('<a rel="noopener" target="_blank" href="%s">', esc_url($url)),
		'</a>',
	);
	return str_replace($search, $replace, $template);
}
