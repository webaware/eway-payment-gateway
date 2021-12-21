<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use webaware\eway_payment_gateway\Plugin;

use function webaware\eway_payment_gateway\get_api_wrapper;

class PluginTest extends TestCase {

	/**
	 * ensure that environment has been specified
	 */
	public function testEnvironment() {
		global $plugin_main_env;

		$this->assertArrayHasKey('eway_api_key', $plugin_main_env);
		$this->assertArrayHasKey('eway_api_password', $plugin_main_env);
		$this->assertArrayHasKey('eway_ecrypt_key', $plugin_main_env);
		$this->assertArrayHasKey('eway_customerid', $plugin_main_env);
	}

	/**
	 * can get instance of plugin
	 * @depends testEnvironment
	 */
	public function testPlugin() {
		$this->assertTrue(Plugin::getInstance() instanceof Plugin);
	}

	/**
	 * fully-populated transaction generates correct JSON
	 * @depends testPlugin
	 */
	public function testJsonTxFull() {
		$eway							= $this->getAPI();

		$eway->invoiceDescription		= __FUNCTION__;
		$eway->invoiceReference			= '5554321';
		$eway->transactionNumber		= '5554321';
		$eway->cardHoldersName			= 'Test Only';
		$eway->cardNumber				= '4444333322221111';
		$eway->cardExpiryMonth			= 12;
		$eway->cardExpiryYear			= 2030;
		$eway->cardVerificationNumber	= '123';
		$eway->amount					= 100.00;
		$eway->currencyCode				= 'AUD';
		$eway->firstName				= 'Test';
		$eway->lastName					= 'Only';
		$eway->companyName				= 'Testers, Inc';
		$eway->emailAddress				= 'test@example.com';
		$eway->phone					= '0123456789';
		$eway->address1					= '123 Example Street';
		$eway->address2					= '';
		$eway->suburb					= 'Sometown';
		$eway->state					= 'NSW';
		$eway->postcode					= '2000';
		$eway->country					= 'AU';
		$eway->countryName				= 'Australia';
		$eway->comments					= 'Fully populated test transaction';

		$eway->hasShipping				= true;
		$eway->shipFirstName			= 'Amos';
		$eway->shipLastName				= 'Squito';
		$eway->shipAddress1				= '999 Example Street';
		$eway->shipAddress2				= '"The Castle"';
		$eway->shipSuburb				= 'Another Town';
		$eway->shipState				= 'New South Wales';
		$eway->shipCountry				= 'AU';
		$eway->shipPostcode				= 'Australia';

		$json = $eway->getPaymentDirect();

		$expected = '{"Customer":{"FirstName":"Test","LastName":"Only","Street1":"123 Example Street","City":"Sometown","State":"NSW","PostalCode":"2000","Country":"au","Email":"test@example.com","CompanyName":"Testers, Inc","Phone":"0123456789","Comments":"Fully populated test transaction","CardDetails":{"Name":"Test Only","Number":"4444333322221111","ExpiryMonth":"12","ExpiryYear":"30","CVN":"123"}},"Payment":{"TotalAmount":"10000","InvoiceNumber":"5554321","InvoiceDescription":"testJsonTxFull","InvoiceReference":"5554321","CurrencyCode":"AUD"},"ShippingAddress":{"FirstName":"Amos","LastName":"Squito","Street1":"999 Example Street","Street2":"\"The Castle\"","City":"Another Town","State":"New South Wales","PostalCode":"Australia","Country":"au"},"CustomerIP":"103.29.100.101","Method":"ProcessPayment","TransactionType":"Purchase","PartnerID":"4577fd8eb9014c7188d7be672c0e0d88"}';

		$this->assertSame($json, $expected);
	}

	/**
	 * partially-populated transaction generates correct JSON
	 * @depends testPlugin
	 */
	public function testJsonTxPartial() {
		$eway							= $this->getAPI();

		$eway->invoiceDescription		= __FUNCTION__;
		$eway->invoiceReference			= '5554321';
		$eway->transactionNumber		= '5554321';
		$eway->cardHoldersName			= 'Test Only';
		$eway->cardNumber				= '4444333322221111';
		$eway->cardExpiryMonth			= 12;
		$eway->cardExpiryYear			= 2030;
		$eway->cardVerificationNumber	= '123';
		$eway->amount					= 100.00;
		$eway->currencyCode				= 'AUD';
		$eway->firstName				= 'Test';
		$eway->lastName					= 'Only';
		$eway->emailAddress				= 'test@example.com';
		$eway->country					= 'AU';
		$eway->comments					= 'Partially populated test transaction';

		$json = $eway->getPaymentDirect();

		$expected = '{"Customer":{"FirstName":"Test","LastName":"Only","Country":"au","Email":"test@example.com","Comments":"Partially populated test transaction","CardDetails":{"Name":"Test Only","Number":"4444333322221111","ExpiryMonth":"12","ExpiryYear":"30","CVN":"123"}},"Payment":{"TotalAmount":"10000","InvoiceNumber":"5554321","InvoiceDescription":"testJsonTxPartial","InvoiceReference":"5554321","CurrencyCode":"AUD"},"CustomerIP":"103.29.100.101","Method":"ProcessPayment","TransactionType":"Purchase","PartnerID":"4577fd8eb9014c7188d7be672c0e0d88"}';

		$this->assertSame($json, $expected);
	}

	/**
	 * get an API wrapper
	 * @return EwayRapidAPI
	 */
	private function getAPI() {
		global $plugin_main_env;
		$capture	= true;
		$useSandbox	= true;
		$creds = [
			'api_key'		=> $plugin_main_env['eway_api_key'],
			'password'		=> $plugin_main_env['eway_api_password'],
			'ecrypt_key'	=> $plugin_main_env['eway_ecrypt_key'],
			'customerid'	=> $plugin_main_env['eway_customerid'],
		];
		return get_api_wrapper($creds, $capture, $useSandbox);
	}

}
