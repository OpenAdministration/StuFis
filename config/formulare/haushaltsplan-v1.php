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
   "value" => "Haushaltsplan $year",
 ],

];

foreach ( ["einnahmen" => "Einnahmen", "ausgaben" => "Ausgaben"] as $id => $caption) {

  $layout [] =
   [
     "type" => "h3", /* renderer */
     "id" => "head2",
     "value" => $caption,
   ];

  $children = [
    [ "id" => "titel.$id.nummer",    "name" => "Titel",       "type" => "titelnr", "width" => 2, "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.name",      "name" => "Bezeichnung", "type" => "text",    "width" => ($year == date("Y") ? 4 : 6), "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.$id",       "name" => "$caption",    "type" => "money",   "width" => 2, "opts" => [ "required", "sum-over-table-bottom" ], "currency" => "€", "addToSum" => ["$id"] ],
  ];
  if ($year == date("Y")) {
    $children[] =
      [ "id" => "titel.$id.invrefprojekt.einnahmen",   "name" => "beschlossene Einnahmen",  "type" => "invref",  "width" => 1,
        "opts" => ["with-headline","aggregate","hide-edit","skip-referencesId"],
        "printSum" => [ "expr: %einnahmen - %einnahmen.erstattet" ],
        "printSumLayout" => [ [ "type" => "money", "currency" => "€", "name" => "Einnahmen" ] ],
        "otherForms" => [
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-stura", ],
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-hv", ],
          ["type" => "projekt-intern-genehmigung", "state" => "done-hv", ],
        ],
      ];
    $children[] =
      [ "id" => "titel.$id.invrefprojekt.ausgaben",   "name" => "beschlossene Ausgaben",  "type" => "invref",  "width" => 1,
        "opts" => ["with-headline","aggregate","hide-edit","skip-referencesId"],
        "printSum" => [ "expr: %ausgaben - %ausgaben.erstattet" ],
        "printSumLayout" => [ [ "type" => "money", "currency" => "€", "name" => "Ausgaben" ] ],
        "otherForms" => [
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-stura", ],
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-hv", ],
          ["type" => "projekt-intern-genehmigung", "state" => "done-hv", ],
        ],
      ];
  }
  $children[] =
    [ "id" => "titel.$id.invrefzahlung",
      "name" => "getätigte $caption",
      "type" => "invref",
      "width" => 2,
      "opts" => ["with-headline","aggregate","hide-edit"],
      "printSum" => [ (($id == "einnahmen") ? "expr: %einnahmen - %ausgaben" : "expr: %ausgaben - %einnahmen" ) ],
      "printSumLayout" => [ [ "type" => "money", "currency" => "€", "name" => "$caption" ] ],
      "otherForms" => [
        ["type" => "auslagenerstattung-genehmigung", "state" => "ok", "referenceFormField" => "haushaltsplan.otherForm", ],
        ["type" => "auslagenerstattung-genehmigung", "state" => "payed", "referenceFormField" => "haushaltsplan.otherForm", ],
      ],
    ];
  if ($year == date("Y")) {
    $children[] =
      [ "id" => "titel.$id.invref0",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
        "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","skip-referencesId","hideableDuringRead"],
        "title" => "Genehmigte Projekte (offene Posten)",
        "printSum" => [ "expr: %einnahmen - %einnahmen.erstattet", "expr: %ausgaben - %ausgaben.erstattet" ],
        "printSumWidth" => 2,
        "otherForms" => [
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-stura", ],
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-hv", ],
          ["type" => "projekt-intern-genehmigung", "state" => "done-hv", ],
        ],
      ];
  }
  $children[] =
    [ "id" => "titel.$id.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
      "printSum" => [ "einnahmen", "ausgaben" ],
      "printSumWidth" => 2,
      "title" => "Getätigte oder genehmigte $caption",
      "otherForms" => [
        ["type" => "auslagenerstattung-genehmigung", "state" => "ok", "referenceFormField" => "haushaltsplan.otherForm", ],
        ["type" => "auslagenerstattung-genehmigung", "state" => "payed", "referenceFormField" => "haushaltsplan.otherForm", ],
      ],
    ];
  
  $layout[] =
   [
     "type" => "table", /* renderer */
     "id" => "gruppen.$id",
     "opts" => ["with-row-number"],
     "width" => 12,
     "columns" => [
       [ "id" => "gruppe.$id",
         "type" => "group",
         "printSumFooter" => ["$id"],
         "opts" => ["title"],
         "children" => [
           [ "id" => "gruppe.$id.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required", "title" ] ],
           [
             "type" => "table", /* renderer */
             "id" => "titel.$id",
             "opts" => ["with-headline","with-expand"],
             "width" => 12,
             "columns" => [
                [ "id" => "titel.$id.grp", "type" => "group", "opts" => ["title"], "width" => 12,
                  "name" => true,
                  "printSumFooter" => ["$id"],
                  "children" => $children,
                ], // column
             ], // columns
           ], // table titel
         ], // children
       ], // column
     ], // columns
   ]; // table gruppen
}; // foreach
 
/* formname , formrevision */
registerForm( "haushaltsplan", "$year", $layout, $config );

endfor;
