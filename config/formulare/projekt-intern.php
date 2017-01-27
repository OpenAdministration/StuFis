<?php

$config = [
  "title" => "Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
  "state" => [ "draft" => "Entwurf", "new" => "eingereicht", "wip" => "in Bearbeitung", "done" => "erledigt", "obsolete" => "veraltet / wird nicht bearbeitet" ],
  "createState" => "draft",
  "permission" => [
    /* each permission has a name and a list of sufficient conditions.
     * Each condition is an AND clause.
     * This is merged with form data that can add extra permissions not given here
     * hasPermission: true if all given permissions are present
     * group: true if all given groups are present
     * field: true if all given checks are ok
     */
    "canRead" => [
      [ "state" => "draft", "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canEdit" => [
      [ "state" => "draft", "creator" => "self" ],
      [ "state" => "draft", "hasPermission" => "isCorrectGremium" ],
      [ "state" => "draft", "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.draft.to.new" => [
      [ "hasPermission" => "canEdit" ],
    ],
    "canStateChange.from.new.to.obsolete" => [
      [ "state" => "draft", "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.wip" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.done" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.done" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.done.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
];

registerFormClass( "projekt-intern", $config );

