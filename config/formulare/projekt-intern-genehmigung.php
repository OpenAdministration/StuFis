<?php

$config = [
  "title" => "Genehmigung für ein Projekt der Studierendenschaft (internes Projekt)",
  "shortTitle" => "Genehmigung internes Projekt",
  "state" => [ "draft" => [ "Entwurf", ],
               "wait-stura" => [ "Warte auf StuRa Beschluss", ],
               "ok-by-hv" => [ "Genehmigt durch HV (muss noch verkündet werden)", ],
               "ok-by-stura" => [ "Genehmigt durch StuRa-Beschluss", ],
               "done-hv" => [ "Genehmigt durch HV und protokolliert in StuRa Sitzung", ],
               "revoked" => [ "Zurückgezogen (KEINE Gnehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "wait-stura", "ok-by-hv" ],
    "wait-stura" => [ "ok-by-stura" ],
    "ok-by-hv" => [ "done-hv" ],
  ],
  "createState" => "draft",
  "buildFrom" => [
    [ "projekt-intern" /* type */, "done" /* state */ ],
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
      [ "hasPermission" => "isCorrectGremium", "state" => "ok" ],
      [ "hasPermission" => "isCorrectGremium", "state" => "wait-stura" ],
      [ "hasPermission" => "isCorrectGremium", "state" => "done" ],
      [ "hasPermission" => "isCorrectGremium", "state" => "revoked" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canEditPartiell" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.genehmigung.recht.int.sturabeschluss" => [
      [ "state" => "ok-by-hv", "group" => "ref-finanzen", ],
      [ "state" => "done-hv", "group" => "ref-finanzen", ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
    ],
    # Genehmigung durch StuRa
    "canEditState" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canStateChange.from.draft.to.wait-stura" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.wait-stura.to.ok-by-stura" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.draft.to.ok-by-stura" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Genehmigung durch HV
    "canStateChange.from.draft.to.ok-by-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.ok-by-hv.to.done-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.draft.to.done-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.wait-stura.to.ok-by-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.wait-stura.to.done-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Rücknahme
    "canRevoke" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.wait-stura.to.revoked" => [
      [ "hasPermission" => "canRevoke" ],
    ],
    "canStateChange.from.ok-by-hv.to.revoked" => [
      [ "hasPermission" => "canRevoke" ],
    ],
    "canStateChange.from.ok-by-stura.to.revoked" => [
      [ "hasPermission" => "canRevoke" ],
    ],
    "canStateChange.from.done-hv.to.revoked" => [
      [ "hasPermission" => "canRevoke" ],
    ],
    "canUnrevoke" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.revoked.to.wait-stura" => [
      [ "hasPermission" => "canUnrevoke" ],
    ],
    "canStateChange.from.revoked.to.ok-by-hv" => [
      [ "hasPermission" => "canUnrevoke" ],
    ],
    "canStateChange.from.revoked.to.ok-by-stura" => [
      [ "hasPermission" => "canUnrevoke" ],
    ],
    "canStateChange.from.revoked.to.done-hv" => [
      [ "hasPermission" => "canUnrevoke" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.ok-by-hv"      => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.done-hv"       => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.wait-stura"    => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.ok-by-stura"   => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.wait-stura.to.revoked"  => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.ok-by-hv.to.revoked"    => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.ok-by-stura.to.revoked" => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done-hv.to.revoked"     => [ [ "sendMail" => true, "attachForm" => false ] ],
  ],
];

registerFormClass( "projekt-intern-genehmigung", $config );

