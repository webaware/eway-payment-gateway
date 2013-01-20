<?php

/**
* implement bulk of gateway
*/
class wpsc_merchant_eway extends wpsc_merchant {

	public $name = 'eway';

	/**
	* grab the gateway-specific data from the checkout form post
	*/
	public function construct_value_array() {
		$this->collected_gateway_data = array (
			'card_number' => self::getPostValue('card_number'),
			'card_name' => self::getPostValue('card_name'),
			'expiry_month' => self::getPostValue('expiry_month'),
			'expiry_year' => self::getPostValue('expiry_year'),
			'c_v_n' => self::getPostValue('cvn'),

			// additional fields from checkout
			'first_name' => self::getCollectedDataValue(get_option('eway_form_first_name')),
			'last_name' => self::getCollectedDataValue(get_option('eway_form_last_name')),
			'address' => self::getCollectedDataValue(get_option('eway_form_address')),
			'city' => self::getCollectedDataValue(get_option('eway_form_city')),
			'state' => self::getCollectedDataValue(get_option('eway_form_state')),
			'country' => @stripslashes($_POST['collected_data'][get_option('eway_form_country')][0]),
			'post_code' => self::getCollectedDataValue(get_option('eway_form_post_code')),
			'email' => self::getCollectedDataValue(get_option('eway_form_email')),
		);

		// convert wp-e-commerce country code into country name
		if ($this->collected_gateway_data['country']) {
			$this->collected_gateway_data['country'] = wpsc_get_country($this->collected_gateway_data['country']);
		}
	}

	/**
	* Read a field from form post input.
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	private static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? stripslashes(trim($_POST[$fieldname])) : '';
	}

	/**
	* Read a field from form post input.
	*
	* Guaranteed to return a string, trimmed of leading and trailing spaces, sloshes stripped out.
	*
	* @return string
	* @param string $fieldname name of the field in the form post
	*/
	private static function getCollectedDataValue($fieldname) {
		return isset($_POST['collected_data'][$fieldname]) ? stripslashes(trim($_POST['collected_data'][$fieldname])) : '';
	}

	/**
	* submit to gateway
	*/
	public function submit() {
		global $wpdb;

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

		if (empty($this->collected_gateway_data['expiry_month']) || !preg_match('/^(?:0[0-9]|1[012])$/', $this->collected_gateway_data['expiry_month'])) {
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
			// make sure hasn't expired!
			if ($this->collected_gateway_data['expiry_month'] === '12')
				$expired = mktime(0, 0, 0, 1, 0, 1 + $this->collected_gateway_data['expiry_year']);
			else
				$expired = mktime(0, 0, 0, 1 + $this->collected_gateway_data['expiry_month'], 0, $this->collected_gateway_data['expiry_year']);
			if ($expired == 0) {
				$this->set_error_message('Credit card expiry month or year is invalid');
				$errors++;
			}
			else {
				$today = time();
				if ($expired < $today) {
					$this->set_error_message('Credit card expiry has passed');
					$errors++;
				}
			}
		}

		if (empty($this->collected_gateway_data['c_v_n'])) {
			$this->set_error_message('Please enter CVN (Card Verification Number)');
			$errors++;
		}

		// if there were errors, fail the transaction so that user can fix things up
		if ($errors) {
			$this->set_purchase_processed_by_purchid(1);	// failed
			//~ $this->go_to_transaction_results($this->cart_data['session_id']);
			return;
		}

		// get purchase logs
		if ($this->purchase_id > 0) {
			$purchase_logs = $wpdb->get_row(
				$wpdb->prepare('select totalprice from `' . WPSC_TABLE_PURCHASE_LOGS . '` where id = %d', $this->purchase_id), ARRAY_A);
		}
		elseif (!empty($this->session_id)) {
			$purchase_logs = $wpdb->get_row(
				$wpdb->prepare('select id,totalprice from `' . WPSC_TABLE_PURCHASE_LOGS . '` where sessionid = %s limit 1', $this->session_id),
				ARRAY_A);

			$this->purchase_id = $purchase_logs['id'];
		}

		// process the payment
		$isLiveSite = !get_option('eway_test');
		$useStored = get_option('wpsc_merchant_eway_stored');

		if ($useStored)
			$eway = new wpsc_merchant_eway_stored_payment(get_option('ewayCustomerID_id'), $isLiveSite);
		else
			$eway = new wpsc_merchant_eway_payment(get_option('ewayCustomerID_id'), $isLiveSite);

		$eway->invoiceDescription = get_bloginfo('name');
		$eway->invoiceReference = $this->purchase_id;
		$eway->cardHoldersName = $this->collected_gateway_data['card_name'];
		$eway->cardNumber = $this->collected_gateway_data['card_number'];
		$eway->cardExpiryMonth = $this->collected_gateway_data['expiry_month'];
		$eway->cardExpiryYear = $this->collected_gateway_data['expiry_year'];
		$eway->cardVerificationNumber = $this->collected_gateway_data['c_v_n'];
		$eway->firstName = $this->collected_gateway_data['first_name'];
		$eway->lastName = $this->collected_gateway_data['last_name'];
		$eway->emailAddress = $this->collected_gateway_data['email'];
		$eway->postcode = $this->collected_gateway_data['post_code'];

		// aggregate street, city, state, country into a single string
		$parts = array (
			$this->collected_gateway_data['address'],
			$this->collected_gateway_data['city'],
			$this->collected_gateway_data['state'],
			$this->collected_gateway_data['country'],
		);
		$eway->address = implode(', ', array_filter($parts, 'strlen'));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName = $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription = apply_filters('wpsc_merchant_eway_invoice_desc', $eway->invoiceDescription);
		$eway->invoiceReference = apply_filters('wpsc_merchant_eway_invoice_ref', $eway->invoiceReference);
		$eway->option1 = apply_filters('wpsc_merchant_eway_option1', '');
		$eway->option2 = apply_filters('wpsc_merchant_eway_option2', '');
		$eway->option3 = apply_filters('wpsc_merchant_eway_option3', '');

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$total = $purchase_logs['totalprice'];
		$eway->amount = $isLiveSite ? $total : ceil($total);

//~ $this->set_error_message(htmlspecialchars($eway->getPaymentXML()));
//~ $this->set_purchase_processed_by_purchid(1);
//~ return;

		try {
			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				if ($useStored)
					$status = class_exists('WPSC_Purchase_Log') ? WPSC_Purchase_Log::ORDER_RECEIVED : 2;
				else
					$status = class_exists('WPSC_Purchase_Log') ? WPSC_Purchase_Log::ACCEPTED_PAYMENT : 3;
				$this->set_transaction_details($response->transactionNumber, $status);
				$this->go_to_transaction_results($this->cart_data['session_id']);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$status = class_exists('WPSC_Purchase_Log') ? WPSC_Purchase_Log::INCOMPLETE_SALE : 1;
				$this->set_error_message(htmlspecialchars($response->error));
				$this->set_purchase_processed_by_purchid($status);
				return;
			}
		}
		catch (wpsc_merchant_eway_exception $e) {
			// an exception occured, so record the error
			$status = class_exists('WPSC_Purchase_Log') ? WPSC_Purchase_Log::INCOMPLETE_SALE : 1;
			$this->set_error_message(htmlspecialchars($e->getMessage()));
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
	* tell wp-e-commerce about fields we require on the checkout form
	*/
	public static function setCheckoutFields() {
		global $gateway_checkout_form_fields;

		// check if this gateway is selected for checkout payments
		if (in_array(WPSC_MERCH_EWAY_GATEWAY_NAME, (array) get_option('custom_gateway_options'))) {
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

			// load template with passed values, capture output and register
			ob_start();
			self::loadTemplate('wpsc-eway-fields.php', compact('th', 'optMonths', 'optYears'));
			$gateway_checkout_form_fields[WPSC_MERCH_EWAY_GATEWAY_NAME] = ob_get_clean();
		}
	}

	/**
	* load template from theme or plugin
	* can't use locate_template() because wp-e-commerce is _doing_it_wrong() again!
	* so have to roll our own...
	* @param string $template name of template file
	* @param array $variables an array of variables that should be accessible by the template
	*/
	protected static function loadTemplate($template, $variables) {
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
				$template = WPSC_MERCH_EWAY_PLUGIN_ROOT . 'templates/' . $template;
			}
		}

		include $template;
	}
}
