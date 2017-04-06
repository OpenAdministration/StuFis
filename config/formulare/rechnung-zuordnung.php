<?php

# Vorkasse: Feld für "Leistung (noch nicht) erbracht / Rechnungsbeleg folgt"
# Sammelrechnung: mehrere Rechnungen anlegen, eine Sammelrechnung als gemeinsame Referenz mit InvRef-Check-Tabelle und Validate-Constraint für Erledigung.

$config = [
  "title" => "Rechnung direkt an den StuRa (Projektzuordnung)",
  "shortTitle" => "Rechnung direkt an den StuRa (Projektzuordnung)",
  "state" => [ "draft"          => [ "Noch keine Zahlung benötigt" ],
               "submitted"      => [ "Zahlung beantragt", "Zahlung beantragen", ],
               "ok-hv"       => [ "KV fehlt", "als Haushaltsverantwortlicher genehmigen" ],
               "ok-kv"       => [ "HV fehlt", "als Kassenverantwortlicher genehmigen" ],
               "ok"             => [ "Zahlung genehmigt" ],
               "instructed"     => [ "Zahlung angewiesen", ],
               "payed"          => [ "Bezahlt (Kontoauszug)", ],
               "revoked"        => [ "Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)", "zurückziehen", ],
             ],
  "proposeNewState" => [
    "draft" => [ "submitted" ],
    "submitted" => [ "ok-hv", "ok-kv", "revoked" ],
    "ok-hv" => [ "ok", "revoked" ],
    "ok-kv" => [ "ok", "revoked" ],
  ],
  "buildFrom" => [ "rechnung-beleg" ],
  "createState" => "draft",
  "categories" => [
    "need-action" => [
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "draft" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "submitted" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "ok-kv" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "ok-hv" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "ok" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "instructed" ], # empty field
      [ "hasPermission" => "isResponsible", "field:rechnung.leistung" => "==", "state" => "payed" ], # empty field
      [ "state" => "draft", "hasPermission" => "isResponsible" ],
      [ "state" => "draft", "group" => "ref-finanzen" ],
      [ "state" => "submitted", "group" => "ref-finanzen" ],
      [ "state" => "ok-hv", "group" => "ref-finanzen-kv" ],
      [ "state" => "ok-kv", "group" => "ref-finanzen-hv" ],
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
      [ "state" => "ok-hv", "doValidate" => "checkBetragZugeordnet"],
      [ "state" => "ok-kv", "doValidate" => "checkBetragZugeordnet"],
      [ "state" => "ok", "doValidate" => "checkBetragZugeordnet"],
      [ "state" => "payed", "doValidate" => "checkBetragZugeordnet"],
      # richtige Formularversion aka Haushaltsjahr
      [ "doValidate" => "checkKostenstellenplan", ],
      [ "doValidate" => "checkHaushaltsplan", ],
      # sachliche und rechnerische Richtigkeit (Unterschrift)
      [ "state" => "ok-hv", "doValidate" => "checkRichtigkeitHV", ],
      [ "state" => "ok-kv", "doValidate" => "checkRichtigkeitKV", ],
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
      # Projekt gesetzt
      [ "state" => "submitted", "doValidate" => "checkProjekt"],
      [ "state" => "ok-hv", "doValidate" => "checkProjekt"],
      [ "state" => "ok-kv", "doValidate" => "checkProjekt"],
      [ "state" => "ok", "doValidate" => "checkProjekt"],
      [ "state" => "payed", "doValidate" => "checkProjekt"],
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
    "canDelete" => [
      [ "state" => "draft", "group" => "ref-finanzen", ],
      [ "state" => "draft", "hasPermission" => "isResponsible", ],
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
    "canStateChange.from.submitted.to.ok-hv" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.ok-hv.to.submitted" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.submitted.to.ok-kv" => [
      [ "group" => "ref-finanzen-kv" ],
    ],
# fixup state identifiers
    "canStateChange.from.ok-by-kv.to.ok-kv" => [
      [ "group" => "ref-finanzen-kv" ],
    ],
    "canStateChange.from.ok-kv.to.submitted" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok-kv.to.ok" => [
      [ "group" => "ref-finanzen-hv" ],
    ],
    "canStateChange.from.ok-hv.to.ok" => [
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
    "canStateChange.from.ok-hv.to.revoked" => [
      [ "group" => "ref-finanzen" ],
    ],
    "canStateChange.from.ok-kv.to.revoked" => [
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
    "from.ok-hv.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok-kv.to.ok"     => [ [ "sendMail" => true, "attachForm" => true ] ],
    "from.ok.to.revoked"      => [ [ "sendMail" => true, "attachForm" => true ] ],
  ],
];

registerFormClass( "rechnung-zuordnung", $config );

