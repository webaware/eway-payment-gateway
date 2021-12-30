<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
 * collect and display admin notices for failed prerequisites
 */
final class EwayPaymentGatewayRequires {

	private static $notices;

	/**
	 * set up the notices container, and hook actions for displaying notices
	 * only called when a notice is being added
	 */
	private function init() {
		if (!is_array(self::$notices)) {
			self::$notices = array();

			// hook admin_notices, again! so need to hook later than 10
			add_action('admin_notices', array(__CLASS__, 'maybeShowAdminNotices'), 20);

			// show Requires notices before update information, so hook earlier than 10
			add_action('after_plugin_row_' . EWAY_PAYMENTS_PLUGIN_NAME, array(__CLASS__, 'showPluginRowNotices'), 9, 2);
		}
	}

	/**
	 * add a Requires notices
	 * @param string $notice
	 */
	public function addNotice($notice) {
		$this->init();
		self::$notices[] = $notice;
	}

	/**
	 * maybe show admin notices, if on an appropriate admin page with admin or similar logged in
	 */
	public static function maybeShowAdminNotices() {
		if (self::canShowAdminNotices()) {
			$notices = self::$notices;
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-admin-notice.php';
		}
	}

	/**
	 * show plugin page row with requires notices
	 */
	public static function showPluginRowNotices() {
		global $wp_list_table;

		if (empty($wp_list_table)) {
			return;
		}

		$notices = self::$notices;
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-plugin-notice.php';
	}

	/**
	 * test whether we can show admin-related notices
	 * @return bool
	 */
	private static function canShowAdminNotices() {
		global $hook_suffix;

		// only on specific pages
		if ($hook_suffix !== 'woocommerce_page_wc-settings' && $hook_suffix !== 'woocommerce_page_wc-status') {
			return false;
		}

		// only on specific pages
		$settings_pages = array(
			'settings_page_wpsc-settings',				// WP eCommerce
			'woocommerce_page_wc-settings',				// WooCommerce
			'woocommerce_page_wc-status',				// WooCommerce
			'event_page_events-manager-gateways',		// Events Manager
			'classifieds_page_awpcp-admin-settings',	// AWPCP
		);
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
