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
    "isBeschlussStuRa" => [
      [ "field:genehmigung.recht" => "==stura" ],
    ],
    "isBeschlussHV" => [
      [ "field:genehmigung.recht" => "==fsr" ],
    ],
    "isBeschlussOther" => [
      [ "field:genehmigung.recht" => "==other" ],
      [ "field:genehmigung.recht" => "==buero" ],
      [ "field:genehmigung.recht" => "==fahrt" ],
      [ "field:genehmigung.recht" => "==verbrauch" ],
      [ "field:genehmigung.recht" => "==kleidung"],
    ],
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:projekt.leitung" ],
  "referenceField" => [ "name" => "genehmigung.antrag", "type" => "otherForm" ],
  "citeFieldsInMailIfNotEmpty" => [ "genehmigung.hinweis" => "Auflagen", "genehmigung.modified" => "Genehmigtes Projekt weicht vom Antrag ab"],
  "validate" => [
    "checkRechtsgrundlage" => [
      [ "id" => "genehmigung.recht", "value" => "is:notEmpty" ],
    ],
    "checkOtherBeschlussOther" => [
      [ "id" => "genehmigung.recht", "value" => "equals:other" ],
      [ "id" => "genehmigung.recht.other.reason", "value" => "is:notEmpty" ],
    ],
    "checkOtherBeschlussKleidung" => [
      [ "id" => "genehmigung.recht", "value" => "equals:kleidung" ],
      [ "id" => "genehmigung.recht.kleidung.gremium", "value" => "is:notEmpty" ],
    ],
    "checkOtherBeschluss" => [
      [ "or" => [
        [ "doValidate" => "checkOtherBeschlussOther" ],
        [ "doValidate" => "checkOtherBeschlussKleidung"],
        [ "id" => "genehmigung.recht", "value" => "equals:buero" ],
        [ "id" => "genehmigung.recht", "value" => "equals:fahrt" ],
        [ "id" => "genehmigung.recht", "value" => "equals:verbrauch" ],
      ] ],
    ],
    "checkSturaBeschluss" => [
      [ "id" => "genehmigung.recht", "value" => "equals:stura" ],
      [ "id" => "genehmigung.recht.stura.beschluss", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht.stura.datum", "value" => "is:notEmpty" ],
    ],
    "checkSturaBeschlussHV" => [
      [ "id" => "genehmigung.recht.int.sturabeschluss", "value" => "is:notEmpty" ],
    ],
    "checkGremiumBeschlussHV" => [
      [ "id" => "genehmigung.recht.int.datum", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht.int.gremium", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht", "value" => "equals:fsr" ],
    ],
    "checkTitel" => [
      [ "or" => [
          [ "id" => "genehmigung.titel", "value" => "is:notEmpty" ],
          [ "id" => "geld.titel", "value" => "is:notEmpty" ],
        ]
      ],
    ],
    "checkKonto" => [
      [ "or" => [
          [ "id" => "genehmigung.konto", "value" => "is:notEmpty" ],
          [ "id" => "geld.konto", "value" => "is:notEmpty" ],
        ]
      ],
    ],
  ],
  "preNewStateActions" => [
    "to.ok-by-hv" => [
      [ "writeField" => "ifEmpty", "name" => "genehmigung.recht",   "type" => "radio", "value" => "fsr" ],
    ],
    "to.done-hv" => [
      [ "writeField" => "ifEmpty", "name" => "genehmigung.recht",   "type" => "radio", "value" => "fsr" ],
    ],
    "to.ok-by-stura" => [
      [ "writeField" => "ifEmpty", "name" => "genehmigung.recht",   "type" => "radio", "value" => "stura" ],
    ],
  ],
  "fillOnCopy" => [
    [ "name" => "genehmigung.recht.int.gremium", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung.antrag", "projekt.org.name" ] ],
    [ "name" => "genehmigung.recht.int.datum",   "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung.antrag", "projekt.protokoll", 'pattern' => '\d\d\d\d-\d\d-\d\d' ] ],
  ],
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

       [ "id" => "genehmigung.recht.grp.0", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Büromaterial: StuRa-Beschluss 21/20-07: bis zu 50 EUR", "type" => "radio", "value" => "buero", "width" => 12, ],
       ], ],
       [ "id" => "genehmigung.recht.grp.1", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Fahrtkosten: StuRa-Beschluss 21/20-08: Fahrtkosten", "type" => "radio", "value" => "fahrt", "width" => 12, ],
       ], ],
       [ "id" => "genehmigung.recht.grp.2", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Verbrauchsmaterial: Finanzordnung §11: bis zu 150 EUR", "type" => "radio", "value" => "verbrauch", "width" => 12, ],
       ], ],

       [ "id" => "genehmigung.recht.grp.3", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Beschluss StuRa-Sitzung\nFür FSR-Titel ist außerdem ein FSR Beschluss notwendig.", "type" => "radio", "value" => "stura",
           "width" => [12, 12, 6, 6],  ],
         [ "id" => "genehmigung.recht.stura.beschluss", "title" => "Beschluss-Nr", "type" => "text",
           "width" => [ 6, 6, 2, 2], ],
         [ "id" => "genehmigung.recht.stura.datum", "title" => "vom", "type" => "date",
           "width" => [ 6, 6, 2, 2], ],
        [ "id" => "genehmigung.recht.stura.empty", "type" => "plaintext", "width" => 2, ],
       ], ],

       [ "id" => "genehmigung.recht.grp.4", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Beschluss Fachschaftsrat/Referat\nStuRa-Beschluss 21/21-05: für ein internes Projekt bis zu 250 EUR\nMuss auf der nächsten StuRa Sitzung bekannt gemacht werden\nund erhält dann eine StuRa-Beschluss-Nr.", "type" => "radio", "value" => "fsr",
          "width" => [12, 12, 6, 6, ], ],
         [ "id" => "genehmigung.recht.int.gremium", "title" => "Gremium", "type" => "text",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.org.name"],
         [ "id" => "genehmigung.recht.int.datum", "title" => "vom", "type" => "date",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.protokoll", "onClickFillFromPattern" => '\d\d\d\d-\d\d-\d\d'],
         [ "id" => "genehmigung.recht.int.sturabeschluss", "title" => "StuRa-Beschluss-Nr", "type" => "text",
           "width" => [ 4, 4, 2, 2, ], ],
       ], ],
       [ "id" => "genehmigung.recht.grp.4b", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Gremienkleidung: \n StuRa Beschluss 24/04-09 bis zu 25€ pro Person für das teuerste Kleidungsstück (pro Gremium und Legislatur). Für Aktive ist ein Beschluss des Fachschaftsrates / Referates notwendig.", "type" => "radio", "value" => "kleidung",
          "width" => [12, 12, 6, 6, ], ],
         [ "id" => "genehmigung.recht.kleidung.gremium", "title" => "Gremium", "type" => "text",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.org.name"],
         [ "id" => "genehmigung.recht.kleidung.datum", "title" => "vom", "type" => "date",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.protokoll", "onClickFillFromPattern" => '\d\d\d\d-\d\d-\d\d'],
         [ "id" => "genehmigung.recht.kleidung.empty", "type" => "plaintext", "width" => 2, ],
       ], ],

       [ "id" => "genehmigung.recht.grp.5", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Andere Rechtsgrundlage", "type" => "radio", "value" => "other",
           "width" => [12, 12, 6, 6],  ],
         [ "id" => "genehmigung.recht.other.reason", "title" => "Grund", "type" => "text",
           "width" => [ 12, 12, 6, 6], ],
       ], ],

     ], ],

     [ "id" => "genehmigung.titel",   "title" =>"Titel im Haushaltsplan",             "type" => "ref",       "width" => 6, "opts" => [ "hasFeedback", "no-invref" ], "placeholder" => "optional",
       "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "state" => "final" ], [ "titel.ausgaben" => "Ausgaben", "titel.einnahmen" => "Einnahmen" ] ],
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
     [ "id" => "projekt.protokoll",   "title" =>"Projektbeschluss (Wiki Direktlink)", "type" => "url",    "width" => 12, "placeholder" => "https://wiki.stura.tu-ilmenau.de/protokoll/...", "opts" => ["not-required","hasFeedback","wikiUrl"], "pattern" => "^https:\/\/wiki\.stura\.tu-ilmenau\.de\/protokoll\/.*", "pattern-error" => "Muss mit \"https://wiki.stura.tu-ilmenau.de/protokoll/\" beginnen.",  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer",                       "type" => "daterange", "width" => 12,  "opts" => [ "required"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
   ],
 ];

$layout[] = [
   "type" => "table", /* renderer */
   "id" => "finanzgruppentbl",
   "opts" => ["with-row-number","with-headline"],
   "width" => 12,
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
   "renderOptRead" => [ "no-form-compress" ],
   "columns" => [
     [
       "type" => "group", /* renderer */
       "width" => 12,
       "id" => "group2",
       "name" => true,
       "opts" => [ "title", "sum-over-table-bottom" ],
       "children" => [
         [ "id" => "geld.name",        "name" => "Ein/Ausgabengruppe",                 "type" => "text",   "width" => 4, "opts" => [ "required", "title" ],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
         [ "id" => "geld.titel",       "name" => "Titel",                              "type" => "ref",     "width" => 2, "placeholder" => "s. Genehmigung",
           "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "state" => "final" ], [ "titel.ausgaben" => "Ausgaben", "titel.einnahmen" => "Einnahmen" ] ],
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
         [ "id" => "geld.invref.grp", "type" => "group", "width" => 12, "opts" => ["well"],
           "children" => [
             [ "id" => "geld.invref.0",      "name" => "Verwendung",                         "type" => "invref", "width" => 12, "opts" => ["with-headline","aggregate-by-otherForm"],
               "printSum" => [ "einnahmen", "ausgaben" ], "printSumWidth" => 2,
               "title" => "Genehmigte oder getätigte Ausgaben und Einnahmen",
               "otherForms" => [
                 ["type" => "rechnung-zuordnung", "state" => "ok", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
                 ["type" => "rechnung-zuordnung", "state" => "payed", "referenceFormField" => "genehmigung",
                  "addToSum" => [ "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
                 ["type" => "rechnung-zuordnung", "state" => "instructed", "referenceFormField" => "genehmigung",
                  "addToSum" => [ "ausgaben" => ["ausgaben.erstattet"] ],
                 ],
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
               "extraFooter" => [ # hidden if no invref references
                 [ "id" => "geld.invref.0.sum", "type" => "group", "width" => 12, "title" => "Nach Abzug der getätigten Ausgaben und Einnahmen verbleiben",
                   "children" => [
                     [
                       "type" => "plaintext", /* renderer */
                       "title" => "",
                       "id" => "geld.invref.0.sum.info",
                       "width" => 8,
                       "value" => " ",
                     ],
                     [ "id" => "geld.einnahmen.rest.0",   "name" => "verbleibende Einnahmen",  "type" => "money",  "width" => 2,
                       "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
                       "printSumDefer" => "expr:%einnahmen - %einnahmen.erstattet"
                     ],
                     [ "id" => "geld.ausgaben.rest.0",   "name" => "verbleibende Ausgaben",  "type" => "money",  "width" => 2,
                       "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
                       "printSumDefer" => "expr:%ausgaben - %ausgaben.erstattet"
                     ],
                   ],
                 ],
               ],
             ],
  
             [ "id" => "geld.invref.1",    "name" => "Verwendung",                         "type" => "invref", "width" => 12, "opts" => ["with-headline","aggregate-by-otherForm"],
               "printSum" => [ "einnahmen", "ausgaben" ], "printSumWidth" => 2,
               "title" => "Beantragte Auslagenerstattungen",
               "otherForms" => [
                 ["type" => "rechnung-zuordnung", "state" => "draft", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
                 ["type" => "rechnung-zuordnung", "state" => "ok-hv", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
                 ["type" => "rechnung-zuordnung", "state" => "ok-kv", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
                 ["type" => "auslagenerstattung", "state" => "draft", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "einnahmen" => ["einnahmen.beantragt"], "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
                 ["type" => "auslagenerstattung", "state" => "ok-hv", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "einnahmen" => ["einnahmen.beantragt"], "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
                 ["type" => "auslagenerstattung", "state" => "ok-kv", "referenceFormField" => "genehmigung", 
                  "addToSum" => [ "einnahmen" => ["einnahmen.beantragt"], "ausgaben" => ["ausgaben.beantragt"] ],
                 ],
               ],
               "extraFooter" => [ # hidden if no invref references
                 [ "id" => "geld.invref.1.sum", "type" => "group", "width" => 12, "title" => "Nach Abzug der getätigten oder beantragten Auslagenerstattungen verbleiben",
                   "children" => [
                     [
                       "type" => "plaintext", /* renderer */
                       "title" => "",
                       "id" => "geld.invref.1.sum.info",
                       "width" => 8,
                       "value" => " ",
                     ],
                     [ "id" => "geld.einnahmen.rest.1",   "name" => "verbleibende Einnahmen",  "type" => "money",  "width" => 2,
                       "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
                       "printSumDefer" => "expr:%einnahmen - %einnahmen.erstattet - %einnahmen.beantragt"
                     ],
                     [ "id" => "geld.ausgaben.rest.1",   "name" => "verbleibende Ausgaben",  "type" => "money",  "width" => 2,
                       "currency" => "€", "opts" => ["hide-if-zero","sum-over-table-bottom","hide-edit"],
                       "printSumDefer" => "expr:%ausgaben - %ausgaben.erstattet - %ausgaben.beantragt"
                     ],
                   ],
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

