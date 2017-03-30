<?php

# Vorkasse: Feld für "Leistung (noch nicht) erbracht / Rechnungsbeleg folgt"
# Sammelrechnung: mehrere Rechnungen anlegen, eine Sammelrechnung als gemeinsame Referenz mit InvRef-Check-Tabelle und Validate-Constraint für Erledigung.

$config = [
  "title" => "Rechnung direkt an den StuRa (Projektzuordnung)",
  "shortTitle" => "Rechnung direkt an den StuRa (Projektzuordnung)",
  "state" => [ "draft"          => [ "Noch keine Zahlung benötigt" ],
               "submitted"      => [ "Zahlung beantragt", "Zahlung beantragen", ],
               "ok-by-hv"       => [ "KV fehlt", "als Haushaltsverantwortlicher genehmigen" ],
               "ok-by-kv"       => [ "HV fehlt", "als Kassenverantwortlicher genehmigen" ],
               "ok"             => [ "Zahlung genehmigt" ],
               "instructed"     => [ "Zahlung angewiesen", ],
               "payed"          => [ "Bezahlt (Kontoauszug)", ],
               "revoked"        => [ "Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "submitted" ],
    "submitted" => [ "ok-by-hv", "ok-by-kv", "revoked" ],
    "ok-by-hv" => [ "ok", "revoked" ],
    "ok-by-kv" => [ "ok", "revoked" ],
    "ok" => [ "instructed", ],
  ],
  "buildFrom" => [ "rechnung-beleg" ],
  "createState" => "draft",
  "categories" => [
    "need-action" => [
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==" ], # empty field
      [ "state" => "draft", "hasPermission" => "isResponsible" ],
      [ "state" => "draft", "group" => "ref-finanzen" ],
      [ "state" => "submitted", "group" => "ref-finanzen" ],
      [ "state" => "ok-by-hv", "group" => "ref-finanzen-kv" ],
      [ "state" => "ok-by-kv", "group" => "ref-finanzen-hv" ],
      [ "hasPermission" => [ "isUnvollstaendig", "isResponsible" ], ],
    ],
    "need-payment" => [
      [ "state" => "ok", "group" => "ref-finanzen", "field:iban" => "!=" ], # wenn IBAN nicht leer dann überweisen
    ],
    "_export_sct" => [
      [ "state" => "ok", "group" => "ref-finanzen", "field:iban" => "!=" ], # wenn IBAN nicht leer dann überweisen
    ],
    "_need_booking_payment" => [
      [ "state" => "ok", "group" => "ref-finanzen" ],
      [ "state" => "instructed", "group" => "ref-finanzen" ],
    ],
    "wait-action" => [
      [ "state" => "ok", "group" => "ref-finanzen", "field:iban" => "==" ], # wenn IBAN nicht leer dann warte auf Lastschrift
    ],
    "finished" => [
      [ "state" => "instructed" ],
      [ "state" => "payed" ],
      [ "state" => "revoked" ],
    ],
  ],
  "validate" => [
    "postEdit" => [
      [ "id" => "teilprojekt.beleg", "validate" => "checkTeilbetrag", ],
      [ "state" => "ok", "requiredIsNotEmpty" => true ],
      [ "state" => "instructed", "requiredIsNotEmpty" => true ],
      [ "state" => "payed", "requiredIsNotEmpty" => true ],
      # richtige Summen bezahlt
      [ "state" => "payed", "doValidate" => "checkZahlung", ], # hier sollten die Beträge stimmen
      [ "state" => "submitted", "doValidate" => "checkBetragZugeordnet"],
      [ "state" => "ok", "doValidate" => "checkBetragZugeordnet"],
      [ "state" => "payed", "doValidate" => "checkBetragZugeordnet"],
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
  ],
  "permission" => [
    "isResponsible" => [
      [ "creator" => "self" ],
      [ "hasPermission" => "isProjektLeitung" ],
      [ "field:teilrechnung.projekt" => "==", "hasPermission" => "isCorrectGremium" ], # noch keine Projektzuordnung
      [ "field:teilrechnung.projekt" => "==", "field:teilrechnung.org.name" => "==" ], # weder Projekt noch Gremienzuordnung
    ],
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
      [ "hasPermission" => "isCorrectGremium" ],
      [ "hasPermission" => "isProjektLeitung" ],
      [ "field:teilrechnung.org.name" => "==" ],
    ],
    "canEdit" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
      [ "state" => "draft", "hasPermission" => "isResponsible", ],
      [ "state" => "submitted", "group" => "ref-finanzen", ],
    ],
    "canCreate" => [
      [ "hasPermission" => "isCreateable" ],
    ],
    "canBeCloned" => true,
    "canStateChange.from.draft.to.submitted" => [
      [ "hasPermission" => "isResponsible" ],
    ],
    "canStateChange.from.submitted.to.ok-by-hv" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.submitted.to.ok-by-kv" => [
      [ "group" => "ref-finanzen-kv" ],
    ],
    "canStateChange.from.ok-by-kv.to.ok" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.ok-by-hv.to.ok" => [
      [ "group" => "ref-finanzen-kv" ],
    ],
    "canStateChange.from.ok.to.instructed" => [
      [ "group" => "ref-finanzen", "field:iban" => "!=" ], # nicht anweisen wenn IBAN leer
    ],
    "canStateChange.from.instructed.to.payed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.payed" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.submitted.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok-by-hv.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok-by-kv.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.payed.to.ok" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canDStateChange.from.draft.to.revoked" => [
      [ "group" => "ref-finanzen" ],
      [ "hasPermission" => "isResponsible" ],
    ],
    "canStateChange.from.revoked.to.draft" => [
      [ "group" => "ref-finanzen" ],
      [ "hasPermission" => "isResponsible" ],
    ],
  ],
  "postNewStateActions" => [
    "from.draft.to.submitted" => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok-by-hv.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok-by-kv.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"      => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "rechnung-zuordnung", $config );

