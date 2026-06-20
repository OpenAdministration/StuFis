<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validierung Sprachzeilen
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen enthalten die Standard-Fehlermeldungen, die von
    | der Validator-Klasse verwendet werden. Einige dieser Regeln haben mehrere
    | Versionen wie die Größenregeln. Es steht Ihnen frei, jede dieser Meldungen
    | hier zu ändern.
    |
    */

    // TODO: Translate

    'accepted' => 'Das :attribute muss akzeptiert werden.',
    'accepted_if' => 'Das :attribute muss akzeptiert werden, wenn :other :value ist.',
    'active_url' => 'Das :attribute ist keine gültige URL.',
    'after' => 'Das :attribute muss ein Datum nach :date sein.',
    'after_or_equal' => 'Das :attribute muss ein Datum nach oder gleich dem :date sein.',
    'alpha' => 'Das :attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das :attribute darf nur Buchstaben, Zahlen, Bindestriche, Unterstriche enthalten.',
    'username' => 'Darf nur Buchstaben, Zahlen, Bindestriche, Unterstriche und Punkte enthalten.',
    'alpha_num' => 'Das :attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => 'Das :attribute muss ein Array sein.',
    'before' => 'Das :attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute muss ein Datum vor oder gleich :date sein.',

    'between' => [
        'array' => 'Das :attribute muss zwischen :min und :max Elemente haben.',
        'file' => 'Das :attribute muss zwischen :min und :max Kilobytes liegen.',
        'numeric' => 'Das :attribute muss zwischen :min und :max liegen.',
        'string' => 'Das :attribute muss zwischen :min und :max Zeichen liegen.',
    ],
    'boolean' => 'Das Feld :attribute muss wahr oder falsch sein.',
    'confirmed' => 'Die :attribute Bestätigung stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => 'Das :attribute ist kein gültiges Datum.',
    'date_equals' => 'Das :attribute muss ein Datum sein, das gleich dem :date ist.',
    'date_format' => 'Das :attribute entspricht nicht dem Format :format.',
    'declined' => 'Das :attribute muss abgelehnt werden.',
    'declined_if' => 'Das :attribute muss abgelehnt werden, wenn :other :value ist.',
    'different' => 'Das :attribute und :other müssen unterschiedlich sein.',
    'digits' => 'Das :attribute muss :digits Ziffern sein.',
    'digits_between' => 'Das :attribute muss zwischen :min und :max Stellen liegen.',
    'dimensions' => 'Das :attribute hat ungültige Bildabmessungen.',
    'distinct' => 'Das Feld :attribute hat einen doppelten Wert.',
    'email' => 'Die :attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => 'Das :attribute muss mit einem der folgenden enden: :values.',
    'enum' => 'Das ausgewählte :attribute ist ungültig.',
    'exists' => 'Das gewählte :attribute ist ungültig.',
    'file' => 'Das :attribute muss eine Datei sein.',
    'filled' => 'Das Feld :attribute muss einen Wert haben.',
    'gt' => [
        'array' => 'Das :attribute muss mehr als :value Elemente haben.',
        'file' => 'Das :attribute muss größer als :value kilobytes sein.',
        'numeric' => 'Das :attribute muss größer als :value sein.',
        'string' => 'Das :attribute muss größer als :value Zeichen sein.',
    ],
    'gte' => [
        'array' => 'Das :attribute muss :value Elemente oder mehr haben.',
        'file' => 'Das :attribute muss größer oder gleich dem :value kilobytes sein.',
        'numeric' => 'Das :attribute muss größer oder gleich :value sein.',
        'string' => 'Das :attribute muss größer oder gleich :value Zeichen sein.',
    ],
    'image' => 'Das :attribute muss ein Bild sein.',
    'in' => 'Das ausgewählte :attribute ist ungültig.',
    'in_array' => 'Das Feld :attribute existiert nicht in :other.',
    'integer' => 'Das :attribute muss eine ganze Zahl sein.',
    'ip' => 'Das :attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das :attribute muss eine gültige IPv6-Adresse sein.',
    'json' => 'Das :attribute muss ein gültiger JSON-String sein.',
    'lt' => [
        'array' => 'Das :attribute muss weniger als :value Elemente haben.',
        'file' => 'Das :attribute muss kleiner als :value kilobytes sein.',
        'numeric' => 'Das :attribute muss kleiner :value sein.',
        'string' => 'Das :attribute muss kleiner :value Zeichen sein.',
    ],
    'lte' => [
        'array' => 'Das :attribute muss :value Elemente oder weniger haben.',
        'file' => 'Das :attribute muss kleiner oder gleich dem :value kilobytes sein.',
        'numeric' => 'Das :attribute muss kleiner oder gleich :value sein.',
        'string' => 'Das :attribute muss kleiner oder gleich :value Zeichen sein.',
    ],
    'mac_address' => 'Das :attribute muss eine gültige MAC-Adresse sein.',
    'max' => [
        'array' => 'Das :attribute muss :max Elemente oder weniger haben.',
        'file' => 'Das :attribute muss kleiner oder gleich dem :max kilobytes sein.',
        'numeric' => 'Das :attribute muss kleiner oder gleich :max sein.',
        'string' => 'Das :attribute muss kleiner oder gleich :max Zeichen sein.',
    ],
    'extensions' => 'Das :attribute muss die eine der folgenden Dateiendung haben: :values.',
    'mimes' => 'Das :attribute muss eine Datei vom Typ: :values sein.',
    'mimetypes' => 'Das :attribute muss eine Datei vom Typ: :values sein.',
    'min' => [
        'array' => 'Das :attribute muss :min Elemente oder mehr haben.',
        'file' => 'Das :attribute muss größer oder gleich dem :min kilobytes sein.',
        'numeric' => 'Das :attribute muss größer oder gleich :min sein.',
        'string' => 'Das :attribute muss größer oder gleich :min Zeichen sein.',
    ],
    'multiple_of' => 'Das :attribut muss ein Vielfaches von :value sein.',
    'not_in' => 'Das ausgewählte :attribut ist ungültig.',
    'not_regex' => 'Das :attribute Format ist ungültig.',
    'numeric' => 'Das :attribut muss eine Zahl sein.',
    'password' => [
        'letters' => 'Das :attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => 'Das :attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => 'Das :attribute muss mindestens eine Zahl enthalten.',
        'symbols' => 'Das :attribute muss mindestens ein Symbol enthalten.',
        'uncompromised' => 'Das angegebene :attribute ist in einem Datenleck aufgetaucht. Bitte wählen Sie ein anderes :attribute .',
    ],
    'prohibited' => 'Das :attribute feld ist verboten.',
    'prohibited_if' => 'Das :attribute Feld ist verboten, wenn :other :value ist.',
    'prohibited_unless' => 'Das Feld :attribute ist verboten, wenn :other nicht in :values steht.',
    'prohibits' => 'Das :attribute Feld verbietet das Vorhandensein von :other.',
    'regex' => 'Das :attribute Format ist ungültig.',
    'required' => 'Das :attribute Feld ist erforderlich.',
    'required_array_keys' => 'Das :attribute Feld muss Einträge enthalten für: :values.',
    'required_if' => 'Das :attribute Feld ist erforderlich, wenn :other :value ist.',
    'required_unless' => 'Das Feld :attribute ist erforderlich, wenn :other nicht in :values steht.',
    'required_with' => 'Das :attribute Feld ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => 'Das :attribute Feld ist erforderlich, wenn :values vorhanden ist.',
    'required_without' => 'Das Feld :attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => 'Das :attribute Feld ist erforderlich, wenn keines der :values vorhanden ist.',
    'same' => 'Das :attribute und :other müssen übereinstimmen.',
    'size' => [
        'array' => 'Das :attribute muss :ize Elemente oder mehr haben.',
        'file' => 'Das :attribute muss größer oder gleich dem :size kilobytes sein.',
        'numeric' => 'Das :attribute muss größer oder gleich :size sein.',
        'string' => 'Das :attribute muss größer oder gleich :size Zeichen sein.',
    ],
    'starts_with' => 'Das :attribute muss mit einer der folgenden Angaben beginnen: :values.',
    'string' => 'Das :attribute muss ein String sein.',
    'timezone' => 'Das :attribute muss eine gültige Zeitzone sein.',
    'unique' => 'Das :attribute wurde bereits vergeben.',
    'uploaded' => 'Das :attribute konnte nicht hochgeladen werden.',
    'url' => 'Das :attribute muss eine gültige URL sein.',
    'uuid' => 'Das :attribute muss eine gültige UUID sein.',
    'disabled' => 'Kann nicht verändert werden.',
    'uppercase' => 'Das :attribute darf nur aus Großbuchstaben bestehen',
    'and_x_more' => 'und :count weiterer Fehler|und :count weitere Fehler',

    /*
    |--------------------------------------------------------------------------
    | Benutzerdefinierte Validierungssprachenzeilen
    |--------------------------------------------------------------------------
    |
    |  Hier können Sie benutzerdefinierte Validierungsmeldungen für Attribute
    | angeben, indem Sie die Konvention "attribute.rule" zur Benennung der Zeilen
    | verwenden. Dies macht es schnell möglich eine spezifische Sprachzeile für
    | eine bestimmte Attributregel anzugeben.
    |
    */

    'custom' => [
        'name' => [
            'required' => 'Bitte gib einen Namen für das Projekt an.',
            'max' => 'Der Name darf maximal :max Zeichen lang sein.',
        ],
        'responsible' => [
            'required' => 'Bitte gib eine verantwortliche Person an.',
            'email' => 'Die verantwortliche Person muss eine gültige E-Mail-Adresse haben.',
            'max' => 'Die E-Mail-Adresse darf maximal :max Zeichen lang sein.',
        ],
        'org' => [
            'required' => 'Bitte gib die zuständige Organisation an.',
            'max' => 'Die Organisation darf maximal :max Zeichen lang sein.',
        ],
        'protokoll' => [
            'url' => 'Der Link muss eine gültige URL sein.',
        ],
        'recht' => [
            'required' => 'Bitte wähle eine Rechtsgrundlage aus.',
            'exists' => 'Die gewählte Rechtsgrundlage ist ungültig.',
        ],
        'date_start' => [
            'required' => 'Bitte gib ein Startdatum an.',
            'date' => 'Das Startdatum muss ein gültiges Datum sein.',
        ],
        'date_end' => [
            'required' => 'Bitte gib ein Enddatum an.',
            'date' => 'Das Enddatum muss ein gültiges Datum sein.',
            'after' => 'Das Enddatum muss nach dem Startdatum liegen.',
        ],
        'beschreibung' => [
            'required' => 'Bitte gib eine Beschreibung an.',
        ],
        'posts' => [
            'required' => 'Es muss mindestens ein Posten angelegt werden.',
            'min' => 'Es muss mindestens ein Posten angelegt werden.',
        ],
        'posts.*.titel_id' => [
            'required' => 'Bitte wähle einen Haushaltstitel für Posten :position aus.',
            'exists' => 'Der gewählte Haushaltstitel für Posten :position ist ungültig.',
        ],
        'posts.*.name' => [
            'required' => 'Bitte gib eine Bezeichnung für Posten :position an.',
            'max' => 'Die Bezeichnung von Posten :position darf maximal :max Zeichen lang sein.',
        ],
        'posts.*.einnahmen' => [
            'required' => 'Bitte gib die Einnahmen für Posten :position an.',
        ],
        'posts.*.ausgaben' => [
            'required' => 'Bitte gib die Ausgaben für Posten :position an.',
        ],
        'posts.*.bemerkung' => [
            'max' => 'Die Bemerkung von Posten :position darf maximal :max Zeichen lang sein.',
        ],
        'uploads.*' => [
            'uploaded' => 'Der Anhang konnte nicht hochgeladen werden. Bitte versuche es erneut.',
            'extensions' => 'Anhänge müssen im Format PDF, XLSX oder ODS sein.',
            'max' => 'Ein Anhang darf maximal 5 MB groß sein.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Benutzerdefinierte Validierungsattribute
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen werden verwendet, um unseren Attributplatzhalter
    | durch etwas Lesefreundlicheres zu ersetzen, z. B. "E-Mail-Adresse" anstelle von "email".
    | Dies hilft uns einfach, unsere Nachricht ausdrucksstärker zu machen.
    |
    */

    'attributes' => [
        // project
        'name' => 'Name',
        'responsible' => 'Verantwortliche Person',
        'org' => 'Organisation',
        'protokoll' => 'Link',
        'recht' => 'Rechtsgrundlage',
        'recht-additional' => 'Zusätzliche Rechtsgrundlage',
        'date_start' => 'Startdatum',
        'date_end' => 'Enddatum',
        'beschreibung' => 'Beschreibung',
        'posts' => 'Posten',
        'posts.*.titel_id' => 'Haushaltstitel',
        'posts.*.name' => 'Postenbezeichnung',
        'posts.*.einnahmen' => 'Einnahmen',
        'posts.*.ausgaben' => 'Ausgaben',
        'posts.*.bemerkung' => 'Bemerkung',
        'newAttachments' => 'Anhang',
        'newAttachments.*' => 'Anhang',
        'uploads' => 'Anhang',
        'uploads.*' => 'Anhang',
        // unknown
        'email' => 'E-Mail-Adresse',
        'mail' => 'E-Mail-Adresse',
        'start_date' => 'Start Datum',
        'end_date' => 'End Datum',
        'decision_date' => 'Beschluss Datum',
        'username' => 'Nutzer:innenname',
        // CSV Upload
        'mapping.date' => 'Ausführungsdatum',
        'mapping.valuta' => 'Valuta-/Wertstellungsdatum',
        'mapping.type' => 'Transaktionstyp',
        'mapping.empf_iban' => 'Empfänger IBAN',
        'mapping.empf_bic' => 'Empfänger BIC',
        'mapping.empf_name' => 'Empfänger Name',
        'mapping.primanota' => 'Primanota',
        'mapping.value' => 'Wert',
        'mapping.saldo' => 'Saldo',
        'mapping.zweck' => 'Verwendungszweck',
        'mapping.comment' => 'Kommentar',
        'mapping.customer_ref' => 'Kundenreferenz',
        'sync_from' => 'Verwenden ab',
        'sync_until' => 'Verwenden bis',
        'iban' => 'IBAN',
        'short' => 'Kürzel',
    ],
];
