<?php
/**
* @package Google reCaptcha
* @copyright Copyright 2003-2017 Zen Cart Development Team
* @copyright Portions Copyright 2003 osCommerce
* @license http://www.zen-cart-pro.at/license/2_0.txt GNU Public License V2.0
* @version $Id: config.google_recaptcha.php 2017-07-03 12:47:36Z webchills $
*/
if (!defined('IS_ADMIN_FLAG')) {
 die('Illegal Access');
}
$autoLoadConfig[190][] = array('autoType'=>'class',
                              'loadFile'=>'observers/class.google_recaptcha.php');
$autoLoadConfig[190][] = array('autoType'=>'classInstantiate',
                              'className'=>'google_recaptcha',
                              'objectName'=>'google_recaptcha');
