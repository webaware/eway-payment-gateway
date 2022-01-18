<?php
namespace webaware\eway_payment_gateway\event_espresso;

use EE_Onsite_Gateway;
use EEI_Payment;
use EEI_Transaction;
use webaware\eway_payment_gateway\Credentials;
use webaware\eway_payment_gateway\EwayRapidAPI;
use webaware\eway_payment_gateway\CustomerDetails;
use webaware\eway_payment_gateway\CardDetails;
use webaware\eway_payment_gateway\PaymentDetails;
use webaware\eway_payment_gateway\Logging;

use function webaware\eway_payment_gateway\get_api_options;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Event Espresso gateway functionality
 */
final class Gateway extends EE_Onsite_Gateway {

	private $logger;

	/**
	 *
	 * @param EEI_Payment $ee_payment
	 * @param array $billing_info
	 * @return EE_Payment|EEI_Payment
	 */
	public function do_direct_payment($ee_payment, $billing_info = null) {
		// create a logger
		$this->logger = new Logging('event-espresso', empty($this->_eway_logging) ? 'off' : $this->_eway_logging);

		$this->log($billing_info, $ee_payment);

		try {

			if (! $ee_payment instanceof EEI_Payment) {
				throw new EwayPaymentsException(__('Error. No associated payment was found.', 'eway-payment-gateway'));
			}

			$transaction = $ee_payment->transaction();
			if (! $transaction instanceof EEI_Transaction) {
				throw new EwayPaymentsException(__('Could not process this payment because it has no associated transaction.', 'eway-payment-gateway'));
			}

			$capture	= true;		// TODO: maybe support stored payment for EE
			$useSandbox	= $this->_debug_mode;
			$creds		= $this->getApiCredentials();

			if ($creds->isMissingCredentials()) {
				$this->logger->log('error', 'credentials need to be defined before transactions can be processed.');
				throw new EwayPaymentsException(__('Eway payments is not configured for payments yet', 'eway-payment-gateway'));
			}

			$eway		= new EwayRapidAPI($creds->api_key, $creds->password, $useSandbox);
			$eway->capture = $capture;

			$gateway_formatter		= $this->_get_gateway_formatter();
			$primary_registration	= $transaction->primary_registration();
			$primary_attendee		= $primary_registration instanceof EE_Registration ? $primary_registration->attendee() : false;

			$TXN_ID = $ee_payment->TXN_ID();

			// allow plugins/themes to modify transaction ID; NB: must remain unique for Eway account!
			$transactionID = apply_filters('event_espresso_eway_trans_number', $TXN_ID);

			// wrap up the billing information cleanly
			$billing = new BillingInfo($billing_info);

			$customer = new CustomerDetails;
			$customer->setFirstName($billing->first_name);
			$customer->setLastName($billing->last_name);
			$customer->setStreet1($billing->address);
			$customer->setStreet2($billing->address2);
			$customer->setCity($billing->city);
			$customer->setState($billing->state);
			$customer->setPostalCode($billing->zip);
			$customer->setCountry($billing->country);
			$customer->setEmail($billing->email);
			$customer->setPhone($billing->phone);

			$customer->CardDetails = new CardDetails(
				$billing->card_name,
				$billing->card_number,
				$billing->expiry_month,
				$billing->expiry_year,
				$billing->cvn,
			);

			// use cardholder name for last name if no customer name entered
			if (empty($customer->FirstName) && empty($customer->LastName)) {
				$customer->setLastName($customer->CardDetails->Name);
			}

			// only populate payment record if there's an amount value
			$payment = new PaymentDetails;
			$amount = $ee_payment->amount();
			$currency = $ee_payment->currency_code();
			if ($amount > 0) {
				$payment->setTotalAmount($amount, $currency);
				$payment->setCurrencyCode($currency);
				$payment->setInvoiceReference($transactionID);
				$payment->setInvoiceDescription(apply_filters('event_espresso_eway_invoice_desc', $gateway_formatter->formatOrderDescription($ee_payment), $TXN_ID));
				$payment->setInvoiceNumber(apply_filters('event_espresso_eway_invoice_ref', $TXN_ID, $TXN_ID));
			}

			// allow plugins/themes to set option fields
			$options = get_api_options([
				apply_filters('event_espresso_eway_option1', '', $TXN_ID),
				apply_filters('event_espresso_eway_option2', '', $TXN_ID),
				apply_filters('event_espresso_eway_option3', '', $TXN_ID),
			]);

			$this->logger->log('info', sprintf('%1$s gateway, invoice ref: %2$s, transaction: %3$s, amount: %4$s, cc: %5$s',
				$useSandbox ? 'test' : 'live',
				$payment->InvoiceNumber, $payment->InvoiceReference, $payment->TotalAmount, $customer->CardDetails->Number));

			$response = $eway->processPayment($customer, null, $payment, $options);

			if ($response->TransactionStatus) {
				// transaction was successful, so record details and complete payment
				$ee_payment->set_txn_id_chq_nmbr($response->TransactionID);
				$extra = [];
				if (!empty($response->AuthorisationCode)) {
					$extra[] = 'Authcode ' . $response->AuthorisationCode;
				}
				if ($response->BeagleScore >= 0) {
					$extra[] = 'Beagle score ' . $response->BeagleScore;
				}
				if (!empty($extra)) {
					$ee_payment->set_extra_accntng(implode("\n", $extra));
				}

				$ee_payment->set_status($this->_pay_model->approved_status());
				$ee_payment->set_gateway_response(__('Payment accepted', 'eway-payment-gateway') . "\n" . implode("\n", $response->ResponseMessage));

				$this->logger->log('info', sprintf('success, invoice ref: %1$s, transaction: %2$s, status = %3$s, amount = %4$s, authcode = %5$s, Beagle = %6$s',
				$payment->InvoiceNumber, $response->TransactionID, 'completed',
					$response->Payment->TotalAmount, $response->AuthorisationCode, $response->BeagleScore));
			}
			else {
				// transaction was unsuccessful, so record transaction number and the error
				$error_msg = $response->getErrorMessage(esc_html__('Transaction failed', 'eway-payment-gateway'));
				$ee_payment->set_gateway_response($error_msg);
				$ee_payment->set_status($this->_pay_model->failed_status());

				$this->logger->log('info', sprintf('failed; invoice ref: %1$s, error: %2$s', $payment->InvoiceNumber, $response->getErrorsForLog()));
				if ($response->BeagleScore > 0) {
					$this->logger->log('info', sprintf('BeagleScore = %s', $response->BeagleScore));
				}
			}
		}
		catch (EwayPaymentsException $e) {
			// an exception occured, so record the error
			$ee_payment->set_gateway_response(esc_html($e->getMessage()));
			$ee_payment->set_status($this->_pay_model->failed_status());

			$this->logger->log('error', $e->getMessage());
		}

		return $ee_payment;
	}

	/**
	 * get API credentials based on settings
	 */
	private function getApiCredentials() : Credentials {
		static $creds = false;

		if ($creds === false) {
			if (!$this->_debug_mode) {
				$creds = new Credentials(
					$this->_eway_api_key,
					$this->_eway_password,
					$this->_eway_ecrypt_key,
				);
			}
			else {
				$creds = new Credentials(
					$this->_eway_sandbox_api_key,
					$this->_eway_sandbox_password,
					$this->_eway_sandbox_ecrypt_key,
				);
			}
		}

		return $creds;
	}

}
