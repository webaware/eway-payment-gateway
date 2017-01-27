<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* plugin controller class
*/
class EwayPaymentsPlugin {

	/**
	* static method for getting the instance of this singleton object
	* @return EwayPaymentsPlugin
	*/
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		spl_autoload_register(array(__CLASS__, 'autoload'));

		add_action('init', array($this, 'init'));
		add_action('init', array($this, 'loadTextDomain'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);
		add_action('admin_notices', array($this, 'checkPrerequisites'));

		// register with WP eCommerce
		add_filter('wpsc_merchants_modules', array($this, 'wpscRegister'));

		// register with WooCommerce
		add_filter('woocommerce_payment_gateways', array($this, 'wooRegister'));

		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/class.EwayPaymentsLogging.php';
	}

	/**
	* handle init action
	*/
	public function init() {
		// register with Events Manager
		if (class_exists('EM_Gateways')) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EwayPaymentsEventsManager.php';
			EM_Gateways::register_gateway('eway', 'EwayPaymentsEventsManager');
		}

		// register with Another WordPress Classifieds Plugin
		if (function_exists('awpcp')) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EwayPaymentsAWPCP3.php';
			EwayPaymentsAWPCP3::setup();
		}
	}

	/**
	* load text translations
	*/
	public function loadTextDomain() {
		load_plugin_textdomain('eway-payment-gateway');
	}

	/**
	* check for required PHP extensions, tell admin if any are missing
	*/
	public function checkPrerequisites() {
		// need at least PHP 5.2.11 for libxml_disable_entity_loader()
		$php_min = '5.2.11';
		if (version_compare(PHP_VERSION, $php_min, '<')) {
			include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-php.php';
		}

		// need these PHP extensions too
		$prereqs = array('json', 'libxml', 'pcre', 'SimpleXML', 'xmlwriter');
		$missing = array();
		foreach ($prereqs as $ext) {
			if (!extension_loaded($ext)) {
				$missing[] = $ext;
			}
		}
		if (!empty($missing)) {
			include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/requires-extensions.php';
		}
	}

	/**
	* register new WP eCommerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function wpscRegister($gateways) {
		require_once EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EwayPaymentsWpsc.php';

		return EwayPaymentsWpsc::register($gateways);
	}

	/**
	* register new WooCommerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function wooRegister($gateways) {
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/wc-compatibility.php';
		require_once EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EwayPaymentsWoo.php';

		return EwayPaymentsWoo::register($gateways);
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file === EWAY_PAYMENTS_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/eway-payment-gateway" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/eway-payment-gateway/" target="_blank">%s</a>', _x('Rating', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/eway-payment-gateway" target="_blank">%s</a>', _x('Translate', 'plugin details links', 'eway-payment-gateway'));
			$links[] = sprintf('<a href="https://shop.webaware.com.au/donations/?donation_for=eWAY+Payment+Gateway" target="_blank">%s</a>', _x('Donate', 'plugin details links', 'eway-payment-gateway'));
		}

		return $links;
	}

	/**
	* load template from theme or plugin
	* @param string $template name of template file
	* @param array $variables an array of variables that should be accessible by the template
	*/
	public static function loadTemplate($template, $variables) {
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
	* send XML data via HTTP and return response
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws EwayPaymentsException
	*/
	public static function xmlPostRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent'	=> 'WordPress/eWAY Payment Gateway ' . EWAY_PAYMENTS_VERSION,
			'sslverify'		=> $sslVerifyPeer,
			'timeout'		=> 60,
			'headers'		=> array('Content-Type' => 'text/xml; charset=utf-8'),
			'body'			=> $data,
		));

		if (is_wp_error($response)) {
			throw new EwayPaymentsException($response->get_error_message());
		}

		// error code returned by request
		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			$msg = wp_remote_retrieve_response_message($response);

			if (empty($msg)) {
				/* translators: %s = the error code */
				$msg = sprintf(__('Error posting eWAY request: %s', 'eway-payment-gateway'), $code);
			}
			else {
				/* translators: 1. the error code; 2. the error message */
				$msg = sprintf(__('Error posting eWAY request: %1$s, %2$s', 'eway-payment-gateway'), $code, $msg);
			}
			throw new EwayPaymentsException($msg);
		}

		return wp_remote_retrieve_body($response);
	}

	/**
	* get the customer's IP address dynamically from server variables
	* @param bool $isLiveSite
	* @return string
	*/
	public static function getCustomerIP($isLiveSite) {
		// if test mode and running on localhost, then kludge to an Aussie IP address
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && !$isLiveSite) {
			return '210.1.199.10';
		}

		// check for remote address, ignore all other headers as they can be spoofed easily
		if (isset($_SERVER['REMOTE_ADDR']) && self::isIpAddress($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

		return '';
	}

	/**
	* check whether a given string is an IP address
	* @param string $maybeIP
	* @return bool
	*/
	protected static function isIpAddress($maybeIP) {
		if (function_exists('inet_pton')) {
			// check for IPv4 and IPv6 addresses
			return !!inet_pton($maybeIP);
		}

		// just check for IPv4 addresses
		return !!ip2long($maybeIP);
	}

	/**
	* autoload classes as/when needed
	* @param string $class_name name of class to attempt to load
	*/
	public static function autoload($class_name) {
		static $classMap = array (
			'EwayPaymentsFormPost'					=> 'includes/class.EwayPaymentsFormPost.php',
			'EwayPaymentsFormUtils'					=> 'includes/class.EwayPaymentsFormUtils.php',
			'EwayPaymentsPayment'					=> 'includes/class.EwayPaymentsPayment.php',
			'EwayPaymentsRapidAPI'					=> 'includes/class.EwayPaymentsRapidAPI.php',
			'EwayPaymentsResponse'					=> 'includes/class.EwayPaymentsResponse.php',
			'EwayPaymentsResponseDirectPayment'		=> 'includes/class.EwayPaymentsResponseDirectPayment.php',
			'EwayPaymentsStoredPayment'				=> 'includes/class.EwayPaymentsStoredPayment.php',
		);

		if (isset($classMap[$class_name])) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . $classMap[$class_name];
		}
	}

}
