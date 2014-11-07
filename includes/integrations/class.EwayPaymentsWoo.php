<?php

/**
* payment gateway integration for WooCommerce
* @link http://wcdocs.woothemes.com/codex/extending/payment-gateway-api/
*/
class EwayPaymentsWoo extends WC_Payment_Gateway {

	/**
	* initialise gateway with custom settings
	*/
	public function __construct() {
		//~ parent::__construct();		// no parent constructor (yet!)

		$this->id						= 'eway_payments';
		$this->icon						= apply_filters('woocommerce_eway_icon', EwayPaymentsPlugin::getUrlPath() . 'images/eway-tiny.png');
		$this->method_title				= 'eWAY';
		$this->admin_page_heading 		= 'eWAY payment gateway';
		$this->admin_page_description 	= 'Integration with the eWAY Direct API credit card payment gateway.';
		$this->has_fields = true;

		// load form fields.
		$this->initFormFields();

		// load settings (via WC_Settings_API)
		$this->init_settings();

		// define user set variables
		$this->enabled				= $this->settings['enabled'];
		$this->title				= $this->settings['title'];
		$this->description			= $this->settings['description'];
		$this->availability			= $this->settings['availability'];
		$this->countries			= $this->settings['countries'];
		$this->eway_customerid		= $this->settings['eway_customerid'];
		$this->eway_sandbox			= $this->settings['eway_sandbox'];
		$this->eway_stored			= $this->settings['eway_stored'];
		$this->eway_beagle			= $this->settings['eway_beagle'];
		$this->eway_card_form		= $this->settings['eway_card_form'];
		$this->eway_card_msg		= $this->settings['eway_card_msg'];
		$this->eway_site_seal		= $this->settings['eway_site_seal'];
		$this->eway_site_seal_code	= $this->settings['eway_site_seal_code'];

		// handle support for standard WooCommerce credit card form instead of our custom template
		if ($this->eway_card_form == 'yes') {
			$this->supports[] = 'default_credit_card_form';
			add_filter('woocommerce_credit_card_form_fields', array($this, 'wooCcFormFields'), 10, 2);
			add_action('woocommerce_credit_card_form_start', array($this, 'wooCcFormStart'));
			add_action('woocommerce_credit_card_form_end', array($this, 'wooCcFormEnd'));
		}

		// add email fields
		add_filter('woocommerce_email_order_meta_keys', array($this, 'wooEmailOrderMetaKeys'));

		// save admin options, via WC_Settings_API
		// v1.6.6 and under:
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
		// v2.0+
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	* initialise settings form fields
	*/
	public function initFormFields() {
		global $woocommerce;

		// get recorded settings, so we can determine sane defaults when upgrading
		$settings = get_option('woocommerce_eway_payments_settings');

		$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> 'Enable eWAY credit card payment',
							'default' 		=> 'no',
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'desc_tip'		=> true,
							'default'		=> 'Credit card',
						),
			'description' => array(
							'title' 		=> __( 'Description', 'woocommerce' ),
							'type' 			=> 'textarea',
							'description' 	=> __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'desc_tip'		=> true,
							'default'		=> 'Pay with your credit card using eWAY secure checkout',
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __( 'All allowed countries', 'woocommerce' ),
								'specific' 	=> __( 'Specific Countries', 'woocommerce' ),
							),
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries,
						),

			'eway_customerid' => array(
							'title' 		=> 'eWAY customer ID',
							'type' 			=> 'text',
							'description' 	=> '',
							'default' 		=> EWAY_PAYMENTS_TEST_CUSTOMER,
						),
			'eway_stored' => array(
							'title' 		=> 'Stored payments',
							'label' 		=> 'enable stored payments',
							'type' 			=> 'checkbox',
							'description' 	=> "<a href='http://www.eway.com.au/how-it-works/payment-products#stored-payments' target='_blank'>Stored payments</a> records payment details but doesn't bill immediately. Useful for drop-shipping merchants.<em id='woocommerce-eway-admin-stored-test' style='color:#c00'><br />NB: Stored Payments uses the Direct Payments sandbox; there is no Stored Payments sandbox.</em>",
							'default' 		=> 'no',
						),
			'eway_sandbox' => array(
							'title' 		=> 'Sandbox mode',
							'label' 		=> 'enable sandbox (testing) mode',
							'type' 			=> 'checkbox',
							'description' 	=> 'Use the sandbox testing environment, no live payments are accepted; use test card number 4444333322221111',
							'desc_tip'		=> true,
							'default' 		=> 'yes',
						),
			'eway_beagle' => array(
							'title' 		=> 'Beagle (anti-fraud)',
							'label' 		=> 'enable Beagle (free) anti-fraud',
							'type' 			=> 'checkbox',
							'description' 	=> '<a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a> is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle rules</a> in your MYeWAY console before enabling Beagle.<em id="woocommerce-eway-admin-stored-beagle" style="color:#c00"><br />Beagle is not available for Stored Payments</em>',
							'default' 		=> 'no',
						),
			'eway_card_form' => array(
							'title' 		=> 'Credit card fields',
							'label' 		=> 'use WooCommerce standard credit card fields',
							'type' 			=> 'checkbox',
							'description' 	=> 'Ticked, the standard WooCommerce credit card fields will be used. Unticked, a custom template will be used for the credit card fields.',
							'desc_tip'		=> true,
							'default' 		=> (is_array($settings) ? 'no' : 'yes'),
						),
			'eway_card_msg' => array(
							'title' 		=> 'Credit card message',
							'type' 			=> 'text',
							'description' 	=> 'Message to show above credit card fields, e.g. "Visa and Mastercard only"',
							'desc_tip'		=> true,
							'default'		=> '',
						),
			'eway_site_seal' => array(
							'title' 		=> 'Show eWAY Site Seal',
							'label' 		=> 'show the eWAY site seal after the credit card fields',
							'type' 			=> 'checkbox',
							'description' 	=> 'Add the verified eWAY Site Seal to your checkout',
							'desc_tip'		=> true,
							'default' 		=> 'no',
						),
			'eway_site_seal_code' => array(
							'type' 			=> 'textarea',
							'description' 	=> '<a href="http://www.eway.com.au/developers/resources/site-seal-generator" target="_blank">generate your site seal on the eWAY website</a> and paste it here',
							'default'		=> '',
							'css'			=> 'height:14em',
						),
			);
	}

	/**
	* extend parent method for initialising settings, so that new settings can receive defaults
	*/
	public function init_settings() {
		parent::init_settings();


		if (is_callable(array($this, 'get_form_fields'))) {
			$form_fields = $this->get_form_fields();
		}
		else {
			// WooCommerce 2.0.20 or earlier
			$form_fields = $this->form_fields;
		}

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
		include EWAY_PAYMENTS_PLUGIN_ROOT . '/views/admin-woocommerce.php';
	}

	/**
	* add Name field to WooCommerce credit card form
	* @param array $fields
	* @param string $gateway
	* @return array
	*/
	public function wooCcFormFields($fields, $gateway) {
		if ($gateway == $this->id) {
			$fields = array_merge(array(
				'card-name-field' => '<p class="form-row form-row-wide">
					<label for="' . esc_attr( $this->id ) . '-card-name">Card Holder\'s Name <span class="required">*</span></label>
					<input id="' . esc_attr( $this->id ) . '-card-name" class="input-text wc-credit-card-form-card-name" type="text" maxlength="50" autocomplete="off" name="' . $this->id . '-card-name" />
				</p>'), $fields);
		}

		return $fields;
	}

	/**
	* show message before fields in standard WooCommerce credit card form
	* @param string $gateway
	*/
	public function wooCcFormStart($gateway) {
		if ($gateway == $this->id) {
			if (!empty($this->settings['eway_card_msg'])) {
				printf('<span class="eway-credit-card-message">%s</span>', $this->settings['eway_card_msg']);
			}
		}
	}

	/**
	* show site seal after fields in standard WooCommerce credit card form, if entered
	* @param string $gateway
	*/
	public function wooCcFormEnd($gateway) {
		if ($gateway == $this->id) {
			if (!empty($this->settings['eway_site_seal']) && !empty($this->settings['eway_site_seal_code']) && $this->settings['eway_site_seal'] == 'yes') {
				echo $this->settings['eway_site_seal_code'];
			}
		}
	}

	/**
	* display payment form on checkout page
	*/
	public function payment_fields() {
		if ($this->eway_card_form == 'yes') {
			// use standard WooCommerce credit card form
			$this->credit_card_form();
		}
		else {
			// build drop-down items for months
			$optMonths = '';
			foreach (array('01','02','03','04','05','06','07','08','09','10','11','12') as $option) {
				$optMonths .= "<option value='$option'>$option</option>\n";
			}

			// build drop-down items for years
			$thisYear = (int) date('Y');
			$optYears = '';
			foreach (range($thisYear, $thisYear + 15) as $year) {
				$optYears .= "<option value='$year'>$year</option>\n";
			}

			// load payment fields template with passed values
			$settings = $this->settings;
			EwayPaymentsPlugin::loadTemplate('woocommerce-eway-fields.php', compact('optMonths', 'optYears', 'settings'));
		}
	}

	/**
	* get field values from credit card form -- either WooCommerce standard, or the old template
	* @return array
	*/
	protected function getCardFields() {
		if ($this->eway_card_form == 'yes') {
			// split expiry field into month and year
			$expiry = self::getPostValue('eway_payments-card-expiry');
			$expiry = array_map('trim', explode('/', $expiry, 2));
			if (count($expiry) == 2) {
				// prefix year with '20' if it's exactly two digits
				if (preg_match('/^[0-9]{2}$/', $expiry[1])) {
					$expiry[1] = '20' . $expiry[1];
				}
			}
			else {
				$expiry = array('', '');
			}

			$fields = array(
				'eway_card_number'  => self::getPostValue('eway_payments-card-number'),
				'eway_card_name'    => self::getPostValue('eway_payments-card-name'),
				'eway_expiry_month' => $expiry[0],
				'eway_expiry_year'  => $expiry[1],
				'eway_cvn'          => self::getPostValue('eway_payments-card-cvc'),
			);
		}
		else {
			$fields = array(
				'eway_card_number'  => self::getPostValue('eway_card_number'),
				'eway_card_name'    => self::getPostValue('eway_card_name'),
				'eway_expiry_month' => self::getPostValue('eway_expiry_month'),
				'eway_expiry_year'  => self::getPostValue('eway_expiry_year'),
				'eway_cvn'          => self::getPostValue('eway_cvn'),
			);
		}

		return $fields;
	}

	/**
	* validate entered data for errors / omissions
	* @return bool
	*/
	public function validate_fields() {
		// check for missing or invalid values
		$errors = 0;
		$expiryError = false;
		$ccfields = $this->getCardFields();

		if ($ccfields['eway_card_number'] === '') {
			wc_add_notice('Please enter credit card number', 'error');
			$errors++;
		}

		if ($ccfields['eway_card_name'] === '') {
			wc_add_notice('Please enter card holder name', 'error');
			$errors++;
		}

		if (empty($ccfields['eway_expiry_month']) || !preg_match('/^(?:0[1-9]|1[012])$/', $ccfields['eway_expiry_month'])) {
			wc_add_notice('Please select credit card expiry month', 'error');
			$errors++;
			$expiryError = true;
		}

		// FIXME: if this code makes it into the 2100's, update this regex!
		if (empty($ccfields['eway_expiry_year']) || !preg_match('/^20\d\d$/', $ccfields['eway_expiry_year'])) {
			wc_add_notice('Please select credit card expiry year', 'error');
			$errors++;
			$expiryError = true;
		}

		if (!$expiryError) {
			// check that first day of month after expiry isn't earlier than today
			$expired = mktime(0, 0, 0, 1 + $ccfields['eway_expiry_month'], 0, $ccfields['eway_expiry_year']);
			$today = time();
			if ($expired < $today) {
				wc_add_notice('Credit card expiry has passed', 'error');
				$errors++;
			}
		}

		if ($ccfields['eway_cvn'] === '') {
			wc_add_notice('Please enter CVN (Card Verification Number)', 'error');
			$errors++;
		}

		return $errors === 0;
	}

	/**
	* process the payment and return the result
	* @param int $order_id
	* @return array
	*/
	public function process_payment($order_id) {
		global $woocommerce;

		$order = new WC_Order($order_id);
		$ccfields = $this->getCardFields();

		$isLiveSite = ($this->eway_sandbox != 'yes');

		if ($this->eway_stored == 'yes')
			$eway = new EwayPaymentsStoredPayment($this->eway_customerid, $isLiveSite);
		else
			$eway = new EwayPaymentsPayment($this->eway_customerid, $isLiveSite);

		$eway->invoiceDescription		= get_bloginfo('name');
		$eway->invoiceReference			= $order_id;										// customer invoice reference
		$eway->transactionNumber		= $order_id;										// transaction reference
		$eway->cardHoldersName			= $ccfields['eway_card_name'];
		$eway->cardNumber				= strtr($ccfields['eway_card_number'], array(' ' => '', '-' => ''));
		$eway->cardExpiryMonth			= $ccfields['eway_expiry_month'];
		$eway->cardExpiryYear			= $ccfields['eway_expiry_year'];
		$eway->cardVerificationNumber	= $ccfields['eway_cvn'];
		$eway->firstName				= $order->billing_first_name;
		$eway->lastName					= $order->billing_last_name;
		$eway->emailAddress				= $order->billing_email;
		$eway->postcode					= $order->billing_postcode;

		// for Beagle (free) security
		if ($this->eway_beagle == 'yes') {
			$eway->customerCountryCode = $order->billing_country;
		}

		// convert WooCommerce country code into country name
		$billing_country = $order->billing_country;
		if (isset($woocommerce->countries->countries[$billing_country])) {
			$billing_country = $woocommerce->countries->countries[$billing_country];
		}

		// aggregate street, city, state, country into a single string
		$parts = array (
			$order->billing_address_1,
			$order->billing_address_2,
			$order->billing_city,
			$order->billing_state,
			$billing_country,
		);
		$eway->address = implode(', ', array_filter($parts, 'strlen'));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName = $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription = apply_filters('woocommerce_eway_invoice_desc', $eway->invoiceDescription, $order_id);
		$eway->invoiceReference = apply_filters('woocommerce_eway_invoice_ref', $eway->invoiceReference, $order_id);
		$eway->option1 = apply_filters('woocommerce_eway_option1', '', $order_id);
		$eway->option2 = apply_filters('woocommerce_eway_option2', '', $order_id);
		$eway->option3 = apply_filters('woocommerce_eway_option3', '', $order_id);

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$total = $order->order_total;
		$eway->amount = $isLiveSite ? $total : ceil($total);

		try {
			$response = $eway->processPayment();

			if ($response->status) {
				// transaction was successful, so record details and complete payment
				update_post_meta($order_id, 'Transaction ID', $response->transactionNumber);
				if (!empty($response->authCode)) {
					update_post_meta($order_id, 'Authcode', $response->authCode);
				}
				if (!empty($response->beagleScore)) {
					update_post_meta($order_id, 'Beagle score', $response->beagleScore);
				}

				if ($this->eway_stored == 'yes') {
					// payment hasn't happened yet, so record status as 'on-hold' and reduce stock in anticipation
					$order->reduce_order_stock();
					$order->update_status('on-hold', 'Awaiting stored payment');
					unset($_SESSION['order_awaiting_payment']);
				}
				else {
					$order->payment_complete();
				}
				$woocommerce->cart->empty_cart();

				$result = array(
					'result' => 'success',
					'redirect' => $this->get_return_url($order),
				);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$order->update_status('failed', nl2br(esc_html($response->error)));
				wc_add_notice(nl2br(esc_html($response->error)), 'error');
				$result = array('result' => 'failure');
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$order->update_status('failed', nl2br(esc_html($e->getMessage())));
			wc_add_notice(nl2br(esc_html($e->getMessage())), 'error');
			$result = array('result' => 'failure');
		}

		return $result;
	}

	/**
	* add the successful transaction ID to WooCommerce order emails
	* @param array $keys
	* @return array
	*/
	public function wooEmailOrderMetaKeys( $keys ) {
		$keys[] = 'Transaction ID';

		return $keys;
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
	* Read a field from form post input.
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	* @param string $fieldname name of the field in the form post
	* @return string
	*/
	protected static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? wp_unslash(trim((string) $_POST[$fieldname])) : '';
	}

}
