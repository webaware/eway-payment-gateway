<?php
namespace webaware\eway_payment_gateway;

use JsonSerializable;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class for dealing with an Eway Rapid API payment
 * @link https://eway.io/api-v3/
 */
final class EwayRapidAPI {

	#region "constants"

	// API hosts
	const API_HOST_LIVE						= 'https://api.ewaypayments.com';
	const API_HOST_SANDBOX					= 'https://api.sandbox.ewaypayments.com';

	const PARTNER_ID						= '4577fd8eb9014c7188d7be672c0e0d88';

	#endregion // constants

	#region "members"

	/**
	 * use Eway sandbox
	 */
	public bool $useSandbox;

	/**
	 * capture payment (alternative is just authorise, no capture)
	 */
	public bool $capture = true;

	/**
	 * whether to validate the remote TLS/SSL certificate
	 */
	public bool $sslVerifyPeer = true;

	/**
	 * connection timeout in seconds
	 */
	public int $timeout = 15;

	/**
	 * API key
	 */
	private string $apiKey;

	/**
	 * API password
	 */
	private string $apiPassword;

	/**
	 * ID of device or application processing the transaction
	 */
	public string $deviceID;

	/**
	 * HTTP user agent string identifying plugin, perhaps for debugging
	 */
	public string $httpUserAgent;

	#endregion "members"

	/**
	 * populate members with defaults, and set account and environment information
	 */
	public function __construct(string $apiKey, string $apiPassword, bool $useSandbox) {
		$this->apiKey			= $apiKey;
		$this->apiPassword		= $apiPassword;
		$this->useSandbox		= $useSandbox;
		$this->httpUserAgent	= 'Eway Payment Gateway v' . EWAY_PAYMENTS_VERSION;
	}

	/**
	 * process a payment against Eway; throws exception on error with error described in exception message.
	 * @throws EwayPaymentsException
	 */
	public function processPayment(CustomerDetails $customer, ?ShippingAddress $shipping, PaymentDetails $payment, array $options) : EwayResponseDirectPayment {
		$errors = $this->validateAmount($payment->TotalAmount);

		if (!empty($errors)) {
			throw new EwayPaymentsException(implode("\n", $errors));
		}

		$request = $this->getPaymentDirect($customer, $shipping, $payment, $options);
		$responseJSON = $this->apiPostRequest('Transaction', $request);

		$response = new EwayResponseDirectPayment();
		$response->loadResponse($responseJSON);

		return $response;
	}

	/**
	 * validate the amount for processing
	 */
	protected function validateAmount($amount) : array {
		$errors = [];

		if (!is_numeric($amount) || $amount <= 0) {
			$errors[] = __('amount must be given as a number in dollars and cents', 'eway-payment-gateway');
		}

		return $errors;
	}

	/**
	 * create JSON request document for direct payment
	 */
	public function getPaymentDirect(CustomerDetails $customer, ?ShippingAddress $shipping, PaymentDetails $payment, array $options) : string {
		$is_live_site = !$this->useSandbox;

		$request = new TransactionRequest($is_live_site, self::PARTNER_ID, TransactionRequest::TRANS_PURCHASE, $this->capture);
		$request->Customer			= $customer;
		$request->Payment			= $payment;
		$request->ShippingAddress	= $shipping;

		if (!empty($options)) {
			$request->Options		= $options;
		}

		return wp_json_encode($request);
	}

	/**
	 * generalise an API post request
	 * @throws EwayPaymentsException
	 */
	protected function apiPostRequest(string $endpoint, string $request) : string {
		// select host and endpoint
		$host = $this->useSandbox ? self::API_HOST_SANDBOX : self::API_HOST_LIVE;
		$url = "$host/$endpoint";

		// execute the request, and retrieve the response
		$response = wp_remote_post($url, [
			'user-agent'	=> $this->httpUserAgent,
			'sslverify'		=> $this->sslVerifyPeer,
			'timeout'		=> $this->timeout,
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
	 * @throws EwayPaymentsException
	 */
	protected function apiGetRequest(string $endpoint, string $request) : string {
		// select host and endpoint
		$host = $this->useSandbox ? self::API_HOST_SANDBOX : self::API_HOST_LIVE;
		$url = sprintf('%s/%s/%s', $host, urlencode($endpoint), urlencode($request));

		// execute the request, and retrieve the response
		$response = wp_remote_get($url, [
			'user-agent'	=> $this->httpUserAgent,
			'sslverify'		=> $this->sslVerifyPeer,
			'timeout'		=> $this->timeout,
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
	 */
	protected function getBasicAuthentication() : string {
		return 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiPassword}");
	}

	/**
	 * check http get/post response, throw exception if an error occurred
	 * @param array|object $response
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

}

/**
 * implement jsonSerialize() that removes null/uninitialised properties
 */
trait SerialiseWithoutNull {

	/**
	 * convert object properties to array (stripping uninitialised properties)
	 * and then filter out null properties
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return array_filter((array) $this, static function($value) {
			return !is_null($value);
		});
	}

}

/**
 * card details record
 */
final class CardDetails implements JsonSerializable {

	use SerialiseWithoutNull;

	public ?string				$Name;
	public ?string				$Number;
	public ?string				$ExpiryMonth;
	public ?string				$ExpiryYear;
	public ?string				$StartMonth;	// UK
	public ?string				$StartYear;		// UK
	public ?string				$IssueNumber;	// UK
	public ?string				$CVN;

	public function __construct(?string $name, ?string $card_number, ?string $expiry_month, ?string $expiry_year, ?string $cvn) {
		$this->Name				= $name ? substr($name, 0, 50) : null;
		$this->ExpiryMonth		= $expiry_month ? sprintf('%02d', $expiry_month) : null;
		$this->ExpiryYear		= $expiry_year ? sprintf('%02d', $expiry_year % 100) : null;

		// these may be long encrypted strings from CSE (Client Side Encryption)
		$this->Number			= $card_number ?: null;
		$this->CVN				= $cvn ?: null;
	}

}

/**
 * Customer Details record
 */
final class CustomerDetails implements JsonSerializable {

	use SerialiseWithoutNull;

	public ?string				$Title;
	public ?string				$FirstName;
	public ?string				$LastName;
	public ?string				$Street1;
	public ?string				$Street2;
	public ?string				$City;
	public ?string				$State;
	public ?string				$PostalCode;
	public ?string				$Country;
	public ?string				$Email;
	public ?string				$CompanyName;
	public ?string				$JobDescription;
	public ?string				$Phone;
	public ?string				$Mobile;
	public ?string				$Comments;
	public ?string				$Fax;
	public ?string				$Url;
	public ?CardDetails			$CardDetails;

	public function setTitle(?string $title) {
		$this->Title = $title ? sanitise_customer_title($title) : null;
	}

	public function setFirstName(?string $first_name) {
		$this->FirstName = $first_name ? substr($first_name, 0, 50) : null;
	}

	public function setLastName(?string $last_name) {
		$this->LastName = $last_name ? substr($last_name, 0, 50) : null;
	}

	public function setStreet1(?string $address1) {
		$this->Street1 = $address1 ? substr($address1, 0, 50) : null;
	}

	public function setStreet2(?string $address2) {
		$this->Street2 = $address2 ? substr($address2, 0, 50) : null;
	}

	public function setCity(?string $suburb) {
		$this->City = $suburb ? substr($suburb, 0, 50) : null;
	}

	public function setState(?string $state) {
		$this->State = $state ? substr($state, 0, 50) : null;
	}

	public function setPostalCode(?string $postcode) {
		$this->PostalCode = $postcode ? substr($postcode, 0, 30) : null;
	}

	public function setCountry(?string $country) {
		$this->Country = $country ? substr(strtolower($country), 0, 2) : null;
	}

	public function setEmail(?string $email_address) {
		$this->Email = $email_address ? substr($email_address, 0, 50) : null;
	}

	public function setCompanyName(?string $company_name) {
		$this->CompanyName = $company_name ? substr($company_name, 0, 50) : null;
	}

	public function setJobDescription(?string $job_description) {
		$this->JobDescription = $job_description ? substr($job_description, 0, 50) : null;
	}

	public function setPhone(?string $phone) {
		$this->Phone = $phone ? substr($phone, 0, 32) : null;
	}

	public function setMobile(?string $mobile) {
		$this->Mobile = $mobile ? substr($mobile, 0, 32) : null;
	}

	public function setComments(?string $comments) {
		$this->Comments = $comments ? substr($comments, 0, 255) : null;
	}

	public function setFax(?string $fax) {
		$this->Fax = $fax ? substr($fax, 0, 32) : null;
	}

	public function setUrl(?string $website) {
		$this->Url = $website ? substr($website, 0, 512) : null;
	}

}

/**
 * Shipping Address record
 */
final class ShippingAddress implements JsonSerializable {

	use SerialiseWithoutNull;

	// valid shipping methods
	const SHIP_METHOD_UNKNOWN			= 'Unknown';
	const SHIP_METHOD_LOWCOST			= 'LowCost';
	const SHIP_METHOD_CUSTOMER			= 'DesignatedByCustomer';
	const SHIP_METHOD_INTERNATIONAL		= 'International';
	const SHIP_METHOD_MILITARY			= 'Military';
	const SHIP_METHOD_NEXTDAY			= 'NextDay';
	const SHIP_METHOD_PICKUP			= 'StorePickup';
	const SHIP_METHOD_2DAY				= 'TwoDayService';
	const SHIP_METHOD_3DAY				= 'ThreeDayService';
	const SHIP_METHOD_OTHER				= 'Other';

	public ?string				$ShippingMethod;
	public ?string				$FirstName;
	public ?string				$LastName;
	public ?string				$Street1;
	public ?string				$Street2;
	public ?string				$City;
	public ?string				$State;
	public ?string				$PostalCode;
	public ?string				$Country;
	public ?string				$Email;
	public ?string				$Phone;
	public ?string				$Fax;

	public function setShippingMethod(?string $method) {
		$this->ShippingMethod = $method ? substr($method, 0, 30) : null;
	}

	public function setFirstName(?string $first_name) {
		$this->FirstName = $first_name ? substr($first_name, 0, 50) : null;
	}

	public function setLastName(?string $last_name) {
		$this->LastName = $last_name ? substr($last_name, 0, 50) : null;
	}

	public function setStreet1(?string $street1) {
		$this->Street1 = $street1 ? substr($street1, 0, 50) : null;
	}

	public function setStreet2(?string $street2) {
		$this->Street2 = $street2 ? substr($street2, 0, 50) : null;
	}

	public function setCity(?string $city) {
		$this->City = $city ? substr($city, 0, 50) : null;
	}

	public function setState(?string $state) {
		$this->State = $state ? substr($state, 0, 50) : null;
	}

	public function setPostalCode(?string $postcode) {
		$this->PostalCode = $postcode ? substr($postcode, 0, 30) : null;
	}

	public function setCountry(?string $country) {
		$this->Country = $country ? substr(strtolower($country), 0, 2) : null;
	}

	public function setEmail(?string $email) {
		$this->Email = $email ? substr($email, 0, 50) : null;
	}

	public function setPhone(?string $phone) {
		$this->Phone = $phone ? substr($phone, 0, 32) : null;
	}

	public function setFax(?string $fax) {
		$this->Fax = $fax ? substr($fax, 0, 32) : null;
	}

}

/**
 * Payment record
 */
final class PaymentDetails implements JsonSerializable {

	use SerialiseWithoutNull;

	public string				$TotalAmount = '0';		// must be '0' for CreateTokenCustomer, UpdateTokenCustomer
	public ?string				$InvoiceNumber;
	public ?string				$InvoiceDescription;
	public ?string				$InvoiceReference;
	public ?string				$CurrencyCode;

	public function setTotalAmount($amount, string $currency_code) {
		$this->TotalAmount = $amount ? format_currency($amount, $currency_code) : '0';
	}

	public function setInvoiceNumber(?string $invoice_number) {
		$this->InvoiceNumber = $invoice_number ? substr($invoice_number, 0, 64) : null;
	}

	public function setInvoiceDescription(?string $description) {
		$this->InvoiceDescription = $description ? substr($description, 0, 64) : null;
	}

	public function setInvoiceReference(?string $reference) {
		$this->InvoiceReference	= $reference ? substr($reference, 0, 50) : null;
	}

	public function setCurrencyCode(?string $currency_code) {
		$this->CurrencyCode = $currency_code ? substr($currency_code, 0, 3) : null;
	}

}

/**
 * Direct Connection Transaction Request record
 */
final class TransactionRequest implements JsonSerializable {

	use SerialiseWithoutNull;

	// valid transaction types
	const TRANS_PURCHASE		= 'Purchase';
	const TRANS_RECURRING		= 'Recurring';
	const TRANS_MOTO			= 'MOTO';

	// valid actions
	const METHOD_PAYMENT		= 'ProcessPayment';
	const METHOD_AUTHORISE		= 'Authorise';

	public CustomerDetails		$Customer;
	public ?PaymentDetails		$Payment;
	public ?ShippingAddress		$ShippingAddress;
	public ?array				$Options;
	public string				$CustomerIP;
	public string				$Method;
	public string				$TransactionType;
	public ?string				$DeviceID;
	public string				$PartnerID;

	public function __construct(bool $is_live_site, string $partner_id, string $transaction_type, bool $is_capture) {
		$this->CustomerIP		= get_customer_IP($is_live_site);
		$this->PartnerID		= $partner_id;
		$this->TransactionType	= $transaction_type;
		$this->Method			= $is_capture ? self::METHOD_PAYMENT : self::METHOD_AUTHORISE;
	}

	public function setDeviceID(?string $device_id) {
		$this->DeviceID = $device_id ? substr($device_id, 0, 50) : null;
	}

}
