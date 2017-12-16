<?php

$config = [
    "captionField" => [ "zahlung.datum", "zahlung.verwendungszweck" ],
    "revisionTitle" => "Bar (Version 20170302)",
    "permission" => [
        "isCreateable" => true,
        "canStateChange.from.payed.to.canceled" => [
            [ "group" => "ref-finanzen" ],
        ],
        "canStateChange.from.draft.to.canceled" => [
            [ "group" => "ref-finanzen" ],
        ],
        "canStateChange.from.booked.to.canceled" => [
            [ "group" => "ref-finanzen" ],
        ],
        "canDelete" => [
            [ "group" => "ref-finanzen", "state" => "draft"],
        ],

        "canEdit" => [
            [ "group" => "ref-finanzen", "state" => "draft"],
        ],
    ],
    "mailTo" => [ "mailto:ref-finanzen@tu-ilmenau.de" ],
    "validate" => [
        "checkSum" => [
            [ "sum" => "expr: %ausgaben - %einnahmen + %einnahmen.beleg - %ausgaben.beleg",
             "maxValue" => 0.00,
             "minValue" => 0.00,
            ],
        ],
    ],
    "categories" => [
        "_variable_booking_reason" => true,
    ],
];

$layout = [
    [
        "type" => "h2", /* renderer */
        "id" => "head1",
        "value" => "Zahlung (Bargeld)",
    ],
    [
        "type" => "group", /* renderer */
        "width" => 12,
        "opts" => ["well"],
        "id" => "group1",
        "title" => "Zahlung",
        "children" => [
            [ "id" => "zahlung.konto",            "title" => "Konto",                "type" => "ref",     "width" => 3, "opts" => ["required", "hasFeedback","edit-skip-referencesId"],
             "references" => [ [ "type" => "kontenplan", "revision" => date("Y"), "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ], [ "konten.bar" => "Konto" ] ],
             "referencesKey" => [ "konten.bar" => "konten.bar.nummer" ],
             "referencesId" => "kontenplan.otherForm",
            ],
            [ "id" => "zahlung.datum",            "title" => "Datum",               "type" => "date",     "width" => 3, "opts" => ["required"], ],
            [ "id" => "zahlung.einnahmen",        "title" => "Einnahmen",           "type" => "money",    "width" => 3, "opts" => ["required"], "addToSum" => [ "einnahmen" ], "currency" => "€"],
            [ "id" => "zahlung.ausgaben",         "title" => "Ausgaben",            "type" => "money",    "width" => 3, "opts" => ["required"], "addToSum" => [ "ausgaben" ], "currency" => "€"],
            [ "id" => "zahlung.verwendungszweck", "title" => "Verwendung lt. Kassenbuch",    "type" => "textarea", "width" => 12, "opts" => ["required"], ],
        ],
    ],

    [
        "type" => "textarea", /* renderer */
        "id" => "zahlung.vermerk",
        "title" => "Vermerk",
        "width" => 12,
        "min-rows" => 3,
    ],

    [
        "type" => "table", /* renderer */
        "id" => "zahlung.grund.table",
        "opts" => ["with-row-number","with-headline"],
        "width" => 12,
        "columns" => [
            [
                "type" => "group", /* renderer */
                "width" => 12,
                "id" => "group2",
                "name" => true,
                "opts" => ["sum-over-table-bottom","readonly"],
                "children" => [
                    [ "id" => "zahlung.grund.beleg", "name" => "Beleg", "type" => "otherForm", "width" => 4, "opts" => ["readonly"],],
                    [ "id" => "zahlung.grund.hinweis", "name" => "Hinweis", "type" => "text", "width" => 4 , "opts" => ["readonly"],],
                    [ "id" => "zahlung.grund.einnahmen", "name" => "Einnahmen", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["sum-over-table-bottom","readonly"],   "addToSum" => ["einnahmen.beleg"], ],
                    [ "id" => "zahlung.grund.ausgaben", "name" => "Ausgaben", "type" => "money",  "width" => 2, "currency" => "€", "opts" => ["readonly","sum-over-table-bottom"],   "addToSum" => ["ausgaben.beleg"] ,],
                ],
            ],
        ],
    ],



];

/* formname , formrevision */
registerForm( "zahlung", "v1-bar", $layout, $config );

