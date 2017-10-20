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
        ]
    ],
    "preNewStateActions" => [
        "to.no-need" => [
            [ "writeField" => "always", "name" => "geld.abgerechnet",   "type" => "money", "value" => "0" ],
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
        "id" => "group1",
        "title" => "Allgemeine Angaben",
        "children" => [
            [ "id" => "projekt.name", "title" =>"Projektname" ,"type" => "text",   "width" => 12, "opts" => ["required", "hasFeedback"], "minLength" => "10" ],

            [ "id" => "projekt.zeitraum",    "title" =>"Projektdauer", "type" => "daterange", "width" => 6,  "opts" => ["required"] ],

            [ "id" => "projekt.org",        "title" =>"Organisation/Verein",                        "type" => "text",   "width" => 6, "opts" => ["required", "hasFeedback"],],

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
        ],
    ],
    [
        "type" => "group", /* renderer */
        "width" => 12,
        "opts" => ["well"],
        "id" => "group0",
        "title" => "Beschluss StuRa-Sitzung",
        "children" => [
            [ "id" => "geld.stura",
             "name" => "StuRa",
             "title" =>"beschlossene StuRa-Förderung",
             "type" => "money",
             "width" => 4,
             "currency" => "€",
             "opts"=>["required", "hasFeedback",],
             "addToSum" => ["stura"], //nur für ,00 vervolständigung
            ],
            [ "id" => "geld.vorkasse",
             "name" => "Vorkasse",
             "title" =>"davon auf Vorkasse",
             "type" => "money",
             "width" => 4,
             "currency" => "€",
             "opts"=>["required", "hasFeedback"],
             "addToSum" => ["vorkasse"], //nur für ,00 vervolständigung
            ],
            [ "id" => "geld.abgerechnet",
             "name" => "abgerechnet",
             "title" =>"davon korrekt abgerechnet",
             "type" => "money",
             "width" => 4,
             "currency" => "€",
             "addToSum" => ["abrechnung"],//nur für ,00 vervolständigung und addition hhp
             "value" => "-1"
            ],
            [ "id" => "stura.datum",
             "title" => "vom", "type" => "date",
             "width" => 3,
             "opts" => ["required", "hasFeedback"],
            ],
            [ "id" => "genehmigung.recht.stura.beschluss",
             "title" => "StuRa-Beschluss-Nr",
             "type" => "text",
             "width" => 3,
             "opts"=>["required", "hasFeedback"],
            ],

        ],

    ],

];

/* formname , formrevision */
registerForm("extern-express", "v1", $layout, $config );

