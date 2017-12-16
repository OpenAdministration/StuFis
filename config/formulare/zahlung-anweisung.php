<?php

$config = [
    "title" => "Zahlungsanweisung",
    "shortTitle" => "Zahlungsanweisung",
    "state" => [ "ok" => [ "Erstellt", ],
                "payed" => [ "Bei der Bank eingereicht", "Wurde bei der Bank eingereicht" ],
                "booked" => [ "Auf Kontoauszug gesehen", "Wurde auf Kontoauszug bestätigt" ],
               ],
    "proposeNewState" => [
        "ok" => [ "payed" ],
        "payed" => [ "booked" ],
    ],
    "createState" => "ok",
    "categories" => [
        "need-payment" => [
            [ "state" => "ok", "group" => "ref-finanzen" ],
        ],
        "finished" => [
            [ "state" => "payed", "group" => "ref-finanzen" ],
            [ "state" => "booked", "group" => "ref-finanzen" ],
        ],
        "_export_bank" => [
            [ "state" => "ok", "group" => "ref-finanzen" ],
            [ "state" => "payed", "group" => "ref-finanzen" ],
        ],
    ],
    "validate" => [
        "postEdit" => [
            [ "state" => "final", "requiredIsNotEmpty" => true ],
            [ "doValidate" => "checkBeleg", ],
            [ "doValidate" => "checkKontenplan", ],
        ],
        "checkBeleg" => [
            "or" => [
                [ "type" => "extern-express", "state" => "stura-ok", "validate" => "postEdit",],
                [ "id" => "zahlung.beleg",
                 "otherForm" => [
                     [ "type" => "auslagenerstattung", "state" => "instructed", "validate" => "postEdit",  ],
                     [ "type" => "auslagenerstattung", "state" => "payed", "validate" => "postEdit",      ],
                     [ "type" => "rechnung-zuordnung", "state" => "instructed", "validate" => "postEdit", ],
                     [ "type" => "rechnung-zuordnung", "state" => "payed", "validate" => "postEdit",       ],
                     [ "type" => "extern-express", "state" => "stura-ok", "validate" => "postEdit",]
                     //[ "type" => "extern-express", "state" => "balancing-ok", "validate" => ["postEdit","prepaymnt-exists"]       ],
                 ],
                ],
            ]

        ],
        "checkKontenplan" => [
            [ "id" => "kontenplan.otherForm",
             "otherForm" => [
                 [ "type" => "kontenplan", "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ],
             ],
            ],
        ],
    ],
    "permission" => [
        "canRead" => [
            [ "group" => "ref-finanzen", ],
        ],
        "canEdit" => false,
        "canDelete" => false,
        "canBeCloned" => false,
        "canCreate" => [
            [ "hasPermission" => "isCreateable", "group" => "ref-finanzen" ],
        ],
        # Genehmigung durch StuRa
        "canStateChange.from.ok.to.payed" => [
            [ "group" => "ref-finanzen" ],
        ],
        "canStateChange.from.payed.to.booked" => [
            [ "group" => "ref-finanzen" ],
        ],
    ],
];

registerFormClass( "zahlung-anweisung", $config );

