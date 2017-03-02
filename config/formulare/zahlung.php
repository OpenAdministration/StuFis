<?php

$config = [
  "title" => "Zahlung",
  "shortTitle" => "Zahlung",
  "state" => [ "payed" => [ "Gezahlt", ],
               "booked" => [ "Gezahlt und gebucht", ],
               "cancelled" => [ "Storniert", ],
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

