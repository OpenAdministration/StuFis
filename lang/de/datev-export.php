<?php

return [
    'headline'     => 'DATEV Export',
    'sub-headline' => 'Buchungen als DATEV-Exportdatei herunterladen',

    'budget-plan' => [
        'select-label' => 'Haushaltsplan auswählen',
    ],

    'pdf' => [
        'label'       => 'PDFs exportieren',
        'description' => 'Belege und Anhänge als PDF-Dateien in den Export einschließen',
    ],

    'date-field' => [
        'label'       => 'Stichtag',
        'description' => 'Welches Datum bestimmt, ob eine Auslage in den Zeitraum fällt.',
        'options'     => [
            'booking_date'          => 'Buchungsdatum',
            'expense_created_date'  => 'Erstelldatum der Auslage',
            'earliest_receipt_date' => 'Frühestes Belegdatum',
            'earliest_payment_date' => 'Frühestes Zahlungsdatum',
        ],
    ],

    'timespan' => [
        'label'       => 'Exportzeitraum',
        'placeholder' => 'Von – Bis auswählen',
    ],

    'summary' => [
        'incomplete' => 'Bitte Haushaltsplan und Zeitraum wählen, um die Anzahl der Auslagen zu sehen.',
    ],

    'preview' => [
        'refresh' => 'Aktualisieren',
        'loading' => 'Vorschau wird geladen …',
        'stale'   => 'Filter geändert – Vorschau aktualisieren.',
        'columns' => [
            'invoice'    => 'Abrechnung',
            'name'       => 'Name',
            'project'    => 'Projekt',
            'beleg-date' => 'Belegdatum',
            'bookings'   => 'Buchungen',
            'paid-at'    => 'Bezahlt am',
        ],
    ],

    'expenses-found' => 'Auslagen gefunden',
    'export-button'  => 'DATEV Export herunterladen',
    'exporting'      => 'Export wird erstellt …',
];
