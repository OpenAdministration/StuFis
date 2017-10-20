<?php

$config = [
    "title" => "Externes Projekt (Express)",
    "shortTitle" => "Externes Projekt",
    "state" => [
        "draft"             => [ "Entwurf", "als Entwurf speichern" ],
        "beschlossen"       => [ "Beschlossen", "als beschlossen speichern" ],
        "terminated"        => [ "Abgerechnet", "als abgerechnet speichern" ],
        "no-need"        => [ "Antragsteller verzichtet", "Antragsteller verzichtet" ],
    ],
    "createState" => "draft",

    "proposeNewState" => [
        "draft" => [ "beschlossen","terminated"],
        "beschlossen" => [ "terminated","no-need"],
    ],
    "categories" => [
        "need-action" => [
            //TODO FIXME
            [ "state" => "draft", "hasPermission" => "canRead" ],
            [ "state" => "beschlossen", "hasPermission" => "canRead"  ],
        ],
    ],
    "validate" => [
        "postEdit" => [
            [ "state" => "beschlossen","requiredIsNotEmpty" => true,"doValidate" => "checkBeschlossen"],
            [ "state" => "terminated","doValidate" => "checkAbrechnung","requiredIsNotEmpty" => true,
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
        "canStateChange.from.beschlossen.to.terminated" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.draft.to.terminated" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.beschlossen.to.draft" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.terminated.to.draft" => [
            [ "hasPermission" => "canRead" ],
        ],
        "canStateChange.from.terminated.to.beschlossen" => [
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

