<?php

$config = [
  "captionField" => [ "projekt.name", "projekt.org.name" ],
  "revisionTitle" => "Version 20170131",
  "permission" => [
    "isCorrectGremium" => [
      [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
    ],
    "isCreateable" => true,
    "isProjektLeitung" => [
      [ "inOtherForm:referenceField" => [ "isProjektLeitung", ], ],
    ],
    "isEigenerAntrag" => [
      [ "inOtherForm:referenceField" => [ "isEigenerAntrag", ], ],
    ],
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:antragsteller" ],
  "referenceField" => [ "name" => "genehmigung.antrag", "type" => "otherForm" ],
  "fillOnCopy" => [
    [ "name" => "genehmigung.recht", "type" => "radio", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht" ] ],
    [ "name" => "genehmigung.recht.stura.beschluss", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.stura.beschluss" ] ],
    [ "name" => "genehmigung.recht.stura.datum", "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.stura.datum" ] ],
    [ "name" => "genehmigung.recht.int.gremium", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.gremium" ] ],
    [ "name" => "genehmigung.recht.int.datum", "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.datum" ] ],
    [ "name" => "genehmigung.recht.int.sturabeschluss", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.sturabeschluss" ] ],
    [ "name" => "genehmigung.titel", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.titel" ] ],
    [ "name" => "genehmigung.konto", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.konto" ] ],
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
   "id" => "group0",
   "title" => "Genehmigung",
   "children" => [
     [ "id" => "genehmigung.recht.grp",   "title" =>"Rechtsgrundlage",        "type" => "group",    "width" => 12, "children" => [

       [ "id" => "genehmigung.recht", "text" => "Büromaterial: StuRa-Beschluss 21/20-07: bis zu 50 EUR", "type" => "radio", "value" => "buero", "width" => 12, "opts" => ["required"], ],
       [ "id" => "genehmigung.recht", "text" => "Fahrtkosten: StuRa-Beschluss 21/20-08: Fahrtkosten", "type" => "radio", "value" => "fahrt", "width" => 12, "opts" => ["required"], ],
       [ "id" => "genehmigung.recht", "text" => "Verbrauchsmaterial: Finanzordnung §11: bis zu 150 EUR", "type" => "radio", "value" => "verbrauch", "width" => 12, "opts" => ["required"], ],

       [ "id" => "genehmigung.recht", "text" => "Beschluss StuRa-Sitzung\nFür FSR-Titel ist außerdem ein FSR Beschluss notwendig.", "type" => "radio", "value" => "stura", "width" => 6, "opts" => ["required"], ],
       [ "id" => "genehmigung.recht.stura.beschluss", "title" => "Beschluss-Nr", "type" => "text", "width" => 2, ],
       [ "id" => "genehmigung.recht.stura.datum", "title" => "vom", "type" => "date", "width" => 2, ],

       [ "id" => "genehmigung.recht", "text" => "Beschluss Fachschaftsrat/Referat\nStuRa-Beschluss 21/21-05: für ein internes Projekt bis zu 250 EUR\nMuss auf der nächsten StuRa Sitzung bekannt gemacht werden\nund erhält dann eine StuRa-Beschluss-Nr.", "type" => "radio", "value" => "fsr", "width" => 6, "opts" => ["required"], ],
       [ "id" => "genehmigung.recht.int.gremium", "title" => "Gremium", "type" => "text", "width" => 2, "onClickFillFrom" => "projekt.org.name"],
       [ "id" => "genehmigung.recht.int.datum", "title" => "vom", "type" => "date", "width" => 2,  "onClickFillFrom" => "projekt.protokoll", "onClickFillFromPattern" => '\d\d\d\d-\d\d-\d\d'],
       [ "id" => "genehmigung.recht.int.sturabeschluss", "title" => "StuRa-Beschluss-Nr", "type" => "text", "width" => 2, ],
     ], ],
     [ "id" => "genehmigung.titel",   "title" =>"Titel im Haushaltsplan", "type" => "text",     "width" => 6, "opts" => ["required", "hasFeedback"], "minLength" => "5" ],
     [ "id" => "genehmigung.konto",   "title" =>"Konto (Gnu-Cash)",       "type" => "text",     "width" => 6, "opts" => [ "hasFeedback"], "minLength" => "5", "placeholder" => "Wie Titel" ],
     [ "id" => "genehmigung.antrag",  "title" =>"Antrag auf Erstattung war",  "type" => "otherForm",     "width" => 12, "opts" => ["required", "hasFeedback", "readonly"] ],
     [ "id" => "genehmigung.modified", "text" =>"Genehmigte Erstattung weicht vom Antrag ab", "type" => "checkbox", "width" => 12, "opts" => [ "toggleReadOnly" ], "value" => "yes" ],
   ],
 ],

 [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "value" => "Erstattungsantrag",
 ],

 [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "children" => [
     [ "id" => "projekt.name",      "title" =>"Projekt",                     "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10",  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.name",  "title" =>"Projekt von",                 "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.mail",  "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "antragsteller",     "title" =>"Antragsteller (eMail)",       "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "genehmigung",       "title" =>"Projektgenehmigung",          "type" => "otherForm", "width" => 12, "opts" => ["hasFeedback","readonly"], ],
     [ "id" => "iban",              "title" =>"Bankverbindung (IBAN)",       "type" => "iban",  "width" => 12, "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ], # FIXME IBAN TYPE for validation
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzauslagen",
   "opts" => ["with-row-number"],
   "width" => 12,
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
   "columns" => [
     [ "id" => "geld",
       "type" => "group", /* renderer */
       "width" => 12,
       "printSumFooter" => ["einnahmen","ausgaben"],
       "children" => [
         [ "id" => "geld.datum",        "title" => "Datum",                  "type" => "date",   "width" => 3, "opts" => [ "required" ],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
         [ "id" => "geld.beschreibung", "title" => "Beschreibung",           "type" => "text",   "width" => 3, "placeholder" => "Hinweis",  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
         [ "id" => "geld.file",         "title" => "Beleg",                  "type" => "file",   "width" => 6,  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
         [
           "type" => "table", /* renderer */
           "id" => "finanzauslagenposten",
           "opts" => ["with-row-number", "with-headline"],
           "width" => 12,
           "columns" => [
             [ "id" => "geld.posten",       "name" => "Posten aus Genehmigung", "type" => "ref",
               "references" => ["field:genehmigung", "finanzgruppentbl"],
               "updateByReference" => [
                 "geld.titel" /* destination */ => /* remote source */ [ "geld.titel", "genehmigung.titel" /* fallback */ ],
                 "geld.konto" /* destination */ => /* remote source */ [ "geld.konto", "genehmigung.konto" ],
               ]
             ],
             [ "id" => "geld.einnahmen",    "name" => "Einnahmen",              "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["einnahmen", "einnahmen.beleg"], "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
             [ "id" => "geld.ausgaben",     "name" => "Ausgaben",               "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["ausgaben", "ausgaben.beleg"],   "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
             [ "id" => "geld.titel",       "name" => "Titel",                   "type" => "text",   "width" => 2, "placeholder" => "s. Genehmigung", ],
             [ "id" => "geld.konto",       "name" => "Konto (Gnu-Cash)",        "type" => "text",   "width" => 2, "placeholder" => "s. Genehmigung", ],
           ],
         ],
       ],
     ],
# FIXME Anlagen und Verweise
   ], // finanzgruppentbl
 ],

 [
   "type" => "multifile", /* renderer */
   "id" => "upload",
   "title" => "Anhänge hochladen",
   "width" => 12,
   "destination" => "geld.file",
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
 ],

 [
   "type" => "plaintext", /* renderer */
   "title" => "Erläuterung",
   "id" => "info",
   "width" => 12,
   "opts" => ["well"],
   "value" => "Die Auslagenerstattung muss zeitnah nach Tätigung der Ausgabe eingereicht werden. Das Projekt darf erst durchgeführt werden, wenn der Antrag genehmigt wurde.",
 ],

];

/* formname , formrevision */
registerForm( "auslagenerstattung-genehmigung", "v1", $layout, $config );

