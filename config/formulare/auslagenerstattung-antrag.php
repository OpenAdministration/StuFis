<?php

$config = [
    "title" => "Antrag auf Auslagenerstattung",
    "shortTitle" => "Auslagenerstattung (Antrag)",
    "state" => [ "draft" => [ "Entwurf" ],
                "new" => [ "Beantragt", "beantragen" ],
               ],
    "createState" => "draft",
    "proposeNewState" => [
        "draft" => [ "new", ],
    ],
    "buildFrom" => [ "projekt-intern" ],
    "categories" => [
        "need-action" => [
            [ "state" => "draft", "creator" => "self" ],
            [ "state" => "draft", "hasPermissionNoAdmin" => "isProjektLeitung" ],
        ],
    ],
    "validate" => [
        "postEdit" => [
            [ "state" => "new", "requiredIsNotEmpty" => true ],
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
        "canRead" => [
            [ "creator" => "self" ],
            [ "group" => "ref-finanzen" ],
            [ "group" => "konsul" ],
            # FIXME können wir das lesbar machen falls sich die zugehörige Genehmigung auf das richtige Gremium bezieht?
            # FIXME können wir einzelne Felder unlesbar machen (Bankverbindung) für bestimmte Gruppen -> externes Dictionary
        ],
        "isProjektLeitung" => [
            [ "inOtherForm:referenceField" => [ "isProjektLeitung", ], ],
        ],
        "canEdit" => [
            [ "state" => "draft", "creator" => "self" ],
            [ "state" => "draft", "group" => "ref-finanzen", ],
        ],
        "canBeCloned" => true,
        "canDelete" => [
            [ "state" => "draft", "hasPermission" => "canEdit" ],
        ],
        "canCreate" => [
            [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
        ],
        "canBeLinked" => [
            [ "state" => "new", ],
        ],
        "canStateChange.from.draft.to.new" => [
            [ "group" => "ref-finanzen" ],
            # Zustimmung vom Projektverantwortlichen erforderlich
            [ "hasPermission" => [ "isProjektLeitung" ], ],
        ],
    ],
    "postNewStateActions" => [
        "from.draft.to.new" => [ [ "copy" => true, "type" => "auslagenerstattung", "revision" => "v1", "redirect" => true ] ],
    ],
];

registerFormClass( "auslagenerstattung-antrag", $config );

