<?php

/**
* payment gateway integration for Another WordPress Classifieds Plugin since v3.0
* @link http://www.awpcp.com/
*/
class EwayPaymentsAWPCP3 extends AWPCP_PaymentGateway {

	protected $integration;

	/**
	* initialise payment gateway
	* @param EwayPaymentsAWPCP $integration the integration code for AWPCP v < 3.0
	*/
    public function __construct($integration) {
		$this->integration = $integration;

		$methods = $this->integration->awpcpPaymentMethods(array());
		$method = $methods[0];

        parent::__construct($method->slug, $method->name, $method->description, $method->icon);
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
		$form = "<p>" . $this->integration->awpcpCheckoutStepText('', false, $transaction) . "</p>\n";
        $form .= $this->integration->awpcpCheckoutForm('', $transaction);
        return $form;
    }

	/**
	* process payment notification
	* @param AWPCP_Payment_Transaction $transaction
	*/
    public function process_payment_notification($transaction) {
		return;
    }

	/**
	* process completed transaction
	* @param AWPCP_Payment_Transaction $transaction
	*/
    public function process_payment_completed($transaction) {
		$errors = $this->integration->verifyForm($transaction);
		$success = (count($errors) === 0);

		$transaction->errors['verification-post'] = $errors;
		$transaction->errors['validation'] = array();

		if ($success) {

			try {
				$response = $this->integration->processTransaction($transaction);

				if ($response->status) {
					// transaction was successful, so record details and complete payment
					$transaction->set('txn-id', $response->transactionNumber);
					$transaction->completed = current_time('mysql');

					if (!empty($response->authCode)) {
						$transaction->set('eway_authcode', $response->authCode);
					}

					//~ if (!empty($response->beagleScore)) {
						//~ $transaction->set('eway_beagle_score', $response->beagleScore);
					//~ }

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
				}
				else {
					// transaction was unsuccessful, so record transaction number and the error
					$transaction->set('txn-id', $response->transactionNumber);
					$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
					$transaction->errors['validation'] = nl2br(esc_html($response->error . "\nuse your browser's back button to try again."));
					$success = false;
				}
			}
			catch (EwayPaymentsException $e) {
				// an exception occured, so record the error
				$transaction->payment_status = AWPCP_Payment_Transaction::PAYMENT_STATUS_FAILED;
				$transaction->errors['validation'] = nl2br(esc_html($e->getMessage()) . "\nuse your browser's back button to try again.");
				$success = false;
			}
		}

		$transaction->set('verified', $success);
	}

	/**
	* process payment cancellation
	* @param AWPCP_Payment_Transaction $transaction
	*/
    public function process_payment_canceled($transaction) {
        // TODO: process_payment_canceled
    }

}
