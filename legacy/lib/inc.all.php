<?php

$conf = [];

define('REALM', config('app.realm'));
if (App::runningUnitTests()) {
    define('ORG_DATA', (include SYSBASE.'/config/config.orgs.testing.php')[REALM] ?? []);
} else {
    define('ORG_DATA', (include SYSBASE.'/config/config.orgs.php')[REALM] ?? []);
}
const GREMIEN = ORG_DATA['gremien'] ?? [];
const MAILINGLISTS = ORG_DATA['mailinglists'] ?? [];
define('DEV', config('app.debug'));
const URIBASE = '/';
define('BASE_URL', config('app.url'));
define('FINTS_REGNR', config('app.fints.registration-number', ''));

setlocale(LC_TIME, 'de_DE.UTF8', 'de_DE.UTF-8');

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
const UPLOAD_TARGET_DATABASE = false; // true|false store into
const UPLOAD_USE_DISK_CACHE = false;  // if DATABASE storage enabled , use filesystem as cache
const UPLOAD_MULTIFILE_BREAOK_ON_ERROR = true; // if there are multiple files on Upload and an error occures: FALSE -> upload files with no errors, TRUE upload no file
const UPLOAD_MAX_MULTIPLE_FILES = 1; // how many files can be uploaded at once
const UPLOAD_DISK_PATH = SYSBASE.'/public/files/get/filestorage'; // path to DATABASE filecache or FILESYSTEM storage - no '/' at the ends
const UPLOAD_MAX_SIZE = 41943215; // in bytes - also check DB BLOB max size and php upload size limit in php.ini
const UPLOAD_PROHIBITED_EXTENSIONS = 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden';
const UPLOAD_MOD_XSENDFILE = 1; // 0 - dont use it, 1 - auto detect on apache modules, 2 force usage - if detection fails

require_once SYSBASE.'/lib/inc.helper.php';
