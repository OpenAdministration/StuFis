<?php

$config = [
  "captionField" => [ "projekt.name", "projekt.org.name" ],
  "revisionTitle" => "Version 20170131",
  "permission" => [
    "isCorrectGremium" => [
      [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
    ],
    "isCreateable" => true,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:antragsteller" ],
  "referenceField" => [ "name" => "genehmigung", "type" => "otherForm" ],
  "fillOnCopy" => [
    [ "name" => "antragsteller", "type" => "email", "prefill" => "user:mail" ],
  ],
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
   "children" => [
     [ "id" => "projekt.name",      "title" =>"Projekt",                     "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
     [ "id" => "projekt.org.name",  "title" =>"Projekt von",                 "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "projekt.org.mail",  "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "antragsteller",     "title" =>"Antragsteller (eMail)",       "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"] ],
     [ "id" => "genehmigung",       "title" =>"Projektgenehmigung",          "type" => "otherForm", "width" => 12, "opts" => ["hasFeedback"], ],
     [ "id" => "iban",              "title" =>"Bankverbindung (IBAN)",       "type" => "text",  "width" => 12, "opts" => ["required", "hasFeedback"] ], # FIXME IBAN TYPE for validation
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzauslagen",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "rowCountField" => "numauslagen",
   "columns" => [
     [ "id" => "geld.datum",        "name" => "Datum",                  "type" => "date",   "width" => 3, "opts" => [ "required" ] ],
     [ "id" => "geld.beschreibung", "name" => "Beschreibung",           "type" => "text",   "width" => 4, ],
     [ "id" => "geld.posten",       "name" => "Posten aus Genehmigung", "type" => "ref",    "width" => 2, ],
     [ "id" => "geld.einnahmen",    "name" => "Einnahmen",              "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.ausgaben",     "name" => "Ausgaben",               "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"] ],
     [ "id" => "geld.titel",       "name" => "Titel",                   "type" => "text",   "width" => 2, "placeholder" => "s. Genehmigung", ],
     [ "id" => "geld.konto",       "name" => "Konto (Gnu-Cash)",        "type" => "text",   "width" => 2, "placeholder" => "s. Genehmigung", ],
# FIXME Anlagen und Verweise
   ], // finanzgruppentbl
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
registerForm( "auslagenerstattung", "v1", $layout, $config );

