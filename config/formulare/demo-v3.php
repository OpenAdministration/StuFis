<?php

$layout = [

 [
   "type" => "table", /* renderer */
   "id" => "finanzgruppentbl",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "rowCountField" => "numgrp",
   "columns" => [
     [ "id" => "anhang.beschreibung", "name" => "Beschreibung 1",             "type" => "text",   "width" => 3, "opts" => ["title"] ],
     [
       "type" => "multifile", /* renderer */
       "id" => "geld.anhang.3",
       "name" => "Anhänge #2",
       "width" => 9,
       "opts" => ["title"],
     ],
   ], // finanzgruppentbl
 ],

 [
   "type" => "table", /* renderer */
   "id" => "refanhaenge3",
   "width" => 12,
   "title" => "Verweise",
   "columns" => [
     [ "id" => "refanhang3",  "name" => "Anhänge",                            "type" => "ref",   "width" => 12, "references" => "finanzgruppentbl"],
   ],
 ],
];

$config = [];

/* formname , formrevision */
registerForm( "demo", "v3", $layout, $config );

