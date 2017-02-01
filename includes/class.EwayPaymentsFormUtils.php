<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* utility functions for payment forms
*/
class EwayPaymentsFormUtils {

	/**
	* get a list of options for credit card Month dropdown list
	* @param string $current_month
	* @return string
	*/
	public static function getMonthOptions($current_month = '') {
		ob_start();

		foreach (array('01','02','03','04','05','06','07','08','09','10','11','12') as $month) {
			printf('<option value="%1$s"%2$s>%1$s</option>', $month, selected($month, $current_month, false));
		}

		return ob_get_clean();
	}

	/**
	* get a list of options for credit card Year dropdown list
	* @param string $current_year
	* @return string
	*/
	public static function getYearOptions($current_year = '') {
		ob_start();

		$thisYear = (int) date('Y');
		foreach (range($thisYear, $thisYear + 15) as $year) {
			printf('<option value="%1$s"%2$s>%1$s</option>', $year, selected($year, $current_year, false));
		}

		return ob_get_clean();
	}

	/**
	* get API wrapper, based on available credentials and settings
	* @param array $creds
	* @param bool $capture
	* @param bool $useSandbox
	* @return EwayPaymentsRapidAPI|EwayPaymentsPayment|EwayPaymentsStoredPayment
	*/
	public static function getApiWrapper($creds, $capture, $useSandbox) {
		if (!empty($creds['api_key']) && !empty($creds['password'])) {
			$eway = new EwayPaymentsRapidAPI($creds['api_key'], $creds['password'], $useSandbox);
			$eway->capture = $capture;
		}
		elseif (!empty($creds['customerid'])) {
			if ($capture) {
				$eway = new EwayPaymentsPayment($creds['customerid'], !$useSandbox);
			}
			else {
				$eway = new EwayPaymentsStoredPayment($creds['customerid'], !$useSandbox);
			}
		}
		else {
			$eway = false;
		}

		return $eway;
	}

}
