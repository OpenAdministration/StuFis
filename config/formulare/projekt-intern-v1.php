<?php

$config = [
  "captionField" => [ "projekt.name", "projekt.zeitraum" ],
  "revisionTitle" => "Version 20170126",
  "categories" => [
    "_isExpiredProject" => [
      ["field:projekt.zeitraum[end]" => "<".date("Y-m-d")],
    ],
    "_isExpiredProject2W" => [
      ["field:projekt.zeitraum[end]" => "<".date("Y-m-d", strtotime("-2 weeks"))],
    ],
  ],
  "permission" => [
    "isCorrectGremium" => [
      [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
    ],
    "isProjektLeitung" => [
      [ "field:projekt.leitung" => "isIn:data-source:own-mail" ],
    ],
    "isCreateable" => true,
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:projekt.leitung" ],
  "referenceField" => [ "name" => "genehmigung.antrag", "type" => "otherForm" ],
  "citeFieldsInMailIfNotEmpty" => [ "genehmigung.hinweis" => "Auflagen", "genehmigung.modified" => "Genehmigtes Projekt weicht vom Antrag ab"],
];

$layout = [];

$layout[] = [
   "type" => "h2", /* renderer */
   "id" => "head1",
   "autoValue" => "class:title",
 ];

$layout[] = [
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
     [ "id" => "genehmigung.titel",   "title" =>"Titel im Haushaltsplan",             "type" => "ref",       "width" => 6, "opts" => [ "hasFeedback", "no-invref" ], "placeholder" => "optional",
       "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "state" => "final" ], [ "titel.einnahmen" => "Einnahmen", "titel.ausgaben" => "Ausgaben" ] ],
       "referencesKey" => [ "titel.einnahmen" => "titel.einnahmen.nummer", "titel.ausgaben" => "titel.ausgaben.nummer" ],
       "referencesId" => "haushaltsplan.otherForm",
     ],
     [ "id" => "genehmigung.konto",   "title" =>"Kostenstelle",                       "type" => "ref",       "width" => 6, "opts" => [ "hasFeedback", "no-invref" ], "placeholder" => "optional",
       "references" => [ [ "type" => "kostenstellenplan", "revision" => date("Y"), "state" => "final" ], "kosten" ],
       "referencesKey" => [ "kosten" => "kosten.nummer" ],
       "referencesId" => "kostenstellenplan.otherForm",
     ],
     [ "id" => "genehmigung.antrag",  "title" =>"Antrag war",             "type" => "otherForm",     "width" => 12, "opts" => ["required", "hasFeedback", "readonly"] ],
     [ "id" => "genehmigung.hinweis", "title" =>"Auflagen",               "type" => "textarea", "width" => 12, "opts" => [ "hasFeedback"] ],
     [ "id" => "genehmigung.modified", "text" =>"Genehmigtes Projekt weicht vom Antrag ab", "type" => "checkbox", "width" => 12, "opts" => [ "toggleReadOnly" ], "value" => "yes" ],
   ],
 ];

$layout[] = [
   "type" => "group", /* renderer */
   "width" => 12,
   "opts" => ["well"],
   "id" => "group1",
   "title" => "Genehmigtes Projekt",
   "children" => [
     [ "id" => "projekt.name",        "title" =>"Projektname",                        "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10", "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.leitung",     "title" =>"Projektverantwortlich (eMail)",      "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.name",    "title" =>"Projekt von",                        "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.mail",    "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["required","hasFeedback","wikiUrl"], "pattern" => "^https:\/\/wiki\.stura\.tu-ilmenau\.de\/protokoll\/.*", "pattern-error" => "Muss mit \"https://wiki.stura.tu-ilmenau.de/protokoll/\" beginnen.",  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer",                       "type" => "daterange", "width" => 12,  "opts" => [ "required"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
   ],
 ];

$layout[] = [
   "type" => "table", /* renderer */
   "id" => "finanzgruppentbl",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
   "columns" => [
     [
       "type" => "group", /* renderer */
       "width" => 12,
       "id" => "group2",
       "name" => true,
       "opts" => [ "title" ],
       "children" => [
         [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 4, "opts" => [ "required", "title" ],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
         [ "id" => "geld.titel",       "name" => "Titel",                              "type" => "ref",     "width" => 2, "placeholder" => "s. Genehmigung",
           "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "state" => "final" ], [ "titel.einnahmen" => "Einnahmen", "titel.ausgaben" => "Ausgaben" ] ],
           "referencesKey" => [ "titel.einnahmen" => "titel.einnahmen.nummer", "titel.ausgaben" => "titel.ausgaben.nummer" ],
           "referencesId" => "haushaltsplan.otherForm",
           "refValueIfEmpty" => "genehmigung.titel",
         ],
         [ "id" => "geld.konto",       "name" => "Kostenstelle",                        "type" => "ref",     "width" => 2, "placeholder" => "s. Genehmigung",
           "references" => [ [ "type" => "kostenstellenplan", "revision" => date("Y"), "state" => "final" ], "kosten" ],
           "referencesKey" => [ "kosten" => "kosten.nummer" ],
           "referencesId" => "kostenstellenplan.otherForm",
           "refValueIfEmpty" => "genehmigung.konto",
         ],
         [ "id" => "geld.einnahmen",   "name" => "Einnahmen",                          "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], "addToSum" => ["einnahmen"]],
         [ "id" => "geld.ausgaben",    "name" => "Ausgaben",                           "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], "addToSum" => ["ausgaben"] ],
# FIXME Restsummen anzeigen
         [ "id" => "geld.invref.grp", "type" => "group", "width" => 12, "opts" => ["well"],
           "children" => [
             [ "id" => "geld.invref.0",      "name" => "Verwendung",                         "type" => "invref", "width" => 12, "opts" => ["with-headline","aggregate-by-otherForm"],
               "printSum" => [ "einnahmen", "ausgaben" ],
               "title" => "Genehmigte oder getätigte Ausgaben und Einnahmen",
               "otherForms" => [
                 ["type" => "auslagenerstattung", "state" => "ok", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "einnahmen" => ["einnahmen.erstattet"], "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
                 ["type" => "auslagenerstattung", "state" => "payed", "referenceFormField" => "genehmigung",
                  "addToSum" => [ "einnahmen" => ["einnahmen.erstattet"], "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
                 ["type" => "auslagenerstattung", "state" => "instructed", "referenceFormField" => "genehmigung",
                  "addToSum" => [ "einnahmen" => ["einnahmen.erstattet"], "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
               ],
             ],
  
             [ "id" => "geld.invref",      "name" => "Verwendung",                         "type" => "invref", "width" => 12, "opts" => ["with-headline","aggregate-by-otherForm"],
               "printSum" => [ "einnahmen", "ausgaben" ],
               "title" => "Beantragte Ausgaben und Einnahmen",
               "otherForms" => [
                 ["type" => "auslagenerstattung", "state" => "draft", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "einnahmen" => ["einnahmen.beantragt"], "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
               ],
             ],
           ], // children
        ], // geld.invref.grp
      ], // children
    ], // column
  ], // columns
];

$layout[] = [
   "type" => "textarea", /* renderer */
   "id" => "projekt.beschreibung",
   "title" => "Projektbeschreibung",
   "width" => 12,
   "min-rows" => 10,
   "opts" => ["required"],
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
 ];

$layout [] = [
   "type" => "plaintext", /* renderer */
   "title" => "Erläuterung",
   "id" => "info",
   "width" => 12,
   "opts" => ["well"],
   "value" => "Der Projektantrag muss rechtzeitig vor Projektbeginn eingereicht werden. Das Projekt darf erst durchgeführt werden, wenn der Antrag genehmigt wurde.",
 ];

/* formname , formrevision */
registerForm( "projekt-intern", "v1", $layout, $config );

