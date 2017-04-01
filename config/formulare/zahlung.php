<?php

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
       [ "state" => "payed", "group" => "ref-finanzen", "hasCategory" => "_variable_booking_reason" ],
    ],
    "finished" => [
       [ "state" => "booked", "group" => "ref-finanzen" ],
    ],
    "_need_booking_reason" => [
       [ "state" => "payed", "group" => "ref-finanzen", "hasCategory" => "_variable_booking_reason" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "state" => "payed", "requiredIsNotEmpty" => true ],
      [ "state" => "booked", "requiredIsNotEmpty" => true ],
      [ "state" => "canceled", "requiredIsNotEmpty" => true ],

      [ "state" => "payed", "doValidate" => "checkBeleg", ],
      [ "state" => "payed", "doValidate" => "checkKontenplan", ],
      [ "state" => "booked", "doValidate" => "checkBeleg", ],
      [ "state" => "booked", "doValidate" => "checkKontenplan", ],
      [ "state" => "booked", "doValidate" => "checkSum", ],
    ],
    "checkBeleg" => [
      [ "id" => "zahlung.grund.beleg",
        "otherForm" => [
          [ "type" => "rechnung-zuordnung", "state" => "ok", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "rechnung-zuordnung", "state" => "instructed", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "rechnung-zuordnung", "state" => "payed", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "auslagenerstattung", "state" => "ok", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "auslagenerstattung", "state" => "instructed", "validate" => "postEdit",
            "fieldMatch" => [
              [ "otherFormFieldName" => "genehmigung.jahr", "thisFormFieldName" => "zahlung.datum", "condition" => "matchYear", ],
            ],
          ],
          [ "type" => "auslagenerstattung", "state" => "payed", "validate" => "postEdit",
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
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.grund.table" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.group2" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.grund.beleg" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.grund.hinweis" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.grund.einnahmen" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEditPartiell.field.zahlung.grund.ausgaben" => [
      [ "state" => "payed", "group" => "ref-finanzen", ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
    ],
    "canBeCloned" => false,
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

