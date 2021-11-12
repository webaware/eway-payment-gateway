<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an Eway Rapid API payment
* @link https://eway.io/api-v3/
*/
class EwayRapidAPI {

	#region "constants"

	// API hosts
	const API_HOST_LIVE						= 'https://api.ewaypayments.com';
	const API_HOST_SANDBOX					= 'https://api.sandbox.ewaypayments.com';

	// API endpoints for REST/JSON
	const API_DIRECT_PAYMENT				= 'Transaction';

	// valid actions
	const METHOD_PAYMENT					= 'ProcessPayment';
	const METHOD_AUTHORISE					= 'Authorise';

	// valid transaction types
	const TRANS_PURCHASE					= 'Purchase';
	const TRANS_RECURRING					= 'Recurring';
	const TRANS_MOTO						= 'MOTO';

	// valid shipping methods
	const SHIP_METHOD_UNKNOWN				= 'Unknown';
	const SHIP_METHOD_LOWCOST				= 'LowCost';
	const SHIP_METHOD_CUSTOMER				= 'DesignatedByCustomer';
	const SHIP_METHOD_INTERNATIONAL			= 'International';
	const SHIP_METHOD_MILITARY				= 'Military';
	const SHIP_METHOD_NEXTDAY				= 'NextDay';
	const SHIP_METHOD_PICKUP				= 'StorePickup';
	const SHIP_METHOD_2DAY					= 'TwoDayService';
	const SHIP_METHOD_3DAY					= 'ThreeDayService';
	const SHIP_METHOD_OTHER					= 'Other';

	const PARTNER_ID						= '4577fd8eb9014c7188d7be672c0e0d88';

	#endregion // constants

	#region "members"

	#region "connection specific members"

	/**
	* use Eway sandbox
	* @var boolean
	*/
	public $useSandbox;

	/**
	* capture payment (alternative is just authorise, no capture)
	* @var boolean
	*/
	public $capture;

	/**
	* default TRUE, whether to validate the remote SSL certificate
	* @var boolean
	*/
	public $sslVerifyPeer;

	/**
	* API key
	* @var string
	*/
	public $apiKey;

	/**
	* API password
	* @var string
	*/
	public $apiPassword;

	/**
	* ID of device or application processing the transaction
	* @var string max. 50 characters
	*/
	public $deviceID;

	/**
	* HTTP user agent string identifying plugin, perhaps for debugging
	* @var string
	*/
	public $httpUserAgent;

	#endregion // "connection specific members"

	#region "payment specific members"

	/**
	* action to perform: one of the METHOD_* values
	* @var string
	*/
	public $method;

	/**
	* a unique transaction number from your site (NB: see transactionNumber which is intended for invoice number or similar)
	* @var string max. 50 characters
	*/
	public $transactionNumber;

	/**
	* an invoice reference to track by
	* @var string max. 12 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 64 characters
	*/
	public $invoiceDescription;

	/**
	* total amount of payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amount;

	/**
	* ISO 4217 currency code
	* @var string 3 characters in uppercase
	*/
	public $currencyCode;

	// customer and billing details

	/**
	* customer's title
	* @var string max. 5 characters
	*/
	public $title;

	/**
	* customer's first name
	* @var string max. 50 characters
	*/
	public $firstName;

	/**
	* customer's last name
	* @var string max. 50 characters
	*/
	public $lastName;

	/**
	* customer's company name
	* @var string max. 50 characters
	*/
	public $companyName;

	/**
	* customer's job description (e.g. position)
	* @var string max. 50 characters
	*/
	public $jobDescription;

	/**
	* customer's address line 1
	* @var string max. 50 characters
	*/
	public $address1;

	/**
	* customer's address line 2
	* @var string max. 50 characters
	*/
	public $address2;

	/**
	* customer's suburb/city/town
	* @var string max. 50 characters
	*/
	public $suburb;

	/**
	* customer's state/province
	* @var string max. 50 characters
	*/
	public $state;

	/**
	* customer's postcode
	* @var string max. 30 characters
	*/
	public $postcode;

	/**
	* customer's country code
	* @var string 2 characters lowercase
	*/
	public $country;

	/**
	* customer's email address
	* @var string max. 50 characters
	*/
	public $emailAddress;

	/**
	* customer's phone number
	* @var string max. 32 characters
	*/
	public $phone;

	/**
	* customer's mobile phone number
	* @var string max. 32 characters
	*/
	public $mobile;

	/**
	* customer's fax number
	* @var string max. 32 characters
	*/
	public $fax;

	/**
	* customer's website URL
	* @var string max. 512 characters
	*/
	public $website;

	/**
	* comments about the customer
	* @var string max. 255 characters
	*/
	public $comments;

	// card details

	/**
	* name on credit card
	* @var string max. 50 characters
	*/
	public $cardHoldersName;

	/**
	* credit card number, with no spaces
	* @var string max. 50 characters
	*/
	public $cardNumber;

	/**
	* month of expiry, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardExpiryMonth;

	/**
	* year of expiry
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardExpiryYear;

	/**
	* start month, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardStartMonth;

	/**
	* start year
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardStartYear;

	/**
	* card issue number
	* @var string
	*/
	public $cardIssueNumber;

	/**
	* CVN (Creditcard Verification Number) for verifying physical card is held by buyer
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber;

	/**
	* true when there is shipping information
	* @var bool
	*/
	public $hasShipping;

	/**
	* shipping method: one of the SHIP_METHOD_* values
	* @var string max. 30 characters
	*/
	public $shipMethod;

	/**
	* shipping first name
	* @var string max. 50 characters
	*/
	public $shipFirstName;

	/**
	* shipping last name
	* @var string max. 50 characters
	*/
	public $shipLastName;

	/**
	* shipping address line 1
	* @var string max. 50 characters
	*/
	public $shipAddress1;

	/**
	* shipping address line 2
	* @var string max. 50 characters
	*/
	public $shipAddress2;

	/**
	* shipping suburb/city/town
	* @var string max. 50 characters
	*/
	public $shipSuburb;

	/**
	* shipping state/province
	* @var string max. 50 characters
	*/
	public $shipState;

	/**
	* shipping postcode
	* @var string max. 30 characters
	*/
	public $shipPostcode;

	/**
	* shipping country code
	* @var string 2 characters lowercase
	*/
	public $shipCountry;

	/**
	* shipping email address
	* @var string max. 50 characters
	*/
	public $shipEmailAddress;

	/**
	* shipping phone number
	* @var string max. 32 characters
	*/
	public $shipPhone;

	/**
	* shipping fax number
	* @var string max. 32 characters
	*/
	public $shipFax;

	/**
	* optional additional information for use in shopping carts, etc.
	* @var array[string] max. 254 characters each
	*/
	public $options = [];

	#endregion "payment specific members"

	#endregion "members"

	/**
	* populate members with defaults, and set account and environment information
	* @param string $apiKey Eway API key
	* @param string $apiPassword Eway API password
	* @param boolean $useSandbox use Eway sandbox
	*/
	public function __construct($apiKey, $apiPassword, $useSandbox = true) {
		$this->apiKey			= $apiKey;
		$this->apiPassword		= $apiPassword;
		$this->useSandbox		= $useSandbox;
		$this->capture			= true;
		$this->sslVerifyPeer	= true;
		$this->httpUserAgent	= 'Eway Payment Gateway v' . EWAY_PAYMENTS_VERSION;
	}

	/**
	* process a payment against Eway; throws exception on error with error described in exception message.
	* @throws EwayPaymentsException
	*/
	public function processPayment() {
		$errors = $this->validateAmount();

		if (!empty($errors)) {
			throw new EwayPaymentsException(implode("\n", $errors));
		}

		$request = $this->getPaymentDirect();
		$responseJSON = $this->apiPostRequest(self::API_DIRECT_PAYMENT, $request);

		$response = new EwayResponseDirectPayment();
		$response->loadResponse($responseJSON);

		return $response;
	}

	/**
	* validate the amount for processing
	* @return array list of errors in validation
	*/
	protected function validateAmount() {
		$errors = [];

		if (!is_numeric($this->amount) || $this->amount <= 0) {
			$errors[] = __('amount must be given as a number in dollars and cents', 'eway-payment-gateway');
		}
		else if (!is_float($this->amount)) {
			$this->amount = (float) $this->amount;
		}

		return $errors;
	}

	/**
	* create JSON request document for direct payment
	* @return string
	*/
	public function getPaymentDirect() {
		$request = new \stdClass();

		$request->Customer				= $this->getCustomerRecord(true);
		$request->Payment				= $this->getPaymentRecord();
		$request->TransactionType		= self::TRANS_PURCHASE;
		$request->PartnerID				= self::PARTNER_ID;
		$request->CustomerIP			= get_customer_IP(!$this->useSandbox);

		if (!$this->capture) {
			// just authorise the transaction;
			$request->Method = self::METHOD_AUTHORISE;
		}
		else {
			// capture transaction for non-token payment
			$request->Method = self::METHOD_PAYMENT;
		}

		if ($this->hasShipping) {
			$request->ShippingAddress	= $this->getShippingAddressRecord();
		}

		if (!empty($this->options)) {
			$request->Options			= $this->getOptionsRecord();
		}

		if (!empty($this->deviceID)) {
			$request->DeviceID 			= substr($this->deviceID, 0, 50);
		}

		return wp_json_encode($request);
	}

	/**
	* build Customer record for request
	* @return stdClass
	*/
	protected function getCustomerRecord() {
		$record = new \stdClass;

		$record->Title				= $this->title				? self::sanitiseTitle($this->title) : '';
		$record->FirstName			= $this->firstName			? substr($this->firstName, 0, 50) : '';
		$record->LastName			= $this->lastName			? substr($this->lastName, 0, 50) : '';
		$record->Street1			= $this->address1			? substr($this->address1, 0, 50) : '';
		$record->Street2			= $this->address2			? substr($this->address2, 0, 50) : '';
		$record->City				= $this->suburb				? substr($this->suburb, 0, 50) : '';
		$record->State				= $this->state				? substr($this->state, 0, 50) : '';
		$record->PostalCode			= $this->postcode			? substr($this->postcode, 0, 30) : '';
		$record->Country			= $this->country			? strtolower($this->country) : '';
		$record->Email				= $this->emailAddress		? substr($this->emailAddress, 0, 50) : '';
		$record->CardDetails		= $this->getCardDetailsRecord();

		if (!empty($this->companyName)) {
			$record->CompanyName	= substr($this->companyName, 0, 50);
		}

		if (!empty($this->jobDescription)) {
			$record->JobDescription	= substr($this->jobDescription, 0, 50);
		}

		if (!empty($this->phone)) {
			$record->Phone			= substr($this->phone, 0, 32);
		}

		if (!empty($this->mobile)) {
			$record->Mobile			= substr($this->mobile, 0, 32);
		}

		if (!empty($this->fax)) {
			$record->Fax			= substr($this->fax, 0, 32);
		}

		if (!empty($this->website)) {
			$record->Url			= substr($this->website, 0, 512);
		}

		if (!empty($this->comments)) {
			$record->Comments		= substr($this->comments, 0, 255);
		}

		return $record;
	}

	/**
	* build ShippindAddress record for request
	* @return stdClass
	*/
	protected function getShippingAddressRecord() {
		$record = new \stdClass;

		if ($this->shipMethod) {
			$record->ShippingMethod	= $this->shipMethod;
		}
		if ($this->shipFirstName) {
			$record->FirstName		= substr($this->shipFirstName, 0, 50);
		}
		if ($this->shipLastName) {
			$record->LastName		= substr($this->shipLastName, 0, 50);
		}
		if ($this->shipEmailAddress) {
			$record->Email			= substr($this->shipEmailAddress, 0, 50);
		}
		if ($this->shipPhone) {
			$record->Phone			= substr($this->shipPhone, 0, 32);
		}
		if ($this->shipFax) {
			$record->Fax			= substr($this->shipFax, 0, 32);
		}

		$record->Street1			= $this->shipAddress1		? substr($this->shipAddress1, 0, 50) : '';
		$record->Street2			= $this->shipAddress2		? substr($this->shipAddress2, 0, 50) : '';
		$record->City				= $this->shipSuburb			? substr($this->shipSuburb, 0, 50) : '';
		$record->State				= $this->shipState			? substr($this->shipState, 0, 50) : '';
		$record->PostalCode			= $this->shipPostcode		? substr($this->shipPostcode, 0, 30) : '';
		$record->Country			= $this->shipCountry		? strtolower($this->shipCountry) : '';

		return $record;
	}

	/**
	* build CardDetails record for request
	* NB: TODO: does not currently handle StartMonth, StartYear, IssueNumber (used in UK)
	* NB: card number and CVN can be very lengthy encrypted values
	* @return stdClass
	*/
	protected function getCardDetailsRecord() {
		$record = new \stdClass;

		if (!empty($this->cardHoldersName)) {
			$record->Name				= substr($this->cardHoldersName, 0, 50);
		}

		if (!empty($this->cardNumber)) {
			$record->Number				= $this->cardNumber;
		}

		if (!empty($this->cardExpiryMonth)) {
			$record->ExpiryMonth		= sprintf('%02d', $this->cardExpiryMonth);
		}

		if (!empty($this->cardExpiryYear)) {
			$record->ExpiryYear			= sprintf('%02d', $this->cardExpiryYear % 100);
		}

		if (!empty($this->cardVerificationNumber)) {
			$record->CVN				= $this->cardVerificationNumber;
		}

		return $record;
	}

	/**
	* build Payment record for request
	* @return stdClass
	*/
	protected function getPaymentRecord() {
		$record = new \stdClass;

		if ($this->amount > 0) {
			$record->TotalAmount		= self::formatCurrency($this->amount, $this->currencyCode);
			$record->InvoiceReference	= $this->transactionNumber	? substr($this->transactionNumber, 0, 50) : '';
			$record->InvoiceDescription	= $this->invoiceDescription	? substr($this->invoiceDescription, 0, 64) : '';
			$record->InvoiceNumber		= $this->invoiceReference	? substr($this->invoiceReference, 0, 12) : '';
			$record->CurrencyCode		= $this->currencyCode		? substr($this->currencyCode, 0, 3) : '';
		}
		else {
			$record->TotalAmount		= 0;
		}

		return $record;
	}

	/**
	* build Options record for request
	* @return array
	*/
	protected function getOptionsRecord() {
		$options = [];

		foreach ($this->options as $option) {
			if (!empty($option)) {
				$options[] = ['Value' => substr($option, 0, 254)];
			}
		}

		return $options;
	}

	/**
	* generalise an API post request
	* @param string $endpoint
	* @param string $request
	* @return string JSON response
	* @throws EwayPaymentsException
	*/
	protected function apiPostRequest($endpoint, $request) {
		// select host and endpoint
		$host = $this->useSandbox ? self::API_HOST_SANDBOX : self::API_HOST_LIVE;
		$url = "$host/$endpoint";

		// execute the request, and retrieve the response
		$response = wp_remote_post($url, [
			'user-agent'	=> $this->httpUserAgent,
			'sslverify'		=> $this->sslVerifyPeer,
			'timeout'		=> 30,
			'headers'		=> [
									'Content-Type'		=> 'application/json',
									'Authorization'		=> $this->getBasicAuthentication(),
							   ],
			'body'			=> $request,
		]);

		// check for http error
		$this->checkHttpResponse($response);

		return wp_remote_retrieve_body($response);
	}

	/**
	* generalise an API get request
	* @param string $endpoint
	* @param string $request
	* @return string JSON response
	* @throws EwayPaymentsException
	*/
	protected function apiGetRequest($endpoint, $request) {
		// select host and endpoint
		$host = $this->useSandbox ? self::API_HOST_SANDBOX : self::API_HOST_LIVE;
		$url = sprintf('%s/%s/%s', $host, urlencode($endpoint), urlencode($request));

		// execute the request, and retrieve the response
		$response = wp_remote_get($url, [
			'user-agent'	=> $this->httpUserAgent,
			'sslverify'		=> $this->sslVerifyPeer,
			'timeout'		=> 30,
			'headers'		=> [
									'Content-Type'		=> 'application/json',
									'Authorization'		=> $this->getBasicAuthentication(),
							   ],
		]);

		// check for http error
		$this->checkHttpResponse($response);

		return wp_remote_retrieve_body($response);
	}

	/**
	* get encoded authorisation information for request
	* @return string
	*/
	protected function getBasicAuthentication() {
		return 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiPassword}");
	}

	/**
	* check http get/post response, throw exception if an error occurred
	* @param array $response
	* @throws EwayPaymentsException
	*/
	protected function checkHttpResponse($response) {
		// failure to handle the http request
		if (is_wp_error($response)) {
			$msg = $response->get_error_message();
			throw new EwayPaymentsException(sprintf(__('Error posting Eway request: %s', 'eway-payment-gateway'), $msg));
		}

		// error code returned by request
		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			$msg = wp_remote_retrieve_response_message($response);

			if (empty($msg)) {
				$msg = sprintf(__('Error posting Eway request: %s', 'eway-payment-gateway'), $code);
			}
			else {
				/* translators: 1. the error code; 2. the error message */
				$msg = sprintf(__('Error posting Eway request: %1$s, %2$s', 'eway-payment-gateway'), $code, $msg);
			}
			throw new EwayPaymentsException($msg);
		}
	}

	/**
	* format amount per currency
	* @param float $amount
	* @param string $currencyCode
	* @return string
	*/
	protected static function formatCurrency($amount, $currencyCode) {
		switch ($currencyCode) {

			// Japanese Yen already has no decimal fraction
			case 'JPY':
				$value = number_format($amount, 0, '', '');
				break;

			default:
				$value = number_format($amount * 100, 0, '', '');
				break;

		}

		return $value;
	}

	/**
	* sanitise the customer title, to avoid error V6058: Invalid Customer Title
	* @param string $title
	* @return string
	*/
	protected static function sanitiseTitle($title) {
		$valid = [
			'mr'			=> 'Mr.',
			'master'		=> 'Mr.',
			'ms'			=> 'Ms.',
			'mrs'			=> 'Mrs.',
			'missus'		=> 'Mrs.',
			'miss'			=> 'Miss',
			'dr'			=> 'Dr.',
			'doctor'		=> 'Dr.',
			'sir'			=> 'Sir',
			'prof'			=> 'Prof.',
			'professor'		=> 'Prof.',
		];

		$simple = rtrim(strtolower(trim($title)), '.');

		return isset($valid[$simple]) ? $valid[$simple] : '';
	}

}
