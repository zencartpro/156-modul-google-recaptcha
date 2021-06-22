<?php
/**
*
* @package Google reCaptcha
* @copyright Copyright 2003-2021 Zen Cart Development Team
* @copyright Portions Copyright 2003 osCommerce
* @license http://www.zen-cart-pro.at/license/2_0.txt GNU Public License V2.0
* @version $Id: auto.google_recaptcha_observer.php 2021-06-22 09:40:36Z webchills $
*/

class zcObserverGoogleRecaptchaObserver extends base {
    public function __construct() {
        $pages_to_check = [];
        if (defined('GOOGLE_RECAPCHTA_CONTACT_US') && GOOGLE_RECAPCHTA_CONTACT_US==='true') {
            $pages_to_check[] = 'NOTIFY_CONTACT_US_CAPTCHA_CHECK';
        }
        if (defined('GOOGLE_RECAPCHTA_CREATE_ACCOUNT') && GOOGLE_RECAPCHTA_CREATE_ACCOUNT==='true') {
            $pages_to_check[] = 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK';
        }
        if (defined('GOOGLE_RECAPCHTA_REVIEWS') && GOOGLE_RECAPCHTA_REVIEWS==='true') {
            $pages_to_check[] = 'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK';
        }
        if (count($pages_to_check) > 0) $this->attach($this, $pages_to_check);
    }

    /**
     * @param $class
     * @param $eventID
     * @param array $paramsArray
     * @return bool|string
     */
    public function update(&$class, $eventID, $paramsArray = array()) {
        global $messageStack, $error, $privatekey; //$error is used by template header to send or reject form.

        require_once __DIR__ . '/google/autoload.php';

        switch (true) {
            case (ini_get('allow_url_fopen')!=='1' && function_exists('fsockopen')) :
                // if file_get_contents() is disabled, this alternative request method uses fsockopen().
                $method = 'SocketPost';
                $recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\SocketPost());
                break;
            case (!function_exists('fsockopen')) :
                // if fsockopen is disabled, this alternative request method uses Curl().
                $method = 'CurlPost';
                $recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\CurlPost());
                break;
            default:
                $method = 'default';
                $recaptcha = new \ReCaptcha\ReCaptcha($privatekey);
        }

        $event_array = array('NOTIFY_CONTACT_US_CAPTCHA_CHECK' => 'contact', 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK' => 'create_account', 'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK' => 'review_text'); 

//uncomment for debugging
//$messageStack->add('contact', 'allow_url_fopen=' . ini_get('allow_url_fopen') . '<br>' . 'fsockopen=' . function_exists('fsockopen') . '<br>' . '$method used=' . $method);

        if (isset($_POST['g-recaptcha-response'])) {
            $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
            if (!$resp->isSuccess()) {
                $errorArray = array();
                $errors = $resp->getErrorCodes();
                //replace Google error codes with local language strings
                if (in_array('missing-input-secret', $errors, true)) {
                    $errorArray[] = RECAPTCHA_MISSING_INPUT_SECRET;
                }
                if (in_array('invalid-input-secret', $errors, true)) {
                    $errorArray[] = RECAPTCHA_INVALID_INPUT_SECRET;
                }
                if (in_array('missing-input-response', $errors, true)) {
                    $errorArray[] = RECAPTCHA_MISSING_INPUT_RESPONSE;
                }
                if (in_array('invalid-input-response', $errors, true)) {
                    $errorArray[] = RECAPTCHA_INVALID_INPUT_RESPONSE;
                }
                if (in_array('bad-request', $errors, true)) {
                    $errorArray[] = RECAPTCHA_BAD_REQUEST;
                }
                if (in_array('timeout-or-duplicate', $errors, true)) {
                    $errorArray[] = RECAPTCHA_TIMEOUT_OR_DUPLICATE;
                }
                $error_messages = implode(', ', $errorArray);
                $messageStack->add($event_array[$eventID], $error_messages);
                $error = true;
            }
        } else {
            $messageStack->add($event_array[$eventID], RECAPTCHA_MISSING_INPUT_RESPONSE);
            $_POST['g-recaptcha-response'] = 'no recaptcha response';
            $error = true;
        }
        return $error;
    }
}