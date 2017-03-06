<?php

# FIXME: Status zwischen "Anzahlung angewiesen" und "Gezahlt und verbucht" (aka Zahlung auf Kontoauszug erledigt) unterscheiden
# FIXME: Wenn in lezterem Status dann validateInput mit Summe Einnahmen = Summe Einnahmen.Zahlung analog Ausgaben

$config = [
  "title" => "Genehmigung Auslagenerstattung",
  "shortTitle" => "Genehmigung Auslagenerstattung",
  "state" => [ "draft" => [ "Entwurf" ],
               "ok" => [ "Genehmigt", "genehmigen", ],
               "payed" => [ "Bezahlt", ],
               "revoked" => [ "Zurückgezogen (KEINE Gnehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "ok", ],
    "ok" => [ "payed", ],
  ],
  "createState" => "draft",
  "buildFrom" => [ [ "auslagenerstattung", "ok" ] ],
  "categories" => [
    "need-action" => [
      [ "state" => "draft", "group" => "ref-finanzen" ],
      [ "state" => "ok", "group" => "ref-finanzen" ],
    ],
    "finished" => [
      [ "state" => "payed" ],
      [ "state" => "revoked" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "state" => "payed", "doValidate" => "checkZahlung", ], # hier sollten die Beträge stimmen
#      [ "state" => "ok", "doValidate" => "checkZahlung", ], # hier kann es noch über- oder unterzahlt sein
      [ "doValidate" => "checkKostenstellenplan", ],
      [ "doValidate" => "checkHaushaltsplan", ],
    ],
    "checkZahlung" => [
      [ "sum" => "expr: %ausgaben.zahlung - %einnahmen.zahlung + %einnahmen.beleg - %ausgaben.beleg",
        "maxValue" => 0.00,
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
    ],
    "canStateChange.from.draft.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.payed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
  "newStateActions" => [
    "from.draft.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"   => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "auslagenerstattung-genehmigung", $config );

