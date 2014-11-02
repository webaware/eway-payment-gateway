<?php

/**
* plugin controller class
*/
class EwayPaymentsPlugin {
	public $urlBase;									// string: base URL path to files in plugin

	/**
	* static method for getting the instance of this singleton object
	* @return EwayPaymentsPlugin
	*/
	public static function getInstance() {
		static $instance = NULL;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		$this->urlBase = plugin_dir_url(EWAY_PAYMENTS_PLUGIN_FILE);

		add_action('init', array($this, 'init'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);
		add_action('admin_notices', array($this, 'checkPrerequisites'));

		// register with WP e-Commerce
		add_filter('wpsc_merchants_modules', array($this, 'wpscRegister'));

		// register with WooCommerce
		add_filter('woocommerce_payment_gateways', array($this, 'wooRegister'));
	}

	/**
	* handle init action
	*/
	public function init() {
		// register with Events Manager
		if (class_exists('EM_Gateways')) {
			EM_Gateways::register_gateway('eway', 'EwayPaymentsEventsManager');
		}

		// register with Another WordPress Classifieds Plugin
		global $awpcp;
		if (isset($awpcp)) {
			new EwayPaymentsAWPCP();
		}
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
		$prereqs = array('libxml', 'SimpleXML', 'xmlwriter');
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
	* register new WP e-Commerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function wpscRegister($gateways) {
		return EwayPaymentsWpsc::register($gateways);
	}

	/**
	* register new WooCommerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function wooRegister($gateways) {
		if (!function_exists('WC')) {
			// pre-WC2.1 so load compatibility layer
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/wc-compatibility.php';
		}

		return EwayPaymentsWoo::register($gateways);
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file == EWAY_PAYMENTS_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/eway-payment-gateway">' . __('Get help') . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/eway-payment-gateway/">' . __('Rating') . '</a>';
			$links[] = '<a href="http://shop.webaware.com.au/downloads/eway-payment-gateway/">' . __('Donate') . '</a>';
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

		// can't use locate_template() because WP e-Commerce is _doing_it_wrong() again!
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
	* get base URL path for plugin files
	* @return string
	*/
	public static function getUrlPath() {
		$plugin = self::getInstance();
		return $plugin->urlBase;
	}

	/**
	* send data via HTTP and return response
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws GFDpsPxPayCurlException
	*/
	public static function curlSendRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent'	=> 'WordPress/eWAY Payment Gateway',
			'sslverify'		=> $sslVerifyPeer,
			'timeout'		=> 60,
			'headers'		=> array('Content-Type' => 'text/xml; charset=utf-8'),
			'body'			=> $data,
		));

		if (is_wp_error($response)) {
			throw new EwayPaymentsException($response->get_error_message());
		}

		return $response['body'];
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
}
