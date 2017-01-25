<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for Another WordPress Classifieds Plugin
* @link http://awpcp.com/
*/
class EwayPaymentsAWPCP {

	const PAYMENT_METHOD = 'eway';

	// payments API -- v3.0+
	protected $paymentsAPI = false;

	protected $logger;

	/**
	* initialise gateway with custom settings
	*/
	public function __construct() {
		// admin actions / filters
		add_action('awpcp_register_settings', array($this, 'awpcpRegisterSettings'));
		//~ add_filter('awpcp_validate_settings_payment-settings', array($this, 'awpcpValidateSettings'), 10, 2);

		// front end actions / filters
		add_filter('awpcp-register-payment-methods', array($this, 'awpcpRegisterPaymentMethods'), 20);			// AWPCP v3.0+
		add_filter('awpcp-payment-methods', array($this, 'awpcpPaymentMethods'), 20);
		add_filter('awpcp-place-ad-checkout-step-form-text', array($this, 'awpcpCheckoutStepText'), 10, 3);
		add_filter('awpcp-checkout-form', array($this, 'awpcpCheckoutForm'), 10, 2);
		add_filter('awpcp-payments-verify-transaction', array($this, 'awpcpVerifyTx'), 10, 2);
		add_filter('awpcp-payments-validate-transaction', array($this, 'awpcpValidateTx'), 10, 2);

		// make sure we have jQuery so that checkout form script works
		wp_enqueue_script('jquery');

		// create a logger
		$this->logger = new EwayPaymentsLogging('awpcp', get_awpcp_option('eway_logging', 'off'));
	}

	/**
	* AWPCP v3.0+: register new payment gateway with front end (NB: admin side never calls this!)
	* @param AWPCP_PaymentsAPI $payments AWPCP payments API
	*/
	public function awpcpRegisterPaymentMethods($payments) {
		$this->paymentsAPI = $payments;

		if (get_awpcp_option('activateeway')) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'includes/integrations/class.EwayPaymentsAWPCP3.php';
			$this->paymentsAPI->register_payment_method(new EwayPaymentsAWPCP3($this, $this->logger));
		}
	}

	/**
	* AWPCP < v3.0: register new payment gateway with front end (NB: admin side never calls this!)
	* @param array $methods array of registered gateways
	* @return array
	*/
	public function awpcpPaymentMethods($methods) {
		// allow custom icon for payment method
		$icon = get_awpcp_option('eway_icon');
		if (empty($icon)) {
			$icon = plugins_url('images/eway-siteseal.png', EWAY_PAYMENTS_PLUGIN_FILE);
		}
		$icon = apply_filters('awpcp_eway_icon', $icon);

		$method = new stdClass;
		$method->slug			= self::PAYMENT_METHOD;
		$method->name			= _x('eWAY Payment Gateway', 'AWPCP payment method name', 'eway-payment-gateway');
		$method->icon			= $icon;
		$method->description	= _x('Credit card payment via eWAY', 'AWPCP payment method description', 'eway-payment-gateway');

		$methods[] = $method;

		return $methods;
	}

	/**
	* register settings for this payment method
	*/
	public function awpcpRegisterSettings() {
		global $awpcp;

		// create a new section
		$section = $awpcp->settings->add_section('payment-settings',
						_x('eWAY Payment Gateway', 'AWPCP payment settings', 'eway-payment-gateway'),
						'eway', 100, array($awpcp->settings, 'section'));

		$awpcp->settings->add_setting($section, 'activateeway',
						_x('Activate eWAY?', 'AWPCP payment settings', 'eway-payment-gateway'),
						'checkbox', 1,
						_x('Activate eWAY?', 'AWPCP payment settings label', 'eway-payment-gateway'));

		$awpcp->settings->add_setting($section, 'eway_customerid',
						_x('eWAY customer ID', 'AWPCP payment settings', 'eway-payment-gateway'),
						'textfield', EWAY_PAYMENTS_TEST_CUSTOMER,
						'<br />' . _x('your eWAY customer ID', 'AWPCP payment settings label', 'eway-payment-gateway'));

		$awpcp->settings->add_setting($section, 'eway_test_force',
						_x('Force test ID for sandbox?', 'AWPCP payment settings', 'eway-payment-gateway'),
						'checkbox', 1,
						_x('Force special test ID 87654321 for sandbox?', 'AWPCP payment settings label', 'eway-payment-gateway'));

		$awpcp->settings->add_setting($section, 'eway_stored',
						_x('Stored payments', 'AWPCP payment settings', 'eway-payment-gateway'),
						'checkbox', 0,
						__("Stored payments records payment details but doesn't bill immediately. Useful when ads must be approved by admin, allowing you to reject payments for rejected ads.", 'eway-payment-gateway'));

		// TODO: add Beagle if new version supports taking country info before billing
		//~ $awpcp->settings->add_setting($section, 'eway_beagle', 'Beagle (anti-fraud)', 'checkbox', 0,
			//~ "<a href='https://www.eway.com.au/developers/api/beagle-lite' target='_blank'>Beagle</a> is a service from eWAY that provides a level of fraud protection for your transactions. It uses information about the IP address of the purchaser to suggest whether there is a risk of fraud. You must configure Beagle rules in your MYeWAY console before enabling Beagle");

		$log_options = array(
			'off' 		=> _x('Off', 'logging settings', 'eway-payment-gateway'),
			'info'	 	=> _x('All messages', 'logging settings', 'eway-payment-gateway'),
			'error' 	=> _x('Errors only', 'logging settings', 'eway-payment-gateway'),
		);
		$log_descripton = sprintf('<br />%s<br />%s<br />%s',
							__('enable logging to assist trouble shooting', 'eway-payment-gateway'),
							__('the log file can be found in this folder:', 'eway-payment-gateway'),
							EwayPaymentsLogging::getLogFolderRelative());
		$awpcp->settings->add_setting($section, 'eway_logging',
						_x('Logging', 'AWPCP payment settings', 'eway-payment-gateway'),
						'select', 'off', $log_descripton, array('options' => $log_options));

		$awpcp->settings->add_setting($section, 'eway_card_message',
						_x('Credit card message', 'AWPCP payment settings', 'eway-payment-gateway'),
						'textfield', '',
						'<br />' . _x('Message to show above credit card fields, e.g. "Visa and Mastercard only"', 'AWPCP payment settings label', 'eway-payment-gateway'));

		$awpcp->settings->add_setting($section, 'eway_site_seal_code',
						_x('eWAY Site Seal', 'AWPCP payment settings', 'eway-payment-gateway'),
						'textarea', '',
						sprintf('<br /><a href="https://www.eway.com.au/features/tools-site-seal" target="_blank">%s</a>',
							__('generate your site seal on the eWAY website, and paste it here', 'eway-payment-gateway')));

		$awpcp->settings->add_setting($section, 'eway_icon',
						_x('Payment Method Icon', 'AWPCP payment settings', 'eway-payment-gateway'),
						'textfield', '',
						'<br />' . _x('URL to a custom icon to show for the payment method.', 'AWPCP payment settings label', 'eway-payment-gateway'));
	}

	/**
	* change the text shown above the checkout form
	*/
	public function awpcpCheckoutStepText($text, $form_values, $transaction) {
		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {
			$text = sprintf(__('Please enter your credit card details for secure payment via <a target="_blank" href="%s">eWAY</a>.', 'eway-payment-gateway'),
						'https://www.eway.com.au/');
			$text = apply_filters('awpcp_eway_checkout_message', $text, $form_values, $transaction);
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
				return __('There was an error processing your payment.', 'eway-payment-gateway');
			}

			// get URL for where to post the checkout form data
			if ($this->paymentsAPI) {
				$checkoutURL = $this->paymentsAPI->get_return_url($transaction);
			}
			else {
				list($checkoutURL) = awpcp_payment_urls($transaction);
			}

			$card_msg = esc_html(get_awpcp_option('eway_card_message'));

			$optMonths = EwayPaymentsFormUtils::getMonthOptions();
			$optYears  = EwayPaymentsFormUtils::getYearOptions();

			// load template with passed values
			ob_start();
			EwayPaymentsPlugin::loadTemplate('awcp-eway-fields.php', compact('checkoutURL', 'card_msg', 'optMonths', 'optYears'));
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
		// only if this is the current payment method
		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {
			$verified = $transaction->get('verified');
			if (!$verified) {
				// check for missing or invalid values
				$errors = $this->verifyForm($transaction);

				$transaction->errors = array_merge($transaction->errors, $errors);
				$verified = (count($errors) === 0);
			}
		}

		return $verified;
	}

    /**
    * verify credit card form data
    * @param AWPCPtrans $transaction
    * @return array an array of error messages
    */
    public function verifyForm($transaction) {
		$postdata		= new EwayPaymentsFormPost();

		$fields			= array(
			'card_number'	=> $postdata->getValue('eway_card_number'),
			'card_name'		=> $postdata->getValue('eway_card_name'),
			'expiry_month'	=> $postdata->getValue('eway_expiry_month'),
			'expiry_year'	=> $postdata->getValue('eway_expiry_year'),
			'cvn'			=> $postdata->getValue('eway_cvn'),
		);

		$errors			= $postdata->verifyCardDetails($fields);

		return $errors;
	}

	/**
	* validate checkout by processing transaction against eWAY
	* @param bool $verified
	* @param AWPCP_Payment_Transaction $transaction
	* @return bool
	*/
	public function awpcpValidateTx($valid, $transaction) {
		if ($transaction->get('payment-method') == self::PAYMENT_METHOD) {

			try {
				$response = $this->processTransaction($transaction);

				if ($response->status) {
					// transaction was successful, so record details and complete payment
					$transaction->set('txn-id', $response->transactionNumber);

					if (!empty($response->authCode)) {
						$transaction->set('eway_authcode', $response->authCode);
					}

					//~ if (!empty($response->beagleScore)) {
						//~ $transaction->set('eway_beagle_score', $response->beagleScore);
					//~ }

					$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_COMPLETED);

					$valid = true;

					$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s',
						$transaction->id, $response->transactionNumber, 'completed', $response->amount, $response->authCode));
				}
				else {
					// transaction was unsuccessful, so record transaction number and the error
					$transaction->set('txn-id', $response->transactionNumber);
					$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_FAILED);
					$transaction->errors[] = nl2br(esc_html($response->error . "\n" . __("use your browser's back button to try again.", 'eway-payment-gateway')));
					$valid = false;

					$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $transaction->id, $response->error));
				}
			}
			catch (EwayPaymentsException $e) {
				// an exception occured, so record the error
				$transaction->set('payment-status', AWPCP_Payment_Transaction::$PAYMENT_STATUS_FAILED);
				$transaction->errors[] = nl2br(esc_html($e->getMessage() . "\n" . __("use your browser's back button to try again.", 'eway-payment-gateway')));
				$valid = false;

				$this->logger->log('error', $e->getMessage());
			}
		}

		return $valid;
	}

	/**
	* process transaction against eWAY
	* @return $response
	*/
	public function processTransaction($transaction) {
		$isLiveSite = !get_awpcp_option('paylivetestmode');
		$eway_stored = get_awpcp_option('eway_stored');

		if (!$isLiveSite && get_awpcp_option('eway_test_force')) {
			$eway_customerid = EWAY_PAYMENTS_TEST_CUSTOMER;
		}
		else {
			$eway_customerid = get_awpcp_option('eway_customerid');
		}
		$eway_customerid = apply_filters('awpcp_eway_customer_id', $eway_customerid, $isLiveSite, $transaction);

		// TODO: add Beagle if new version supports taking country info before billing
		//~ $eway_beagle = get_awpcp_option('eway_beagle');

		$item = $transaction->get_item(0); // no support for multiple items
		$ad = AWPCP_Ad::find_by_id($transaction->get('ad-id'));
		$user = wp_get_current_user();

		if ($eway_stored) {
			$eway = new EwayPaymentsStoredPayment($eway_customerid, $isLiveSite);
		}
		else {
			$eway = new EwayPaymentsPayment($eway_customerid, $isLiveSite);
		}

		$postdata = new EwayPaymentsFormPost();

		$eway->invoiceDescription			= $item->name;
		$eway->invoiceReference				= $transaction->id;									// customer invoice reference
		//~ $eway->transactionNumber		= $transaction->id;									// transaction reference
		$eway->cardHoldersName				= $postdata->getValue('eway_card_name');
		$eway->cardNumber					= $postdata->cleanCardnumber($postdata->getValue('eway_card_number'));
		$eway->cardExpiryMonth				= $postdata->getValue('eway_expiry_month');
		$eway->cardExpiryYear				= $postdata->getValue('eway_expiry_year');
		$eway->cardVerificationNumber		= $postdata->getValue('eway_cvn');

		list($eway->firstName, $eway->lastName) = self::getContactNames($ad, $user, $eway->cardHoldersName);

		$eway->emailAddress					= $ad->ad_contact_email ? $ad->ad_contact_email : ($user ? $user->user_email : '');
		$eway->address						= self::getContactAddress($ad, $user);

		// TODO: add Beagle if new version supports taking country info before billing
		// for Beagle (free) security
		//~ if ($this->eway_beagle == 'yes') {
			//~ $eway->customerCountryCode = $order->billing_country;
		//~ }

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription			= apply_filters('awpcp_eway_invoice_desc', $eway->invoiceDescription, $transaction);
		$eway->invoiceReference				= apply_filters('awpcp_eway_invoice_ref', $eway->invoiceReference, $transaction);
		$eway->option1						= apply_filters('awpcp_eway_option1', '', $transaction);
		$eway->option2						= apply_filters('awpcp_eway_option2', '', $transaction);
		$eway->option3						= apply_filters('awpcp_eway_option3', '', $transaction);

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		if (method_exists($transaction, 'get_totals')) {
			// AWPCP v3.0+
			$totals = $transaction->get_totals();
			$total = $totals['money'];
		}
		else {
			// AWPCP v < 3.0
			$total = $transaction->get('amount');
		}
		$eway->amount = $isLiveSite ? $total : ceil($total);
		if ($eway->amount != $total) {
			$this->logger->log('info', sprintf('amount rounded up from %1$s to %2$s, to pass test gateway',
				number_format($total, 2), number_format($eway->amount, 2)));
		}

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$isLiveSite ? 'live' : 'test', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

		$response = $eway->processPayment();

		return $response;
	}

	/**
	* get contact name from available data
	* @param AWPCP_Ad $ad
	* @param WP_User $user
	* @param string $cardHoldersName
	* @return array two elements: first name, last name
	*/
	protected static function getContactNames($ad, $user, $cardHoldersName) {
		$names = array('', '');

		if ($ad->ad_contact_name) {
			$names = self::splitCompoundName($ad->ad_contact_name);
		}
		elseif ($user) {
			$names = array($user->first_name, $user->last_name);
		}

		// use cardholder name for customer name if no customer name available
		if (empty($names[0]) && empty($names[1])) {
			$names = self::splitCompoundName($cardHoldersName);
		}

		return $names;
	}

	/**
	* attempt to split name into parts, and hope to not offend anyone!
	* @param string $compoundName
	* @return array two elements: first name, last name
	*/
	protected static function splitCompoundName($compoundName) {
		$names = explode(' ', $compoundName);

		$firstName = empty($names[0]) ? '' : array_shift($names);		// remove first name from array
		$lastName = trim(implode(' ', $names));

		return array($firstName, $lastName);
	}

	/**
	* attempt to get a meaningful address field from available data
	* @param AWPCP_Ad $ad
	* @param WP_User $user
	* @return string
	*/
	protected static function getContactAddress($ad, $user) {
		$address = '';

		if ($ad->ad_city || $ad->ad_state || $ad->ad_country) {
			$parts = array (
				$ad->ad_city,
				$ad->ad_state,
				$ad->ad_country,
			);
			$address = implode(', ', array_filter($parts, 'strlen'));
		}
		elseif (method_exists('AWPCP_Ad', 'get_ad_regions')) {
			$regions = AWPCP_Ad::get_ad_regions($ad->ad_id);
			if (!empty($regions[0])) {
				$parts = array (
					$regions[0]['city'],
					$regions[0]['state'],
					$regions[0]['country'],
				);
				$address = implode(', ', array_filter($parts, 'strlen'));
			}
		}
		elseif ($user) {
			$profile = get_user_meta($user->ID, 'awpcp-profile', true);
			$parts = array (
				isset($profile['address']) ? $profile['address'] : '',
				isset($profile['city'])    ? $profile['city']    : '',
				isset($profile['state'])   ? $profile['state']   : '',
			);
			$address = implode(', ', array_filter($parts, 'strlen'));
		}

		return $address;
	}

}
