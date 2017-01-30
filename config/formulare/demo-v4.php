<?php

$config = [
  "title" => "DEMO: Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
  "caption-field" => [ "projekt.name", "projekt.zeitraum" ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => $config["title"],
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "children" => [
     [ "id" => "projekt.name",        "title" =>"Projektname",                        "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
     [ "id" => "projekt.org",         "title" =>"Projekt von",                        "type" => "select", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.leitung",     "title" =>"Projektverantwortlich (eMail)",      "type" => "email",  "width" =>  6, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["required","hasFeedback"], "pattern" => "^https:\/\/wiki\.stura\.tu-ilmenau\.de\/protokoll\/.*", "pattern-error" => "Muss mit \"https://wiki.stura.tu-ilmenau.de/protokoll/\" beginnen." ],
#     [ "id" => "start",       "title" =>"Projektbeginn",                      "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-after" => "field:ende" ],
#     [ "id" => "ende",        "title" =>"Projektende",                        "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-before" => "field:start" ],
     [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer",                       "type" => "daterange", "width" => 12,  "opts" => ["not-before-creation", "required"] ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzgruppentbl",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "rowCountField" => "numgrp",
   "columns" => [
     [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 2, ],
     [ "id" => "geld.einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [
       "type" => "group", /* renderer */
       "name" => "Nachweise",
       "width" => 6,
       "id" => "group2",
       "children" => [
         [
           "type" => "table", /* renderer */
           "id" => "finanzgruppentblanlagen",
           "width" => 12,
           "name" => "Nachweise",
           "columns" => [
             [ "id" => "geld.anhang.file",    "name" => "Anhänge",             "type" => "ref",   "width" => 6, "references" => "anhaenge"],
             [ "id" => "geld.anhang.comment", "name" => "Hinweis",             "type" => "text",   "width" => 6, "opts" => ["title"] ],
           ],
         ],
         [
           "type" => "multifile", /* renderer */
           "id" => "upload",
           "title" => "Anhänge hochladen",
           "width" => 12,
           "destination" => "anhang.datei",
           "opts" => ["update-ref"],
         ],
       ],
     ],
   ], // finanzgruppentbl
 ],

 [
   "type" => "textarea", /* renderer */
   "id" => "projekt.beschreibung",
   "title" => "Projektbeschreibung",
   "width" => 12,
   "min-rows" => 10,
   "opts" => ["required"]
 ],

 [
   "type" => "table", /* renderer */
   "title" => "Anhänge",
   "id" => "anhaenge",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "columns" => [
     [ "id" => "anhang.datei",        "name" => "Datei",                    "type" => "file",   "width" => 6, "opts" => ["title"] ],
     [ "id" => "anhang.beschreibung", "name" => "Beschreibung",             "type" => "text",   "width" => 6, "opts" => ["title"] ],
   ],
 ],

 [
   "type" => "plaintext", /* renderer */
   "title" => "Erläuterung",
   "id" => "info",
   "width" => 12,
   "value" => "Der Projektantrag muss rechtzeitig vor Projektbeginn eingereicht werden. Das Projekt darf erst durchgeführt werden, wenn der Antrag genehmigt wurde.",
 ],

];

/* formname , formrevision */
registerForm( "demo", "v4", $layout, $config );
