<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
* extend Eway response for Direct Connection payment request
* @link https://eway.io/api-v3/
*/
class EwayResponseDirectPayment extends EwayResponse {

	#region members

	/**
	* bank authorisation code
	* @var string
	*/
	public $AuthorisationCode;

	/**
	* 2-digit bank response code
	* @var string
	*/
	public $ResponseCode;

	/**
	* array of codes describing the result (including Beagle failure codes)
	* @var array
	*/
	public $ResponseMessage;

	/**
	* Eway transacation ID
	* @var string
	*/
	public $TransactionID;

	/**
	* Eway transaction status: true for success
	* @var boolean
	*/
	public $TransactionStatus;

	/**
	* Eway transaction type
	* @var string
	*/
	public $TransactionType;

	/**
	* Beagle fraud detection score
	* @var string
	*/
	public $BeagleScore;

	/**
	* verification results object
	* @var object
	*/
	public $Verification;

	/**
	* customer details object (includes card details object)
	* @var object
	*/
	public $Customer;

	/**
	* payment details object
	* @var object
	*/
	public $Payment;

	/**
	* a list of errors
	* @var array
	*/
	public $Errors;

	#endregion

	/**
	* get 'invalid response' message for this response class
	* @return string
	*/
	protected function getMessageInvalid() {
		return __('Invalid response from Eway for Direct payment', 'eway-payment-gateway');
	}

}
