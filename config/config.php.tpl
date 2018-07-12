<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $URIBASE, $STORAGE, $ANTRAGMAILTO, $GremiumPrefix, $URIBASEREF, $wikiUrl, $CA_file, $DEV, $HIBISCUSGROUP, $HIBISCUSPASSWORD, $FUI2PDF_URL, $BUILD_DB;

$conf = [
    "HibiscusXMLRPCConnector" => [
        "HIBISCUS_URL" => "FIXME",
        "HIBISCUS_PASSWORD" => "FIXME",
        "HIBISCUS_USERNAME" => "FIXME",
    ],
    "DBConnector" => [
        "DB_DSN" => "mysql:dbname=FIXME;host=localhost",
        "DB_USERNAME" => "FIXME",
        "DB_PASSWORD" => "FIXME",
        "DB_PREFIX" => "finanzformular__",
        /* dev option */
        "BUILD_DB" => false,
    ],
    "AuthSamlHandler" => [
        "SIMPLESAMLDIR" => dirname(__FILE__,3) . "/simplesamlphp",
        "SIMPLESAMLAUTHSOURCE" => "FIXME",
        "AUTHGROUP" => "FIXME",
        "ADMINGROUP" => "FIXME",
    ],
	"AuthBasicHandler" => [
		"ADMINGROUP" => "",
		'BASICUSER' => [
			'FIXME' => [ 
				'password' => 'FIXME', 
				'displayName' => 'FIXME',
				'mail' => 'FIXME',
				'groups' => ['FIXME'],
				'eduPersonPrincipalName' => ['FIXME'],
			],
		]
	],
];

$HIBISCUSGROUP = "ref-finanzen"

$URIBASE = "/FIXME/";
define('URIBASE', $URIBASE);
define('BASE_TITLE', 'Finanztool');
$URIBASEREF = "https://" . $_SERVER["SERVER_NAME"];
define('BASE_URL', $URIBASEREF);
$STORAGE = dirname(dirname(__FILE__)) . "/storage";
$ANTRAGMAILTO = "FIXME";
define("MAILFROM", "ref-it@tu-ilmenau.de");
$GremiumPrefix = ["Studierendenrat","Fachschaftsrat", "Referat", "AG","Wahlkommission"];
$wikiUrl = "FIXME"; #/lib/exe/xmlrpc.php
$CA_file = dirname(__FILE__) . '/ca.pem';
$FUI2PDF_APIKEY = "FIXME";
$FUI2PDF_URL = "https://box.stura.tu-ilmenau.de/FIXME/index.php";
define("FUI2PDF_URL", $FUI2PDF_URL);
define("FUI2PDF_AUTH", base64_encode("FIXME:FIXME"));

/* Development Options */
$ANTRAGMAILTO = "FIXME@tu-ilmenau.de";
$DEV = false;
define('DEBUG', 0 + (($DEV)?1:0));
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($DEV){
    ini_set('xdebug.var_display_max_depth', 5);
    ini_set('xdebug.var_display_max_children', 256);
    ini_set('xdebug.var_display_max_data', 500);
}
/**
 * set php error settings
 */
ini_set('display_errors', ($DEV)? 1:0);
ini_set('display_startup_errors', ($DEV)? 1:0);
ini_set("log_errors", 1);
error_reporting(E_ALL);
ini_set("error_log", dirname(__FILE__, 2 )."/logs/error.log");

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
define('UPLOAD_TARGET_DATABASE', true); // true|false store into
define('UPLOAD_USE_DISK_CACHE', false);  // if DATABASE storage enabled , use filesystem as cache
define('UPLOAD_MULTIFILE_BREAOK_ON_ERROR', true); //if there are multiple files on Upload and an error occures: FALSE -> upload files with no errors, TRUE upload no file
define('UPLOAD_MAX_MULTIPLE_FILES', 1); // how many files can be uploaded at once
define('UPLOAD_DISK_PATH', dirname(__FILE__).'/public/files/get/filestorage'); // path to DATABASE filecache or FILESYSTEM storage - no '/' at the ends
define('UPLOAD_MAX_SIZE', 41943215); //in bytes - also check DB BLOB max size and php upload size limit in php.ini
define('UPLOAD_PROHIBITED_EXTENSIONS', 'ph.*?,cgi,pl,pm,exe,com,bat,pif,cmd,src,asp,aspx,js,lnk,html,htm,forbidden');
define('UPLOAD_MOD_XSENDFILE', 1); //0 - dont use it, 1 - auto detect on apache modules, 2 force usage - if detection fails

