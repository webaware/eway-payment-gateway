<?php
namespace webaware\eway_payment_gateway;

use WP_User;
use AWPCP_Ad;
use AWPCP_Exception;
use AWPCP_PaymentGateway;
use AWPCP_PaymentsAPI;
use AWPCP_Payment_Transaction;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * payment gateway integration for Another WordPress Classifieds Plugin since v3.0
 * @link http://awpcp.com/
 */
final class MethodAWPCP extends AWPCP_PaymentGateway {

	private $logger;

	const PAYMENT_METHOD = 'eway';

	/**
	 * set up hooks for the integration
	 */
	public static function register_eway() : void {
		add_filter('awpcp-register-payment-methods', [__CLASS__, 'awpcpRegisterPaymentMethods'], 20);
		add_action('awpcp_register_settings', [__CLASS__, 'awpcpRegisterSettings']);
		add_action('admin_print_styles-classified-ads_page_awpcp-admin-settings', [__CLASS__, 'settingsStyles']);
		add_action('admin_print_footer_scripts-classified-ads_page_awpcp-admin-settings', [__CLASS__, 'settingsScripts']);
	}

	/**
	 * initialise payment gateway
	 */
	public function __construct() {
		$this->logger		= new Logging('awpcp', get_awpcp_option('eway_logging', 'off'));

		$icon = get_awpcp_option('eway_icon');
		if (empty($icon)) {
			$icon = plugins_url('static/images/eway-small.svg', EWAY_PAYMENTS_PLUGIN_FILE);
		}

		parent::__construct(
			/* slug */			self::PAYMENT_METHOD,
			/* name */			esc_html_x('Eway Payment Gateway', 'AWPCP payment method name', 'eway-payment-gateway'),
			/* description */	esc_html_x('Credit card payment via Eway', 'AWPCP payment method description', 'eway-payment-gateway'),
			/* icon */			apply_filters('awpcp_eway_icon', $icon)
		);

		add_action('admin_head-classified-ads_page_awpcp-admin-settings', [$this, 'maybeNotifyCreds']);
	}

	/**
	 * maybe notify admins that gateway is missing credentials
	 */
	public function maybeNotifyCreds() : void {
		if (self::isSettingsPage() && $this->getApiCredentials()->isMissingCredentials()) {
			add_action('admin_notices', [$this, 'adminNotifyCreds']);
		}
	}

	/**
	 * notify admins that gateway is missing credentials
	 */
	public function adminNotifyCreds() : void {
		require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-notice-missing-creds.php';
	}

	/**
	 * customise styles for settings page
	 */
	public static function settingsStyles() : void {
		if (!self::isSettingsPage()) {
			return;
		}
		echo '<style>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . 'static/css/admin-awpcp-settings.css');
		echo '</style>';
	}

	/**
	 * load scripts for settings page
	 */
	public static function settingsScripts() : void {
		if (!self::isSettingsPage()) {
			return;
		}
		$min = SCRIPT_DEBUG ? '' : '.min';
		echo '<script>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . "static/js/admin-awpcp-settings$min.js");
		echo '</script>';
	}

	/**
	 * register new payment gateway with front end (NB: admin side never calls this!)
	 * @param AWPCP_PaymentsAPI $payments
	 */
	public static function awpcpRegisterPaymentMethods(AWPCP_PaymentsAPI $payments) : void {
		if (get_awpcp_option('activateeway')) {
			$payments->register_payment_method(new self());
		}
	}

	/**
	 * register settings for this payment method
	 * @param AWPCP_SettingsManager $settings
	 */
	public static function awpcpRegisterSettings($settings) : void {
		if (!method_exists($settings, 'add_settings_subgroup')) {
			return;
		}

		// create a new section
		$subgroup = 'eway-settings';
		$section = 'eway';

		$settings->add_settings_subgroup([
			'id'       => $subgroup,
			'name'     => esc_html_x('Eway Settings', 'settings field', 'eway-payment-gateway'),
			'priority' => 100,
			'parent'   => 'payment-settings',
		]);

		$settings->add_settings_section([
			'id'       => 'eway',
			'name'     => esc_html_x('Eway Settings', 'settings field', 'eway-payment-gateway'),
			'priority' => 10,
			'subgroup' => $subgroup,
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'activateeway',
			'name'			=> esc_html_x('Activate Eway?', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'checkbox',
			'default'		=> 1,
			'description'	=> esc_html_x('Activate Eway?', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_api_key',
			'name'			=> esc_html_x('API key', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textfield',
			'default'		=> '',
			'description'	=> esc_html_x('Rapid API key from your live Eway account', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_password',
			'name'			=> esc_html_x('API password', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'password',
			'default'		=> '',
			'description'	=> esc_html_x('Rapid API key from your live Eway account', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_ecrypt_key',
			'name'			=> esc_html_x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textarea',
			'default'		=> '',
			'description'	=> esc_html_x('Client Side Encryption key from your live Eway account', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_sandbox_api_key',
			'name'			=> esc_html_x('Sandbox API key', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textfield',
			'default'		=> '',
			'description'	=> esc_html_x('Rapid API key from your sandbox account', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_sandbox_password',
			'name'			=> esc_html_x('Sandbox API password', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'password',
			'default'		=> '',
			'description'	=> esc_html_x('Rapid API password from your sandbox account', 'settings label', 'eway-payment-gateway'),
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_sandbox_ecrypt_key',
			'name'			=> esc_html_x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textarea',
			'default'		=> '',
			'description'	=> esc_html_x('Client Side Encryption key from your sandbox account', 'settings label', 'eway-payment-gateway'),
		]);

		$descripton = sprintf('%s<br />%s',
			esc_html__("Capture processes the payment immediately. Authorize holds the amount on the customer's card for processing later.", 'eway-payment-gateway'),
			esc_html__('Authorize can be useful when ads must be approved by an admin, allowing you to reject payments for rejected ads.', 'eway-payment-gateway'));
		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_stored',
			'name'			=> esc_html_x('Payment Method', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'select',
			'default'		=> '0',
			'description'	=> $descripton,
			'options'		=> [
				'0' 	=> esc_html_x('Capture', 'payment method', 'eway-payment-gateway'),
				'1'	 	=> esc_html_x('Authorize', 'payment method', 'eway-payment-gateway'),
			],
		]);

		$descripton = sprintf('%s<br />%s<br />%s',
			esc_html__('Enable logging to assist trouble shooting;', 'eway-payment-gateway'),
			esc_html__('the log file can be found in this folder:', 'eway-payment-gateway'),
			Logging::getLogFolderRelative());
		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_logging',
			'name'			=> esc_html_x('Logging', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'select',
			'default'		=> 'off',
			'description'	=> $descripton,
			'options'		=> [
				'off' 	=> esc_html_x('Off', 'logging settings', 'eway-payment-gateway'),
				'info'	=> esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'),
				'error' => esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'),
			],
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_card_message',
			'name'			=> esc_html_x('Credit card message', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textfield',
			'default'		=> '',
			'description'	=> esc_html_x('Message to show above credit card fields, e.g. "Visa and Mastercard only"', 'settings label', 'eway-payment-gateway'),
		]);

		$description = sprintf('<a href="https://www.eway.com.au/features/tools/tools-site-seal/" rel="noopener" target="_blank">%s</a>',
			esc_html__('Generate your site seal on the Eway website, and paste it here', 'eway-payment-gateway'));
		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_site_seal_code',
			'name'			=> esc_html_x('Eway Site Seal', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textarea',
			'default'		=> '',
			'description'	=> $description,
		]);

		$settings->add_setting([
			'section'		=> $section,
			'id'			=> 'eway_icon',
			'name'			=> esc_html_x('Payment Method Icon', 'settings field', 'eway-payment-gateway'),
			'type'			=> 'textfield',
			'default'		=> '',
			'description'	=> esc_html_x('URL to a custom icon to show for the payment method.', 'settings label', 'eway-payment-gateway'),
		]);
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

		$creds = $this->getApiCredentials();
		if ($creds->isMissingCredentials()) {
			return __('Eway payments is not configured for payments yet', 'eway-payment-gateway');
		}

		$item = $transaction->get_item(0); // no support for multiple items
		if (is_null($item)) {
			return __('There was an error processing your payment.', 'eway-payment-gateway');
		}

		$payments    = awpcp_payments_api();
		$checkoutURL = $payments->get_return_url($transaction);

		$checkout_message = eway_payment_gateway_external_link(
			__('Please enter your credit card details for secure payment via {{a}}Eway{{/a}}.', 'eway-payment-gateway'), 'https://www.eway.com.au/'
		);
		$checkout_message = apply_filters('awpcp_eway_checkout_message', $checkout_message, false, $transaction);

		$card_msg = esc_html(get_awpcp_option('eway_card_message'));

		$optMonths = get_month_options();
		$optYears  = get_year_options();

		// load template with passed values
		ob_start();
		eway_load_template('awcp-eway-fields.php', compact('checkoutURL', 'checkout_message', 'card_msg', 'optMonths', 'optYears'));
		$form = ob_get_clean();

		$min = SCRIPT_DEBUG ? ''     : '.min';
		$ver = get_cache_buster();

		if ($creds->hasCSEKey()) {
			add_action('wp_enqueue_scripts', [$this, 'ecryptEnqueue'], 20);	// can't enqueue yet, so wait until plugin has enqueued script
			add_action('wp_footer', [$this, 'ecryptScript']);
		}

		wp_enqueue_script('eway-awpcp-checkout-form', plugins_url("static/js/awpcp-checkout-form$min.js", EWAY_PAYMENTS_PLUGIN_FILE), ['jquery'], $ver, true);
		wp_localize_script('eway-awpcp-checkout-form', 'eway_awpcp_checkout', [
			'errors' => [
				'card_number'	=> __('card number cannot be empty', 'eway-payment-gateway'),
				'card_name'		=> __('card holder name cannot be empty', 'eway-payment-gateway'),
				'expiry_month'	=> __('credit card expiry is missing', 'eway-payment-gateway'),
				'cvn'			=> __('cvn is missing', 'eway-payment-gateway'),
			],
		]);

		return $form;
	}

	/**
	 * enqueue the Eway ecrypt script for client-side encryption
	 */
	public function ecryptEnqueue() : void {
		wp_enqueue_script('eway-payment-gateway-ecrypt');
	}

	/**
	 * configure the scripts for client-side encryption
	 */
	public function ecryptScript() : void {
		$creds	= $this->getApiCredentials();

		$vars = [
			'mode'		=> 'awpcp',
			'key'		=> $creds->ecrypt_key,
			'form'		=> '#awpcp-eway-checkout',
			'fields'	=> [
				'#eway_card_number'		=> ['name' => 'cse:eway_card_number', 'is_cardnum' => true],
				'#eway_cvn'				=> ['name' => 'cse:eway_cvn', 'is_cardnum' => false],
			],
		];

		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_vars', $vars);
	}

	/**
	 * process payment notification
	 * @param AWPCP_Payment_Transaction $transaction
	 */
	public function process_payment_notification($transaction) {
	}

	/**
	 * process completed transaction
	 * @param AWPCP_Payment_Transaction $transaction
	 */
	public function process_payment_completed($transaction) {
		$postdata		= new FormPost();

		$fields			= [
			'card_number'	=> $postdata->getValue('eway_card_number'),
			'card_name'		=> $postdata->getValue('eway_card_name'),
			'expiry_month'	=> $postdata->getValue('eway_expiry_month'),
			'expiry_year'	=> $postdata->getValue('eway_expiry_year'),
			'cvn'			=> $postdata->getValue('eway_cvn'),
		];

		$errors			= $postdata->verifyCardDetails($fields);
		$success		= (count($errors) === 0);

		$transaction->errors['verification-post'] = $errors;
		$transaction->errors['validation'] = [];

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
	 * process transaction against Eway
	 * @throws EwayPaymentsException
	 */
	private function processTransaction(AWPCP_Payment_Transaction $transaction) : EwayResponse {
		$item		= $transaction->get_item(0); // no support for multiple items
		$ad			= self::getAdByID($transaction->get('ad-id'));
		$user		= wp_get_current_user();

		$capture	= !get_awpcp_option('eway_stored');
		$useSandbox	= (bool) get_awpcp_option('paylivetestmode');
		$creds		= apply_filters('awpcp_eway_credentials', $this->getApiCredentials(), $useSandbox, $transaction);

		if ($creds->isMissingCredentials()) {
			$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
			throw new EwayPaymentsException(__('Eway payments is not configured for payments yet', 'eway-payment-gateway'));
		}

		$eway		= new EwayRapidAPI($creds->api_key, $creds->password, $useSandbox);
		$eway->capture = $capture;

		$postdata = new FormPost();

		$customer = new CustomerDetails;

		$customer->CardDetails = new CardDetails(
			$postdata->getValue('eway_card_name'),
			$postdata->cleanCardnumber($postdata->getValue('eway_card_number')),
			$postdata->getValue('eway_expiry_month'),
			$postdata->getValue('eway_expiry_year'),
			$postdata->getValue('eway_cvn'),
		);

		self::setTxContactDetails($customer, $ad, $user);

		// only populate payment record if there's an amount value
		$payment = new PaymentDetails;
		$totals = $transaction->get_totals();
		$amount = $totals['money'];
		$currency = awpcp_get_currency_code();
		if ($amount > 0) {
			$payment->setTotalAmount($amount, $currency);
			$payment->setCurrencyCode($currency);
			$payment->setInvoiceReference($transaction->id);
			$payment->setInvoiceDescription(apply_filters('awpcp_eway_invoice_desc', $item->name, $transaction));
			$payment->setInvoiceNumber(apply_filters('awpcp_eway_invoice_ref', $transaction->id, $transaction));
		}

		// allow plugins/themes to set option fields
		$options = get_api_options([
			apply_filters('awpcp_eway_option1', '', $transaction),
			apply_filters('awpcp_eway_option2', '', $transaction),
			apply_filters('awpcp_eway_option3', '', $transaction),
		]);

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live',
			$payment->InvoiceNumber, $payment->InvoiceReference, $payment->TotalAmount, $customer->CardDetails->Number));

		$response = $eway->processPayment($customer, null, $payment, $options);

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
	 * get API credentials based on settings
	 */
	private function getApiCredentials() : Credentials {
		if (!get_awpcp_option('paylivetestmode')) {
			$creds = new Credentials(
				get_awpcp_option('eway_api_key'),
				get_awpcp_option('eway_password'),
				get_awpcp_option('eway_ecrypt_key'),
			);
		}
		else {
			$creds = new Credentials(
				get_awpcp_option('eway_sandbox_api_key'),
				get_awpcp_option('eway_sandbox_password'),
				get_awpcp_option('eway_sandbox_ecrypt_key'),
			);
		}

		return $creds;
	}

	/**
	 * get contact name from available data
	 * @return array two elements: first name, last name
	 */
	private static function getContactNames(object $ad, WP_User $user, string $cardHoldersName) : array {
		$names = ['', ''];

		$ad_contact_name = awpcp_listing_renderer()->get_contact_name($ad);
		if ($ad_contact_name) {
			$names = self::splitCompoundName($ad_contact_name);
		}
		elseif ($user) {
			$names = [$user->first_name, $user->last_name];
		}

		// use cardholder name for customer name if no customer name available
		if (empty($names[0]) && empty($names[1])) {
			$names = self::splitCompoundName($cardHoldersName);
		}

		return $names;
	}

	/**
	 * attempt to split name into parts, and hope to not offend anyone!
	 * @return array two elements: first name, last name
	 */
	private static function splitCompoundName(string $compoundName) : array {
		$names = explode(' ', $compoundName);

		$firstName = empty($names[0]) ? '' : array_shift($names);		// remove first name from array
		$lastName = trim(implode(' ', $names));

		return [$firstName, $lastName];
	}

	/**
	 * attempt to get meaningful contact details from available data
	 */
	private static function setTxContactDetails(CustomerDetails $customer, object $ad, WP_User $user) : void {
		$profile = $user ? get_user_meta($user->ID, 'awpcp-profile', true) : false;

		list($first_name, $last_name) = self::getContactNames($ad, $user, $customer->CardDetails->Name);
		$customer->setFirstName($first_name);
		$customer->setLastName($last_name);

		$renderer = awpcp_listing_renderer();
		$ad_contact_email	= $renderer->get_contact_email($ad);
		$ad_region			= $renderer->get_first_region($ad);

		if ($ad_contact_email) {
			$customer->setEmail($ad_contact_email);
		}
		elseif ($user) {
			$customer->setEmail($user->user_email);
		}

		if (!empty($ad_region['city']) || !empty($ad_region['state']) || !empty($ad_region['country'])) {
			$customer->setCity($ad_region['city']);
			$customer->setState($ad_region['state']);
			$customer->setCountry(self::getCountryCode($ad_region['country']));
		}
		elseif (method_exists('AWPCP_Ad', 'get_ad_regions')) {
			$regions = AWPCP_Ad::get_ad_regions($ad->ad_id);
			if (!empty($regions[0])) {
				$customer->setCity($regions[0]['city']);
				$customer->setState($regions[0]['state']);
				$customer->setCountry(self::getCountryCode($regions[0]['country']));
			}
		}
		elseif ($profile) {
			if (isset($profile['address'])) {
				$customer->setStreet1($profile['address']);
			}
			if (isset($profile['city'])) {
				$customer->setCity($profile['city']);
			}
			if (isset($profile['state'])) {
				$customer->setState($profile['state']);
			}
		}
	}

	/**
	 * get country code from name
	 */
	private static function getCountryCode(string $country_name) : ?string {
		if (empty($country_name)) {
			return null;
		}

		if (strlen($country_name) === 2) {
			return $country_name;
		}

		$countries = [
			// ISO-3166-1 alpha-2 list of country names => codes, in lowercase
			'afghanistan'								=> 'af',
			'albania'									=> 'al',
			'algeria'									=> 'dz',
			'american samoa'							=> 'as',
			'andorra'									=> 'ad',
			'angola'									=> 'ao',
			'anguilla'									=> 'ai',
			'antarctica'								=> 'aq',
			'antigua & barbuda'							=> 'ag',
			'argentina'									=> 'ar',
			'armenia'									=> 'am',
			'aruba'										=> 'aw',
			'australia'									=> 'au',
			'austria'									=> 'at',
			'azerbaijan'								=> 'az',
			'bahamas'									=> 'bs',
			'bahrain'									=> 'bh',
			'bangladesh'								=> 'bd',
			'barbados'									=> 'bb',
			'belarus'									=> 'by',
			'belgium'									=> 'be',
			'belize'									=> 'bz',
			'benin'										=> 'bj',
			'bermuda'									=> 'bm',
			'bhutan'									=> 'bt',
			'bolivia'									=> 'bo',
			'bosnia'									=> 'ba',
			'botswana'									=> 'bw',
			'bouvet island'								=> 'bv',
			'brazil'									=> 'br',
			'british indian ocean territory'			=> 'io',
			'british virgin islands'					=> 'vg',
			'brunei'									=> 'bn',
			'bulgaria'									=> 'bg',
			'burkina faso'								=> 'bf',
			'burundi'									=> 'bi',
			'cambodia'									=> 'kh',
			'cameroon'									=> 'cm',
			'canada'									=> 'ca',
			'cape verde'								=> 'cv',
			'caribbean netherlands'						=> 'bq',
			'cayman islands'							=> 'ky',
			'central african republic'					=> 'cf',
			'chad'										=> 'td',
			'chile'										=> 'cl',
			'china'										=> 'cn',
			'christmas island'							=> 'cx',
			'cocos (keeling) islands'					=> 'cc',
			'colombia'									=> 'co',
			'comoros'									=> 'km',
			'congo - brazzaville'						=> 'cg',
			'congo - kinshasa'							=> 'cd',
			'cook islands'								=> 'ck',
			'costa rica'								=> 'cr',
			'croatia'									=> 'hr',
			'cuba'										=> 'cu',
			'curaçao'									=> 'cw',
			'cyprus'									=> 'cy',
			'czech republic'							=> 'cz',
			'côte d’ivoire'								=> 'ci',
			'denmark'									=> 'dk',
			'djibouti'									=> 'dj',
			'dominica'									=> 'dm',
			'dominican republic'						=> 'do',
			'ecuador'									=> 'ec',
			'egypt'										=> 'eg',
			'el salvador'								=> 'sv',
			'equatorial guinea'							=> 'gq',
			'eritrea'									=> 'er',
			'estonia'									=> 'ee',
			'ethiopia'									=> 'et',
			'falkland islands'							=> 'fk',
			'faroe islands'								=> 'fo',
			'fiji'										=> 'fj',
			'finland'									=> 'fi',
			'france'									=> 'fr',
			'french guiana'								=> 'gf',
			'french polynesia'							=> 'pf',
			'french southern territories'				=> 'tf',
			'gabon'										=> 'ga',
			'gambia'									=> 'gm',
			'georgia'									=> 'ge',
			'germany'									=> 'de',
			'ghana'										=> 'gh',
			'gibraltar'									=> 'gi',
			'greece'									=> 'gr',
			'greenland'									=> 'gl',
			'grenada'									=> 'gd',
			'guadeloupe'								=> 'gp',
			'guam'										=> 'gu',
			'guatemala'									=> 'gt',
			'guernsey'									=> 'gg',
			'guinea'									=> 'gn',
			'guinea-bissau'								=> 'gw',
			'guyana'									=> 'gy',
			'haiti'										=> 'ht',
			'heard & mcdonald islands'					=> 'hm',
			'honduras'									=> 'hn',
			'hong kong'									=> 'hk',
			'hungary'									=> 'hu',
			'iceland'									=> 'is',
			'india'										=> 'in',
			'indonesia'									=> 'id',
			'iran'										=> 'ir',
			'iraq'										=> 'iq',
			'ireland'									=> 'ie',
			'isle of man'								=> 'im',
			'israel'									=> 'il',
			'italy'										=> 'it',
			'jamaica'									=> 'jm',
			'japan'										=> 'jp',
			'jersey'									=> 'je',
			'jordan'									=> 'jo',
			'kazakhstan'								=> 'kz',
			'kenya'										=> 'ke',
			'kiribati'									=> 'ki',
			'kuwait'									=> 'kw',
			'kyrgyzstan'								=> 'kg',
			'laos'										=> 'la',
			'latvia'									=> 'lv',
			'lebanon'									=> 'lb',
			'lesotho'									=> 'ls',
			'liberia'									=> 'lr',
			'libya'										=> 'ly',
			'liechtenstein'								=> 'li',
			'lithuania'									=> 'lt',
			'luxembourg'								=> 'lu',
			'macau'										=> 'mo',
			'macedonia'									=> 'mk',
			'madagascar'								=> 'mg',
			'malawi'									=> 'mw',
			'malaysia'									=> 'my',
			'maldives'									=> 'mv',
			'mali'										=> 'ml',
			'malta'										=> 'mt',
			'marshall islands'							=> 'mh',
			'martinique'								=> 'mq',
			'mauritania'								=> 'mr',
			'mauritius'									=> 'mu',
			'mayotte'									=> 'yt',
			'mexico'									=> 'mx',
			'micronesia'								=> 'fm',
			'moldova'									=> 'md',
			'monaco'									=> 'mc',
			'mongolia'									=> 'mn',
			'montenegro'								=> 'me',
			'montserrat'								=> 'ms',
			'morocco'									=> 'ma',
			'mozambique'								=> 'mz',
			'myanmar'									=> 'mm',
			'namibia'									=> 'na',
			'nauru'										=> 'nr',
			'nepal'										=> 'np',
			'netherlands'								=> 'nl',
			'new caledonia'								=> 'nc',
			'new zealand'								=> 'nz',
			'nicaragua'									=> 'ni',
			'niger'										=> 'ne',
			'nigeria'									=> 'ng',
			'niue'										=> 'nu',
			'norfolk island'							=> 'nf',
			'north korea'								=> 'kp',
			'northern mariana islands'					=> 'mp',
			'norway'									=> 'no',
			'oman'										=> 'om',
			'pakistan'									=> 'pk',
			'palau'										=> 'pw',
			'palestine'									=> 'ps',
			'panama'									=> 'pa',
			'papua new guinea'							=> 'pg',
			'paraguay'									=> 'py',
			'peru'										=> 'pe',
			'philippines'								=> 'ph',
			'pitcairn islands'							=> 'pn',
			'poland'									=> 'pl',
			'portugal'									=> 'pt',
			'puerto rico'								=> 'pr',
			'qatar'										=> 'qa',
			'romania'									=> 'ro',
			'russia'									=> 'ru',
			'rwanda'									=> 'rw',
			'réunion'									=> 're',
			'samoa'										=> 'ws',
			'san marino'								=> 'sm',
			'saudi arabia'								=> 'sa',
			'senegal'									=> 'sn',
			'serbia'									=> 'rs',
			'seychelles'								=> 'sc',
			'sierra leone'								=> 'sl',
			'singapore'									=> 'sg',
			'sint maarten'								=> 'sx',
			'slovakia'									=> 'sk',
			'slovenia'									=> 'si',
			'solomon islands'							=> 'sb',
			'somalia'									=> 'so',
			'south africa'								=> 'za',
			'south georgia & south sandwich islands'	=> 'gs',
			'south korea'								=> 'kr',
			'south sudan'								=> 'ss',
			'spain'										=> 'es',
			'sri lanka'									=> 'lk',
			'st. barthélemy'							=> 'bl',
			'st. helena'								=> 'sh',
			'st. kitts & nevis'							=> 'kn',
			'st. lucia'									=> 'lc',
			'st. martin'								=> 'mf',
			'st. pierre & miquelon'						=> 'pm',
			'st. vincent & grenadines'					=> 'vc',
			'sudan'										=> 'sd',
			'suriname'									=> 'sr',
			'svalbard & jan mayen'						=> 'sj',
			'swaziland'									=> 'sz',
			'sweden'									=> 'se',
			'switzerland'								=> 'ch',
			'syria'										=> 'sy',
			'são tomé & príncipe'						=> 'st',
			'taiwan'									=> 'tw',
			'tajikistan'								=> 'tj',
			'tanzania'									=> 'tz',
			'thailand'									=> 'th',
			'timor-leste'								=> 'tl',
			'togo'										=> 'tg',
			'tokelau'									=> 'tk',
			'tonga'										=> 'to',
			'trinidad & tobago'							=> 'tt',
			'tunisia'									=> 'tn',
			'turkey'									=> 'tr',
			'turkmenistan'								=> 'tm',
			'turks & caicos islands'					=> 'tc',
			'tuvalu'									=> 'tv',
			'u.s. outlying islands'						=> 'um',
			'u.s. virgin islands'						=> 'vi',
			'united kingdom'							=> 'gb',
			'united states'								=> 'us',
			'uganda'									=> 'ug',
			'ukraine'									=> 'ua',
			'united arab emirates'						=> 'ae',
			'uruguay'									=> 'uy',
			'uzbekistan'								=> 'uz',
			'vanuatu'									=> 'vu',
			'vatican city'								=> 'va',
			'venezuela'									=> 've',
			'vietnam'									=> 'vn',
			'wallis & futuna'							=> 'wf',
			'western sahara'							=> 'eh',
			'yemen'										=> 'ye',
			'zambia'									=> 'zm',
			'zimbabwe'									=> 'zw',
			'åland islands'								=> 'ax',

			// some abbreviated names and common names for frequently used Eway countries
			'aust'										=> 'au',
			'aust.'										=> 'au',
			'n.z.'										=> 'nz',
			'u.k.'										=> 'gb',
			'britain'									=> 'gb',
			'england'									=> 'gb',
			'scotland'									=> 'gb',
			'wales'										=> 'gb',
			'northern ireland'							=> 'gb',
			'uae'										=> 'ae',
			'usa'										=> 'us',
			'u.s.a.'									=> 'us',
			'united states of america'					=> 'us',
		];

		if (function_exists('mb_strtolower')) {
			$country_lower = mb_strtolower($country_name);
		}
		else {
			$country_lower = strtolower($country_name);
		}

		return isset($countries[$country_lower]) ? $countries[$country_lower] : null;
	}

	/**
	 * retreive an advertisement by ID
	 */
	private static function getAdByID(string $ad_id) : ?object {
		try {
			$ad = awpcp_listings_collection()->get($ad_id);
		}
		catch (AWPCP_Exception $e) {
			$ad = null;
		}

		return $ad;
	}

	/**
	 * check to see if we're on this payment method's settings page
	 */
	private static function isSettingsPage() : bool {
		$page	= $_GET['page'] ?? '';
		$sg		= $_GET['sg'] ?? '';
		return is_admin() && $page === 'awpcp-admin-settings' && $sg === 'eway-settings';
	}

}
