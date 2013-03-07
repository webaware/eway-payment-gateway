<?php

/**
* payment gateway integration for Another WordPress Classifieds Plugin
* @ref http://www.awpcp.com/
*/
class EwayPaymentsAWPCP {

	const PAYMENT_METHOD = 'eway';

	/**
	* initialise gateway with custom settings
	*/
	public function __construct() {
		// admin actions / filters
		add_action('awpcp_register_settings', array($this, 'awpcpRegisterSettings'));
		//~ add_filter('awpcp_validate_settings_payment-settings', array($this, 'awpcpValidateSettings'), 10, 2);

		// front end actions / filters
		add_filter('awpcp-payment-methods', array($this, 'awpcpPaymentMethods'), 20);
		add_filter('awpcp-place-ad-checkout-step-form-text', array($this, 'awpcpCheckoutStepText'), 10, 3);
		add_filter('awpcp-checkout-form', array($this, 'awpcpCheckoutForm'), 10, 2);
		add_filter('awpcp-payments-verify-transaction', array($this, 'awpcpVerifyTx'), 10, 2);
		add_filter('awpcp-payments-validate-transaction', array($this, 'awpcpValidateTx'), 10, 2);

		// make sure we have jQuery so that checkout form script works
		wp_enqueue_script('jquery');
	}

	/**
	* register new payment gateway with front end (NB: admin side never calls this!)
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public function awpcpPaymentMethods($methods) {
		$method = new stdClass;
		$method->slug = self::PAYMENT_METHOD;
		$method->name = 'eWAY Payment Gateway';
		$method->icon = plugins_url('images/eway-awpcp.png', __FILE__);
		$method->description = 'Credit card payment via eWAY';

		$methods[] = $method;

		return $methods;
	}

	/**
	* register settings for this payment method
	*/
	public function awpcpRegisterSettings() {
		global $awpcp;

		// create a new section
		$section = $awpcp->settings->add_section('payment-settings', 'eWAY Payment Gateway', 'eway', 100, array($awpcp->settings, 'section'));

		$awpcp->settings->add_setting($section, 'activateeway', 'Activate eWAY?',
			'checkbox', 1, 'Activate eWAY?');

		$awpcp->settings->add_setting($section, 'eway_customerid', 'eWAY customer ID', 'textfield', '87654321',
			'your eWAY customer ID');

		$awpcp->settings->add_setting($section, 'eway_stored', 'Stored payments', 'checkbox', 0,
			"<a href='http://www.eway.com.au/how-it-works/what-products-are-included-#stored-payments' target='_blank'>Stored payments</a> records payment details but doesn't bill immediately. Useful when ads must be approved by admin, allowing you to reject payments for rejected ads.");

		// TODO: add Beagle if new version supports taking country info before billing
		//~ $awpcp->settings->add_setting($section, 'eway_beagle', 'Beagle (anti-fraud)', 'checkbox', 0,
			//~ "<a href='http://www.eway.com.au/developers/resources/beagle-(free)-rules' target='_blank'>Beagle</a> is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure <a href='http://www.eway.com.au/developers/resources/beagle-(free)-rules' target='_blank'>Beagle rules</a> in your MYeWAY console before enabling Beagle");
	}

	/**
	* change the text shown above the checkout form
	*/
	public function awpcpCheckoutStepText($text, $form_values, $transaction) {
		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {
			$text = "Please enter your credit card details for secure payment via <a href='http://www.eway.com.au/'>eWAY</a>.";
		}

		return $text;
	}

	/**
	* get HTML for checkout form
	* @param string $form
	* @param AWPCP_Payment_Transaction $transaction
	* @return string
	*/
	public function awpcpCheckoutForm($form, $transaction) {
		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {

			$item = $transaction->get_item(0); // no support for multiple items
			if (is_null($item)) {
				return __('There was an error processing your payment.', 'AWPCP');
			}

			// get URL for where to post the checkout form data
			list($checkoutURL) = awpcp_payment_urls($transaction);

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

			// load template with passed values
			ob_start();
			EwayPaymentsPlugin::loadTemplate('awcp-eway-fields.php', compact('checkoutURL', 'optMonths', 'optYears'));
			$form = ob_get_clean();
		}

		return $form;
	}

	/**
	* verify data in checkout form before processing
	* @param bool $verified
	* @param AWPCP_Payment_Transaction $transaction
	* @return bool
	*/
	public function awpcpVerifyTx($verified, $transaction) {

//~ error_log(__METHOD__ . "\n" . print_r($transaction,1));
//~ error_log(__METHOD__ . "\n" . print_r($_POST,1));

		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {

			$verified = $transaction->get('verified');
			if (!$verified) {
				// check for missing or invalid values
				$errors = 0;
				$expiryError = false;

				if (self::getPostValue('eway_card_number') === '') {
					$transaction->errors[] = 'Please enter credit card number';
					$errors++;
				}

				if (self::getPostValue('eway_card_name') === '') {
					$transaction->errors[] = 'Please enter card holder name';
					$errors++;
				}

				$eway_expiry_month = self::getPostValue('eway_expiry_month');
				if (empty($eway_expiry_month) || !preg_match('/^(?:0[1-9]|1[012])$/', $eway_expiry_month)) {
					$transaction->errors[] = 'Please select credit card expiry month';
					$errors++;
					$expiryError = true;
				}

				// FIXME: if this code makes it into the 2100's, update this regex!
				$eway_expiry_year = self::getPostValue('eway_expiry_year');
				if (empty($eway_expiry_year) || !preg_match('/^20\d\d$/', $eway_expiry_year)) {
					$transaction->errors[] = 'Please select credit card expiry year';
					$errors++;
					$expiryError = true;
				}

				if (!$expiryError) {
					// check that first day of month after expiry isn't earlier than today
					$expired = mktime(0, 0, 0, 1 + $eway_expiry_month, 0, $eway_expiry_year);
					$today = time();
					if ($expired < $today) {
						$transaction->errors[] = 'Credit card expiry has passed';
						$errors++;
					}
				}

				if (self::getPostValue('eway_cvn') === '') {
					$transaction->errors[] = 'Please enter CVN (Card Verification Number)';
					$errors++;
				}

				$verified = ($errors === 0);
			}
		}

//~ error_log(__METHOD__ . ": verified = $verified");

		return $verified;
	}

	/**
	* validate checkout by processing transaction against eWAY
	* @param bool $verified
	* @param AWPCP_Payment_Transaction $transaction
	* @return bool
	*/
	public function awpcpValidateTx($valid, $transaction) {

//~ error_log(__METHOD__ . "\n" . print_r($_POST,1));

		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {

			$valid = $transaction->get('validated');
			if (!$valid) {
				$eway_customerid = get_awpcp_option('eway_customerid');
				$eway_stored = get_awpcp_option('eway_stored');

				// TODO: add Beagle if new version supports taking country info before billing
				//~ $eway_beagle = get_awpcp_option('eway_beagle');

				$isLiveSite = !get_awpcp_option('paylivetestmode');
				$item = $transaction->get_item(0); // no support for multiple items
				$user = wp_get_current_user();

				if ($eway_stored)
					$eway = new EwayPaymentsStoredPayment($eway_customerid, $isLiveSite);
				else
					$eway = new EwayPaymentsPayment($eway_customerid, $isLiveSite);

				$eway->invoiceDescription = $item->name;
				$eway->invoiceReference = $transaction->id;									// customer invoice reference
				//~ $eway->transactionNumber = $transaction->id;								// transaction reference
				$eway->cardHoldersName = self::getPostValue('eway_card_name');
				$eway->cardNumber = self::getPostValue('eway_card_number');
				$eway->cardExpiryMonth = self::getPostValue('eway_expiry_month');
				$eway->cardExpiryYear = self::getPostValue('eway_expiry_year');
				$eway->cardVerificationNumber = self::getPostValue('eway_cvn');
				$eway->firstName = $user ? $user->first_name : '';
				$eway->lastName = $user ? $user->last_name : '';
				$eway->emailAddress = $user ? $user->email : '';
				//~ $eway->postcode = $order->billing_postcode;

				// TODO: add Beagle if new version supports taking country info before billing
				// for Beagle (free) security
				//~ if ($this->eway_beagle == 'yes') {
					//~ $eway->customerCountryCode = $order->billing_country;
				//~ }

				// convert WooCommerce country code into country name
				//~ $billing_country = $order->billing_country;
				//~ if (isset($woocommerce->countries->countries[$billing_country])) {
					//~ $billing_country = $woocommerce->countries->countries[$billing_country];
				//~ }

				// aggregate street, city, state into a single string
				if ($user) {
					$profile = get_user_meta($user->ID, 'awpcp-profile', true);
					$parts = array (
						isset($profile['address']) ? $profile['address'] : '',
						isset($profile['city']) ? $profile['city'] : '',
						isset($profile['state']) ? $profile['state'] : '',
					);
					$eway->address = implode(', ', array_filter($parts, 'strlen'));
				}

				// use cardholder name for last name if no customer name available
				if (empty($eway->firstName) && empty($eway->lastName)) {
					$eway->lastName = $eway->cardHoldersName;
				}

				// allow plugins/themes to modify invoice description and reference, and set option fields
				$eway->invoiceDescription = apply_filters('awpcp_eway_invoice_desc', $eway->invoiceDescription, $order_id);
				$eway->invoiceReference = apply_filters('awpcp_eway_invoice_ref', $eway->invoiceReference, $order_id);
				$eway->option1 = apply_filters('awpcp_eway_option1', '', $order_id);
				$eway->option2 = apply_filters('awpcp_eway_option2', '', $order_id);
				$eway->option3 = apply_filters('awpcp_eway_option3', '', $order_id);

				// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
				$total = $transaction->get('amount');
				$eway->amount = $isLiveSite ? $total : ceil($total);

//~ error_log(__METHOD__ . "\n" . print_r($eway,1));
//~ error_log(__METHOD__ . "\n" . $eway->getPaymentXML());
//~ return false;

				try {
					$response = $eway->processPayment();

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

					$transaction->set('txn-id', $response->transactionNumber);

					if ($response->status) {
						// transaction was successful, so record details and complete payment

						//~ if (!empty($response->authCode)) {
							//~ update_post_meta($order_id, 'Authcode', $response->authCode);
						//~ }
						//~ if (!empty($response->beagleScore)) {
							//~ update_post_meta($order_id, 'Beagle score', $response->beagleScore);
						//~ }

						if ($eway_stored) {
							// payment hasn't happened yet, so record status as 'on-hold' and reduce stock in anticipation
							$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_PENDING);
						}
						else {
							$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_COMPLETED);
						}

						$valid = true;
					}
					else {
						// transaction was unsuccessful, so record transaction number and the error
						$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_FAILED);
						$transaction->errors[] = nl2br(htmlspecialchars($response->error));
						$valid = false;
					}
				}
				catch (EwayPaymentsException $e) {
					// an exception occured, so record the error
					$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_FAILED);
					$transaction->errors[] = nl2br(htmlspecialchars($e->getMessage()));
					$valid = false;
				}
			}
		}

//~ error_log(__METHOD__ . ": valid = $valid");
//~ error_log(__METHOD__ . "\n" . print_r($transaction,1));

		return $valid;
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
