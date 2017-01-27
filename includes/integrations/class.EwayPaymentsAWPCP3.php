<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for Another WordPress Classifieds Plugin since v3.0
* @link http://awpcp.com/
*/
class EwayPaymentsAWPCP3 extends AWPCP_PaymentGateway {

	protected $logger;

	const PAYMENT_METHOD = 'eway';

	/**
	* set up hooks for the integration
	*/
	public static function setup() {
		add_filter('awpcp-register-payment-methods', array(__CLASS__, 'awpcpRegisterPaymentMethods'), 20);
		add_action('awpcp_register_settings', array(__CLASS__, 'awpcpRegisterSettings'));
	}

	/**
	* initialise payment gateway
	*/
	public function __construct() {
		$this->logger		= new EwayPaymentsLogging('awpcp', get_awpcp_option('eway_logging', 'off'));

		$icon = get_awpcp_option('eway_icon');
		if (empty($icon)) {
			$icon = plugins_url('images/eway-siteseal.png', EWAY_PAYMENTS_PLUGIN_FILE);
		}

		parent::__construct(
			/* slug */			self::PAYMENT_METHOD,
			/* name */			_x('eWAY Payment Gateway', 'AWPCP payment method name', 'eway-payment-gateway'),
			/* description */	_x('Credit card payment via eWAY', 'AWPCP payment method description', 'eway-payment-gateway'),
			/* icon */			apply_filters('awpcp_eway_icon', $icon)
		);
	}

	/**
	* register new payment gateway with front end (NB: admin side never calls this!)
	* @param AWPCP_PaymentsAPI $payments
	*/
	public static function awpcpRegisterPaymentMethods($payments) {
		if (get_awpcp_option('activateeway')) {
			$payments->register_payment_method(new self());
		}
	}

	/**
	* register settings for this payment method
	*/
	public static function awpcpRegisterSettings() {
		$awpcp = awpcp();

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
	* declare type of integration as showing a custom form for credit card details
	* @return string
	*/
	public function get_integration_type() {
		return self::INTEGRATION_CUSTOM_FORM;
	}

	/**
	* process payment of a transaction -- show the checkout form
	* @param AWPCP_Payment_Transaction $transaction
	* @return string
	*/
	public function process_payment($transaction) {
		if ($transaction->get('payment-method') !== self::PAYMENT_METHOD) {
			return '';
		}

		$item = $transaction->get_item(0); // no support for multiple items
		if (is_null($item)) {
			return __('There was an error processing your payment.', 'eway-payment-gateway');
		}

		$payments    = awpcp_payments_api();
		$checkoutURL = $payments->get_return_url($transaction);

		$checkout_message = sprintf(__('Please enter your credit card details for secure payment via <a target="_blank" href="%s">eWAY</a>.', 'eway-payment-gateway'),
					'https://www.eway.com.au/');
		$checkout_message = apply_filters('awpcp_eway_checkout_message', $checkout_message, false, $transaction);

		$card_msg = esc_html(get_awpcp_option('eway_card_message'));

		$optMonths = EwayPaymentsFormUtils::getMonthOptions();
		$optYears  = EwayPaymentsFormUtils::getYearOptions();

		// load template with passed values
		ob_start();
		EwayPaymentsPlugin::loadTemplate('awcp-eway-fields.php', compact('checkoutURL', 'checkout_message', 'card_msg', 'optMonths', 'optYears'));
		$form = ob_get_clean();

		$min = SCRIPT_DEBUG ? ''     : '.min';
		$ver = SCRIPT_DEBUG ? time() : EWAY_PAYMENTS_VERSION;

		wp_enqueue_script('eway-awpcp-checkout-form', plugins_url("js/awpcp-checkout-form$min.js", EWAY_PAYMENTS_PLUGIN_FILE), array('jquery'), $ver, true);
		wp_localize_script('eway-awpcp-checkout-form', 'eway_awpcp_checkout', array(
			'errors' => array(
				'card_number'	=> __('Credit Card Number is missing', 'eway-payment-gateway'),
				'card_name'		=> __("Card Holder's Name is missing", 'eway-payment-gateway'),
				'expiry_month'	=> __('Credit Card Expiry is missing', 'eway-payment-gateway'),
				'cvn'			=> __('CVN is missing', 'eway-payment-gateway'),
			)
		));

		return $form;
	}

	/**
	* process payment notification
	* @param AWPCP_Payment_Transaction $transaction
	*/
	public function process_payment_notification($transaction) {
		return;
	}

	/**
	* process completed transaction
	* @param AWPCP_Payment_Transaction $transaction
	*/
	public function process_payment_completed($transaction) {
		$postdata		= new EwayPaymentsFormPost();

		$fields			= array(
			'card_number'	=> $postdata->getValue('eway_card_number'),
			'card_name'		=> $postdata->getValue('eway_card_name'),
			'expiry_month'	=> $postdata->getValue('eway_expiry_month'),
			'expiry_year'	=> $postdata->getValue('eway_expiry_year'),
			'cvn'			=> $postdata->getValue('eway_cvn'),
		);

		$errors			= $postdata->verifyCardDetails($fields);
		$success		= (count($errors) === 0);

		$transaction->errors['verification-post'] = $errors;
		$transaction->errors['validation'] = array();

		if ($success) {

			try {
				$response = $this->processTransaction($transaction);

				if ($response->TransactionStatus) {
					// transaction was successful, so record details and complete payment
					$transaction->set('txn-id', $response->TransactionID);
					$transaction->completed = current_time('mysql');

					if (!empty($response->AuthorisationCode)) {
						$transaction->set('eway_authcode', $response->AuthorisationCode);
					}

					if ($response->BeagleScore > 0) {
						$transaction->set('eway_beagle_score', $response->BeagleScore);
					}

					/* TODO: stored payments in AWPCP, when plugin workflow supports it
					if ($eway_stored) {
						// payment hasn't happened yet, so record status as 'on-hold' in anticipation
						$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;
					}
					else {
					*/
						$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED;
					/*
					}
					*/

					$success = true;

					$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, beagle = %6$s',
						$transaction->id, $response->TransactionID, 'completed',
						$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
				}
				else {
					// transaction was unsuccessful, so record transaction number and the error
					$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
					$transaction->set('txn-id', $response->TransactionID);
					$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
					$transaction->errors['validation'] = $error_msg;
					$success = false;

					$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $transaction->id, $response->getErrorsForLog()));
					if ($response->BeagleScore > 0) {
						$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
					}
				}
			}
			catch (EwayPaymentsException $e) {
				// an exception occured, so record the error
				$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
				$transaction->errors['validation'] = nl2br(esc_html($e->getMessage()) . "\n" . __("use your browser's back button to try again.", 'eway-payment-gateway'));
				$success = false;

				$this->logger->log('error', $e->getMessage());
			}
		}

		$transaction->set('verified', $success);
	}

	/**
	* process transaction against eWAY
	* @return $response
	*/
	protected function processTransaction($transaction) {
		$isLiveSite = !get_awpcp_option('paylivetestmode');
		$eway_stored = get_awpcp_option('eway_stored');

		if (!$isLiveSite && get_awpcp_option('eway_test_force')) {
			$eway_customerid = EWAY_PAYMENTS_TEST_CUSTOMER;
		}
		else {
			$eway_customerid = get_awpcp_option('eway_customerid');
		}
		$eway_customerid = apply_filters('awpcp_eway_customer_id', $eway_customerid, $isLiveSite, $transaction);

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
		$eway->transactionNumber			= $transaction->id;									// transaction reference
		$eway->cardHoldersName				= $postdata->getValue('eway_card_name');
		$eway->cardNumber					= $postdata->cleanCardnumber($postdata->getValue('eway_card_number'));
		$eway->cardExpiryMonth				= $postdata->getValue('eway_expiry_month');
		$eway->cardExpiryYear				= $postdata->getValue('eway_expiry_year');
		$eway->cardVerificationNumber		= $postdata->getValue('eway_cvn');

		list($eway->firstName, $eway->lastName) = self::getContactNames($ad, $user, $eway->cardHoldersName);

		self::setTxContactDetails($eway, $ad, $user);

		// TODO: add Beagle if new version supports taking country info before billing
		// for Beagle (free) security
		//~ if ($this->eway_beagle == 'yes') {
			//~ $eway->country = $order->billing_country;
		//~ }

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription			= apply_filters('awpcp_eway_invoice_desc', $eway->invoiceDescription, $transaction);
		$eway->invoiceReference				= apply_filters('awpcp_eway_invoice_ref', $eway->invoiceReference, $transaction);
		$eway->options						= array_filter(array(
													apply_filters('awpcp_eway_option1', '', $transaction),
													apply_filters('awpcp_eway_option2', '', $transaction),
													apply_filters('awpcp_eway_option3', '', $transaction),
												), 'strlen');

		$totals = $transaction->get_totals();
		$eway->amount = $totals['money'];

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$isLiveSite ? 'live' : 'test', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

		$response = $eway->processPayment();

		return $response;
	}

	/**
	* process payment cancellation
	* @param AWPCP_Payment_Transaction $transaction
	*/
	public function process_payment_canceled($transaction) {
		// TODO: process_payment_canceled
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
	* attempt to get meaningful contact details from available data
	* @param object $eway
	* @param AWPCP_Ad $ad
	* @param WP_User $user
	* @return string
	*/
	protected static function setTxContactDetails($eway, $ad, $user) {
		$profile = $user ? get_user_meta($user->ID, 'awpcp-profile', true) : false;

		$eway->emailAddress			= '';
		$eway->address1				= '';
		$eway->address2				= '';
		$eway->suburb				= '';
		$eway->state				= '';
		$eway->countryName			= '';
		$eway->postcode				= '';

		if ($ad->ad_contact_email) {
			$eway->emailAddress		= $ad->ad_contact_email;
		}
		elseif ($user) {
			$eway->emailAddress		= $user->user_email;
		}

		if ($ad->ad_city || $ad->ad_state || $ad->ad_country) {
			$eway->suburb			= $ad->ad_city;
			$eway->state			= $ad->ad_state;
			$eway->countryName		= $ad->ad_country;
		}
		elseif (method_exists('AWPCP_Ad', 'get_ad_regions')) {
			$regions = AWPCP_Ad::get_ad_regions($ad->ad_id);
			if (!empty($regions[0])) {
				$eway->suburb		= $regions[0]['city'];
				$eway->state		= $regions[0]['state'];
				$eway->countryName	= $regions[0]['country'];
			}
		}
		elseif ($profile) {
			if (isset($profile['address'])) {
				$eway->address1		= $profile['address'];
			}
			if (isset($profile['city'])) {
				$eway->suburb		= $profile['city'];
			}
			if (isset($profile['state'])) {
				$eway->state		= $profile['state'];
			}
		}
	}

}
