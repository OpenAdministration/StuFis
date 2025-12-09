<?php

return [
    'stateNames' => [
        'draft' => 'Entwurf',
        'wip' => 'Beantragt',
        'ok-by-hv' => 'Genehmigt durch HV (nicht verkündet)',
        'done-hv' => 'verkündet durch HV',
        'need-stura' => 'Warte auf Gremien-Beschluss',
        'ok-by-stura' => 'Genehmigt durch Gremien-Beschluss',
        'done-other' => 'Genehmigt',
        'revoked' => 'Abgelehnt / Zurückgezogen',
        'terminated' => 'Abgeschlossen',
    ],
    'stateActions' => [
        'draft' => 'bearbeiten',
        'wip' => 'beantragen',
        'ok-by-hv' => 'geprüft',
        'done-hv' => 'genehmigen',
        'need-stura' => 'geprüft',
        'ok-by-stura' => 'genehmigen',
        'done-other' => 'genehmigen',
        'revoked' => 'zurückziehen / ablehnen',
        'terminated' => 'beenden',
    ],
    'error' => [
        'posten_illegal_deleted' => 'Posten mit denen noch eine Abrechnung existiert dürfen nicht gelöscht werden!'
    ],
    'view' => [
        'summary_cards' => [
            'state' => 'Status',
            'budgetplan' => 'Haushaltsplan',
            'out_total' => 'Geplante Ausgaben',
            'in_total' => 'Geplante Einnahmen',
            'out_available' => 'Noch Verfügbar',
            'in_available' => 'Noch Verfügbar',
            'out_ratio' => 'Auslastung',
            'in_ratio' => 'Auslastung',
        ],
        'header' => [
            'title' => 'Projekt',
            'created_at' => 'Erstellt am',
            'change_status' => 'Status ändern',
            'edit' => 'Bearbeiten',
            'delete' => 'Löschen',
            'new-expense' => 'Abrechnung erstellen'
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
            'total_in' => 'Einnahmen',
            'total_out' => 'Ausgaben',
        ],
        'state-modal' => [
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
