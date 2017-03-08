<?php

function verify_mail($email) {
  if (!preg_match('/^([a-z0-9_\\-\\.]+)@([a-z0-9_\\-\\.]+)\\.([a-z]{2,5})$/i',$email)) {
    return false;
  }
  $SMTP_Validator = new SMTP_validateEmail();
  $results = $SMTP_Validator->validate(array($email), MAILFROM);
  return $results[$email];
}

function notifyStateTransition($antrag, $newState, $newStateCreator, $action) {
  notifyStateTransitionTG($antrag, $newState, $newStateCreator, $action);
  notifyStateTransitionMail($antrag, $newState, $newStateCreator, $action);
}

function notifyStateTransitionTG($antrag, $newState, $newStateCreator, $action) {
  global $URIBASE, $URIBASEREF;

  $src = "../telegrambot/mysupersecretpathforwebhook/inc.php";
  if (!file_exists($src)) return;

  include "$src";
  $url = trim($URIBASEREF,"/").str_replace("//","/","/".$URIBASE."/").rawurlencode($antrag["token"]);
  $revConfig = getFormConfig($antrag["type"], $antrag["revision"]);
  $caption = getAntragDisplayTitle($antrag, $revConfig);
  $antragtitle = preg_replace('/\s+/', ' ', strip_tags(implode(" ", $caption)));
  $classConfig = getFormClass($antrag["type"]);
  if (isset($classConfig["title"]))
    $classTitle = "{$classConfig["title"]}";
  $msg = $classTitle . "\n" . $antragtitle . "\n Neuer Status: " . $classConfig["state"][$newState][0] . "\n von: " . $newStateCreator . "\n" . $url;
  sendToAllTGUser($msg);
}

function notifyStateTransitionMail($antrag, $newState, $newStateCreator, $action) {
  global $URIBASE, $URIBASEREF, $ANTRAGMAILTO, $mail_object, $STORAGE;

  $attachAnhang = (bool) $action["attachForm"];

  $classConfig = getFormClass($antrag["type"]);
  $revConfig = getFormConfig($antrag["type"], $antrag["revision"]);

  $citeText = "";
  if (isset($revConfig["citeFieldsInMailIfNotEmpty"])) {
    foreach($revConfig["citeFieldsInMailIfNotEmpty"] as $fieldName => $label) {
      $value = getFormValueInt($fieldName, null, $antrag["_inhalt"], "");
      if ($value == "") continue;
      $citeText .= "$label: $value\n\n";
    }
  }

  if (!isset($revConfig["mailTo"])) {
    $mailTo = "ref-it@tu-ilmenau.de";
    $subject = "[KEINE MAILTO ANGABE] $subject";
  } else {
    $mailTo = $revConfig["mailTo"];
  }

  $to = [];
  foreach ($mailTo as $mail) {
    if (substr($mail,0,7) == "mailto:") {
      $to[] = substr($mail,7);
    } elseif (substr($mail,0,6) == "field:") {
      $fieldName = substr($mail,6);
      $value = getFormValueInt($fieldName, null, $antrag["_inhalt"], null);
      if ($value === null) continue;
      $to[] = $value;
    }
  }
  if (count($to) == 0) return;
  $to = array_unique($to);

  $antragurl = trim($URIBASEREF,"/").str_replace("//","/","/".$URIBASE."/").rawurlencode($antrag["token"]);
  $caption = getAntragDisplayTitle($antrag, $revConfig);
  $antragtitle = preg_replace('/\s+/', ' ', strip_tags(implode(" ", $caption)));

  if (isset($classConfig["title"]))
    $classTitle = "{$classConfig["title"]}";

  #$revTitle = "{$antrag["revision"]}";
  #if (isset($revConfig["revisionTitle"]))
  #  $revTitle = "[{$antrag["revision"]}] {$revConfig["revisionTitle"]}";

  #$subject = "Information zu {$antragtitle} ({$classTitle} - {$revTitle})";
  $subject = "Information zu {$antragtitle} ({$classTitle})";

  $newStateTxt = $classConfig["state"][$newState][0];

  $txt = "";
  $txt .= "Hallo,\n";
  $txt .= "\n";
  $txt .= "der Antrag\n";
  $txt .= "  {$classTitle}\n";
  $txt .= "  {$antragtitle}\n";
  $txt .= "wurde in den Bearbeitungsstatus \"{$newStateTxt}\" verschoben.\n";
  $txt .= "\n";
  $txt .= $citeText;
  $txt .= "Mit freundlichen Grüßen,\n";
  $txt .= "Referat Finanzen\n";
  $txt .= "\n";
  $txt .= "{$antragurl}";

  $boundary = strtoupper(md5(uniqid(time())));
  $message = "This is a multi-part message in MIME format -- Dies ist eine mehrteilige Nachricht im MIME-Format" . "\r\n" .
             "--$boundary" ."\r\n" .
             "Content-type: text/plain; charset=UTF-8" . "\r\n" .
             "Content-Transfer-Encoding: base64" . "\r\n".
             "\r\n" .
              chunk_split(base64_encode($txt)) .
             "\r\n" .
             "\r\n" .
             "--$boundary";

  if ($attachAnhang) {
    $antrag["state"] = $newState;
    $antrag["stateCreator"] = $newStateCreator;
    $antraghtml = antrag2html($antrag);
    $message .= "\r\n" .
               "Content-Type: text/html; name=\"antrag{$antrag["id"]}.html\"" ."\r\n" .
               "Content-Transfer-Encoding: base64" . "\r\n" .
               "Content-Disposition: attachment; filename=\"antrag{$antrag["id"]}.html\"\r\n" .
               "\r\n" .
               chunk_split(base64_encode($antraghtml)) .
               "\r\n" .
               "\r\n" .
               "--$boundary";

    foreach ($antrag["_anhang"] as $ah) {
      if (strtolower($ah["state"]) != "active") continue;
      $fileName = $ah["antrag_id"]."-".$ah["id"].".".str_replace(["[","]",'"'], ["-","",""], $ah["fieldname"]).".".pathinfo($ah["filename"],PATHINFO_BASENAME);
      $message .= "\r\n" .
               "Content-Type: ".$ah["mimetype"]."; name=\"".$fileName."\"\r\n" .
               "Content-Transfer-Encoding: base64" . "\r\n" .
               "Content-Disposition: attachment; filename=\"".$fileName."\"\r\n" .
               "\r\n" .
               chunk_split(base64_encode(file_get_contents($STORAGE."/".$ah["antrag_id"]."/".$ah["path"]))) .
               "\r\n" .
               "\r\n" .
               "--$boundary";
    }
  }

  $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
  $header = Array(
    "From"                      => $ANTRAGMAILTO,
    "Reply-To"                  => $ANTRAGMAILTO,
    "X-Mailer"                  => $URIBASEREF.$URIBASE,
    "To"                        => join(", ", $to),
    "Subject"                   => $subject,
    "MIME-Version"              => "1.0",
    "Content-Type"              => "multipart/mixed; boundary=$boundary; charset=UTF-8"
   );

  if (isset($ANTRAGMAILTO)) {
    add_message("eMail an $ANTRAGMAILTO umgeleitet");
    $to = [ $ANTRAGMAILTO ];
  }
  return (true === $mail_object->send($to, $header, $message));
}

function antrag2html($antrag) {
  global $URIBASEREF, $URIBASE;
  global $inlineCSS;
  $oldInlineCSS = $inlineCSS;
  $inlineCSS = true;
  $form = getForm($antrag["type"],$antrag["revision"]);
  ob_start();
  require "../template/header-print.tpl";
  require "../template/antrag.head.tpl";
  require "../template/antrag.tpl";
  require "../template/antrag.foot-print.tpl";
  require "../template/footer-print.tpl";
  $antraghtml = ob_get_contents();
  ob_end_clean();
  $inlineCSS = $oldInlineCSS;
  return $antraghtml;
}

