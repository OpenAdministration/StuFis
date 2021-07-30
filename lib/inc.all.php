<?php

use framework\Singleton;

ini_set('session.use_strict_mode', 1);
session_start();


$conf = include SYSBASE . "/config/config.php";
require_once SYSBASE . '/lib/inc.nonce.php';
require_once SYSBASE . '/lib/inc.helper.php';

define("BASE_URL", 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://'. $_SERVER["SERVER_NAME"] . ':' . $_SERVER['SERVER_PORT']);
const FULL_APP_PATH = BASE_URL . URIBASE;

Singleton::configureAll($conf);
