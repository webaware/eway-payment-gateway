<?php
namespace webaware\eway_payment_gateway;

use EM\Payments\Gateway_Admin;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * payment gateway admin integration for Events Manager
 */
final class MethodEventsManager_Admin extends Gateway_Admin {

	public static $gateway_class	= MethodEventsManager::class;
	public static $gateway			= 'eway';

	/**
	 * initialise admin class
	 */
	public static function init() {
		self::$gateway_class = MethodEventsManager::class;
		parent::init();
	}

	/**
	 * add custom tabs for gateway settings
	 * @param array $tabs
	 * @return array
	 */
	public static function settings_tabs($tabs = []) {
		$tabs['options'] = [
			'name'		=> esc_html(translate('Gateway Options', 'em-pro')),
			'callback'	=> [__CLASS__, 'settings_options'],
		];
		return parent::settings_tabs($tabs);
	}

	/**
	 * outputs custom settings fields
	 */
	public static function settings_options() {
		add_action('admin_print_footer_scripts', [__CLASS__, 'adminSettingsScript']);

		if (MethodEventsManager::getApiCredentials()->isMissingCredentials()) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-notice-missing-creds.php';
		}

		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-events-manager.php';
	}

	/**
	 * save custom settings
	 * @param array $options
	 * @return boolean
	 */
	public static function update($options = []) {
		$gateway = MethodEventsManager::$gateway;

		$options = array_merge([
			"em_{$gateway}_mode",
			"em_{$gateway}_api_key",
			"em_{$gateway}_password",
			"em_{$gateway}_ecrypt_key",
			"em_{$gateway}_sandbox_api_key",
			"em_{$gateway}_sandbox_password",
			"em_{$gateway}_sandbox_ecrypt_key",
			"em_{$gateway}_stored",
			"em_{$gateway}_ssl_force",
			"em_{$gateway}_logging",
			"em_{$gateway}_card_msg",
		], $options);

		// filters for specific data
		add_filter("gateway_update_em_{$gateway}_mode", 'sanitize_text_field');
		add_filter("gateway_update_em_{$gateway}_card_msg", 'sanitize_text_field');
		add_filter("gateway_update_em_{$gateway}_logging", 'sanitize_text_field');

		add_filter("gateway_update_em_{$gateway}_api_key", 'strip_tags');
		add_filter("gateway_update_em_{$gateway}_password", 'strip_tags');
		add_filter("gateway_update_em_{$gateway}_ecrypt_key", 'strip_tags');
		add_filter("gateway_update_em_{$gateway}_sandbox_api_key", 'strip_tags');
		add_filter("gateway_update_em_{$gateway}_sandbox_password", 'strip_tags');
		add_filter("gateway_update_em_{$gateway}_sandbox_ecrypt_key", 'strip_tags');

		add_filter("gateway_update_em_{$gateway}_stored", 'intval');
		add_filter("gateway_update_em_{$gateway}_ssl_force", 'intval');

		return parent::update($options);
	}

	/**
	 * add page script for admin options
	 */
	public static function adminSettingsScript() : void {
		$min	= SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "static/js/admin-events-manager-settings$min.js");
		echo '</script>';
	}

}
