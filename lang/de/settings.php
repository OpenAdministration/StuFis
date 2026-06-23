<?php

return [
    'headline' => 'Einstellungen',
    'sub-headline' => 'Anwendungsweite Konfiguration verwalten',
    'saved' => 'Einstellungen gespeichert.',
    'save-button' => 'Speichern',

    'groups' => [
        'general' => 'Allgemein',
        'project' => 'Projekte',
        'committees' => 'Gremien',
        'features' => 'Funktionen',
    ],

    'finance-mail' => [
        'label' => 'Finanz-Kontaktadresse',
        'description' => 'E-Mail-Adresse des Finanzreferats.',
    ],
    'mail-domain' => [
        'label' => 'Mail-Domain',
        'description' => 'Domain, die an reine Projekt-Mail-Aliase angehängt wird.',
    ],
    'description-min' => [
        'label' => 'Mindestlänge der Projektbeschreibung',
        'description' => 'Mindestanzahl sichtbarer Zeichen (HTML wird ignoriert). 0 deaktiviert die Untergrenze.',
    ],
    'description-max' => [
        'label' => 'Maximallänge der Projektbeschreibung',
        'description' => 'Maximalanzahl sichtbarer Zeichen (HTML wird ignoriert). -1 deaktiviert die Obergrenze.',
        'invalid' => 'Die Maximallänge muss -1 (unbegrenzt) oder mindestens so groß wie die Mindestlänge sein.',
    ],
    'protocol-active' => [
        'label' => 'Protokoll-Link anzeigen',
        'description' => 'Blendet das Protokoll-Link-Feld im Projektformular ein.',
    ],
    'protocol-label' => [
        'label' => 'Beschriftung des Protokoll-Links',
    ],
    'committee-mode' => [
        'label' => 'Gremien-Modus',
        'description' => 'Wie Gremien für Nutzer aufgelöst werden.',
        'options' => [
            'filter' => 'Filtern (Schnittmenge aus Login-Provider und Liste)',
            'all' => 'Statische Gremien (ignoriert Login Provider, für alle User die gleichen Gremien)',
            'raw' => 'Ungefiltert (ignoriert Liste, reicht Login-Provider direkt durch)',
        ],
    ],
    'committee-data' => [
        'label' => 'Gremienliste',
        'description' => 'Ein Gremium pro Zeile.',
    ],
    'tax-active' => [
        'label' => 'Umsatzsteuer aktivieren',
        'description' => 'Schaltet die Umsatzsteuer-Funktion (USt-Titel) im Haushaltsplan frei.',
    ],
    'datev' => [
        'label' => 'DATEV-Export anzeigen',
        'description' => 'Zeigt den DATEV-Export-Button in der Haushaltsplan-Ansicht.',
    ],
];
