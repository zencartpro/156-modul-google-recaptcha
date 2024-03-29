<?php
/**
 * Contact Us Page
 *
 * @package page
 * @copyright Copyright 2003-2021 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: header_php.php for Google reCaptcha 2021-06-22 09:32:16Z webchills $
 */

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CONTACT_US');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

$error = false;
$enquiry = '';

if (isset($_GET['action']) && ($_GET['action'] == 'send')) {
    $name = zen_db_prepare_input($_POST['contactname']);
    $email_address = zen_db_prepare_input($_POST['email']);
    $enquiry = zen_db_prepare_input(strip_tags($_POST['enquiry']));
    $antiSpam = isset($_POST['should_be_empty']) ? zen_db_prepare_input($_POST['should_be_empty']) : '';
    if (!empty($_POST['contactname']) && preg_match('~https?://?~', $_POST['contactname'])) $antiSpam = 'spam';

    $zco_notifier->notify('NOTIFY_CONTACT_US_CAPTCHA_CHECK', $_POST);

    $zc_validate_email = zen_validate_email($email_address);

    if ($zc_validate_email && !empty($enquiry) && !empty($name) && $error == FALSE) {
        // if anti-spam is not triggered, prepare and send email:
        if ($antiSpam != '') {
            $zco_notifier->notify('NOTIFY_SPAM_DETECTED_USING_CONTACT_US', $_POST);
        } elseif ($antiSpam == '') {

            // auto complete when logged in
            if (zen_is_logged_in() && !zen_in_guest_checkout()) {
                $sql = "SELECT customers_id, customers_firstname, customers_lastname, customers_password, customers_email_address, customers_default_address_id 
                      FROM " . TABLE_CUSTOMERS . "
                      WHERE customers_id = :customersID";

                $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
                $check_customer = $db->Execute($sql);
                $customer_email = $check_customer->fields['customers_email_address'];
                $customer_name  = $check_customer->fields['customers_firstname'] . ' ' . $check_customer->fields['customers_lastname'];
                
            } else {
                $customer_email = NOT_LOGGED_IN_TEXT;
                $customer_name = NOT_LOGGED_IN_TEXT;
               
            }

            $zco_notifier->notify('NOTIFY_CONTACT_US_ACTION', (isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 0), $customer_email, $customer_name, $email_address, $name, $enquiry);

            // declare variable
            $send_to_array = [];

            // use contact us dropdown if defined and if a destination is provided
            if (CONTACT_US_LIST != '' && isset($_POST['send_to'])){
                $send_to_array = explode(",", CONTACT_US_LIST);

                if (isset($send_to_array[$_POST['send_to']])) {
                    preg_match('/\<[^>]+\>/', $send_to_array[$_POST['send_to']], $send_email_array);
                }
            }

            $send_to_email = trim(EMAIL_FROM); // default to EMAIL_FROM
            $send_to_name  = trim(STORE_NAME);  // default to STORE_NAME

            // Assign email destination from array
            if (!empty($send_email_array)) {
                $send_to_email = preg_replace ("/>/", "", $send_email_array[0]);
                $send_to_email = trim(preg_replace("/</", "", $send_to_email));
                $send_to_name  = trim(preg_replace('/\<[^*]*/', '', $send_to_array[$_POST['send_to']]));
            }

            // Prepare extra-info details
            $extra_info = email_collect_extra_info($name, $email_address, $customer_name, $customer_email);
            // Prepare Text-only portion of message
            $text_message = OFFICE_FROM . "\t" . $name . "\n" .
            OFFICE_EMAIL . "\t" . $email_address . "\n\n" .
            '------------------------------------------------------' . "\n\n" .
            strip_tags($_POST['enquiry']) .  "\n\n" .
            '------------------------------------------------------' . "\n\n" .
            $extra_info['TEXT'];
            // Prepare HTML-portion of message
            $html_msg['EMAIL_MESSAGE_HTML'] = strip_tags($_POST['enquiry']);
            $html_msg['CONTACT_US_OFFICE_FROM'] = OFFICE_FROM . ' ' . $name . '<br />' . OFFICE_EMAIL . '(' . $email_address . ')';
            $html_msg['EXTRA_INFO'] = $extra_info['HTML'];
            // Send message
            zen_mail($send_to_name, $send_to_email, EMAIL_SUBJECT, $text_message, $name, $email_address, $html_msg, 'contact_us');
        }
        zen_redirect(zen_href_link(FILENAME_CONTACT_US, 'action=success', 'SSL'));
    } else {
        $error = true;
        if (empty($name)) {
            $messageStack->add('contact', ENTRY_EMAIL_NAME_CHECK_ERROR);
        }
        if ($zc_validate_email == false) {
            $messageStack->add('contact', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
        }
        if (empty($enquiry)) {
            $messageStack->add('contact', ENTRY_EMAIL_CONTENT_CHECK_ERROR);
        }
    }
} // end action==send


if (ENABLE_SSL == 'true' && $request_type != 'SSL') {
    zen_redirect(zen_href_link(FILENAME_CONTACT_US, '', 'SSL'));
}

//bof Sticky
$name = $name ?? '';
$email_address = $email_address ?? '';
//eof Sticky

// default email and name if customer is logged in
if (zen_is_logged_in()) {
    $sql = "SELECT customers_id, customers_firstname, customers_lastname, customers_password, customers_email_address, customers_default_address_id 
            FROM " . TABLE_CUSTOMERS . "
            WHERE customers_id = :customersID";

    $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_customer = $db->Execute($sql);
    $email_address = $check_customer->fields['customers_email_address'];
    $name = $check_customer->fields['customers_firstname'] . ' ' . $check_customer->fields['customers_lastname'];
    
}

$send_to_array = array();
if (CONTACT_US_LIST !=''){
    foreach (explode(",", CONTACT_US_LIST) as $k => $v) {
        $send_to_array[] = array('id' => $k, 'text' => preg_replace('/\<[^*]*/', '', $v));
    }
}

// include template specific file name defines
$define_page = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/', FILENAME_DEFINE_CONTACT_US, 'false');

$breadcrumb->add(NAVBAR_TITLE);

// This should be the last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CONTACT_US');
