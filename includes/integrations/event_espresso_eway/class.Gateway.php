<?php
namespace webaware\eway_payment_gateway\event_espresso;

use webaware\eway_payment_gateway\Logging;

use function webaware\eway_payment_gateway\get_api_wrapper;

if (!defined('ABSPATH')) {
	exit;
}

/**
* Event Espresso gateway functionality
*/
class Gateway extends \EE_Onsite_Gateway {

	protected $logger;

	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array $billing_info
	 * @return \EE_Payment|\EEI_Payment
	 */
	public function do_direct_payment($payment, $billing_info = null) {
		// create a logger
		$this->logger = new Logging('event-espresso', empty($this->_eway_logging) ? 'off' : $this->_eway_logging);

		$this->log($billing_info, $payment);

		try {

			if (!$payment instanceof \EEI_Payment) {
				throw new EwayPaymentsException(__('Error. No associated payment was found.', 'eway-payment-gateway'));
			}

			$transaction = $payment->transaction();
			if (!$transaction instanceof \EEI_Transaction) {
				throw new EwayPaymentsException(__('Could not process this payment because it has no associated transaction.', 'eway-payment-gateway'));
			}

			$capture	= true;		// TODO: maybe support stored payment for EE
			$useSandbox	= $this->_debug_mode;
			$creds		= $this->getApiCredentials();
			$eway		= get_api_wrapper($creds, $capture, $useSandbox);

			if (!$eway) {
				throw new EwayPaymentsException(__('eWAY payments is not configured for payments yet.', 'eway-payment-gateway'));
			}

			$gateway_formatter		= $this->_get_gateway_formatter();
			$primary_registration	= $transaction->primary_registration();
			$primary_attendee		= $primary_registration instanceof EE_Registration ? $primary_registration->attendee() : false;

			$TXN_ID = $payment->TXN_ID();

			// allow plugins/themes to modify transaction ID; NB: must remain unique for eWAY account!
			$transactionID = apply_filters('event_espresso_eway_trans_number', $TXN_ID);

			// wrap up the billing information cleanly
			$billing = new BillingInfo($billing_info);

			$eway->invoiceDescription		= $gateway_formatter->formatOrderDescription($payment);
			$eway->invoiceReference			= $TXN_ID;						// customer invoice reference
			$eway->transactionNumber		= $transactionID;
			$eway->cardHoldersName			= $billing->card_name;
			$eway->cardNumber				= $billing->card_number;
			$eway->cardExpiryMonth			= $billing->expiry_month;
			$eway->cardExpiryYear			= $billing->expiry_year;
			$eway->cardVerificationNumber	= $billing->cvn;
			$eway->amount					= $payment->amount();
			$eway->currencyCode				= $payment->currency_code();
			$eway->firstName				= $billing->first_name;
			$eway->lastName					= $billing->last_name;
			// $eway->companyName				= $???;
			$eway->emailAddress				= $billing->email;
			$eway->phone					= $billing->phone;
			$eway->address1					= $billing->address;
			$eway->address2					= $billing->address2;
			$eway->suburb					= $billing->city;
			$eway->state					= $billing->state;
			$eway->postcode					= $billing->zip;
			$eway->country					= $billing->country;
			// $eway->comments					= ???;

			// use cardholder name for last name if no customer name entered
			if (empty($eway->firstName) && empty($eway->lastName)) {
				$eway->lastName				= $eway->cardHoldersName;
			}

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$eway->invoiceDescription		= apply_filters('event_espresso_eway_invoice_desc', $eway->invoiceDescription, $TXN_ID);
			$eway->invoiceReference			= apply_filters('event_espresso_eway_invoice_ref', $eway->invoiceReference, $TXN_ID);
			$eway->options					= array_filter([
													apply_filters('event_espresso_eway_option1', '', $TXN_ID),
													apply_filters('event_espresso_eway_option2', '', $TXN_ID),
													apply_filters('event_espresso_eway_option3', '', $TXN_ID),
											], 'strlen');

			$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
				$useSandbox ? 'test' : 'live', $eway->invoiceReference, $eway->transactionNumber, $eway->amount, $eway->cardNumber));

			$response = $eway->processPayment();

			if ($response->TransactionStatus) {
				// transaction was successful, so record details and complete payment
				$payment->set_txn_id_chq_nmbr($response->TransactionID);
				$extra = [];
				if (!empty($response->AuthorisationCode)) {
					$extra[] = 'Authcode ' . $response->AuthorisationCode;
				}
				if ($response->BeagleScore >= 0) {
					$extra[] = 'Beagle score ' . $response->BeagleScore;
				}
				if (!empty($extra)) {
					$payment->set_extra_accntng(implode("\n", $extra));
				}

				$payment->set_status($this->_pay_model->approved_status());
				$payment->set_gateway_response(__('Payment accepted', 'eway-payment-gateway') . "\n" . implode("\n", $response->ResponseMessage));

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
					$eway->invoiceReference, $response->TransactionID, 'completed',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$payment->set_gateway_response($error_msg);
				$payment->set_status($this->_pay_model->failed_status());

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $eway->invoiceReference, $response->getErrorsForLog()));
				if ($response->BeagleScore > 0) {
					$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
				}
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$payment->set_gateway_response(esc_html($e->getMessage()));
			$payment->set_status($this->_pay_model->failed_status());

			$this->logger->log('error', $e->getMessage());
		}

		return $payment;
	}

	/**
	* get API credentials based on settings
	* @return array
	*/
	protected function getApiCredentials() {
		static $creds = false;

		if ($creds === false) {
			if (!$this->_debug_mode) {
				$creds = array_filter([
					'api_key'		=> $this->_eway_api_key,
					'password'		=> $this->_eway_password,
					'ecrypt_key'	=> $this->_eway_ecrypt_key,
				]);
			}
			else {
				$creds = array_filter([
					'api_key'		=> $this->_eway_sandbox_api_key,
					'password'		=> $this->_eway_sandbox_password,
					'ecrypt_key'	=> $this->_eway_sandbox_ecrypt_key,
				]);
			}
		}

		return $creds;
	}

}
