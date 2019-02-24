<?php

define('SYSBASE', dirname(dirname(__FILE__)));
include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';

require_once SYSBASE . '/lib/class.Helper.php';
require_once SYSBASE . '/lib/baseclass/Enum.php';
require_once SYSBASE . '/lib/LoadGroups.php';
require_once SYSBASE . '/lib/Singleton.php';
require_once SYSBASE . '/lib/baseclass/Enum.php';
require_once SYSBASE . '/lib/LoadGroups.php';
require_once SYSBASE . '/lib/class.Crypto.php';
/* --- include external library: phpmailer --- */
define('MAIL_LANGUAGE_PATH', SYSBASE . '/lib/external_libraries/phpmailer/language');
require_once SYSBASE . '/lib/external_libraries/phpmailer/src/PHPMailer.php';
require_once SYSBASE . '/lib/external_libraries/phpmailer/src/SMTP.php';
require_once SYSBASE . '/lib/external_libraries/phpmailer/src/Exception.php';
/* --- */
require_once SYSBASE . '/lib/class.MailHandler.php';
require_once SYSBASE . '/lib/svg/class.SvgDiagram.php';
require_once SYSBASE . '/lib/class.EscFunc.php';
require_once SYSBASE . '/lib/Renderer.php';
require_once SYSBASE . '/lib/HibiscusXMLRPCConnector.php';
require_once SYSBASE . '/lib/class.AuthHandler.php';
require_once SYSBASE . '/lib/AuthSamlHandler.php';
require_once SYSBASE . '/lib/class.AuthBasicHandler.php';
require_once SYSBASE . '/lib/DBConnector.php';
require_once SYSBASE . '/lib/inc.helper.php';
require_once SYSBASE . '/lib/FormHandlerInterface.php';
require_once SYSBASE . '/lib/ExternVorgangHandler.php';
require_once SYSBASE . '/lib/MenuRenderer.php';
require_once SYSBASE . '/lib/FormTemplater.php';
require_once SYSBASE . '/lib/ProjektHandler.php';
require_once SYSBASE . '/lib/AuslagenHandler2.php';
require_once SYSBASE . '/lib/HHPHandler.php';
require_once SYSBASE . '/lib/StateHandler.php';
require_once SYSBASE . '/lib/chat/ChatHandler.php';
require_once SYSBASE . '/lib/PermissionHandler.php';
require_once SYSBASE . '/lib/HTMLPageRenderer.php';
require_once SYSBASE . '/lib/class.RestHandler.php';
require_once SYSBASE . '/lib/class.FileController.php';
require_once SYSBASE . '/lib/class.ErrorHandler.php';
require_once SYSBASE . '/lib/class.Router.php';
require_once SYSBASE . '/lib/class.CSVBuilder.php';

require_once SYSBASE . '/lib/exceptions/IllegalStateException.php';
require_once SYSBASE . '/lib/exceptions/IllegalTransitionException.php';
require_once SYSBASE . '/lib/exceptions/ActionNotSetException.php';
require_once SYSBASE . '/lib/exceptions/IdNotSetException.php';
require_once SYSBASE . '/lib/exceptions/InvalidDataException.php';
require_once SYSBASE . '/lib/exceptions/OldFormException.php';
require_once SYSBASE . '/lib/exceptions/WrongVersionException.php';

require_once SYSBASE . '/lib/php-sepa-xml/SepaTransferFile.php';

Singleton::configureAll($conf);

//if (!extension_loaded("zip")) die("Missing ZIP support");
