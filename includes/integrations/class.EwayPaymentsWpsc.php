<?php

/**
* payment gateway integration for WP e-Commerce
* @link http://docs.getshopped.org/category/developer-documentation/
*/
class EwayPaymentsWpsc extends wpsc_merchant {

	public $name = 'eway';

	const WPSC_GATEWAY_NAME = 'wpsc_merchant_eway';

	/**
	* register new payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public static function register($gateways) {
		// register the gateway class and additional functions
		$gateways[] = array (
			'name'						=> 'eWAY payment gateway',
			'api_version'				=> 2.0,
			'image'						=> EwayPaymentsPlugin::getUrlPath() . 'images/eway-tiny.png',
			'internalname'				=> self::WPSC_GATEWAY_NAME,
			'class_name'				=> __CLASS__,
			'has_recurring_billing'		=> false,
			'wp_admin_cannot_cancel'	=> true,
			'display_name'				=> 'eWAY Credit Card Payment',
			'form'						=> 'EwayPaymentsWpsc_configForm',		// called as variable function name, wp-e-commerce is _doing_it_wrong(), again!
			'submit_function'			=> array(__CLASS__, 'saveConfig'),
			'payment_type'				=> 'credit_card',
			'requirements'				=> array(
												'php_version' => 5.2,
											),
		);

		// register extra fields we require on the checkout form
		self::setCheckoutFields();

		// also register admin hooks if required
		if (is_admin()) {
			add_action('wpsc_billing_details_bottom', array(__CLASS__, 'actionBillingDetailsBottom'));
		}

		return $gateways;
	}

	/**
	* grab the gateway-specific data from the checkout form post
	*/
	public function construct_value_array() {
		$country_field = get_option('eway_form_country');
		if ($country_field && !empty($_POST['collected_data'][$country_field][0])) {
			$country = wp_unslash($_POST['collected_data'][$country_field][0]);
		}
		else {
			$country = '';
		}

		$this->collected_gateway_data = array (
			'card_number'	=> strtr(self::getPostValue('card_number'), array(' ' => '', '-' => '')),
			'card_name'		=> self::getPostValue('card_name'),
			'expiry_month'	=> self::getPostValue('expiry_month'),
			'expiry_year'	=> self::getPostValue('expiry_year'),
			'c_v_n'			=> self::getPostValue('cvn'),

			// additional fields from checkout
			'first_name'	=> self::getCollectedDataValue(get_option('eway_form_first_name')),
			'last_name'		=> self::getCollectedDataValue(get_option('eway_form_last_name')),
			'address'		=> self::getCollectedDataValue(get_option('eway_form_address')),
			'city'			=> self::getCollectedDataValue(get_option('eway_form_city')),
			'state'			=> self::getCollectedDataValue(get_option('eway_form_state')),
			'country'		=> $country,
			'post_code'		=> self::getCollectedDataValue(get_option('eway_form_post_code')),
			'email'			=> self::getCollectedDataValue(get_option('eway_form_email')),
		);
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
		return isset($_POST[$fieldname]) ? wp_unslash(trim($_POST[$fieldname])) : '';
	}

	/**
	* Read a field from form post input.
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	protected static function getCollectedDataValue($fieldname) {
		return isset($_POST['collected_data'][$fieldname]) ? wp_unslash(trim($_POST['collected_data'][$fieldname])) : '';
	}

	/**
	* submit to gateway
	*/
	public function submit() {
		global $wpdb;

		// check for missing or invalid values
		$errors = $this->validateData();

		// if there were errors, fail the transaction so that user can fix things up
		if ($errors) {
			$this->set_purchase_processed_by_purchid(1);	// failed
			//~ $this->go_to_transaction_results($this->cart_data['session_id']);
			return;
		}

		// get purchase logs
		if ($this->purchase_id > 0) {
			$sql = 'select totalprice from `' . WPSC_TABLE_PURCHASE_LOGS . '` where id = %d';
			$purchase_logs = $wpdb->get_row($wpdb->prepare($sql, $this->purchase_id), ARRAY_A);
		}
		elseif (!empty($this->session_id)) {
			$sql = 'select id,totalprice from `' . WPSC_TABLE_PURCHASE_LOGS . '` where sessionid = %s limit 1';
			$purchase_logs = $wpdb->get_row($wpdb->prepare($sql, $this->session_id), ARRAY_A);

			$this->purchase_id = $purchase_logs['id'];
		}
		else {
			$this->set_error_message('No cart ID and no active session!');
			return;
		}

		// process the payment
		$isLiveSite = !get_option('eway_test');
		$useStored = get_option('wpsc_merchant_eway_stored');

		if ($useStored) {
			$eway = new EwayPaymentsStoredPayment(get_option('ewayCustomerID_id'), $isLiveSite);
		}
		else {
			$eway = new EwayPaymentsPayment(get_option('ewayCustomerID_id'), $isLiveSite);
		}

		$eway->invoiceDescription		= get_bloginfo('name');
		$eway->invoiceReference			= $this->purchase_id;								// customer invoice reference
		$eway->transactionNumber		= $this->purchase_id;								// transaction reference
		$eway->cardHoldersName			= $this->collected_gateway_data['card_name'];
		$eway->cardNumber				= $this->collected_gateway_data['card_number'];
		$eway->cardExpiryMonth			= $this->collected_gateway_data['expiry_month'];
		$eway->cardExpiryYear			= $this->collected_gateway_data['expiry_year'];
		$eway->cardVerificationNumber	= $this->collected_gateway_data['c_v_n'];
		$eway->firstName				= $this->collected_gateway_data['first_name'];
		$eway->lastName					= $this->collected_gateway_data['last_name'];
		$eway->emailAddress				= $this->collected_gateway_data['email'];
		$eway->postcode					= $this->collected_gateway_data['post_code'];

		// for Beagle (free) security
		if (get_option('wpsc_merchant_eway_beagle')) {
			$eway->customerCountryCode	= $this->collected_gateway_data['country'];
		}

		// convert wp-e-commerce country code into country name
		$country = $this->collected_gateway_data['country'] ? wpsc_get_country($this->collected_gateway_data['country']) : '';

		// aggregate street, city, state, country into a single string
		$parts = array (
			$this->collected_gateway_data['address'],
			$this->collected_gateway_data['city'],
			$this->collected_gateway_data['state'],
			$country,
		);
		$eway->address = implode(', ', array_filter($parts, 'strlen'));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName = $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription = apply_filters('wpsc_merchant_eway_invoice_desc', $eway->invoiceDescription, $this->purchase_id);
		$eway->invoiceReference = apply_filters('wpsc_merchant_eway_invoice_ref', $eway->invoiceReference, $this->purchase_id);
		$eway->option1 = apply_filters('wpsc_merchant_eway_option1', '', $this->purchase_id);
		$eway->option2 = apply_filters('wpsc_merchant_eway_option2', '', $this->purchase_id);
		$eway->option3 = apply_filters('wpsc_merchant_eway_option3', '', $this->purchase_id);

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$total = $purchase_logs['totalprice'];
		$eway->amount = $isLiveSite ? $total : ceil($total);

		try {
			$response = $eway->processPayment();

			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				if ($useStored) {
					$status = 2; // WPSC_Purchase_Log::ORDER_RECEIVED
				}
				else {
					$status = 3; // WPSC_Purchase_Log::ACCEPTED_PAYMENT
				}

				$this->set_transaction_details($response->transactionNumber, $status);
				$this->set_authcode($response->authCode);
				if (!empty($response->beagleScore)) {
					$this->setPaymentNotes('Beagle score: ' . $response->beagleScore);
				}
				$this->go_to_transaction_results($this->cart_data['session_id']);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$status = 6; // WPSC_Purchase_Log::PAYMENT_DECLINED
				$this->set_error_message(nl2br(esc_html($response->error)));
				$this->setPaymentNotes($response->error);
				$this->set_purchase_processed_by_purchid($status);
				return;
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$status = 1; // WPSC_Purchase_Log::INCOMPLETE_SALE
			$this->set_error_message(nl2br(esc_html($e->getMessage())));
			$this->set_purchase_processed_by_purchid($status);
			return;
		}

	 	exit();
	}

	/**
	* parse gateway notification, recieves and converts the notification to an array, if possible
	* @return boolean
	*/
	public function parse_gateway_notification() {
		return false;
	}

	/**
	* process gateway notification, checks and decides what to do with the data from the gateway
	* @return boolean
	*/
	public function process_gateway_notification() {
		return false;
	}

	/**
	* validate entered data for errors / omissions
	* @return int number of errors found
	*/
	protected function validateData() {
		// check for missing or invalid values
		$errors = 0;
		$expiryError = FALSE;

		if (empty($this->collected_gateway_data['card_number'])) {
			$this->set_error_message('Please enter credit card number');
			$errors++;
		}

		if (empty($this->collected_gateway_data['card_name'])) {
			$this->set_error_message('Please enter card holder name');
			$errors++;
		}

		if (empty($this->collected_gateway_data['expiry_month']) || !preg_match('/^(?:0[1-9]|1[012])$/', $this->collected_gateway_data['expiry_month'])) {
			$this->set_error_message('Please select credit card expiry month');
			$errors++;
			$expiryError = TRUE;
		}

		// FIXME: if this code makes it into the 2100's, update this regex!
		if (empty($this->collected_gateway_data['expiry_year']) || !preg_match('/^20\d\d$/', $this->collected_gateway_data['expiry_year'])) {
			$this->set_error_message('Please select credit card expiry year');
			$errors++;
			$expiryError = TRUE;
		}

		if (!$expiryError) {
			// check that first day of month after expiry isn't earlier than today
			$expired = mktime(0, 0, 0, 1 + $this->collected_gateway_data['expiry_month'], 0, $this->collected_gateway_data['expiry_year']);
			$today = time();
			if ($expired < $today) {
				$this->set_error_message('Credit card expiry has passed');
				$errors++;
			}
		}

		if (empty($this->collected_gateway_data['c_v_n'])) {
			$this->set_error_message('Please enter CVN (Card Verification Number)');
			$errors++;
		}

		return $errors;
	}

	/**
	* update payment log notes (seems to be missing functionality in wpsc)
	* @param string $notes
	*/
	protected function setPaymentNotes($notes) {
		global $wpdb;

		$wpdb->update(WPSC_TABLE_PURCHASE_LOGS,
			array('notes' => $notes),
			array('id' => $this->purchase_id),
			array('%s'),
			array('%d')
		);
	}

	/**
	* tell wp-e-commerce about fields we require on the checkout form
	*/
	protected static function setCheckoutFields() {
		global $gateway_checkout_form_fields;

		// check if this gateway is selected for checkout payments
		if (in_array(self::WPSC_GATEWAY_NAME, (array) get_option('custom_gateway_options'))) {
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

			// use TH for field label cells if selected, otherwise use TD (default wp-e-commerce behaviour)
			$th = get_option('wpsc_merchant_eway_th') ? 'th' : 'td';

			// optional message to show above credit card fields
			$card_msg = esc_html(get_option('wpsc_merchant_eway_card_msg'));

			// load template with passed values, capture output and register
			ob_start();
			EwayPaymentsPlugin::loadTemplate('wpsc-eway-fields.php', compact('th', 'card_msg', 'optMonths', 'optYears'));
			$gateway_checkout_form_fields[self::WPSC_GATEWAY_NAME] = ob_get_clean();
		}
	}

	/**
	* display additional fields for gateway config form
	* return string
	*/
	public static function configForm() {
		ob_start();
		include EWAY_PAYMENTS_PLUGIN_ROOT . '/views/admin-wpsc.php';
		return ob_get_clean();
	}

	/**
	* save config details from payment gateway admin
	*/
	public static function saveConfig() {
		if (isset($_POST['ewayCustomerID_id'])) {
			update_option('ewayCustomerID_id', $_POST['ewayCustomerID_id']);
		}

		if (isset($_POST['eway_stored'])) {
			update_option('wpsc_merchant_eway_stored', $_POST['eway_stored']);
		}

		if (isset($_POST['eway_test'])) {
			update_option('eway_test', $_POST['eway_test']);
		}

		if (isset($_POST['eway_th'])) {
			update_option('wpsc_merchant_eway_th', $_POST['eway_th']);
		}

		if (isset($_POST['eway_beagle'])) {
			update_option('wpsc_merchant_eway_beagle', $_POST['eway_beagle']);
		}

		if (isset($_POST['eway_card_msg'])) {
			update_option('wpsc_merchant_eway_card_msg', $_POST['eway_card_msg']);
		}

		foreach ((array)$_POST['eway_form'] as $form => $value) {
			update_option(('eway_form_'.$form), $value);
		}

		return true;
	}

	/**
	* hook billing details display on admin, to show eWAY transaction number and authcode
	*/
	public static function actionBillingDetailsBottom() {
		global $purchlogitem;

		if (!empty($purchlogitem->extrainfo->transactid) || !empty($purchlogitem->extrainfo->authcode)) {
			include EWAY_PAYMENTS_PLUGIN_ROOT . '/views/admin-wpsc-billing-details.php';
		}
	}
}

/**
* proxy function for calling class method, because wp-e-commerce is _doing_it_wrong(), again!
* @return string
*/
function EwayPaymentsWpsc_configForm() {
	return EwayPaymentsWpsc::configForm();
}
