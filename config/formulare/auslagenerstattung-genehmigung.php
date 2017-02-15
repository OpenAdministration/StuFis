<?php

$config = [
  "title" => "Genehmigung Auslagenerstattung",
  "shortTitle" => "Genehmigung Auslagenerstattung",
  "state" => [ "draft" => [ "Entwurf" ],
               "ok" => [ "Genehmigt", "genehmigen", ],
               "revoked" => [ "Zurückgezogen (KEINE Gnehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "new", ],
    "new" => [ "ok", "revoked", ],
  ],
  "createState" => "draft",
  "buildFrom" => [ [ "auslagenerstattung", "ok" ] ],
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
      [ "hasPermission" => "isEigenerAntrag" ],
      [ "hasPermission" => "isProjektLeitung" ],
# FIXME können wir das lesbar machen falls sich die zugehörige Genehmigung auf das richtige Gremium bezieht?
# FIXME können wir einzelne Felder unlesbar machen (Bankverbindung) für bestimmte Gruppen -> externes Dictionary
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    "canStateChange.from.draft.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.revoked" => [
      [ "group" => "ref-finanzen" ],
      [ "hasPermission" => "isEigenerAntrag" ],
      [ "hasPermission" => "isProjektLeitung" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"   => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "auslagenerstattung-genehmigung", $config );

