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
		$this->collected_gateway_data = array(
			'card_number' => stripslashes($_POST['card_number']),
			'card_name' => stripslashes($_POST['card_name']),
			'expiry_month' => stripslashes($_POST['expiry_month']),
			'expiry_year' => stripslashes($_POST['expiry_year']),
			'c_v_n' => stripslashes($_POST['cvn']),

			// additional fields from checkout, required for eWAY processing
			'address' => @stripslashes($_POST['collected_data'][get_option('eway_form_address')]),
			'city' => @stripslashes($_POST['collected_data'][get_option('eway_form_city')]),
			'state' => @stripslashes($_POST['collected_data'][get_option('eway_form_state')]),
			'country' => @stripslashes($_POST['collected_data'][get_option('eway_form_country')][0]),
			'post_code' => @stripslashes($_POST['collected_data'][get_option('eway_form_post_code')]),
			'email' => @stripslashes($_POST['collected_data'][get_option('eway_form_email')]),
		);
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

		if (get_option('eway_cvn') && empty($this->collected_gateway_data['c_v_n'])) {
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
				$wpdb->prepare('select * from `' . WPSC_TABLE_PURCHASE_LOGS . '` where id = %d', $this->purchase_id),
				ARRAY_A);
		}
		elseif (!empty($this->session_id)) {
			$purchase_logs = $wpdb->get_row(
				$wpdb->prepare('select * from `' . WPSC_TABLE_PURCHASE_LOGS . '` where sessionid = %s limit 1', $this->session_id),
				ARRAY_A);

			$this->purchase_id = $purchase_logs['id'];
		}

		// process the payment
		$isLiveSite = !get_option('eway_test');
		$eway = new wpsc_merchant_eway_payment(get_option('ewayCustomerID_id'), $isLiveSite);
		$eway->invoiceDescription = get_bloginfo('name');
		$eway->transactionNumber = $purchase_logs['id'];
		$eway->cardHoldersName = $this->collected_gateway_data['card_name'];
		$eway->cardNumber = $this->collected_gateway_data['card_number'];
		$eway->cardExpiryMonth = $this->collected_gateway_data['expiry_month'];
		$eway->cardExpiryYear = $this->collected_gateway_data['expiry_year'];
		$eway->cardVerificationNumber = $this->collected_gateway_data['c_v_n'];
		$eway->emailAddress = $this->collected_gateway_data['email'];
		$eway->address = trim($this->collected_gateway_data['address']
			. ' ' . $this->collected_gateway_data['city']
			. ' ' . $this->collected_gateway_data['state']
			. ' ' . $this->collected_gateway_data['country']);
		$eway->postcode = $this->collected_gateway_data['post_code'];

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$total = $purchase_logs['totalprice'];
		$eway->amount = $isLiveSite ? $total : ceil($total);

		try {
			$response = $eway->processPayment();
			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$this->set_transaction_details($response->transactionNumber, 2);	// succeeded
				$this->go_to_transaction_results($this->cart_data['session_id']);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$this->set_error_message($response->error);
				$this->set_purchase_processed_by_purchid(1);	// failed
				return;
			}
		}
		catch (Exception $e) {
			// an exception occured, so record the error
			$this->set_error_message($e->getMessage());
			$this->set_purchase_processed_by_purchid(1);	// failed
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
	* @return string
	*/
	public static function getCheckoutFields() {
		// try to get the collected data from the form post, if any
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$values = array(
				'card_number' => @htmlspecialchars(stripslashes($_POST['card_number'])),
				'card_name' => @htmlspecialchars(stripslashes($_POST['card_name'])),
				'expiry_month' => @htmlspecialchars(stripslashes($_POST['expiry_month'])),
				'expiry_year' => @htmlspecialchars(stripslashes($_POST['expiry_year'])),
				'c_v_n' => @htmlspecialchars(stripslashes($_POST['cvn'])),
			);
		}
		else {
			$values = array(
				'card_number' => '', 'card_name' => '', 'expiry_month' => '', 'expiry_year' => '', 'c_v_n' => '',
			);
		}

		$optMonths = '';
		foreach (array('01','02','03','04','05','06','07','08','09','10','11','12') as $option) {
			$optMonths .= '<option value="' . htmlentities($option) . '"';
			if ($option == $values['expiry_month'])
				$optMonths .= ' selected="selected"';
			$optMonths .= '>' . htmlentities($option) . "</option>\n";
		}

		$thisYear = (int) date('Y');
		$maxYear = $thisYear + 15;
		$optYears = '';
		for ($year = $thisYear; $year <= $maxYear; ++$year) {
			$optYears .= "<option value='$year'";
			if ($year == $values['expiry_year'])
				$optYears .= ' selected="selected"';
			$optYears .= ">$year</option>\n";
		}

		// use TH for field label cells if selected, otherwise use TD (default wp-e-commerce behaviour)
		$th = get_option('wpsc_merchant_eway_th') ? 'th' : 'td';

		$checkoutFields = <<<EOT
<tr class='wpsc-merch-eway-row'>
	<$th><label>Credit Card Number <span class='asterix'>*</span></label></$th>
	<td>
		<input type='text' value='' name='card_number' id='eway_card_number' value="{$values['card_number']}" />
	</td>
</tr>
<tr class='wpsc-merch-eway-row'>
	<$th><label>Card Holder's Name <span class='asterix'>*</span></label></$th>
	<td>
		<input type='text' value='' name='card_name' id='eway_card_name' value="{$values['card_name']}" />
	</td>
</tr>
<tr class='wpsc-merch-eway-row'>
	<$th><label>Credit Card Expiry <span class='asterix'>*</span></label></$th>
	<td style='white-space: nowrap'>
	<select class='wpsc_ccBox' name='expiry_month' style='width: 4em'>
		$optMonths
	</select>/<select class='wpsc_ccBox' name='expiry_year' style='width: 5em'>
		$optYears
	</select>
	</td>
</tr>

EOT;

		if (get_option('eway_cvn')) {
			$checkoutFields .= <<<EOT
<tr class='wpsc-merch-eway-row'>
	<$th><label id='eway_cvn'>CVN <span class='asterix'>*</span></label></$th>
	<td>
		<input type='text' size='4' maxlength='4' value='' name='cvn' id='eway_cvn' />
	</td>
</tr>

EOT;
		}

		return $checkoutFields;
	}

}
