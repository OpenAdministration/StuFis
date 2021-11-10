<?php

use framework\auth\AuthCasHandler;
use framework\auth\AuthSamlHandler;
use framework\DBConnector;
use framework\MailHandler;

const ORGANIZATION_NAME = 'Studierendenrat der XXX';
const ORGANIZATION_MAILDOMAIN = 'XXX.de';
const ORGANIZATION_IBAN = 'DEXXX';
const ORGANIZATION_BIC = 'XXX';
const BASE_TITLE = 'Finanztool';

const AUTH_HANDLER = AuthCasHandler::class;

$conf = [
    DBConnector::class => [
        'DB_DSN' => 'mysql:dbname=finanzen;host=localhost',
        'DB_USERNAME' => '<fillme>',
        'DB_PASSWORD' => '<fillme>',
        'DB_PREFIX' => '<prefix>__',
        /* dev option */
        'BUILD_DB' => true, // only first start
    ],
    AuthSamlHandler::class => [
        'SIMPLESAMLDIR' => dirname(__FILE__, 3) . '/simplesamlphp',
        'SIMPLESAMLAUTHSOURCE' => 'default-sp',
        'AUTHGROUP' => '<auth-group>',
        'ADMINGROUP' => 'finanz-admin',
    ],
    AuthCasHandler::class => [
        'HOST' => '',
        'PORT' => '',
        'PATH' => '',
        'CAS_VERSION' => '',
        'CERT_FILE' => '',
        'ADMINGROUP' => '',
        'AUTH_REALM' => '',
    ],
    MailHandler::class => [
        '_settings' => [
            'MAIL_PASSWORD' => 'def50200c981696af6c2092f8fd5fa3de49826a0b7898716ce5988bfdc3bdb4cd1cf8950be8c70fb7ad95186dcb3a483218aaaab017b20df8bccf4efb056db3049060554147444a9d114daf6e24aa1ec8d00899ff428425b3e01b0b09daa7c82ebb7a78e5ae50e0cdc7eabf0b255e0503374ac2bf4bd694ff9e8b9236addce4dba1a818164cfa97da0616d0187cf7fca3b510dfebef33d77249ae6aa8cf023ef9873654f63fa11ef5486655674066b59c9a84e7a3974e57cda2181fce571f80b5213109c76f3aa257c1fb75476211f0373549c4dbd808e69b53ec2e3c6bc534c24271688', // encrypted
            'SMTP_HOST' => 'smail.fem.tu-ilmenau.de',
            'SMTP_USER' => 'no-reply@stura.tu-ilmenau.de',
            'SMTP_SECURE' => 'tls', //tls|ssl' //tls = startls
            'SMTP_PORT' => '587',
            'MAIL_FROM' => 'no-reply@stura.tu-ilmenau.de',
            'MAIL_FROM_ALIAS' => 'Stura-Finanzen-Tool',
        ],
    ],
];

const URIBASE = '/FinanzAntragUI/';

const FINTS_REGNR = 'EB9C6F0B1E70EFA70CA809992';
const HIBISCUSGROUP = 'ref-finanzen';

define('BASE_URL', 'https://' . $_SERVER['SERVER_NAME']);
const GREMIUM_PREFIX = ['Studierendenrat', 'Fachschaftsrat', 'Referat', 'AG', 'Wahlkommission', 'KTS'];
$wikiUrl = 'https://formulardienst:iGeish4l@wiki.stura.tu-ilmenau.de'; ///lib/exe/xmlrpc.php
const FUI2PDF_APIKEY = 'mbObJfJn5mpzJbTsZ8BoJeatqmlsmdy911XipBWR9s3GQpiERH';
const FUI2PDF_URL = 'https://box.stura.tu-ilmenau.de/FUI2PDF2/public/index.php';
define('FUI2PDF_AUTH', base64_encode('cron:croncron'));

setlocale(LC_TIME, 'de_DE.UTF8', 'de_DE.UTF-8');

/* Development Options */

const DEV = true;
const SAML = false;
const MAIL_INSTALL = false; // test mail settings / encrypt password
const TG_ISSUE_LINK = 'https://t.me/joinchat/GQM8p1CX2CNPgtLghjaC8w';
const GIT_ISSUE_LINK = 'https://gitlab.tu-ilmenau.de/StuRA/FinanzAntragUI/issues/new';

const ACHIEVEMENT_ACTIVE = false;
const ACHIEVEMENT_RESTURL = 'https://helfer.stura.tu-ilmenau.de:1470/achievements/rest/1.0';
const ACHIEVEMENT_APPSECRET = 'MzpjUEpaT09JQUdOakJETzFCZ241cnBaZzJUTDQwN0xnVENSWDZ5UDZWa2F2OXFQSjJqM3lNWUZOUExIbzhqU3VMamc3Vks0a21OaklLaFRLY2VacGZudXp2M0xHOWl6dmJ1blBDcGYxa1hIWUFUWG5sZDJ1T0dHWEJzc01pYzdBdzowOnQ1MUx0WnZUcmZocEVzRGpWek0yU3VFUnN3NTBDVXNFbzJxbHBaVGc3YUdMd29wbUE3WndPRDIzMW5XSnZXdDRsOEtMYU5RRldLekhTdmdQeEVtZWlaZHdGTG5QZUVEVHV6ZmJnNm1OWktWSXMwVzhpb2k3T0xGdjhscE9JRTVV';
const ACHIEVEMENT_TESTUSER = 'lust4532';

if (!SAML) {
    $DEV_Attributes = [
        'gremien' => [
            'Referat Finanzen',
            'Referat IT',
        ],
        'groups' => [
//"ref-finanzen",
//"ref-finanzen-belege",
//"ref-finanzen-finanzen",
//"ref-finanzen-kv",
//"ref-finanzen-hv",
//"admin",
            'sgis',
        ],
        'mailinglists' => [
            'ref-it@tu-ilmenau.de',
//"ref-finanzen@tu-ilmenau.de",
//"ref-internationales@tu-ilmenau.de",
        ],
        'displayName' => [
            'Some Test User',
        ],
        'alle-gremien' => [
            'Referat Finanzen',
            'Referat IT',
            'Referat Internationales',
        ],
        'alle-mailinglists' => [
            'ref-it@tu-ilmenau.de',
            'ref-finanzen@tu-ilmenau.de',
            'ref-internationales@tu-ilmenau.de',
        ],
        'eduPersonPrincipalName' => [
            'lust4532',
        ],
        'mail' => 'lukas.staab@tu-ilmenau.de',
    ];
    define('DEV_ATTRIBUTES', $DEV_Attributes);
    ini_set('xdebug.var_display_max_depth', 5);
    ini_set('xdebug.var_display_max_children', 256);
    ini_set('xdebug.var_display_max_data', 500);
}

/*
 * set php error settings
 */
ini_set('display_errors', (DEV) ? 1 : 0);
ini_set('display_startup_errors', (DEV) ? 1 : 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', dirname(__FILE__, 2) . '/logs/error.log');

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

return $conf;
