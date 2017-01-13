<?php

global $formulare;

/* formname , formrevision */
$formulare["projekt-intern"]["v1"] = [

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
   "id" => "finanzgruppen",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "columns" => [
     [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 4, ],
     [ "id" => "geld.einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [
       "type" => "table", /* renderer */
       "id" => "finanzgruppenanlagen",
       "opts" => [],
       "width" => 4,
       "name" => "Nachweise",
       "columns" => [
         [ "id" => "geld.anhang",  "name" => "Anhänge",                            "type" => "ref",   "width" => 12, "references" => "anhaenge"],
       ],
     ],
   ],
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
     [ "id" => "anhang.datei",        "name" => "Datei",                    "type" => "file",   "width" => 6, ],
     [ "id" => "anhang.beschreibung", "name" => "Beschreibung",             "type" => "text",   "width" => 6, ],
   ],
 ],

 [
   "type" => "multifile", /* renderer */
   "id" => "upload",
   "title" => "Anhänge hochladen",
   "width" => 12,
   "destination" => "anhang.datei",
 ],

];
