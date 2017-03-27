<?php

$config = [
  "title" => "Rechnung direkt an den StuRa (Beleg)",
  "shortTitle" => "Rechnung direkt an den StuRa (Beleg)",
  "state" => [ "draft"          => [ "unvollständige Eingabe" ],
               "proforma"       => [ "Vorläufiger Beleg für Vorkasse" ],
               "ok"             => [ "Rechnung eingangen" ],
               "revoked"        => [ "Zurückgezogen (Duplikat o.ä.)", ],
             ],
  "proposeNewState" => [
    "draft" => [ "proforma", "ok" ],
    "proforma" => [ "ok", "revoked" ],
    "ok" => [ "revoked" ],
  ],
  "buildFrom" => [ "projekt-intern", "" ],
  "createState" => "draft",
  "categories" => [
    "need-action" => [
      [ "state" => "draft", "hasPermission" => "isResponsible" ],
      [ "state" => "proforma", "hasPermission" => "isResponsible" ],
    ],
    "finished" => [
      [ "state" => "ok" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "doValidate" => "checkTeilbetrag", ],
      [ "state" => "proforma", "requiredIsNotEmpty" => true ],
      [ "state" => "ok", "requiredIsNotEmpty" => true ],
      [ "state" => "proforma", "id" => "rechnung.proforma", "value" => "is:notEmpty" ],
      [ "state" => "ok", "id" => "rechnung.proforma", "value" => "is:empty" ],
    ],
    "checkTeilbetrag" => [
      [ "sum" => "expr:%ausgaben - %ausgaben.zuordnet + %einnahmen.zugeordnet", "maxValue" => 0.00, ],
    ],
  ],
  "permission" => [
    "isResponsible" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isCorrectGremium" ],
    ],
    "canRead" => [
      [ "group" => "ref-finanzen" ],
      [ "group" => "konsul" ],
      [ "hasPermission" => "isResponsible" ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
      [ "state" => "draft", "hasPermission" => "isCorrectGremium" ],
    ],
    "canDelete" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
      [ "state" => "draft", "hasPermission" => "isCorrectGremium" ],
    ],
    "canBeLinked" => [
      [ "state" => "ok", ],
      [ "state" => "proforma", ],
    ],
    # append new files if proforma
    "canCreate" => [
      [ "hasPermission" => [ "isCreateable" ] ],
    ],
    "canBeCloned" => [
      [ "group" => "ref-finanzen", ],
    ],
    "canStateChange.from.draft.to.proforma" => true,
    "canStateChange.from.draft.to.ok" => true,
    "canStateChange.from.proforma.to.ok" => true,
    "canStateChange.from.proforma.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
  ],
  "postNewStateActions" => [
    "from.draft.to.proforma"   => [ [ "copy" => true, "type" => "rechnung-zuordnung", "revision" => "v1", "redirect" => true ],
                                    [ "sendMail" => true, "attachForm" => true ] ],
    "from.draft.to.ok"         => [ [ "copy" => true, "type" => "rechnung-zuordnung", "revision" => "v1", "redirect" => true ],
                                    [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "rechnung-beleg", $config );

