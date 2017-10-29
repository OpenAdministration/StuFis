<?php

$config = [
  "captionField" => [ "rechnung.datum", "rechnung.firma", "rechnung.ausgaben|currency=€", "rechnung.org.name" ],
  "revisionTitle" => "Version 20170326",
  "permission" => [
    "isCorrectGremium" => [
      [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
      [ "field:projekt.org.name" => "==" ], # empty
    ],
    "isCreateable" => false,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", ],
  "referenceField" => [ "name" => "teilrechnung.projekt", "type" => "otherForm" ], # also pass-through for create.$state copy action
  "fillOnCopy" => [
    [ "name" => "rechnung.org.name", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:teilrechnung.projekt", "projekt.org.name" ] ],
    [ "name" => "rechnung.org.mail", "type" => "email", "prefill" => "otherForm", "otherForm" => [ "field:teilrechnung.projekt", "projekt.org.mail" ] ],
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
     [ "id" => "rechnung.datum",        "title" => "Rechnungsdatum",                      "type" => "date",      "width" => 6,  "opts" => ["required", "hasFeedback"], ],
     [ "id" => "rechnung.eingang",      "title" => "Posteingang beim StuRa",              "type" => "date",      "width" => 6,  "opts" => ["required", "hasFeedback"], "value" => date("Y-m-d") ],
     [ "id" => "rechnung.firma",        "title" => "Rechnung von (Firma)",                "type" => "text",      "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
     [ "id" => "rechnung.ausgaben",     "title" => "Geforderter Betrag",                  "type" => "money",     "width" => 12, "opts" => ["required", "hasFeedback"], "currency" => "€", "addToSum" => [ "ausgaben" ], ],
     [ "id" => "rechnung.zahlungsart",  "title" => "Zahlung per Überweisung",             "type" => "checkbox",  "width" => 6,  "value" => "transfer", "text" => "Zahlung per Überweisung" ],
     [ "id" => "rechnung.frist",        "title" => "Zahlung bis",                         "type" => "date",      "width" => 6,  ],
     [ "id" => "iban",                  "title" => "Bankverbindung (IBAN) (nur wenn Zahlung per Überweisung)",   "type" => "iban",      "width" => 12, ],
     [ "id" => "rechnung.file",         "title" => "(Gescanntes) Rechungsdokument (pdf)", "type" => "multifile", "width" => 12 ],
     [ "id" => "rechnung.org.name",     "title" => "Rechnung für Projekt von Gremium",    "type" => "text",      "width" =>  6, "data-source" => "all-orgs", "placeholder" => "Institution wählen", ],
     [ "id" => "rechnung.org.mail",     "title" => "Benachrichtigung (Mailingliste zu \"Rechnung für Projekt von Gremium\")", "type" => "email", "width" =>  6, "data-source" => "all-mailinglists", "placeholder" => "Mailingliste wählen", ],
     [ "id" => "rechnung.proforma",     "title" => "Pro-Forma Beleg für Vorkasse",        "type" => "checkbox",  "width" => 12,  "value" => "yes", "text" => "Pro-Forma Beleg für Vorkasse"],
     [ "id" => "teilrechnung.projekt",  "title" => "Vorschlag für Projektzuordnung",      "type" => "otherForm", "width" => 12, "opts" => ["readonly"], ],
   ],
 ],

 [
   "type" => "plaintext", /* renderer */
   "title" => "Erläuterung",
   "id" => "info",
   "width" => 12,
   "opts" => ["well"],
   "value" => "Die Rechnung direkt an den StuRa muss zeitnah nach Erhalt eingereicht werden, sodass ggf. mögliche Skonti noch genutzt und Mahnungen vermieden werden.",
 ],

 [ "id" => "zahlungen.invref1", "type" => "invref", "width" => 12,
   "opts" => ["with-headline","aggregate-by-otherForm","hide-edit"],
   "printSum" => [ "einnahmen.zugeordnet", "ausgaben.zugeordnet" ],
   "printSumWidth" => 2,
   "orderBy" => [ "field:zahlung.datum", "id" ],
   "title" => "Zahlungen",
   "renderOptRead" => [ "no-form-compress" ],
   "otherForms" => [
     ["type" => "rechnung-zuordnung", "referenceFormField" => "teilrechnung.beleg",
      "addToSum" => [ "ausgaben.rechnung" => [ "ausgaben.zugeordnet" ], "einnahmen.rechnung" => [ "einnahmen.zugeordnet" ] ],
     ],
   ],
 ],

];

/* formname , formrevision */
registerForm( "rechnung-beleg", "v1", $layout, $config );

