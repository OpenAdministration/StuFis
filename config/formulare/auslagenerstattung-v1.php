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
      [ "field:antragsteller.email" => "isIn:data-source:own-mail" ],
    ],
  ],
  "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:antragsteller.email" ],
  "referenceField" => [ "name" => "genehmigung.antrag", "type" => "otherForm" ],
  "fillOnCopy" => [
    [ "name" => "genehmigung.recht", "type" => "radio", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht" ] ],
    [ "name" => "genehmigung.recht.stura.beschluss", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.stura.beschluss" ] ],
    [ "name" => "genehmigung.recht.stura.datum", "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.stura.datum" ] ],
    [ "name" => "genehmigung.recht.int.gremium", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.gremium" ] ],
    [ "name" => "genehmigung.recht.int.datum", "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.datum" ] ],
    [ "name" => "genehmigung.recht.int.sturabeschluss", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.int.sturabeschluss" ] ],
    [ "name" => "genehmigung.recht.kleidung.gremium", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.kleidung.gremium" ] ],
    [ "name" => "genehmigung.recht.kleidung.datum", "type" => "date", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.kleidung.datum" ] ],
    [ "name" => "genehmigung.recht.other.reason", "type" => "text", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.recht.other.reason" ] ],
    [ "name" => "genehmigung.titel", "type" => "ref", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.titel" ] ],
    [ "name" => "genehmigung.konto", "type" => "ref", "prefill" => "otherForm", "otherForm" => [ "field:genehmigung", "genehmigung.konto" ] ],
    [ "name" => "genehmigung.jahr", "type" => "text", "prefill" => "value:".date("Y") ],
  ],
  "preNewStateActions" => [
    "from.draft.to.ok-kv"  => [ [ "writeField" => "always", "name" => "genehmigung.rechnerischeRichtigkeit", "type" => "signbox" ] ],
    "from.ok-kv.to.draft"  => [ [ "writeField" => "always", "name" => "genehmigung.rechnerischeRichtigkeit", "type" => "signbox", "value" => "" ] ],

    "from.draft.to.ok-hv"  => [ [ "writeField" => "always", "name" => "genehmigung.sachlicheRichtigkeit", "type" => "signbox" ] ],
    "from.ok-hv.to.draft"  => [ [ "writeField" => "always", "name" => "genehmigung.sachlicheRichtigkeit", "type" => "signbox", "value" => "" ] ],

    "from.ok-hv.to.ok"  => [ [ "writeField" => "always", "name" => "genehmigung.rechnerischeRichtigkeit", "type" => "signbox" ] ],
    "from.ok.to.ok-hv"  => [ [ "writeField" => "always", "name" => "genehmigung.rechnerischeRichtigkeit", "type" => "signbox", "value" => "" ] ],

    "from.ok-kv.to.ok"  => [ [ "writeField" => "always", "name" => "genehmigung.sachlicheRichtigkeit", "type" => "signbox" ] ],
    "from.ok.to.ok-kv"  => [ [ "writeField" => "always", "name" => "genehmigung.sachlicheRichtigkeit", "type" => "signbox", "value" => "" ] ],
  ],
  "validate" => [
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
    "checkRichtigkeit" => [
      [ "id" => "genehmigung.sachlicheRichtigkeit", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.rechnerischeRichtigkeit", "value" => "is:notEmpty" ],
    ],
    "checkRechtsgrundlage" => [
      [ "id" => "genehmigung.recht", "value" => "is:notEmpty" ],
      [ "or" => [
          [ "id" => "genehmigung.recht", "value" => "notEquals:stura" ],
          [ "doValidate" => "checkBeschlussStura" ],
        ]
      ],
      [ "or" => [
          [ "id" => "genehmigung.recht", "value" => "notEquals:fsr" ],
          [ "doValidate" => "checkBeschlussHV" ],
        ]
      ],
      [ "or" => [
          [ "id" => "genehmigung.recht", "value" => "notEquals:other" ],
          [ "doValidate" => "checkBeschlussOther" ],
        ]
      ],
    ],
    "checkBeschlussStura" => [
      [ "id" => "genehmigung.recht", "value" => "equals:stura" ],
      [ "id" => "genehmigung.recht.stura.beschluss", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht.stura.datum", "value" => "is:notEmpty" ],
    ],
    "checkBeschlussHV" => [
      [ "id" => "genehmigung.recht", "value" => "equals:fsr" ],
      [ "id" => "genehmigung.recht.int.sturabeschluss", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht.int.datum", "value" => "is:notEmpty" ],
      [ "id" => "genehmigung.recht.int.gremium", "value" => "is:notEmpty" ],
    ],
    "checkBeschlussOther" => [
      [ "id" => "genehmigung.recht", "value" => "equals:other" ],
      [ "id" => "genehmigung.recht.other.reason", "value" => "is:notEmpty" ],
    ],
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
# Änderungen an diesem Feld brauchen eine Aktualisierung der *.titel und *.konto Felder und dort neben haushaltsplan.otherForm/kostenstellenplan.otherForm auch neue Select-Inhalte.
# Ansatz: *alten* Wert speichern und Formular neuladen mit einem passenden Override
# dazu "refreshBeforeChange" flag
     [ "id" => "genehmigung.jahr", "title" =>"Haushaltsjahr", "type" => "text", "width" => 12, "opts" => ["required", "hasFeedback", "readonly", "refreshFormBeforeChange"], "prefill" => "value:".date("Y"),
       "pattern" => '^\d\d\d\d$' ],
     [ "id" => "genehmigung.recht.grp",   "title" =>"Rechtsgrundlage",        "type" => "group",    "width" => 12, "children" => [

       [ "id" => "genehmigung.recht.grp.0", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Büromaterial: StuRa-Beschluss 21/20-07: bis zu 50 EUR", "type" => "radio", "value" => "buero", "width" => 12, "opts" => ["required"], ],
       ], ],
       [ "id" => "genehmigung.recht.grp.1", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Fahrtkosten: StuRa-Beschluss 21/20-08: Fahrtkosten", "type" => "radio", "value" => "fahrt", "width" => 12, "opts" => ["required"], ],
       ], ],
       [ "id" => "genehmigung.recht.grp.2", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Verbrauchsmaterial: Finanzordnung §11: bis zu 150 EUR", "type" => "radio", "value" => "verbrauch", "width" => 12, "opts" => ["required"], ],
       ], ],

       [ "id" => "genehmigung.recht.grp.3", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Beschluss StuRa-Sitzung\nFür FSR-Titel ist außerdem ein FSR Beschluss notwendig.", "type" => "radio", "value" => "stura",
           "width" => [12, 12, 6, 6],
           "opts" => ["required"], ],
         [ "id" => "genehmigung.recht.stura.beschluss", "title" => "Beschluss-Nr", "type" => "text",
           "width" => [ 6, 6, 2, 2], ],
         [ "id" => "genehmigung.recht.stura.datum", "title" => "vom", "type" => "date",
           "width" => [ 6, 6, 2, 2], ],
        [ "id" => "genehmigung.recht.stura.empty", "type" => "plaintext", "width" => 2, ],
       ], ],

       [ "id" => "genehmigung.recht.grp.4", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Beschluss Fachschaftsrat/Referat\nStuRa-Beschluss 21/21-05: für ein internes Projekt bis zu 250 EUR\nMuss auf der nächsten StuRa Sitzung bekannt gemacht werden\nund erhält dann eine StuRa-Beschluss-Nr.", "type" => "radio", "value" => "fsr",
          "width" => [12, 12, 6, 6, ],
          "opts" => ["required"], ],
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
          "width" => [12, 12, 6, 6, ],
          "opts" => ["required"], ],
         [ "id" => "genehmigung.recht.kleidung.gremium", "title" => "Gremium", "type" => "text",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.org.name"],
         [ "id" => "genehmigung.recht.kleidung.datum", "title" => "vom", "type" => "date",
           "width" => [ 4, 4, 2, 2, ],
           "onClickFillFrom" => "projekt.protokoll", "onClickFillFromPattern" => '\d\d\d\d-\d\d-\d\d'],
       ], ],	

       [ "id" => "genehmigung.recht.grp.5", "type" => "group",    "width" => 12, "children" => [
         [ "id" => "genehmigung.recht", "text" => "Andere Rechtsgrundlage", "type" => "radio", "value" => "other",
           "width" => [12, 12, 6, 6],  ],
         [ "id" => "genehmigung.recht.other.reason", "title" => "Grund", "type" => "text",
           "width" => [ 12, 12, 6, 6], ],
       ], ],

     ], ],
     [ "id" => "genehmigung.titel",   "title" =>"Titel im Haushaltsplan",             "type" => "ref",       "width" => 6, "opts" => [ "hasFeedback", "no-invref", "edit-skip-referencesId" ], "placeholder" => "optional",
       "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ], [ "titel.ausgaben" => "Ausgaben", "titel.einnahmen" => "Einnahmen" ] ],
       "referencesKey" => [ "titel.einnahmen" => "titel.einnahmen.nummer", "titel.ausgaben" => "titel.ausgaben.nummer" ],
       "referencesId" => "haushaltsplan.otherForm",
     ],
     [ "id" => "genehmigung.konto",   "title" =>"Kostenstelle",                      "type" => "ref",       "width" => 6, "opts" => [ "hasFeedback", "no-invref", "edit-skip-referencesId" ], "placeholder" => "optional",
       "references" => [ [ "type" => "kostenstellenplan", "revision" => date("Y"), "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ], "kosten" ],
       "referencesKey" => ["kosten" => "kosten.nummer" ],
       "referencesId" => "kostenstellenplan.otherForm",
     ],
     [ "id" => "genehmigung.antrag",  "title" =>"Antrag auf Erstattung war",  "type" => "otherForm",     "width" => 12, "opts" => ["required", "hasFeedback", "readonly"] ],
     [ "id" => "genehmigung.modified", "text" =>"Genehmigte Erstattung weicht vom Antrag ab", "type" => "checkbox", "width" => 12, "opts" => [ "toggleReadOnly" ], "value" => "yes" ],
     [ "id" => "genehmigung.sachlicheRichtigkeit", "title" =>"Sachliche Richtigkeit", "type" => "signbox", "width" => 6, "opts" => [ "required", "readonly" ]],
     [ "id" => "genehmigung.rechnerischeRichtigkeit", "title" =>"Rechnerische Richtigkeit", "type" => "signbox", "width" => 6, "opts" => [ "required", "readonly" ] ],
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
     [ "id" => "projekt.name",        "title" =>"Projekt",                     "type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10",  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.name",    "title" =>"Projekt von",                 "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "projekt.org.mail",    "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "antragsteller.name",  "title" =>"Zahlungsempfänger (Name)",        "type" => "text",  "width" => 12, "placeholder" => "Vorname Nachname", "prefill" => "user:fullname", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "antragsteller.email", "title" =>"Benachrichtigung bei Überweisung (eMail)",       "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
     [ "id" => "genehmigung",         "title" =>"Projektgenehmigung",          "type" => "otherForm", "width" => 12, "opts" => ["hasFeedback","readonly"], ],
     [ "id" => "iban",                "title" =>"Bankverbindung (IBAN) des Zahlungsempfängers",       "type" => "iban",  "width" => 12, "opts" => ["required", "hasFeedback"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
   ],
 ],

 [
   "type" => "table", /* renderer */
   "id" => "finanzauslagen",
   "opts" => ["with-row-number"],
   "width" => 12,
   "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
   "renderOptRead" => [ "no-form-compress" ],
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
           "toggleReadOnly" => [ "genehmigung.modified", "yes" ],
           "columns" => [
             [ "id" => "geld.posten",       "name" => "Posten aus Genehmigung", "type" => "ref",
               "references" => ["field:genehmigung", "finanzgruppentbl"],
               "updateByReference" => [
                 # fallback not really needed due to refValueIfEmpty
                 #"geld.titel" /* destination */ => /* remote source */ [ "geld.titel", "genehmigung.titel" /* fallback */ ],
                 #"geld.konto" /* destination */ => /* remote source */ [ "geld.konto", "genehmigung.konto" ],
                 "geld.titel" /* destination */ => /* remote source */ [ "geld.titel" ],
                 "geld.konto" /* destination */ => /* remote source */ [ "geld.konto" ],
               ]
             ],
             [ "id" => "geld.einnahmen",    "name" => "Einnahmen",              "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["einnahmen", "einnahmen.beleg"], "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
             [ "id" => "geld.ausgaben",     "name" => "Ausgaben",               "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["ausgaben", "ausgaben.beleg"],   "opts" => ["sum-over-table-bottom"],  "toggleReadOnly" => [ "genehmigung.modified", "yes" ], ],
             [ "id" => "geld.titel",       "name" => "Titel",                   "type" => "ref",    "width" => 2, "placeholder" => "s. Genehmigung", "opts" => ["edit-skip-referencesId"],
               "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ], [ "titel.ausgaben" => "Ausgaben", "titel.einnahmen" => "Einnahmen" ] ],
               "referencesKey" => [ "titel.einnahmen" => "titel.einnahmen.nummer", "titel.ausgaben" => "titel.ausgaben.nummer" ],
               "referencesId" => "haushaltsplan.otherForm",
               "refValueIfEmpty" => "genehmigung.titel",
             ],
             [ "id" => "geld.konto",       "name" => "Kostenstelle",            "type" => "ref",    "width" => 2, "placeholder" => "s. Genehmigung", "opts" => ["edit-skip-referencesId"],
               "references" => [ [ "type" => "kostenstellenplan", "revision" => date("Y"), "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ], "kosten" ],
               "referencesKey" => [ "kosten" => "kosten.nummer" ],
               "referencesId" => "kostenstellenplan.otherForm",
               "refValueIfEmpty" => "genehmigung.konto",
             ],
           ],
         ],
       ],
     ],
   ], // finanzgruppentbl
 ],

 [
   "type" => "multifile", /* renderer */
   "id" => "upload",
   "title" => "Mehrere Belege hochladen (werden automatisch oben als neue Zeilen ergänzt)",
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

 [ "id" => "zahlungen.invref1", "type" => "invref", "width" => 12,
   "opts" => ["with-headline","aggregate-by-otherForm","hide-edit"],
   "printSum" => [ "einnahmen.beleg", "ausgaben.beleg" ],
   "printSumWidth" => 2,
   "orderBy" => [ "field:zahlung.datum", "id" ],
   "title" => "Zahlungen",
   "renderOptRead" => [ "no-form-compress" ],
   "otherForms" => [
     ["type" => "zahlung", "referenceFormField" => "zahlung.grund.beleg",
      "addToSum" => [ "ausgaben.beleg" => [ "ausgaben.zahlung" ], "einnahmen.beleg" => [ "einnahmen.zahlung" ] ],
     ],
   ],
 ],

];

/* formname , formrevision */
registerForm( "auslagenerstattung", "v1", $layout, $config );

