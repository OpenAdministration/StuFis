<?php

return [
    // Page titles
    'page_titles' => [
        'projects' => 'Projektübersicht',
        'stura' => 'StuRa-Sitzung',
        'hv' => 'Haushaltsverantwortliche*r',
        'kv' => 'Kassenverantwortliche*r',
        'belege' => 'Fehlende Belege',
        'export_bank' => 'Überweisungen',
    ],

    // Main tabs
    'tabs' => [
        'my_committees' => 'Meine Gremien',
        'all_committees' => 'Alle Gremien',
        'open_projects' => 'Offene Projekte',
    ],

    // Todo tabs
    'todo_tabs' => [
        'missing_receipts' => 'Belege fehlen',
        'hv' => 'Haushaltsverantwortliche*r',
        'kv' => 'Kassenverantwortliche*r',
        'transfers' => 'Überweisungen',
    ],

    // Table headers
    'table' => [
        'name' => 'Name',
        'recipient' => 'Zahlungsempfänger',
        'income' => 'Einnahmen',
        'expenses' => 'Ausgaben',
        'status' => 'Status',
        'project' => 'Projekt',
        'expense' => 'Abrechnung',
        'organization' => 'Organisation',
        'project_start' => 'Projektbeginn',
        'last_changed' => 'Zuletzt geändert',
        'iban' => 'IBAN',
        'reference' => 'Verwendungszweck',
        'amount' => 'Auszuzahlen',
    ],

    // Summary
    'summary' => [
        'submitted' => 'Eingereicht',
        'paid' => 'Ausgezahlt',
    ],

    // Alerts
    'alerts' => [
        'no_committee_title' => 'Schade!',
        'no_committee_message' => 'Leider scheinst du noch kein Gremium zu haben.',
        'no_projects_title' => 'Hinweis',
        'no_projects_message' => 'In deinen Gremien wurden in diesem Haushaltsjahr noch keine Projekte angelegt. Fange doch jetzt damit an!',
        'create_new_project' => 'Neues Projekt erstellen',
        'no_open_projects_title' => 'Super!',
        'no_open_projects_message' => 'Es gibt in diesem Haushaltsjahr keine offenen Projekte. Für den Haushaltsabschluss ist das wirklich eine gute Sache!',
    ],

    // Unassigned projects
    'unassigned_projects' => 'Nicht zugeordnete Projekte',

    // StuRa section
    'stura' => [
        'title' => 'Projekte für die nächste Sitzung',
        'to_vote' => 'Vom Gremium abzustimmen',
        'for_announcement' => 'Zur Verkündung',
        'no_projects_to_vote' => 'Aktuell keine Projekte zur Abstimmung.',
        'no_projects_for_announcement' => 'Aktuell keine Projekte zur Verkündung.',
    ],

    // HV section
    'hv' => [
        'projects_to_review' => 'Zu prüfende Projekte',
        'expenses_to_review' => 'Sachliche Richtigkeit der Auslagen prüfen',
        'no_projects' => 'Aktuell keine Projekte zu prüfen.',
        'no_expenses' => 'Aktuell keine Auslagen zu prüfen.',
    ],

    // KV section
    'kv' => [
        'expenses_to_review' => 'Rechnerische Richtigkeit der Auslagen prüfen',
        'no_expenses' => 'Aktuell keine Auslagen zu prüfen.',
    ],

    // Belege section
    'belege' => [
        'missing_receipts' => 'Belege fehlen',
        'no_missing' => 'Alle Belege vollständig.',
    ],

    // Bank export section
    'bank' => [
        'pending_transfers' => 'Zu überweisen',
        'no_transfers_title' => 'Alles erledigt!',
        'no_transfers_message' => 'Aktuell liegen keine Überweisungen vor.',
        'total' => 'Gesamt',
    ],

    // Expense states
    'expense_states' => [
        'draft' => 'Entwurf',
        'wip' => 'In Bearbeitung',
        'ok' => 'Genehmigt',
        'instructed' => 'Angewiesen',
        'booked' => 'Gebucht',
        'revocation' => 'Widerrufen',
        'terminated' => 'Abgeschlossen',
    ],
];
