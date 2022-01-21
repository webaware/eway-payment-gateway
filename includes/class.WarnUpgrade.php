<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * warn admins about missing prerequisites before they upgrade and lose capability
 */
final class WarnUpgrade {

	const MIN_WP			= '5.1';		// first version that prevents plugin updates on old PHP
	const MIN_PHP			= '7.4';
	const MIN_WOOCOMMERCE	= '3.0';
	const OPT_DISMISS		= 'eway_dismiss_warn_update_5';

	private $notices = [];

	/**
	 * add WordPress hooks
	 */
	public function addHooks() {
		add_action('admin_notices', [$this, 'maybeWarnUpdates']);
		add_action('wp_ajax_eway_payment_gateway_dismiss', [$this, 'dismissWarnings']);
	}

	/**
	 * maybe warn admins about update prerequisites not met
	 */
	public function maybeWarnUpdates() {
		if (get_option(self::OPT_DISMISS)) {
			return;
		}

		if (!$this->canShowAdminNotices()) {
			return;
		}

		// check WordPress version
		$wp_ver = get_bloginfo('version');
		if (version_compare($wp_ver, self::MIN_WP, '<')) {
			$this->notices[] = sprintf(esc_html__('It requires WordPress %1$s or higher; your website has WordPress %2$s.', 'eway-payment-gateway'),
				esc_html(self::MIN_WP), esc_html($wp_ver));
		}

		// check PHP version
		if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
			$this->notices[] = eway_payment_gateway_external_link(
				sprintf(esc_html__('It requires PHP %1$s or higher; your website has PHP %2$s which is {{a}}old, obsolete, and unsupported{{/a}}.', 'eway-payment-gateway'),
					esc_html(self::MIN_PHP), esc_html(PHP_VERSION)),
				'https://www.php.net/supported-versions.php'
			);
			$this->notices[] = sprintf(esc_html__('Please upgrade your website hosting. At least PHP %s is recommended.', 'eway-payment-gateway'),
				esc_html(self::MIN_PHP));
		}

		// check for use of legacy API in all integrations that support it (i.e. not Event Espresso)
		$this->checkWooCommerce();
		$this->checkWPeCommerce();
		$this->checkEventsManager();
		$this->checkAWPCP();

		// if we have any notices, show the notices box and load the script for the Dismiss button
		if (!empty($this->notices)) {
			$notices = $this->notices;
			$dismiss = self::OPT_DISMISS;
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/upgrade-warning-notice.php';

			$min = SCRIPT_DEBUG ? '' : '.min';
			$ver = SCRIPT_DEBUG ? time() : EWAY_PAYMENTS_VERSION;

			wp_enqueue_script('eway-payment-gateway-dismiss', plugins_url("static/js/admin-dismiss$min.js", EWAY_PAYMENTS_PLUGIN_FILE), ['jquery'], $ver, true);
		}
	}

	/**
	 * dismiss warnings about update prerequisites not met
	 */
	public function dismissWarnings() {
		$dismiss = isset($_GET['dismiss']) ? $_GET['dismiss'] : '';

		if ($dismiss !== self::OPT_DISMISS) {
			return;
		}

		check_ajax_referer(self::OPT_DISMISS, 'nonce');

		update_option(self::OPT_DISMISS, '1');
		wp_send_json_success(['status' => '1']);
	}

	/**
	 * check for prerequisites in WooCommerce
	 */
	private function checkWooCommerce() {
		if (!function_exists('WC')) {
			return;
		}

		if (version_compare(WC()->version, self::MIN_WOOCOMMERCE, '<')) {
			$this->notices[] = sprintf(esc_html__('It requires WooCommerce version %1$s or higher; your website has WooCommerce version %2$s.', 'eway-payment-gateway'),
				esc_html(self::MIN_WOOCOMMERCE), esc_html(WC()->version));
		}

		$settings = get_option('woocommerce_eway_payments_settings', null);
		if ($settings && !empty($settings['eway_customerid'])) {
			if (empty($settings['eway_api_key']) || empty($settings['eway_password'])) {
				$this->addKeyPasswordNotice(empty($settings['eway_ecrypt_key']));
			}
		}
	}

	/**
	 * check for prerequisites in WP eCommerce
	 */
	private function checkWPeCommerce() {
		if (!class_exists('WP_eCommerce', false)) {
			return;
		}

		if (get_option('ewayCustomerID_id')) {
			$api_key	= get_option('eway_api_key');
			$password	= get_option('eway_password');
			$cse		= get_option('eway_ecrypt_key');
			if (empty($api_key) || empty($password)) {
				$this->addKeyPasswordNotice(empty($cse));
			}
		}
	}

	/**
	 * check for prerequisites in Events Manager
	 */
	private function checkEventsManager() {
		if (!class_exists('EM_Gateways', false)) {
			return;
		}

		if (get_option('em_eway_cust_id')) {
			$api_key	= get_option('em_eway_api_key');
			$password	= get_option('em_eway_password');
			$cse		= get_option('em_eway_ecrypt_key');
			if (empty($api_key) || empty($password)) {
				$this->addKeyPasswordNotice(empty($cse));
			}
		}
	}

	/**
	 * check for prerequisites in AWPCP
	 */
	private function checkAWPCP() {
		if (!function_exists('get_awpcp_option')) {
			return;
		}

		if (get_awpcp_option('eway_customerid')) {
			$api_key	= get_awpcp_option('eway_api_key');
			$password	= get_awpcp_option('eway_password');
			$cse		= get_awpcp_option('eway_ecrypt_key');
			if (empty($api_key) || empty($password)) {
				$this->addKeyPasswordNotice(empty($cse));
			}
		}
	}

	/**
	 * add notice for missing API key / password
	 * @param bool $alsoCSE
	 */
	private function addKeyPasswordNotice($alsoCSE) {
		$this->notices[] = eway_payment_gateway_external_link(
			esc_html__('A valid Rapid API key and password will be required. {{a}}Create an API key and password now{{/a}} and add them to your Eway settings.', 'eway-payment-gateway'),
			'https://go.eway.io/s/article/How-do-I-setup-my-Live-eWAY-API-Key-and-Password'
		);
		if ($alsoCSE) {
			$this->notices[] = eway_payment_gateway_external_link(
				esc_html__('You may also need to create a {{a}}Client Side Encryption key{{/a}} and add that to your Eway settings. Check with your Eway account manager.', 'eway-payment-gateway'),
				'https://go.eway.io/s/article/How-do-I-set-up-Client-Side-Encryption'
			);
		}
	}

	/**
	 * test whether we can show admin-related notices
	 * @return bool
	 */
	private function canShowAdminNotices() {
		global $hook_suffix;

		// only on specific pages
		$settings_pages = [
			'index.php',										// WordPress dashboard
			'plugins.php',										// Plugins list
			'update-core.php',									// Updates
			'settings_page_wpsc-settings',						// WP eCommerce
			'woocommerce_page_wc-settings',						// WooCommerce
			'woocommerce_page_wc-status',						// WooCommerce
			'event-espresso_page_espresso_payment_settings',	// Event Espresso
			'event_page_events-manager-gateways',				// Events Manager
			'classified-ads_page_awpcp-admin-settings',			// AWPCP
			'classifieds_page_awpcp-admin-settings',			// AWPCP -- legacy
		];
		if (!in_array($hook_suffix, $settings_pages)) {
			return false;
		}

		// only bother admins / plugin installers / option setters with this stuff
		if (!current_user_can('activate_plugins') && !current_user_can('manage_options')) {
			return false;
		}

		return true;
	}

}
