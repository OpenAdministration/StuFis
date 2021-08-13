<?php

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;
use Dotenv\Repository\RepositoryBuilder;
use framework\auth\AuthCasHandler;

const FINANRANTRAGUI_FW_SI = true; // secret.php check

ini_set('session.use_strict_mode', 1);
session_start();

$conf = [];

try {
    $repository = RepositoryBuilder::createWithDefaultAdapters()
        //->allowList(['FOO', 'BAR'])
        ->make();

    $dotenv = Dotenv::create($repository, SYSBASE);
    $dotenv->load();

    $dotenv->required('ENV')->allowedRegexValues('/(dev|production|test|demo)/i');

    $dotenv->required([
        'DB_DSN',
        'DB_PASSWORD',
        'DB_PREFIX',
        'FINTS_REG_NR',
        'AUTH_REALM',
        'AUTH_ADMIN_GROUP',
        'URIBASE'
    ]);

    $dotenv->required([
        'AUTH_DEBUG',
    ])->isBoolean();

    $dotenv->required('AUTH_METHOD')->allowedRegexValues('/(saml|cas)/i');
    switch (strtolower($_ENV['AUTH_METHOD'])) {
        case 'cas':
            define("AUTH_HANDLER", AuthCasHandler::class);
            $dotenv->required(['CAS_HOST', 'CAS_PATH', 'CAS_CERTFILE']);
            $dotenv->required('CAS_VERSION')->allowedRegexValues('/[1-3]\.0/');
            $dotenv->required('CAS_PORT')->isInteger();
            break;
        case 'saml':
            break;
    }

    switch (strtolower($_ENV['MAIL_METHOD'])) {
        case 'sendmail':
        case 'smtp':
    }
}catch (ValidationException $exception){
    die($exception->getMessage());
}



define('REALM', $_ENV['AUTH_REALM']);
define('ORG_DATA', (include SYSBASE . '/config/config.orgs.php')[REALM] ?? []);
const GREMIEN = ORG_DATA['gremien'] ?? [];
const MAILINGLISTS = ORG_DATA['mailinglists'] ?? [];
define('DEV', $_ENV['DEBUG']);
define('URIBASE', $_ENV['URIBASE']);
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://'. $_SERVER["SERVER_NAME"] . ':' . $_SERVER['SERVER_PORT']);
const FULL_APP_PATH = BASE_URL . URIBASE;

ini_set('display_errors', (DEV) ? 1 : 0);
ini_set('display_startup_errors', (DEV) ? 1 : 0);
ini_set("log_errors", 1);
error_reporting(E_ALL);
ini_set("error_log", SYSBASE . "runtime/logs/error.log");


// ===== UPLOAD SETTINGS =====
// DATABASE or FILESYSTEM storage
// Database Pros
// - good if recoverability is critical | gut wenn Wiederherstellbarkeit kritisch
// - backups with database, only new only need
// Fileysystem Pros
// - on defect systems restoring the online system is way faster if no files need to pushed back in database
// - easily run separate processes that catalog document metadata, perform virus scanning, perform keyword indexing
// - use storages wich uses compression, encryption, etc
// - no need for interpreter (PHP) to load file into ram
const UPLOAD_TARGET_DATABASE = true; // true|false store into
const UPLOAD_USE_DISK_CACHE = false;  // if DATABASE storage enabled , use filesystem as cache
const UPLOAD_MULTIFILE_BREAOK_ON_ERROR = true; //if there are multiple files on Upload and an error occures: FALSE -> upload files with no errors, TRUE upload no file
const UPLOAD_MAX_MULTIPLE_FILES = 1; // how many files can be uploaded at once
const UPLOAD_DISK_PATH = SYSBASE . '/public/files/get/filestorage'; // path to DATABASE filecache or FILESYSTEM storage - no '/' at the ends
const UPLOAD_MAX_SIZE = 41943215; //in bytes - also check DB BLOB max size and php upload size limit in php.ini
const UPLOAD_PROHIBITED_EXTENSIONS = 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden';
const UPLOAD_MOD_XSENDFILE = 1; //0 - dont use it, 1 - auto detect on apache modules, 2 force usage - if detection fails

require_once SYSBASE . '/lib/inc.nonce.php';
require_once SYSBASE . '/lib/inc.helper.php';