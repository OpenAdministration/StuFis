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
                    'path' => '(mykonto|booking|konto|booking-history)',
                    'type' => 'pattern',
                    'param' => 'action'
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
            'action' => 'pick',
            'navigation' => 'hhp',
            'children' => [
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'action' => 'view',
                    'param' => 'hhp-id',
                    'load' => [
                        LoadGroups::SELECTPICKER,
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
                LoadGroups::SELECTPICKER
            ],
            'children' => [
                [
                    'path' => '\d+',
                    'type' => 'pattern',
                    'action' => 'view',
                    'param' => 'pid',
                    'load' => [
                        LoadGroups::SELECTPICKER,
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
                                    'load' => [],
                                    'children' => [
                                        [
                                            'path' => 'edit',
                                            'type' => 'path',
                                            'action' => 'edit'
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
                    'children' => [
                        [
                            'path' => 'edit',
                            'type' => 'path',
                            'action' => 'edit',
                        ]
                    ]
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
                                    'path' => '(updatecreate|filedelete|state)',
                                    'type' => 'pattern',
                                    'param' => 'mfunction',
                                    'match' => 0,
                                ],
                            ]
                        ],
                    ]
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

