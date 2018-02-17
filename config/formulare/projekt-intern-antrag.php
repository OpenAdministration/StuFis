<?php

$config = [
  "title" => "Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
    "shortTitle" => "Projektantrag (antrag-intern)",
  "state" => [ "draft"      => [ "Entwurf", "Entwurf", ],
               "new"        => [ "Eingereicht", "einreichen" ],
             ],
  "createState" => "draft",
  "proposeNewState" => [
    "draft" => [ "new" ],
  ],
  "categories" => [
    "need-action" => [
       [ "state" => "draft", "hasPermission" => "isCorrectGremium" ],
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
    "canBeLinked" => [
      [ "state" => "new" ],
    ],
    "canBeCloned" => true,
    "canRead" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
      [ "group" => "konsul" ],
    ],
    "canEdit" => [
      [ "state" => "draft", "hasPermission" => "canRead" ],
    ],
    "canDelete" => [
      [ "state" => "draft", "hasPermission" => "canEdit" ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    "canStateChange.from.draft.to.new" => [
      [ "hasPermission" => "canEdit" ],
    ],
  ],
  "postNewStateActions" => [
    "from.draft.to.new" => [ [ "copy" => true, "type" => "projekt-intern", "revision" => "v1", "redirect" => true ] ],
  ],
];

registerFormClass( "projekt-intern-antrag", $config );

