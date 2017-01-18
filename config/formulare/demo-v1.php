<?php

global $formulare;

/* formname , formrevision */
$formulare["demo"]["v1"] = [

 [
   "type" => "group", /* renderer */
   "title" => "Meta-Daten",
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "children" => [
     [ "id" => "projekt.name",        "title" =>"Projektname",                        "type" => "text",   "width" => 12, "opts" => ["required"] ],
     [ "id" => "projekt.org",         "title" =>"Projekt von",                        "type" => "select", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required"] ],
     [ "id" => "projekt.leitung",     "title" =>"Projektverantwortlich (eMail)",      "type" => "email",  "width" =>  6, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required"] ],
     [ "id" => "projekt.protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["required"] ],
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
   "columns" => [
     [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 2, ],
     [ "id" => "geld.einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [
       "type" => "group", /* renderer */
       "name" => "Nachweise",
       "width" => 2,
       "opts" => ["well"],
       "id" => "group2",
       "children" => [
         [
           "type" => "table", /* renderer */
           "id" => "finanzgruppentblanlagen",
           "width" => 12,
           "name" => "Nachweise",
           "columns" => [
             [ "id" => "geld.anhang.1",  "name" => "Anhänge",                            "type" => "ref",   "width" => 12, "references" => "geld.anhang.2"],
           ],
         ],
         [
           "type" => "multifile", /* renderer */
           "id" => "upload",
           "title" => "Anhänge hochladen",
           "width" => 12,
           "destination" => "geld.anhang.2.datei",
           "opts" => ["update-ref"],
         ],
       ],
     ],
     [
       "type" => "group", /* renderer */
       "name" => "Anhänge #1",
       "width" => 2,
       "opts" => ["well"],
       "id" => "group3",
       "children" => [
         [
           "type" => "table", /* renderer */
           "title" => "Anhänge",
           "id" => "geld.anhang.2",
           "opts" => ["with-row-number","with-headline"],
           "width" => 12,
           "columns" => [
             [ "id" => "geld.anhang.2.datei",        "name" => "Datei",                    "type" => "file",   "width" => 6, "opts" => ["title"] ],
             [ "id" => "geld.anhang.2.beschreibung", "name" => "Beschreibung",             "type" => "text",   "width" => 6, "opts" => ["title"] ],
           ],
         ],
         [
           "type" => "multifile", /* renderer */
           "id" => "upload",
           "title" => "Anhänge hochladen",
           "width" => 12,
           "destination" => "geld.anhang.2.datei",
         ],
       ],
     ],
     [
       "type" => "multifile", /* renderer */
       "id" => "geld.anhang.3",
       "name" => "Anhänge #2",
       "width" => 2,
       "opts" => ["dir"],
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
     [ "id" => "anhang.beschreibung", "name" => "Beschreibung 1",             "type" => "text",   "width" => 3, "opts" => ["title"] ],
     [ "id" => "anhang.beschreibung", "name" => "Beschreibung 2",             "type" => "text",   "width" => 3, ],
   ],
 ],

 [
   "type" => "multifile", /* renderer */
   "id" => "upload",
   "title" => "Anhänge hochladen",
   "width" => 12,
   "destination" => "anhang.datei",
 ],

 [
   "type" => "multifile", /* renderer */
   "id" => "upload2",
   "title" => "Anhänge hochladen (no-table)",
   "width" => 12,
   "opts" => ["dir"],
 ],

];
