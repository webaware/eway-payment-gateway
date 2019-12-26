<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
* payment gateway integration for Another WordPress Classifieds Plugin since v3.0
* @link http://awpcp.com/
*/
class MethodAWPCP extends \AWPCP_PaymentGateway {

	protected $logger;

	const PAYMENT_METHOD = 'eway';

	/**
	* set up hooks for the integration
	*/
	public static function register_eway() {
		add_filter('awpcp-register-payment-methods', [__CLASS__, 'awpcpRegisterPaymentMethods'], 20);
		add_action('awpcp_register_settings', [__CLASS__, 'awpcpRegisterSettings']);
		add_action('admin_print_styles-classified-admin_page_awpcp-admin-settings', [__CLASS__, 'settingsStyles']);

		// pre AWPCP 4.0
		add_action('admin_print_styles-classifieds_page_awpcp-admin-settings', [__CLASS__, 'settingsStyles']);
	}

	/**
	* initialise payment gateway
	*/
	public function __construct() {
		$this->logger		= new Logging('awpcp', get_awpcp_option('eway_logging', 'off'));

		$icon = get_awpcp_option('eway_icon');
		if (empty($icon)) {
			$icon = plugins_url('images/eway-siteseal.png', EWAY_PAYMENTS_PLUGIN_FILE);
		}

		parent::__construct(
			/* slug */			self::PAYMENT_METHOD,
			/* name */			esc_html_x('eWAY Payment Gateway', 'AWPCP payment method name', 'eway-payment-gateway'),
			/* description */	esc_html_x('Credit card payment via eWAY', 'AWPCP payment method description', 'eway-payment-gateway'),
			/* icon */			apply_filters('awpcp_eway_icon', $icon)
		);
	}

	/**
	* customise styles for settings page
	*/
	public static function settingsStyles() {
		echo '<style>';
		readfile(EWAY_PAYMENTS_PLUGIN_ROOT . 'css/admin-awpcp-settings.css');
		echo '</style>';
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
	* @param AWPCP_SettingsManager $settings
	*/
	public static function awpcpRegisterSettings($settings) {
		// create a new section
		if (method_exists($settings, 'add_settings_subgroup')) {
			// since AWPC 4.0.0
			$subgroup = 'eway-settings';
			$section = 'eway';

			$settings->add_settings_subgroup([
				'id'       => $subgroup,
				'name'     => esc_html_x('eWAY Settings', 'settings field', 'eway-payment-gateway'),
				'priority' => 100,
				'parent'   => 'payment-settings',
			]);

			$settings->add_settings_section([
				'id'       => 'eway',
				'name'     => esc_html_x('eWAY Settings', 'settings field', 'eway-payment-gateway'),
				'priority' => 10,
				'subgroup' => $subgroup,
			]);
		}
		else {
			// pre AWPCP 4.0.0
			$section = $settings->add_section(
				'payment-settings',
				esc_html_x('eWAY Settings', 'settings field', 'eway-payment-gateway'),
				'eway',
				100,
				[$settings, 'section']
			);
		}

		$settings->add_setting($section, 'activateeway',
						esc_html_x('Activate eWAY?', 'settings field', 'eway-payment-gateway'),
						'checkbox', 1,
						esc_html_x('Activate eWAY?', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_api_key',
						esc_html_x('API key', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('Rapid API key from your live eWAY account', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_password',
						esc_html_x('API password', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('Rapid API password from your live eWAY account', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_ecrypt_key',
						esc_html_x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
						'textarea', '',
						esc_html_x('Client Side Encryption key from your live eWAY account', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_customerid',
						esc_html_x('eWAY customer ID', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html__('Legacy connections only; please add your API key/password and Client Side Encryption key instead.', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_sandbox_api_key',
						esc_html_x('Sandbox API key', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('Rapid API key from your sandbox account', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_sandbox_password',
						esc_html_x('Sandbox API password', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('Rapid API password from your sandbox account', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_sandbox_ecrypt_key',
						esc_html_x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
						'textarea', '',
						esc_html_x('Client Side Encryption key from your sandbox account', 'settings label', 'eway-payment-gateway'));

		$methods = [
			'0' 		=> esc_html_x('Capture', 'payment method', 'eway-payment-gateway'),
			'1'		 	=> esc_html_x('Authorize', 'payment method', 'eway-payment-gateway'),
		];
		$settings->add_setting($section, 'eway_stored',
						esc_html_x('Payment Method', 'settings field', 'eway-payment-gateway'),
						'select', 0,
						esc_html__("Capture processes the payment immediately. Authorize holds the amount on the customer's card for processing later.", 'eway-payment-gateway')
						. '<br/>'
						. esc_html__('Authorize can be useful when ads must be approved by an admin, allowing you to reject payments for rejected ads.', 'eway-payment-gateway'),
						['options' => $methods]);

		$log_options = [
			'off' 		=> esc_html_x('Off', 'logging settings', 'eway-payment-gateway'),
			'info'	 	=> esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'),
			'error' 	=> esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'),
		];
		$log_descripton = sprintf('%s<br />%s<br />%s',
							esc_html__('Enable logging to assist trouble shooting', 'eway-payment-gateway'),
							esc_html__('the log file can be found in this folder:', 'eway-payment-gateway'),
							Logging::getLogFolderRelative());
		$settings->add_setting($section, 'eway_logging',
						esc_html_x('Logging', 'settings field', 'eway-payment-gateway'),
						'select', 'off', $log_descripton, ['options' => $log_options]);

		$settings->add_setting($section, 'eway_card_message',
						esc_html_x('Credit card message', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('Message to show above credit card fields, e.g. "Visa and Mastercard only"', 'settings label', 'eway-payment-gateway'));

		$settings->add_setting($section, 'eway_site_seal_code',
						esc_html_x('eWAY Site Seal', 'settings field', 'eway-payment-gateway'),
						'textarea', '',
						sprintf('<a href="https://www.eway.com.au/features/tools-site-seal" rel="noopener" target="_blank">%s</a>',
							esc_html__('Generate your site seal on the eWAY website, and paste it here', 'eway-payment-gateway')));

		$settings->add_setting($section, 'eway_icon',
						esc_html_x('Payment Method Icon', 'settings field', 'eway-payment-gateway'),
						'textfield', '',
						esc_html_x('URL to a custom icon to show for the payment method.', 'settings label', 'eway-payment-gateway'));
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

		$checkout_message = eway_payment_gateway_external_link(
			__('Please enter your credit card details for secure payment via {{a}}eWAY{{/a}}.', 'eway-payment-gateway'), 'https://www.eway.com.au/'
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
		$ver = SCRIPT_DEBUG ? time() : EWAY_PAYMENTS_VERSION;

		$creds = $this->getApiCredentials();
		if (!empty($creds['ecrypt_key'])) {
			add_action('wp_enqueue_scripts', [$this, 'ecryptEnqueue'], 20);	// can't enqueue yet, so wait until plugin has enqueued script
			add_action('wp_footer', [$this, 'ecryptScript']);
		}

		wp_enqueue_script('eway-awpcp-checkout-form', plugins_url("js/awpcp-checkout-form$min.js", EWAY_PAYMENTS_PLUGIN_FILE), ['jquery'], $ver, true);
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
	* enqueue the eWAY ecrypt script for client-side encryption
	*/
	public function ecryptEnqueue() {
		wp_enqueue_script('eway-payment-gateway-ecrypt');
	}

	/**
	* configure the scripts for client-side encryption
	*/
	public function ecryptScript() {
		$creds	= $this->getApiCredentials();

		$vars = [
			'mode'		=> 'awpcp',
			'key'		=> $creds['ecrypt_key'],
			'form'		=> '#awpcp-eway-checkout',
			'fields'	=> [
							'#eway_card_number'			=> ['name' => 'cse:eway_card_number', 'is_cardnum' => true],
							'#eway_cvn'					=> ['name' => 'cse:eway_cvn', 'is_cardnum' => false],
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
						$transaction->payment_status = \AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;
					}
					else {
					*/
						$transaction->payment_status = \AWPCP_Payment_Transaction::PAYMENT_STATUS_COMPLETED;
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
					$transaction->payment_status = \AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
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
				$transaction->payment_status = \AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
				$transaction->errors['validation'] = nl2br(esc_html($e->getMessage()) . "\n" . __("use your browser's back button to try again.", 'eway-payment-gateway'));
				$success = false;

				$this->logger->log('error', $e->getMessage());
			}
		}

		$transaction->set('verified', $success);
	}

	/**
	* process transaction against eWAY
	* @param AWPCP_Payment_Transaction $transaction
	* @return $response
	* @throws EwayPaymentsException
	*/
	protected function processTransaction($transaction) {
		$item		= $transaction->get_item(0); // no support for multiple items
		$ad			= self::getAdByID($transaction->get('ad-id'));
		$user		= wp_get_current_user();

		$capture	= !get_awpcp_option('eway_stored');
		$useSandbox	= (bool) get_awpcp_option('paylivetestmode');
		$creds		= apply_filters('awpcp_eway_credentials', $this->getApiCredentials(), $useSandbox, $transaction);
		$eway		= get_api_wrapper($creds, $capture, $useSandbox);

		if (!$eway) {
			$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
			throw new EwayPaymentsException(__('eWAY payments is not configured for payments yet', 'eway-payment-gateway'));
		}

		$postdata = new FormPost();

		$eway->invoiceDescription			= $item->name;
		$eway->invoiceReference				= $transaction->id;									// customer invoice reference
		$eway->transactionNumber			= $transaction->id;									// transaction reference
		$eway->currencyCode					= awpcp_get_currency_code();
		$eway->cardHoldersName				= $postdata->getValue('eway_card_name');
		$eway->cardNumber					= $postdata->cleanCardnumber($postdata->getValue('eway_card_number'));
		$eway->cardExpiryMonth				= $postdata->getValue('eway_expiry_month');
		$eway->cardExpiryYear				= $postdata->getValue('eway_expiry_year');
		$eway->cardVerificationNumber		= $postdata->getValue('eway_cvn');

		list($eway->firstName, $eway->lastName) = self::getContactNames($ad, $user, $eway->cardHoldersName);

		self::setTxContactDetails($eway, $ad, $user);

		// allow plugins/themes to modify invoice description and reference, and set option fields
		$eway->invoiceDescription			= apply_filters('awpcp_eway_invoice_desc', $eway->invoiceDescription, $transaction);
		$eway->invoiceReference				= apply_filters('awpcp_eway_invoice_ref', $eway->invoiceReference, $transaction);
		$eway->options						= array_filter([
													apply_filters('awpcp_eway_option1', '', $transaction),
													apply_filters('awpcp_eway_option2', '', $transaction),
													apply_filters('awpcp_eway_option3', '', $transaction),
											  ], 'strlen');

		$totals = $transaction->get_totals();
		$eway->amount = $totals['money'];

		$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
			$useSandbox ? 'test' : 'live', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

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
	* get API credentials based on settings
	* @return array
	*/
	protected function getApiCredentials() {
		$useSandbox	= (bool) get_awpcp_option('paylivetestmode');

		if (!$useSandbox) {
			$creds = [
				'api_key'		=> get_awpcp_option('eway_api_key'),
				'password'		=> get_awpcp_option('eway_password'),
				'ecrypt_key'	=> get_awpcp_option('eway_ecrypt_key'),
				'customerid'	=> get_awpcp_option('eway_customerid'),
			];
		}
		else {
			$creds = [
				'api_key'		=> get_awpcp_option('eway_sandbox_api_key'),
				'password'		=> get_awpcp_option('eway_sandbox_password'),
				'ecrypt_key'	=> get_awpcp_option('eway_sandbox_ecrypt_key'),
				'customerid'	=> EWAY_PAYMENTS_TEST_CUSTOMER,
			];
		}

		return $creds;
	}

	/**
	* get contact name from available data
	* @param AWPCP_Ad $ad
	* @param WP_User $user
	* @param string $cardHoldersName
	* @return array two elements: first name, last name
	*/
	protected static function getContactNames($ad, $user, $cardHoldersName) {
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
	* @param string $compoundName
	* @return array two elements: first name, last name
	*/
	protected static function splitCompoundName($compoundName) {
		$names = explode(' ', $compoundName);

		$firstName = empty($names[0]) ? '' : array_shift($names);		// remove first name from array
		$lastName = trim(implode(' ', $names));

		return [$firstName, $lastName];
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

		$renderer = awpcp_listing_renderer();
		$ad_contact_email	= $renderer->get_contact_email($ad);
		$ad_region			= $renderer->get_first_region($ad);

		if ($ad_contact_email) {
			$eway->emailAddress		= $ad_contact_email;
		}
		elseif ($user) {
			$eway->emailAddress		= $user->user_email;
		}

		if (!empty($ad_region['city']) || !empty($ad_region['state']) || !empty($ad_region['country'])) {
			$eway->suburb			= $ad_region['city'];
			$eway->state			= $ad_region['state'];
			$eway->countryName		= $ad_region['country'];
		}
		elseif (method_exists('AWPCP_Ad', 'get_ad_regions')) {
			$regions = \AWPCP_Ad::get_ad_regions($ad->ad_id);
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

		// attempt to set country code
		if (strlen($eway->countryName) === 2) {
			$eway->country = $eway->countryName;
		}
		elseif ($eway->countryName) {
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

				// some abbreviated names and common names for frequently used eWAY countries
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
				$countryLower = mb_strtolower($eway->countryName);
			}
			else {
				$countryLower = strtolower($eway->countryName);
			}

			if (isset($countries[$countryLower])) {
				$eway->country = $countries[$countryLower];
			}
		}

	}

	/**
	* retreive an advertisement by ID
	* @param string $ad_id
	* @return object
	*/
	protected static function getAdByID($ad_id) {
		try {
			$ad = awpcp_listings_collection()->get($ad_id);
		} catch (\AWPCP_Exception $e) {
			$ad = null;
		}

		return $ad;
	}

}
