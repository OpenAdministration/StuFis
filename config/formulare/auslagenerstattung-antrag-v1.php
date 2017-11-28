<?php

$config = [
    "captionField" => [ "projekt.name", "projekt.org.name" ],
    "revisionTitle" => "Version 20170131",
    "permission" => [
        "isCorrectGremium" => [
            [ "field:projekt.org.name" => "isIn:data-source:own-orgs" ],
        ],
        "isEigenerAntrag" => [
            [ "field:antragsteller.email" => "isIn:data-source:own-mail" ],
        ],
        "isCreateable" => true,
    ],
    "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de", "field:projekt.org.mail", "field:antragsteller.email" ],
    "referenceField" => [ "name" => "genehmigung", "type" => "otherForm" ],
    "fillOnCopy" => [
        [ "name" => "antragsteller.email", "type" => "email", "prefill" => "user:mail" ],
        //[ "name" => "antragsteller.name", "type" => "text", "prefill" => "user:fullname" ],
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
            [ "id" => "projekt.name",        "title" =>"Projekt",                     "type" => "text",   "width" => 8, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
            [ "id" => "projekt.name.zusatz",        "title" =>"Projekt",                     "type" => "text",   "width" => 4, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
            [ "id" => "projekt.org.name",    "title" =>"Projekt von",                 "type" => "text", "width" =>  6, "data-source" => "own-orgs", "placeholder" => "Institution wählen", "opts" => ["required", "hasFeedback"] ],
            [ "id" => "projekt.org.mail",    "title" =>"Benachrichtigung (Mailingliste zu \"Projekt von\")",  "type" => "email",  "width" =>  6, "data-source" => "own-mailinglists", "placeholder" => "Mailingliste wählen", "opts" => ["required", "hasFeedback"] ],
            [ "id" => "antragsteller.email", "title" => "Person für Rückfragen (eMail)",       "type" => "email",  "width" => 12, "placeholder" => "Vorname.Nachname@tu-ilmenau.de", "prefill" => "user:mail", "opts" => ["required", "hasFeedback"] ],

            [ "id" => "genehmigung",         "title" =>"Projektgenehmigung",          "type" => "otherForm", "width" => 12, "opts" => ["hasFeedback","readonly"], ],
            [ "id" => "antragsteller.name",  "title" =>"Zahlungsempfänger (Name)",        "type" => "text",  "width" => 6, "placeholder" => "Vorname Nachname", /*"prefill" => "user:fullname",*/ "opts" => ["required", "hasFeedback"] ],
            [ "id" => "vwzk",                "title" =>"Zusätzlicher Verwendungszeck (z.B. Rechnungsnummer)",       "type" => "text",  "width" => 6,  "placeholder" => "optional"],
            [ "id" => "iban",                "title" =>"Bankverbindung (IBAN) des Zahlungsempfängers",       "type" => "iban",  "width" => 12, "opts" => ["required", "hasFeedback"] ],

        ],
    ],


    [
        "type" => "table", /* renderer */
        "id" => "finanzauslagen",
        "opts" => ["with-row-number"],
        "renderOptRead" => [ "no-form-compress" ],
        "width" => 12,
        "columns" => [
            [ "id" => "geld",
             "type" => "group", /* renderer */
             "width" => 12,
             "printSumFooter" => ["einnahmen","ausgaben"],
             "opts" => ["title"],
             "children" => [
                 [ "id" => "geld.datum",        "title" => "Belegdatum",                  "type" => "date",   "width" => 3, "opts" => [ "required", "title" ] ],
                 [ "id" => "geld.beschreibung", "title" => "Beschreibung",           "type" => "text",   "width" => 3, "opts" => [ "title" ], "placeholder" => "Hinweis", ],
                 [ "id" => "geld.file",         "title" => "Beleg",                  "type" => "file",   "width" => 6, ],
                 [
                     "type" => "table", /* renderer */
                     "id" => "finanzauslagenposten",
                     "opts" => ["with-row-number", "with-headline"],
                     "width" => 12,
                     "columns" => [
                         [ "id" => "geld.posten",       "name" => "Posten aus Genehmigung", "type" => "ref",
                          "references" => ["referenceField", "finanzgruppentbl"],
                          "updateByReference" => [
                              # fallback not really needed
                              #"geld.titel" /* destination */ => /* remote source */ [ "geld.titel", "genehmigung.titel" /* fallback */ ],
                              #"geld.konto" /* destination */ => /* remote source */ [ "geld.konto", "genehmigung.konto" ],
                              "geld.titel" /* destination */ => /* remote source */ [ "geld.titel" ],
                              "geld.konto" /* destination */ => /* remote source */ [ "geld.konto" ],
                          ]
                         ],
                         [ "id" => "geld.einnahmen",    "name" => "Einnahmen",              "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["einnahmen", "einnahmen.beleg"], "opts" => ["sum-over-table-bottom"] ],
                         [ "id" => "geld.ausgaben",     "name" => "Ausgaben",               "type" => "money",  "width" => 2, "currency" => "€", "addToSum" => ["ausgaben", "ausgaben.beleg"],   "opts" => ["sum-over-table-bottom"] ],
                         [ "id" => "geld.titel",        "name" => "Titel",                  "type" => "ref",    "width" => 2, "placeholder" => "s. Genehmigung", "opts" => [ "hasFeedback", "hideable" ],
                          "references" => [ [ "type" => "haushaltsplan", "revision" => date("Y"), "state" => "final" ], [ "titel.ausgaben" => "Ausgaben", "titel.einnahmen" => "Einnahmen" ] ],
                          "referencesKey" => [ "titel.einnahmen" => "titel.einnahmen.nummer", "titel.ausgaben" => "titel.ausgaben.nummer" ],
                          "referencesId" => "haushaltsplan.otherForm",
                         ],
                         [ "id" => "geld.konto",        "name" => "Kostenstelle",           "type" => "ref",    "width" => 2, "placeholder" => "s. Genehmigung", "opts" => [ "hasFeedback", "hideable" ],
                          "references" => [ [ "type" => "kostenstellenplan", "revision" => date("Y"), "state" => "final" ], "kosten" ],
                          "referencesKey" => [ "kosten" => "kosten.nummer" ],
                          "referencesId" => "kostenstellenplan.otherForm",
                         ],
                     ], // columns
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
    ],

    [
        "type" => "alert-warning", /* renderer */
        "id" => "info",
        "width" => 12,
        "opts" => ["well"],
        "value" => "\nDie Auslagenerstattung muss zeitnah nach Tätigung der Ausgabe eingereicht werden. \nDie Auslagenerstattung kann nur vom Projektverantwortlichen eingereicht werden, jeder andere kann nur als Entwurf speichern.\nDirekt nach dem speichern muss dieser Antrag ausgedruckt (Druckerbutton rechts oben - nicht die Druckfunktion des Browsers nutzen!) und mit den Originalbelegen in den vorgesehenen Feldern ins Fach des Referat Finanzen im StuRa Büro gelegt werden. Auch online Belege sind auszudrucken und als Belege abzugeben",
    ],

];

/* formname , formrevision */
registerForm( "auslagenerstattung-antrag", "v1", $layout, $config );
