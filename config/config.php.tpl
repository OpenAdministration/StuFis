<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $URIBASE, $STORAGE, $ANTRAGMAILTO, $GremiumPrefix, $URIBASEREF, $wikiUrl, $CA_file, $DEV, $HIBISCUSGROUP, $HIBISCUSPASSWORD;

$DB_DSN = "FIXME";
$DB_USERNAME = "FIXME";
$DB_PASSWORD = "FIXME";
$DB_PREFIX = "finanzformular__";
$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "FIXME";
# permissions required by index_old.php
$AUTHGROUP = "FIXME";
# permission required for hibiscus import
$HIBISCUSGROUP = "FIXME";
# admin groups (comma separated)
$ADMINGROUP = "FIXME";
$HIBISCUSPASSWORD = "FIXME";
$URIBASE = "/FinanzAntragUI/";
$URIBASEREF = "https://".$_SERVER["SERVER_NAME"];
$STORAGE = dirname(dirname(__FILE__))."/storage";
$ANTRAGMAILTO = "FIXME@tu-ilmenau.de";
$GremiumPrefix = [];
define("MAILFROM", "ref-it@tu-ilmenau.");
$wikiUrl = "https://FIXME:FIXME@wiki.stura.tu-ilmenau.de"; #/lib/exe/xmlrpc.php
$CA_file = dirname(__FILE__).'/ca.pem';
$DEV = false;

// :vim:set syntax=php:
