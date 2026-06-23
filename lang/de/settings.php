<?php

return [
    'headline' => 'Einstellungen',
    'sub-headline' => 'Anwendungsweite Konfiguration verwalten',
    'saved' => 'Einstellungen gespeichert.',
    'save-button' => 'Speichern',

    'groups' => [
        'general' => 'Allgemein',
        'project' => 'Projekte',
        'committees' => 'Organisationen',
        'features' => 'Funktionen',
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
        'label' => 'Ergänzender-Link anzeigen',
        'description' => 'Blendet das Ergänzender-Link-Feld im Projektformular ein.',
    ],
    'protocol-label' => [
        'label' => 'Beschriftung des Ergänzenden-Links',
    ],
    'committee-mode' => [
        'label' => 'Organisations-Modus',
        'description' => 'Wie Organisationen für Nutzer aufgelöst werden.',
        'options' => [
            'filter' => 'Filtern (Schnittmenge aus Login-Provider und Liste)',
            'all' => 'Statische Organisationen (ignoriert Login Provider, für alle User die gleichen Gremien)',
            'raw' => 'Ungefiltert (ignoriert Liste, reicht Login-Provider direkt durch)',
        ],
    ],
    'committee-data' => [
        'label' => 'Organisationen Liste',
        'description' => 'Ein Organisation pro Zeile.',
    ],
    'tax-active' => [
        'label' => 'Umsatzsteuer aktivieren',
        'description' => 'Schaltet die Umsatzsteuer-Funktion (USt-Titel) im Haushaltsplan frei.',
    ],
    'datev' => [
        'label' => 'DATEV-Export anzeigen',
        'description' => 'Zeigt den DATEV-Export-Button in der Haushaltsplan-Ansicht.',
    ],

    'legal-bases' => [
        'heading' => 'Rechtsgrundlagen',
        'description' => 'Auswählbare Rechtsgrundlagen im Projektformular. Der Slug ist die gespeicherte Kennung und kann nach dem Anlegen nicht mehr geändert werden.',
        'add' => 'Hinzufügen',
        'remove' => 'Entfernen',
        'reorder' => 'Zum Sortieren ziehen',
        'empty' => 'Noch keine Rechtsgrundlagen angelegt.',
        'in-use' => 'Diese Rechtsgrundlage wird noch von Projekten verwendet und kann nicht gelöscht werden. Bitte stattdessen deaktivieren.',
        'slug' => 'Slug',
        'label' => 'Bezeichnung',
        'label-additional' => 'Beschriftung des Zusatzfeldes',
        'placeholder' => 'Platzhalter des Zusatzfeldes',
        'hint-text' => 'Hinweistext',
        'active' => 'Aktiv',
    ],
];
