<?php

$config = [
  "captionField" => [ "zahlung.datum", "zahlung.empfaenger_name", "zahlung.art", "zahlung.verwendungszweck" ],
  "revisionTitle" => "Bank.Hibiscus (Version 20170302)",
  "permission" => [
    "isCreateable" => false,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
  "validate" => [
    "checkSum" => [
      [ "sum" => "expr: %ausgaben - %einnahmen + %einnahmen.beleg - %ausgaben.beleg",
        "maxValue" => 0.00,
        "minValue" => 0.00,
      ],
    ],
  ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Zahlung (bargeldlos)",
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "title" => "Zahlung",
   "children" => [
     [ "id" => "zahlung.konto",            "title" => "Konto",                "type" => "ref",     "width" => 3, "opts" => ["required", "hasFeedback","edit-skip-referencesId"],
       "references" => [ [ "type" => "kontenplan", "revision" => date("Y"), "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ], [ "konten.giro" => "Konto" ] ],
       "referencesKey" => [ "konten.giro" => "konten.giro.nummer" ],
       "referencesId" => "kontenplan.otherForm",
     ],
     [ "id" => "zahlung.datum",            "title" => "Datum",               "type" => "date",     "width" => 3, "opts" => ["required"], ],
     [ "id" => "zahlung.einnahmen",        "title" => "Einnahmen",           "type" => "money",    "width" => 3, "opts" => ["required"], "addToSum" => [ "einnahmen" ], "currency" => "€"],
     [ "id" => "zahlung.ausgaben",         "title" => "Ausgaben",            "type" => "money",    "width" => 3, "opts" => ["required"], "addToSum" => [ "ausgaben" ], "currency" => "€"],
     [ "id" => "zahlung.grp.1",                                              "type" => "group",     "width" => 6,
       "children" => [
         [ "id" => "zahlung.verwendungszweck", "title" => "Verwendungszweck",    "type" => "textarea", "width" => 12, "opts" => ["required"], ],
         [ "id" => "zahlung.hibiscus",         "title" => "Hibiscus",            "type" => "number",   "width" => 4, "opts" => ["readonly"], ],
         [ "id" => "zahlung.saldo",            "title" => "Hibiscus-Saldo",      "type" => "money",    "width" => 8, "opts" => ["readonly"], "currency" => "€"],
       ],
     ],
     [ "id" => "zahlung.grp.0",                                              "type" => "group",     "width" => 6,
       "children" => [
         [ "id" => "zahlung.art",              "title" => "Art",                 "type" => "text",     "width" => 12, ],
         [ "id" => "zahlung.empfaenger_name",  "title" => "Empfänger",           "type" => "text",     "width" => 12, ],
         [ "id" => "zahlung.empfaenger_konto", "title" => "Konto (Empfänger)",   "type" => "text",     "width" => 12, ],
         [ "id" => "zahlung.empfaenger_blz",   "title" => "BLZ (Empfänger)",     "type" => "text",     "width" => 12, ],
       ],
     ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "zahlung.grund.table",
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
         [ "id" => "zahlung.grund.beleg", "name" => "Beleg", "type" => "otherForm", "width" => 4 ],
         [ "id" => "zahlung.grund.hinweis", "name" => "Hinweis", "type" => "text", "width" => 4 ],
         [ "id" => "zahlung.grund.einnahmen", "name" => "Einnahmen", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],   "addToSum" => ["einnahmen.beleg"]],
         [ "id" => "zahlung.grund.ausgaben", "name" => "Ausgaben", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],   "addToSum" => ["ausgaben.beleg"] ],
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
registerForm( "zahlung", "v1-giro-hibiscus", $layout, $config );

