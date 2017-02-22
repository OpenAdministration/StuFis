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

 [
   "type" => "h3", /* renderer */
   "id" => "head2",
   "value" => "Einnahmen",
 ],

 [
   "type" => "table", /* renderer */
   "id" => "gruppen.einnahmen",
   "opts" => ["with-row-number"],
   "width" => 12,
   "columns" => [
     [ "id" => "gruppe.einnahmen",
       "type" => "group",
       "printSumFooter" => ["einnahmen"],
       "children" => [
         [ "id" => "gruppe.einnahmen.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required" ] ],
         [
           "type" => "table", /* renderer */
           "id" => "titel.einnahmen",
           "opts" => ["with-headline"],
           "width" => 12,
           "columns" => [
              [ "id" => "titel.einnahmen.nummer",    "name" => "Titel",       "type" => "titelnr",   "opts" => [ "required" ], "width" => 4 ],
              [ "id" => "titel.einnahmen.name",      "name" => "Bezeichnung", "type" => "text",   "opts" => [ "required" ], "width" => 4 ],
              [ "id" => "titel.einnahmen.einnahmen", "name" => "Einnahmen",   "type" => "money",  "opts" => [ "required", "sum-over-table-bottom" ], "width" => 4, "currency" => "€", "addToSum" => ["einnahmen"] ],
           ],
         ], // table titel
       ], // children
     ], // column
   ], // columns
 ], // table gruppen

 [
   "type" => "h3", /* renderer */
   "id" => "head2",
   "value" => "Ausgaben",
 ],

 [
   "type" => "table", /* renderer */
   "id" => "gruppen.ausgaben",
   "opts" => ["with-row-number"],
   "width" => 12,
   "columns" => [
     [ "id" => "gruppe.ausgaben",
       "type" => "group",
       "printSumFooter" => ["ausgaben"],
       "children" => [
         [ "id" => "gruppe.ausgaben.name",   "name" => "Gruppe",                 "type" => "text", "width" => 12,      "opts" => [ "required" ] ],
         [
           "type" => "table", /* renderer */
           "id" => "titel.ausgaben",
           "opts" => ["with-headline"],
           "width" => 12,
           "columns" => [
              [ "id" => "titel.ausgaben.nummer",    "name" => "Titel",       "type" => "titelnr",   "opts" => [ "required" ], "width" => 4 ],
              [ "id" => "titel.ausgaben.name",      "name" => "Bezeichnung", "type" => "text",   "opts" => [ "required" ], "width" => 4 ],
              [ "id" => "titel.ausgaben.ausgaben",  "name" => "Ausgaben",    "type" => "money",  "opts" => [ "required", "sum-over-table-bottom" ], "width" => 4, "currency" => "€" , "addToSum" => ["ausgaben"]],
           ],
         ], // table titel
       ], // children
     ], // column
   ], // columns
 ], // table gruppen

];

/* formname , formrevision */
registerForm( "haushaltsplan", "$year", $layout, $config );

endfor;
