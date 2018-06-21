<?php

/**
 * Remove unwanted chars from ID
 *
 * Cleans a given ID to only use allowed characters. Accented characters are
 * converted to unaccented ones
 *
 */
function cleanID($raw_id){
    $id = trim((string)$raw_id);
    $id = strtolower($id);

    $id = strtr($id,';/','::');

    //clean up
    $id = preg_replace('#:+#',':',$id);
    $id = trim($id,':._-');
    $id = preg_replace('#:[:\._\-]+#',':',$id);
    $id = preg_replace('#[:\._\-]+:#',':',$id);

    return($id);
}

function getClient() {
  global $wikiUrl, $CA_file;
  static $wikiClient;
  if (!$wikiClient) {
    $request = new HTTP_Request2_SNI();
    $request->setConfig("ssl_cafile", $CA_file);
    $wikiClient = XML_RPC2_Client::create($wikiUrl."/lib/exe/xmlrpc.php", Array("httpRequest" => $request, "backend" => "php"));
  }
  return $wikiClient;
}

function listWikiNS($wiki, $depth = 0, $autoDie = true) {
  try {
    $wikiClient = getClient();
    $method="plugin.remotelistnamespace.listNamespace";
    return $wikiClient->$method($wiki, [ "depth" => $depth ]);
  } catch (XML_RPC2_FaultException $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
    return false;
  } catch (Exception $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing '.$wiki.': ' . $e->getMessage() );
    return false;
  }
}

function listWikiPages($wiki, $depth = 0, $autoDie = true) {
  try {
    $wikiClient = getClient();
    $method="dokuwiki.getPagelist";
    return $wikiClient->$method($wiki, [ "depth" => $depth ]);
  } catch (XML_RPC2_FaultException $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
    return false;
  } catch (Exception $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing '.$wiki.': ' . $e->getMessage() );
    return false;
  }
}

function getAllWikiPage($autoDie = true) {
  try {
    $wikiClient = getClient();
    $method="wiki.getAllPages";
    return $wikiClient->$method();
  } catch (XML_RPC2_FaultException $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing all pages #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
    return false;
  } catch (Exception $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception listing all pages: ' . $e->getMessage() );
    return false;
  }
}

function existsWikiPage($wiki, $autoDie = true) {
  try {
    $wikiClient = getClient();
    # getPage returns template for new page if page does not exist -> check existance before
#    $method="wiki.getPageVersions";
#    $rev = $wikiClient->$method($wiki);
#    $numRev = count($rev);
#    $doesExist = ($numRev > 0);
    $method = "wiki.getPageInfo";
    $ret = $wikiClient->$method($wiki);
    $doesExist = true; # XML_RPC2 Exception will catch this
     return $doesExist;
  } catch (XML_RPC2_FaultException $e) {
    if ($e->getFaultCode() == 121)
      return false;
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception reading '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
    return false;
  } catch (Exception $e) {
    if ($autoDie)
      die(__LINE__."@".__FILE__.': Exception reading '.$wiki.': ' . $e->getMessage() );
    return false;
  }
}

function fetchWikiPage($wiki) {
  try {
    $wikiClient = getClient();
    # getPage returns template for new page if page does not exist -> check existance before
    if (!existsWikiPage($wiki)) return "";
    $method="wiki.getPage";
    return $wikiClient->$method($wiki);
  } catch (XML_RPC2_FaultException $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.': ' . $e->getMessage() );
  }
}

function writeWikiPage($wiki, $text) {
  try {
    $wikiClient = getClient();
    $method="wiki.putPage";
    return $wikiClient->$method($wiki, $text, Array());
  } catch (XML_RPC2_FaultException $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.' #' . $e->getFaultCode() . ' : ' . $e->getFaultString());
  } catch (Exception $e) {
    die(__LINE__."@".__FILE__.': Exception reading '.$wiki.': ' . $e->getMessage() );
  }
}

