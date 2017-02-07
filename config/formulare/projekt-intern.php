<?php

$config = [
  "title" => "Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
  "shortTitle" => "Antrag internes Projekt",
  "state" => [ "draft"    => [ "Entwurf", "Entwurf", ],
               "new"      => [ "Eingereicht", "einreichen" ],
               "wip"      => [ "In Bearbeitung", "mir zur Bearbeitung zuweisen", ],
               "done"     => [ "Erledigt", "erledigen", ],
               "obsolete" => [ "Zurückgezogen", "zurückziehen", ],
             ],
  "createState" => "draft",
  "stateNoValidate" => [ "draft", ],
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
      [ "state" => "wip" ],
      [ "state" => "done" ],
      [ "state" => "obsolete" ],
    ],
    "canRead" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canEdit" => [
      [ "state" => "draft", "hasPermission" => "canRead" ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    "canStateChange.from.draft.to.new" => [
      [ "hasPermission" => "canEdit" ],
    ],
    "canStateChange.from.new.to.obsolete" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.wip" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.new" => [
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
  "newStateActions" => [
    "from.draft.to.new" => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.new.to.done" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.wip.to.done" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.new.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.wip.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
  ],
  "proposeNewState" => [
    "draft" => [ "new" ],
    "new" => [ "wip" ],
  ],
];

registerFormClass( "projekt-intern", $config );

