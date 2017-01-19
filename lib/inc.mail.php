<?php

function verify_mail($email) {
  if (!preg_match('/^([a-z0-9_\\-\\.]+)@([a-z0-9_\\-\\.]+)\\.([a-z]{2,5})$/i',$email)) {
    return false;
  }
  $SMTP_Validator = new SMTP_validateEmail();
  $results = $SMTP_Validator->validate(array($email), MAILFROM);
  return $results[$email];
}

function sendAntrag($antrag, $ahs, $msg) {
  global $ANTRAGMAILTO, $mail_object;

  $antragtxt = "";
  foreach ($antrag as $k => $v)
    $antragtxt .= "$k = $v\n";

  $antragtxt .= "\n\nNachweise:\n";
  foreach ($ahs as $ah) {
    if (strtolower($ah["state"]) != "active") continue;
    $antragtxt .= $ah["id"]." ".$ah["type"]."\n";
    if ($ah["description"] == "") continue;
    $antragtxt .= " Beschreibung:\n";
    foreach (explode("\n",$ah["description"]) as $dline)
      $antragtxt .= "  ".trim($dline)."\n";
  }

  $boundary = strtoupper(md5(uniqid(time())));
  $message = "This is a multi-part message in MIME format -- Dies ist eine mehrteilige Nachricht im MIME-Format" . "\r\n" .
             "--$boundary" ."\r\n" .
             "Content-type: text/plain; charset=UTF-8" . "\r\n" .
             "Content-Transfer-Encoding: base64" . "\r\n".
             "\r\n" .
              chunk_split(base64_encode($msg)) .
             "\r\n" .
             "\r\n" .
             "--$boundary" . "\r\n" .
             "Content-Type: text/plain; name=\"antrag{$antrag["id"]}.txt\"" ."\r\n" .
             "Content-Transfer-Encoding: base64" . "\r\n" .
             "\r\n" .
             chunk_split(base64_encode($antragtxt)) .
             "\r\n" .
             "\r\n" .
             "--$boundary";

  foreach ($ahs as $ah) {
    if (strtolower($ah["state"]) != "active") continue;
    $message .= "\r\n" .
             "Content-Type: ".$ah["mimetype"]."; name=\"".$ah["id"]."-".$ah["type"].".".pathinfo($ah["name"],PATHINFO_EXTENSION)."\"\r\n" .
             "Content-Transfer-Encoding: base64" . "\r\n" .
             "Content-Disposition: attachment; filename=\"".$ah["id"]."-".$ah["type"].".".pathinfo($ah["name"],PATHINFO_EXTENSION)."\"\r\n" .
             "\r\n" .
             chunk_split(base64_encode(file_get_contents($ah["path"]))) .
             "\r\n" .
             "\r\n" .
             "--$boundary";
  }

  $to = [ $antrag["email"], $ANTRAGMAILTO ];
  $subject = "Dein Antrag auf Erstattung des Semesterbeitrages der Studierendenschaft";

  $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
  $header = Array(
    "From"                      => $ANTRAGMAILTO,
    "Reply-To"                  => $ANTRAGMAILTO,
    "X-Mailer"                  => "helfer.stura.tu-ilmenau.de/erstattung-semesterbeitrag",
    "To"                        => join(", ", $to),
    "Subject"                   => $subject,
    "MIME-Version"              => "1.0",
    "Content-Type"              => "multipart/mixed; boundary=$boundary; charset=UTF-8"
   );
  return (true === $mail_object->send($to, $header, $message));
}

