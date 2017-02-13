<?php

$config = [
  "captionField" => [ "projekt.name", "projekt.zeitraum" ],
  "revisionTitle" => "Version 20170126",
  "permission" => [
    "isCorrectGremium" => [
      [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
    ],
    "isCreateable" => true,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:projekt.leitung" ],
];

$layout = [
 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "autoValue" => "class:title",
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "title" => "Allgemeine Angaben",
   "children" => [
     [ "id" => "projekt.name",        "title" =>"Projektname",                        "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
     [ "id" => "projekt.leitung",     "title" =>"Projektverantwortlich (eMail)",      "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"] ],
#     [ "id" => "projekt.org.name2",    "title" =>"Projekt von",                        "type" => "select", "width" =>  12, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.org.name",    "title" =>"Projekt von",                        "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.org.mail",    "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["required","hasFeedback","wikiUrl"], "pattern-from-prefix" => "https://wiki.stura.tu-ilmenau.de/protokoll/", "pattern-error" => "Muss mit \"https://wiki.stura.tu-ilmenau.de/protokoll/\" beginnen." ],
     [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer",                       "type" => "daterange", "width" => 12,  "opts" => ["not-before-creation", "required"] ],
     [ "id" => "genehmigung.titel",   "title" =>"Titel im Haushaltsplan", "type" => "text",     "width" => 6, "opts" => [ "hasFeedback"], "minLength" => "5", "placeholder" => "optional" ],
     [ "id" => "genehmigung.konto",   "title" =>"Konto (Gnu-Cash)",       "type" => "text",     "width" => 6, "opts" => [ "hasFeedback"], "minLength" => "5", "placeholder" => "Wie Titel" ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzgruppentbl",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "columns" => [
     [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",                 "opts" => [ "required" ] ],
     [ "id" => "geld.einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.titel",       "name" => "Titel",                              "type" => "text",   "width" => 2, "placeholder" => "s. Kopfbereich", "opts" => ["hideable"] ],
     [ "id" => "geld.konto",       "name" => "Konto (Gnu-Cash)",                   "type" => "text",   "width" => 2, "placeholder" => "s. Kopfbereich", "opts" => ["hideable"] ],
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
   "type" => "plaintext", /* renderer */
   "title" => "Erläuterung",
   "id" => "info",
   "width" => 12,
   "opts" => ["well"],
   "value" => "Der Projektantrag muss rechtzeitig vor Projektbeginn eingereicht werden. Das Projekt darf erst durchgeführt werden, wenn der Antrag genehmigt wurde.",
 ],

];

/* formname , formrevision */
registerForm( "projekt-intern", "v1", $layout, $config );

