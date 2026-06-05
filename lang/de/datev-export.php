<?php

return [
    'headline'     => 'DATEV Export',
    'sub-headline' => 'Gebuchte Abrechnungen als DATEV-Exportdatei für DATEV Belegtransfer herunterladen',

    'budget-plan' => [
        'select-label' => 'Haushaltsplan auswählen',
    ],

    'pdf' => [
        'label'       => 'PDFs exportieren',
        'description' => 'Belege und Anhänge als PDF-Dateien in den Export einschließen',
    ],

    'date-field' => [
        'label'       => 'Stichtag',
        'description' => 'Die Auswahl des Datums bestimmt, ob die Abrechnung in den Zeitraum fällt.',
        'options'     => [
            'booking_date'          => 'Buchungsdatum',
            'expense_created_date'  => 'Erstelldatum der Abrechnung',
            'earliest_receipt_date' => 'Frühestes Belegdatum',
            'earliest_payment_date' => 'Frühestes Zahlungsdatum',
        ],
    ],

    'timespan' => [
        'label'       => 'Exportzeitraum',
        'placeholder' => 'Von – Bis auswählen',
    ],

    'summary' => [
        'incomplete' => 'Bitte Haushaltsplan und Zeitraum wählen, um die Anzahl der Abrechnungen zu sehen.',
    ],

    'preview' => [
        'refresh' => 'Aktualisieren',
        'loading' => 'Vorschau wird geladen …',
        'stale'   => 'Filter geändert – Vorschau aktualisieren.',
        'columns' => [
            'invoice'    => 'Abrechnung',
            'name'       => 'Zahlungsempfänger',
            'project'    => 'Projekt',
            'beleg-date' => 'Belegdatum',
            'bookings'   => 'Buchungen',
            'paid-at'    => 'Bezahlt am',
        ],
    ],

    'expenses-found' => 'Abrechnungen gefunden',
    'export-button'  => 'DATEV Export herunterladen',
    'exporting'      => 'Export wird erstellt …',
];
