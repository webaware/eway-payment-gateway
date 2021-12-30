<?php
namespace webaware\eway_payment_gateway;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * extend Eway response for Direct Connection payment request
 * @link https://eway.io/api-v3/
 */
final class EwayResponseDirectPayment extends EwayResponse {

	#region members

	/**
	 * bank authorisation code
	 */
	public ?string $AuthorisationCode;

	/**
	 * 2-digit bank response code
	 */
	public ?string $ResponseCode;

	/**
	 * array of codes describing the result (including Beagle failure codes)
	 * @var array
	 */
	public $ResponseMessage;

	/**
	 * Eway transaction ID
	 */
	public ?string $TransactionID;

	/**
	 * Eway transaction status: true for success
	 */
	public ?bool $TransactionStatus;

	/**
	 * Eway transaction type
	 */
	public ?string $TransactionType;

	/**
	 * Beagle fraud detection score
	 */
	public ?string $BeagleScore;

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
	 */
	protected function getMessageInvalid() : string {
		return __('Invalid response from Eway for Direct payment', 'eway-payment-gateway');
	}

}
