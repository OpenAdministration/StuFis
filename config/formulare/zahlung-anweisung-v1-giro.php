<?php

$config = [
  "captionField" => [ "zahlung.datum" ],
  "revisionTitle" => "Version 20170302",
  "permission" => [
    "isCreateable" => true,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Zahlungsanweisung (Giro)",
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "title" => "Zahlung",
   "children" => [
     [ "id" => "zahlung.konto",            "title" => "Konto",                "type" => "ref",     "width" => 6, "opts" => ["required", "hasFeedback","edit-skip-referencesId"],
       "references" => [ [ "type" => "kontenplan", "revision" => date("Y"), "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ], [ "konten.giro" => "Konto" ] ],
       "referencesKey" => [ "konten.giro" => "konten.giro.nummer" ],
       "referencesId" => "kontenplan.otherForm",
     ],
     [ "id" => "zahlung.datum",            "title" => "Datum",               "type" => "date",     "width" => 6, "opts" => ["required"], ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "zahlung.table",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "columns" => [
     [
       "type" => "group", /* renderer */
       "width" => 12,
       "id" => "group2",
       "name" => true,
       "opts" => ["sum-over-table-bottom"],
       "children" => [
         [ "id" => "zahlung.beleg", "name" => "Beleg", "type" => "otherForm", "width" => 4 ],
         [ "id" => "zahlung.eref", "name" => "Ende-zu-Ende Referenz", "type" => "text", "width" => 4, "opts" => ["required"], ],
         [ "id" => "zahlung.einnahmen", "name" => "Einnahmen", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],   "addToSum" => ["einnahmen.beleg"]],
         [ "id" => "zahlung.ausgaben", "name" => "Ausgaben", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],   "addToSum" => ["ausgaben.beleg"] ],
         [ "id" => "zahlung.verwendungszweck", "title" => "Verwendungszweck",    "type" => "textarea", "width" => 12, "opts" => ["required"], ],
         [ "id" => "zahlung.empfname", "title" => "Empfänger (Name)", "type" => "text", "width" => 6, "opts" => ["required"], ],
         [ "id" => "zahlung.empfiban", "title" => "Empfänger (IBAN)", "type" => "iban", "width" => 6, "opts" => ["required"], ],
       ],
     ],
   ],
 ],

 [
   "type" => "textarea", /* renderer */
   "id" => "zahlung.vermerk",
   "title" => "Vermerk",
   "width" => 12,
   "min-rows" => 10,
 ],

];

/* formname , formrevision */
registerForm( "zahlung-anweisung", "v1-giro", $layout, $config );

