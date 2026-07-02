<?php

// AUTO-GENERATED demo budget fixture (from the legacy demo dump). plans -> groups -> titels;
// leaf ids are preserved so demo bookings/posts referencing titel_id still resolve.
return [
    1 => [
        'von' => '2023-04-01',
        'bis' => '2024-03-31',
        'state' => 'final',
        'groups' => [
            1 => [
                'name' => 'laufende Einnahmen',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 1,
                        'name' => 'Semesterbeiträge',
                        'nr' => 'E.1.1',
                        'value' => '100000.00',
                    ],
                    1 => [
                        'id' => 2,
                        'name' => 'Zinseinnahmen',
                        'nr' => 'E.1.2',
                        'value' => '0.00',
                    ],
                ],
            ],
            2 => [
                'name' => 'Stura-Dienstleistungen',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 3,
                        'name' => 'Theaterfahrten',
                        'nr' => 'E.2.1',
                        'value' => '500.00',
                    ],
                    1 => [
                        'id' => 4,
                        'name' => 'Exkursionen',
                        'nr' => 'E.2.2',
                        'value' => '500.00',
                    ],
                ],
            ],
            3 => [
                'name' => 'Gremienarbeit- und Projekte',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 5,
                        'name' => 'Fachschaftsräte',
                        'nr' => 'E.3.1',
                        'value' => '0.00',
                    ],
                    1 => [
                        'id' => 6,
                        'name' => 'FSR EI',
                        'nr' => 'E.3.1.1',
                        'value' => '0.00',
                    ],
                    2 => [
                        'id' => 7,
                        'name' => 'FSR IA',
                        'nr' => 'E.3.1.2',
                        'value' => '0.00',
                    ],
                    3 => [
                        'id' => 8,
                        'name' => 'FSR MB',
                        'nr' => 'E.3.1.3',
                        'value' => '0.00',
                    ],
                    4 => [
                        'id' => 9,
                        'name' => 'FSR MN',
                        'nr' => 'E.3.1.4',
                        'value' => '0.00',
                    ],
                    5 => [
                        'id' => 10,
                        'name' => 'FSR WM',
                        'nr' => 'E.3.1.5',
                        'value' => '0.00',
                    ],
                    6 => [
                        'id' => 11,
                        'name' => 'StuRa-Projekte',
                        'nr' => 'E.3.2',
                        'value' => '0.00',
                    ],
                    7 => [
                        'id' => 12,
                        'name' => 'Erstiwoche',
                        'nr' => 'E.3.3',
                        'value' => '5000.00',
                    ],
                ],
            ],
            4 => [
                'name' => 'Rückzahlungen und Gebühren',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 13,
                        'name' => 'Kontogebühren',
                        'nr' => 'A.1.1',
                        'value' => '200.00',
                    ],
                    1 => [
                        'id' => 14,
                        'name' => 'Angestellte',
                        'nr' => 'A.1.2',
                        'value' => '40000.00',
                    ],
                    2 => [
                        'id' => 15,
                        'name' => 'Versicherungen',
                        'nr' => 'A.1.3',
                        'value' => '1000.00',
                    ],
                    3 => [
                        'id' => 16,
                        'name' => 'Transferkonto',
                        'nr' => 'A.1.4',
                        'value' => '0.00',
                    ],
                ],
            ],
            5 => [
                'name' => 'Ausgaben für Ausstattung',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 17,
                        'name' => 'Telefon und Faxdienste',
                        'nr' => 'A.2.1',
                        'value' => '50.00',
                    ],
                    1 => [
                        'id' => 18,
                        'name' => 'Bürobedarf',
                        'nr' => 'A.2.2',
                        'value' => '1000.00',
                    ],
                    2 => [
                        'id' => 19,
                        'name' => 'Domains und IT-Dienstleistungen',
                        'nr' => 'A.2.3',
                        'value' => '3000.00',
                    ],
                ],
            ],
            6 => [
                'name' => 'Stura-Dienstleistungen',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 20,
                        'name' => 'Theaterfahrten',
                        'nr' => 'A.3.1',
                        'value' => '1000.00',
                    ],
                    1 => [
                        'id' => 21,
                        'name' => 'Exkursionen',
                        'nr' => 'A.3.2',
                        'value' => '1000.00',
                    ],
                    2 => [
                        'id' => 22,
                        'name' => 'Zeitungen und Zeitschriften',
                        'nr' => 'A.3.3',
                        'value' => '200.00',
                    ],
                    3 => [
                        'id' => 23,
                        'name' => 'Veröffentlichungen',
                        'nr' => 'A.3.4',
                        'value' => '500.00',
                    ],
                ],
            ],
            7 => [
                'name' => 'Gremienarbeit- und Projekte',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 24,
                        'name' => 'Fachschaftsräte',
                        'nr' => 'A.4.1',
                        'value' => '10000.00',
                    ],
                    1 => [
                        'id' => 25,
                        'name' => 'FSR EI',
                        'nr' => 'A.4.1.1',
                        'value' => '2000.00',
                    ],
                    2 => [
                        'id' => 26,
                        'name' => 'FSR IA',
                        'nr' => 'A.4.1.2',
                        'value' => '2000.00',
                    ],
                    3 => [
                        'id' => 27,
                        'name' => 'FSR MB',
                        'nr' => 'A.4.1.3',
                        'value' => '2000.00',
                    ],
                    4 => [
                        'id' => 28,
                        'name' => 'FSR MN',
                        'nr' => 'A.4.1.4',
                        'value' => '2000.00',
                    ],
                    5 => [
                        'id' => 29,
                        'name' => 'FSR WM',
                        'nr' => 'A.4.1.5',
                        'value' => '2000.00',
                    ],
                    6 => [
                        'id' => 30,
                        'name' => 'StuRa-Projekte',
                        'nr' => 'A.4.2',
                        'value' => '30000.00',
                    ],
                    7 => [
                        'id' => 31,
                        'name' => 'Erstiwoche',
                        'nr' => 'A.4.3',
                        'value' => '20000.00',
                    ],
                    8 => [
                        'id' => 32,
                        'name' => 'Reisekosten',
                        'nr' => 'A.4.4',
                        'value' => '2000.00',
                    ],
                    9 => [
                        'id' => 33,
                        'name' => 'Mitgliedsbeiträge',
                        'nr' => 'A.4.5',
                        'value' => '2000.00',
                    ],
                    10 => [
                        'id' => 34,
                        'name' => 'Gremienwahlen',
                        'nr' => 'A.4.6',
                        'value' => '1000.00',
                    ],
                    11 => [
                        'id' => 35,
                        'name' => 'Klausurtagung',
                        'nr' => 'A.4.7',
                        'value' => '6000.00',
                    ],
                ],
            ],
        ],
    ],
    2 => [
        'von' => '2024-04-01',
        'bis' => null,
        'state' => 'final',
        'groups' => [
            8 => [
                'name' => 'laufende Einnahmen',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 74,
                        'name' => 'Semesterbeiträge',
                        'nr' => 'E1.1',
                        'value' => '100000.00',
                    ],
                    1 => [
                        'id' => 75,
                        'name' => 'Zinseinnahmen',
                        'nr' => 'E1.2',
                        'value' => '10.00',
                    ],
                ],
            ],
            9 => [
                'name' => 'Stura-Dienstleistungen',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 36,
                        'name' => 'Theaterfahrten',
                        'nr' => 'E.2.1',
                        'value' => '50.00',
                    ],
                    1 => [
                        'id' => 37,
                        'name' => 'Exkursionen',
                        'nr' => 'E.2.2',
                        'value' => '50.00',
                    ],
                ],
            ],
            10 => [
                'name' => 'Gremienarbeit- und Projekte',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 38,
                        'name' => 'Fachschaftsräte',
                        'nr' => 'E.3.1',
                        'value' => '0.00',
                    ],
                    1 => [
                        'id' => 39,
                        'name' => 'FSR EI',
                        'nr' => 'E.3.1.1',
                        'value' => '0.00',
                    ],
                    2 => [
                        'id' => 40,
                        'name' => 'FSR IA',
                        'nr' => 'E.3.1.2',
                        'value' => '0.00',
                    ],
                    3 => [
                        'id' => 41,
                        'name' => 'FSR MB',
                        'nr' => 'E.3.1.3',
                        'value' => '0.00',
                    ],
                    4 => [
                        'id' => 42,
                        'name' => 'FSR MN',
                        'nr' => 'E.3.1.4',
                        'value' => '0.00',
                    ],
                    5 => [
                        'id' => 43,
                        'name' => 'FSR WM',
                        'nr' => 'E.3.1.5',
                        'value' => '0.00',
                    ],
                    6 => [
                        'id' => 44,
                        'name' => 'StuRa-Projekte',
                        'nr' => 'E.3.2',
                        'value' => '2000.00',
                    ],
                    7 => [
                        'id' => 45,
                        'name' => 'Erstiwoche',
                        'nr' => 'E.3.3',
                        'value' => '2000.00',
                    ],
                ],
            ],
            11 => [
                'name' => 'Semesterbeitragszuweisung',
                'type' => 0,
                'titels' => [
                    0 => [
                        'id' => 46,
                        'name' => 'FSR EI',
                        'nr' => 'E.4.1',
                        'value' => '2000.00',
                    ],
                    1 => [
                        'id' => 47,
                        'name' => 'FSR IA',
                        'nr' => 'E.4.2',
                        'value' => '2000.00',
                    ],
                    2 => [
                        'id' => 48,
                        'name' => 'FSR MB',
                        'nr' => 'E.4.3',
                        'value' => '2000.00',
                    ],
                    3 => [
                        'id' => 49,
                        'name' => 'FSR MN',
                        'nr' => 'E.4.4',
                        'value' => '2000.00',
                    ],
                    4 => [
                        'id' => 50,
                        'name' => 'FSR WM',
                        'nr' => 'E.4.5',
                        'value' => '2000.00',
                    ],
                ],
            ],
            12 => [
                'name' => 'Rückzahlungen und Gebühren',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 51,
                        'name' => 'Kontogebühren',
                        'nr' => 'A.1.1',
                        'value' => '250.00',
                    ],
                    1 => [
                        'id' => 52,
                        'name' => 'Angestellte',
                        'nr' => 'A.1.2',
                        'value' => '51600.00',
                    ],
                    2 => [
                        'id' => 53,
                        'name' => 'Versicherungen',
                        'nr' => 'A.1.3',
                        'value' => '0.00',
                    ],
                    3 => [
                        'id' => 54,
                        'name' => 'Transferkonto',
                        'nr' => 'A.1.4',
                        'value' => '0.00',
                    ],
                ],
            ],
            13 => [
                'name' => 'Ausgaben für Ausstattung',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 55,
                        'name' => 'Telefon und Faxdienste',
                        'nr' => 'A.2.1',
                        'value' => '50.00',
                    ],
                    1 => [
                        'id' => 56,
                        'name' => 'Bürobedarf',
                        'nr' => 'A.2.2',
                        'value' => '500.00',
                    ],
                    2 => [
                        'id' => 57,
                        'name' => 'Domains und IT-Dienstleistungen',
                        'nr' => 'A.2.3',
                        'value' => '1200.00',
                    ],
                ],
            ],
            14 => [
                'name' => 'Stura-Dienstleistungen',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 58,
                        'name' => 'Theaterfahrten',
                        'nr' => 'A.3.1',
                        'value' => '1000.00',
                    ],
                    1 => [
                        'id' => 59,
                        'name' => 'Exkursionen',
                        'nr' => 'A.3.2',
                        'value' => '1000.00',
                    ],
                    2 => [
                        'id' => 60,
                        'name' => 'Zeitungen und Zeitschriften',
                        'nr' => 'A.3.3',
                        'value' => '100.00',
                    ],
                    3 => [
                        'id' => 61,
                        'name' => 'Veröffentlichungen',
                        'nr' => 'A.3.4',
                        'value' => '350.00',
                    ],
                ],
            ],
            15 => [
                'name' => 'Gremienarbeit- und Projekte',
                'type' => 1,
                'titels' => [
                    0 => [
                        'id' => 62,
                        'name' => 'Fachschaftsräte',
                        'nr' => 'A.4.1',
                        'value' => '10000.00',
                    ],
                    1 => [
                        'id' => 63,
                        'name' => 'FSR EI',
                        'nr' => 'A.4.1.1',
                        'value' => '2000.00',
                    ],
                    2 => [
                        'id' => 64,
                        'name' => 'FSR IA',
                        'nr' => 'A.4.1.2',
                        'value' => '2000.00',
                    ],
                    3 => [
                        'id' => 65,
                        'name' => 'FSR MB',
                        'nr' => 'A.4.1.3',
                        'value' => '2000.00',
                    ],
                    4 => [
                        'id' => 66,
                        'name' => 'FSR MN',
                        'nr' => 'A.4.1.4',
                        'value' => '2000.00',
                    ],
                    5 => [
                        'id' => 67,
                        'name' => 'FSR WM',
                        'nr' => 'A.4.1.5',
                        'value' => '2000.00',
                    ],
                    6 => [
                        'id' => 68,
                        'name' => 'StuRa-Projekte',
                        'nr' => 'A.4.2',
                        'value' => '39400.00',
                    ],
                    7 => [
                        'id' => 69,
                        'name' => 'Erstiwoche',
                        'nr' => 'A.4.3',
                        'value' => '30000.00',
                    ],
                    8 => [
                        'id' => 70,
                        'name' => 'Reisekosten',
                        'nr' => 'A.4.4',
                        'value' => '2000.00',
                    ],
                    9 => [
                        'id' => 71,
                        'name' => 'Mitgliedsbeiträge',
                        'nr' => 'A.4.5',
                        'value' => '0.00',
                    ],
                    10 => [
                        'id' => 72,
                        'name' => 'Gremienwahlen',
                        'nr' => 'A.4.6',
                        'value' => '2000.00',
                    ],
                    11 => [
                        'id' => 73,
                        'name' => 'Klausurtagung',
                        'nr' => 'A.4.7',
                        'value' => '4500.00',
                    ],
                ],
            ],
        ],
    ],
];
