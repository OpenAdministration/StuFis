<?php
global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $URIBASE, $antrag, $STORAGE, $formid;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";

requireAuth();

$msgs = Array();
$ret = false;
$target = false;

if (!isset($_REQUEST["action"])) {
  die("Keine Aktion");
} else if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
 $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
} else {
 switch ($_REQUEST["action"]):
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
     header("Content-Type: text/plain; charset=UTF-8");
     break;
   default:
     die("Aktion nicht bekannt.");
 endswitch;
} /* switch */

$result = Array();
$result["msgs"] = $msgs;
$result["ret"] = ($ret !== false);
if ($target !== false)
  $result["target"] = $target;
$result["_REQUEST"] = $_REQUEST;
$result["_FILES"] = $_FILES;

header("Content-Type: text/json; charset=UTF-8");
echo json_encode($result);
exit;
