<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
* plugin controller class
*/
class Plugin {

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* hide constructor
	*/
	private function __construct() { }

	/**
	* initialise plugin
	*/
	public function pluginStart() {
		add_action('init', 'eway_payment_gateway_load_text_domain');
		add_filter('plugin_row_meta', [$this, 'addPluginDetailsLinks'], 10, 2);
		add_action('admin_notices', [$this, 'checkPrerequisites']);
		add_action('wp_enqueue_scripts', [$this, 'registerScripts']);

		// register integrations
		add_filter('wpsc_merchants_modules', [$this, 'registerWPeCommerce']);
		add_action('init', [$this, 'maybeRegisterAWPCP']);
		add_action('init', [$this, 'maybeRegisterEventsManager']);
		$this->maybeRegisterWooCommerce();		// hooked on plugins_loaded
	}

	/**
	* check for required PHP extensions, tell admin if any are missing
	*/
	public function checkPrerequisites() {
		if (!eway_payment_gateway_can_show_admin_notices()) {
			return;
		}

		// need these PHP extensions
		$missing = array_filter(['json', 'libxml', 'pcre', 'SimpleXML', 'xmlwriter'], function($ext) {
			return !extension_loaded($ext);
		});
		if (!empty($missing)) {
			include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-extensions.php';
		}
	}

	/**
	* register required scripts
	*/
	public function registerScripts() {
		$min = SCRIPT_DEBUG ? '' : '.min';
		$ver = SCRIPT_DEBUG ? time() : EWAY_PAYMENTS_VERSION;

		wp_register_script('eway-ecrypt', "https://secure.ewaypayments.com/scripts/eCrypt$min.js", [], null, true);
		wp_register_script('eway-payment-gateway-ecrypt', plugins_url("js/ecrypt$min.js", EWAY_PAYMENTS_PLUGIN_FILE), ['jquery','eway-ecrypt'], $ver, true);
		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_msg', [
			'ecrypt_mask'			=> _x('•', 'encrypted field mask character', 'eway-payment-gateway'),
			'card_number_invalid'	=> __('Card number is invalid', 'eway-payment-gateway'),
		]);
	}

	/**
	* register new WP eCommerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function registerWPeCommerce($gateways) {
		$this->loadRequired();
		require_once EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.WPeCommerce.php';

		return MethodWPeCommerce::register_eway($gateways);
	}

	/**
	* maybe load WooCommerce payment gateway
	*/
	public function maybeRegisterWooCommerce() {
		if (!function_exists('WC')) {
			return;
		}

		$this->loadRequired();
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/wc-compatibility.php';
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.WooCommerce.php';

		return MethodWooCommerce::register_eway();
	}

	/**
	* maybe register with Events Manager
	*/
	public function maybeRegisterEventsManager() {
		if (class_exists('EM_Gateways')) {
			$this->loadRequired();
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EventsManager.php';
			return MethodEventsManager::register_eway();
		}
	}

	/**
	* maybe register with Another WordPress Classifieds Plugin (AWPCP)
	*/
	public function maybeRegisterAWPCP() {
		if (function_exists('awpcp')) {
			$this->loadRequired();
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.AWPCP.php';
			MethodAWPCP::register_eway();
		}
	}

	/**
	* load some functions and classes required for all integrations
	*/
	protected function loadRequired() {
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/functions-form-utils.php';
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/class.Logging.php';
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file === EWAY_PAYMENTS_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/eway-payment-gateway" rel="noopener" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/eway-payment-gateway/" rel="noopener" target="_blank">%s</a>', _x('Rating', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/eway-payment-gateway" rel="noopener" target="_blank">%s</a>', _x('Translate', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://shop.webaware.com.au/donations/?donation_for=eWAY+Payment+Gateway" rel="noopener" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'eway-payment-gateway'));
		}

		return $links;
	}

}
