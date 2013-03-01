<?php

/**
* payment gateway integration for WooCommerce
* @ref http://wcdocs.woothemes.com/codex/extending/payment-gateway-api/
*/
class EwayPaymentsWoo extends WC_Payment_Gateway {

	/**
	* initialise gateway with custom settings
	*/
	public function __construct() {
		//~ parent::__construct();		// no parent constructor (yet!)

		$this->id						= 'eway_payments';
		$this->method_title				= 'eWAY';
		$this->admin_page_heading 		= 'eWAY payment gateway';
		$this->admin_page_description 	= 'Integration with the eWAY Direct API credit card payment gateway.';
		$this->has_fields = true;

		// load form fields.
		$this->initFormFields();

		// load settings (via WC_Settings_API)
		$this->init_settings();

		// Define user set variables
		$this->enabled			= $this->settings['enabled'];
		$this->title			= $this->settings['title'];
		$this->description		= $this->settings['description'];
		$this->availability		= $this->settings['availability'];
		$this->countries		= $this->settings['countries'];
		$this->eway_customerid	= $this->settings['eway_customerid'];
		$this->eway_sandbox		= $this->settings['eway_sandbox'];
		$this->eway_stored		= $this->settings['eway_stored'];
		$this->eway_beagle		= $this->settings['eway_beagle'];

		// hook some actions / filters
		add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));	// save admin options, via WC_Settings_API
		add_filter('woocommerce_email_order_meta_keys', array($this, 'filterWooEmailOrderMetaKeys'));		// add email fields
	}

	/**
	* initialise settings form fields
	*/
	public function initFormFields() {
		global $woocommerce;

		$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
							'default' 		=> 'no',
						),
			'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> 'Credit card (eWAY)',
						),
			'description' => array(
							'title' 		=> __( 'Description', 'woocommerce' ),
							'type' 			=> 'textarea',
							'description' 	=> __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
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
							'default' 		=> '87654321',
						),
			'eway_stored' => array(
							'title' 		=> 'Stored payments',
							'label' 		=> 'enable stored payments',
							'type' 			=> 'checkbox',
							'description' 	=> "<a href='http://www.eway.com.au/how-it-works/what-products-are-included-#stored-payments' target='_blank'>Stored payments</a> records payment details but doesn't bill immediately. Useful for drop-shipping merchants.<em id='woocommerce-eway-admin-stored-test' style='color:#c00'><br />NB: Stored Payments uses the Direct Payments sandbox; there is no Stored Payments sandbox.</em>",
							'default' 		=> 'no',
						),
			'eway_sandbox' => array(
							'title' 		=> 'Sandbox mode',
							'label' 		=> 'enable sandbox (testing) mode',
							'type' 			=> 'checkbox',
							'description' 	=> 'Use the sandbox testing environment, no live payments are accepted; use test card number 4444333322221111',
							'default' 		=> 'yes',
						),
			'eway_beagle' => array(
							'title' 		=> 'Beagle (anti-fraud)',
							'label' 		=> 'enable Beagle (free) anti-fraud',
							'type' 			=> 'checkbox',
							'description' 	=> '<a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle</a> is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure <a href="http://www.eway.com.au/developers/resources/beagle-(free)-rules" target="_blank">Beagle rules</a> in your MYeWAY console before enabling Beagle<em id="woocommerce-eway-admin-stored-beagle" style="color:#c00"><br />Beagle is not available for Stored Payments</em>',
							'default' 		=> 'no',
						),
			);
	}

	/**
	* show the admin panel for setting plugin options
	*/
	public function admin_options() {
		include EWAY_PAYMENTS_PLUGIN_ROOT . '/views/admin-woocommerce.php';
	}

	/**
	* display payment form on checkout page
	*/
	public function payment_fields() {
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

		// load template with passed values, capture output and register
		EwayPaymentsPlugin::loadTemplate('woocommerce-eway-fields.php', compact('optMonths', 'optYears'));
	}

	/**
	* validate entered data for errors / omissions
	* @return bool
	*/
	public function validate_fields() {
		global $woocommerce;

		// check for missing or invalid values
		$errors = 0;
		$expiryError = false;

		if (self::getPostValue('eway_card_number') === '') {
			$woocommerce->add_error('Please enter credit card number');
			$errors++;
		}

		if (self::getPostValue('eway_card_name') === '') {
			$woocommerce->add_error('Please enter card holder name');
			$errors++;
		}

		$eway_expiry_month = self::getPostValue('eway_expiry_month');
		if (empty($eway_expiry_month) || !preg_match('/^(?:0[1-9]|1[012])$/', $eway_expiry_month)) {
			$woocommerce->add_error('Please select credit card expiry month');
			$errors++;
			$expiryError = true;
		}

		// FIXME: if this code makes it into the 2100's, update this regex!
		$eway_expiry_year = self::getPostValue('eway_expiry_year');
		if (empty($eway_expiry_year) || !preg_match('/^20\d\d$/', $eway_expiry_year)) {
			$woocommerce->add_error('Please select credit card expiry year');
			$errors++;
			$expiryError = true;
		}

		if (!$expiryError) {
			// check that first day of month after expiry isn't earlier than today
			$expired = mktime(0, 0, 0, 1 + $eway_expiry_month, 0, $eway_expiry_year);
			$today = time();
			if ($expired < $today) {
				$woocommerce->add_error('Credit card expiry has passed');
				$errors++;
			}
		}

		if (self::getPostValue('eway_cvn') === '') {
			$woocommerce->add_error('Please enter CVN (Card Verification Number)');
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

		$isLiveSite = ($this->eway_sandbox != 'yes');

		if ($this->eway_stored == 'yes')
			$eway = new EwayPaymentsStoredPayment($this->eway_customerid, $isLiveSite);
		else
			$eway = new EwayPaymentsPayment($this->eway_customerid, $isLiveSite);

		$eway->invoiceDescription = get_bloginfo('name');
		$eway->invoiceReference = $order_id;										// customer invoice reference
		$eway->transactionNumber = $order_id;										// transaction reference
		$eway->cardHoldersName = self::getPostValue('eway_card_name');
		$eway->cardNumber = self::getPostValue('eway_card_number');
		$eway->cardExpiryMonth = self::getPostValue('eway_expiry_month');
		$eway->cardExpiryYear = self::getPostValue('eway_expiry_year');
		$eway->cardVerificationNumber = self::getPostValue('eway_cvn');
		$eway->firstName = $order->billing_first_name;
		$eway->lastName = $order->billing_last_name;
		$eway->emailAddress = $order->billing_email;
		$eway->postcode = $order->billing_postcode;

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

//~ error_log(__METHOD__ . "\n" . print_r($eway,1));
//~ error_log(__METHOD__ . "\n" . $eway->getPaymentXML());
//~ return array('result' => 'failure');

		try {
			$response = $eway->processPayment();

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

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

				$url = add_query_arg(array('key' => $order->order_key, 'order' => $order->id), get_permalink(woocommerce_get_page_id('thanks')));
				$result = array('result' => 'success', 'redirect' => $url);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$order->update_status('failed', nl2br(htmlspecialchars($response->error)));
				$woocommerce->add_error(nl2br(htmlspecialchars($response->error)));
				$result = array('result' => 'failure');
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$order->update_status('failed', nl2br(htmlspecialchars($e->getMessage())));
			$woocommerce->add_error(nl2br(htmlspecialchars($e->getMessage())));
			$result = array('result' => 'failure');
		}

		return $result;
    }

	/**
	* add the successful transaction ID to WooCommerce order emails
	* @param array $keys
	* @return array
	*/
	public function filterWooEmailOrderMetaKeys( $keys ) {
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
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	protected static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? stripslashes(trim($_POST[$fieldname])) : '';
	}
}
