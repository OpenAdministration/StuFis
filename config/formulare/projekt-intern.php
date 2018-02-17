<?php

$config = [
    "title" => "Internes Projekt",
    "shortTitle" => "Projekt (intern)",
    "state" => ["draft" => ["Entwurf",],
        "wip" => ["Beantragt", "beantragen"],
        "ok-by-hv" => ["Genehmigt durch HV (nicht verkündet)",],
        "need-stura" => ["Warte auf StuRa-Beschluss",],
        "ok-by-stura" => ["Genehmigt durch StuRa-Beschluss",],
        "done-hv" => ["Genehmigt durch HV und verkündet in StuRa Sitzung",],
        "done-other" => ["Genehmigt ohne Verkündung",],
        "revoked" => ["Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)", "zurückziehen / ablehnen",],
        "terminated" => ["Abgeschlossen (keine weiteren Ausgaben)", "beenden",],
    ],
    "proposeNewState" => [
        "draft" => ["wip"],
        "wip" => ["need-stura", "ok-by-hv", "revoked", "done-other"],
        "ok-by-hv" => ["done-hv"],
        "need-stura" => ["ok-by-stura", "revoked"],
        "done-hv" => ["terminated"],
        "done-other" => ["terminated"],
        "ok-by-stura" => ["terminated"],
    ],
    "printMode" => [
        "zahlungsanweisung" =>
            ["title" => "Titelseite drucken",
                "condition" => [
                    ["state" => "draft", "group" => "ref-finanzen"],
                    ["state" => "ok-by-stura", "group" => "ref-finanzen"],
                ],
            ],
    ],
    "createState" => "draft",
    //"buildFrom" => [
    //    [ "projekt-intern-antrag" /* type */, "done" /* state */ ],
    //],
    "validate" => [
        "postEdit" => [
            ["state" => "wip", "requiredIsNotEmpty" => true],
            ["state" => "ok-by-hv", "requiredIsNotEmpty" => true],
            ["state" => "need-stura", "requiredIsNotEmpty" => true],
            ["state" => "ok-by-stura", "requiredIsNotEmpty" => true],
            ["state" => "done-hv", "requiredIsNotEmpty" => true],
            ["state" => "done-other", "requiredIsNotEmpty" => true],
            ["state" => "terminated", "requiredIsNotEmpty" => true],
            # passende Rechtsgrundlage ausgewählt
            ["state" => "ok-by-stura", "doValidate" => "checkRechtsgrundlage",],
            ["state" => "ok-by-hv", "doValidate" => "checkRechtsgrundlage",],
            ["state" => "done-hv", "doValidate" => "checkRechtsgrundlage",],
            ["state" => "done-other", "doValidate" => "checkRechtsgrundlage",],
    
            ["state" => "ok-by-stura", "doValidate" => "checkSturaBeschluss",],
    
            ["state" => "done-hv", "doValidate" => "checkSturaBeschlussHV",],
            ["state" => "done-hv", "doValidate" => "checkGremiumBeschlussHV",],
            ["state" => "ok-by-hv", "doValidate" => "checkGremiumBeschlussHV",],
    
            ["state" => "done-other", "doValidate" => "checkOtherBeschluss",],
            # Titel ausgewählt
            ["state" => "ok-by-stura", "doValidate" => "checkTitel",],
            ["state" => "ok-by-hv", "doValidate" => "checkTitel",],
            ["state" => "done-hv", "doValidate" => "checkTitel",],
            ["state" => "done-other", "doValidate" => "checkTitel",],
            # Derzeit nicht erzwungen: Kostenstelle ausgewählt
            #      [ "state" => "ok-by-stura", "doValidate" => "checkKonto", ],
            #      [ "state" => "ok-by-hv", "doValidate" => "checkKonto", ],
            #      [ "state" => "done-hv", "doValidate" => "checkKonto", ],
            #      [ "state" => "done-other", "doValidate" => "checkKonto", ],
        ],
    ],
    /*"categories" => [
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
            [ "state" => "need-stura", "hasPermission" => "isProjektLeitung" ],
            [ "state" => "need-stura", "creator" => "self" ],
        ],
        "wait-stura" => [
            [ "state" => "need-stura", "hasPermission" => "isCorrectGremium" ],
            [ "state" => "need-stura", "hasPermission" => "isProjektLeitung" ],
            [ "state" => "need-stura", "creator" => "self" ],
            [ "state" => "need-stura", "group" => "ref-finanzen-hv" ],
            [ "state" => "need-stura", "group" => "stura" ],
        ],
    ],*/
    "permission" => [
        /* each permission has a name and a list of sufficient conditions.
     * Each condition is an AND clause.
     * This is merged with form data that can add extra permissions not given here
     * hasPermission: true if all given permissions are present
     * group: true if all given groups are present
     * field: true if all given checks are ok
     */
        "canRead" => [
            ["creator" => "self"],
            ["hasPermission" => "isCorrectGremium"],
            ["hasPermission" => "isProjektLeitung"],
            ["group" => "ref-finanzen"],
            ["group" => "konsul"],
            ["state" => "need-stura", "group" => "stura"],
            ["state" => "ok-by-hv", "group" => "stura"],
        ],
        "canBeCloned" => [
            ["group" => "ref-finanzen",],
        ],
        "canEditPartiell" => [
            ["state" => "ok-by-hv", "group" => "ref-finanzen",],
        ],
        "canEditPartiell.field.genehmigung.recht.int.sturabeschluss" => [
            ["state" => "ok-by-hv", "group" => "ref-finanzen",],
        ],
        "canEdit" => [
            ["state" => "draft", "hasPermission" => "canRead",],
            ["state" => "wip", "group" => "ref-finanzen",],
            ["state" => "need-stura", "group" => "ref-finanzen",],
        ],
        "canBeLinked" => [
            ["state" => "ok-by-hv",],
            ["state" => "ok-by-stura",],
            ["state" => "done-hv",],
            ["state" => "done-other",],
            ["state" => "terminated", "group" => "ref-finanzen"],
        ],
        "canCreate" => [
            ["hasPermission" => ["canEdit", "isCreateable"]],
            ["hasPermission" => ["canRead", "isCreateable"]],
        ],
        "canEditState" => [
            ["group" => "ref-finanzen-hv",],
        ],
        "canDelete" => [
            ["state" => "draft", "hasPermission" => "canEdit"],
        ],
        "canStateChange.from.draft.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.wip.to.draft" => [
            ["hasPermission" => "canEditState"],
        ],
        # Genehmigung durch StuRa
        "canStateChange.from.wip.to.need-stura" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.need-stura.to.ok-by-stura" => [
            ["hasPermission" => "canEditState"],
        ],
        # Undo
        "canStateChange.from.need-stura.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.ok-by-stura.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        # Genehmigung durch HV
        "canStateChange.from.need-stura.to.ok-by-hv" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.wip.to.ok-by-hv" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.ok-by-hv.to.done-hv" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.wip.to.done-other" => [
            ["hasPermission" => "canEditState"],
        ],
        # Undo
        "canStateChange.from.ok-by-hv.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.done-hv.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        "canStateChange.from.done-other.to.wip" => [
            ["hasPermission" => "canEditState"],
        ],
        # Rücknahme
        "canRevoke" => [
            ["creator" => "self"],
            ["hasPermission" => "isCorrectGremium"],
            ["hasPermission" => "isProjektLeitung"],
            ["group" => "ref-finanzen"],
        ],
        "canStateChange.from.wip.to.revoked" => [
            ["hasPermission" => "canRevoke"],
        ],
        "canStateChange.from.ok-by-hv.to.revoked" => [
            ["hasPermission" => "canRevoke"],
        ],
        "canStateChange.from.ok-by-stura.to.revoked" => [
            ["hasPermission" => "canRevoke"],
        ],
        "canStateChange.from.done-hv.to.revoked" => [
            ["hasPermission" => "canRevoke"],
        ],
        "canStateChange.from.done-other.to.revoked" => [
            ["hasPermission" => "canRevoke"],
        ],
        "canUnrevoke" => [
            ["group" => "ref-finanzen-hv"],
        ],
        "canStateChange.from.revoked.to.ok-by-hv" => [
            ["hasPermission" => ["canUnrevoke", "isBeschlussHV"]],
        ],
        "canStateChange.from.revoked.to.ok-by-stura" => [
            ["hasPermission" => ["canUnrevoke", "isBeschlussStuRa"]],
        ],
        "canStateChange.from.revoked.to.done-hv" => [
            ["hasPermission" => ["canUnrevoke", "isBeschlussHV"]],
        ],
        "canStateChange.from.revoked.to.done-other" => [
            ["hasPermission" => ["canUnrevoke", "isBeschlussOther"]],
        ],
        "canStateChange.from.revoked.to.wip" => [
            ["hasPermission" => ["canUnrevoke"]],
        ],
        # Beendung
        "canTerminate" => [
            ["creator" => "self"],
            ["hasPermission" => "isCorrectGremium"],
            ["hasPermission" => "isProjektLeitung"],
            ["group" => "ref-finanzen"],
        ],
        "canStateChange.from.ok-by-stura.to.terminated" => [
            ["hasPermission" => "canTerminate"],
        ],
        "canStateChange.from.done-hv.to.terminated" => [
            ["hasPermission" => "canTerminate"],
        ],
        "canStateChange.from.done-other.to.terminated" => [
            ["hasPermission" => "canTerminate"],
        ],
        "canUnterminate" => [
            ["group" => "ref-finanzen-hv"],
        ],
        "canStateChange.from.terminated.to.ok-by-stura" => [
            ["hasPermission" => ["canTerminate", "isBeschlussStuRa"]],
        ],
        "canStateChange.from.terminated.to.done-hv" => [
            ["hasPermission" => ["canTerminate", "isBeschlussHV"]],
        ],
        "canStateChange.from.terminated.to.done-other" => [
            ["hasPermission" => ["canTerminate", "isBeschlussOther"]],
        ],
    ],
    "postNewStateActions" => [
        "create.wip" => [["sendMail" => true, "attachForm" => true, "text" => "EDIT"]],
        "from.wip.to.ok-by-hv" => [["sendMail" => true, "attachForm" => true]],
        "from.wip.to.need-stura" => [["sendMail" => true, "attachForm" => true]],
        "from.need-stura.to.ok-by-stura" => [["sendMail" => true, "attachForm" => true]],
        "from.need-stura.to.ok-by-hv" => [["sendMail" => true, "attachForm" => true]],
        "from.ok-by-hv.to.revoked" => [["sendMail" => true, "attachForm" => false]],
        "from.ok-by-stura.to.revoked" => [["sendMail" => true, "attachForm" => false]],
        "from.need-stura.to.revoked" => [["sendMail" => true, "attachForm" => false]],
        "from.done-hv.to.revoked" => [["sendMail" => true, "attachForm" => false]],
        "from.done-hv.to.terminated" => [["sendMail" => true, "attachForm" => false]],
        "from.done-other.to.revoked" => [["sendMail" => true, "attachForm" => false]],
        "from.done-other.to.terminated" => [["sendMail" => true, "attachForm" => false, "text" => ""]],
        "from.ok-by-stura.to.terminated" => [["sendMail" => true, "attachForm" => false, "text" => "Was geschieht jetzt:\n\nDer Antrag auf Auslagenerstattung muss, am besten gleich vom Antragssteller, über den Drucker-Button im Formular ausgedruckt werden. Der Originalbeleg ist anzuheften und beim Referat Finanzen abzugeben. Falls der Antragssteller nicht der Antragssteller des Projektes sein sollte, muss letzterer die Auslagenerstellung noch prüfen und einreichen.\n\nDer Antrag auf Auslagenerstattung kann erst bearbeitet werden, wenn der Originalbeleg beim Referat Finanzen eingegangen ist und vom Antragsstellers des Projektes eingereicht wurde.\n\nDer Antrag muss von jeweils einem Haushaltsverantwortlichen und einem Kassenverantwortlichen aus dem Referat Finanzen geprüft und genehmigt werden. Ist dies geschehen und das Geld wurde überwiesen, so ist dies im blauen Feld über dem Antrag ersichtlich.\n\nWurden alle Auslagenerstattungen eines Projektes eingereicht und es fallen keine weiteren Kosten an, so ist der Status des Projektes durch den Antragssteller auf \"abgeschlossen\" zu stellen. Dies ist möglich, in dem man auf dem blauen Feld auf den Stift klickt. Auch ein Zurückziehen des Antrages ist so möglich.\n\n\n\nDas Referat Finanzen wird mit Eingang des Antrages benachrichtigt. Je nach aktueller Menge der Projekte kann es zu Verzögerungen der Bearbeitung kommen.\n\n\nBei Fragen oder Anmerkungen zum Antrag könnt ihr euch gern an ref-finanzen@tu-ilmenau.de wenden.\n\nBei Fragen oder Anmerkungen technischer Natur (z.B. Verbesserungen) könnt ihr euch an ref-it@tu-ilmenau.de wenden."]],
    ],
];

registerFormClass("projekt-intern", $config);

