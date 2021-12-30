<?php
use webaware\eway_payment_gateway\Credentials;
use webaware\eway_payment_gateway\Logging;
use webaware\eway_payment_gateway\event_espresso\Gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Event Espresso payment method
 * @link https://github.com/eventespresso/event-espresso-core/blob/master/docs/L--Payment-Methods-and-Gateways/creating-a-payment-method.md
 */
final class EE_PMT_event_espresso_eway extends EE_PMT_Base {

	/**
	 * @param EE_Payment_Method $pm_instance
	 */
	public function __construct($pm_instance = null) {
		require_once __DIR__ . '/class.Gateway.php';

		$this->_gateway				= new Gateway();
		$this->_pretty_name			= _x('Eway', 'Event Espresso method name', 'eway-payment-gateway');
		$this->_requires_https		= true;
		$this->_has_billing_form	= true;

		parent::__construct($pm_instance);

		add_action('AHEE__EE_Form_Section_Proper___construct_finalize__end', [$this, 'maybeNotifyCreds'], 10, 3);
		add_action('AHEE__EED_Single_Page_Checkout__enqueue_styles_and_scripts', [$this, 'maybeEnqueueCSE']);
		add_action('admin_print_scripts', [$this, 'adminEnqueueScripts']);
		add_filter('FHEE__EEM_Payment_Method__get_all_for_transaction__payment_methods', [$this, 'maybeDisableMethod']);
	}

	/**
	 * Adds the help tab
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config() {
		return [
			// TODO: add help tab content
		];
	}

	/**
	 * @param EE_Transaction $transaction
	 * @return EE_Billing_Attendee_Info_Form
	 */
	public function generate_new_billing_form(EE_Transaction $transaction = null) {
		$creds = $this->getApiCredentials();

		if (empty($creds) || $creds->isMissingCredentials()) {
			$subsections = [];
		}
		else {
			$subsections = [

				'card_number'	=>	new EE_Text_Input([
					'required'        => true,
					'html_label_text' => __('Credit Card Number', 'eway-payment-gateway'),
				]),

				'card_name'		=>	new EE_Text_Input([
					'required'        => true,
					'html_label_text' => __("Card Holder's Name", 'eway-payment-gateway'),
				]),

				'expiry_month'	=>	new EE_Credit_Card_Month_Input(true, [
					'required'			=> true,
					'html_label_text'	=> __('Card Expiry Month', 'eway-payment-gateway'),
				]),

				'expiry_year'	=>	new EE_Credit_Card_Year_Input([
					'required'			=> true,
					'html_label_text'	=> __('Card Expiry Year', 'eway-payment-gateway'),
				]),

				'cvn'			=>	new EE_Text_Input([
					'required'        => true,
					'html_label_text' => __('CVN/CVV', 'eway-payment-gateway'),
				]),

			];
		}

		$form = new EE_Billing_Attendee_Info_Form($this->_pm_instance, [

			'name'        => 'event_espresso_eway_form',
			'subsections' => $subsections,

		]);

		return $form;
	}

	/**
	 * Gets the form for all the settings related to this payment method type
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {
		// NB: class names have a trailing space because EE appends additional classes without a space!

		$form = new EE_Payment_Method_Form([

			'extra_meta_inputs'	=> [

				'eway_api_key'				=>	new EE_Text_Input([
													'html_label_text'	=> _x('API key', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_password'				=>	new EE_Password_Input([
													'html_label_text'	=> _x('API password', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_ecrypt_key'			=>	new EE_Text_Area_Input([
													'html_label_text'	=> _x('Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_sandbox_api_key'		=>	new EE_Text_Input([
													'html_label_text'	=> _x('Sandbox API key', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_sandbox_password'		=>	new EE_Password_Input([
													'html_label_text'	=> _x('Sandbox API password', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_sandbox_ecrypt_key'	=>	new EE_Text_Area_Input([
													'html_label_text'	=> _x('Sandbox Client Side Encryption key', 'settings field', 'eway-payment-gateway'),
													'html_class'		=> 'eway-no-autocorrupt ',
												]),

				'eway_logging'				=>	new EE_Select_Input([
													'off' 		=> esc_html_x('Off', 'logging settings', 'eway-payment-gateway'),
													'info'	 	=> esc_html_x('All messages', 'logging settings', 'eway-payment-gateway'),
													'error' 	=> esc_html_x('Errors only', 'logging settings', 'eway-payment-gateway'),
												],
												[
													'html_label_text'	=> _x('Logging', 'settings field', 'eway-payment-gateway'),
													'html_help_text'	=>	sprintf('%s<br/>%s<br/>%s',
																				esc_html__('Enable logging to assist trouble shooting', 'eway-payment-gateway'),
																				esc_html__('the log file can be found in this folder:', 'eway-payment-gateway'),
																				esc_html(Logging::getLogFolderRelative())),
												]),

			],

		]);

		return $form;
	}

	/**
	 * enqueue admin scripts for settings fields
	 */
	public function adminEnqueueScripts() : void {
		global $plugin_page;

		if ($plugin_page === 'espresso_payment_settings') {
			$min = SCRIPT_DEBUG ? '' : '.min';
			$ver = SCRIPT_DEBUG ? time() : EWAY_PAYMENTS_VERSION;
			wp_enqueue_script('event_espresso_eway-settings', plugins_url("static/js/admin-event-espresso$min.js", EWAY_PAYMENTS_PLUGIN_FILE), [], $ver, true);
		}
	}

	/**
	 * maybe notify admin that some credentials are missing
	 * NB: payment method instance is not available yet, so need to hit the database for answers
	 */
	public function maybeNotifyCreds($form, $parent_section, $name) : void {
		static $has_checked_creds = false;

		if ($has_checked_creds || $name !== 'event_espresso_eway_settings_form') {
			return;
		}

		global $wpdb;

		$sql		= "
			select PMD_debug_mode
			from {$wpdb->prefix}esp_payment_method
			where PMD_slug = 'event_espresso_eway'
		";
		$sandbox	= $wpdb->get_var($sql) ? '_sandbox' : '';
		$api_key	= "eway{$sandbox}_api_key";
		$password	= "eway{$sandbox}_password";

		$sql		= "
			select count(EXM_ID)
			from {$wpdb->prefix}esp_extra_meta
			where EXM_type = 'Payment_Method'
			and EXM_key in (%s,%s)
			and EXM_value > ''
		";
		$count = $wpdb->get_var($wpdb->prepare($sql, [$api_key, $password]));

		if ($count < 2) {
			require EWAY_PAYMENTS_PLUGIN_ROOT . 'views/admin-notice-missing-creds.php';
		}

		$has_checked_creds = true;
	}

	/**
	 * maybe enqueue the Client Side Encryption scripts for encrypting credit card details
	 */
	public function maybeEnqueueCSE() : void {
		$creds = $this->getApiCredentials();
		if ($creds && $creds->hasCSEKey() && ! $creds->isMissingCredentials()) {
			wp_enqueue_script('eway-payment-gateway-ecrypt');
			add_action('wp_footer', [$this, 'ecryptScript']);
		}
	}

	/**
	 * configure the scripts for client-side encryption
	 */
	public function ecryptScript() : void {
		$creds = $this->getApiCredentials();

		$vars = [
			'mode'		=> 'event-espresso',
			'key'		=> $creds->ecrypt_key,
			'form'		=> '#ee-spco-payment_options-reg-step-form',
			'fields'	=> [
							'#event-espresso-eway-form-card-number'	=> ['name' => 'cse:card_number', 'is_cardnum' => true, 'false_fill' => true],
							'#event-espresso-eway-form-cvn'			=> ['name' => 'cse:cvn', 'is_cardnum' => false, 'false_fill' => true],
						],
		];

		wp_localize_script('eway-payment-gateway-ecrypt', 'eway_ecrypt_vars', $vars);
	}

	/**
	 * maybe disable this payment method if it is missing some credentials
	 */
	public function maybeDisableMethod(array $methods) : array {
		$creds = $this->getApiCredentials();
		if ($creds && $creds->isMissingCredentials()) {
			$methods = array_filter($methods, function($method) {
				return $method->slug() !== 'event_espresso_eway';
			});
		}
		return $methods;
	}

	/**
	 * get API credentials based on settings
	 */
	private function getApiCredentials() : ?Credentials {
		static $creds = false;

		if ($creds === false) {
			$pm = $this->_pm_instance;

			if (empty($pm)) {
				$creds = null;
			}
			elseif (!$pm->debug_mode()) {
				$creds = new Credentials(
					$pm->get_extra_meta('eway_api_key', true),
					$pm->get_extra_meta('eway_password', true),
					$pm->get_extra_meta('eway_ecrypt_key', true),
				);
			}
			else {
				$creds = new Credentials(
					$pm->get_extra_meta('eway_sandbox_api_key', true),
					$pm->get_extra_meta('eway_sandbox_password', true),
					$pm->get_extra_meta('eway_sandbox_ecrypt_key', true),
				);
			}
		}

		return $creds;
	}

}
