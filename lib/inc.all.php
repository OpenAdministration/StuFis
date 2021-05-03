<?php

use framework\Singleton;

ini_set('session.use_strict_mode', 1);
session_start();

define('SYSBASE', dirname(__DIR__));

require_once SYSBASE . '/vendor/autoload.php';

$conf = include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';
require_once SYSBASE . '/lib/inc.helper.php';

Singleton::configureAll($conf);
