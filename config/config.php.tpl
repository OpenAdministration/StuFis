<?php

global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $AUTHGROUP, $ADMINGROUP, $URIBASE, $STORAGE, $ANTRAGMAILTO, $GremiumPrefix, $URIBASEREF;

$DB_DSN = "FIXME";
$DB_USERNAME = "FIXME";
$DB_PASSWORD = "FIXME";
$DB_PREFIX = "finanzformular__";
$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "FIXME";
# permissions required by index.php
$AUTHGROUP = "FIXME";
# admin groups (comma separated)
$ADMINGROUP = "FIXME";
$URIBASE = "/FinanzAntragUI/";
$URIBASEREF = "https://".$_SERVER["SERVER_NAME"];
$STORAGE = dirname(dirname(__FILE__))."/storage";
$ANTRAGMAILTO = "FIXME@tu-ilmenau.de";
$GremiumPrefix = [];
define("MAILFROM", "ref-it@tu-ilmenau.");

// :vim:set syntax=php:
