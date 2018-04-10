<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for Events Manager
* with thanks to EM_Gateway_Authorize_AIM for showing the way...
*/
class EwayPaymentsEventsManager extends EM_Gateway {

	protected $logger;

	private $registered_timer = 0;

	/**
	* Set up gateaway and add relevant actions/filters
	*/
	public function __construct() {
		$this->gateway						= 'eway';
		$this->title						= _x('eWAY', 'Events Manager payment method title', 'eway-payment-gateway');
		$this->status						= 4;
		$this->status_txt					= _x('Processing (eWAY)', 'Events Manager status text', 'eway-payment-gateway');
		$this->button_enabled				= false;
		$this->supports_multiple_bookings	= true;

		// ensure options are present, set to defaults if not
		$defaults = array (
			"em_{$this->gateway}_option_name"				=> _x('Credit Card', 'Events Manager payment method name', 'eway-payment-gateway'),
			"em_{$this->gateway}_booking_feedback"			=> _x('Booking successful.', 'Events Manager booking feedback', 'eway-payment-gateway'),
			"em_{$this->gateway}_booking_feedback_free"		=> _x('Booking successful. You have not been charged for this booking.', 'Events Manager booking feedback free', 'eway-payment-gateway'),
			"em_{$this->gateway}_mode"						=> 'sandbox',
		);
		foreach ($defaults as $option => $value) {
			if (get_option($option) === false) {
				add_option($option, $value);
			}
		}

		// create a logger
		$this->logger = new EwayPaymentsLogging('events-manager', get_option("em_{$this->gateway}_logging", 'off'));

		// initialise parent class
		parent::__construct();

		add_action('admin_print_styles-event_page_events-manager-gateways', array($this, 'adminSettingsStyles'));

		if ($this->is_active()) {
			// force SSL for booking submissions on live site, because credit card details need to be encrypted
			if (get_option("em_{$this->gateway}_mode") === 'live') {
				add_filter('em_wp_localize_script', array(__CLASS__, 'forceBookingAjaxSSL'));
				add_filter('em_booking_form_action_url', array(__CLASS__, 'force_ssl'));
			}

			// force whole bookings page to SSL if settings require
			if (get_option("em_{$this->gateway}_ssl_force", 1)) {
				add_action('template_redirect', array(__CLASS__, 'redirect_ssl'));
			}

			// perform additional form post validation
			// but only from front -- payment form won't be there in Bookings admin!
			if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
				add_filter('em_booking_validate', array($this, 'emBookingValidate'), 10, 2);
				add_filter('em_multiple_booking_validate', array($this, 'emBookingValidate'), 10, 2);
			}
		}
	}

	/**
	* load custom styles for settings page
	*/
	public function adminSettingsStyles() {
		// only for eWAY settings page
		if (empty($_GET['gateway']) || $_GET['gateway'] !== $this->gateway) {
			return;
		}

		echo '<style>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . 'css/admin-events-manager-settings.css');
		echo '</style>';
	}

	/**
	* add page script for admin options
	*/
	public function adminSettingsScript() {
		$min	= SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "js/admin-events-manager-settings$min.js");
		echo '</script>';
	}

	/**
	* attempt to map country code to name
	* @param string $countryCode
	* @return string
	*/
	protected static function getCountryName($countryCode) {
		$name = '';

		// check for country code as non-empty string
		// NB: Events Manager Pro up to at least v2.3.6 returns array when Country field doesn't exist
		if ($countryCode && is_string($countryCode)) {
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
	* perform additional booking form post validation, to check for required credit card fields
	* @param boolean $result
	* @param object $EM_Booking
	* @return boolean
	*/
	public function emBookingValidate($result, $EM_Booking) {
		// only perform validation if this payment method has been selected
		if (isset($_REQUEST['gateway']) && $_REQUEST['gateway'] === $this->gateway) {
			$postdata		= new EwayPaymentsFormPost();

			$fields			= array(
				'card_number'	=> $postdata->getValue('x_card_num'),
				'card_name'		=> $postdata->getValue('x_card_name'),
				'expiry_month'	=> $postdata->getValue('x_exp_date_month'),
				'expiry_year'	=> $postdata->getValue('x_exp_date_year'),
				'cvn'			=> $postdata->getValue('x_card_code'),
			);

			$errors			= $postdata->verifyCardDetails($fields);

			if (!empty($errors)) {
				foreach ($errors as $error) {
					$EM_Booking->add_error($error);
				}
				$result = false;
			}
		}

		return $result;
	}

	/**
	* This function intercepts the previous booking form url from the javascript localized array of EM variables and forces it to be an HTTPS url.
	* @param array $localized_array
	* @return array
	*/
	public static function forceBookingAjaxSSL($localized_array) {
		$localized_array['bookingajaxurl'] = self::force_ssl($localized_array['bookingajaxurl']);
		return $localized_array;
	}

	/**
	* Turns any url into an HTTPS url.
	* @param string $url
	* @return string
	*/
	public static function force_ssl($url) {
		// only fix if source URL starts with http://
		if (stripos($url, 'http://') === 0) {
			$url = 'https' . substr($url, 4);
		}

		return $url;
	}

	/**
	* if page is an event and it has a booking form, make sure it's on SSL
	*/
	public static function redirect_ssl() {
		global $post;

		// only if we're on an event page, and not SSL
		if (!empty($post->post_type) && $post->post_type === EM_POST_TYPE_EVENT && !is_ssl()) {
			try {
				// create event object, check that it has bookings
				$event = new EM_Event($post);
				if ($event->event_rsvp) {
					// redirect to SSL
					$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					wp_redirect($url);
				}
			}
			catch (Exception $e) {
				// NOP
			}
		}
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
			if (get_option('dbem_multiple_bookings') && get_class($EM_Booking) === 'EM_Multiple_Booking' ) {
				add_filter('em_multiple_booking_save', array($this, 'em_booking_save'), 2, 2);
			}
			else {
				add_filter('em_booking_save', array($this, 'em_booking_save'), 2, 2);
			}
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
					if (!get_option("em_{$this->gateway}_manual_approval", false) || !get_option('dbem_bookings_approval')) {
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
							if (is_multisite()) {
								include_once(ABSPATH.'/wp-admin/includes/ms.php');
								wpmu_delete_user($EM_Person->ID);
							}
							else {
								include_once(ABSPATH.'/wp-admin/includes/user.php');
								wp_delete_user($EM_Person->ID);
							}

							// remove email confirmation
							global $EM_Notices;
							$EM_Notices->notices['confirms'] = array();
						}
					}

					$EM_Booking->manage_override = true;		// send emails even when admin books
					$EM_Booking->delete();
					$EM_Booking->manage_override = false;

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
			if (!empty($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_meta['gateway'] === $this->gateway && $EM_Booking->get_price() > 0) {
				$return['message'] = get_option("em_{$this->gateway}_booking_feedback");
			}
			else {
				// returning a free message
				$return['message'] = get_option("em_{$this->gateway}_booking_feedback_free");
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
		$card_msg	= esc_html(get_option("em_{$this->gateway}_card_msg"));

		$postdata = new EwayPaymentsFormPost();

		$card_num	= esc_html($postdata->getValue('x_card_num'));
		$card_name	= esc_html($postdata->getValue('x_card_name'));
		$card_code	= esc_html($postdata->getValue('x_card_code'));

		$optMonths = EwayPaymentsFormUtils::getMonthOptions($postdata->getValue('x_exp_date_month'));
		$optYears  = EwayPaymentsFormUtils::getYearOptions($postdata->getValue('x_exp_date_year'));

		// load template with passed values, capture output and register
		EwayPaymentsPlugin::loadTemplate('eventsmanager-eway-fields.php', compact('card_msg', 'card_num', 'card_name', 'card_code', 'optMonths', 'optYears'));

		// maybe set up Client Side Encryption
		$creds = $this->getApiCredentials();
		if (!empty($creds['ecrypt_key'])) {
			wp_enqueue_script('eway-payment-gateway-ecrypt');
			add_action('wp_footer', array($this, 'ecryptScript'));
		}
	}

	/**
	* configure the scripts for client-side encryption
	*/
	public function ecryptScript() {
		$creds	= $this->getApiCredentials();

		$vars = array(
			'mode'		=> 'events-manager',
			'key'		=> $creds['ecrypt_key'],
			'form'		=> 'form.em-booking-form',
			'fields'	=> array(
							'#eway_card_num'	=> array('name' => 'cse:x_card_num', 'is_cardnum' => true),
							'#eway_card_code'	=> array('name' => 'cse:x_card_code', 'is_cardnum' => false),
						),
		);

		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_vars', $vars);
	}

	/**
	* attempt to process payment
	* @param EM_Booking $EM_Booking
	* @return boolean
	*/
	public function processPayment($EM_Booking){
		// allow plugins/themes to modify transaction ID; NB: must remain unique for eWAY account!
		$transactionID = apply_filters('em_eway_trans_number', $EM_Booking->booking_id);

		$capture	= !get_option("em_{$this->gateway}_stored");
		$useSandbox	= (get_option("em_{$this->gateway}_mode") === 'sandbox');
		$creds		= apply_filters('em_eway_credentials', $this->getApiCredentials(), $useSandbox, $EM_Booking);
		$eway		= EwayPaymentsFormUtils::getApiWrapper($creds, $capture, $useSandbox);

		$postdata = new EwayPaymentsFormPost();

		$eway->invoiceDescription			= $EM_Booking->get_event()->event_name;
		$eway->invoiceReference				= $EM_Booking->booking_id;						// customer invoice reference
		$eway->transactionNumber			= $transactionID;
		$eway->amount						= $EM_Booking->get_price(false, false, true);
		$eway->currencyCode					= get_option('dbem_bookings_currency', 'AUD');
		$eway->cardHoldersName				= $postdata->getValue('x_card_name');
		$eway->cardNumber					= $postdata->cleanCardnumber($postdata->getValue('x_card_num'));
		$eway->cardExpiryMonth				= $postdata->getValue('x_exp_date_month');
		$eway->cardExpiryYear				= $postdata->getValue('x_exp_date_year');
		$eway->cardVerificationNumber		= $postdata->getValue('x_card_code');
		$eway->emailAddress					= $EM_Booking->get_person()->user_email;
		$eway->address1						= EM_Gateways::get_customer_field('address', $EM_Booking);
		$eway->address2						= EM_Gateways::get_customer_field('address_2', $EM_Booking);
		$eway->suburb						= EM_Gateways::get_customer_field('city', $EM_Booking);
		$eway->state						= EM_Gateways::get_customer_field('state', $EM_Booking);
		$eway->postcode						= $postdata->getValue('zip');
		$eway->country						= EM_Gateways::get_customer_field('country', $EM_Booking);
		$eway->countryName					= $eway->country;

		// convert Events Manager country code into country name
		if ($eway->country) {
			$eway->countryName = self::getCountryName($eway->country);
		}

		// attempt to split name into parts, and hope to not offend anyone!
		$names = explode(' ', $EM_Booking->get_person()->get_name());
		if (!empty($names[0])) {
			$eway->firstName				= array_shift($names);		// remove first name from array
		}
		$eway->lastName						= trim(implode(' ', $names));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName = $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->amount						= apply_filters('em_eway_amount', $eway->amount, $EM_Booking);
		$eway->invoiceDescription			= apply_filters('em_eway_invoice_desc', $eway->invoiceDescription, $EM_Booking);
		$eway->invoiceReference				= apply_filters('em_eway_invoice_ref', $eway->invoiceReference, $EM_Booking);
		$eway->options						= array_filter(array(
													apply_filters('em_eway_option1', '', $EM_Booking),
													apply_filters('em_eway_option2', '', $EM_Booking),
													apply_filters('em_eway_option3', '', $EM_Booking),
												), 'strlen');

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

		// Get Payment
		try {
			$result = false;
			$response = $eway->processPayment();

			if ($response->TransactionStatus) {
				// transaction was successful, so record transaction number and continue
				$EM_Booking->booking_meta[$this->gateway] = array(
					'txn_id'	=> $response->TransactionID,
					'authcode'	=> $response->AuthorisationCode,
					'amount'	=> $response->Payment->TotalAmount,
				);

				$notes = array();
				if (!empty($response->AuthorisationCode)) {
					$notes[] = sprintf(__('Authcode: %s', 'eway-payment-gateway'), $response->AuthorisationCode);
				}
				if ($response->BeagleScore > 0) {
					$notes[] = sprintf(__('Beagle score: %s', 'eway-payment-gateway'), $response->BeagleScore);
				}
				$note = implode("\n", $notes);

				$status = get_option("em_{$this->gateway}_stored") ? 'Pending' : 'Completed';
				$this->record_transaction($EM_Booking, $response->Payment->TotalAmount, $eway->currencyCode, date('Y-m-d H:i:s', current_time('timestamp')), $response->TransactionID, $status, $note);
				$result = true;

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
					$eway->invoiceReference, $response->TransactionID, get_option("em_{$this->gateway}_stored") ? 'pending' : 'completed',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
			}
			else {
				// transaction was unsuccessful, so record the error
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$EM_Booking->add_error($error_msg);

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $eway->invoiceReference, $response->getErrorsForLog()));
				if ($response->BeagleScore > 0) {
					$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
				}
			}
		}
		catch (Exception $e) {
			// an exception occured, so record the error
			$EM_Booking->add_error(nl2br($e->getMessage()));
			$this->logger->log('error', $e->getMessage());
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
		add_action('admin_print_footer_scripts', array($this, 'adminSettingsScript'));

		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-events-manager.php';
	}

	/**
	* Run when saving settings, saves the settings available in self::mysettings()
	* return boolean
	*/
	public function update() {
		$options = array (
			"em_{$this->gateway}_mode",
			"em_{$this->gateway}_api_key",
			"em_{$this->gateway}_password",
			"em_{$this->gateway}_ecrypt_key",
			"em_{$this->gateway}_cust_id",
			"em_{$this->gateway}_sandbox_api_key",
			"em_{$this->gateway}_sandbox_password",
			"em_{$this->gateway}_sandbox_ecrypt_key",
			"em_{$this->gateway}_stored",
			"em_{$this->gateway}_ssl_force",
			"em_{$this->gateway}_logging",
			"em_{$this->gateway}_card_msg",
			"em_{$this->gateway}_manual_approval",
			"em_{$this->gateway}_booking_feedback",
			"em_{$this->gateway}_booking_feedback_free",
		);

		// filters for specific data
		add_filter("gateway_update_em_{$this->gateway}_mode", 'sanitize_text_field');
		add_filter("gateway_update_em_{$this->gateway}_cust_id", 'sanitize_text_field');
		add_filter("gateway_update_em_{$this->gateway}_card_msg", 'sanitize_text_field');
		add_filter("gateway_update_em_{$this->gateway}_logging", 'sanitize_text_field');

		add_filter("gateway_update_em_{$this->gateway}_api_key", 'strip_tags');
		add_filter("gateway_update_em_{$this->gateway}_password", 'strip_tags');
		add_filter("gateway_update_em_{$this->gateway}_ecrypt_key", 'strip_tags');
		add_filter("gateway_update_em_{$this->gateway}_sandbox_api_key", 'strip_tags');
		add_filter("gateway_update_em_{$this->gateway}_sandbox_password", 'strip_tags');
		add_filter("gateway_update_em_{$this->gateway}_sandbox_ecrypt_key", 'strip_tags');

		add_filter("gateway_update_em_{$this->gateway}_booking_feedback", 'wp_kses_data');
		add_filter("gateway_update_em_{$this->gateway}_booking_feedback_free", 'wp_kses_data');

		add_filter("gateway_update_em_{$this->gateway}_stored", 'intval');
		add_filter("gateway_update_em_{$this->gateway}_ssl_force", 'intval');
		add_filter("gateway_update_em_{$this->gateway}_manual_approval", 'intval');

		return parent::update($options);
	}

	/**
	* get API credentials based on settings
	* @return array
	*/
	protected function getApiCredentials() {
		$useSandbox	= (get_option("em_{$this->gateway}_mode") === 'sandbox');

		if (!$useSandbox) {
			$creds = array(
				'api_key'		=> get_option("em_{$this->gateway}_api_key"),
				'password'		=> get_option("em_{$this->gateway}_password"),
				'ecrypt_key'	=> get_option("em_{$this->gateway}_ecrypt_key"),
				'customerid'	=> get_option("em_{$this->gateway}_cust_id"),
			);
		}
		else {
			$creds = array(
				'api_key'		=> get_option("em_{$this->gateway}_sandbox_api_key"),
				'password'		=> get_option("em_{$this->gateway}_sandbox_password"),
				'ecrypt_key'	=> get_option("em_{$this->gateway}_sandbox_ecrypt_key"),
				'customerid'	=> EWAY_PAYMENTS_TEST_CUSTOMER,
			);
		}

		return $creds;
	}

}
