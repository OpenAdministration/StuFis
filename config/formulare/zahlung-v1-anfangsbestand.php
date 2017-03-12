<?php

$config = [
  "caption" => "Anfangsbestand",
  "captionField" => [ "zahlung.datum" ],
  "revisionTitle" => "Kassen-Anfangsbestand (Version 20170302)",
  "permission" => [
    "isCreateable" => true,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
  "validate" => [
    "checkSum" => true,
  ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Kassen-Anfangsbestand",
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "title" => "Zahlung",
   "children" => [
     [ "id" => "zahlung.konto",            "title" => "Konto",                "type" => "ref",     "width" => 6, "opts" => ["required", "hasFeedback", "edit-skip-referencesId"],
       "references" => [ [ "type" => "kontenplan", "revision" => date("Y"), "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ], [ "konten.giro" => "Konto" ] ],
       "referencesKey" => [ "konten.giro" => "konten.giro.nummer" ],
       "referencesId" => "kontenplan.otherForm",
     ],
     [ "id" => "zahlung.datum",            "title" => "Datum",               "type" => "date",     "width" => 2, "opts" => ["required"], ],
     [ "id" => "zahlung.einnahmen",        "title" => "Einnahmen",           "type" => "money",    "width" => 2, "opts" => ["required"], "addToSum" => [ "einnahmen" ], "currency" => "€"],
   ],
 ],

];

/* formname , formrevision */
registerForm( "zahlung", "v1-anfangsbestand", $layout, $config );

