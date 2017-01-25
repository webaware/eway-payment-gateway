<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for WP eCommerce
* @link http://docs.wpecommerce.org/category/payment-gateways/
*/
class EwayPaymentsWpsc extends wpsc_merchant {

	public $name = 'eway';

	protected $logger;

	const WPSC_GATEWAY_NAME = 'wpsc_merchant_eway';

	/**
	* register new payment gateway
	* @param array $gateways array of registered gateways
	* @return array
	*/
	public static function register($gateways) {
		// register the gateway class and additional functions
		$gateways[] = array (
			'name'						=> _x('eWAY payment gateway', 'WP eCommerce payment method name', 'eway-payment-gateway'),
			'api_version'				=> 2.0,
			'image'						=> EwayPaymentsPlugin::getUrlPath() . 'images/eway-tiny.png',
			'internalname'				=> self::WPSC_GATEWAY_NAME,
			'class_name'				=> __CLASS__,
			'has_recurring_billing'		=> false,
			'wp_admin_cannot_cancel'	=> true,
			'display_name'				=> _x('eWAY Credit Card Payment', 'WP eCommerce payment method display name', 'eway-payment-gateway'),
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
	* initialise class
	* @param int $purchase_id
	* @param bool $is_receiving
	*/
	public function __construct($purchase_id = null, $is_receiving = false) {
		// create logger
		$this->logger = new EwayPaymentsLogging('wp-ecommerce', get_option('eway_logging', 'off'));

		parent::__construct($purchase_id, $is_receiving);
	}

	/**
	* grab the gateway-specific data from the checkout form post
	*/
	public function construct_value_array() {
		$postdata = new EwayPaymentsFormPost();

		$country_field = get_option('eway_form_country');
		if ($country_field) {
			$country = $postdata->getSubkey('collected_data', get_option('eway_form_first_name'));
			$country = empty($country[0]) ? '' : $country[0];
		}
		else {
			$country = '';
		}

		$this->collected_gateway_data = array (
			'card_number'	=> $postdata->cleanCardnumber($postdata->getValue('card_number')),
			'card_name'		=> $postdata->getValue('card_name'),
			'expiry_month'	=> $postdata->getValue('expiry_month'),
			'expiry_year'	=> $postdata->getValue('expiry_year'),
			'c_v_n'			=> $postdata->getValue('cvn'),

			// additional fields from checkout
			'first_name'	=> $postdata->getSubkey('collected_data', get_option('eway_form_first_name')),
			'last_name'		=> $postdata->getSubkey('collected_data', get_option('eway_form_last_name')),
			'address'		=> $postdata->getSubkey('collected_data', get_option('eway_form_address')),
			'city'			=> $postdata->getSubkey('collected_data', get_option('eway_form_city')),
			'state'			=> $postdata->getSubkey('collected_data', get_option('eway_form_state')),
			'country'		=> $country,
			'post_code'		=> $postdata->getSubkey('collected_data', get_option('eway_form_post_code')),
			'email'			=> $postdata->getSubkey('collected_data', get_option('eway_form_email')),
		);
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
			$purchase_logs = new WPSC_Purchase_Log($this->purchase_id);
		}
		elseif (!empty($this->session_id)) {
			$purchase_logs = new WPSC_Purchase_Log($this->session_id, 'sessionid');

			$this->purchase_id = $purchase_logs->get('id');
		}
		else {
			$this->set_error_message(__('No cart ID and no active session!', 'eway-payment-gateway'));
			return;
		}

		// process the payment
		$isLiveSite = !get_option('eway_test');
		$useStored = get_option('wpsc_merchant_eway_stored');

		$customerID = get_option('ewayCustomerID_id');
		$customerID = apply_filters('wpsc_merchant_eway_customer_id', $customerID, $isLiveSite, $this->purchase_id);

		// allow plugins/themes to modify transaction ID; NB: must remain unique for eWAY account!
		$transactionID = apply_filters('wpsc_merchant_eway_trans_number', $this->purchase_id);

		if ($useStored) {
			$eway = new EwayPaymentsStoredPayment($customerID, $isLiveSite);
		}
		else {
			$eway = new EwayPaymentsPayment($customerID, $isLiveSite);
		}

		$eway->invoiceDescription		= get_bloginfo('name');
		$eway->invoiceReference			= $this->purchase_id;								// customer invoice reference
		$eway->transactionNumber		= $transactionID;
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
		$eway->address					= implode(', ', array_filter($parts, 'strlen'));

		// use cardholder name for last name if no customer name entered
		if (empty($eway->firstName) && empty($eway->lastName)) {
			$eway->lastName				= $eway->cardHoldersName;
		}

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription		= apply_filters('wpsc_merchant_eway_invoice_desc', $eway->invoiceDescription, $this->purchase_id);
		$eway->invoiceReference			= apply_filters('wpsc_merchant_eway_invoice_ref', $eway->invoiceReference, $this->purchase_id);
		$eway->option1					= apply_filters('wpsc_merchant_eway_option1', '', $this->purchase_id);
		$eway->option2					= apply_filters('wpsc_merchant_eway_option2', '', $this->purchase_id);
		$eway->option3					= apply_filters('wpsc_merchant_eway_option3', '', $this->purchase_id);

		// if live, pass through amount exactly, but if using test site, round up to whole dollars or eWAY will fail
		$total = $purchase_logs->get('totalprice');
		$eway->amount					= $isLiveSite ? $total : ceil($total);
		if ($eway->amount != $total) {
			$this->logger->log('info', sprintf('amount rounded up from %1$s to %2$s, to pass test gateway',
				number_format($total, 2), number_format($eway->amount, 2)));
		}

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$isLiveSite ? 'live' : 'test', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

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
				$log_details = array(
					'processed'			=> $status,
					'transactid'		=> $response->transactionNumber,
					'authcode'			=> $response->authCode,
				);

				if (!empty($response->beagleScore)) {
					$log_details['notes'] = sprintf(__('Beagle score: %s', 'eway-payment-gateway'), $response->beagleScore);
				}

				wpsc_update_purchase_log_details($this->purchase_id, $log_details);

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
					$eway->invoiceReference, $response->transactionNumber, $useStored == 'yes' ? 'order received' : 'accepted payment',
					$response->amount, $response->authCode, $response->beagleScore));

				$this->go_to_transaction_results($this->cart_data['session_id']);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$status = 6; // WPSC_Purchase_Log::PAYMENT_DECLINED
				$this->set_error_message(nl2br(esc_html($response->error)));

				$log_details = array(
					'processed'			=> $status,
					'notes'				=> $response->error,
				);
				wpsc_update_purchase_log_details($this->purchase_id, $log_details);

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $eway->invoiceReference, $response->error));

				return;
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$this->logger->log('error', $e->getMessage());
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
		$postdata		= new EwayPaymentsFormPost();

		$fields			= array(
			'card_number'	=> $postdata->getValue('card_number'),
			'card_name'		=> $postdata->getValue('card_name'),
			'expiry_month'	=> $postdata->getValue('expiry_month'),
			'expiry_year'	=> $postdata->getValue('expiry_year'),
			'cvn'			=> $postdata->getValue('cvn'),
		);

		$errors			= $postdata->verifyCardDetails($fields);

		if (!empty($errors)) {
			foreach ($errors as $error) {
				$this->set_error_message($error);
			}
		}

		return count($errors);
	}

	/**
	* tell wp-e-commerce about fields we require on the checkout form
	*/
	protected static function setCheckoutFields() {
		global $gateway_checkout_form_fields;

		// check if this gateway is selected for checkout payments
		if (in_array(self::WPSC_GATEWAY_NAME, (array) get_option('custom_gateway_options'))) {
			$optMonths = EwayPaymentsFormUtils::getMonthOptions();
			$optYears  = EwayPaymentsFormUtils::getYearOptions();

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
		include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-wpsc.php';
		return ob_get_clean();
	}

	/**
	* save config details from payment gateway admin
	*/
	public static function saveConfig() {
		if (isset($_POST['ewayCustomerID_id'])) {
			update_option('ewayCustomerID_id', sanitize_text_field(wp_unslash($_POST['ewayCustomerID_id'])));
		}

		if (isset($_POST['eway_stored'])) {
			update_option('wpsc_merchant_eway_stored', $_POST['eway_stored'] ? '1' : '0');
		}

		if (isset($_POST['eway_test'])) {
			update_option('eway_test', $_POST['eway_test'] ? '1' : '0');
		}

		if (isset($_POST['eway_logging'])) {
			update_option('eway_logging', sanitize_text_field(wp_unslash($_POST['eway_logging'])));
		}

		if (isset($_POST['eway_th'])) {
			update_option('wpsc_merchant_eway_th', $_POST['eway_th'] ? '1' : '0');
		}

		if (isset($_POST['eway_beagle'])) {
			update_option('wpsc_merchant_eway_beagle', $_POST['eway_beagle'] ? '1' : '0');
		}

		if (isset($_POST['eway_card_msg'])) {
			update_option('wpsc_merchant_eway_card_msg', sanitize_text_field(wp_unslash($_POST['eway_card_msg'])));
		}

		if (isset($_POST['eway_form'])) {
			foreach ((array)$_POST['eway_form'] as $form => $value) {
				update_option('eway_form_' . $form, $value ? absint($value) : '');
			}
		}

		return true;
	}

	/**
	* hook billing details display on admin, to show eWAY transaction number and authcode
	*/
	public static function actionBillingDetailsBottom() {
		global $purchlogitem;

		if (empty($purchlogitem->extrainfo->gateway) || $purchlogitem->extrainfo->gateway !== self::WPSC_GATEWAY_NAME) {
			return;
		}

		if (!empty($purchlogitem->extrainfo->transactid) || !empty($purchlogitem->extrainfo->authcode)) {
			include EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-wpsc-billing-details.php';
		}
	}

	/**
	* show select list options for checkout form fields
	* @param int $selected
	*/
	public static function showCheckoutFormFields($selected) {
		static $fields = false;

		if ($fields === false) {
			global $wpdb;
			$fields = $wpdb->get_results(sprintf("select id,name,unique_name from `%s` where active = '1' and type != 'heading'", WPSC_TABLE_CHECKOUT_FORMS));
		}

		echo '<option value="">Please choose</option>';
		foreach ($fields as $field) {
			printf('<option value="%s"%s>%s (%s)</option>', esc_attr($field->id), selected($field->id, $selected, false), esc_html($field->name), esc_html($field->unique_name));
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
