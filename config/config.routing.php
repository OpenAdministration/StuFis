<?php
$routing = [
    'path' => '',
    'type' => 'path',
    'controller' => 'menu',
    'action' => 'mygremium',
    'not_found' => '404',
    'method' => 'GET',
    'load' => [],
    'children' => [
        [
            'path' => 'menu',
            'type' => 'path',
            'children' => [
                [
                    'path' => 'mykonto',
                    'type' => 'pattern',
                    'param' => 'action'
                ],
                [
                    'path' => 'konto',
                    'type' => 'path',
                    'action' => 'konto',
                    'navigation' => 'konto',
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
                    'path' => '(booking|booking-history)',
                    'type' => 'pattern',
                    'param' => 'action',
                    'navigation' => 'booking',
                    'load' => [
                        LoadGroups::SELECTPICKER,
                        LoadGroups::BOOKING,
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
                    'path' => 'save-booking',
                    'type' => 'path',
                    'action' => 'save-booking',
                    'navigation' => 'booking',
                    'method' => 'POST',
                ],
                [
                    'path' => 'check-booking',
                    'type' => 'path',
                    'action' => 'check-booking',
                    'navigation' => 'booking',
                ],
                [
                    'path' => 'mygremium',
                    'type' => 'path',
                    'action' => 'mygremium',
                    'navigation' => 'mygremium',
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
                [
                    'path' => 'allgremium',
                    'type' => 'path',
                    'action' => 'allgremium',
                    'navigation' => 'allgremium',
                ],
            ],
        ],
        [
            'path' => 'hhp',
            'type' => 'path',
            'controller' => 'hhp',
            'action' => 'pick-hhp',
            'navigation' => 'hhp',
            'children' => [
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
        ],
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
        ],
        [
            'path' => 'rest',
            'type' => 'path',
            'controller' => 'error',
            'action' => '403',
            'method' => 'POST',
            'children' => [
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
                            ]
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
                    'groups' => 'ref-finanzen',
                    'action' => 'update-konto',
                ],
                [
                    'path' => 'booking',
                    'type' => 'path',
                    'controller' => 'rest',
                    'action' => 'new-booking',
                    'groups' => 'ref-finanzen',
                    'children' => [
                        [
                            'path' => 'cancel',
                            'type' => 'path',
                            'controller' => 'rest',
                            'action' => 'cancel-booking',
                        ],
                    ],
                ]
            ]
        ],
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
        ]
    ]
];



