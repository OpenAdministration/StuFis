<?php

$config = [
  "caption" => [ "Anfangsbestand" ],
  "revisionTitle" => "Kassen-Anfangsbestand (Version 20170302)",
  "permission" => [
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
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
     [ "id" => "zahlung.konto",            "title" => "Konto",                "type" => "ref",     "width" => 6, "opts" => ["required", "hasFeedback"],
       "references" => [ [ "type" => "kontenplan", "revision" => date("Y"), "state" => "final" ], [ "konten.giro" => "Bankkonten", "konten.bar" => "Bargeldkassen" ] ],
       "referencesKey" => [ "konten.bar" => "konten.bar.nummer", "konten.giro" => "konten.giro.nummer" ],
       "referencesId" => "kontenplan.otherForm",
     ],
     [ "id" => "zahlung.datum",            "title" => "Datum",               "type" => "date",     "width" => 2, "opts" => ["required"], ],
     [ "id" => "zahlung.einnahmen",        "title" => "Einnahmen",           "type" => "money",    "width" => 2, "opts" => ["required"], "addToSum" => [ "einnahmen" ], "currency" => "€"],
   ],
 ],

];

/* formname , formrevision */
registerForm( "zahlung", "v1-anfangsbestand", $layout, $config );

