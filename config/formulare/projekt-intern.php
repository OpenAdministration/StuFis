<?php

$config = [
  "title" => "Finanzantrag für ein Projekt der Studierendenschaft (internes Projekt)",
  "shortTitle" => "Antrag internes Projekt",
  "state" => [ "draft"      => [ "Entwurf", "Entwurf", ],
               "new"        => [ "Eingereicht", "einreichen" ],
               "wip"        => [ "In Bearbeitung", "mir zur Bearbeitung zuweisen", ],
               "done"       => [ "Erledigt", "erledigen", ],
               "need-stura" => [ "StuRa-Beschluss fehlt", "auf StuRa Beschluss warten", ],
               "obsolete"   => [ "Zurückgezogen", "zurückziehen", ],
             ],
  "createState" => "draft",
  "categories" => [
    "finished" => [
       [ "state" => "obsolete", "group" => "ref-finanzen" ],
       [ "state" => "done", "group" => "ref-finanzen" ],
    ],
    "wait-action" => [
       [ "state" => "new", "notHasCategory" => "need-action" ],
       [ "state" => "wip", "notHasCategory" => "need-action" ],
       [ "state" => "need-stura", "notHasCategory" => "need-action" ],
    ],
    "need-action" => [
       [ "state" => "new", "group" => "ref-finanzen" ],
       [ "state" => "wip", "group" => "ref-finanzen" ],
       [ "state" => "need-stura", "hasPermission" => "isCorrectGremium" ],
       [ "state" => "need-stura", "creator" => "self" ],
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
      [ "state" => "wip" ],
      [ "state" => "done" ],
    ],
    "canRead" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
      [ "group" => "konsul" ],
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
    "canStateChange.from.new.to.done" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.need-stura" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.new" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.done" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.need-stura" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wip.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.need-stura.to.done" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.need-stura.to.new" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.need-stura.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.done.to.obsolete" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.new" => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.new.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.new.to.need-stura" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.wip.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.wip.to.need-stura" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done.to.obsolete" => [ [ "sendMail" => true, "attachForm" => false ] ],
  ],
  "proposeNewState" => [
    "draft" => [ "new" ],
    "new" => [ "wip", "need-stura" ],
    "wip" => [ "need-stura" ],
  ],
];

registerFormClass( "projekt-intern", $config );

