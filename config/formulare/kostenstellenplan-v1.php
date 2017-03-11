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
   "value" => "Kostenstellenplan $year",
 ],

];

$children = [
  [ "id" => "kosten.nummer",    "name" => "Nummer",       "type" => "kostennr", "width" => 2, "opts" => [ "required", "title" ] ],
  [ "id" => "kosten.name",      "name" => "Bezeichnung", "type" => "text",    "width" => ($year == date("Y") ? 6 : 8), "opts" => [ "required", "title" ] ],
];
if ($year == date("Y")) {
  $children[] =
    [ "id" => "kosten.einnahmen.offen",   "name" => "offene Einnahmen",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "einnahmen.offen"
    ];
  $children[] =
    [ "id" => "kosten.ausgaben.offen",   "name" => "offene Ausgaben",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "ausgaben.offen"
    ];
  $children[] =
    [ "id" => "kosten.einnahmen",   "name" => "getätigte Einnahmen",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "einnahmen.brutto"
    ];
  $children[] =
    [ "id" => "kosten.ausgaben",   "name" => "getätigte Ausgaben",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "ausgaben.brutto"
    ];
} else {
  $children[] =
    [ "id" => "kosten.einnahmen",   "name" => "Einnahmen",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "einnahmen.brutto"
    ];
  $children[] =
    [ "id" => "kosten.ausgaben",   "name" => "Ausgaben",  "type" => "money",  "width" => 1,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "ausgaben.brutto"
    ];
}

$invreftables = [];
if ($year == date("Y")) {
  $invreftables[] =
    [ "id" => "kosten.invref0",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","skip-referencesId","hideableDuringRead"],
      "title" => "Genehmigte Projekte (offene Posten)",
      "printSum" => [ "expr: %einnahmen - %einnahmen.erstattet", "expr: %ausgaben - %ausgaben.erstattet" ],
      "printSumWidth" => 2,
      "otherForms" => [
        ["type" => "projekt-intern", "state" => "ok-by-stura",
         "addToSum" => [ "expr: %einnahmen - %einnahmen.erstattet" => [ "einnahmen.offen" ] ,
                         "expr: %ausgaben - %ausgaben.erstattet" => [ "ausgaben.offen" ] ],
        ],
        ["type" => "projekt-intern", "state" => "ok-by-hv",
         "addToSum" => [ "expr: %einnahmen - %einnahmen.erstattet" => [ "einnahmen.offen" ] ,
                         "expr: %ausgaben - %ausgaben.erstattet" => [ "ausgaben.offen" ] ],
        ],
        ["type" => "projekt-intern", "state" => "done-hv",
         "addToSum" => [ "expr: %einnahmen - %einnahmen.erstattet" => [ "einnahmen.offen" ] ,
                         "expr: %ausgaben - %ausgaben.erstattet" => [ "ausgaben.offen" ] ],
        ],
      ],
    ];
}
$invreftables[] =
  [ "id" => "kosten.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
    "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
    "printSum" => [ "einnahmen", "ausgaben" ],
    "printSumWidth" => 2,
    "title" => "Getätigte oder genehmigte Einnahmen und Ausgaben",
    "otherForms" => [
      ["type" => "auslagenerstattung", "state" => "ok",    "referenceFormField" => "kostenstellenplan.otherForm",
       "addToSum" => [ "ausgaben" => [ "ausgaben.brutto" ], "einnahmen" => [ "einnahmen.brutto" ] ],
      ],
      ["type" => "auslagenerstattung", "state" => "payed", "referenceFormField" => "kostenstellenplan.otherForm",
       "addToSum" => [ "ausgaben" => [ "ausgaben.brutto" ], "einnahmen" => [ "einnahmen.brutto" ] ],
      ],
      ["type" => "auslagenerstattung", "state" => "instructed", "referenceFormField" => "kostenstellenplan.otherForm",
       "addToSum" => [ "ausgaben" => [ "ausgaben.brutto" ], "einnahmen" => [ "einnahmen.brutto" ] ],
      ],
    ],
  ];
$children[] = [
  "id" => "kosten.invref0.grp", "type" => "group", "opts" => ["well","hide-edit","hideableDuringRead"], "width" => 12,
  "children" => $invreftables,
];

$printSumFooter = [];
if ($year == date("Y")) {
  $printSumFooter[] = "einnahmen.offen";
  $printSumFooter[] = "ausgaben.offen";
}
$printSumFooter[] = "einnahmen.brutto";
$printSumFooter[] = "ausgaben.brutto";

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
              [ "id" => "kosten.grp", "type" => "group", "opts" => ["title","sum-over-table-bottom"], "width" => 12,
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
