<?php

global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $attributes, $logoutUrl;

function getUserFullName() {
  global $attributes;
  requireAuth();
  return $attributes["displayName"][0];
}

function getUserMail() {
  global $attributes;
  requireAuth();
  return $attributes["mail"][0];
}

function requireAuth() {
  global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
  global $attributes, $logoutUrl;

  require_once($SIMPLESAML.'/lib/_autoload.php');
  $as = new SimpleSAML_Auth_Simple($SIMPLESAMLAUTHSOURCE);
  if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && !$as->isAuthenticated()) {
    header('HTTP/1.0 401 Unauthorized');
    die();
  }

  $as->requireAuth();

  $attributes = $as->getAttributes();
  $logoutUrl = $as->getLogoutURL();
}

function requireGroup($group) {
  global $attributes;

  requireAuth();

  if (!hasGroup($group)) {
    header('HTTP/1.0 401 Unauthorized');
    include SYSBASE."/template/permission-denied.tpl";
    die();
  }
}

function getUsername() {
  global $attributes;
  if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0])) 
    return $attributes["eduPersonPrincipalName"][0];
  if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
    return $attributes["mail"][0];
  return NULL;
}

function hasGroup($group) {
  global $attributes;

  if (count(array_intersect(explode(",",strtolower($group)), array_map("strtolower",$attributes["groups"]))) == 0) {
    return false;
  }

  return true;
}

