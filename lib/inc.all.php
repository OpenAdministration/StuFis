<?php

define('SYSBASE', dirname(dirname(__FILE__)));

require_once SYSBASE.'/config/config.php';
require_once(SYSBASE.'/lib/class.validateEmail.php');
require_once SYSBASE.'/lib/inc.error.php';
require_once SYSBASE.'/lib/inc.simplesaml.php';
require_once SYSBASE.'/lib/inc.db.php';
require_once SYSBASE.'/lib/inc.nonce.php';
require_once SYSBASE.'/lib/inc.header.php';
require_once SYSBASE.'/lib/inc.seo.php';
require_once SYSBASE.'/lib/inc.helper.php';
require_once SYSBASE.'/lib/inc.mail.php';
require_once SYSBASE.'/lib/inc.formulare.php';
require_once SYSBASE.'/lib/inc.printable.php';
require_once SYSBASE.'/lib/inc.sni.php';
require_once SYSBASE.'/lib/inc.dokuwiki.php';
require_once SYSBASE.'/lib/inc.hibiscus.php';
require_once SYSBASE . '/lib/HTML_Renderer.php';
require_once SYSBASE.'/lib/php-sepa-xml/SepaTransferFile.php';
require_once 'XML/RPC2/Client.php';

if (!extension_loaded("zip")) die("Missing ZIP support");

// Mail
require_once 'Mail.php';
global $mail_object;
$mail_object = Mail::factory('smtp', array("debug" => false, "timeout" => 5));

