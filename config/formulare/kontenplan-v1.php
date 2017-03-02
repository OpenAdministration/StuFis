<?php

for ($year = 2017; $year <= date("Y") + 1; $year++):

$config = [
  "revisionTitle" => $year,
  "caption" => $year,
  "permission" => [
    "isCreateable" => ($year == date("Y") || $year == date("Y")+1),
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
  "renderOptRead" => [ "no-form-compress" ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Kontenplan $year",
 ],

];

$children = [
  [ "id" => "konten.nummer",    "name" => "Nummer",       "type" => "kostennr", "width" => 2, "opts" => [ "required", "title" ] ],
  [ "id" => "konten.name",      "name" => "Bezeichnung", "type" => "text",    "width" => 6, "opts" => [ "required", "title" ] ],
];
$children[] =
  [ "id" => "konten.einnahmen",   "name" => "Einnahmen",  "type" => "money",  "width" => 2,
    "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
    "printSumDefer" => "einnahmen"
  ];
$children[] =
  [ "id" => "konten.ausgaben",   "name" => "Ausgaben",  "type" => "money",  "width" => 2,
    "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
    "printSumDefer" => "ausgaben"
  ];

$invreftables = [];
$invreftables[] =
  [ "id" => "konten.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
    "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
    "printSum" => [ "einnahmen", "ausgaben" ],
    "printSumWidth" => 2,
    "title" => "Getätigte oder genehmigte Einnahmen und Ausgaben",
    "otherForms" => [
      ["type" => "zahlung", "referenceFormField" => "kontenplan.otherForm",
       "addToSum" => [ "ausgaben" => [ "ausgaben" ], "einnahmen" => [ "einnahmen" ] ],
      ],
    ],
  ];
$children[] = [
  "id" => "konten.invref0.grp", "type" => "group", "opts" => ["well","hide-edit","hideableDuringRead"], "width" => 12,
  "children" => $invreftables,
];

$printSumFooter = [];
$printSumFooter[] = "einnahmen";
$printSumFooter[] = "ausgaben";

$layout[] =
 [
   "type" => "table", /* renderer */
   "id" => "gruppen",
   "opts" => ["with-row-number"],
   "width" => 12,
   "columns" => [
     [ "id" => "gruppe",
       "type" => "group",
       "opts" => ["title"],
       "printSumFooter" => $printSumFooter,
       "children" => [
         [ "id" => "gruppe.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required", "title" ], "format" => "h4" ],
         [
           "type" => "table", /* renderer */
           "id" => "kosten",
           "opts" => ["with-headline","with-expand"],
           "width" => 12,
           "columns" => [
              [ "id" => "konten.grp", "type" => "group", "opts" => ["title","sum-over-table-bottom"], "width" => 12,
                "name" => true,
                "children" => $children,
              ], // column
           ], // columns
         ], // table titel
       ], // children
     ], // column
   ], // columns
 ]; // table gruppen
 
/* formname , formrevision */
registerForm( "kontenplan", "$year", $layout, $config );

endfor;
