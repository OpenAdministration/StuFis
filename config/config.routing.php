<?php

use framework\LoadGroups;

return [
    'path' => '',
    'type' => 'path',
    'controller' => 'menu',
    'action' => 'mygremium',
    'not_found' => '404',
    'method' => 'GET',
    'load' => [],
    'children' => [
        [
            'path' => 'konto',
            'type' => 'path',
            'action' => 'bank',
            'navigation' => 'konto',
            'controller' => 'booking',
            'load' => [
                LoadGroups::SELECTPICKER,
                LoadGroups::DATEPICKER,
                LoadGroups::MODALS,
            ],
            'children' => [
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'param' => 'hhp-id',
                    'children' => [
                        [
                            'path' => '(bank|kasse|sparbuch)',
                            'type' => 'pattern',
                            'param' => 'action',
                        ]
                    ],
                ],
                [
                    'path' => 'credentials',
                    'type' => 'path',
                    'controller' => 'fints',
                    'action' => 'pick-my-credentials',
                    'groups' => 'ref-finanzen-kv',
                    'children' => [
                        [
                            'path' => 'new',
                            'type' => 'path',
                            'action' => 'new-credentials',
                        ],
                        [
                            'path' => '\d+',
                            'type' => 'pattern',
                            'param' => 'credential-id',
                            'action' => 'pick-sepa-action',
                            'children' => [
                                [
                                    'path' => 'tan-mode',
                                    'type' => 'path',
                                    'action' => 'pick-tan-mode',
                                    'children' => [
                                        [
                                            'path' => '\d+',
                                            'type' => 'pattern',
                                            'param' => 'tan-mode-id',
                                            'children' => [
                                                [
                                                    'path' => 'medium',
                                                    'type' => 'path',
                                                    'action' => 'pick-tan-medium',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'path' => 'sepa',
                                    'type' => 'path',
                                    'action' => 'pick-sepa-konto',
                                ],
                                [
                                    'path' => '([A-Z]{2}[0-9]{6})',
                                    'type' => 'pattern',
                                    'param' => 'short-iban',
                                    'action' => 'sepa-details',
                                    'children' => [
                                        [
                                            'path' => 'import',
                                            'type' => 'path',
                                            'action' => 'new-sepa-konto-import',
                                        ],
                                    ],
                                ],
                                [
                                    'path' => 'delete',
                                    'type' => 'path',
                                    'action' => 'delete-credentials',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], // konto
        [
            'path' => 'booking',
            'type' => 'path',
            'navigation' => 'booking',
            'action' => 'instruct',
            'controller' => 'booking',
            'load' => [
                LoadGroups::SELECTPICKER,
                LoadGroups::MODALS,
                LoadGroups::BOOKING,
            ],
            'children' => [
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'param' => 'hhp-id',
                    'load' => [
                        LoadGroups::SELECTPICKER,
                        LoadGroups::MODALS,
                    ],
                    'children' => [
                        [
                            'path' => 'history',
                            'type' => 'path',
                            'action' => 'history',
                        ],
                        [
                            'path' => 'text',
                            'type' => 'path',
                            'action' => 'confirm-instruct',
                        ],
                        [
                            'path' => 'instruct',
                            'type' => 'path',
                            'action' => 'instruct',
                            'load' => [
                                LoadGroups::BOOKING,
                                LoadGroups::MODALS,
                                LoadGroups::SELECTPICKER,
                            ]
                        ],
                    ]
                ],
            ]
        ], // booking
        [
            'path' => 'menu',
            'type' => 'path',
            'children' => [
                [
                    'path' => 'mykonto',
                    'type' => 'path',
                    'action' => 'mykonto',
                    'navigation' => 'mykonto',
                ],
                [
                    'path' => '(mygremium|allgremium|mystuff|search)',
                    'type' => 'pattern',
                    'param' => 'action',
                    'navigation' => 'overview',
                    'load' => [
                        LoadGroups::SELECTPICKER,
                    ],
                    'children' => [
                        [
                            'path' => '\d+',
                            'type' => 'pattern',
                            'param' => 'hhp-id',
                        ],
                    ],
                ],
                [
                    'path' => 'extern',
                    'type' => 'path',
                    'action' => 'extern',
                    'navigation' => 'overview',
                    'groups' => "ref-finanzen",

                ],
                [
                    'path' => 'hv',
                    'type' => 'path',
                    'action' => 'hv',
                    'navigation' => 'hv',
                ],
                [
                    'path' => 'kv',
                    'type' => 'path',
                    'action' => 'kv',
                    'navigation' => 'kv',
                    'children' => [
                        [
                            'path' => 'exportBank',
                            'type' => 'path',
                            'action' => 'exportBank',
                        ]
                    ]
                ],
                [
                    'path' => 'stura',
                    'type' => 'path',
                    'action' => 'stura',
                    'navigation' => 'stura',
                ],
            ],
        ], // menu
        [
            'path' => 'hhp',
            'type' => 'path',
            'controller' => 'hhp',
            'action' => 'pick-hhp',
            'navigation' => 'hhp',
            'children' => [
                [
                    'path' => 'import',
                    'type' => 'path',
                    'action' => 'import',
                    'groups' => 'ref-finanzen-hv',
                    'load' => [LoadGroups::DATEPICKER],
                    'children' => [
                        [
                            'path' => 'preview',
                            'type' => 'path',
                            'action' => 'preview',
                            'method' => 'POST',
                            'load' => [LoadGroups::MODALS],
                        ],
                    ],
                ],
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'action' => 'view-hhp',
                    'param' => 'hhp-id',
                    'children' => [
                        [
                            'path' => 'titel',
                            'type' => 'path',
                            'children' => [
                                [
                                    'path' => '\d+',
                                    'type' => 'pattern',
                                    'action' => 'view-titel',
                                    'param' => 'titel-id',
                                    'load' => [
                                        LoadGroups::SELECTPICKER,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], // hhp
        [
            'path' => 'projekt',
            'type' => 'path',
            'controller' => 'projekt',
            'action' => 'create',
            'load' => [
                LoadGroups::DATEPICKER,
                LoadGroups::SELECTPICKER,
                LoadGroups::CHAT,
            ],
            'children' => [
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'action' => 'view',
                    'param' => 'pid',
                    'load' => [
                        LoadGroups::SELECTPICKER,
                        LoadGroups::CHAT,
                    ],
                    'children' => [
                        [
                            'path' => 'auslagen',
                            'type' => 'path',
                            'controller' => 'auslagen',
                            'action' => 'create',
                            'load' => [
                                LoadGroups::DATEPICKER,
                                LoadGroups::SELECTPICKER,
                                LoadGroups::FILEINPUT,
                                LoadGroups::AUSLAGEN,
                            ],
                            'children' => [
                                [
                                    'path' => '\d+',
                                    'type' => 'pattern',
                                    'action' => 'view',
                                    'param' => 'aid',
                                    'load' => [
                                        LoadGroups::DATEPICKER,
                                        LoadGroups::SELECTPICKER,
                                        LoadGroups::FILEINPUT,
                                        LoadGroups::AUSLAGEN,
                                        LoadGroups::CHAT,
                                    ],
                                    'children' => [
                                        [
                                            'path' => 'edit',
                                            'type' => 'path',
                                            'action' => 'edit',
                                            'load' => [
                                                LoadGroups::DATEPICKER,
                                                LoadGroups::SELECTPICKER,
                                                LoadGroups::FILEINPUT,
                                                LoadGroups::AUSLAGEN,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'path' => '(edit|history)',
                            'type' => 'pattern',
                            'action' => 'edit',
                            'param' => 'action',
                            'load' => [
                                LoadGroups::DATEPICKER,
                                LoadGroups::SELECTPICKER
                            ],
                        ]
                    ]
                ],
                [
                    'path' => 'create',
                    'type' => 'path',
                    'action' => 'create',
                ]
            ]
        ], // projekt
        [
            'path' => 'rest',
            'type' => 'path',
            'controller' => 'error',
            'action' => '403',
            'method' => 'POST',
            'children' => [
                [
                    'path' => 'mirror',
                    'type' => 'path',
                    'controller' => 'rest',
                    'action' => 'mirror',
                ],
                [
                    'path' => 'clear-session',
                    'type' => 'path',
                    'controller' => 'rest',
                    'action' => 'clear-session',
                ],
                [
                    'path' => 'forms',
                    'type' => 'path',
                    'controller' => 'error',
                    'action' => '403',
                    'children' => [
                        [
                            'path' => '(projekt)(.*)',
                            'type' => 'pattern',
                            'param' => 'id',
                            'match' => 1,
                            'controller' => 'rest',
                            'action' => 'projekt',
                            'is_suffix' => true
                        ],
                        [
                            'path' => 'auslagen',
                            'type' => 'path',
                            'controller' => 'rest',
                            'action' => 'auslagen',
                            'children' => [
                                [
                                    'path' => '(updatecreate|filedelete|state|belegpdf)',
                                    'type' => 'pattern',
                                    'param' => 'mfunction',
                                    'match' => 0,
                                ],
                                [
                                    'path' => 'zahlungsanweisung',
                                    'type' => 'pattern',
                                    'param' => 'mfunction',
                                    'groups' => 'ref-finanzen',
                                    'match' => 0,
                                ],
                            ]
                        ],
                        [
                            'path' => 'extern',
                            'type' => 'path',
                            'controller' => 'rest',
                            'action' => 'extern',
                            'groups' => 'ref-finanzen',
                            'children' => [
                                [
                                    'path' => '\d+',
                                    'type' => 'pattern',
                                    'param' => 'eid',
                                    'children' => [
                                        [
                                            'path' => '\d+',
                                            'type' => 'pattern',
                                            'param' => 'vid',
                                            'children' => [
                                                [
                                                    'path' => '(zahlungsanweisung)',
                                                    //updatecreate|filedelete|stat|belegpdf|
                                                    'type' => 'pattern',
                                                    'param' => 'mfunction',
                                                    'match' => 0,
                                                ],
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ],
                        [
                            'path' => 'hhp',
                            'type' => 'path',
                            'controller' => 'rest',
                            'children' => [
                                [
                                    'path' => 'save-import',
                                    'type' => 'path',
                                    'action' => 'save-hhp-import',
                                    'groups' => 'ref-finanzen-hv',
                                ]
                            ],
                        ],
                    ]
                ],
                [
                    'path' => 'chat',
                    'type' => 'path',
                    'controller' => 'rest',
                    'action' => 'chat',
                ],
                [
                    'path' => 'hibiscus',
                    'type' => 'path',
                    'controller' => 'rest',
                    'groups' => 'ref-finanzen-kv',
                    'action' => 'update-konto',
                ],
                [
                    'path' => 'konto',
                    'type' => 'path',
                    'controller' => 'rest',
                    'groups' => 'ref-finanzen-kv',
                    'children' => [
                        [
                            'path' => 'credentials',
                            'type' => 'path',
                            'children' => [
                                [
                                    'path' => 'save',
                                    'type' => 'path',
                                    'action' => 'save-new-konto-credentials'
                                ],
                                [
                                    'path' => 'import-konto',
                                    'type' => 'path',
                                    'action' => 'import-konto'
                                ],
                                [
                                    'path' => 'unlock',
                                    'type' => 'path',
                                    'action' => 'unlock-credentials',
                                ],
                                [
                                    'path' => 'lock',
                                    'type' => 'path',
                                    'action' => 'lock-credentials',
                                ],
                                [
                                    'path' => 'submit-tan',
                                    'type' => 'path',
                                    'action' => 'submit-tan'
                                ]
                            ]
                        ],
                        [
                            'path' => 'tan-mode',
                            'type' => 'path',
                            'children' => [
                                [
                                    'path' => 'save',
                                    'type' => 'path',
                                    'action' => 'save-default-tan-mode'
                                ]
                            ]
                        ],

                    ]
                ],
                [
                    'path' => 'booking',
                    'type' => 'path',
                    'controller' => 'error',
                    'action' => '403',
                    'children' => [
                        [
                            'path' => 'instruct',
                            'type' => 'path',
                            'controller' => 'rest',
                            'children' => [
                                [
                                    'path' => '([0-9]*)',
                                    'type' => 'pattern',
                                    'param' => 'instruct-id',
                                    'children' => [
                                        [
                                            'path' => 'delete',
                                            'type' => 'path',
                                            'controller' => 'rest',
                                            'action' => 'delete-booking-instruct',
                                            'groups' => 'ref-finanzen-hv,ref-finanzen-kv',
                                        ]
                                    ],
                                ],
                                [
                                    'path' => 'new',
                                    'type' => 'path',
                                    'controller' => 'rest',
                                    'action' => 'new-booking-instruct',
                                    'groups' => 'ref-finanzen-hv',
                                ],
                                [
                                    'path' => 'save',
                                    'type' => 'path',
                                    'controller' => 'rest',
                                    'action' => 'confirm-instruct',
                                    'groups' => 'ref-finanzen-kv',
                                ],
                            ],
                        ],
                        [
                            'path' => 'cancel',
                            'type' => 'path',
                            'controller' => 'rest',
                            'action' => 'cancel-booking',
                            'groups' => [
                                'ref-finanzen-kv',
                                'ref-finanzen-hv'
                            ],
                        ]
                    ],
                ],
                [
                    'path' => 'kasse',
                    'type' => 'path',
                    'controller' => 'rest',
                    'groups' => 'ref-finanzen-kv',
                    'children' => [
                        [
                            'path' => 'new',
                            'type' => 'path',
                            'action' => 'save-new-kasse-entry',
                        ]
                    ]
                ],

            ]
        ], // rest
        [
            'path' => 'files',
            'type' => 'path',
            'controller' => 'error',
            'action' => '403',
            'method' => 'GET',
            'children' => [
                [
                    'path' => 'get',
                    'type' => 'path',
                    'controller' => 'error',
                    'action' => '403',
                    'children' => [
                        [
                            'path' => '([0-9a-f]{64})',
                            'type' => 'pattern',
                            'param' => 'key',
                            'match' => 0,
                            'controller' => 'files',
                            'action' => 'get',
                            'children' => [
                                [
                                    'path' => 'fdl',
                                    'type' => 'path',
                                    'param' => 'fdl',
                                    'value' => 1,
                                    'controller' => 'files',
                                    'action' => 'get',
                                ],
                            ]
                        ],
                    ]
                ]
            ]
        ], // files
        [
            'path' => 'export',
            'type' => 'path',
            'controller' => 'error',
            'action' => '403',
            'method' => 'GET',
            'children' => [
                [
                    'path' => 'hhp',
                    'type' => 'path',
                    'controller' => 'hhp',
                    'children' => [
                        [
                            'path' => '([0-9]*)',
                            'type' => 'pattern',
                            'param' => 'hhp-id',
                            'children' => [
                                [
                                    'path' => 'csv',
                                    'type' => 'path',
                                    'action' => 'export-csv',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'path' => 'booking',
                    'type' => 'path',
                    'controller' => 'booking',
                    'children' => [
                        [
                            'path' => '([0-9]*)',
                            'type' => 'pattern',
                            'param' => 'hhp-id',
                            'children' => [
                                [
                                    'path' => 'csv',
                                    'type' => 'path',
                                    'action' => 'export-csv',
                                ],
                                [
                                    'path' => 'zip',
                                    'type' => 'path',
                                    'action' => 'export-zip',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], // export
    ],
];



