<?php

ini_set('session.use_strict_mode', 1);
session_start();

define('SYSBASE', dirname(__DIR__));

require_once SYSBASE . '/vendor/autoload.php';

include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';

require_once SYSBASE . '/lib/class.Helper.php';
require_once SYSBASE . '/lib/baseclass/Enum.php';
require_once SYSBASE . '/lib/LoadGroups.php';
require_once SYSBASE . '/lib/Singleton.php';
require_once SYSBASE . '/lib/baseclass/Enum.php';
require_once SYSBASE . '/lib/LoadGroups.php';
require_once SYSBASE . '/lib/class.Crypto.php';
require_once SYSBASE . '/lib/class.MailHandler.php';
require_once SYSBASE . '/lib/svg/class.SvgDiagram.php';
require_once SYSBASE . '/lib/class.EscFunc.php';
require_once SYSBASE . '/lib/Renderer.php';
require_once SYSBASE . '/lib/HibiscusXMLRPCConnector.php';
require_once SYSBASE . '/lib/DBConnector.php';
require_once SYSBASE . '/lib/inc.helper.php';
require_once SYSBASE . '/lib/FormHandlerInterface.php';
require_once SYSBASE . '/lib/ExternVorgangHandler.php';
require_once SYSBASE . '/lib/MenuRenderer.php';
require_once SYSBASE . '/lib/class.BookingTableManager.php';
require_once SYSBASE . '/lib/class.BookingHandler.php';
require_once SYSBASE . '/lib/FormTemplater.php';
require_once SYSBASE . '/lib/ProjektHandler.php';
require_once SYSBASE . '/lib/AuslagenHandler2.php';
require_once SYSBASE . '/lib/HHPHandler.php';
require_once SYSBASE . '/lib/FinTSHandler.php';
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



Singleton::configureAll($conf);

//if (!extension_loaded("zip")) die("Missing ZIP support");
