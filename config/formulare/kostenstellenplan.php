<?php

$config = [
  "title" => "Kostenstellenplan",
  "shortTitle" => "Kostenstellen",
  "state" => [ "draft"    => [ "Entwurf", "Entwurf", ],
               "final"    => [ "In Kraft", "in Kraft" ],
             ],
  "createState" => "draft",
  "categories" => [
    "plan" => [
      [],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "state" => "final", "requiredIsNotEmpty" => true ],
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
      [ "state" => "final" ],
    ],
    "canBeCloned" => true,
    "canRead" => true,
    "canEdit" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canDelete" => [
      [ "state" => "draft", "hasPermission" => "canEdit" ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    "canStateChange.from.draft.to.final" => [
      [ "hasPermission" => "canEdit" ],
    ],
  ],
  "proposeNewState" => [
    "draft" => [ "final" ],
  ],
];

registerFormClass( "kostenstellenplan", $config );

