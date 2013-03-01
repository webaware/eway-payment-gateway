<?php

if (!defined('EM_EWAY_GATEWAY')) {
	define('EM_EWAY_GATEWAY', 'eway');
}

/**
* payment gateway integration for Events Manager
* with thanks to EM_Gateway_Authorize_AIM for showing the way...
*/
class EwayPaymentsEventsManager extends EM_Gateway {

	private $registered_timer = 0;

	/**
	* Set up gateaway and add relevant actions/filters
	*/
	public function __construct() {
		$this->gateway = EM_EWAY_GATEWAY;
		$this->title = 'eWAY';
		$this->status = 4;
		$this->status_txt = 'Processing (eWAY)';
		$this->button_enabled = false; // TODO: we can't use a button here ?

		// ensure options are present, set to defaults if not
		$defaults = array (
			'em_' . EM_EWAY_GATEWAY . '_option_name'			=> 'Credit Card',
			'em_' . EM_EWAY_GATEWAY . '_booking_feedback'		=> 'Booking successful.',
			'em_' . EM_EWAY_GATEWAY . '_booking_feedback_free'	=> 'Booking successful. You have not been charged for this booking.',
			'em_' . EM_EWAY_GATEWAY . '_cust_id'				=> '87654321',
			'em_' . EM_EWAY_GATEWAY . '_stored'					=> '0',
			'em_' . EM_EWAY_GATEWAY . '_beagle'					=> '0',
			'em_' . EM_EWAY_GATEWAY . '_test_force'				=> '1',
			'em_' . EM_EWAY_GATEWAY . '_mode'					=> 'sandbox',
		);
		foreach ($defaults as $option => $value) {
			if (get_option($option) === false) {
				add_option($option, $value);
			}
		}

		// initialise parent class
		parent::__construct();

		if ($this->is_active()) {
			// force SSL for booking submissions, since we have card info
			if (get_option('em_' . EM_EWAY_GATEWAY . '_mode') == 'live') { // no need if in sandbox mode
				add_filter('em_wp_localize_script', array(__CLASS__, 'filterEmWpLocalizeScript')); // modify booking script, force SSL for all
				add_filter('em_booking_form_action_url', array(__CLASS__, 'force_ssl')); // modify booking script, force SSL for all
			}

			add_filter('em_booking_validate', array(__CLASS__, 'filterEmBookingValidate'), 10, 2);	// perform additional form post validation
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
	protected static function getPostValue($fieldname) {
		return isset($_POST[$fieldname]) ? stripslashes(trim($_POST[$fieldname])) : '';
	}

	/**
	* attempt to map country code to name
	* @param string $countryCode
	* @return string
	*/
	protected static function getCountryName($countryCode) {
		$name = '';

		if ($countryCode) {
			$countries = em_get_countries();
			if (isset($countries[$countryCode])) {
				$name = $countries[$countryCode];
			}
		}

		return $name;
	}

	/*
	* --------------------------------------------------
	* Booking Interception - functions that modify booking object behaviour
	* --------------------------------------------------
	*/

	/**
	* This function intercepts the previous booking form url from the javascript localized array of EM variables and forces it to be an HTTPS url.
	* @param array $localized_array
	* @return array
	*/
	public static function filterEmWpLocalizeScript($localized_array) {
		$localized_array['bookingajaxurl'] = self::force_ssl($localized_array['bookingajaxurl']);
		return $localized_array;
	}

	/**
	* perform additional booking form post validation, to check for required credit card fields
	* @param boolean $result
	* @param object $EM_Booking
	* @return boolean
	*/
	public static function filterEmBookingValidate($result, $EM_Booking) {
		// only perform validation if this payment method has been selected
		if (isset($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_meta['gateway'] == EM_EWAY_GATEWAY) {
			$required = array (
				'x_card_name'		=> 'You must enter credit card holder name.',
				'x_card_num'		=> 'You must enter credit card number.',
				'x_exp_date_month'	=> 'You must enter credit card expiry month.',
				'x_exp_date_year'	=> 'You must enter credit card expiry year.',
				'x_card_code'		=> 'You must enter credit card CVN/CCV.',
			);

			foreach ($required as $name => $msg) {
				$value = self::getPostValue($name);
				if (empty($value)) {
					$EM_Booking->add_error($msg);
					$result = false;
				}
			}

			$x_exp_date_month = self::getPostValue('x_exp_date_month');
			$x_exp_date_year = self::getPostValue('x_exp_date_year');
			if (!empty($x_exp_date_month) && !empty($x_exp_date_year)) {
				// check that first day of month after expiry isn't earlier than today
				$expired = mktime(0, 0, 0, 1 + $x_exp_date_month, 0, $x_exp_date_year);
				$today = time();
				if ($expired < $today) {
					$EM_Booking->add_error('Credit card expiry has passed');
					$result = false;
				}
			}

		}

		return $result;
	}

	/**
	* Turns any url into an HTTPS url.
	* @param string $url
	* @return string
	*/
	public static function force_ssl($url) {
		return str_replace('http://', 'https://', $url);
	}

	/**
	* Triggered by the em_booking_add_yourgateway action, modifies the booking status if the event isn't free and also adds a filter
	* to modify user feedback returned.
	* @param EM_Event $EM_Event
	* @param EM_Booking $EM_Booking
	* @param boolean $post_validation
	*/
	public function booking_add($EM_Event, $EM_Booking, $post_validation = false){
		$this->registered_timer = current_time('timestamp', 1);

		parent::booking_add($EM_Event, $EM_Booking, $post_validation);

		if ($post_validation && empty($EM_Booking->booking_id)) {
			add_filter('em_booking_save', array($this, 'em_booking_save'), 2, 2);
		}
	}

	/**
	* Added to filters once a booking is added. Once booking is saved, we capture payment, and approve the booking (saving a second time).
	* If payment isn't approved, just delete the booking and return false for save.
	* @param bool $result
	* @param EM_Booking $EM_Booking
	* @return bool
	*/
	public function em_booking_save($result, $EM_Booking){
		// make sure booking save was successful before we try anything
		if ($result) {
			if ($EM_Booking->get_price() > 0) {
				// handle results
				if ($this->processPayment($EM_Booking)) {
					// Set booking status, but no emails sent
					if (!get_option('em_'.EM_EWAY_GATEWAY.'_manual_approval', false) || !get_option('dbem_bookings_approval')) {
						$EM_Booking->set_status(1, false); // Approve
					}
					else {
						$EM_Booking->set_status(0, false); // Set back to normal "pending"
					}
				}
				else {
					// not good.... error inserted into booking in capture function. Delete this booking from db
					if (!is_user_logged_in() && get_option('dbem_bookings_anonymous') && !get_option('dbem_bookings_registration_disable') && !empty($EM_Booking->person_id)) {
						// delete the user we just created, only if created after em_booking_add filter is called
						// (which is when a new user for this booking would be created)
						$EM_Person = $EM_Booking->get_person();
						if (strtotime($EM_Person->data->user_registered) >= $this->registered_timer) {
							include_once(ABSPATH.'/wp-admin/includes/user.php');
							wp_delete_user($EM_Person->ID);

							// remove email confirmation
							global $EM_Notices;
							$EM_Notices->notices['confirms'] = array();
						}
					}
					$EM_Booking->delete();
					return false;
				}
			}
		}
		return $result;
	}

	/**
	* Intercepts return data after a booking has been made and adds eway vars, modifies feedback message.
	* @param array $return
	* @param EM_Booking $EM_Booking
	* @return array
	*/
	public function booking_form_feedback( $return, $EM_Booking = false ){
		// Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if (!empty($return['result'])) {
			if (!empty($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_meta['gateway'] == EM_EWAY_GATEWAY && $EM_Booking->get_price() > 0) {
				$return['message'] = get_option('em_' . EM_EWAY_GATEWAY . '_booking_feedback');
			}
			else {
				// returning a free message
				$return['message'] = get_option('em_' . EM_EWAY_GATEWAY . '_booking_feedback_free');
			}
		}
		return $return;
	}

	/*
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing eway bookings
	 * --------------------------------------------------
	 */

	/**
	* Outputs custom content and credit card information.
	*/
	public function booking_form(){
		$card_num = htmlspecialchars(self::getPostValue('x_card_num'));
		$card_name = htmlspecialchars(self::getPostValue('x_card_name'));
		$card_code = htmlspecialchars(self::getPostValue('x_card_code'));

		// build drop-down items for months
		$optMonths = '';
		$exp_date_month = self::getPostValue('x_exp_date_month');
		foreach (array('01','02','03','04','05','06','07','08','09','10','11','12') as $option) {
			$selected = selected($option, $exp_date_month, false);
			$optMonths .= "<option $selected value='$option'>$option</option>\n";
		}

		// build drop-down items for years
		$thisYear = (int) date('Y');
		$optYears = '';
  		$exp_date_year = self::getPostValue('x_exp_date_year');
		foreach (range($thisYear, $thisYear + 15) as $year) {
			$selected = selected($option, $exp_date_year, false);
			$optYears .= "<option $selected value='$year'>$year</option>\n";
		}

		// load template with passed values, capture output and register
		EwayPaymentsPlugin::loadTemplate('eventsmanager-eway-fields.php', compact('card_num', 'card_name', 'card_code', 'optMonths', 'optYears'));
	}

	/*
	 * --------------------------------------------------
	 * functions specific to eway payments
	 * --------------------------------------------------
	 */

	/**
	* attempt to process payment
	* @param EM_Booking $EM_Booking
	* @return boolean
	*/
	public function processPayment($EM_Booking){
		// process the payment
		$isLiveSite = !(get_option('em_' . EM_EWAY_GATEWAY . '_mode') == 'sandbox');
		if (!$isLiveSite && get_option('em_' . EM_EWAY_GATEWAY . '_test_force')) {
			$customerID = '87654321';
		}
		else {
			$customerID = get_option('em_' . EM_EWAY_GATEWAY . '_cust_id');
		}

		if (get_option('em_' . EM_EWAY_GATEWAY . '_stored'))
			$eway = new EwayPaymentsStoredPayment($customerID, $isLiveSite);
		else
			$eway = new EwayPaymentsPayment($customerID, $isLiveSite);

		$eway->invoiceDescription = preg_replace('/[^a-zA-Z0-9\s]/', '', $EM_Booking->get_event()->event_name); //clean event name
		$eway->invoiceReference = $EM_Booking->booking_id;						// customer invoice reference
		$eway->transactionNumber = $EM_Booking->booking_id;						// transaction reference
		$eway->cardHoldersName = self::getPostValue('x_card_name');
		$eway->cardNumber = self::getPostValue('x_card_num');
		$eway->cardExpiryMonth = self::getPostValue('x_exp_date_month');
		$eway->cardExpiryYear = self::getPostValue('x_exp_date_year');
		$eway->cardVerificationNumber = self::getPostValue('x_card_code');
		$eway->emailAddress = $EM_Booking->get_person()->user_email;
		$eway->postcode = self::getPostValue('zip');

		// for Beagle (free) security
		if (get_option('em_' . EM_EWAY_GATEWAY . '_beagle')) {
			$eway->customerCountryCode = EM_Gateways::get_customer_field('country', $EM_Booking);
		}

		// attempt to split name into parts, and hope to not offend anyone!
		$names = explode(' ', $EM_Booking->get_person()->get_name());
		if (!empty($names[0])) {
			$eway->firstName = array_shift($names);		// remove first name from array
		}
		$eway->lastName = trim(implode(' ', $names));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName = $eway->cardHoldersName;
		}

		// aggregate street, city, state, country into a single string
		$parts = array (
			EM_Gateways::get_customer_field('address', $EM_Booking),
			EM_Gateways::get_customer_field('address_2', $EM_Booking),
			EM_Gateways::get_customer_field('city', $EM_Booking),
			EM_Gateways::get_customer_field('state', $EM_Booking),
			self::getCountryName(EM_Gateways::get_customer_field('country', $EM_Booking)),
		);
		$eway->address = implode(', ', array_filter($parts, 'strlen'));

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$amount = $EM_Booking->get_price(false, false, true);
		$eway->amount = $isLiveSite ? $amount : ceil($amount);

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription = apply_filters('em_eway_invoice_desc', $eway->invoiceDescription, $EM_Booking);
		$eway->invoiceReference = apply_filters('em_eway_invoice_ref', $eway->invoiceReference, $EM_Booking);
		$eway->option1 = apply_filters('em_eway_option1', '', $EM_Booking);
		$eway->option2 = apply_filters('em_eway_option2', '', $EM_Booking);
		$eway->option3 = apply_filters('em_eway_option3', '', $EM_Booking);

//~ error_log(__METHOD__ . "\n" . print_r($eway,1));
//~ error_log(__METHOD__ . "\n" . $eway->getPaymentXML());
//~ return array('result' => 'failure');

		// Get Payment
		try {
			$result = false;
			$response = $eway->processPayment();

//~ error_log(__METHOD__ . "\n" . print_r($response,1));

			if ($response->status) {
				// transaction was successful, so record transaction number and continue
				$EM_Booking->booking_meta[EM_EWAY_GATEWAY] = array(
					'txn_id' => $response->transactionNumber,
					'authcode' => $response->authCode,
					'amount' => $response->amount,
				);
				$note = empty($response->authCode) ? '' : 'Authcode: ' . $response->authCode;
				if (!empty($response->beagleScore)) $note .= "\nBeagle score: {$response->beagleScore}";
				$status = get_option('em_' . EM_EWAY_GATEWAY . '_stored') ? 'Pending' : 'Completed';
				$this->record_transaction($EM_Booking, $response->amount, 'AUD', date('Y-m-d H:i:s', current_time('timestamp')), $response->transactionNumber, $status, $note);
				$result = true;
			}
			else {
				// transaction was unsuccessful, so record the error
				$EM_Booking->add_error($response->error);
			}
		}
		catch (Exception $e) {
			// an exception occured, so record the error
			$this->set_error_message(htmlspecialchars($e->getMessage()));
			$this->set_purchase_processed_by_purchid(1);	// failed
			return;
		}

		// Return status
		return apply_filters('em_gateway_eway_authorize', $result, $EM_Booking, $this);
	}

	/*
	* --------------------------------------------------
	* Gateway Settings Functions
	* --------------------------------------------------
	*/

	/**
	* Outputs custom fields in the settings page
	*/
	public function mysettings() {
		include EWAY_PAYMENTS_PLUGIN_ROOT . '/views/admin-events-manager.php';
	}

	/**
	* Run when saving settings, saves the settings available in self::mysettings()
	* return boolean
	*/
	public function update() {
		parent::update();

		$options = array (
			'em_' . EM_EWAY_GATEWAY . '_mode'					=> self::getPostValue('eway_mode'),
			'em_' . EM_EWAY_GATEWAY . '_cust_id'				=> self::getPostValue('eway_cust_id'),
			'em_' . EM_EWAY_GATEWAY . '_stored'					=> self::getPostValue('eway_stored'),
			'em_' . EM_EWAY_GATEWAY . '_beagle'					=> self::getPostValue('eway_beagle'),
			'em_' . EM_EWAY_GATEWAY . '_test_force'				=> self::getPostValue('eway_test_force'),
			'em_' . EM_EWAY_GATEWAY . '_manual_approval'		=> self::getPostValue('manual_approval'),
			'em_' . EM_EWAY_GATEWAY . '_booking_feedback'		=> wp_kses_data(self::getPostValue('booking_feedback')),
			'em_' . EM_EWAY_GATEWAY . '_booking_feedback_free'	=> wp_kses_data(self::getPostValue('booking_feedback_free')),
		);

		foreach ($options as $option => $value) {
			update_option($option, $value);
		}

		// default action is to return true
		return true;
	}
}
