<?php

$config = [
  "title" => "Projekt der Studierendenschaft (internes Projekt)",
  "shortTitle" => "Projekt (intern)",
  "state" => [ "draft" => [ "Beantragt", ],
               "ok-by-hv" => [ "Genehmigt durch HV (muss noch verkündet werden)", ],
               "need-stura" => [ "Warte auf StuRa-Beschluss", ],
               "ok-by-stura" => [ "Genehmigt durch StuRa-Beschluss", ],
               "done-hv" => [ "Genehmigt durch HV und protokolliert in StuRa Sitzung", ],
               "done-other" => [ "Genehmigt ohne Verkündung", ],
               "revoked" => [ "Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)", "zurückziehen / ablehnen", ],
               "terminated" => [ "Abgeschlossen (keine weiteren Ausgaben)", "beenden", ],
             ],
  "proposeNewState" => [
    "draft" => [ "need-stura", "ok-by-hv", "revoked", "wip", "done-other" ],
    "wip" => [ "need-stura", "ok-by-hv", "revoked", "draft", "done-other" ],
    "ok-by-hv" => [ "done-hv" ],
    "need-stura" => [ "ok-by-stura", "revoked" ],
    "done-hv" => ["terminated"],
    "done-other" => ["terminated"],
    "ok-by-stura" => [ "terminated" ],
  ],
  "createState" => "draft",
  "buildFrom" => [
    [ "projekt-intern-antrag" /* type */, "done" /* state */ ],
  ],
  "validate" => [
    "postEdit" => [
      [ "state" => "ok-by-hv", "requiredIsNotEmpty" => true ],
      [ "state" => "need-stura", "requiredIsNotEmpty" => true ],
      [ "state" => "ok-by-stura", "requiredIsNotEmpty" => true ],
      [ "state" => "done-hv", "requiredIsNotEmpty" => true ],
      [ "state" => "done-other", "requiredIsNotEmpty" => true ],
      [ "state" => "terminated", "requiredIsNotEmpty" => true ],
      # passende Rechtsgrundlage ausgewählt
      [ "state" => "ok-by-stura", "doValidate" => "checkRechtsgrundlage", ],
      [ "state" => "ok-by-hv", "doValidate" => "checkRechtsgrundlage", ],
      [ "state" => "done-hv", "doValidate" => "checkRechtsgrundlage", ],
      [ "state" => "done-other", "doValidate" => "checkRechtsgrundlage", ],

      [ "state" => "ok-by-stura", "doValidate" => "checkSturaBeschluss", ],

      [ "state" => "done-hv", "doValidate" => "checkSturaBeschlussHV", ],
      [ "state" => "done-hv", "doValidate" => "checkGremiumBeschlussHV", ],
      [ "state" => "ok-by-hv", "doValidate" => "checkGremiumBeschlussHV", ],

      [ "state" => "done-other", "doValidate" => "checkOtherBeschluss", ],
      # Titel ausgewählt
      [ "state" => "ok-by-stura", "doValidate" => "checkTitel", ],
      [ "state" => "ok-by-hv", "doValidate" => "checkTitel", ],
      [ "state" => "done-hv", "doValidate" => "checkTitel", ],
      [ "state" => "done-other", "doValidate" => "checkTitel", ],
      # Derzeit nicht erzwungen: Kostenstelle ausgewählt
#      [ "state" => "ok-by-stura", "doValidate" => "checkKonto", ],
#      [ "state" => "ok-by-hv", "doValidate" => "checkKonto", ],
#      [ "state" => "done-hv", "doValidate" => "checkKonto", ],
#      [ "state" => "done-other", "doValidate" => "checkKonto", ],
    ],
  ],
  "categories" => [
    "report-stura" => [
       [ "state" => "ok-by-hv", "group" => "ref-finanzen" ],
    ],
    "finished" => [
       [ "state" => "terminated" ],
       [ "state" => "revoked" ],
    ],
    "running-project" => [
       [ "state" => "ok-by-hv", "notHasCategory" => "_isExpiredProject2W" ],
       [ "state" => "ok-by-stura", "notHasCategory" => "_isExpiredProject2W" ],
       [ "state" => "done-hv", "notHasCategory" => "_isExpiredProject2W" ],
       [ "state" => "done-other", "notHasCategory" => "_isExpiredProject2W" ],
    ],
    "expired-project" => [
       [ "state" => "ok-by-hv", "hasCategory" => "_isExpiredProject2W" ],
       [ "state" => "ok-by-stura", "hasCategory" => "_isExpiredProject2W" ],
       [ "state" => "done-hv", "hasCategory" => "_isExpiredProject2W" ],
       [ "state" => "done-other", "hasCategory" => "_isExpiredProject2W" ],
    ],
    "need-action" => [
       [ "state" => "draft", "group" => "ref-finanzen-hv" ],
       [ "state" => "wip", "group" => "ref-finanzen-hv" ],
#       [ "state" => "ok-by-hv", "group" => "ref-finanzen" ], # im StuRa Tab
       [ "state" => "need-stura", "hasPermission" => "isCorrectGremium" ],
       [ "state" => "need-stura", "creator" => "self" ],
    ],
    "wait-stura" => [
       [ "state" => "need-stura", "hasPermission" => "isCorrectGremium" ],
       [ "state" => "need-stura", "creator" => "self" ],
       [ "state" => "need-stura", "group" => "ref-finanzen-hv" ],
       [ "state" => "need-stura", "group" => "stura" ],
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
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
      [ "group" => "konsul" ],
      [ "state" => "need-stura", "group" => "stura" ],
    ],
    "canBeCloned" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.genehmigung.recht.int.sturabeschluss" => [
      [ "state" => "need-stura", "group" => "ref-finanzen", ],
      [ "state" => "ok-by-stura", "group" => "ref-finanzen", ],
      [ "state" => "ok-by-hv", "group" => "ref-finanzen", ],
      [ "state" => "done-hv", "group" => "ref-finanzen", ],
      [ "state" => "terminated", "group" => "ref-finanzen", ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
      [ "state" => "need-stura", "group" => "ref-finanzen", ],
    ],
    "canBeLinked" => [
      [ "state" => "ok-by-hv", ],
      [ "state" => "ok-by-stura", ],
      [ "state" => "done-hv", ],
      [ "state" => "done-other", ],
      [ "state" => "terminated", "group" => "ref-finanzen" ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "canEdit", "isCreateable" ] ],
      [ "hasPermission" => [ "canRead", "isCreateable" ] ],
    ],
    "canEditState" => [
      [ "group" => "ref-finanzen-hv", ],
    ],
    # Genehmigung durch StuRa
    "canStateChange.from.draft.to.need-stura" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.need-stura.to.ok-by-stura" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Undo
    "canStateChange.from.need-stura.to.draft" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.ok-by-stura.to.draft" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Genehmigung durch HV
    "canStateChange.from.need-stura.to.ok-by-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.draft.to.ok-by-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.ok-by-hv.to.done-hv" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.draft.to.done-other" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Undo
    "canStateChange.from.ok-by-hv.to.draft" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.done-hv.to.draft" => [
      [ "hasPermission" => "canEditState" ],
    ],
    "canStateChange.from.done-other.to.draft" => [
      [ "hasPermission" => "canEditState" ],
    ],
    # Rücknahme
    "canRevoke" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.draft.to.revoked" => [
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
    "canStateChange.from.done-other.to.revoked" => [
      [ "hasPermission" => "canRevoke" ],
    ],
    "canUnrevoke" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.revoked.to.ok-by-hv" => [
      [ "hasPermission" => [ "canUnrevoke", "isBeschlussHV" ] ],
    ],
    "canStateChange.from.revoked.to.ok-by-stura" => [
      [ "hasPermission" => [ "canUnrevoke", "isBeschlussStuRa" ] ],
    ],
    "canStateChange.from.revoked.to.done-hv" => [
      [ "hasPermission" => [ "canUnrevoke", "isBeschlussHV" ] ],
    ],
    "canStateChange.from.revoked.to.done-other" => [
      [ "hasPermission" => [ "canUnrevoke", "isBeschlussOther" ] ],
    ],
    "canStateChange.from.revoked.to.draft" => [
      [ "hasPermission" => [ "canUnrevoke" ] ],
    ],
    # Beendung
    "canTerminate" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok-by-stura.to.terminated" => [
      [ "hasPermission" => "canTerminate" ],
    ],
    "canStateChange.from.done-hv.to.terminated" => [
      [ "hasPermission" => "canTerminate" ],
    ],
    "canStateChange.from.done-other.to.terminated" => [
      [ "hasPermission" => "canTerminate" ],
    ],
    "canUnterminate" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.terminated.to.ok-by-stura" => [
      [ "hasPermission" => [ "canTerminate", "isBeschlussStuRa" ] ],
    ],
    "canStateChange.from.terminated.to.done-hv" => [
      [ "hasPermission" => [ "canTerminate", "isBeschlussHV" ] ],
    ],
    "canStateChange.from.terminated.to.done-other" => [
      [ "hasPermission" => [ "canTerminate", "isBeschlussOther" ] ],
    ],
  ],
  "postNewStateActions" => [
    "create.draft"                    => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.ok-by-hv"          => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.need-stura"        => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.need-stura.to.ok-by-stura"  => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.need-stura.to.ok-by-hv"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok-by-hv.to.revoked"        => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.ok-by-stura.to.revoked"     => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.need-stura.to.revoked"      => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done-hv.to.revoked"         => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done-hv.to.terminated"      => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done-other.to.revoked"      => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.done-other.to.terminated"   => [ [ "sendMail" => true, "attachForm" => false ] ],
    "from.ok-by-stura.to.terminated"  => [ [ "sendMail" => true, "attachForm" => false ] ],
  ],
];

registerFormClass( "projekt-intern", $config );

