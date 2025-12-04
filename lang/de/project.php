<?php

return [
    'stateNames' => [
        'draft' => 'Entwurf',
        'wip' => 'Beantragt',
        'ok-by-hv' => 'Genehmigt durch HV (nicht verkündet)',
        'need-stura' => 'Warte auf Gremien-Beschluss',
        'ok-by-stura' => 'Genehmigt durch Gremien-Beschluss',
        'done-hv' => 'verkündet durch HV',
        'done-other' => 'Genehmigt',
        'revoked' => 'Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)',
        'terminated' => 'Abgeschlossen (keine weiteren Ausgaben)',
    ],
    'stateActions' => [
        'draft' => '',
        'wip' => 'beantragen',
        'ok-by-hv' => '',
        'need-stura' => '',
        'ok-by-stura' => '',
        'done-hv' => '',
        'done-other' => '',
        'revoked' => 'zurückziehen / ablehnen',
        'terminated' => 'beenden',
    ],
    'error' => [
        'posten_illegal_deleted' => 'Posten mit denen noch eine Abrechnung existiert dürfen nicht gelöscht werden!'
    ],
    'view' => [
        'header' => [
            'title' => 'Projekt',
            'created_at' => 'Erstellt am',
            'change_status' => 'Status ändern',
            'edit' => 'Bearbeiten',
            'delete' => 'Löschen',
        ],
        'budget_summary' => [
            'total' => 'Gesamtbudget',
            'spent' => 'Ausgegeben',
            'available' => 'Verfügbar',
            'usage' => 'Auslastung',
        ],
        'approval' => [
            'heading' => 'Genehmigung',
            'legal_basis' => 'Rechtsgrundlage',
            'none' => 'Keine Angabe',
        ],
        'details' => [
            'heading' => 'Projektdetails',
            'name' => 'Projektname',
            'responsible' => 'Projektverantwortlich',
            'org' => 'Organisation',
            'period' => 'Projektzeitraum',
            'from' => 'von',
            'to' => 'bis',
            'link' => 'Ergänzender Link',
            'none' => 'Keine Angabe',
        ],
        'budget_table' => [
            'heading' => 'Budget & Posten',
            'subheading' => 'Budgetplanung mit Ausgabenverfolgung',
            'nr' => 'Nr.',
            'group' => 'Ein/Ausgabengruppe',
            'remark' => 'Bemerkung',
            'title' => 'Titel',
            'income' => 'Einnahmen (Soll)',
            'expenses' => 'Ausgaben (Soll)',
            'claimed' => 'Claimed (Ist)',
            'status' => 'Status',
            'sum' => 'Summe',
        ],
        'description' => [
            'heading' => 'Projektbeschreibung',
        ],
        'expenses' => [
            'heading' => 'Im Projekt vorhandene Abrechnungen',
            'none' => 'Keine',
        ],
        'status_modal' => [
            'heading' => 'Status wechseln',
            'placeholder' => 'Neuen Status auswählen',
            'cancel' => 'Abbrechen',
            'save' => 'Speichern',
        ],
        'delete_modal' => [
            'heading' => 'Wirklich Löschen?',
            'intro' => 'Dieses Projekt kann endgültig gelöscht werden wenn:',
            'conditions' => [
                'owner' => 'du Projektersteller*in oder Haushaltsverantwortliche*r bist',
                'no_expenses' => 'im Projekt keine Abrechnungen (mehr) vorhanden sind',
            ],
            'warning' => 'Wenn das Projekt gelöscht wird, werden alle Daten dazu entfernt und können nicht wieder hergestellt werden.',
            'cancel' => 'Abbrechen',
            'confirm' => 'Unwiderruflich Löschen',
        ],
    ],
];
