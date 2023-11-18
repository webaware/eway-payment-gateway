<?php
namespace webaware\eway_payment_gateway;

use WC_Order;
use WC_Payment_Gateway_CC;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * payment gateway integration for WooCommerce
 * @link https://docs.woothemes.com/document/payment-gateway-api/
 */
final class MethodWooCommerce extends WC_Payment_Gateway_CC {

	private Logging $logger;

	public string $admin_page_heading;
	public string $admin_page_description;

	/**
	 * hook WooCommerce to register gateway integration
	 */
	public static function register_eway() : void {
		add_filter('woocommerce_payment_gateways', [__CLASS__, 'register']);
		add_action('before_woocommerce_init', [__CLASS__, 'declareCompatibleHPOS']);
	}

	/**
	 * register new payment gateway
	 */
	public static function register(array $gateways) : array {
		$gateways[] = __CLASS__;
		return $gateways;
	}

	/**
	 * declare compatibility with HPOS (high performance order storage, i.e. custom tables)
	 */
	public static function declareCompatibleHPOS() {
		if (class_exists(FeaturesUtil::class)) {
			FeaturesUtil::declare_compatibility('custom_order_tables', EWAY_PAYMENTS_PLUGIN_FILE, true);
		}
	}

	/**
	 * initialise gateway with custom settings
	 */
	public function __construct() {
		// NB: no parent constructor (yet!)

		$this->id						= 'eway_payments';
		$this->icon						= apply_filters('woocommerce_eway_icon', plugins_url('static/images/eway-tiny.svg', EWAY_PAYMENTS_PLUGIN_FILE));
		$this->method_title				= _x('Eway', 'WooCommerce payment method title', 'eway-payment-gateway');
		$this->method_description		= __('Take payments online with Eway credit card payments.', 'eway-payment-gateway');
		$this->admin_page_heading 		= _x('Eway payment gateway', 'WooCommerce admin page heading', 'eway-payment-gateway');
		$this->admin_page_description 	= $this->method_description;
		$this->has_fields				= true;

		// load form fields.
		$this->initFormFields();

		// load settings (via WC_Settings_API)
		$this->init_settings();

		// define user set variables
		$this->enabled					= $this->settings['enabled'];
		$this->title					= $this->settings['title'];
		$this->description				= $this->settings['description'];
		$this->availability				= $this->settings['availability'];
		$this->countries				= $this->settings['countries'];

		$creds = $this->getApiCredentials();

		// maybe make this gateway unavailable if missing API credentials
		if ($this->enabled === 'yes' && $creds->isMissingCredentials()) {
			add_filter('woocommerce_available_payment_gateways', [$this, 'wooMakeUnavailable']);
		}
		add_action('woocommerce_settings_checkout', [$this, 'wooMaybeNotifyCreds']);

		// handle support for standard WooCommerce credit card form instead of our custom template
		if ($this->enabled === 'yes' && $this->get_option('eway_card_form') === 'yes' && !$creds->isMissingCredentials()) {
			$this->supports[]			= 'default_credit_card_form';
			add_filter('woocommerce_credit_card_form_fields', [$this, 'wooCcFormFields'], 10, 2);
			add_action('woocommerce_credit_card_form_start', [$this, 'wooCcFormStart']);
			add_action('woocommerce_credit_card_form_end', [$this, 'wooCcFormEnd']);
		}

		// create a logger
		$this->logger = new Logging('woocommerce', empty($this->settings['eway_logging']) ? 'off' : $this->settings['eway_logging']);

		add_filter('woocommerce_email_order_meta_fields', [$this, 'wooEmailOrderMetaKeys'], 10, 3);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
	}

	/**
	 * initialise settings form fields
	 */
	private function initFormFields() : void {
		// get recorded settings, so we can determine sane defaults when upgrading
		$settings = get_option('woocommerce_eway_payments_settings');

		$this->form_fields = [

			'enabled' => [
							'title' 		=> translate('Enable/Disable', 'woocommerce'),
							'type' 			=> 'checkbox',
							'label' 		=> esc_html__('enable Eway credit card payment', 'eway-payment-gateway'),
							'default' 		=> 'no',
			],

			'title' => [
							'title' 		=> translate('Method Title', 'woocommerce'),
							'type' 			=> 'text',
							'description' 	=> translate('This controls the title which the user sees during checkout.', 'woocommerce'),
							'desc_tip'		=> true,
							'default'		=> _x('Credit card', 'WooCommerce payment method title', 'eway-payment-gateway'),
			],

			'description' => [
							'title' 		=> translate('Description', 'woocommerce'),
							'type' 			=> 'textarea',
							'description' 	=> translate('This controls the description which the user sees during checkout.', 'woocommerce'),
							'desc_tip'		=> true,
							'default'		=> _x('Pay with your credit card using Eway secure checkout', 'WooCommerce payment method description', 'eway-payment-gateway'),
			],

			'availability' => [
							'title' 		=> translate('Method availability', 'woocommerce'),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability wc-enhanced-select',
							'options'		=> [
								'all' 		=> translate('All allowed countries', 'woocommerce'),
								'specific' 	=> translate('Specific Countries', 'woocommerce'),
							],
			],

			'countries' => [
							'title' 		=> translate('Specific Countries', 'woocommerce'),
							'type' 			=> 'multiselect',
							'class'			=> 'wc-enhanced-select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> WC()->countries->countries,
			],

			'eway_api_key' => [
							'title' 		=> _x('API key', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'text',
							'css'			=> 'width: 100%',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_password' => [
							'title' 		=> _x('API password', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'password',
							'custom_attributes'	=> [
														'autocorrect'		=> 'new-password',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_ecrypt_key' => [
							'title' 		=> _x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'textarea',
							'css'			=> 'height: 6em',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_sandbox' => [
							'title' 		=> _x('Sandbox mode', 'settings field', 'eway-payment-gateway'),
							'label' 		=> __('enable sandbox (testing) mode', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'description' 	=> esc_html__('Use the sandbox testing environment, no live payments are accepted; use test card number 4444333322221111', 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default' 		=> 'yes',
			],

			'eway_sandbox_api_key' => [
							'title' 		=> _x('Sandbox API key', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'text',
							'css'			=> 'width: 100%',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_sandbox_password' => [
							'title' 		=> _x('Sandbox API password', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'password',
							'custom_attributes'	=> [
														'autocorrect'		=> 'new-password',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_sandbox_ecrypt_key' => [
							'title' 		=> _x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'textarea',
							'css'			=> 'height: 6em',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
														'autocapitalize'	=> 'off',
														'spellcheck'		=> 'false',
												],
			],

			'eway_stored' => [
							'title' 		=> _x('Payment Method', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'select',
							'class'			=> 'wc-enhanced-select',
							'description' 	=> esc_html__("Capture processes the payment immediately. Authorize holds the amount on the customer's card for processing later.", 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default' 		=> 'no',
							'options'		=> [
								// for backwards-compatibility, Capture - "not stored", Authorize - "stored / Pre-Auth"
								'no' 		=> esc_html_x('Capture', 'payment method', 'eway-payment-gateway'),
								'yes'	 	=> esc_html_x('Authorize', 'payment method', 'eway-payment-gateway'),
							],
			],

			'eway_logging' => [
							'title' 		=> _x('Logging', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'select',
							'class'			=> 'wc-enhanced-select',
							'description'	=>	sprintf('%s<br/>%s<br/>%s',
													esc_html__('Enable logging to assist trouble shooting', 'eway-payment-gateway'),
													esc_html__('the log file can be found in this folder:', 'eway-payment-gateway'),
													esc_html(Logging::getLogFolderRelative())),
							'default' 		=> 'off',
							'options'		=> [
								'off' 		=> esc_html_x('Off', 'logging settings', 'eway-payment-gateway'),
								'info'	 	=> esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'),
								'error' 	=> esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'),
							],
			],

			'eway_card_form' => [
							'title' 		=> _x('Credit card fields', 'settings field', 'eway-payment-gateway'),
							'label' 		=> esc_html__('use WooCommerce standard credit card fields', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'description' 	=> esc_html__('Ticked, the standard WooCommerce credit card fields will be used. Unticked, a custom template will be used for the credit card fields.', 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default' 		=> (is_array($settings) ? 'no' : 'yes'),
			],

			'eway_card_msg' => [
							'title' 		=> _x('Credit card message', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'text',
							'css'			=> 'width:100%',
							'description' 	=> esc_html_x('Message to show above credit card fields, e.g. "Visa and Mastercard only"', 'settings label', 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default'		=> '',
			],

			'eway_emails_show_txid' => [
							'title' 		=> _x('Transaction ID on emails', 'settings field', 'eway-payment-gateway'),
							'label' 		=> esc_html__('show the Eway transaction ID on order emails', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'default' 		=> 'yes',
			],

			'eway_site_seal' => [
							'title' 		=> _x('Show Eway Site Seal', 'settings field', 'eway-payment-gateway'),
							'label' 		=> esc_html__('show the Eway site seal after the credit card fields', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'description' 	=> esc_html__('Add the verified Eway Site Seal to your checkout', 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default' 		=> 'no',
			],

			'eway_site_seal_code' => [
							'type' 			=> 'textarea',
							'description' 	=> sprintf('<a href="https://www.eway.com.au/features/tools/tools-site-seal/" rel="noopener" target="_blank">%s</a>',
													esc_html__('Generate your site seal on the Eway website, and paste it here', 'eway-payment-gateway')),
							'default'		=> '',
							'css'			=> 'height:14em',
			],

		];
	}

	/**
	 * extend parent method for initialising settings, so that new settings can receive defaults
	 */
	public function init_settings() {
		parent::init_settings();

		$form_fields = $this->get_form_fields();

		if ($form_fields) {
			foreach ($form_fields as $key => $value) {
				if (!isset($this->settings[$key])) {
					$this->settings[$key] = isset($value['default']) ? $value['default'] : '';
				}
			}
		}
	}

	/**
	 * show the admin panel for setting plugin options
	 */
	public function admin_options() {
		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-woocommerce.php';

		add_action('admin_print_footer_scripts', [$this, 'adminSettingsScript']);
	}

	/**
	 * add page script for admin options
	 */
	public function adminSettingsScript() : void {
		$min	= SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "static/js/admin-woocommerce-settings$min.js");
		echo '</script>';
	}

	/**
	 * remove from list of available gateways, because credentials are not complete
	 * @param array $available
	 * @return array
	 */
	public function wooMakeUnavailable(array $available) : array {
		unset($available[$this->id]);
		return $available;
	}

	/**
	 * maybe notify admins that gateway is missing credentials
	 */
	public function wooMaybeNotifyCreds() : void {
		static $first_time = true;

		$tab = $_GET['tab'] ?? '';
		$section = $_GET['section'] ?? '';

		if ($first_time && $tab === 'checkout' && $section === $this->id && $this->enabled === 'yes') {
			$first_time = false;

			// check uncached settings
			$settings = get_option($this->get_option_key(), null);

			if (is_array($settings)) {
				$sandbox = ($settings['eway_sandbox'] ?? '') === 'yes' ? 'sandbox_' : '';
			}

			$api_key	= $settings["eway_{$sandbox}api_key"] ?? '';
			$password	= $settings["eway_{$sandbox}password"] ?? '';

			if (empty($api_key) || empty($password)) {
				require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-notice-missing-creds.php';
			}
		}
	}

	/**
	 * add Name field to WooCommerce credit card form
	 */
	public function wooCcFormFields(array $fields, string $gateway) : array {
		if ($gateway === $this->id) {
			ob_start();
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/woocommerce-ccfields-card-name.php';
			$card_name = ob_get_clean();

			$fields = array_merge(['card-name-field' => $card_name], $fields);
		}

		return $fields;
	}

	/**
	 * show message before fields in standard WooCommerce credit card form
	 */
	public function wooCcFormStart(string $gateway) : void {
		if ($gateway === $this->id) {
			if (!empty($this->settings['eway_card_msg'])) {
				printf('<span class="eway-credit-card-message">%s</span>', esc_html($this->settings['eway_card_msg']));
			}

			$this->maybeEnqueueCSE();
		}
	}

	/**
	 * maybe enqueue the Client Side Encryption scripts for encrypting credit card details
	 */
	private function maybeEnqueueCSE() : void {
		$creds = $this->getApiCredentials();
		if ($creds->hasCSEKey()) {
			wp_enqueue_script('eway-payment-gateway-ecrypt');
			add_action('wp_footer', [$this, 'ecryptScript']);
		}
	}

	/**
	 * configure the scripts for client-side encryption
	 */
	public function ecryptScript() : void {
		$creds	= $this->getApiCredentials();

		$vars = [
			'mode'		=> 'woocommerce',
			'key'		=> $creds->ecrypt_key,
			'form'		=> 'form.checkout,form#order_review',
			'fields'	=> [
							"#{$this->id}-card-number"	=> ['name' => "cse:{$this->id}-card-number", 'is_cardnum' => true],
							"#{$this->id}-card-cvc"		=> ['name' => "cse:{$this->id}-card-cvc", 'is_cardnum' => false],
							'#eway_card_number'			=> ['name' => 'cse:eway_card_number', 'is_cardnum' => true],
							'#eway_cvn'					=> ['name' => 'cse:eway_cvn', 'is_cardnum' => false],
						],
		];

		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_vars', $vars);
	}

	/**
	 * show site seal after fields in standard WooCommerce credit card form, if entered
	 */
	public function wooCcFormEnd(string $gateway) : void {
		if ($gateway === $this->id) {
			if (!empty($this->settings['eway_site_seal']) && !empty($this->settings['eway_site_seal_code']) && $this->settings['eway_site_seal'] === 'yes') {
				echo $this->settings['eway_site_seal_code'];
			}
		}
	}

	/**
	 * display payment form on checkout page
	 */
	public function payment_fields() : void {
		if ($this->get_option('eway_card_form') === 'yes') {
			// use standard WooCommerce credit card form
			$this->form();
		}
		else {
			$optMonths = get_month_options();
			$optYears  = get_year_options();

			// load payment fields template with passed values
			$settings = $this->settings;
			eway_load_template('woocommerce-eway-fields.php', compact('optMonths', 'optYears', 'settings'));

			$this->maybeEnqueueCSE();
		}
	}

	/**
	 * get field values from credit card form -- either WooCommerce standard, or the old template
	 */
	private function getCardFields() : array {
		$postdata = new FormPost();

		if ($this->get_option('eway_card_form') === 'yes') {
			// split expiry field into month and year
			$expiry = $postdata->getValue('eway_payments-card-expiry');
			$expiry = array_map('trim', explode('/', $expiry, 2));
			if (count($expiry) === 2) {
				// prefix year with '20' if it's exactly two digits
				if (preg_match('/^[0-9]{2}$/', $expiry[1])) {
					$expiry[1] = '20' . $expiry[1];
				}
			}
			else {
				$expiry = ['', ''];
			}

			$fields = [
				'card_number'  => $postdata->cleanCardnumber($postdata->getValue('eway_payments-card-number')),
				'card_name'    => $postdata->getValue('eway_payments-card-name'),
				'expiry_month' => $expiry[0],
				'expiry_year'  => $expiry[1],
				'cvn'          => $postdata->getValue('eway_payments-card-cvc'),
			];
		}
		else {
			$fields = [
				'card_number'  => $postdata->cleanCardnumber($postdata->getValue('eway_card_number')),
				'card_name'    => $postdata->getValue('eway_card_name'),
				'expiry_month' => $postdata->getValue('eway_expiry_month'),
				'expiry_year'  => $postdata->getValue('eway_expiry_year'),
				'cvn'          => $postdata->getValue('eway_cvn'),
			];
		}

		return $fields;
	}

	/**
	 * validate entered data for errors / omissions
	 * @return bool
	 */
	public function validate_fields() {
		$postdata		= new FormPost();
		$fields			= $this->getCardFields();
		$errors			= $postdata->verifyCardDetails($fields);

		if (!empty($errors)) {
			foreach ($errors as $error) {
				wc_add_notice($error, 'error');
			}
		}

		return empty($errors);
	}

	/**
	 * process the payment and return the result
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment($order_id) {
		$order		= self::getOrder($order_id);
		$ccfields	= $this->getCardFields();

		$capture	= apply_filters('woocommerce_eway_method_capture', ($this->get_option('eway_stored')  !== 'yes'), $order);
		$useSandbox	= ($this->get_option('eway_sandbox') === 'yes');
		$creds		= apply_filters('woocommerce_eway_credentials', $this->getApiCredentials(), $useSandbox, $order);

		if ($creds->isMissingCredentials()) {
			$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
			wc_add_notice(esc_html__('Eway payments is not configured for payments yet', 'eway-payment-gateway'), 'error');
			return ['result' => 'failure'];
		}

		$eway		= new EwayRapidAPI($creds->api_key, $creds->password, $useSandbox);
		$eway->capture = $capture;

		// allow plugins/themes to modify transaction ID; NB: must remain unique for Eway account!
		$transactionID = apply_filters('woocommerce_eway_trans_number', $order_id);

		$customer = new CustomerDetails;
		$customer->setFirstName($order->get_billing_first_name());
		$customer->setLastName($order->get_billing_last_name());
		$customer->setStreet1($order->get_billing_address_1());
		$customer->setStreet2($order->get_billing_address_2());
		$customer->setCity($order->get_billing_city());
		$customer->setState($order->get_billing_state());
		$customer->setPostalCode($order->get_billing_postcode());
		$customer->setCountry($order->get_billing_country());
		$customer->setEmail($order->get_billing_email());
		$customer->setCompanyName($order->get_billing_company());
		$customer->setPhone($order->get_billing_phone());
		$customer->setComments($order->get_customer_note());

		$customer->CardDetails = new CardDetails(
			$ccfields['card_name'],
			$ccfields['card_number'],
			$ccfields['expiry_month'],
			$ccfields['expiry_year'],
			$ccfields['cvn'],
		);

		// use cardholder name for last name if no customer name entered
		if (empty($customer->FirstName) && empty($customer->LastName)) {
			$customer->setLastName($customer->CardDetails->Name);
		}

		// maybe send shipping details
		$shipping = null;
		if ($order->get_shipping_address_1() || $order->get_shipping_address_2()) {
			$shipping = new ShippingAddress;
			$shipping->setFirstName($order->get_shipping_first_name());
			$shipping->setLastName($order->get_shipping_last_name());
			$shipping->setStreet1($order->get_shipping_address_1());
			$shipping->setStreet2($order->get_shipping_address_2());
			$shipping->setCity($order->get_shipping_city());
			$shipping->setState($order->get_shipping_state());
			$shipping->setPostalCode($order->get_shipping_postcode());
			$shipping->setCountry($order->get_shipping_country());
		}

		// only populate payment record if there's an amount value
		$payment = new PaymentDetails;
		$amount = $order->get_total();
		$currency = $order->get_currency();
		if ($amount > 0) {
			$payment->setTotalAmount($amount, $currency);
			$payment->setCurrencyCode($currency);
			$payment->setInvoiceReference($transactionID);
			$payment->setInvoiceDescription(apply_filters('woocommerce_eway_invoice_desc', get_bloginfo('name'), $order_id));
			$payment->setInvoiceNumber(apply_filters('woocommerce_eway_invoice_ref', $order->get_order_number(), $order_id));
		}

		// allow plugins/themes to set option fields
		$options = get_api_options([
			apply_filters('woocommerce_eway_option1', '', $order_id),
			apply_filters('woocommerce_eway_option2', '', $order_id),
			apply_filters('woocommerce_eway_option3', '', $order_id),
		]);

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live',
			$payment->InvoiceNumber, $payment->InvoiceReference, $payment->TotalAmount, $customer->CardDetails->Number));

		try {
			$response = $eway->processPayment($customer, $shipping, $payment, $options);

			if ($response->TransactionStatus) {
				// transaction was successful, so record details and complete payment
				$meta = ['Transaction ID' => $response->TransactionID];
				if (!empty($response->AuthorisationCode)) {
					$meta['Authcode'] = $response->AuthorisationCode;
				}
				if ($response->BeagleScore >= 0) {
					$meta['Beagle score'] = $response->BeagleScore;
				}
				self::updateOrderMeta($order, $meta);

				if (!$capture) {
					// payment hasn't happened yet, so record status as 'on-hold' and reduce stock in anticipation
					wc_reduce_stock_levels($order_id);
					$order->update_status('on-hold', __('Payment authorized', 'eway-payment-gateway'));
					if (isset($_SESSION)) {
						unset($_SESSION['order_awaiting_payment']);
					}
				}
				else {
					$order->payment_complete();
				}
				WC()->cart->empty_cart();

				$result = [
					'result'	=> 'success',
					'redirect'	=> $this->get_return_url($order),
				];

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
					$payment->InvoiceNumber, $response->TransactionID, $capture ? 'completed' : 'on-hold',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$order->update_status('failed', $error_msg);
				wc_add_notice(apply_filters('woocommerce_eway_error_msg', $error_msg, $response), 'error');
				$result = ['result' => 'failure'];

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $payment->InvoiceNumber, $response->getErrorsForLog()));
				if ($response->BeagleScore > 0) {
					$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
				}
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$order->update_status('failed', nl2br(esc_html($e->getMessage())));
			wc_add_notice(nl2br(esc_html($e->getMessage())), 'error');
			$result = ['result' => 'failure'];

			$this->logger->log('error', $e->getMessage());
		}

		return $result;
	}

	/**
	 * get API credentials based on settings
	 */
	private function getApiCredentials() : Credentials {
		if ($this->get_option('eway_sandbox') !== 'yes') {
			$creds = new Credentials(
				$this->get_option('eway_api_key'),
				$this->get_option('eway_password'),
				$this->get_option('eway_ecrypt_key'),
			);
		}
		else {
			$creds = new Credentials(
				$this->get_option('eway_sandbox_api_key'),
				$this->get_option('eway_sandbox_password'),
				$this->get_option('eway_sandbox_ecrypt_key'),
			);
		}

		return $creds;
	}

	/**
	 * add the successful transaction ID to WooCommerce order emails
	 */
	public function wooEmailOrderMetaKeys(?array $keys, bool $sent_to_admin, /* mixed */ $order) : array {
		if (apply_filters('woocommerce_eway_email_show_trans_number', $this->get_option('eway_emails_show_txid') === 'yes', $order)) {
			$order			= self::getOrder($order);
			$key			= 'Transaction ID';

			$keys[$key]		= [
				'label'		=> wptexturize($key),
				'value'		=> wptexturize($order->get_meta($key, true)),
			];
		}

		return $keys;
	}

	/**
	 * get order object for order
	 * @param int|object|WC_Order $order
	 * @return WC_Order
	 */
	private static function getOrder($order) : WC_Order {
		if (!($order instanceof WC_Order)) {
			$order = wc_get_order($order);
		}

		return $order;
	}

	/**
	 * update order meta
	 */
	private static function updateOrderMeta(WC_Order $order, array $meta) : void {
		foreach ($meta as $key => $value) {
			$order->update_meta_data($key, $value);
		}
		$order->save_meta_data();
	}

}
