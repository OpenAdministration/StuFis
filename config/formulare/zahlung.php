<?php

# FIXME für bar, giro, giro-hibiscus: check zahlungsbegründender beleg sum matches own sums
# FIXME für bar, giro, giro-hibiscus: check zahlungsbegründender hhp matches own hhp

$config = [
  "title" => "Zahlung",
  "shortTitle" => "Zahlung",
  "state" => [ "payed" => [ "Gezahlt", ],
               "booked" => [ "Gezahlt und gebucht", ],
               "canceled" => [ "Storniert", ],
             ],
  "proposeNewState" => [
    "payed" => [ "booked" ],
  ],
  "createState" => "payed",
  "categories" => [
    "need-booking" => [
       [ "state" => "payed", "group" => "ref-finanzen" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "state" => "payed", "doValidate" => "checkBeleg", ],
      [ "state" => "payed", "doValidate" => "checkKontenplan", ],
      [ "state" => "booked", "doValidate" => "checkBeleg", ],
      [ "state" => "booked", "doValidate" => "checkKontenplan", ],
    ],
    "checkBeleg" => [
      [ "id" => "zahlung.grund.beleg",
        "otherForm" => [
          [ "type" => "auslagenerstattung-genehmigung", "state" => "ok", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "auslagenerstattung-genehmigung", "state" => "payed", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
        ],
      ],
    ],
    "checkKontenplan" => [
     [ "id" => "kontenplan.otherForm",
       "otherForm" => [
         [ "type" => "kontenplan", "revisionIsYearFromField" => "zahlung.datum", "state" => "final" ],
       ],
     ],
    ],
  ],
  "permission" => [
    "canRead" => [
      [ "group" => "konsul" ],
    ],
    "canEditPartiell" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.genehmigung.recht.int.sturabeschluss" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
    ],
    "canCreate" => [
      [ "hasPermission" => [ "group" => "ref-finanzen", "isCreateable" ] ],
    ],
    # Genehmigung durch StuRa
    "canStateChange.from.payed.to.booked" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
];

registerFormClass( "zahlung", $config );

