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
     [ "id" => "name",        "title" =>"Projektname",                        "type" => "text",   "width" => 12, "opts" => ["required"] ],
     [ "id" => "org",         "title" =>"Projekt von",                        "type" => "select", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required"] ],
     [ "id" => "leitung",     "title" =>"Projektverantwortlich (eMail)",      "type" => "email",  "width" =>  6, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required"] ],
     [ "id" => "protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["required"] ],
#     [ "id" => "start",       "title" =>"Projektbeginn",                      "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-after" => "field:ende" ],
#     [ "id" => "ende",        "title" =>"Projektende",                        "type" => "date",   "width" => 6,  "opts" => ["not-before-creation"], "not-before" => "field:start" ],
     [ "id" => "zeitraum",    "title" =>"Projektdauer",                       "type" => "daterange", "width" => 12,  "opts" => ["not-before-creation", "required"] ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzgruppen",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "columns" => [
     [ "id" => "name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 8, ],
     [ "id" => "einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
   ],
 ],

 [
   "type" => "textarea", /* renderer */
   "id" => "beschreibung",
   "title" => "Projektbeschreibung",
   "width" => 12,
   "min-rows" => 10,
   "opts" => ["required"]
 ],

];
