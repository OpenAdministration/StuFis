<?php

$config = [
    "title" => "Externes Projekt (Express)",
    "shortTitle" => "Externes Projekt",
    "state" => [
        "draft"             => [ "Entwurf", "als Entwurf speichern" ],
        "beschlossen"       => [ "Beschlossen", "als beschlossen speichern" ],
        "vorkasse-bezahlt"  => [ "Vorkasse ausgezahlt + Gebucht"],
        "abrechnung-ok"     => [ "Korrekt Abgerechnet", "als abgerechnet speichern" ],
        "terminated"        => [ "Abgeschlossen und Gebucht"],
        "no-need"           => [ "Antragsteller verzichtet", "Antragsteller verzichtet" ],
    ],
    "createState" => "draft",

    "proposeNewState" => [
        "draft" => [ "beschlossen","abrechnung-ok"],
        "beschlossen" => [ "abrechnung-ok","no-need"],
    ],
    "categories" => [
        "need-action" => [
            //TODO FIXME
            [ "state" => "draft", "hasPermission" => "canRead" ],
            [ "state" => "beschlossen", "hasPermission" => "canRead"  ],
        ],
        "_need_booking_payment" => [
            [ "state" => "beschlossen", "group" => "ref-finanzen" ],
            [ "state" => "abrechnung-ok", "group" => "ref-finanzen" ],
        ],
    ],
    "validate" => [
        "postEdit" => [
            [ "state" => "beschlossen","requiredIsNotEmpty" => true,"doValidate" => "checkBeschlossen"],
            [ "state" => "abrechnung-ok","doValidate" => "checkAbrechnung","requiredIsNotEmpty" => true,
            ],
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
            [ "state" => "beschlossen","group" => "ref-finanzen", ],
        ],
        "canEditPartiell.field.geld.abgerechnet" => [
            [ "state" => "beschlossen", "group" => "ref-finanzen", ],
        ],

        "canDelete" => [
            [ "state" => "draft", "hasPermission" => "canEdit" ],
        ],
        "canCreate" => [
            [ "hasPermission" => [ "canRead", "isCreateable" ] ],
        ],
        "canStateChange.from.draft.to.beschlossen" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.beschlossen.to.abrechnung-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.draft.to.abrechnung-ok" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.beschlossen.to.draft" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.beschlossen.to.vorkasse-bezahlt" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.abrechnung-ok.to.draft" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.abrechnung-ok.to.beschlossen" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.beschlossen.to.no-need" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.no-need.to.beschlossen" => [
            [ "hasPermission" => "canRead" ],
        ],
    ],
];

registerFormClass( "extern-express", $config );

