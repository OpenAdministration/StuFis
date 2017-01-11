<?php

define('SYSBASE', dirname(dirname(__FILE__)));

require_once SYSBASE.'/config/config.php';
require_once SYSBASE.'/lib/inc.error.php';
require_once SYSBASE.'/lib/inc.simplesaml.php';
require_once SYSBASE.'/lib/inc.db.php';
require_once SYSBASE.'/lib/inc.nonce.php';
require_once SYSBASE.'/lib/inc.header.php';
require_once SYSBASE.'/lib/inc.seo.php';
require_once SYSBASE.'/lib/inc.helper.php';
require_once SYSBASE.'/lib/inc.mail.php';
require_once SYSBASE.'/lib/inc.formulare.php';

// Mail
require_once 'Mail.php';
global $mail_object;
$mail_object =& Mail::factory('smtp', array("debug" => false, "timeout" => 5));

