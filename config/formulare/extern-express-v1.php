<?php

$config = [
    "captionField" => [ "projekt.name", "projekt.zeitraum", "projekt.org" ],
    "revisionTitle" => "Version 20171001",
    "permission" => [ "group" => "ref-finanzen", "isCreateable" => true,],
    "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de",
                 /*"field:projekt.org.mail",*/],
    "validate" => [
        "checkAbrechnung" => [
            ["id" => "geld.abgerechnet",
             "value" => "smallerEquals:#geld.stura"
            ],
            ["id" => "geld.abgerechnet",
             "value" => "bigger:0"
            ],
        ],
        "checkBeschlossen" => [
            ["id" => "geld.stura",
             "value" => "bigger:0"
            ],
            ["id" => "geld.vorkasse",
             "value" => "smallerEquals:#geld.stura"
            ],
        ],
        "prepaymnt-exists" => [
            ["id" => "geld.vorkasse",
             "value" => "bigger:0"
            ],
        ],

        "no-prepaymnt" =>[
            ["id" => "geld.vorkasse",
             "value" => "equals:0"
            ],
        ],
        "prepaymnt-payed" => [
            ["id" => "geld.abgerechnet",
             "value" => "biggerEquals:0"
            ],
        ],
        "balancing-payed" => [
            ["id" => "geld.abgerechnet",
             "value" => "smallerEquals:#geld.stura"
            ],
            ["id" => "geld.abgerechnet",
             "value" => "bigger:0"
            ],
        ],
        "balancing-exists" => [
            ["id" => "geld.abgerechnet",
             "value" => "smallerEquals:#geld.stura"
            ],
            ["id" => "geld.abgerechnet",
             "value" => "bigger:0"
            ],
        ],
    ],
    "preNewStateActions" => [
        "to.no-need" => [
            [ "writeField" => "always", "name" => "geld.abgerechnet",   "type" => "money", "value" => "0" ],
        ],
    ],
];
$config["printMode"]["bewilligungsbescheid"]["mapping"] = [
    "komaVar" => [
        "vereinName" => "projekt.org.name",
        "vereinPerson" => "_",
        "vereinAdresse" => "_",
        "vereinOrt" => "_",
        "datum" => "stura.datum",/*time() - für aktuelle Zeit; stura.datum für Beschlussdatum*/
        "projId" => "autovalue:id",
        "projName" => "projekt.name",
        "projDauer" => "autovalue:daterange:projekt.zeitraum",
        "sturaBeschluss" => "genehmigung.recht.stura.beschluss",
        "sturaBetrag" => "geld.stura",
        "sturaVorkasse" => "geld.vorkasse",
        "iban" => "org.iban"
    ],
];
$config["printMode"]["pruefbescheid"]["mapping"] = [
    "komaVar" => [
        "vereinName" => "projekt.org.name",
        "vereinPerson" => "_",
        "vereinAdresse" => "_",
        "vereinOrt" => "_",
        "datum" => "autovalue:today",/*time() - für aktuelle Zeit; stura.datum für Beschlussdatum*/
        "projId" => "autovalue:id",
        "projName" => "projekt.name",
        "projAbrechnungDatum" => "hmm...",
        "sturaBetrag" => "geld.stura",
        "sturaVorkasse" => "geld.vorkasse",
        "sturaAbrechnung" => "geld.abgerechnet",
        "iban" => "org.iban",
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
        "title" => "Allgemeine Angaben",
        "children" => [
            [ "id" => "projekt.name", "title" =>"Projektname" ,"type" => "text",   "width" => 6, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
            [ "id" => "org.mail", "title" =>"Kontakt (Mail)" ,"type" => "email",   "width" => 6,],

            [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer", "type" => "daterange", "width" => 6,  "opts" => ["required"] ],
    
            ["id" => "projekt.org.name", "title" => "Organisation/Verein", "type" => "text", "width" => 6, "opts" => ["required", "hasFeedback"],],

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
            [ "id" => "org.iban", "title" =>"IBAN" ,"type" => "iban",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],
        ],
    ],
    [
        "type" => "group", /* renderer */
        "width" => 12,
        "opts" => ["well"],
        "id" => "group0",
        "title" => "Beschluss StuRa-Sitzung",
        "children" => [
            [ "id" => "genehmigung.recht.stura.beschluss",
             "title" => "StuRa-Beschluss-Nr",
             "type" => "text",
             "width" => 2,
             "opts"=>["required", "hasFeedback"],
            ],
            [ "id" => "stura.datum",
             "title" => "Beschluss vom", "type" => "date",
             "width" => 2,
             "opts" => ["required", "hasFeedback"],
            ],
            [ "id" => "geld.stura",
             "name" => "StuRa",
             "title" =>"beschl. Förderung",
             "type" => "money",
             "width" => 2,
             "currency" => "€",
             "opts"=>["required", "hasFeedback",],
             "addToSum" => ["stura"], //nur für ,00 vervolständigung
            ],
            [ "id" => "geld.vorkasse",
             "name" => "Vorkasse",
             "title" =>"davon auf Vorkasse",
             "type" => "money",
             "width" => 2,
             "currency" => "€",
             "opts"=>["required", "hasFeedback"],
             "addToSum" => ["vorkasse"], //nur für ,00 vervolständigung
            ],
            ["id" => "geld.abgerechnet",
             "name" => "abgerechnet",
             "title" =>"korrekt abgerechnet",
             "type" => "money",
             "width" => 2,
             "currency" => "€",
             "addToSum" => ["abrechnung"],//nur für ,00 vervolständigung (und addition hhp?)
             "value" => "-1",
            ],


        ],
    ],
    [ "id" => "zahlungen.invref1", "type" => "invref", "width" => 12,
     "opts" => ["with-headline","aggregate-by-otherForm","hide-edit"],
     "printSum" => [ "einnahmen.beleg", "ausgaben.beleg" ],
     "printSumWidth" => 2,
     "orderBy" => [ "field:zahlung.datum", "id" ],
     "title" => "Bisherige Zahlungen zu diesem Projekt",
     "renderOptRead" => [ "no-form-compress" ],
     "otherForms" => [
         ["type" => "zahlung", "referenceFormField" => "zahlung.grund.beleg",
          "addToSum" => [ "ausgaben.beleg" => [ "ausgaben.zahlung" ], "einnahmen.beleg" => [ "einnahmen.zahlung" ] ],
         ],
     ],
    ],
];

/* formname , formrevision */
registerForm("extern-express", "v1", $layout, $config );

