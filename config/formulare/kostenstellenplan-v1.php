<?php

for ($year = 2017; $year <= date("Y") + 1; $year++):

$config = [
  "revisionTitle" => $year,
  "caption" => $year,
  "permission" => [
    "isCreateable" => ($year == date("Y") || $year == date("Y")+1),
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Kostenstellenplan $year",
 ],

];

$children = [
  [ "id" => "kosten.nummer",    "name" => "Nummer",       "type" => "kostennr", "width" => 2, "opts" => [ "required", "title" ] ],
  [ "id" => "kosten.name",      "name" => "Bezeichnung", "type" => "text",    "width" => 10, "opts" => [ "required", "title" ] ],
];
if ($year == date("Y")) {
  $children[] =
    [ "id" => "kosten.invref0",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","skip-referencesId","hideableDuringRead"],
      "title" => "Genehmigte Projekte (offene Posten)",
      "printSum" => [ "expr: %einnahmen - %einnahmen.erstattet", "expr: %ausgaben - %ausgaben.erstattet" ],
      "printSumWidth" => 3,
      "otherForms" => [
        ["type" => "projekt-intern-genehmigung", "state" => "ok-by-stura", ],
        ["type" => "projekt-intern-genehmigung", "state" => "ok-by-hv", ],
        ["type" => "projekt-intern-genehmigung", "state" => "done-hv", ],
      ],
    ];
}
$children[] =
  [ "id" => "kosten.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
    "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
    "printSum" => [ "einnahmen", "ausgaben" ],
    "title" => "Getätigte oder genehmigte Einnahmen und Ausgaben",
    "otherForms" => [
      ["type" => "auslagenerstattung-genehmigung", "state" => "ok",    "referenceFormField" => "kostenstellenplan.otherForm", ],
      ["type" => "auslagenerstattung-genehmigung", "state" => "payed", "referenceFormField" => "kostenstellenplan.otherForm", ],
    ],
  ];

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
       "children" => [
         [ "id" => "gruppe.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required", "title" ] ],
         [
           "type" => "table", /* renderer */
           "id" => "kosten",
           "opts" => ["with-headline","with-expand"],
           "width" => 12,
           "columns" => [
              [ "id" => "kosten.grp", "type" => "group", "opts" => ["title"], "width" => 12,
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
registerForm( "kostenstellenplan", "$year", $layout, $config );

endfor;
