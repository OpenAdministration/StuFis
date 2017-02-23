<?php

for ($year = 2017; $year <= date("Y") + 1; $year++):

$config = [
  "revisionTitle" => $year,
  "permission" => [
    "isCreateable" => ($year == date("Y") || $year == date("Y")+1),
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
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
    [ "id" => "titel.$id.nummer",    "name" => "Titel",       "type" => "titelnr", "width" => 4, "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.name",      "name" => "Bezeichnung", "type" => "text",    "width" => 4, "opts" => [ "required", "title" ] ],
    [ "id" => "titel.$id.$id",       "name" => "$caption",    "type" => "money",   "width" => 4, "opts" => [ "required", "sum-over-table-bottom" ], "currency" => "€", "addToSum" => ["$id"] ],
  ];
  if ($year == date("Y")) {
    $children[] =
      [ "id" => "titel.$id.invref0",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
        "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","skip-referencesId"],
        "title" => "Genehmigte Projekte",
        "printSum" => [ "einnahmen", "ausgaben" ],
        "otherForms" => [
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-stura", ],
          ["type" => "projekt-intern-genehmigung", "state" => "ok-by-hv", ],
          ["type" => "projekt-intern-genehmigung", "state" => "done-hv", ],
        ],
      ];
  }
  $children[] =
    [ "id" => "titel.$id.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit"],
      "printSum" => [ "einnahmen", "ausgaben" ],
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
             "opts" => ["with-headline"],
             "width" => 12,
             "columns" => [
                [ "id" => "titel.$id.grp", "type" => "group", "opts" => ["title"], "width" => 12,
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
