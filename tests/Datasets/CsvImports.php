<?php

dataset('csvImports', [
    [
        'header' => [
            0 => 'Bezeichnung Auftragskonto',
            1 => 'IBAN Auftragskonto',
            2 => 'BIC Auftragskonto',
            3 => 'Bankname Auftragskonto',
            4 => 'Buchungstag',
            5 => 'Valutadatum',
            6 => 'Name Zahlungsbeteiligter',
            7 => 'IBAN Zahlungsbeteiligter',
            8 => 'BIC (SWIFT-Code) Zahlungsbeteiligter',
            9 => 'Buchungstext',
            10 => 'Verwendungszweck',
            11 => 'Betrag',
            12 => 'Waehrung',
            13 => 'Saldo nach Buchung',
            14 => 'Bemerkung',
            15 => 'Kategorie',
            16 => 'Steuerrelevant',
            17 => 'Glaeubiger ID',
            18 => 'Mandatsreferenz',
        ],
        'data' => [
            1 => ['AStA - Basiskonto', 'DE12429644757213399722',
                'NKZUVJYQ0P5', 'Meine Bank', '2024-06-05', '2024-06-05', 'Person 5', 'DE63365090851878254100',
                'IHHVRZIL', 'Gutschrift', 'Entry 5', '420.99', 'EUR', '18474.22', '', 'Sonstiges', '', '', '', ],
            2 => ['AStA - Basiskonto', 'DE12429644757213399722',
                'NKZUVJYQ0P5', 'Meine Bank', '2024-06-05', '2024-06-05', 'Person 4', 'DE76169365307164900914',
                'MWFYLYEL', 'Basislastschrift', 'Entry 4', '-43.40', 'EUR', '18053.23', '', 'Sonstiges', '', '', '', ],
            3 => ['AStA - Basiskonto', 'DE12429644757213399722',
                'NKZUVJYQ0P5', 'Meine Bank', '2024-06-04', '2024-06-04', 'Person 3', 'DE67615841552532938268',
                'MVGUQVQWJZY', 'Gutschrift', 'Entry 3', '2.00', 'EUR', '18096.63', '', 'Sonstiges', '', '', '', ],
            4 => ['AStA - Basiskonto', 'DE12429644757213399722',
                'NKZUVJYQ0P5', 'Meine Bank', '2024-06-03', '2024-06-04', 'Person 2', 'DE79181333728582849451',
                'GENODEF1SDE', 'Gutschrift', 'Entry 2', '5.00', 'EUR', '18094.63', '', 'Sonstiges', '', '', '', ],
            5 => ['AStA - Basiskonto', 'DE12429644757213399722',
                'NKZUVJYQ0P5', 'Meine Bank', '2024-06-03', '2024-06-03', 'Person 1', 'DE73447318315829961821',
                'DZFPEL2K', 'Euro-Überweisung', 'Entry 1', '-13.14', 'EUR', '18089.63', '', 'Sonstiges', '', '', '', ],
        ],
    ],

]);
