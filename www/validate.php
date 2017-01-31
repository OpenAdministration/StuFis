<?php
global $attributes, $logoutUrl, $AUTHGROUP, $nonce, $URIBASE, $antrag, $STORAGE;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";

requireGroup($AUTHGROUP);

$msgs = Array();
$ret = false;
$target = false;
$text = false;

if (!isset($_REQUEST["action"])) {
  die("Keine Aktion");
} else if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
 $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
} else {
 switch ($_REQUEST["action"]):
   case "text.otherForm":
     function checkOtherForm($value) {
      global $URIBASE;

      $otherAntrag = dbGet("antrag", ["id" => $value]);
      if ($otherAntrag === false) return false;
      $inhalt = dbFetchAll("inhalt", ["antrag_id" => $otherAntrag["id"]]);
      $otherAntrag["_inhalt"] = $inhalt;

      $otherForm = getForm($otherAntrag["type"], $otherAntrag["revision"]);
      $readPermitted = hasPermission($otherForm, $otherAntrag, "canRead");
      if (!$readPermitted)
        return false;

      $c = getAntragDisplayTitle($otherAntrag, $otherForm["config"]);
      $target = str_replace("//","/",$URIBASE."/").rawurlencode($otherAntrag["token"]);

      return "<a href=\"".htmlspecialchars($target)."\" target=\"_blank\">".implode(" ",$c)."</a>";
     }
     $text = checkOtherForm($_REQUEST["value"]);
     break;
   case "validate.otherForm":
     if (!isset($_REQUEST["formdata"]))
       $_REQUEST["formdata"] = [];
     function checkOtherForm($otherForms) {
       if (is_array($otherForms)) {
         $ret = true;
         foreach($otherForms as $otherForm) {
           $ret1 = checkOtherForm($otherForm);
           $ret = $ret && $ret1;
         }
         return $ret;
       }

      $otherAntrag = dbGet("antrag", ["id" => $otherForms]);
      if ($otherAntrag === false) return false;
      $inhalt = dbFetchAll("inhalt", ["antrag_id" => $otherAntrag["id"]]);
      $otherAntrag["_inhalt"] = $inhalt;

      $otherForm = getForm($otherAntrag["type"], $otherAntrag["revision"]);
      $readPermitted = hasPermission($otherForm, $otherAntrag, "canRead");
      if (!$readPermitted)
        return false;

       return true;
     }
     $ret = checkOtherForm($_REQUEST["formdata"]);
     if ($ret)
       header("HTTP/1.1 200 OK");
     else
       header("HTTP/1.1 400 Ungültige Formularnummer");
     exit;
   case "validate.email":
     if (!isset($_REQUEST["formdata"]))
       $_REQUEST["formdata"] = [];
     function checkMail($mails) {
       if (is_array($mails)) {
         $ret = true;
         foreach($mails as $mail) {
           $ret1 = checkMail($mail);
           $ret = $ret && $ret1;
         }
         return $ret;
       }
       return (bool) verify_mail($mails);
     }
     $ret = checkMail($_REQUEST["formdata"]);
     if ($ret)
       header("HTTP/1.1 200 OK");
     else
       header("HTTP/1.1 400 Ungültige eMail");
     exit;
   case "validate.wiki":
     if (!isset($_REQUEST["formdata"]))
       $_REQUEST["formdata"] = [];
     function checkWiki($wikis) {
       global $wikiUrl;
       if (is_array($wikis)) {
         $ret = true;
         foreach($wikis as $wiki) {
           $ret1 = checkWiki($wiki);
           $ret = $ret && $ret1;
         }
         return $ret;
       }
       if (parse_url($wikiUrl, PHP_URL_HOST) !== parse_url($wikis, PHP_URL_HOST))
         return false;
       if (NULL !== parse_url($wikis, PHP_URL_QUERY))
         return false;
       $wikiUrlPath = parse_url($wikiUrl, PHP_URL_PATH);
       $wikisPath = parse_url($wikis, PHP_URL_PATH);
       if ($wikisPath === NULL)
         return false; // no page given
       if ($wikiUrlPath !== NULL) {
         if (substr($wikisPath, 0, strlen($wikiUrlPath)) != $wikiUrlPath)
           return false; // bad prefix
         $wikiPage = substr($wikisPath, strlen($wikiUrlPath));
       } else {
         $wikiPage = $wikisPath;
       }
       $wikiPage = cleanID($wikiPage);
       return existsWikiPage($wikiPage,false);
     }
     $ret = checkWiki($_REQUEST["formdata"]);
     if ($ret)
       header("HTTP/1.1 200 OK");
     else
       header("HTTP/1.1 400 Wiki-Seite nicht gefunden");
     exit;
   case "propose.wiki":
     $currentNS = [""];
     $wikiPage = "";
     if (isset($_REQUEST["currentUrl"])) {
       $currentUrl = (string) $_REQUEST["currentUrl"];
       if (parse_url($wikiUrl, PHP_URL_HOST) == parse_url($currentUrl, PHP_URL_HOST)) {
         $wikiUrlPath = parse_url($wikiUrl, PHP_URL_PATH);
         $currentUrlPath = parse_url($currentUrl, PHP_URL_PATH);

         if ($currentUrlPath === NULL)
           $currentUrlPath = "/";
         if ($wikiUrlPath === NULL)
           $wikiUrlPath = "/";

         if (substr($currentUrlPath, 0, strlen($wikiUrlPath)) == $wikiUrlPath) {
           $wikiPage = substr($currentUrlPath, strlen($wikiUrlPath));
         } else {
           $wikiPage = "";
         }
       }
     }
     if (isset($_REQUEST["currentId"])) {
       $currentId = (string) $_REQUEST["currentId"];
       $wikiPage = trim($currentId, ":/");
     }
     $wikiPage = trim(str_replace("/",":",$wikiPage),":");
     $wikiPageNS = explode(":", $wikiPage);
     $ns = false;
     for ($i = 0; $i < count($wikiPageNS); $i++) {
       if ($wikiPageNS[$i] == "") continue;
       if ($ns === false)
         $ns = "";
       else
         $ns .= ":";
       $ns .= $wikiPageNS[$i];
       $currentNS[] = $ns;
     }
     $currentNS = array_unique($currentNS);
     $wikiPage = $ns;

     $prefix = parse_url($wikiUrl, PHP_URL_PATH);
     if ($prefix === NULL)
       $prefix = "";
     else
       $prefix = trim($prefix,"/")."/";

     $result = [];
     $result["delim"] = ":";
     $result["currentPage"] = $wikiPage;
     $result["tree"] = [];
     $extraDepth = 1;
     if (isset($_REQUEST["currentId"])) {
       $currentNS = array_slice($currentNS, -1);
     }
     for ($i = 0; $i < count($currentNS); $i += 1 + $extraDepth) {
       $ns = $currentNS[$i];
       $depth = ($ns == "" ? 0 : count(explode(":", $ns)))+$extraDepth+1;
       $p = ["id" => $ns, "extraDepth" => $extraDepth ];
       $result["tree"][] = $p; // put this first in the results so extraDepth can propagate in JavaScript

       $subnss = listWikiNS($ns, $depth, true);
       foreach($subnss as $subns) {
         $thisDepth = count(explode(":", $subns["id"]));
         if ($thisDepth < $depth)
           $subns["extraDepth"] = $depth - $thisDepth - 1;
         $result["tree"][] = $subns;
       }

       $pages = listWikiPages($ns, $depth, true);
       foreach($pages as $page) {
         $pageid = $page["id"];
         $pageurl = $prefix.str_replace(":","/",$pageid);
         $page["url"] = parse_url($wikiUrl, PHP_URL_SCHEME)."://".parse_url($wikiUrl, PHP_URL_HOST)."/".$pageurl;
         $result["tree"][] = $page;
       }
     }
     header("Content-Type: text/json; charset=UTF-8");
     echo json_encode($result);
     exit;
   default:
     die("Aktion nicht bekannt.");
 endswitch;
} /* switch */

$result = Array();
$result["msgs"] = $msgs;
$result["ret"] = ($ret !== false);
if ($target !== false)
  $result["target"] = $target;
$result["text"] = $text;

header("Content-Type: text/json; charset=UTF-8");
echo json_encode($result);
exit;
