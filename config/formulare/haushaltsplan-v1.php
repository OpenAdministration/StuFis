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
    [ "id" => "titel.$id.nummer",    "name" => "Titel",       "type" => "titelnr", "width" => 2, "editWidth" => 2, "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.name",      "name" => "Bezeichnung", "type" => "text",    "width" => ($year == date("Y") ? 4 : 6), "editWidth" => 6, "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.$id",       "name" => "$caption",    "type" => "money",   "width" => 2, "editWidth" => 4, "opts" => [ "required", "sum-over-table-bottom" ], "currency" => "€", "addToSum" => ["$id"] ],
  ];
  if ($year == date("Y")) {
    if ($id == "einnahmen") {
      $children[] =
        [ "id" => "titel.$id.projekt.ausgaben",   "name" => "erwartete Ausgaben",  "type" => "money",  "width" => 2,
          "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
          "printSumDefer" => "ausgaben.offen"
        ];
    } else {
      $children[] =
        [ "id" => "titel.$id.projekt.einnahmen",   "name" => "erwartete Einnahmen",  "type" => "money",  "width" => 2,
          "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
          "printSumDefer" => "einnahmen.offen"
        ];
    }
    $children[] =
      [ "id" => "titel.$id.rest",   "name" => "verbleibende $caption",  "type" => "money",  "width" => 2,
        "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
        "printSumDefer" => "expr: %$id - %$id.netto - %$id.offen",
      ];
  } else {
    $children[] =
      [ "id" => "titel.$id.zahlungen",   "name" => "getätigte $caption",  "type" => "money",  "width" => 2,
        "currency" => "€", "opts" => ["is-sum","sum-over-table-bottom","hide-edit"],
        "printSumDefer" => "$id.netto"
      ];
  }
  $invreftables = [];
  if ($year == date("Y")) {
    $invreftables[] =
      [ "id" => "titel.$id.invrefprojekt",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
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
    [ "id" => "titel.$id.invrefzahlungen",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
      "printSum" => [ "einnahmen", "ausgaben" ],
      "printSumWidth" => 2,
      "title" => "Getätigte oder genehmigte $caption",
      "otherForms" => [
        ["type" => "auslagenerstattung-genehmigung", "state" => "ok", "referenceFormField" => "haushaltsplan.otherForm",
         "addToSum" => [ "expr: %einnahmen - %ausgaben" => [ "einnahmen.netto" ],
                         "expr: %ausgaben - %einnahmen" => [ "ausgaben.netto" ] ],
        ],
        ["type" => "auslagenerstattung-genehmigung", "state" => "payed", "referenceFormField" => "haushaltsplan.otherForm",
         "addToSum" => [ "expr: %einnahmen - %ausgaben" => [ "einnahmen.netto" ],
                         "expr: %ausgaben - %einnahmen" => [ "ausgaben.netto" ] ],
        ],
        ["type" => "auslagenerstattung-genehmigung", "state" => "instructed", "referenceFormField" => "haushaltsplan.otherForm",
         "addToSum" => [ "expr: %einnahmen - %ausgaben" => [ "einnahmen.netto" ],
                         "expr: %ausgaben - %einnahmen" => [ "ausgaben.netto" ] ],
        ],
      ],
    ];
  $children[] = [
    "id" => "table.$id.invref", "type" => "group", "opts" => [ "well", "hide-edit", "hideableDuringRead" ], "width" => 12,
    "children" => $invreftables,
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
         "printSumFooter" => ["$id", "expr: %$id - %$id.netto - %$id.offen" ],
         "opts" => ["title"],
         "children" => [
           [ "id" => "gruppe.$id.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required", "title" ], "format" => "h4" ],
           [
             "type" => "table", /* renderer */
             "id" => "titel.$id",
             "opts" => ["with-headline","with-expand"],
             "width" => 12,
             "columns" => [
                [ "id" => "titel.$id.grp", "type" => "group", "opts" => ["title","sum-over-table-bottom"], "width" => 12,
                  "name" => true,
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
