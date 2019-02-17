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
			'path' => 'konto',
			'type' => 'path',
			'action' => 'bank',
			'navigation' => 'konto',
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
			],
		],
		[
			'path' => 'booking',
			'type' => 'path',
			'navigation' => 'booking',
			'action' => 'instruct',
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
							'action' => 'booking-text',
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
		],
		[
			'path' => 'menu',
			'type' => 'path',
			'children' => [
				[
					'path' => 'mykonto',
					'type' => 'pattern',
					'param' => 'action',
					'navigation' => 'mykonto',
				],
				[
					'path' => '(mygremium|allgremium|mystuff|search)',
					'type' => 'pattern',
					'param' => 'action',
					'navigation' => 'overview',
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
									'path' => '(updatecreate|filedelete|state|belegpdf|zahlungsanweisung)',
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
					'controller' => 'error',
					'action' => '403',
					'children' => [
						[
							'path' => 'instruct',
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



