<?php

$config = [
  "title" => "Auslagenerstattung",
  "shortTitle" => "Auslagenerstattung",
  "state" => [ "draft" => [ "Entwurf" ],
               "new" => [ "Beantragt", "beantragen" ],
               "ok" => [ "Erledigt", "erledigt", ],
               "revoked" => [ "Zurückgezogen (KEINE Gnehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "new", ],
    "new" => [ "ok", "revoked", ],
  ],
  "createState" => "draft",
  "buildFrom" => [ "projekt-intern-genehmigung" ],
  "categories" => [
    "need-action" => [
      [ "state" => "draft", "creator" => "self" ],
      [ "state" => "draft", "hasPermissionNoAdmin" => "isProjektLeitung" ],
      [ "state" => "new", "group" => "ref-finanzen" ],
    ],
    "finished" => [
      [ "state" => "ok" ],
      [ "state" => "revoked" ],
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
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    "canBeLinked" => [
      [ "state" => "new", ],
      [ "state" => "ok", ],
    ],
    "canStateChange.from.draft.to.new" => [
      [ "group" => "ref-finanzen" ],
      # Zustimmung vom Projektverantwortlichen erforderlich
      [ "hasPermission" => [ "isProjektLeitung" ], ],
    ],
    "canStateChange.from.new.to.revoked" => [
      [ "creator" => "self" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.new.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.new"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.new.to.ok"        => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.new.to.revoked"   => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"    => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "auslagenerstattung", $config );

