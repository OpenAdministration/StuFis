<?php

define('SYSBASE', dirname(dirname(__FILE__)));
require_once SYSBASE . '/lib/Singleton.php';
require_once SYSBASE . '/lib/HibiscusXMLRPCConnector.php';
require_once SYSBASE . '/lib/AuthHandler.php';
require_once SYSBASE . '/lib/DBConnector.php';
require_once SYSBASE . '/lib/inc.helper.php';
require_once(SYSBASE . '/lib/class.validateEmail.php');
require_once SYSBASE . '/lib/inc.error.php';
require_once SYSBASE.'/lib/inc.header.php';
require_once SYSBASE.'/lib/inc.seo.php';
require_once SYSBASE.'/lib/inc.mail.php';
require_once SYSBASE.'/lib/inc.formulare.php';
require_once SYSBASE.'/lib/inc.printable.php';
require_once SYSBASE.'/lib/inc.sni.php';
require_once SYSBASE.'/lib/inc.dokuwiki.php';
require_once SYSBASE . '/lib/MenuRenderer.php';
require_once SYSBASE . '/lib/FormTemplater.php';
require_once SYSBASE . '/lib/ProjektHandler.php';
require_once SYSBASE . '/lib/StateHandler.php';
require_once SYSBASE . '/lib/PermissionHandler.php';
require_once SYSBASE . '/lib/exceptions/IllegalStateException.php';
require_once SYSBASE . '/lib/exceptions/IllegalTransitionException.php';
require_once SYSBASE . '/lib/exceptions/ActionNotSetException.php';
require_once SYSBASE . '/lib/exceptions/IdNotSetException.php';
require_once SYSBASE . '/lib/exceptions/InvalidDataException.php';
require_once SYSBASE . '/lib/exceptions/OldFormException.php';
require_once SYSBASE . '/lib/exceptions/WrongVersionException.php';
require_once SYSBASE.'/lib/php-sepa-xml/SepaTransferFile.php';
require_once 'XML/RPC2/Client.php';
include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';
Singleton::configureAll($conf);

if (!extension_loaded("zip")) die("Missing ZIP support");

// Mail
require_once 'Mail.php';
global $mail_object;
$mail_object = Mail::factory('smtp', array("debug" => false, "timeout" => 5));

