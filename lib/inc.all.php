<?php

define('SYSBASE', dirname(dirname(__FILE__)));
include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';

require_once SYSBASE . '/lib/Singleton.php';
require_once SYSBASE . '/lib/Renderer.php';
require_once SYSBASE . '/lib/HibiscusXMLRPCConnector.php';
require_once SYSBASE . '/lib/AuthHandler.php';
require_once SYSBASE . '/lib/DBConnector.php';
require_once SYSBASE . '/lib/inc.helper.php';
require_once SYSBASE . '/lib/FormHandlerInterface.php';
require_once SYSBASE . '/lib/MenuRenderer.php';
require_once SYSBASE . '/lib/FormTemplater.php';
require_once SYSBASE . '/lib/ProjektHandler.php';
require_once SYSBASE . '/lib/AuslagenHandler2.php';
require_once SYSBASE . '/lib/StateHandler.php';
require_once SYSBASE . '/lib/PermissionHandler.php';
require_once SYSBASE . '/lib/HTMLPageRenderer.php';
require_once SYSBASE . '/lib/class.RestHandler.php';
require_once SYSBASE . '/lib/class.FileController.php';
require_once SYSBASE . '/lib/class.ErrorHandler.php';
require_once SYSBASE . '/lib/class.Router.php';

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
