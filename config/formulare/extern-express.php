<?php

$config = [
    "title" => "Externes Projekt (Express)",
    "shortTitle" => "Externes Projekt",
    "state" => [
        "draft"             => [ "Entwurf", "als Entwurf speichern" ],
        "need-stura" => ["Eingereicht", "als eingereicht speichern"],
        "stura-ok"          => [ "Beschlossen", "als beschlossen speichern" ],
        "prepaymnt-payed"   => [ "Vorkasse ausgezahlt"],
        "prepaymnt-booked" => ["Vorkasse ausgezahlt  und gebucht"],
        "balancing-ok"      => [ "korrekt Abgerechnet", "als korrekt abgerechnet speichern" ],
        "balancing-payed"   => [ "korrekt Abgerechnet und ausgezahlt"],
        "terminated"        => [ "Abgeschlossen und Gebucht"],
        "no-need"           => [ "Antragsteller verzichtet", "Antragsteller verzichtet" ],
    ],
    "createState" => "draft",

    "proposeNewState" => [
        "draft" => ["need-stura", "stura-ok"],
        "stura-ok" => [ "balancing-ok"],
        "balancing-ok" => ["balancing-payed"],
        "prepayment-booked" => ["balancing-ok"],
    ],
    "categories" => [
        "need-action" => [
            //TODO FIXME
            [ "state" => "draft", "hasPermission" => "canRead" ],
            [ "state" => "stura-ok", "hasPermission" => "canRead"  ],
        ],
        "_need_booking_payment" => [
            [ "state" => "prepaymnt-payed", "group" => "ref-finanzen" ],
            [ "state" => "balancing-payed", "group" => "ref-finanzen" ],
        ],
        "need-payment" => [
            [ "state" => "stura-ok", "group" => "ref-finanzen", "passValidation" => "prepaymnt-exists" ],
            [ "state" => "balancing-ok", "group" => "ref-finanzen", "passValidation" => "balancing-exists" ],
        ],
        "_export_sct" => [
            [ "state" => "stura-ok", "group" => "ref-finanzen", "passValidation" => "prepaymnt-exists" ],
            [ "state" => "balancing-ok", "group" => "ref-finanzen", "passValidation" => "balancing-exists" ],
        ],
    ],
    "printMode" => [
        "bewilligungsbescheid" =>
            ["title" => "Bewilligungsbescheid drucken", "condition" =>
                [
                    ["state" => "stura-ok", "group" => "ref-finanzen"],
                    ["state" => "prepaymnt-payed", "group" => "ref-finanzen"],
                    ["state" => "prepaymnt-booked", "group" => "ref-finanzen"],
                    ["state" => "balancing-ok", "group" => "ref-finanzen"],
                    ["state" => "balancing-payed", "group" => "ref-finanzen"],
                    ["state" => "terminated", "group" => "ref-finanzen"],
                    ["state" => "no-need", "group" => "ref-finanzen"],
                ],
            ],
        "pruefbescheid" =>
            ["title" => "Prüfbescheid drucken",
                "condition" =>
                    [
                        ["state" => "balancing-ok", "group" => "ref-finanzen"],
                        ["state" => "balancing-payed", "group" => "ref-finanzen"],
                        ["state" => "terminated", "group" => "ref-finanzen"],
                        ["state" => "no-need", "group" => "ref-finanzen"],
                    ],
            ],
    ],
    "validate" => [
        "postEdit" => [
            [ "state" => "stura-ok","requiredIsNotEmpty" => true,"doValidate" => "checkBeschlossen"],
            [ "state" => "prepaymnt-payed", "doValidate" => "prepaymnt-exists", ],
            [ "state" => "balancing-ok",
             "or" => [
                 ["doValidate" => "no-prepaymnt",],
                 ["doValidate" => "prepaymnt-payed"],
             ],
             ["doValidate" => "balancing-exists"],
            ],
            [ "state" => "balancing-ok", "doValidate" => "balancing-exists", ],
            [ "state" => "balancing-payed", "doValidate" => "balancing-payed", ],

        ],
    ],
    "permission" => [
        /* each permission has a name and a list of sufficient conditions.
     * Each condition is an AND clause.
     * This is merged with form data that can add extra permissions not given here
     * hasPermission: true if all given permissions are present
     * group: true if all given groups are present
     * field: true if all given checks are ok
     */
        "canBeLinked" => [
            [ "state" => "new" ],
        ],
        "canBeCloned" => true,
        "canRead" => [
            [ "group" => "ref-finanzen" ],
        ],
        "canEdit" => [
            [ "state" => "draft", "hasPermission" => "canRead" ],
        ],
        "canEditPartiell" => [
            [ "state" => "stura-ok","group" => "ref-finanzen", ],
            [ "state" => "prepaymnt-booked", "group" => "ref-finanzen", ],
        ],
        "canEditPartiell.field.geld.abgerechnet" => [
            [ "state" => "stura-ok", "group" => "ref-finanzen", ],
            [ "state" => "prepaymnt-booked", "group" => "ref-finanzen", ],
        ],

        "canDelete" => [
            [ "state" => "draft", "hasPermission" => "canEdit" ],
        ],
        "canCreate" => [
            [ "hasPermission" => [ "canRead", "isCreateable" ] ],
        ],
        "canStateChange.from.draft.to.stura-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.stura-ok.to.balancing-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.prepaymnt-payed.to.prepaymnt-booked" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.prepaymnt-booked.to.balancing-ok" => [
            [ "hasPermission" => "canRead" ],
            ["id" => "geld.abgerechnet","value" => "biggerEquals:0"],
        ],
        "canStateChange.from.balancing-ok.to.balancing-payed" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.balancing-payed.to.terminated" => [
            [ "hasPermission" => "canRead"],
        ],
        "canStateChange.from.draft.to.balancing-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.stura-ok.to.draft" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.stura-ok.to.prepaymnt-payed" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.balancing-ok.to.stura-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.stura-ok.to.no-need" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.no-need.to.stura-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.draft.to.need-stura" => [
            ["hasPermission" => "canRead"],
        ],
        "canStateChange.from.need-stura.to.stura-ok" => [
            ["hasPermission" => "canRead"],
        ],
    ],
];

registerFormClass( "extern-express", $config );

