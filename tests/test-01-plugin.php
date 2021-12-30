<?php
namespace webaware\eway_payment_gateway\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Facebook\WebDriver\WebDriverBy;
use webaware\eway_payment_gateway\Plugin;
use webaware\eway_payment_gateway\EwayRapidAPI;

class PluginTest extends TestCase {

	public WebDriverRunner $web;

	private static $web_runner;

	/**
	 * create a web driver for testing
	 */
	public static function setUpBeforeClass() : void {
		self::$web_runner = new WebDriverRunner();
	}

	/**
	 * close the web driver after tests complete
	 */
	public static function tearDownAfterClass() : void {
		self::$web_runner->close();
	}

	/**
	 * make the web driver runner available to a test instance
	 */
	protected function setUp() : void {
		parent::setUp();

		$this->web = self::$web_runner;
	}

	/**
	 * ensure that environment has been specified
	 */
	public function testEnvironment() : void {
		global $plugin_test_env;

		$this->assertArrayHasKey('eway_api_key', $plugin_test_env);
		$this->assertArrayHasKey('eway_api_password', $plugin_test_env);
		$this->assertArrayHasKey('eway_ecrypt_key', $plugin_test_env);
		$this->assertArrayHasKey('eway_customerid', $plugin_test_env);
	}

	/**
	 * can get instance of plugin
	 * @depends testEnvironment
	 */
	public function testPlugin() : void {
		$this->assertTrue(Plugin::getInstance() instanceof Plugin);
	}

	/**
	 * fully-populated transaction generates correct JSON
	 * @depends testPlugin
	 */
	public function testJsonTxFull() : void {
		$eway							= $this->getAPI();

		$eway->invoiceDescription		= __FUNCTION__;
		$eway->invoiceReference			= '5554321';
		$eway->transactionNumber		= '5554321';
		$eway->cardHoldersName			= 'Test Only';
		$eway->cardNumber				= '4444333322221111';
		$eway->cardExpiryMonth			= 12;
		$eway->cardExpiryYear			= date('Y') + 9;
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
	public function testJsonTxPartial() : void {
		$eway							= $this->getAPI();

		$eway->invoiceDescription		= __FUNCTION__;
		$eway->invoiceReference			= '5554321';
		$eway->transactionNumber		= '5554321';
		$eway->cardHoldersName			= 'Test Only';
		$eway->cardNumber				= '4444333322221111';
		$eway->cardExpiryMonth			= 12;
		$eway->cardExpiryYear			= date('Y') + 9;
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
	 * test client-side encryption, generically
	 * @depends testPlugin
	 */
	public function testClientSideEncryption() : void {
		global $plugin_test_env;

		$this->web->driver->get($this->getPageCSE());
		$this->web->setFieldValue('#cse_key', $plugin_test_env['eway_ecrypt_key']);
		$this->web->sendKeys('#card_number', '4444333322221111');
		$this->web->sendKeys('#card_cvn', '123');
		$this->web->driver->findElement(WebDriverBy::id('submit_button'))->click();

		$card_number = $this->web->driver->findElement(WebDriverBy::id('card_number'))->getDomProperty('value');
		$card_cvn = $this->web->driver->findElement(WebDriverBy::id('card_cvn'))->getDomProperty('value');

		$this->assertStringStartsWith('eCrypted:', $card_number);
		$this->assertStringStartsWith('eCrypted:', $card_cvn);
	}

	/**
	 * test end-to-end transaction
	 * @depends testClientSideEncryption
	 */
	public function testTransaction() : void {
		global $plugin_test_env;

		$this->web->driver->get($this->getPageCSE());
		$this->web->setFieldValue('#cse_key', $plugin_test_env['eway_ecrypt_key']);
		$this->web->sendKeys('#card_number', '4444333322221111');
		$this->web->sendKeys('#card_cvn', '123');
		$this->web->driver->findElement(WebDriverBy::id('submit_button'))->click();

		$card_number = $this->web->driver->findElement(WebDriverBy::id('card_number'))->getDomProperty('value');
		$card_cvn = $this->web->driver->findElement(WebDriverBy::id('card_cvn'))->getDomProperty('value');

		$this->assertStringStartsWith('eCrypted:', $card_number);
		$this->assertStringStartsWith('eCrypted:', $card_cvn);

		$eway							= $this->getAPI();

		$eway->invoiceDescription		= __FUNCTION__;
		$eway->invoiceReference			= '5554321';
		$eway->transactionNumber		= '5554321';
		$eway->cardHoldersName			= 'Test Only';
		$eway->cardNumber				= $card_number;
		$eway->cardExpiryMonth			= 12;
		$eway->cardExpiryYear			= date('Y') + 9;
		$eway->cardVerificationNumber	= $card_cvn;
		$eway->amount					= 100.00;
		$eway->currencyCode				= 'AUD';
		$eway->firstName				= 'Test';
		$eway->lastName					= 'Only';
		$eway->emailAddress				= 'test@example.com';
		$eway->country					= 'AU';
		$eway->comments					= 'End-to-end test transaction';

		$response = $eway->processPayment();

		$this->assertTrue($response->TransactionStatus);
	}

	/**
	 * get an API wrapper
	 * @return EwayRapidAPI
	 */
	private function getAPI() : EwayRapidAPI {
		global $plugin_test_env;

		return new EwayRapidAPI($plugin_test_env['eway_api_key'], $plugin_test_env['eway_api_password'], true);
	}

	/**
	 * get file URL for client-side encryption test form
	 * @return string
	 */
	private function getPageCSE() : string {
		return 'file://' . __DIR__ . '/html/ecrypt.html';
	}

}
