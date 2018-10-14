<?php
namespace webaware\eway_payment_gateway;

use webaware\eway_payment_gateway\woocommerce\CompatibleOrder;

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for WooCommerce
* @link https://docs.woothemes.com/document/payment-gateway-api/
*/
class MethodWooCommerce extends \WC_Payment_Gateway_CC {

	protected $logger;

	/**
	* hook WooCommerce to register gateway integration
	*/
	public static function register_eway() {
		add_filter('woocommerce_payment_gateways', [__CLASS__, 'register']);
	}

	/**
	* register new payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public static function register($gateways) {
		$gateways[] = __CLASS__;
		return $gateways;
	}

	/**
	* initialise gateway with custom settings
	*/
	public function __construct() {
		// NB: no parent constructor (yet!)

		$this->id						= 'eway_payments';
		$this->icon						= apply_filters('woocommerce_eway_icon', plugins_url('images/eway-tiny.png', EWAY_PAYMENTS_PLUGIN_FILE));
		$this->method_title				= _x('eWAY', 'WooCommerce payment method title', 'eway-payment-gateway');
		$this->method_description		= __('Take payments online with eWAY credit card payments.', 'eway-payment-gateway');
		$this->admin_page_heading 		= _x('eWAY payment gateway', 'WooCommerce admin page heading', 'eway-payment-gateway');
		$this->admin_page_description 	= $this->method_description;
		$this->has_fields = true;

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
		$this->eway_api_key				= $this->settings['eway_api_key'];
		$this->eway_password			= $this->settings['eway_password'];
		$this->eway_ecrypt_key			= $this->settings['eway_ecrypt_key'];
		$this->eway_customerid			= $this->settings['eway_customerid'];
		$this->eway_sandbox				= $this->settings['eway_sandbox'];
		$this->eway_sandbox_api_key		= $this->settings['eway_sandbox_api_key'];
		$this->eway_sandbox_password	= $this->settings['eway_sandbox_password'];
		$this->eway_sandbox_ecrypt_key	= $this->settings['eway_sandbox_ecrypt_key'];
		$this->eway_stored				= $this->settings['eway_stored'];
		$this->eway_card_form			= $this->settings['eway_card_form'];
		$this->eway_card_msg			= $this->settings['eway_card_msg'];
		$this->eway_site_seal			= $this->settings['eway_site_seal'];
		$this->eway_site_seal_code		= $this->settings['eway_site_seal_code'];
		$this->eway_emails_show_txid	= $this->settings['eway_emails_show_txid'];

		// handle support for standard WooCommerce credit card form instead of our custom template
		if ($this->eway_card_form === 'yes') {
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
	public function initFormFields() {
		// get recorded settings, so we can determine sane defaults when upgrading
		$settings = get_option('woocommerce_eway_payments_settings');

		$this->form_fields = [

			'enabled' => [
							'title' 		=> translate('Enable/Disable', 'woocommerce'),
							'type' 			=> 'checkbox',
							'label' 		=> esc_html__('enable eWAY credit card payment', 'eway-payment-gateway'),
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
							'default'		=> _x('Pay with your credit card using eWAY secure checkout', 'WooCommerce payment method description', 'eway-payment-gateway'),
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
							'type' 			=> 'text',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
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

			'eway_customerid' => [
							'title' 		=> _x('Customer ID', 'settings field', 'eway-payment-gateway'),
							'type' 			=> 'text',
							'description' 	=> esc_html__('Legacy connections only; please add your API key/password and Client Side Encryption key instead.', 'eway-payment-gateway'),
							'desc_tip'		=> true,
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
							'type' 			=> 'text',
							'custom_attributes'	=> [
														'autocorrect'		=> 'off',
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
								// for backwards-compatibility, Capture: "not stored", Authorize: "stored / Pre-Auth"
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
							'label' 		=> esc_html__('show the eWAY transaction ID on order emails', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'default' 		=> 'yes',
			],

			'eway_site_seal' => [
							'title' 		=> _x('Show eWAY Site Seal', 'settings field', 'eway-payment-gateway'),
							'label' 		=> esc_html__('show the eWAY site seal after the credit card fields', 'eway-payment-gateway'),
							'type' 			=> 'checkbox',
							'description' 	=> esc_html__('Add the verified eWAY Site Seal to your checkout', 'eway-payment-gateway'),
							'desc_tip'		=> true,
							'default' 		=> 'no',
			],

			'eway_site_seal_code' => [
							'type' 			=> 'textarea',
							'description' 	=> sprintf('<a href="https://www.eway.com.au/features/tools-site-seal" rel="noopener" target="_blank">%s</a>',
													esc_html__('Generate your site seal on the eWAY website, and paste it here', 'eway-payment-gateway')),
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
	public function adminSettingsScript() {
		$min	= SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "js/admin-woocommerce-settings$min.js");
		echo '</script>';
	}

	/**
	* add Name field to WooCommerce credit card form
	* @param array $fields
	* @param string $gateway
	* @return array
	*/
	public function wooCcFormFields($fields, $gateway) {
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
	* @param string $gateway
	*/
	public function wooCcFormStart($gateway) {
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
	protected function maybeEnqueueCSE() {
		$creds = $this->getApiCredentials();
		if (!empty($creds['ecrypt_key'])) {
			wp_enqueue_script('eway-payment-gateway-ecrypt');
			add_action('wp_footer', [$this, 'ecryptScript']);
		}
	}

	/**
	* configure the scripts for client-side encryption
	*/
	public function ecryptScript() {
		$creds	= $this->getApiCredentials();

		$vars = [
			'mode'		=> 'woocommerce',
			'key'		=> $creds['ecrypt_key'],
			'form'		=> 'form.checkout',
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
	* @param string $gateway
	*/
	public function wooCcFormEnd($gateway) {
		if ($gateway === $this->id) {
			if (!empty($this->settings['eway_site_seal']) && !empty($this->settings['eway_site_seal_code']) && $this->settings['eway_site_seal'] === 'yes') {
				echo $this->settings['eway_site_seal_code'];
			}
		}
	}

	/**
	* display payment form on checkout page
	*/
	public function payment_fields() {
		if ($this->eway_card_form === 'yes') {
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
	* @return array
	*/
	protected function getCardFields() {
		$postdata = new FormPost();

		if ($this->eway_card_form === 'yes') {
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

		$capture	= ($this->eway_stored  !== 'yes');
		$useSandbox	= ($this->eway_sandbox === 'yes');
		$creds		= apply_filters('woocommerce_eway_credentials', $this->getApiCredentials(), $useSandbox, $order);
		$eway		= get_api_wrapper($creds, $capture, $useSandbox);

		if (!$eway) {
			$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
			wc_add_notice(esc_html__('eWAY payments is not configured for payments yet', 'eway-payment-gateway'), 'error');
			return ['result' => 'failure'];
		}

		// allow plugins/themes to modify transaction ID; NB: must remain unique for eWAY account!
		$transactionID = apply_filters('woocommerce_eway_trans_number', $order_id);

		$eway->invoiceDescription		= get_bloginfo('name');
		$eway->invoiceReference			= $order->get_order_number();						// customer invoice reference
		$eway->transactionNumber		= $transactionID;
		$eway->cardHoldersName			= $ccfields['card_name'];
		$eway->cardNumber				= $ccfields['card_number'];
		$eway->cardExpiryMonth			= $ccfields['expiry_month'];
		$eway->cardExpiryYear			= $ccfields['expiry_year'];
		$eway->cardVerificationNumber	= $ccfields['cvn'];
		$eway->amount					= $order->get_total();
		$eway->currencyCode				= $order->get_currency();
		$eway->firstName				= $order->get_billing_first_name();
		$eway->lastName					= $order->get_billing_last_name();
		$eway->companyName				= $order->get_billing_company();
		$eway->emailAddress				= $order->get_billing_email();
		$eway->phone					= $order->get_billing_phone();
		$eway->address1					= $order->get_billing_address_1();
		$eway->address2					= $order->get_billing_address_2();
		$eway->suburb					= $order->get_billing_city();
		$eway->state					= $order->get_billing_state();
		$eway->postcode					= $order->get_billing_postcode();
		$eway->country					= $order->get_billing_country();
		$eway->countryName				= $eway->country;									// for eWAY legacy API
		$eway->comments					= $order->get_customer_note();

		// maybe send shipping details
		if ($order->get_shipping_address_1() || $order->get_shipping_address_2()) {
			$eway->hasShipping			= true;
			$eway->shipFirstName		= $order->get_shipping_first_name();
			$eway->shipLastName			= $order->get_shipping_last_name();
			$eway->shipAddress1			= $order->get_shipping_address_1();
			$eway->shipAddress2			= $order->get_shipping_address_2();
			$eway->shipSuburb			= $order->get_shipping_city();
			$eway->shipState			= $order->get_shipping_state();
			$eway->shipCountry			= $order->get_shipping_country();
			$eway->shipPostcode			= $order->get_shipping_postcode();
		}

		// convert WooCommerce country code into country name (for eWAY legacy API)
		$countries = WC()->countries->countries;
		if (isset($countries[$eway->country])) {
			$eway->countryName = $countries[$eway->country];
		}

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName				= $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription		= apply_filters('woocommerce_eway_invoice_desc', $eway->invoiceDescription, $order_id);
		$eway->invoiceReference			= apply_filters('woocommerce_eway_invoice_ref', $eway->invoiceReference, $order_id);
		$eway->options					= array_filter([
												apply_filters('woocommerce_eway_option1', '', $order_id),
												apply_filters('woocommerce_eway_option2', '', $order_id),
												apply_filters('woocommerce_eway_option3', '', $order_id),
										  ], 'strlen');

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

		try {
			$response = $eway->processPayment();

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

				if ($this->eway_stored === 'yes') {
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
					$eway->invoiceReference, $response->TransactionID, $this->eway_stored === 'yes' ? 'on-hold' : 'completed',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$order->update_status('failed', $error_msg);
				wc_add_notice($error_msg, 'error');
				$result = ['result' => 'failure'];

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $eway->invoiceReference, $response->getErrorsForLog()));
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
	* @return array
	*/
	protected function getApiCredentials() {
		$useSandbox	= ($this->eway_sandbox === 'yes');

		if (!$useSandbox) {
			$creds = [
				'api_key'		=> $this->eway_api_key,
				'password'		=> $this->eway_password,
				'ecrypt_key'	=> $this->eway_ecrypt_key,
				'customerid'	=> $this->eway_customerid,
			];
		}
		else {
			$creds = [
				'api_key'		=> $this->eway_sandbox_api_key,
				'password'		=> $this->eway_sandbox_password,
				'ecrypt_key'	=> $this->eway_sandbox_ecrypt_key,
				'customerid'	=> EWAY_PAYMENTS_TEST_CUSTOMER,
			];
		}

		return $creds;
	}

	/**
	* add the successful transaction ID to WooCommerce order emails
	* @param array $keys
	* @param bool $sent_to_admin
	* @param mixed $order
	* @return array
	*/
	public function wooEmailOrderMetaKeys($keys, $sent_to_admin, $order) {
		if (apply_filters('woocommerce_eway_email_show_trans_number', $this->eway_emails_show_txid === 'yes', $order)) {
			$order			= self::getOrder($order);
			$key			= 'Transaction ID';

			$keys[$key]		= [
				'label'		=> wptexturize($key),
				'value'		=> wptexturize(get_post_meta($order->get_id(), $key, true)),
			];
		}

		return $keys;
	}

	/**
	* get order object for order, maybe wrapping it up for legacy WooCommerce versions
	* @param int|object|WC_Order $order
	* @return WC_Order|CompatibleOrder
	*/
	protected static function getOrder($order) {
		if (is_numeric($order)) {
			// convert order number to order object
			$order = wc_get_order($order);
		}

		if (!method_exists($order, 'get_id')) {
			// wrap legacy order to provide accessor methods
			$order = new CompatibleOrder($order);
		}

		return $order;
	}

	/**
	* update order meta, handling WC 3.0 as well as legacy versions
	* @param WC_Order|CompatibleOrder $order
	* @param array $meta
	*/
	protected static function updateOrderMeta($order, $meta) {
		if (!method_exists($order, 'update_meta_data')) {
			// legacy order object does not have meta handling, so do it the old way
			foreach ($meta as $key => $value) {
				update_post_meta($order->id, $key, $value);
			}
		}
		else {
			// record custom meta against order
			foreach ($meta as $key => $value) {
				$order->update_meta_data($key, $value);
			}
			$order->save_meta_data();
		}
	}

}
