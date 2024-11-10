<?php

namespace framework;

use framework\baseclass\Enum;

class LoadGroups extends Enum
{
    public const __default = [];

    public const SELECTPICKER = [
        'js' => ['bootstrap-select.min'],
        'css' => ['bootstrap-select.min'],
    ];

    public const DATEPICKER = [
        'js' => ['bootstrap-datepicker.min', 'bootstrap-datepicker.de.min'],
        'css' => ['bootstrap-datepicker.min'],
    ];

    public const FILEINPUT = [
        'js' => ['fileinput.min', 'fileinput.de', 'fileinput-themes/gly/theme'],
        'css' => ['fileinput.min'],
    ];

    public const IBAN = [
        'js' => ['iban'],
        'css' => [],
    ];

    public const AUSLAGEN = [
        'js' => ['auslagen'],
        'css' => [],
    ];

    public const CHAT = [
        'js' => ['chat'],
        'css' => ['chat'],
    ];

    public const BOOKING = [
        'js' => ['booking'],
        'css' => [],
    ];

    public const MODALS = [
        'js' => ['modals'],
        'css' => [],
    ];
}
