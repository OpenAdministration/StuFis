<?php
$routing = [
	'path' => '',
	'type' => 'path',
	'controller' => 'menu',
	'action' => 'index',
	'not_found' => '404',
	'method' => 'GET',
	'children' => [
		[
			'path' => 'menu',
			'type' => 'path',
			'children' => [
				[
					'path' => '(mygremium|mykonto|stura|allgremium|hhp|kv|hv|booking|konto|booking-history)',
					'type' => 'pattern',
					'param' => 'action'
				],
			]
		],
		[
			'path' => 'projekt',
			'type' => 'path',
			'controller' => 'projekt',
			'action' => 'create',
			'children' => [
				[
					'path' => '\d+',
					'type' => 'pattern',
					'action' => 'projekt',
					'param' => 'pid',
					'children' => [
						[
							'path' => 'auslagen',
							'type' => 'path',
							'controller' => 'auslagen',
							'action' => 'create',
							'children' => [
								[
									'path' => '\d+',
									'type' => 'pattern',
									'action' => 'view',
									'param' => 'aid',
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
							'param' => 'action'
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