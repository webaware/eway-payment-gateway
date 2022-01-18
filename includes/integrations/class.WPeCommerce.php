<?php
namespace webaware\eway_payment_gateway;

use wpsc_merchant;
use WPSC_Purchase_Log;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * payment gateway integration for WP eCommerce
 * @link http://docs.wpecommerce.org/category/payment-gateways/
 */
final class MethodWPeCommerce extends wpsc_merchant {

	public $name = 'eway';

	private Logging $logger;

	const WPSC_GATEWAY_NAME = 'wpsc_merchant_eway';

	/**
	 * register new payment gateway
	 */
	public static function register_eway(array $gateways) : array {
		// register the gateway class and additional functions
		$gateways[] = [
			'name'						=> _x('Eway payment gateway', 'WP eCommerce payment method name', 'eway-payment-gateway'),
			'api_version'				=> 2.0,
			'image'						=> plugins_url('static/images/eway-tiny.svg', EWAY_PAYMENTS_PLUGIN_FILE),
			'internalname'				=> self::WPSC_GATEWAY_NAME,
			'class_name'				=> __CLASS__,
			'has_recurring_billing'		=> false,
			'wp_admin_cannot_cancel'	=> true,
			'display_name'				=> _x('Eway Credit Card Payment', 'WP eCommerce payment method display name', 'eway-payment-gateway'),
			'form'						=> [__CLASS__, 'configForm'],
			'submit_function'			=> [__CLASS__, 'saveConfig'],
			'payment_type'				=> 'credit_card',
			'requirements'				=> [
				'php_version' => EWAY_PAYMENTS_MIN_PHP,
			],
		];

		// register extra fields we require on the checkout form
		self::setCheckoutFields();

		add_action('wpsc_before_shopping_cart_page', [__CLASS__, 'enqueueCheckoutScript']);

		// also register admin hooks if required
		if (is_admin()) {
			add_action('wpsc_billing_details_bottom', [__CLASS__, 'actionBillingDetailsBottom']);
			add_action('admin_print_footer_scripts-settings_page_wpsc-settings', [__CLASS__, 'adminSettingsScript']);
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
		$this->logger = new Logging('wp-ecommerce', get_option('eway_logging', 'off'));

		parent::__construct($purchase_id, $is_receiving);
	}

	/**
	 * grab the gateway-specific data from the checkout form post
	 */
	public function construct_value_array() {
		$postdata = new FormPost();

		$country_field = get_option('eway_form_country');
		if ($country_field) {
			$country = $postdata->getSubkey('collected_data', get_option('eway_form_country'));
			$country = empty($country[0]) ? '' : $country[0];
		}
		else {
			$country = '';
		}

		$this->collected_gateway_data = [
			'card_number'	=> (string) $postdata->cleanCardnumber($postdata->getValue('card_number')),
			'card_name'		=> (string) $postdata->getValue('card_name'),
			'expiry_month'	=> (string) $postdata->getValue('expiry_month'),
			'expiry_year'	=> (string) $postdata->getValue('expiry_year'),
			'c_v_n'			=> (string) $postdata->getValue('cvn'),

			// additional fields from checkout
			'first_name'	=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_first_name')),
			'last_name'		=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_last_name')),
			'address'		=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_address')),
			'city'			=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_city')),
			'state'			=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_state')),
			'country'		=> $country,
			'post_code'		=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_post_code')),
			'email'			=> (string) $postdata->getSubkey('collected_data', get_option('eway_form_email')),
		];
	}

	/**
	 * submit to gateway
	 */
	public function submit() {
		// check for missing or invalid values
		$errors = $this->validateData();

		// if there were errors, fail the transaction so that user can fix things up
		if ($errors) {
			$this->set_purchase_processed_by_purchid(1);	// failed
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

		// allow plugins/themes to modify transaction ID; NB: must remain unique for Eway account!
		$transactionID = apply_filters('wpsc_merchant_eway_trans_number', $this->purchase_id);

		$capture	= !get_option('wpsc_merchant_eway_stored');
		$useSandbox	= (bool) get_option('eway_test');
		$creds		= apply_filters('wpsc_eway_credentials', self::getApiCredentials(), $useSandbox, $this->purchase_id);

		if ($creds->isMissingCredentials()) {
			$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
			$this->set_error_message(__('Eway payments is not configured for payments yet', 'eway-payment-gateway'));
			return;
		}

		$eway		= new EwayRapidAPI($creds->api_key, $creds->password, $useSandbox);
		$eway->capture = $capture;

		$customer = new CustomerDetails;
		$customer->setFirstName($this->collected_gateway_data['first_name']);
		$customer->setLastName($this->collected_gateway_data['last_name']);
		$customer->setStreet1($this->collected_gateway_data['address']);
		$customer->setCity($this->collected_gateway_data['city']);
		$customer->setState($this->collected_gateway_data['state']);
		$customer->setPostalCode($this->collected_gateway_data['post_code']);
		$customer->setEmail($this->collected_gateway_data['email']);

		$customer->CardDetails = new CardDetails(
			$this->collected_gateway_data['card_name'],
			$this->collected_gateway_data['card_number'],
			$this->collected_gateway_data['expiry_month'],
			$this->collected_gateway_data['expiry_year'],
			$this->collected_gateway_data['c_v_n'],
		);

		// convert wp-e-commerce country code into country name
		if ($this->collected_gateway_data['country']) {
			$customer->setCountry(wpsc_get_country($this->collected_gateway_data['country']));
		}

		// use cardholder name for last name if no customer name entered
		if (empty($customer->FirstName) && empty($customer->LastName)) {
			$customer->setLastName($customer->CardDetails->Name);
		}

		// only populate payment record if there's an amount value
		$payment = new PaymentDetails;
		$amount = $purchase_logs->get('totalprice');
		$currency = wpsc_get_currency_code();
		if ($amount > 0) {
			$payment->setTotalAmount($amount, $currency);
			$payment->setCurrencyCode($currency);
			$payment->setInvoiceReference($transactionID);
			$payment->setInvoiceDescription(apply_filters('wpsc_merchant_eway_invoice_desc', get_bloginfo('name'), $this->purchase_id));
			$payment->setInvoiceNumber(apply_filters('wpsc_merchant_eway_invoice_ref', $this->purchase_id, $this->purchase_id));
		}

		// allow plugins/themes to set option fields
		$options = get_api_options([
			apply_filters('wpsc_merchant_eway_option1', '', $this->purchase_id),
			apply_filters('wpsc_merchant_eway_option2', '', $this->purchase_id),
			apply_filters('wpsc_merchant_eway_option3', '', $this->purchase_id),
		]);

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live',
			$payment->InvoiceNumber, $payment->InvoiceReference, $payment->TotalAmount, $customer->CardDetails->Number));

		try {
			$response = $eway->processPayment($customer, null, $payment, $options);

			if ($response->TransactionStatus) {
				// transaction was successful, so record transaction number and continue
				if ($capture) {
					$status = 3; // WPSC_Purchase_Log::ACCEPTED_PAYMENT
				}
				else {
					$status = 2; // WPSC_Purchase_Log::ORDER_RECEIVED
				}
				$log_details = [
					'processed'			=> $status,
					'transactid'		=> $response->TransactionID,
					'authcode'			=> $response->AuthorisationCode,
				];

				if ($response->BeagleScore > 0) {
					$log_details['notes'] = sprintf(__('Beagle score: %s', 'eway-payment-gateway'), $response->BeagleScore);
				}

				wpsc_update_purchase_log_details($this->purchase_id, $log_details);

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
					$payment->InvoiceNumber, $response->TransactionID, $capture ? 'accepted payment' : 'order received',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));

				$this->go_to_transaction_results($this->cart_data['session_id']);
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$status = 6; // WPSC_Purchase_Log::PAYMENT_DECLINED
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$this->set_error_message($error_msg);

				$log_details = [
					'processed'			=> $status,
					'notes'				=> __('Transaction failed', 'eway-payment-gateway') . '; ' . $response->getErrorsForLog(),
				];
				wpsc_update_purchase_log_details($this->purchase_id, $log_details);

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $payment->InvoiceNumber, $response->getErrorsForLog()));
				if ($response->BeagleScore > 0) {
					$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
				}

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
	private function validateData() : int {
		$postdata		= new FormPost();

		$fields			= [
			'card_number'	=> (string) $postdata->getValue('card_number'),
			'card_name'		=> (string) $postdata->getValue('card_name'),
			'expiry_month'	=> (string) $postdata->getValue('expiry_month'),
			'expiry_year'	=> (string) $postdata->getValue('expiry_year'),
			'cvn'			=> (string) $postdata->getValue('cvn'),
		];

		$errors			= $postdata->verifyCardDetails($fields);

		if (!empty($errors)) {
			foreach ($errors as $error) {
				$this->set_error_message($error);
			}
		}

		return count($errors);
	}

	/**
	 * add page script for admin options
	 */
	public static function adminSettingsScript() : void {
		$tab = $_GET['tab'] ?? '';
		if ($tab !== 'gateway') {
			return;
		}

		$min = SCRIPT_DEBUG ? '' : '.min';

		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "static/js/admin-wpsc-settings$min.js");
		echo '</script>';
	}

	/**
	 * tell wp-e-commerce about fields we require on the checkout form
	 */
	private static function setCheckoutFields() : void {
		global $gateway_checkout_form_fields;

		// check if this gateway is selected for checkout payments
		if (in_array(self::WPSC_GATEWAY_NAME, (array) get_option('custom_gateway_options'))) {
			$optMonths = get_month_options();
			$optYears  = get_year_options();

			// use TH for field label cells if selected, otherwise use TD (default wp-e-commerce behaviour)
			$th = get_option('wpsc_merchant_eway_th') ? 'th' : 'td';

			// optional message to show above credit card fields
			$card_msg = esc_html(get_option('wpsc_merchant_eway_card_msg'));

			// load template with passed values, capture output and register
			ob_start();
			eway_load_template('wpsc-eway-fields.php', compact('th', 'card_msg', 'optMonths', 'optYears'));
			$gateway_checkout_form_fields[self::WPSC_GATEWAY_NAME] = ob_get_clean();
		}
	}

	/**
	 * display additional fields for gateway config form
	 */
	public static function configForm() : string {
		$eway_stored	= get_option('wpsc_merchant_eway_stored') ? '1' : '0';
		$eway_test		= get_option('eway_test')                 ? '1' : '0';
		$eway_th		= get_option('wpsc_merchant_eway_th')     ? '1' : '0';
		$eway_beagle	= get_option('wpsc_merchant_eway_beagle') ? '1' : '0';
		$eway_logging	= get_option('eway_logging', 'off');

		ob_start();

		if (self::getApiCredentials()->isMissingCredentials()) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-notice-missing-creds.php';
		}

		require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-wpsc.php';
		return ob_get_clean();
	}

	/**
	 * save config details from payment gateway admin
	 */
	public static function saveConfig() : void {
		if (empty($_POST['wpsc_merchant_eway_settings'])) {
			return;
		}

		$postdata = new FormPost();

		update_option('eway_api_key',					strip_tags($postdata->getValue('eway_api_key')));
		update_option('eway_password',					strip_tags($postdata->getValue('eway_password')));
		update_option('eway_ecrypt_key',				strip_tags($postdata->getValue('eway_ecrypt_key')));
		update_option('eway_sandbox_api_key',			strip_tags($postdata->getValue('eway_sandbox_api_key')));
		update_option('eway_sandbox_password',			strip_tags($postdata->getValue('eway_sandbox_password')));
		update_option('eway_sandbox_ecrypt_key',		strip_tags($postdata->getValue('eway_sandbox_ecrypt_key')));
		update_option('wpsc_merchant_eway_stored',		$postdata->getValue('eway_stored') ? '1' : '0');
		update_option('eway_test',						$postdata->getValue('eway_test') ? '1' : '0');
		update_option('eway_logging',					sanitize_text_field($postdata->getValue('eway_logging')));
		update_option('wpsc_merchant_eway_th',			$postdata->getValue('eway_th') ? '1' : '0');
		update_option('wpsc_merchant_eway_card_msg',	sanitize_text_field($postdata->getValue('eway_card_msg')));

		if (isset($_POST['eway_form']) && is_array($_POST['eway_form'])) {
			foreach ($_POST['eway_form'] as $form => $value) {
				update_option('eway_form_' . sanitize_key($form), $value ? absint($value) : '');
			}
		}
	}

	/**
	 * hook billing details display on admin, to show Eway transaction number and authcode
	 */
	public static function actionBillingDetailsBottom() : void {
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
	public static function showCheckoutFormFields($selected) : void {
		static $fields = false;

		if ($fields === false) {
			global $wpdb;
			$sql = sprintf("select id,name,unique_name from `%s` where active = '1' and type != 'heading'", WPSC_TABLE_CHECKOUT_FORMS);
			$fields = $wpdb->get_results($sql);
		}

		echo '<option value="">Please choose</option>';
		foreach ($fields as $field) {
			printf('<option value="%s"%s>%s (%s)</option>', esc_attr($field->id), selected($field->id, $selected, false), esc_html($field->name), esc_html($field->unique_name));
		}
	}

	/**
	 * maybe enqueue client side encryption for the checkout form
	 */
	public static function enqueueCheckoutScript($gateway) : void {
		if (self::isActive() && self::getApiCredentials()->hasCSEKey()) {
			wp_enqueue_script('eway-payment-gateway-ecrypt');
			add_action('wp_footer', [__CLASS__, 'ecryptScript']);
		}
	}

	/**
	 * configure the scripts for client-side encryption
	 */
	public static function ecryptScript() : void {
		$creds	= self::getApiCredentials();

		$vars = [
			'mode'		=> 'wp-e-commerce',
			'key'		=> $creds->ecrypt_key,
			'form'		=> 'form.wpsc_checkout_forms',
			'fields'	=> [
				'#eway_card_number'	=> ['name' => 'cse:card_number', 'is_cardnum' => true],
				'#eway_cvn'			=> ['name' => 'cse:cvn', 'is_cardnum' => false],
			],
		];

		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_vars', $vars);
	}

	/**
	 * get API credentials based on settings
	 */
	private static function getApiCredentials() : Credentials {
		if (!get_option('eway_test')) {
			$creds = new Credentials(
				get_option('eway_api_key'),
				get_option('eway_password'),
				get_option('eway_ecrypt_key'),
			);
		}
		else {
			$creds = new Credentials(
				get_option('eway_sandbox_api_key'),
				get_option('eway_sandbox_password'),
				get_option('eway_sandbox_ecrypt_key'),
			);
		}

		return $creds;
	}

	/**
	 * test whether this gateway is enabled
	 */
	private static function isActive() : bool {
		return in_array(self::WPSC_GATEWAY_NAME, get_option('custom_gateway_options', []));
	}

}
