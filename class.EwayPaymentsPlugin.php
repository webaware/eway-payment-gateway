<?php

/**
* plugin controller class
*/
class EwayPaymentsPlugin {
	//~ public $urlBase;									// string: base URL path to files in plugin

	/**
	* static method for getting the instance of this singleton object
	*
	* @return GFEwayPlugin
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
		add_action('init', array($this, 'init'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		// register with WP e-Commerce
		add_filter('wpsc_merchants_modules', array($this, 'filterWpscRegister'));

		// register with WooCommerce
		add_filter('woocommerce_payment_gateways', array($this, 'filterWooRegister'));
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
	* register new wp-e-commerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function filterWpscRegister($gateways) {
		return EwayPaymentsWpsc::register($gateways);
	}

	/**
	* register new WooCommerce payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function filterWooRegister($gateways) {
		return EwayPaymentsWoo::register($gateways);
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file == EWAY_PAYMENTS_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/eway-payment-gateway">' . __('Get help') . '</a>';
			$links[] = '<a href="http://wordpress.org/extend/plugins/eway-payment-gateway/">' . __('Rating') . '</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=CXNFEP4EAMTG6">' . __('Donate') . '</a>';
		}

		return $links;
	}

	/**
	* load template from theme or plugin
	* can't use locate_template() because wp-e-commerce is _doing_it_wrong() again!
	* so have to roll our own...
	* @param string $template name of template file
	* @param array $variables an array of variables that should be accessible by the template
	*/
	public static function loadTemplate($template, $variables) {
		// make variables available to the template
		extract($variables);

		// check in theme / child theme folder
		$stylesheetFolder = get_stylesheet_directory();
		if (file_exists("$stylesheetFolder/$template")) {
			$template = "$stylesheetFolder/$template";
		}
		else {
			// check in parent theme folder
			$templateFolder = get_template_directory();
			if (file_exists("$templateFolder/$template")) {
				$template = "$templateFolder/$template";
			}
			else {
				// use plugin's template
				$template = EWAY_PAYMENTS_PLUGIN_ROOT . 'templates/' . $template;
			}
		}

		include $template;
	}

	/**
	* send data via cURL (or similar if cURL is unavailable) and return response
	* @param string $url
	* @param string $data
	* @param bool $sslVerifyPeer whether to validate the SSL certificate
	* @return string $response
	* @throws GFDpsPxPayCurlException
	*/
	public static function curlSendRequest($url, $data, $sslVerifyPeer = true) {
		// send data via HTTPS and receive response
		$response = wp_remote_post($url, array(
			'user-agent' => EWAY_PAYMENTS_CURL_USER_AGENT,
			'sslverify' => $sslVerifyPeer,
			'timeout' => 60,
			'headers' => array('Content-Type' => 'text/xml; charset=utf-8'),
			'body' => $data,
		));

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

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
