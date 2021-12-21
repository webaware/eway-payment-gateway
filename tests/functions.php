<?php
namespace webaware\eway_payment_gateway\Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * get browser driver
 */
function webdriver_get_driver() {
	$host = 'http://localhost:4444/';
	$capabilities = DesiredCapabilities::chrome();
	return RemoteWebDriver::create($host, $capabilities);
}

/**
 * helper for php-webdriver: clear field then send keys
 * @param object $element
 * @param string $new_value
 */
function webdriver_replace_value($element, $new_value) {
	$element->clear();
	$element->sendKeys($new_value);
}
