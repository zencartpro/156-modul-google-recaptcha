<?php
/**
*
* @package Google reCaptcha
* @copyright Copyright 2003-2019 Zen Cart Development Team
* @copyright Portions Copyright 2003 osCommerce
* @license http://www.zen-cart-pro.at/license/2_0.txt GNU Public License V2.0
* @version $Id: class.google_recaptcha.php 2019-06-27 08:31:36Z webchills $
*/

class google_recaptcha extends base {
	/**
	 * @var array
	 */
	private $pages_to_checkcheck = array();

	function __construct() {

		//comment out any pages you do NOT want to check
		// Wenn Sie das reCaptcha auch auf den Seiten Registrierung und Bewertung schreiben aktivieren wollen, dann entkommentieren Sie hier die beiden Seiten und fügen den reCaptcha Aufruf auch in die entsprechenden Templates ein.
		$pages_to_check[] =  'NOTIFY_CONTACT_US_CAPTCHA_CHECK';
		//$pages_to_check[] =  'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK';
		//$pages_to_check[] =  'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK';
		$this->attach($this,$pages_to_check);
	}

	function update(&$class, $eventID, $paramsArray = array()) {
		global $messageStack, $error, $privatekey;

		require_once __DIR__ . '/google/autoload.php';
		
		// If file_get_contents() is locked down on your PHP installation to disallow
		// its use with URLs, then you can use the alternative request method instead.
		// This makes use of fsockopen() instead.
		//$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\SocketPost());
		// This makes use of curl instead
		$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\Curl());

		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

		if (!$resp->isSuccess()) {
			$event_array = array('NOTIFY_CONTACT_US_CAPTCHA_CHECK' => 'contact', 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK' => 'create_account', 'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK' => 'review_text');
			$messageStack->add($event_array[$eventID], $resp->getErrors());
			$error = true;
		}
		return $error;
	}
}