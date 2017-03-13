<?php

$config = [
  "title" => "Auslagenerstattung",
  "shortTitle" => "Auslagenerstattung",
  "state" => [ "draft" => [ "Beantragt" ],
               "ok" => [ "Genehmigt", "genehmigen", ],
               "instructed" => [ "Angewiesen", ],
               "payed" => [ "Bezahlt (Kontoauszug)", ],
               "revoked" => [ "Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "ok", "revoked" ],
    "ok" => [ "instructed", ],
  ],
  "createState" => "draft",
  "buildFrom" => [ [ "auslagenerstattung-antrag", "ok" ] ],
  "categories" => [
    "need-action" => [
      [ "state" => "draft", "group" => "ref-finanzen" ],
      [ "state" => "ok", "group" => "ref-finanzen" ],
    ],
    "need-payment" => [
      [ "state" => "ok", "group" => "ref-finanzen" ],
    ],
    "_export_sct" => [
      [ "state" => "ok", "group" => "ref-finanzen" ],
    ],
    "_need_booking_payment" => [
      [ "state" => "ok", "group" => "ref-finanzen" ],
      [ "state" => "instructed", "group" => "ref-finanzen" ],
    ],
    "finished" => [
      [ "state" => "instructed" ],
      [ "state" => "payed" ],
      [ "state" => "revoked" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      # richtige Summen bezahlt
      [ "state" => "payed", "doValidate" => "checkZahlung", ], # hier sollten die Beträge stimmen
#      [ "state" => "ok", "doValidate" => "checkZahlung", ], # hier kann es noch über- oder unterzahlt sein
      # richtige Formularversion aka Haushaltsjahr
      [ "doValidate" => "checkKostenstellenplan", ],
      [ "doValidate" => "checkHaushaltsplan", ],
      # sachliche und rechnerische Richtigkeit (Unterschrift)
      [ "state" => "ok", "doValidate" => "checkRichtigkeit", ],
      [ "state" => "instructed", "doValidate" => "checkRichtigkeit", ],
      [ "state" => "payed", "doValidate" => "checkRichtigkeit", ],
      # Rechtsgrundlage ausgewählt
      [ "state" => "ok", "doValidate" => "checkRechtsgrundlage", ],
      [ "state" => "instructed", "doValidate" => "checkRechtsgrundlage", ],
      [ "state" => "payed", "doValidate" => "checkRechtsgrundlage", ],
      # Titel ausgewählt
      [ "state" => "ok", "doValidate" => "checkTitel", ],
      [ "state" => "instructed", "doValidate" => "checkTitel", ],
      [ "state" => "payed", "doValidate" => "checkTitel", ],
      # Derzeit nicht erzwungen: Kostenstelle ausgewählt
#      [ "state" => "ok", "doValidate" => "checkKonto", ],
#      [ "state" => "instructed", "doValidate" => "checkKonto", ],
#      [ "state" => "payed", "doValidate" => "checkKonto", ],
    ],
    "checkZahlung" => [
      [ "sum" => "expr: %ausgaben.zahlung - %einnahmen.zahlung + %einnahmen.beleg - %ausgaben.beleg",
        "maxValue" => 0.00,
        "minValue" => 0.00,
      ],
    ],
    "checkKostenstellenplan" => [
     [ "id" => "kostenstellenplan.otherForm",
       "otherForm" => [
         [ "type" => "kostenstellenplan", "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ],
       ],
     ],
    ],
    "checkHaushaltsplan" => [
     [ "id" => "haushaltsplan.otherForm",
       "otherForm" => [
         [ "type" => "haushaltsplan", "revisionIsYearFromField" => "genehmigung.jahr", "state" => "final" ],
       ],
     ],
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
      [ "hasPermission" => [ "canRead", "isCreateable" ] ],
    ],
    "canBeCloned" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canStateChange.from.draft.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.draft" => [
    ],
    "canStateChange.from.ok.to.instructed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.instructed.to.ok" => [
    ],
    "canStateChange.from.instructed.to.payed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.payed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.payed.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.draft.to.revoked" => [
      [ "group" => "ref-finanzen" ],
      [ "hasPermission" => "isProjektLeitung" ],
      [ "hasPermission" => "isEigenerAntrag" ],
      [ "creator" => "self" ],
    ],
    "canStateChange.from.revoked.to.draft" => [
      [ "group" => "ref-finanzen" ],
      [ "hasPermission" => "isProjektLeitung" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"   => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "auslagenerstattung", $config );

