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

foreach ( [ "giro" => "Bankkonten", "bar" => "Bargeldkonten" ] as $id => $caption) {
  $layout[] =  [
     "type" => "h3", /* renderer */
     "id" => "head1",
     "value" => "$caption",
  ];
  
  $prefix = "";
  if ($id == "giro")
    $prefix = "01 ";
  if ($id == "bar")
    $prefix = "02 ";

  $children = [
    [ "id" => "konten.$id.nummer",    "name" => "Nummer",      "type" => "kontennr", "width" => 2, "opts" => [ "required", "title" ], "pattern-from-prefix" => $prefix, "placeholder" => "$prefix" ],
    [ "id" => "konten.$id.name",      "name" => "Bezeichnung", "type" => "text",    "width" => 6, "opts" => [ "required", "title" ] ],
  ];
  $children[] =
    [ "id" => "konten.$id.einnahmen",   "name" => "Einnahmen",  "type" => "money",  "width" => 2,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "einnahmen"
    ];
  $children[] =
    [ "id" => "konten.$id.ausgaben",   "name" => "Ausgaben",  "type" => "money",  "width" => 2,
      "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
      "printSumDefer" => "ausgaben"
    ];
  
  $invreftables = [];
  $invreftables[] =
    [ "id" => "konten.$id.invref1",   "name" => "Verwendung",  "type" => "invref",  "width" => 12,
      "opts" => ["with-headline","aggregate-by-otherForm","hide-edit","hideableDuringRead"],
      "printSum" => [ "einnahmen", "ausgaben", "expr:%einnahmen - %ausgaben" ],
      "printSumSaldo" => [ "expr:%einnahmen - %ausgaben" ],
      "printSumLayout" => [ "expr:%einnahmen - %ausgaben" => [ "type" => "money", "name" => "Saldo", "currency" => "€" ] ],
      "printSumWidth" => 2,
      "orderBy" => [ "field:zahlung.datum", "id" ],
      "title" => "Getätigte oder genehmigte Einnahmen und Ausgaben",
      "otherForms" => [
        ["type" => "zahlung", "referenceFormField" => "kontenplan.otherForm",
         "addToSum" => [ "ausgaben" => [ "ausgaben" ], "einnahmen" => [ "einnahmen" ] ],
        ],
      ],
    ];
  $children[] = [
    "id" => "konten.$id.invref0.grp", "type" => "group", "opts" => ["well","hide-edit","hideableDuringRead"], "width" => 12,
    "children" => $invreftables,
  ];
  
  $printSumFooter = [];
  $printSumFooter[] = "einnahmen";
  $printSumFooter[] = "ausgaben";
  
  $layout[] =
    [
      "type" => "table", /* renderer */
      "id" => "konten.$id",
      "opts" => ["with-headline","with-expand"],
      "width" => 12,
      "columns" => [
         [ "id" => "konten.$id.grp", "type" => "group", "opts" => ["title","sum-over-table-bottom"], "width" => 12,
           "name" => true,
           "children" => $children,
         ], // column
      ], // columns
    ]; // table titel
}
   
/* formname , formrevision */
registerForm( "kontenplan", "$year", $layout, $config );
  
endfor;
