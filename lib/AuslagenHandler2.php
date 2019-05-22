<?php

/**
 * implement auslagen handler
 *
 * @category          framework
 * @author            michael gnehr
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since             07.05.2018
 * @copyright         Copyright Referat IT (C) 2018 - All rights reserved
 */
class AuslagenHandler2
	extends FormHandlerInterface{
	
	//---------------------------------------------------------
	/* ------- MEMBER VARIABLES -------- */
	/**
	 * list of different groups
	 * and permission
	 *
	 * @var array
	 */
	private static $groups = [
		'editable' => [
			'draft' => ["groups" => ["sgis"]],
			'wip' => ["groups" => ["ref-finanzen"]],
			'ok' => ["groups" => ["ref-finanzen"]],
		],
		'strict_editable' => [
			'groups' => ["ref-finanzen-hv"],
			'dynamic' => [
				'owner',
				'plain_orga'
			],
		],
		'stateless' => [
			'view_creator' => ["groups" => ["ref-finanzen-hv"]],
			'finanzen' => ["groups" => ["ref-finanzen"]],
			'belegpdf' => [
				'groups' => ["ref-finanzen-hv"],
				'dynamic' => [
					'owner',
					'plain_orga'
				]
			],
			'owner' => [
				'dynamic' => [
					'owner',
				],
			],
			'orga' => [
				'dynamic' => [
					'plain_orga',
				],
			],
		]
	];
	/**
	 * possible states inside db
	 *
	 * @var array
	 */
	private static $states = [
		'draft' => ['Entwurf', 'Als Entwurf speichern'],
		'wip' => ['Eingereicht', 'Beantragen'],
		'ok' => ['Genehmigt', 'Genehmigen'],
		'instructed' => ['Zahlung beauftragt', 'Anweisen'],
		'booked' => ['Gebucht', 'Gezahlt und Gebucht'],
		'revocation' => ['Nichtig', '']
	];
	/**
	 * possible substates
	 * may multile subStates are possible
	 * mostly only represented by flags
	 * needed for state diagram
	 * format
	 *    substate =>    [state, required on statechange, label text, action text]
	 *
	 * @var array
	 */
	private static $subStates = [
		'ok-hv' => ['wip', true, 'OK HV', 'als Haushaltsverantwortlicher genehmigen'],
		'ok-kv' => ['wip', true, 'OK KV', 'als Kassenverantwortlicher genehmigen'],
		'ok-belege' => ['wip', true, 'Belege vorhanden', 'Original Belege vorliegend'],
		'revoked' => ['revocation', false, 'Zurückgezogen', 'Zurückziehen'],
		'rejected' => ['revocation', false, 'Abgelehnt', 'Ablehnen'],
		'payed' => ['instructed', true, 'Bezahlt', 'Bezahlt (lt. Kontoauszug)'],
	];
	/**
	 * possible statechanges
	 *    current state => next group => permission
	 *
	 * @var array
	 */
	private static $stateChanges = [
		//mainstate changes
		"draft" => [
			"wip" => true,
		],
		"wip" => [
			"ok" => ["groups" => ["ref-finanzen"]],
			"revocation" => true,
		],
		"ok" => [
			"instructed" => ["groups" => ["ref-finanzen-kv"]],
			"revocation" => true,
		],
		"instructed" => [
			"booked" => ["groups" => ["ref-finanzen-kv"]],
			"revocation" => ["groups" => ["ref-finanzen"]],
		],
		"booked" => [
		],
		"revocation" => [
			"draft" => true,
		],
		// sub state changes
		// turnes map around:
		// target => current state => permission
		'ok-hv' => [
			"wip" => ["groups" => ["ref-finanzen-hv"]],
		],
		'ok-kv' => [
			"wip" => ["groups" => ["ref-finanzen-kv"]],
		],
		'ok-belege' => [
			"wip" => ["groups" => ["ref-finanzen"]],
		],
		'payed' => [
			"instructed" => ["groups" => ["ref-finanzen-kv"]],
		],
		'revoked' => [
			"wip" => [
				'groups' => ["ref-finanzen"],
				'dynamic' => [
					'owner',
					'plain_orga'
				],
			],
			"ok" => [
				'groups' => ["ref-finanzen-hv"],
				'dynamic' => [
					'owner',
					'plain_orga'
				],
			],
		],
		'rejected' => [
			"wip" => ["groups" => ["ref-finanzen"]],
			"ok" => ["groups" => ["ref-finanzen"]],
			"instructed" => ["groups" => ["ref-finanzen"]],
		],
	];
	private static $validFieldKeys = [
		'belege' => '',
		'files' => '',
		"auslagen-name" => '',
		"zahlung-iban" => '',
		"zahlung-name" => '',
		"zahlung-vwzk" => '',
		'address' => '',
		'kv-ok' => '',
		'hv-ok' => '',
		'belege-ok' => '',
	];
	static private $writePermissionAll = [
		"draft" => [],
		"wip" => [],
		"ok" => [],
		"instructed" => [],
		"booked" => [],
		"revocation" => [],
	];
	static private $writePermissionFields = [
		'draft' => [
			'auslagen-name' => ['groups' => ['sgis']],
			'zahlung-name' => ['groups' => ['sgis']],
			'zahlung-iban' => ['groups' => ['sgis']],
			'zahlung-vwzk' => ['groups' => ['sgis']],
			'address' => ['groups' => ['sgis']],
			'belege' => ['groups' => ['sgis']],
			'files' => ['groups' => ['sgis']],
		],
		'wip' => [
			'auslagen-name' => ['groups' => ['ref-finanzen']],
			'zahlung-name' => ['groups' => ['ref-finanzen']],
			'zahlung-iban' => ['groups' => ['ref-finanzen']],
			'zahlung-vwzk' => ['groups' => ['ref-finanzen']],
			'address' => ['groups' => ['ref-finanzen']],
			'belege' => ['groups' => ['ref-finanzen']],
			'files' => ['groups' => ['ref-finanzen']],
		],
		"ok" => [],
		"instructed" => [],
		"booked" => [],
		"revocation" => [],
	];
	static private $visibleFields = [];
	/**
	 * error flag
	 * set in constructor
	 *
	 * @var boolean
	 */
	private $error = null;
	/**
	 * jeson result set
	 *
	 * @var array
	 */
	private $json_result = [];
	/**
	 *
	 * @var DBConnector
	 */
	private $db;
	
	//---------------------------------------------------------
	/* ------- CLASS STATIC VARIABLES -------- */
	/**
	 * Projekt id
	 *
	 * @var int
	 */
	private $projekt_id;
	/**
	 *
	 * @var array
	 */
	private $projekt_data;
	/**
	 * auslagen id
	 *
	 * @var int
	 */
	private $auslagen_id;
	/**
	 * auslagen data
	 *
	 * @var $data
	 */
	private $auslagen_data;
	/**
	 * additional title
	 *
	 * @var string
	 */
	private $title;
	/**
	 * routing info
	 *
	 * @var array
	 */
	private $routeInfo;
	
	//---------------------------------------------------------
	/* ---------- MEMBER GETTER ----------- */
	/**
	 * current state info
	 *
	 * @var array
	 */
	private $stateInfo = [
		'state' => 'draft',
		'substate' => 'draft',
		'date' => '',
		'user' => '',
		'realname' => '',
	];
	/**
	 * contains form ids for submit buttons
	 *
	 * @var unknown
	 */
	private $formSubmitButtons = [];
	private $templater;
	
	//---------------------------------------------------------
	/* ---------- PERMISSION ----------- */
	private $permissionHandler;
	private $stateHandler;
	
	/**
	 * class constructor
	 * check projekt id and auslagen id
	 *
	 * @param array $routeInfo
	 *    required keys:
	 *    action, pid
	 */
	public function __construct($routeInfo){
		$this->error = false;
		//errors ----------------------------
		if (!isset($routeInfo['pid'])){
			$this->error = true;
			ErrorHandler::_errorExit('missing parameter: pid - project id');
		}
		if (!isset($routeInfo['action'])){
			$this->error = true;
			ErrorHandler::_errorExit('missing parameter: action');
		}
		// init variables ---------------------
		$this->routeInfo = $routeInfo;
		$this->db = DBConnector::getInstance();
		$this->projekt_id = $routeInfo['pid'];
		
		// check projekt exists --------------------
		if (!$this->getDbProject())
			return; //set error
		// check auslage exists --------------------
		if (isset($this->routeInfo['aid'])){
			//check auslagen id exists --------------------
			$this->auslagen_id = $routeInfo['aid'];
			if (!$this->getDbAuslagen())
				return;
			if (!$this->getDbBelegePostenFiles())
				return;
			
			//current state
			$this->stateFromAuslagenData();
		}else{
			//current state
			$this->stateInfo = self::state2stateInfo('draft');
		}
		//is editable ------------------------------
		$this->stateInfo['editable'] = false;
		if (isset(self::$groups['editable'][$this->stateInfo['state']])
			&& $this->checkPermissionByMap(self::$groups['editable'][$this->stateInfo['state']])){
			$this->stateInfo['editable'] = true;
		}
		//check if editable and action != create
		// if user is owner or in same organisation or is ref-finanzen
		if ($this->stateInfo['editable'] && $this->routeInfo['action'] != 'create' && !(isset($this->routeInfo['mfunction']) && $this->routeInfo['mfunction'] == 'updatecreate' && !isset($this->routeInfo['aid']))){
			if (!$this->checkPermissionByMap(self::$groups['strict_editable'])){
				$this->stateInfo['editable'] = false;
			}
		}
		$this->stateInfo['project-editable'] = (
			$this->projekt_data['state'] == 'ok-by-stura' ||
			$this->projekt_data['state'] == 'done-hv' ||
			$this->projekt_data['state'] == 'done-other');
		
		//check if there auslage should be edited
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		if (!$this->stateInfo['project-editable'] && !$auth->hasGroup("ref-finanzen")){
			if ($routeInfo['action'] == 'create'
				|| $routeInfo['action'] == 'edit'
				|| ($routeInfo['action'] == 'post' && isset($routeInfo['mfunction']) && $routeInfo['mfunction'] != 'belegpdf')){
				$this->error = 'Für das aktuelle Projekt sind (momentan) keine Abrechnungen möglich.';
				return;
			}
		}
		
		switch ($routeInfo['action']){
			case 'create':
				{
					//page title
					$this->title = ' - Erstellen';
					break;
				};
			case 'edit':
				{
					//page title
					$this->title = ' - Bearbeiten';
					break;
				};
			case 'view':
				{
					$this->stateInfo['editable_link'] = $this->stateInfo['editable'];
					$this->stateInfo['editable'] = false;
					break;
				};
			case 'post':
				{
					break;
				};
			default:
				{
					$this->error = 'Ungültiger request in AuslagenHandler.php';
					return;
				}
		}
		// TODO -------------------------
		//was wird davon noch gebraucht?
		//TODO render auslagen
		if ($this->routeInfo['action'] != 'post'){
			$this->stateHandler = new StateHandler(
				"projekte",
				self::$states,
				self::$stateChanges,
				[],
				[],
				$this->stateInfo['state']
			);
			$this->permissionHandler = new PermissionHandler(
				self::$validFieldKeys,
				$this->stateHandler,
				self::$writePermissionAll,
				self::$writePermissionFields,
				self::$visibleFields,
				$this->stateInfo['editable']
			);
			$this->templater = new FormTemplater($this->permissionHandler);
		}
		//TODO$this->selectable_users = FormTemplater::generateUserSelectable(false);
		//TODO$this->selectable_posten = FormTemplater::generateProjektpostenSelectable(8);
	}
	
	//---------------------------------------------------------
	/* ------- HELPER FUNCTIONS -------- */
	
	/**
	 * get project information from db
	 *
	 * @param boolean $renderError
	 */
	private function getDbProject(){
		$res = $this->db->dbFetchAll(
			"projekte",
			[DBConnector::FETCH_ASSOC],
			[],
			["projekte.id" => $this->projekt_id],
			[],
			["version" => true]
		);
		if (!empty($res)){
			$this->projekt_data = $res[0];
			$this->projekt_data['auslagen'] = [];
		}else{
			$this->error = 'Das Projekt mit der ID: ' . $this->projekt_id . ' existiert nicht. :(<br>';
			return false;
		}
		// get auslagen liste
		$res = $this->db->dbFetchAll(
			'auslagen',
			[DBConnector::FETCH_ASSOC],
			['auslagen.id', 'auslagen.name_suffix', 'auslagen.state', 'auslagen.created'],
			["auslagen.projekt_id" => $this->projekt_id],
			[],
			['auslagen.id' => true]
		);
		if (!empty($res)){
			$aus = [];
			foreach ($res as $row){
				$aus[] = $row;
			}
			$this->projekt_data['auslagen'] = $aus;
		}
		$this->getDbProjektPosten();
		return true;
	}
	
	/**
	 * get auslagen information from db
	 *
	 * @param boolean $renderError
	 */
	private function getDbProjektPosten(){
		$res = $this->db->dbFetchAll(
			"projektposten",
			[DBConnector::FETCH_ASSOC],
			["projektposten.*", "haushaltstitel" => "haushaltstitel.*"],
			["projekt_id" => $this->projekt_id],
			[
				[
					"type" => "inner",
					"table" => "haushaltstitel",
					"on" => [["haushaltstitel.id", "projektposten.titel_id"]]
				],
			]
		);
		$aus = [];
		if (!empty($res)){
			foreach ($res as $row){
				$aus[] = $row;
			}
		}else{
			return false;
		}
		$this->projekt_data['posten'] = $aus;
		return true;
	}
	
	/**
	 * get auslagen information from db
	 *
	 * @param boolean $renderError
	 */
	private function getDbAuslagen(){
		$res = $this->db->dbFetchAll(
			"auslagen",
			[DBConnector::FETCH_ASSOC],
			[
				'auslagen.*',
				'projekte' => 'projekte.*',
			],
			["auslagen.id" => $this->auslagen_id, "auslagen.projekt_id" => $this->projekt_id],
			[
				["type" => "inner", "table" => "projekte", "on" => [["projekte.id", "auslagen.projekt_id"]]],
			]
		);
		if (!empty($res)){
			$out = [];
			$this->auslagen_data = $res[0];
			return true;
		}else{
			$this->error = 'Eine Abrechnung mit der ID: ' . $this->auslagen_id . ' existiert nicht. :(<br>';
			return false;
		}
	}
	
	private function getDbBelegePostenFiles(){
		$res = $this->db->dbFetchAll(
			'belege',
			[DBConnector::FETCH_ASSOC],
			[
				'belege' => 'belege.*',
				'beleg_posten' => 'beleg_posten.*',
				'fileinfo' => 'fileinfo.*',
				'projektposten' => 'projektposten.*',
			],
			["belege.auslagen_id" => $this->auslagen_id],
			[
				["type" => "left", "table" => "beleg_posten", "on" => [["belege.id", "beleg_posten.beleg_id"]]],
				["type" => "left", "table" => "fileinfo", "on" => [["fileinfo.id", "belege.file_id"]]],
				["type" => "left", "table" => "auslagen", "on" => [["belege.auslagen_id", "auslagen.id"]]],
				[
					"type" => "left",
					"table" => "projektposten",
					"on" => [
						["beleg_posten.projekt_posten_id", "projektposten.id"],
						["projektposten.projekt_id", "auslagen.projekt_id"]
					]
				],
			],
			[
				"belege.id" => true,
				"belege.short" => true,
				"beleg_posten.id" => true,
				"beleg_posten.short" => true,
				"projektposten.name" => true
			]
		);
		$belege = [];
		if (!empty($res)){
			$last_beleg = -1;
			$last_posten = -1;
			foreach ($res as $row){
				//belege
				if ($last_beleg != $row['belege.id']){
					$last_beleg = $row['belege.id'];
					$belege[$last_beleg] = [
						'id' => $row['belege.id'],
						'short' => $row['belege.short'],
						'created_on' => $row['belege.created_on'],
						'datum' => $row['belege.datum'],
						'beschreibung' => $row['belege.beschreibung'],
						'file_id' => $row['belege.file_id'],
						'file' => null,
						'posten' => []
					];
					//files
					if ($row['belege.file_id']){
						$belege[$last_beleg]['file'] = [
							'id' => $row['fileinfo.id'],
							'link' => $row['fileinfo.link'],
							'added_on' => $row['fileinfo.added_on'],
							'hashname' => $row['fileinfo.hashname'],
							'filename' => $row['fileinfo.filename'],
							'size' => $row['fileinfo.size'],
							'fileextension' => $row['fileinfo.fileextension'],
							'mime' => $row['fileinfo.mime'],
							'encoding' => $row['fileinfo.encoding'],
							'data' => null,
						];
						$this->stateInfo['editable'] = false;
					}
				}
				//posten
				if ($last_posten != $row['beleg_posten.id']){
					$last_posten = $row['beleg_posten.id'];
					if ($last_posten){
						$belege[$last_beleg]['posten'][$last_posten] = [
							'id' => $row['beleg_posten.id'],
							'beleg_id' => $row['beleg_posten.beleg_id'],
							'short' => $row['beleg_posten.short'],
							'projekt_posten_id' => $row['projektposten.id'],
							'projekt.posten_name' => $row['projektposten.name'],
							'ausgaben' => $row['beleg_posten.ausgaben'],
							'einnahmen' => $row['beleg_posten.einnahmen']
						];
					}
				}
			}
		}
		$this->auslagen_data['belege'] = $belege;
		return true;
	}
	
	/**
	 * calculate stateInfo von aktueller auslage
	 */
	private function stateFromAuslagenData(){
		$this->stateInfo = self::state2stateInfo($this->auslagen_data['state']);
		if (!$this->auslagen_id)
			return;
		//sub states - revocation
		if ($this->stateInfo['state'] == 'revocation'){
			$this->stateInfo['substate'] .=
				(($this->stateInfo['substate']) ? ',' : '')
				. ($this->auslagen_data['rejected'] ?
					'rejected' : 'revoked');
		}
		//sub states - wip - ok_*
		if ($this->stateInfo['state'] == 'wip'){
			$this->stateInfo['substate'] .=
				($this->auslagen_data['ok-belege'] ?
					(($this->stateInfo['substate']) ? ',' : '')
					. 'ok-belege'
					: '');
			$this->stateInfo['substate'] .=
				($this->auslagen_data['ok-hv'] ?
					(($this->stateInfo['substate']) ? ',' : '')
					. 'ok-hv'
					: '');
			$this->stateInfo['substate'] .=
				($this->auslagen_data['ok-kv'] ?
					(($this->stateInfo['substate']) ? ',' : '')
					. 'ok-kv'
					: '');
		}
		//sub state - instructed
		if ($this->stateInfo['state'] == 'instructed'){
			$this->stateInfo['substate'] .=
				($this->auslagen_data['payed'] ?
					(($this->stateInfo['substate']) ? ',' : '')
					. 'payed'
					: '');
		}
	}
	
	/**
	 * create stateInfo from state
	 * state may be substate, state or db_state info
	 *
	 * @param array $state
	 */
	public static function state2stateInfo($state){
		$s = $state;
		$split = null;
		if (strpos($state, ';')){
			$split = explode(';', $s);
			$s = $split[0];
		}
		$out = [
			'state' => '',
			'substate' => '',
		];
		//state / substate
		if (isset(self::$subStates[$s])){
			$out['substate'] = $s;
			$out['state'] = self::$subStates[$s][0];
		}else if (isset(self::$states[$s])){
			$out['state'] = $s;
		}else{
			return $out;
		}
		//optional info - date
		if ($split && isset($split[1])){
			$out['date'] = $split[1];
		}
		//optional info - user
		if ($split && isset($split[2])){
			$out['user'] = $split[2];
		}
		//optional info - user realname
		if ($split && isset($split[3])){
			$out['realname'] = $split[3];
		}
		return $out;
	}
	
	//---------------------------------------------------------
	/* ------------- CONSTRUCTOR ------------- */
	
	/**
	 * check permission of permission entry
	 * e.g entries from stateChanges, $group, ...
	 *
	 * @param boolean|array $map
	 *
	 * @return bool
	 */
	private function checkPermissionByMap($map){
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		if ($map === true){
			return true;
		}
		if (is_array($map)
			&& isset($map['groups'])){
			$g = (is_string($map['groups'])) ? $map['groups'] : implode(",", $map["groups"]);
			if ($auth->hasGroup($g)){
				return true;
			}
		}
		if (is_array($map)
			&& isset($map['dynamic']) && $this->auslagen_id){
			//build dynamic data
			$owner = explode(';', $this->auslagen_data['created']);
			$owner = $owner[1];
			$dynamic = [
				'owner' => $owner,
				'plain_orga' => $this->auslagen_data['projekte.org'],
			];
			//check dynamic permissions
			$a = $auth->getAttributes();
			foreach ($map['dynamic'] as $type){
				if (!isset($dynamic[$type]))
					continue;
				switch ($type){
					case 'owner':
						{
							if ($auth->getUsername() == $dynamic[$type]){
								return true;
							}
						}
					break;
					case 'plain_orga':
						{
							if (!isset($a['gremien']))
								continue 2;
							if (in_array($dynamic[$type], $a['gremien'])){
								return true;
							}
						}
					break;
				}
			}
		}
		return false;
	}
	
	public function getStateString(){
		$subStateName = $this->stateInfo["substate"];
		if (isset(self::$subStates[$subStateName])){
			$sub = " - " . self::$subStates[$subStateName][2];
		}else{
			$sub = "";
		}
		return self::getStateStringFromName($this->stateInfo["state"]) . $sub;
	}
	
	//---------------------------------------------------------
	/* ---------- DB FUNCTIONS ---------- */
	
	public static function initStaticVars(){
	
	}
	
	public static function getStateStringFromName($statename){
		return self::$states[$statename][0];
	}
	
	public function getAuslagenEtag(){
		if (!isset($this->auslagen_data['etag'])){
			if (!$this->getDbAuslagen()){
				return false;
			}
		}
		return $this->auslagen_data['etag'];
	}
	
	public function getBelegPostenFiles(){
		if (!$this->getDbBelegePostenFiles()){
			return false;
		}
		return $this->auslagen_data['belege'];
	}
	
	//---------------------------------------------------------
	/* ---------- HANDLER FUNCTIONS ------------ */
	
	public function getProjektPosten(){
		if ($this->getDbProjektPosten()){
			return $this->projekt_data['posten'];
		}else{
			return false;
		}
	}
	
	//---------------------------------------------------------
	/* ---------- JSON FUNCTIONS ------------ */
	
	/**
	 * @return the $auslagen_id
	 */
	public function getID(){
		if (isset($this->auslagen_id))
			return $this->auslagen_id;
		else
			return null;
	}
	
	//handle auslagen state change
	
	/**
	 * @return the $projekt_id
	 */
	public function getProjektID(){
		if (isset($this->projekt_id))
			return $this->projekt_id;
		else
			return null;
	}
	
	//handle file delete request
	
	/**
	 * @return mixed $error
	 */
	public function getError(){
		return $this->error;
	}
	
	//handle create or update auslage
	
	public function handlePost(){
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		if ($this->error)
			$this->_renderPostError();
		if (!isset($this->routeInfo['mfunction'])){
			$this->error = 'mfunction not set.';
		}
		if (!$this->error)
			switch ($this->routeInfo['mfunction']){
				case 'updatecreate':
					{
						if ($this->stateInfo['editable']){
							$this->post_createupdate();
						}else{
							$this->error = 'Die Abrechnung kann nicht verändert werden.';
						}
					}
				break;
				case 'filedelete':
					{
						if ($this->stateInfo['editable']){
							$this->post_filedelete();
						}else{
							$this->error = 'Die Abrechnung kann nicht verändert werden. Datei nicht gelöcht.';
						}
					}
				break;
				case 'state':
					{
						if (($this->stateInfo['project-editable'] || $auth->hasGroup(
									"ref-finanzen"
								)) && $this->auslagen_id){
							$this->post_statechange();
						}else{
							$this->error = 'Die Abrechnung kann nicht verändert werden. Der Status wurde nicht geändert.';
						}
					}
				break;
				case 'belegpdf':
					{
						if (!isset($this->auslagen_data['id'])){
							$this->error = 'Die Abrechnung wurde nicht gefunden';
						}else if (!isset($this->auslagen_data['belege']) || count($this->auslagen_data['belege']) <= 0){
							$this->error = 'Die Abrechnung enthält keine Belege';
						}else{
							//missing file?
							foreach ($this->auslagen_data['belege'] as $b){
								if (!$b['file']){
									$this->error = 'Für den Beleg [ID: ' . $b['id'] . '][NR: B' . $b['short'] . '] muss noch eine Datei hinterlegt werden.';
									break;
								}
							}
							if (!$this->error){
								$this->post_belegpdf();
							}
						}
					}
				break;
				case "zahlungsanweisung":
					if (!isset($this->auslagen_data['id'])){
						$this->error = 'Die Abrechnung wurde nicht gefunden';
					}else{
						if (in_array($this->stateInfo["state"], ["ok", "instructed", "booked"])){
							$this->post_zahlungsanweisungpdf();
						}
					}
				break;
				default:
					$this->error = 'Unknown Action.';
				break;
			}
		if ($this->error)
			$this->_renderPostError();
		else{
			$this->_renderPostResult();
		}
	}
	
	//---------------------------------------------------------
	/* ---------- RENDER FUNCTIONS ------------ */
	
	private function _renderPostError(){
		$this->json_result = [
			'success' => false,
			'status' => '200',
			'msg' => $this->error,
			'type' => 'modal',
			'subtype' => 'server-error',
			'reload' => (strpos($this->error, 'not allowed') && strpos($this->error, 'files')) ? 3000 : false
		];
		$this->_renderPostResult();
	}
	
	private function _renderPostResult(){
		JsonController::print_json($this->json_result);
	}
	
	private function post_createupdate(){
		//auslage =============================================
		//check etag if no new auslage
		if ($this->routeInfo['validated']['auslagen-id'] != 'NEW' &&
			$this->auslagen_data['etag'] != $this->routeInfo['validated']['etag']){
			$this->error = '<p>Die Prüfsumme der Abrechnung stimmt nicht mit der gesendeten überein.</p>' .
				'<p>Die Abrechnung wurde in der Zwischenzeit geändert, daher muss die Seite neu geladen werden...</p>' .
				'<p>Die übertragene Version liegt ' . ($this->auslagen_data['version'] - $this->routeInfo['validated']['version']) . ' Version(en) zurück.</p>';
			return;
		}else if ($this->routeInfo['validated']['auslagen-id'] == 'NEW'){
			$this->auslagen_data = $this->get_empty_auslage();
		}
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		//fill data
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = $auth->getUsername();
		$newInfo['realname'] = $auth->getUserFullName();
		
		// filter for changes ---------------------------------
		$changed_belege_flag = false;
		$changed_posten_flag = false;
		$removed_belege = [];
		$removed_posten = [];
		$new_belege = [];
		$new_posten = [];
		$changed_belege = [];
		$changed_posten = [];
		if (isset($this->routeInfo['validated']['belege'])){
			foreach ($this->routeInfo['validated']['belege'] as $kb => $b){
				if (strpos($kb, 'new_') !== false){
					$changed_belege_flag = true;
					$new_belege[$kb] = $b;
				}else if (!isset($this->auslagen_data['belege'][$kb])){
					$changed_belege_flag = true;
					//ignore this invalid elements
				}else{
					$ob = $this->auslagen_data['belege'][$kb];
					$changed_belege[$kb] = $ob;
					$fileIdx = 'beleg_' . $kb;
					if (!$ob['file_id'] && isset($_FILES[$fileIdx]['error']) && $_FILES[$fileIdx]['error'][0] === 0){
						$changed_belege_flag = true;
					}
				}
				if (isset($b['posten']))
					foreach ($b['posten'] as $kp => $p){
						if (strpos($kp, 'new_') !== false){
							$changed_posten_flag = true;
							$new_posten[$kp] = ['posten' => $p, 'beleg_id' => $kb];
						}else if (!isset($this->auslagen_data['belege'][$kb]['posten'][$kp])){
							$changed_posten_flag = true;
							//ignore invalid elements
						}else{
							$op = $this->auslagen_data['belege'][$kb]['posten'][$kp];
							$changed_posten[$kp] = $op;
							if ($op['einnahmen'] != $p['in'] || $op['ausgaben'] != $p['out']){
								$changed_posten_flag = true;
							}
						}
					}
			}
		}
		//gelöschte elemente
		foreach ($this->auslagen_data['belege'] as $kb => $b){
			if (!isset($this->routeInfo['validated']['belege'][$kb])){
				$changed_belege_flag = true;
				$removed_belege[$kb] = $b;
			}else{
				foreach ($b['posten'] as $kp => $p){
					if (!isset($this->routeInfo['validated']['belege'][$kb]['posten'][$kp])){
						$changed_posten_flag = true;
						$removed_posten[$kp] = $p;
					}
				}
			}
		}
		// AUSLAGEN -------------------------------------------
		$db_auslage = [
			"projekt_id" => $this->projekt_id,
			"created" => $this->auslagen_data['created'],
			"name_suffix" => $this->routeInfo['validated']['auslagen-name'],
			"state" => $this->auslagen_data['state'],
			"ok-belege" => ($changed_belege_flag || $changed_posten_flag) ? '' : $this->auslagen_data['ok-belege'],
			"ok-hv" => ($changed_belege_flag || $changed_posten_flag) ? '' : $this->auslagen_data['ok-hv'],
			"ok-kv" => ($changed_belege_flag || $changed_posten_flag) ? '' : $this->auslagen_data['ok-kv'],
			"payed" => $this->auslagen_data['payed'],
			"rejected" => $this->auslagen_data['rejected'],
			"zahlung-iban" => strpos(
				$this->routeInfo['validated']['zahlung-iban'],
				'... ...'
			) ? $this->auslagen_data['zahlung-iban'] : $this->encryptedStr(
				$this->routeInfo['validated']['zahlung-iban']
			),
			"zahlung-name" => $this->routeInfo['validated']['zahlung-name'],
			"zahlung-vwzk" => $this->routeInfo['validated']['zahlung-vwzk'],
			"address" => $this->routeInfo['validated']['address'],
			"last_change" => "{$newInfo['date']}",
			"last_change_by" => "{$newInfo['user']};{$newInfo['realname']}",
			"version" => intval($this->auslagen_data['version']) + 1,
			"etag" => Crypto::generateRandomString(16),
		];
		//insert/update in db
		if ($this->auslagen_data['id']){
			$where = [
				'id' => $this->auslagen_data['id'],
				'etag' => $this->auslagen_data['etag'],
			];
			$this->db->dbUpdate('auslagen', $where, $db_auslage);
			$db_auslage['id'] = $this->auslagen_data['id'];
		}else{
			$idd = $this->db->dbInsert('auslagen', $db_auslage);
			$db_auslage['id'] = $idd;
		}
		foreach ($db_auslage as $k => $v){
			$this->auslagen_data[$k] = $v;
		}
		//belege ==============================================
		//removed ------
		$fh = new FileHandler($this->db, ['UPLOAD_WHITELIST' => 'pdf']);
		foreach ($removed_belege as $b){
			//remove file
			$fh->deleteFilesByLinkId($b['id']);
			//remove posten
			$this->db->dbDelete('beleg_posten', ['beleg_id' => $b['id']]);
			//remove beleg
			$this->db->dbDelete('belege', ['id' => $b['id']]);
		}
		$beleg_file_map = [];
		//changed ------
		foreach ($changed_belege as $kb => $b){
			$fileIdx = 'beleg_' . $kb;
			if (!$b['file_id'] && isset($_FILES[$fileIdx]['error'][0]) && $_FILES[$fileIdx]['error'][0] != 4){
				$beleg_file_map[$kb] = [
					'file' => $fileIdx,
					'link' => $b['id'],
				];
			}
			//update values
			$db_beleg = [
				'datum' => ($this->routeInfo['validated']['belege'][$kb]['datum']) ? $this->routeInfo['validated']['belege'][$kb]['datum'] : null,
				'beschreibung' => $this->routeInfo['validated']['belege'][$kb]['beschreibung'],
			];
			$where = [
				'id' => $b['id'],
				'auslagen_id' => $this->auslagen_data['id'],
			];
			$this->db->dbUpdate('belege', $where, $db_beleg);
		}
		//new ------
		$beleg_shortcounter = 0;
		$map_new_beleg_beleg_idx = [];
		foreach ($new_belege as $kb => $b){
			$beleg_shortcounter++;
			$db_beleg = [
				'short' => $this->auslagen_data['version'] . '' . $beleg_shortcounter,
				'created_on' => date_create()->format('Y-m-d H:i:s'),
				'datum' => ($b['datum']) ? $b['datum'] : null,
				'beschreibung' => $b['beschreibung'],
				'auslagen_id' => $this->auslagen_data['id'],
			];
			$idd = $this->db->dbInsert('belege', $db_beleg);
			$db_beleg['id'] = $idd;
			$map_new_beleg_beleg_idx[$kb] = $idd;
			$fileIdx = 'beleg_' . $kb;
			if (isset($_FILES[$fileIdx]['error'][0]) && $_FILES[$fileIdx]['error'][0] === 0){
				$beleg_file_map[$kb] = [
					'file' => $fileIdx,
					'link' => $idd,
				];
			}
		}
		//belegposten =========================================
		//delete ------
		foreach ($removed_posten as $p){
			//remove posten
			$this->db->dbDelete('beleg_posten', ['id' => $p['id']]);
		}
		//changed ------
		foreach ($changed_posten as $kp => $p){
			//update values
			$db_posten = [
				'projekt_posten_id' => $this->routeInfo['validated']['belege'][$p['beleg_id']]['posten'][$p['id']]['projekt-posten'],
				'ausgaben' => $this->routeInfo['validated']['belege'][$p['beleg_id']]['posten'][$p['id']]['out'],
				'einnahmen' => $this->routeInfo['validated']['belege'][$p['beleg_id']]['posten'][$p['id']]['in'],
			];
			$where = [
				'id' => $p['id'],
				'beleg_id' => $p['beleg_id'],
			];
			$this->db->dbUpdate('beleg_posten', $where, $db_posten);
		}
		//new ------
		$posten_shortcounter = 0;
		foreach ($new_posten as $kb => $map){
			if (strpos($map['beleg_id'], 'new_' === false)
				&& isset($removed_belege[$map['beleg_id']])){
				continue;
			}
			$posten_shortcounter++;
			$db_posten = [
				'short' => intval($this->auslagen_data['version'] . '' . $posten_shortcounter),
				'projekt_posten_id' => $map['posten']['projekt-posten'],
				'ausgaben' => $map['posten']['out'],
				'einnahmen' => $map['posten']['in'],
				'beleg_id' => (strpos(
						$map['beleg_id'],
						'new_'
					) !== false) ? $map_new_beleg_beleg_idx[$map['beleg_id']] : $map['beleg_id'],
			];
			$idd = $this->db->dbInsert('beleg_posten', $db_posten);
		}
		//new files ===============================================
		foreach ($beleg_file_map as $fileInfo){
			$file_id = 0;
			//handle file upload
			$res = $fh->upload(intval($fileInfo['link']), $fileInfo['file']);
			if (count($res['error']) > 0){
				$emsg = '';
				foreach ($res['error'] as $e){
					$emsg .= "<p>$e</p>";
				}
				$this->error = $emsg;
			}else{
				/** @var SILMPH\File $file */
				foreach ($res['fileinfo'] as $file){
					$file_id = $file->id;
					break;
				}
			}
			//update beleg -> set file link
			if ($file_id){
				$this->db->dbUpdate('belege', ['id' => $fileInfo['link']], ['file_id' => $file_id]);
			}
		}
		$this->json_result = [
			'success' => true,
			'msg' => 'Die Änderungen wurden erfolgreich übernommen.<br>Seite wird aktulisiert...',
			'reload' => 2000,
			'type' => 'modal',
			'subtype' => 'server-success',
			'headline' => 'Eingaben gespeichert',
			'redirect' => URIBASE . 'projekt/' . $this->projekt_id . '/auslagen/' . $this->auslagen_data['id'],
		];
	}
	
	/**
	 *
	 * @return multitype:NULL string number multitype:
	 */
	private function get_empty_auslage(){
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = $auth->getUsername();
		$newInfo['realname'] = $auth->getUserFullName();
		return [
			"id" => null,
			"projekt_id" => $this->projekt_id,
			"created" => "{$newInfo['date']};{$newInfo['user']};{$newInfo['realname']}",
			"name_suffix" => "",
			"state" => "{$newInfo['state']};{$newInfo['date']};{$newInfo['user']};{$newInfo['realname']}",
			"ok-belege" => '',
			"ok-hv" => '',
			"ok-kv" => '',
			"payed" => '',
			"rejected" => '',
			"zahlung-iban" => '',
			"zahlung-name" => '',
			"zahlung-vwzk" => '',
			'address' => '',
			"last_change" => "{$newInfo['date']}",
			"last_change_by" => '',
			"version" => 0,
			"etag" => '',
			'belege' => [],
		];
	}
	
	/**
	 * encrypt string
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected static function encryptedStr($str){
		$p = $str;
		if (!$p)
			return '';
		$p = Crypto::pad_string($p);
		return Crypto::encrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE . '/secret.php'), URIBASE);
	}
	
	private function post_filedelete(){
		if ($this->auslagen_data['etag'] != $this->routeInfo['validated']['etag']){
			$this->error = '<p>Die Prüfsumme der Abrechnung stimmt nicht mit der gesendeten überein.</p>' .
				'<p>Die Abrechnung wurde in der Zwischenzeit geändert, daher muss die Seite neu geladen werden...</p>' .
				'<p>Die übertragene Version liegt ' . ($this->auslagen_data['version'] - $this->routeInfo['validated']['version']) . ' Version(en) zurück.</p>';
			return;
		}
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		//fill data
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = $auth->getUsername();
		$newInfo['realname'] = $auth->getUserFullName();
		
		//check fileid exists
		$found_file_id = false;
		foreach ($this->auslagen_data['belege'] as $b){
			if ($b['file_id'] == $this->routeInfo['validated']['fid']){
				$found_file_id = $b;
				break;
			}
		}
		if (!$found_file_id){
			$this->error = 'Die Angegebene Datei konnte nicht gefunden werden.';
			return;
		}
		//delete file by link id
		$fh = new FileHandler($this->db);
		$fh->deleteFilesByLinkId($found_file_id['id']);
		//remove id from auslagen, + update changed
		$this->db->dbUpdate('belege', ['id' => $found_file_id['id']], ['file_id' => null]);
		$this->db->dbUpdate(
			'auslagen',
			['id' => $this->auslagen_data['id']],
			[
				"last_change" => "{$newInfo['date']}",
				"last_change_by" => "{$newInfo['user']};{$newInfo['realname']}",
				"version" => intval($this->auslagen_data['version']) + 1,
				"etag" => Crypto::generateRandomString(16),
			]
		);
		$this->json_result = [
			'success' => true,
			'msg' => "Die Datei '{$found_file_id['file']['filename']}.{$found_file_id['file']['fileextension']}' wurde erfolgreich entfernt.",
			'reload' => 2000,
			'type' => 'modal',
			'subtype' => 'server-success',
			'headline' => 'Eingaben gespeichert',
			'redirect' => URIBASE . 'projekt/' . $this->projekt_id . '/auslagen/' . $this->auslagen_data['id'] . '/edit',
		];
	}
	
	//---------------------------------------------------------
	/* ---------- RENDER HELPER ------------ */
	
	private function post_statechange(){
		$newState = $this->routeInfo['validated']['state'];
		if (!$this->state_change_possible($newState) ||
			!$this->state_change($newState, $this->routeInfo['validated']['etag'])){
			$this->error = 'Diese Statusänderung ist momentan nicht möglich.';
		}else{
			$this->json_result = [
				'success' => true,
				'msg' => "Status geändert",
				'reload' => 2000,
				'type' => 'modal',
				'subtype' => 'server-success',
				'headline' => 'Erfolgreich',
				'redirect' => URIBASE . 'projekt/' . $this->projekt_id . '/auslagen/' . $this->auslagen_data['id'],
			];
			if ($newState == 'wip' && !$this->auslagen_data['ok-belege']){
				$this->json_result['reload'] = 5000;
				$this->json_result['msg'] .= '<br><strong>Bitte beachte, dass gegebenenfalls noch Belege eingereicht werden müssen.<br><i>(Vorlage: "Belege PDF")</i></strong>';
			}
		}
		
	}
	
	/**
	 * check if state change into new state is possible
	 * check state
	 * check substate
	 * check permission
	 *
	 * @param string $newState
	 *
	 * @return boolean
	 */
	public function state_change_possible($newState, $is_sub = false){
		//current state
		$c = $this->stateInfo['state'];
		//main stateChange -----------------------------------
		if (isset(self::$states[$newState])){
			//state change possible - notwendig
			if (!isset(self::$stateChanges[$c][$newState])){
				return false;
			}
			//state change possible (subtypes required) - optional
			$required_sub = [];
			foreach (self::$subStates as $sub => $info){
				if ($info[0] == $c && $info[1]){
					$required_sub[] = $sub;
				}
			}
			if (!$is_sub)
				foreach ($required_sub as $required){
					if (strpos($this->stateInfo['substate'], $required) === false){
						return false;
					}
				}
			
			//state change permission
			if ($this->checkPermissionByMap(self::$stateChanges[$c][$newState])){
				return true;
			}
			// sub state changes ----------------------------------
		}else if (isset(self::$subStates[$newState])){
			//mainstatechange possible ?
			//same state || mainstate change possible
			if (self::$subStates[$newState][0] == $c || $this->state_change_possible(
					self::$subStates[$newState][0],
					$newState
				)){
				//if substatechange possible
				if (isset(self::$stateChanges[$newState][$c])){
					if ($this->checkPermissionByMap(self::$stateChanges[$newState][$c])){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * performes statechange
	 * checks state
	 * checks substate
	 * check permission
	 * do db query
	 *
	 * @param string $newState
	 * @param string $etag
	 *
	 * @return bool
	 */
	public function state_change($newState, $etag): bool{
		
		if ($this->state_change_possible($newState)){
			$auth = (AUTH_HANDLER);
			/* @var $auth AuthHandler */
			$auth = $auth::getInstance();
			$newInfo = self::state2stateInfo($newState);
			$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
			$newInfo['user'] = $auth->getUsername();
			$newInfo['realname'] = $auth->getUserFullName();
			//
			try{
				$set = [
					'version' => $this->auslagen_data['version'] + 1,
					'etag' => Crypto::generateRandomString(16),
				];
			}catch (Exception $e){
				return false;
			}
			
			$where = [
				'id' => $this->auslagen_data['id'],
				'etag' => $etag
			];
			if ($this->stateInfo['state'] != $newInfo['state']){
				$set['state'] = "{$newInfo['state']};{$newInfo['date']};{$newInfo['user']};{$newInfo['realname']}";
			}
			//reset values
			if (isset(self::$states[$newState])){
				switch ($newState){
					case 'wip':
						{
							$set['ok-hv'] = '';
							$set['ok-kv'] = '';
							break;
						}
					case 'draft':
						{
							$set['rejected'] = '';
							break;
						}
					case 'instructed':
						{
							$set['payed'] = '';
							break;
						}
					case 'revocation':
						{ //sonderfall nicht alleine setzbar, nur über substates
							return false;
						}
				}
			}
			if (isset(self::$subStates[$newState])){
				switch ($newState){
					case 'ok-belege':
					case 'ok-hv':
					case 'ok-kv':
					case 'payed':
					case 'rejected':
						{
							$set[$newState] = "{$newInfo['date']};{$newInfo['user']};{$newInfo['realname']}";
							break;
						}
				}
			}
			$this->db->dbUpdate('auslagen', $where, $set);
			//automagic -> all ok -> set state ok -> auto genehmigt
			if ($newState == 'ok-belege' || $newState == 'ok-hv' || $newState == 'ok-kv'){
				$tmp_auslage = $this->db->dbFetchAll(
					'auslagen',
					[DBConnector::FETCH_ASSOC],
					[],
					['id' => $this->auslagen_data['id']]
				);
				if (
					$tmp_auslage
					&& isset($tmp_auslage[0]['ok-belege'])
					&& $tmp_auslage[0]['ok-belege']
					&& isset($tmp_auslage[0]['ok-hv'])
					&& $tmp_auslage[0]['ok-hv']
					&& isset($tmp_auslage[0]['ok-kv'])
					&& $tmp_auslage[0]['ok-kv']
					&& substr($tmp_auslage[0]['state'], 0, 3) == 'wip'
				){
					$this->db->dbUpdate(
						'auslagen',
						['id' => $this->auslagen_data['id']],
						['state' => "ok;{$newInfo['date']};{$newInfo['user']};{$newInfo['realname']}"]
					);
					//$tmp_auslage2 = $this->db->dbFetchAll('auslagen',[DBConnector::FETCH_ASSOC], [], ['id' => $this->auslagen_data['id']]);
				}
			}
			return true;
		}
		return false;
	}
	
	private function post_belegpdf(){
		// get auslagen info
		$info = $this->state2stateInfo('draft;' . $this->auslagen_data['created']);
		$out = [
			'APIKEY' => FUI2PDF_APIKEY,
			'action' => 'belegpdf',
			'projekt' => [
				'id' => $this->projekt_data['id'],
				'name' => $this->projekt_data['name'],
				'created' => $this->projekt_data['createdat'],
				'org' => $this->projekt_data['org'],
			],
			'auslage' => [
				'id' => $this->auslagen_data['id'],
				'name' => $this->auslagen_data['name_suffix'],
				'created' => $info['date'],
				'created_by' => $info['realname'],
				'address' => $this->auslagen_data['address'],
				'zahlung' => [
					'name' => $this->auslagen_data['zahlung-name'],
				],
			],
			'belege' => [],
		];
		//put files to info
		$fh = new FileHandler($this->db);
		foreach ($this->auslagen_data['belege'] as $beleg){
			$file = ($beleg['file']) ? $fh->checkFileHash($beleg['file']['hashname']) : null;
			$out['belege'][] = [
				'id' => $beleg['id'],
				'short' => $beleg['short'],
				'date' => $beleg['datum'],
				'desc' => $beleg['beschreibung'],
				'file_id' => $beleg['file_id'],
				'file' => ($file) ? $fh->fileToBase64($file) : '',
			];
		}
		
		
		$result = Helper::do_post_request2(FUI2PDF_URL . '/pdfbuilder', $out, FUI2PDF_AUTH);
		
		// return result to
		if ($result['success'] && !isset($this->routeInfo['validated']['d']) || $this->routeInfo['validated']['d'] == 0){
			if (isset($result['data']['success']) && $result['data']['success']){
				$this->json_result = [
					'success' => true,
					'type' => 'modal',
					'subtype' => 'file',
					'container' => 'object',
					'headline' =>
					//direct link
						'<form method="POST" action="' . URIBASE . 'index.php' . $this->routeInfo['path'] . '"><a ' .
						'" href="#" class="modal-form-fallback-submit text-white">' .
						"Belegvorlage_P" .
						str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
						'-A' .
						str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
						'.pdf' .
						'</a>' .
						'<input type="hidden" name="auslagen-id" value="' . $this->auslagen_id . '">' .
						'<input type="hidden" name="projekt-id" value="' . $this->projekt_id . '">' .
						'<input type="hidden" name="d" value="1">' . '</form>',
					'attr' => [
						'type' => 'application/pdf',
						'download' =>
							"Belegvorlage_P" .
							str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
							'-A' .
							str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
							'.pdf',
					],
					'fallback' => '<form method="POST" action="' . URIBASE . 'index.php' . $this->routeInfo['path'] . '">Die Datei kann leider nicht angezeigt werden, kann aber unter diesem <a ' .
						'" href="#" class="modal-form-fallback-submit">Link</a> heruntergeladen werden.' .
						'<input type="hidden" name="auslagen-id" value="' . $this->auslagen_id . '">' .
						'<input type="hidden" name="projekt-id" value="' . $this->projekt_id . '">' .
						'<input type="hidden" name="d" value="1">' .
						'</form>',
					'datapre' => 'data:application/pdf;base64,',
					'data' => $result['data']['data'],
				];
			}else{
				$this->json_result = [
					'success' => false,
					'type' => 'modal',
					'subtype' => 'server-error',
					'status' => '200',
					'msg' => '<div style="white-space: pre-wrap;">' . print_r(
							(isset($result['data']['error'])) ? $result['data']['error'] : $result['data'],
							true
						) . '</div>',
				];
			}
		}else if ($result['success'] && isset($this->routeInfo['validated']['d']) && $this->routeInfo['validated']['d'] == 1){
			header("Content-Type: application/pdf");
			header(
				'Content-Disposition: attachment; filename="' . "Belegvorlage_P" .
				str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
				'-A' .
				str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
				'.pdf'
				. '"'
			);
			echo base64_decode($result['data']['data']);
			die();
		}else{
			ErrorHandler::_errorLog(print_r($result, true), '[' . get_class($this) . '][PDF-Creation]');
			$this->error = 'Error during PDF creation.';
		}
	}
	
	private function post_zahlungsanweisungpdf(){
		$summed_value = 0;
		$details = [];
		//var_dump($this->auslagen_data["belege"]);
		foreach ($this->auslagen_data["belege"] as $beleg){
			foreach ($beleg["posten"] as $posten){
				$pposten = [];
				foreach ($this->projekt_data['posten'] as $pp){
					if ($pp["id"] === $posten["projekt_posten_id"]){
						$pposten = $pp;
						break;
					}
				}
				$details[] = [
					'beleg-id' => $beleg["id"] . "-B" . $beleg["short"],
					'projektposten' => $posten["projekt_posten_id"],
					'titel' => $pposten["haushaltstitel.titel_nr"],
					'einnahmen' => $posten['einnahmen'],
					'ausgaben' => $posten['ausgaben'],
				];
				$summed_value -= $posten["ausgaben"];
				$summed_value += $posten["einnahmen"];
			}
		}
		
		$recht_all = [
			"buero" => "Büromaterial: Finanzordnung §11",
			"fahrt" => "Fahrtkosten: StuRa-Beschluss 21/20-08 i.V.m. 28/48-S01",
			"verbrauch" => "Verbrauchsmaterial: StuRa-Beschluss 21/20-07",
			"stura" => "StuRa-Beschluss: " . $this->projekt_data['recht-additional'],
			"fsr-ref" => "Beschluss FSR, Referat, AG: StuRa-Beschluss 21/21-05",
			"kleidung" => "Gremienkleidung: StuRa Beschluss 24/04-09",
			"andere" => "Andere Rechtsgrundlage: " . $this->projekt_data['recht-additional'],
			"bahn-card" => "BahnCard: StuRa Beschluss 29/21-W01",
		];
		
		$recht = isset($recht_all[$this->projekt_data["recht"]]) ? $recht_all[$this->projekt_data["recht"]] : "";
		
		$out = [
			'APIKEY' => FUI2PDF_APIKEY,
			'action' => 'zahlungsanweisung',
			
			'short-type-projekt' => "IP",
			'projekt-id' => $this->projekt_data['id'],
			'projekt-name' => $this->projekt_data['name'],
			'projekt-org' => $this->projekt_data['org'],
			'projekt-recht' => $recht,
			'projekt-create' => $this->projekt_data["createdat"],
			
			'short-type-auslage' => "A",
			'auslage-id' => $this->auslagen_data["id"],
			'auslage-name' => $this->auslagen_data["name_suffix"],
			
			'zahlung-name' => $this->auslagen_data['zahlung-name'],
			'zahlung-iban' => self::decryptedStr($this->auslagen_data['zahlung-iban']),
			'zahlung-value' => $summed_value,
			'zahlung-adresse' => $this->auslagen_data['address'],
			
			'details' => $details,
		];
		$result = Helper::do_post_request2(FUI2PDF_URL . '/pdfbuilder', $out, FUI2PDF_AUTH);
		
		// return result to
		if ($result['success'] && !isset($this->routeInfo['validated']['d']) || $this->routeInfo['validated']['d'] == 0){
			if (isset($result['data']['success']) && $result['data']['success']){
				$this->json_result = [
					'success' => true,
					'type' => 'modal',
					'subtype' => 'file',
					'container' => 'object',
					'headline' =>
					//direct link
						'<form method="POST" action="' . URIBASE . 'index.php' . $this->routeInfo['path'] . '"><a ' .
						'" href="#" class="modal-form-fallback-submit text-white">' .
						"Belegvorlage_P" .
						str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
						'-A' .
						str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
						'.pdf' .
						'</a>' .
						'<input type="hidden" name="auslagen-id" value="' . $this->auslagen_id . '">' .
						'<input type="hidden" name="projekt-id" value="' . $this->projekt_id . '">' .
						'<input type="hidden" name="d" value="1">' . '</form>',
					'attr' => [
						'type' => 'application/pdf',
						'download' =>
							"Belegvorlage_P" .
							str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
							'-A' .
							str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
							'.pdf',
					],
					'fallback' => '<form method="POST" action="' . URIBASE . 'index.php' . $this->routeInfo['path'] . '">Die Datei kann leider nicht angezeigt werden, kann aber unter diesem <a ' .
						'" href="#" class="modal-form-fallback-submit">Link</a> heruntergeladen werden.' .
						'<input type="hidden" name="auslagen-id" value="' . $this->auslagen_id . '">' .
						'<input type="hidden" name="projekt-id" value="' . $this->projekt_id . '">' .
						'<input type="hidden" name="d" value="1">' .
						'</form>',
					'datapre' => 'data:application/pdf;base64,',
					'data' => $result['data']['data'],
				];
			}else{
				$this->json_result = [
					'success' => false,
					'type' => 'modal',
					'subtype' => 'server-error',
					'status' => '200',
					'msg' => '<div style="white-space: pre-wrap;">' . print_r(
							(isset($result['data']['error'])) ? $result['data']['error'] : $result['data'],
							true
						) . '</div>',
				];
			}
		}else if ($result['success'] && isset($this->routeInfo['validated']['d']) && $this->routeInfo['validated']['d'] == 1){
			header("Content-Type: application/pdf");
			header(
				'Content-Disposition: attachment; filename="' . "Belegvorlage_P" .
				str_pad($this->projekt_id, 3, "0", STR_PAD_LEFT) .
				'-A' .
				str_pad($this->auslagen_id, 3, "0", STR_PAD_LEFT) .
				'.pdf'
				. '"'
			);
			echo base64_decode($result['data']['data']);
			die();
		}else{
			ErrorHandler::_errorLog(print_r($result, true), '[' . get_class($this) . '][PDF-Creation]');
			$this->error = 'Error during PDF creation.';
		}
	}
	
	/**
	 * decrypt string
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected static function decryptedStr($str){
		$p = $str;
		if (!$p)
			return '';
		$p = Crypto::decrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE . '/secret.php'), URIBASE);
		$p = Crypto::unpad_string($p);
		return $p;
	}
	
	/**
	 * render page
	 *
	 * @see Renderer::render()
	 */
	public function render(){
		if ($this->error){
			ErrorHandler::_renderError($this->error, 404);
			return -1;
		}else{
			return $this->renderAuslagenerstattung("Abrechnung");
		}
	}
	
	/**
	 * render auslagenerstattung html page
	 *
	 * @param string $titel
	 */
	private function renderAuslagenerstattung($titel){
		$auth = (AUTH_HANDLER);
		/* @var $auth AuthHandler */
		$auth = $auth::getInstance();
		$editable = $this->stateInfo['editable'];
		?>
        <h3><?= $titel . (($this->title) ? $this->title : '') ?></h3>
		<?php //--------------------------------------------------------------------
		?>
		
		<?= ($this->routeInfo['action'] == 'view') ? $this->getStateSvg() . $this->getSvgHiddenFields() : ''; ?>
		<?php $show_genemigung_state = ($this->routeInfo['action'] != 'create' || isset($this->auslagen_data['state']) && $this->auslagen_data['state'] != 'draft'); ?>
		
		<?php //--------------------------------------------------------------------
		?>
		<?php if ($show_genemigung_state){ ?>
            <label for="genehmigung">Abrechnungs Status</label>
            <div id='projekt-well' class="well">
                <label for="genehmigung">Status</label><br>
                <div class="col-xs-12 col-xs-12 col-md-4 form-group">
                    <label class="control-label" for="belege-ok__5b3d1833c1532">Status</label>
                    <div><?php
						if ($this->stateInfo['state'] == 'instructed' && strpos(
								$this->stateInfo['substate'],
								'payed'
							) !== false){
							echo 'Bezahlt';
						}else if ($this->stateInfo['state'] == 'revocation'){
							echo ($this->auslagen_data['rejected']) ? 'Abgelehnt' : 'Zurückgezogen';
						}else{
							echo self::$states[$this->stateInfo['state']][0];
						}
						?></div>
                </div>
                <div class="col-xs-12 col-xs-12 col-md-4 form-group">
                    <label class="control-label" for="belege-ok__5b3d1833c1532">Erstellt</label>
                    <div><?php
						if ($this->routeInfo['action'] != 'create'){
							$tmpState = $this->state2stateInfo('draft;' . $this->auslagen_data['created']);
							if ($this->checkPermissionByMap(self::$groups['stateless']['view_creator'])){
								echo "{$tmpState['date']} {$tmpState['realname']}";
							}else{
								echo "{$tmpState['date']}";
							}
						}else{
							echo '-';
						}
						?></div>
                </div>
                <div class="col-xs-12 col-xs-12 col-md-4 form-group">
                    <label class="control-label" for="belege-ok__5b3d1833c1532">Version</label>
                    <div><?= ($this->routeInfo['action'] != 'create') ?
							$this->auslagen_data['version'] . " ({$this->auslagen_data['last_change']})" : '-'; ?></div>
                </div>
                <div class="clearfix"></div>
                <label for="genehmigung">Genehmigung</label>
                <br>
				<?php
				if ($this->auslagen_data['ok-belege']){
					$be_ok = $this->auslagen_data['ok-belege'];
					$be_ok = explode(';', $be_ok);
					$be_ok = "{$be_ok[0]} {$be_ok[2]}";
				}else{
					$be_ok = 'ausstehend';
				}
				echo $this->templater->getTextForm(
					"belege-ok",
					$be_ok,
					[12, 12, 4],
					"Original-Belege",
					"Original-Belege",
					[]
				); ?>
				<?php
				if ($this->auslagen_data['ok-hv']){
					$hv_ok = $this->auslagen_data['ok-hv'];
					$hv_ok = explode(';', $hv_ok);
					$hv_ok = "{$hv_ok[0]} {$hv_ok[2]}";
				}else{
					$hv_ok = 'ausstehend';
				}
				echo $this->templater->getTextForm("hv-ok", $hv_ok, [12, 12, 4], "HV", "HV", []); ?>
				<?php
				if ($this->auslagen_data['ok-kv']){
					$kv_ok = $this->auslagen_data['ok-kv'];
					$kv_ok = explode(';', $kv_ok);
					$kv_ok = "{$kv_ok[0]} {$kv_ok[2]}";
				}else{
					$kv_ok = 'ausstehend';
				}
				echo $this->templater->getTextForm("kv-ok", $kv_ok, [12, 12, 4], "KV", "KV", []); ?>
                <div class="clearfix"></div>
            </div>
		<?php } ?>
        <input type="hidden" name="nononce" value="<?= strrev($GLOBALS["nonce"]) ?>">
        <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
        <form id="<?php $current_form_id = 'auslagen-form-' . count($this->formSubmitButtons);
		$this->formSubmitButtons[] = $current_form_id;
		echo $current_form_id; ?>" class="ajax" method="POST" enctype="multipart/form-data"
              action="<?= URIBASE ?>rest/forms/auslagen/updatecreate">
            <input type="hidden" name="projekt-id" value="<?= $this->projekt_id; ?>">
            <input type="hidden" name="auslagen-id"
                   value="<?= ($this->routeInfo['action'] == 'create') ? 'NEW' : $this->auslagen_id; ?>">
            <input type="hidden" name="version"
                   value="<?= ($this->routeInfo['action'] == 'create') ? '1' : $this->auslagen_data['version']; ?>">
            <input type="hidden" name="etag"
                   value="<?= ($this->routeInfo['action'] == 'create') ? '0' : $this->auslagen_data['etag']; ?>">
			<?= $this->templater->getHiddenActionInput(''); ?>
			<?php //--------------------------------------------------------------------
			?>
            <label for="projekt-well">Abrechnung</label>
            <div id='projekt-well' class="well">
                <label>Name der Abrechnung</label>
				<?= $this->templater->getTextForm(
					"auslagen-name",
					(isset($this->auslagen_data['id'])) ? $this->auslagen_data['name_suffix'] : '',
					12,
					"Pflichtfeld",
					"",
					[],
					'<a href="' . URIBASE . 'projekt/' . $this->projekt_id . '"><i class="fa fa-fw fa-link"> </i>' . htmlspecialchars(
						$this->projekt_data['name']
					) . '</a>'
				) ?>
                <div class="clearfix"></div>
                <label for="zahlung">Zahlungsinformationen</label><br>
				<?= $this->templater->getTextForm(
					"zahlung-name",
					$this->auslagen_data['zahlung-name'],
					[12, 12, 6],
					"Name Zahlungsempfänger",
					"Zahlungsempfänger Name",
					[],
					[]
				) ?>
	
				<?php // iban only show trimmed if not hv/kv important!
				$iban_text = $this->auslagen_data['zahlung-iban'];
				if ($iban_text){
					$iban_text = $this->decryptedStr($iban_text);
				}
				if (!$auth->hasGroup('ref-finanzen-hv,ref-finanzen-kv')){
					$iban_text = self::trimIban($iban_text);
				}else if ($iban_text != ''){
					$iban_text = chunk_split($iban_text, 4, ' ');
				}
				echo $this->templater->getTextForm(
					"zahlung-iban",
					$iban_text,
					[12, 12, 6],
					"DE ...",
					"Zahlungsempfänger IBAN"
				) ?>
                <div class='clearfix'></div>
				<?= $this->templater->getTextForm(
					"zahlung-vwzk",
					$this->auslagen_data['zahlung-vwzk'],
					12,
					"z.B. Rechnungsnr. o.Ä.",
					"Verwendungszweck (verpflichtend bei Firmen)",
					[],
					[]
				) ?>
                <div class="clearfix"></div>
				<?php
				$tmplabel = ($this->routeInfo['action'] == 'edit' || $this->routeInfo['action'] == 'create') ?
					'Anschrift Empfangsberechtigter/Zahlungspflichtiger<small class="form-text text-muted" style="font-size: 0.7em; display: block; line-height: 1.0em;"><i>Der StuRa ist nach §12(2)-3 ThürStudFVO verpflichtet, diese Angaben abzufragen und aufzubewahren. Nach §18 ThürStudFVO beträgt die Dauer mindestens 6 Jahre nach Genehmigung der Entlastung.</i></small>' :
					'Anschrift Empfangsberechtigter/Zahlungspflichtiger';
				$tmpvalue = ($this->routeInfo['action'] == 'edit' || $this->routeInfo['action'] == 'create'
					|| $this->checkPermissionByMap(self::$groups['stateless']['finanzen'])
					|| $this->checkPermissionByMap(self::$groups['stateless']['owner'])
					|| $this->checkPermissionByMap(self::$groups['stateless']['orga'])) ?
					$this->auslagen_data['address'] :
					'Versteckt';
				?>
				<?= $this->templater->getTextareaForm(
					'address',
					$tmpvalue,
					12,
					"Addresszusatz\nStraße 1\n98693 Ilmenau",
					$tmplabel
				) ?>
                <div class="clearfix"></div>
            </div>
			<?php //--------------------------------------------------------------------
			?>
			<?php
	
			$belege = (isset($this->auslagen_data['belege'])) ? $this->auslagen_data['belege'] : [];
	
			$this->render_beleg_sums($belege, 'Gesamt');
	
			$this->render_beleg_container($belege, $editable, 'Belege');
	
			$beleg_nr = 0;
			$tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
			?>
        </form>
        <label for="projekt-well">Projekt Information</label>
        <div id='projekt-well' class="well">
			<?php $this->render_project_auslagen(true); ?>
        </div>
		<?php
		if ($this->routeInfo['action'] != 'create' && $this->routeInfo['action'] != 'edit'){
			if ($auth->hasGroup('ref-finanzen') || $auth->getUsername() == $this->state2stateInfo(
					'wip;' . $this->auslagen_data['created']
				)['user']){
				$this->render_chat_box();
			}
		}
		?>
		<?php
		$this->render_auslagen_links();
		return;
	}
	
	public function getStateSvg(){
		$diagram = intertopia\Classes\svg\SvgDiagram::newDiagram(intertopia\Classes\svg\SvgDiagram::TYPE_STATE);
		$diagram->setData($this->getDiagramStatelistFiltered());
		$diagram->setServerAspectRadio(false);
		$diagram->setSetting('height', 160);
		$diagram->setSetting('width', 780);
		$diagram->setStateSetting('center_lines', false);
		
		//add state Beschreibung/Legende
		/* @var $r intertopia\Classes\svg\SvgDiagramRaw */
		$r = intertopia\Classes\svg\SvgDiagram::newDiagram(intertopia\Classes\svg\SvgDiagram::TYPE_RAW);
		$diagram->addResultAddons(
			$r->drawShape(650, 100, 120, 25, 0, 'Aktueller Status', 0, ['stroke' => 'none', 'fill' => '#CAFF70'])
		);
		$diagram->addResultAddons(
			$r->drawShape(650, 130, 120, 25, 0, 'Wechsel möglich', 0, ['stroke' => 'none', 'fill' => '#b2f7f9'])
		);
		
		$diagram->generate();
		return $diagram->getChart();
	}
	
	public function getDiagramStatelistFiltered(){
		$set = $this->getDiagramStatelist();
		//states to id
		$keymap = [];
		foreach ($set as $line => $lineset){
			foreach ($lineset as $elementkey => $state){
				$keymap[$state['state']] = [
					'l' => $line,
					'k' => $elementkey,
				];
				if (isset($state['children']))
					foreach ($state['children'] as $childkey => $c){
						$keymap[$c['state']] = [
							'l' => $line,
							'k' => $elementkey,
							'c' => $childkey
						];
					}
			}
		}
		//color current state
		$s = $keymap[$this->stateInfo['state']];
		$set[$s['l']][$s['k']]['options']['fill'] = '#CAFF70';
		//possible states
		foreach (self::$stateChanges[$this->stateInfo['state']] as $k => $dev_null){
			if ($k == 'revocation')
				continue;
			if (!$this->state_change_possible($k))
				continue;
			$s = $keymap[$k];
			//color
			$set[$s['l']][$s['k']]['options']['fill'] = '#b2f7f9';
			//clickable
			$set[$s['l']][$s['k']]['options']['trigger'] = true;
			
		}
		//may remove substate: reject
		if (!$this->checkPermissionByMap(self::$groups['stateless']['finanzen'])){
			$s = $keymap['rejected'];
			unset($set[$s['l']][$s['k']]['children'][$s['c']]);
			$set[$s['l']][$s['k']]['target'] = [['draft', 6, ['y' => 20]]];
			$set[$s['l']][$s['k']]['offset'] = ['x' => 0, 'y' => -20];
			$s = $keymap['ok-hv'];
			unset($set[$s['l']][$s['k']]['children'][$s['c']]);
			$s = $keymap['ok-kv'];
			unset($set[$s['l']][$s['k']]['children'][$s['c']]);
			$s = $keymap['ok-belege'];
			unset($set[$s['l']][$s['k']]['children'][$s['c']]);
		}
		//handle childs
		foreach (self::$subStates as $k => $info){
			$s = $keymap[$k];
			//if substate was unset
			if (!isset($set[$s['l']][$s['k']]['children'][$s['c']]))
				continue;
			//if state = child.parent and substate is set -> continue
			if (strpos($this->stateInfo['substate'], $k) !== false && $this->stateInfo['state'] == $info[0])
				continue;
			if ($this->state_change_possible($k)){
				//color clickable
				$set[$s['l']][$s['k']]['children'][$s['c']]['options']['fill'] = '#b2f7f9';
				//clickable
				$set[$s['l']][$s['k']]['children'][$s['c']]['options']['trigger'] = true;
				
			}
		}
		//color subchilds if state = child.parent and substate is set
		foreach (self::$subStates as $ss => $info){
			$s = $keymap[$ss];
			if ($info[0] == 'revocation')
				continue;
			if (!isset($set[$s['l']][$s['k']]['children'][$s['c']]))
				continue;
			if (strpos($this->stateInfo['substate'], $ss) !== false && $this->stateInfo['state'] == $info[0]){
				$set[$s['l']][$s['k']]['children'][$s['c']]['options']['fill'] = '#CAFF70';
			}
		}
		
		return $set;
	}
	
	/**
	 * return complete state diagram data set
	 */
	public function getDiagramStatelist(){
		return [
			'line0' => [
				0 => [
					'state' => 'draft',
					'title' => self::$states['draft'][0],
					'hovertitle' => self::$states['draft'][1],
					'target' => ['wip']
				],
				1 => [
					'state' => 'wip',
					'title' => self::$states['wip'][0],
					'hovertitle' => self::$states['wip'][1],
					'target' => ['ok', 'revocation'],
					'children' => [
						[
							'state' => 'ok-hv',
							'title' => self::$subStates['ok-hv'][2],
							'hovertitle' => self::$subStates['ok-hv'][3],
							'options' => ['fill' => '#cccccc'],
						],
						[
							'state' => 'ok-kv',
							'title' => self::$subStates['ok-kv'][2],
							'hovertitle' => self::$subStates['ok-kv'][3],
							'options' => ['fill' => '#cccccc'],
						],
						[
							'state' => 'ok-belege',
							'title' => self::$subStates['ok-belege'][2],
							'hovertitle' => self::$subStates['ok-belege'][3],
							'options' => ['fill' => '#cccccc'],
						]
					],
				],
				2 => [
					'state' => 'ok',
					'title' => self::$states['ok'][0],
					'hovertitle' => self::$states['ok'][1],
					'target' => ['instructed', 'revocation']
				],
				3 => [
					'state' => 'instructed',
					'title' => self::$states['instructed'][0],
					'hovertitle' => self::$states['instructed'][1],
					'target' => ['booked'],
					'children' => [
						[
							'state' => 'payed',
							'title' => self::$subStates['payed'][2],
							'hovertitle' => self::$subStates['payed'][3],
							'options' => ['fill' => '#cccccc'],
						],
					],
				],
				4 => [
					'state' => 'booked',
					'title' => self::$states['booked'][0],
					'hovertitle' => self::$states['booked'][1],
					'target' => []
				],
			],
			'line1' => [
				2 => [
					'state' => 'revocation',
					'title' => self::$states['revocation'][0],
					'hovertitle' => self::$states['revocation'][1],
					'offset' => ['x' => 0, 'y' => -85],
					'target' => [['draft', 4, ['y' => 30]]],
					'children' => [
						[
							'state' => 'rejected',
							'title' => self::$subStates['rejected'][2],
							'hovertitle' => self::$subStates['rejected'][3],
							'options' => ['fill' => '#cccccc'],
						],
						[
							'state' => 'revoked',
							'title' => self::$subStates['revoked'][2],
							'hovertitle' => self::$subStates['revoked'][3],
							'options' => ['fill' => '#cccccc'],
						],
					],
				]
			]
		];
	}
	
	//---------------------------------------------------------
	/* ---------- TODOS ------------ */
	
	public function getSvgHiddenFields(){
		return '<div class="svg-statechanges hidden">' .
			'<input type="hidden" name="projekt-id" value="' . $this->projekt_id . '">' .
			'<input type="hidden" name="auslagen-id" value="' . (($this->routeInfo['action'] == 'create') ? 'NEW' : $this->auslagen_id) . '">' .
			'<input type="hidden" name="etag" value="' . (($this->routeInfo['action'] == 'create') ? '0' : $this->auslagen_data['etag']) . '">' .
			'<input type="hidden" name="action" value="' . URIBASE . 'rest/forms/auslagen/state">' .
			'</div>';
	}
	
	/**
	 * masks iban to format
	 * xxxx ... ... xx
	 *
	 * @param string $in iban string
	 *
	 * @return string
	 */
	public static function trimIban($in){
		$in = trim($in);
		if ($in === '')
			return '';
		if (mb_strlen($in) >= 5){
			return mb_substr($in, 0, 4) . ' ... ... ' . mb_substr($in, -2);
		}else{
			return $in;
		}
	}
	
	/**
	 * calculate belege sums of 'Auslagenerstattung'
	 *
	 * @param array  $belege
	 * @param string $labal
	 */
	public function render_beleg_sums($belege, $label = '', $render = true){
		$head_sum_in = 0;
		$head_sum_out = 0;
		$p = [];
		if (!($this->routeInfo['action'] == 'edit' || $this->routeInfo['action'] == 'create')){
			if (count($belege) > 0){
				$head_sum = 0;
				foreach ($belege as $bel){
					if (isset($bel['posten']) && count($bel['posten']) > 0){
						foreach ($bel['posten'] as $ppp){
							$head_sum_in += $ppp['einnahmen'];
							$head_sum_out += $ppp['ausgaben'];
							if ($ppp['einnahmen'] > 0){
								if (!isset($p['in'][$ppp['projekt_posten_id']])){
									$p['in'][$ppp['projekt_posten_id']] = 0;
								}
								$p['in'][$ppp['projekt_posten_id']] += $ppp['einnahmen'];
							}
							if ($ppp['ausgaben'] > 0){
								if (!isset($p['out'][$ppp['projekt_posten_id']])){
									$p['out'][$ppp['projekt_posten_id']] = 0;
								}
								$p['out'][$ppp['projekt_posten_id']] += $ppp['ausgaben'];
							}
						}
					}
				}
			}
		}
		
		if ($render && ($head_sum_in > 0 || $head_sum_out > 0)){
			$this->_render_beleg_sums($head_sum_in, $head_sum_out, $label);
		}
		
		return ['id' => $this->auslagen_id, 'in' => $head_sum_in, 'out' => $head_sum_out, 'p' => $p];
	}
	
	/**
	 * render belege box
	 *
	 * @param float  $in
	 * @param float  $out
	 * @param string $float
	 */
	private function _render_beleg_sums($in, $out, $label = ''){
		if ($label){
			echo '<label>' . $label . '</label>';
		}
		?>
        <div class="beleg-sum-table well">
            <div class="row">
                <div class="form-group">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-3"><strong>Einnahmen:</strong></div>
                    <div class="col-sm-2"><?= number_format($in, 2); ?></div>
                    <div class="col-sm-3"><strong>Ausgaben:</strong></div>
                    <div class="col-sm-2"><?= number_format($out, 2); ?></div>
                </div>
            </div>
        </div>
		<?php
	}
	
	/**
	 *
	 * @param array   $beleg
	 *        [
	 *        'id' => NULL,
	 *        'short' => '',
	 *        'created_on' => date_create()->format('Y-m-d H:i:s'),
	 *        'datum' => '',
	 *        'beschreibung' => '',
	 *        'file_id' => NULL,
	 *        'file' => NULL,
	 *        'posten' => []
	 *        ]
	 * @param boolean $hidden
	 */
	public function render_beleg_container($belege, $editable = true, $label = ''){
		if ($label){
			echo '<label>' . $label . '</label>';
		} ?>

        <div class="beleg-table well<?= ($editable) ? ' editable' : '' ?>">
            <div class="hidden datalists">
                <datalist class="datalist-projekt">
                    <option value="0" data-alias="Bitte Wählen">
						<?php foreach ($this->projekt_data['posten'] as $p){
						?>
                    <option value="<?= $p['id']; ?>"
                            data-alias="<?= (($p['einnahmen']) ? '[Einnahme] ' : '') . (($p['ausgaben']) ? '[Ausgabe] ' : '') . $p['name'] ?>">
						<?php } ?>
                </datalist>
            </div>
            <div class="row row-striped">
                <div class="form-group">
                    <div class="col-sm-1"><strong>Beleg</strong></div>
                    <div class="col-sm-11"></div>
                </div>
            </div>
			<?php if (count($belege) == 0){ ?>
                <div class="row row-striped">
                    <div class="form-group no-belege-info">
                        <div class="col-sm-12"><strong style="font-size: 2em;">Keine Belege eingereicht</strong></div>
                    </div>
                </div>
			<?php }
	
			foreach ($belege as $b){
				echo $this->render_beleg_row($b, $editable);
			}
	
			//render hidden beleg for js copy
			if ($editable){
				echo $this->render_beleg_row(
					[
						'id' => '',
						'short' => '',
						'created_on' => '',
						'datum' => '',
						'beschreibung' => '',
						'file_id' => null,
						'file' => null,
						'posten' => []
					],
					$editable,
					true
				);
			}
			if ($editable){ ?>
                <div class="row row-striped add-button-row" style="margin: 10px 0;">
                    <div class="add-belege" style="padding:5px;">
                        <div class="text-center">
                            <button type="button" class="btn btn-success" style="min-width:100px; font-weight: bold;">+
                                Beleg ergänzen
                            </button>
                        </div>
                    </div>
                </div>
			<?php } ?>
        </div>
		<?php
	}
	
	/**
	 * render beleg line
	 *
	 * @param array $beleg beleg data
	 * @param bool  $editable
	 * @param bool  $hidden
	 *
	 * @return string
	 */
	public function render_beleg_row($beleg, $editable = true, $hidden = false){
		ob_start();
		$date = ($beleg['datum']) ? date_create($beleg['datum'])->format('d.m.Y') : '';
		$date_value = ($beleg['datum']) ? date_create($beleg['datum'])->format('Y-m-d') : '';
		$date_form = ($editable) ? $this->templater->getDatePickerForm(
			($hidden) ? '' : "belege[{$beleg['id']}][datum]",
			$date_value,
			0,
			"",
			"",
			[]
		) : '<strong>am </strong>' . $date;
		
		$file_form = '';
		if (!$hidden){
			if ($beleg['file_id']){
				$file_form = '<div class="beleg-file btn-default" style=" border: 1px solid #ddd; border-radius: 5px; padding: 5px 10px; position: relative;" data-id="' . $beleg['file_id'] . '">' .
					'<a href="' . URIBASE . 'files/get/' . $beleg['file']['hashname'] . '">' . $beleg['file']['filename'] . '.' . $beleg['file']['fileextension'] . '</a>' .
					(($this->stateInfo['editable']) ? ('<button type="button" title="Löschen" class="file-delete btn btn-default pull-right">X</button>') : '') .
					'<div><small><span style="min-width: 50px; display: inline-block; font-weight: bold;">Size: </span>' .
					'<span>' . FileHandler::formatFilesize($beleg['file']['size']) . '</span></small>' .
					'<small><span style="min-width: 50px; display: inline-block; margin-left: 10px; font-weight: bold;">Mime: </span>' .
					'<span>' . $beleg['file']['mime'] . '</span></small>' .
					'</div>' .
					'</div>';
			}else{
				if ($editable){
					$file_form = $this->templater->getFileForm(
						"files[beleg_{$beleg['id']}][]",
						0,
						0,
						"Datei...",
						"",
						[]
					);
				}else{
					$file_form = '<span>Keine Datei verknüpft.</span>';
				}
			}
		}else{
			$file_form = $this->templater->getFileForm("", 0, 0, "Datei...", "", []);
		}
		
		$desc_form = '';
		if ($editable){
			$desc_form = $this->templater->getTextareaForm(
				($hidden) ? '' : "belege[{$beleg['id']}][beschreibung]",
				($beleg['beschreibung']) ? $beleg['beschreibung'] : "",
				0,
				"optional",
				"",
				[],
				1
			);
		}else{
			$desc_form = '<span>' . ($beleg['beschreibung']) ? $beleg['beschreibung'] : "keine" . '</span>';
		}
		
		
		?>
        <div class="row row-striped <?= ($hidden) ? 'hidden' : 'bt-dark' ?>" style="padding: 5px;">
            <div class="form-group<?= ($hidden) ? ' beleg-template' : ' beleg-container' ?>"
                 data-id="<?= $beleg['id']; ?>">
                <div class="col-sm-1 beleg-idx-box">
                    <div class="form-group">
                        <div class="col-sm-6 beleg-idx"></div>
                        <div class="col-sm-6 beleg-nr"><?= $beleg['short']; ?><?=
							($editable) ? '<a href="#" class="delete-row"> <i class="fa fa-fw fa-trash"></i></a>' : '' ?></div>
                    </div>
                </div>
                <div class="col-sm-11 beleg-inner">
                    <div class="form-group">
                        <div class="col-sm-4"><strong>Datum des Belegs</strong></div>
                        <div class="col-sm-8"><strong>Scan des Belegs (nur PDF erlaubt)</strong></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-4 beleg-date"><?= $date_form ?></div>
                        <div class="col-sm-8 beleg-file"><?= $file_form ?></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12"><strong>Beschreibung</strong></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 beleg-desc"><?= $desc_form ?></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 beleg-data well" style="margin-top: 10px;"><?php
							echo $this->render_beleg_posten_table($beleg['posten'], $editable, $hidden, $beleg['id']);
							?></div>
                    </div>
                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * render beleg posten table
	 *
	 * @param array $posten
	 * @param bool  $editable
	 * @param bool  $hidden
	 * @param bool  $beleg_id
	 *
	 * @return string
	 */
	public function render_beleg_posten_table($posten, $editable, $hidden, $beleg_id){
		$out = '<div class="row row-striped" style="padding: 5px; border-bottom: 2px solid #ddd;">
					<div class="form-group posten-headline">
						<div class="col-sm-1"></div>
						<div class="col-sm-5"><strong>Projektposten</strong></div>
						<div class="col-sm-3"><strong>Einnahmen</strong></div>
						<div class="col-sm-3"><strong>Ausgaben</strong></div>
					</div>
				</div>';
		
		$sum_in = 0;
		$sum_out = 0;
		$out .= '<div class="row row-striped posten-inner-list" style="padding: 5px; border-bottom: 2px solid #ddd;">';
		
		// if empty and !$editable add empty hint
		$out .= '<div class="form-group posten-empty ' . ((count(
					$posten
				) == 0) ? '' : ' hidden') . '">Keine Angaben</div>';
		
		$inOutPrefix = ['0' => ''];
		foreach ($this->projekt_data['posten'] as $pp){
			$inOutPrefix[$pp['id']] = (($pp['einnahmen']) ? '[Einnahme] ' : '') . (($pp['ausgaben']) ? '[Ausgabe] ' : '');
		}
		
		// if nonempty add lines
		foreach ($posten as $pline){
			$out .= '<div class="form-group posten-entry" data-id="' . $pline['id'] . '" data-projekt-posten-id="' . $pline['projekt_posten_id'] . '">';
			//position counter + trash bin
			$out .= '<div class="col-sm-1 posten-counter">
						' . (($editable) ? '<a><i class="fa fa-fw fa-trash"></i></a>' : '') . '
					</div>';
			//short name / position
			$out .= '<div class="col-sm-1 posten-short">P' . $pline['short'] . '</div>';
			//posten_name
			if ($editable){
				$out .= '<div class="col-sm-4 editable projekt-posten-select" data-value="' . $pline['projekt_posten_id'] . '">'
					. '<div class="input-group form-group">'
					. '<span class="value">' . $inOutPrefix[$pline['projekt_posten_id']] . $pline['projekt.posten_name'] . '</span>'
					. '<input type="hidden" name="belege[' . $beleg_id . '][posten][' . $pline['id'] . '][projekt-posten]" value="' . $pline['projekt_posten_id'] . '">'
					. '</div>'
					. '</div>';
			}else{
				$out .= '<div class="col-sm-4 posten-name">' . $pline['projekt.posten_name'] . '</div>';
			}
			
			//einnahmen
			if ($editable){
				$out .= '<div class="col-sm-3 posten-in">'
					. '<div class="input-group form-group">'
					. '<input class="form-control" name="belege[' . $beleg_id . '][posten][' . $pline['id'] . '][in]" type="number" step="0.01" min="0" value="' . $pline['einnahmen'] . '">'
					. '<div class="input-group-addon">€</div>'
					. '</div>'
					. '</div>';
			}else{
				$out .= '<div class="col-sm-3 posten-in">' . $pline['einnahmen'] . '</div>';
			}
			$sum_in += $pline['einnahmen'];
			
			//ausgaben
			if ($editable){
				$out .= '<div class="col-sm-3 posten-out">'
					. '<div class="input-group form-group">'
					. '<input class="form-control" name="belege[' . $beleg_id . '][posten][' . $pline['id'] . '][out]" type="number" step="0.01" min="0" value="' . $pline['ausgaben'] . '">'
					. '<div class="input-group-addon">€</div>'
					. '</div>'
					. '</div>';
			}else{
				$out .= '<div class="col-sm-3 posten-out">' . $pline['ausgaben'] . '</div>';
			}
			$sum_out += $pline['ausgaben'];
			
			$out .= '<div style="clear:both;"></div></div>';
		}
		
		//if $ediatable add __auto add line__
		if ($editable){
			$out .= '<div class="form-group posten-entry-new">';
			//position counter + trash bin
			$out .= '<div class="col-sm-1 posten-counter">
						<a><i class="hidden fa fa-fw fa-trash"></i></a>
           				<i class="fa fa-fw fa-2x fa-plus text-success" style="opacity: 0.5;"></i>
					</div>';
			//short name / position
			$out .= '<div class="col-sm-1 posten-short"></div>';
			//posten_name
			$out .= '<div class="col-sm-4 editable projekt-posten-select" data-value="0">'
				. '<div class="input-group form-group">'
				. '<span class="value"></span>'
				. '<input type="hidden" value="0">'
				. '</div>'
				. '</div>';
			
			//einnahmen
			$out .= '<div class="col-sm-3 posten-in">'
				. '<div class="input-group form-group">'
				. '<input class="form-control" type="number" step="0.01" min="0" value="0">'
				. '<div class="input-group-addon">€</div>'
				. '</div>'
				. '</div>';
			
			//ausgaben
			$out .= '<div class="col-sm-3 posten-out">'
				. '<div class="input-group form-group">'
				. '<input class="form-control" type="number" step="0.01" min="0" value="0">'
				. '<div class="input-group-addon">€</div>'
				. '</div>'
				. '</div>';
			
			$out .= '<div style="clear:both;"></div></div>';
		}
		
		$out .= '</div>';
		$out .= '<div class="row row-striped" style="padding: 5px; border-top: 2px solid #ddd;">
    				<div class="form-group posten-sum-line">
						<div class="col-sm-1"></div>
						<div class="col-sm-5"></div>
						<div class="col-sm-3 posten-sum-in"><strong><span style="width: 10%;">Σ</span><span class="text-right" style="display: inline-block; padding-right: 10px; width: 80%;">' . number_format(
				$sum_in,
				2
			) . '</span><span style="width: 10%;">€</span></strong></div>
						<div class="col-sm-3 posten-sum-out"><strong><span style="width: 10%;">Σ</span><span class="text-right" style="display: inline-block; padding-right: 10px; width: 80%;">' . number_format(
				$sum_out,
				2
			) . '</span><span style="width: 10%;">€</span></strong></div>
					</div>
    			</div>';
		return $out;
	}
	
	public function render_project_auslagen($echo = false){
		$out = '';
		if (count($this->projekt_data['auslagen']) == 0){
			$out .= '<label for="auslagen-vorhanden">Im Projekt vorhandene Abrechnungen</label>';
			$out .= '<div  class="well" style="margin-bottom: 0px; background-color: white;"><span>Keine</span></div>';
		}else{
			$tmpList = [];
			$show_creator = $this->checkPermissionByMap(self::$groups['stateless']['view_creator']);
			foreach ($this->projekt_data['auslagen'] as $auslage){
				$tmp_state = self::state2stateInfo($auslage['state']);
				$created = self::state2stateInfo('draft;' . $auslage['created']);
				$name = $auslage['id'] . ' - ' . ($auslage['name_suffix'] ? $auslage['name_suffix'] : '(Ohne Namen)') . '<strong><small style="margin-left: 10px;">' . $created['date'] . '</small>' . (($show_creator) ? '<small style="margin-left: 10px;">[' . $created['realname'] . ']</small>' : '') . '</strong>';
				
				$tmpList[] = [
					'html' => $name . '<span class="label label-info pull-right"><span>Status: </span><span>' . self::$states[$tmp_state['state']][0] . '</span></span>',
					'attr' => [
						'href' => URIBASE . 'projekt/' . $this->projekt_id . '/auslagen/' . $auslage['id'],
						'style' => 'color: #3099c2;'
					],
				
				];
			}
			$out .= $this->templater->generateListGroup(
				$tmpList,
				'Im Projekt vorhandene Abrechnungen',
				false,
				true,
				'',
				'a',
				'col-xs-12 col-md-10'
			);
		}
		if ($echo)
			echo $out;
		return $out;
	}
	
	private function render_chat_box(){ ?>
        <div class='clearfix'></div>
        <div id="auslagenchat">
			<?php
			$auth = (AUTH_HANDLER);
			/* @var $auth AuthHandler */
			$auth = $auth::getInstance();
			$btns = [];
			$pdate = date_create(substr($this->auslagen_data['created'], 0, 4) . '-01-01 00:00:00');
			$pdate->modify('+1 year');
			$now = date_create();
			//allow chat only 90 days into next year
			if ($now->getTimestamp() - $pdate->getTimestamp() <= 86400 * 90){
				if ($auth->hasGroup('ref-finanzen') || $auth->getUsername() == self::state2stateInfo(
						'wip;' . $this->auslagen_data['created']
					)['user']){
					$btns[] = [
						'label' => 'Private Nachricht',
						'color' => 'warning',
						'type' => '-1',
						'hover-title' => 'Private Nachricht zwischen Ref-Finanzen und dem Abrechnungs-Ersteller'
					];
				}
				if ($auth->hasGroup('ref-finanzen')){
					$btns[] = ['label' => 'Finanz Nachricht', 'color' => 'primary', 'type' => '3'];
				}
			}
			ChatHandler::renderChatPanel(
				'auslagen',
				$this->auslagen_id,
				$auth->getUserFullName() . " (" . $auth->getUsername() . ")",
				$btns
			); ?>
        </div>
		<?php
	}
	
	public function render_auslagen_links(){
		?>
        <div class="auslagen-links">
			<?php if ($this->routeInfo['action'] != 'edit' && isset($this->stateInfo['editable_link']) && $this->stateInfo['editable_link']){ ?>
                <div class="col-xs-12 form-group">
                    <strong><a class="btn btn-success text-center" style="font-weight: bold;"
                               href="<?= URIBASE . "projekt/{$this->projekt_id}/auslagen/{$this->auslagen_id}/edit" ?>"><i
                                    class="fa fa-fw fa-edit"> </i>Bearbeiten</a></strong>
                </div>
                <div class="clearfix"></div>
			<?php } ?>
			<?php if ($this->stateInfo['editable']){
				foreach ($this->formSubmitButtons as $formId){ ?>
                    <div class="col-xs-12 form-group">
                        <strong>
                            <button data-for="<?= $formId; ?>" type="button"
                                    class="btn btn-success auslagen-form-submit-send" style="font-weight: bold;"><i
                                        class="fa fa-fw fa-save"> </i>Speichern
                            </button>
                        </strong>
                    </div>
				<?php }
				if (isset($this->auslagen_data['id'])){ ?>
                    <div class="col-xs-12 form-group">
                        <strong><a href="<?= URIBASE . "projekt/{$this->projekt_id}/auslagen/{$this->auslagen_data['id']}" ?>"
                                   class="btn btn-danger" style="font-weight: bold;"><i class="fa fa-fw fa-times"> </i>Abbrechen</a></strong>
                    </div>
				<?php } ?>
			<?php } ?>
			<?php if ($this->routeInfo['action'] != 'edit'
				&& isset($this->auslagen_data['id'])
				&& isset($this->auslagen_data['belege'])
				&& count($this->auslagen_data['belege']) > 0){ ?>
                <div class="col-xs-12 form-group">

                    <button data-afor="<?= $this->auslagen_data['id']; ?>" data-pfor="<?= $this->projekt_id; ?>"
                            data-action="<?= URIBASE ?>rest/forms/auslagen/belegpdf" type="button"
                            class="btn btn-primary auslagen-belege-pdf"><i class="fa fa-fw fa-print"> </i>Belege PDF
                    </button>
                </div>
                <div class="clearfix"></div>
                <div class="col-xs-12 form-group">
                    <form method="POST" action="<?= URIBASE ?>rest/forms/auslagen/belegpdf">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-fw fa-download"> </i>Belege PDF
                        </button>
                        <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
                        <input type="hidden" name="auslagen-id" value="<?= $this->auslagen_id ?>">
                        <input type="hidden" name="projekt-id" value="<?= $this->projekt_id ?>">
                        <input type="hidden" name="d" value="1">
                    </form>
                </div>
                <div class="clearfix"></div>
			<?php }
			if ($this->routeInfo['action'] != 'edit'
				&& isset($this->auslagen_data['id'])
				&& in_array($this->stateInfo['state'], ["ok", "instructed", "booked"])
			){ ?>
                <div class="col-xs-12 form-group">
                    <button data-afor="<?= $this->auslagen_data['id']; ?>" data-pfor="<?= $this->projekt_id; ?>"
                            data-action="<?= URIBASE ?>rest/forms/auslagen/zahlungsanweisung" type="button"
                            class="btn btn-primary auslagen-belege-pdf"><i class="fa fa-fw fa-print"> </i>
                        Zahlungsanweisung PDF
                    </button>
                </div>
                <div class="col-xs-12 form-group">
                    <form method="POST" action="<?= URIBASE ?>rest/forms/auslagen/zahlungsanweisung">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-fw fa-download"> </i>
                            Zahlungsanweisung PDF
                        </button>
                        <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
                        <input type="hidden" name="auslagen-id" value="<?= $this->auslagen_id ?>">
                        <input type="hidden" name="projekt-id" value="<?= $this->projekt_id ?>">
                        <input type="hidden" name="d" value="1">
                    </form>
                </div>
                <div class="clearfix"></div>
				<?php
			}
			if (false && $this->routeInfo['action'] != 'edit'){ ?>
                <input type="hidden" name="projekt-id" value="<?= $this->projekt_id; ?>">
                <input type="hidden" name="auslagen-id"
                       value="<?= ($this->routeInfo['action'] == 'create') ? 'NEW' : $this->auslagen_id; ?>">
                <input type="hidden" name="etag"
                       value="<?= ($this->routeInfo['action'] == 'create') ? '0' : $this->auslagen_data['etag']; ?>">
                <input type="hidden" name="action" value="<?= URIBASE ?>rest/forms/auslagen/state">
				<?php if ($this->auslagen_id){
					foreach (self::$stateChanges[$this->stateInfo['state']] as $k => $dev_null){
						if ($k == 'revocation')
							continue;
						if (!$this->state_change_possible($k))
							continue;
				
						?>
                        <div class="col-xs-12 form-group">
                            <button type="button" class="btn btn-default state-changes-now"
                                    title="<?= self::$states[$k][0] ?>"
                                    data-newstate="<?= $k ?>"><?= self::$states[$k][1] ?></button>
                        </div>
                        <div class="clearfix"></div>
						<?php
					}
					foreach (self::$subStates as $k => $info){
						if ($this->state_change_possible($k)){
							?>
                            <div class="col-xs-12 form-group">
                                <button type="button" class="btn btn-default state-changes-now" title="<?= $info[3] ?>"
                                        data-newstate="<?= $k ?>"><?= $info[2] ?></button>
                            </div>
                            <div class="clearfix"></div>
							<?php
						}
					}
				}
			}
			?>

        </div>
		<?php
	}
	
	public function render_auslagen_beleg_diagrams($label = ''){
		?>
        <div class="panel-group well col-xs-12 col-md-10" id="accordion">
            <div class="panel panel-default">
                <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                     href="#collapse_diag_1">
                    <h4 class="panel-title">
                        <i class="fa fa-fw fa-togglebox"></i><strong>&nbsp;<?= $label ?></strong>
                    </h4>
                </div>
                <div id="collapse_diag_1" class="panel-collapse collapse">
                    <div class="panel-body"><?php
						$out = $this->get_auslagen_beleg_sums();
						if (isset($out['error']))
							foreach ($out['error'] as $err_msg){
								echo '<strong class="text-danger" style="padding: 5px; margin-bottom: 5px; border: 2px solid #dd2222; border-radius: 5px; display: inline-block;">' . $err_msg . '</strong>';
							}
						echo '<div class="row">';
						foreach ($out as $key => $value){
							if ($key === 'error')
								continue;
							echo '<div class="form-group">';
							echo '<div class="col-sm-' . (12) . '"><strong>' . $value['headline'] . '</strong></div>';
							echo '</div>';
							echo '<div class="form-group">';
							echo '<div class="col-sm-' . (5) . ' project-svg">' . $value['image_pie'] . '</div>';
							echo '<div class="col-sm-' . (7) . ' project-svg">' . $value['image_adding_beam'] . '</div>';
							echo '</div>';
						}
						echo '</div>';
						?></div>
                </div>
            </div>
        </div>
		<?php
	}
	
	//TODO private $selectable_users;
	//TODO private $selectable_posten;
	
	
	/*
	 'projekt' => [
		 'id' => '',
		 'creator_id' => '',
		 'createdat' => '',
		 'lastupdated' => '',
		 'version' => '',
		 'state' => '',
		 'stateCreator_id' => '',
		 'name' => '',
		 'responsible' => '',
		 'org' => '',
		 'org-mail' => '',
		 'protokoll' => '',
		 'recht' => '',
		 'recht-additional' => '',
		 'date-start' => '',
		 'date-end' => '',
		 'beschreibung' => '',
		 'posten' => []
		 'auslagen' => [
			 'id' => ''
			 'suffix' => ''
			 'status' => ''
		 ],
	 ]

	 'belege' => [
		 [
			 'auslagen_id',
			 'id' => NULL,
			 'short' => '',
			 'created_on' => date_create()->format('Y-m-d H:i:s'),
			 'datum' => '',
			 'beschreibung' => '',
			 'file_id' => NULL,
			 'file' => NULL,
			 'posten' => []
		 ]
	 ]
	 'posten' => [
		 [
			 'beleg_id',
			 'id' => NULL,
			 'short' => '',
			 'projekt_posten_id' => NULL,
			 'projekt.posten_name' => NULL;
			 'ausgaben' => '',
			 'einnahmen' => ''
		 ]
	 ]
	 'file' => [
		 'id' => NULL,
		 'link' => NULL,
		 'added_on' => '',
		 'hashname' => '',
		 'filename' => '',
		 'size' => '',
		 'fileextension' => '',
		 'mime' => '',
		 'encoding' => '',
		 'data' => NULL,
	 ]
	 */
	
	/**
	 * draw auslagen project diagrams
	 *
	 * @param string $label
	 */
	public function get_auslagen_beleg_sums(){
		//project posten ------------------
		$p_in_max = 0;
		$p_out_max = 0;
		$p_d = [];
		$p_added_beam_data = ['in' => [], 'out' => []];
		foreach ($this->projekt_data["posten"] as $pos => $posten){
			$p_in_max += $posten["einnahmen"];
			$p_out_max += $posten["ausgaben"];
			$p_d[$posten["id"]] = [
				'in_max' => $posten["einnahmen"],
				'out_max' => $posten["ausgaben"],
				'pos' => $pos,
				'name' => $posten["name"],
			];
		}
		
		// --------------------------
		//var_dump($p_d);
		
		$g_sums = [
			'in' => 0,
			'out' => 0,
		];
		$a_sums = ['in' => [], 'out' => []];
		
		foreach ($this->projekt_data['auslagen'] as $auslage){
			$this->auslagen_id = $auslage['id'];
			$this->getDbAuslagen();
			$tmp_state = self::state2stateInfo($auslage['state']);
			if ($tmp_state['state'] == 'draft' || $tmp_state['state'] == 'revocation')
				continue;
			$this->getDbBelegePostenFiles();
			
			$belege = (isset($this->auslagen_data['belege'])) ? $this->auslagen_data['belege'] : [];
			$sums = $this->render_beleg_sums($belege, '', false);
			
			$g_sums['in'] += $sums['in'];
			$g_sums['out'] += $sums['out'];
			if ($sums['in'] > 0)
				$a_sums['in']['Abrechnung ' . $sums['id'] . ' - ' . $sums['in'] . '€'] = [$sums['in']];
			if ($sums['out'] > 0)
				$a_sums['out']['Abrechnung ' . $sums['id'] . ' - ' . $sums['out'] . '€'] = [$sums['out']];
			
			foreach ($p_d as $posten_id => $ignore){
				if (isset($sums['p']['in']))
					$p_added_beam_data['in'][$posten_id][$auslage['id']] = (isset($sums['p']['in'][$posten_id])) ? $sums['p']['in'][$posten_id] : 0;
				if (isset($sums['p']['out']))
					$p_added_beam_data['out'][$posten_id][$auslage['id']] = (isset($sums['p']['out'][$posten_id])) ? $sums['p']['out'][$posten_id] : 0;
			}
		}
		foreach ($p_d as $posten_id => $posten_info){
			$p_added_beam_data['in'][$posten_id][] = (array_sum(
					$p_added_beam_data['in'][$posten_id]
				) < $posten_info['in_max']) ? $posten_info['in_max'] - array_sum(
					$p_added_beam_data['in'][$posten_id]
				) : 0;
			$p_added_beam_data['out'][$posten_id][] = (array_sum(
					$p_added_beam_data['out'][$posten_id]
				) < $posten_info['out_max']) ? $posten_info['out_max'] - array_sum(
					$p_added_beam_data['out'][$posten_id]
				) : 0;
		}
		$a_sums['in'][((($p_in_max - $g_sums['in']) > 0) ? 'Nicht vergeben' : 'Überzogen') . ' - ' . ($p_in_max - $g_sums['in']) . '€'] = [(($p_in_max - $g_sums['in']) > 0) ? $p_in_max - $g_sums['in'] : 0];
		$a_sums['out'][((($p_out_max - $g_sums['out']) > 0) ? 'Nicht vergeben' : 'Überzogen') . ' - ' . ($p_out_max - $g_sums['out']) . '€'] = [(($p_out_max - $g_sums['out']) > 0) ? $p_out_max - $g_sums['out'] : 0];
		
		$out_html = [];
		
		if ($p_out_max < $g_sums['out']){
			$out_html['error'][] = 'Das Projektmaximum bei den Einnahmen oder Ausgaben wurde überschritten!';
		}
		
		if ($p_in_max > 0){
			$color = array(
				'red',
				'blue',
				'green',
				'yellow',
				'purple',
				'cyan',
				'#ef888d',
				'#d2a7e5',
				'#e5cd87',
				'#c639f9',
				'#e5c67e',
				'#2bc6bf',
				'#9ef7df',
				'#f2d42e',
				'#e5c97b',
				'#e2ae53',
				'#d1a429',
				'#d35d86',
				'#caf963',
				'#de54f9',
				'#aae06d',
				'#db76f2',
				'#ff0c51',
				'#b6f7a3',
				'#ea7598',
				'#09627c',
				'#2547dd',
				'#99bedb',
				'#b73331',
				'#aaffbd',
				'#ce0a04',
				'#dab0fc',
				'#e8d140',
				'#b1ef77',
				'#506cc9',
				'#ed07ca',
			);
			$color[count($a_sums['in']) - 1] = '#fff';
			
			$d_pie = intertopia\Classes\svg\SvgDiagram::newDiagram(intertopia\Classes\svg\SvgDiagram::TYPE_PIE);
			$d_pie->setData($a_sums['in']);
			$d_pie->setServerAspectRadio(false);
			$d_pie->setSetting('height', 600);
			$d_pie->setSetting('width', 600);
			$d_pie->setPieSetting('perExplanationLineBelow', 1);
			$d_pie->overrideColorArray($color);
			
			$d_adding_beam = intertopia\Classes\svg\SvgDiagram::newDiagram(
				intertopia\Classes\svg\SvgDiagram::TYPE_ADDINGBLOCK
			);
			$tmp = [];
			foreach ($p_added_beam_data['in'] as $key => $value){
				$tmp["$key - " . substr($p_d[$key]['name'], 0, 6) . '..'] = $value;
			}
			$d_adding_beam->setData($tmp);
			$d_adding_beam->setServerAspectRadio(false);
			$d_adding_beam->setSetting('height', 600);
			$d_adding_beam->overrideColorArray($color);
			$d_adding_beam->setAchsisDescription(array('y' => '€', 'x' => 'Projektposten'));
			
			$d_pie->generate();
			$d_adding_beam->generate();
			$out_html[] = [
				'headline' => 'Einnahmen',
				'image_pie' => $d_pie->getChart(),
				'image_adding_beam' => $d_adding_beam->getChart()
			];
		}
		if ($p_out_max > 0){
			$color = array(
				'red',
				'blue',
				'green',
				'yellow',
				'purple',
				'cyan',
				'#ef888d',
				'#d2a7e5',
				'#e5cd87',
				'#c639f9',
				'#e5c67e',
				'#2bc6bf',
				'#9ef7df',
				'#f2d42e',
				'#e5c97b',
				'#e2ae53',
				'#d1a429',
				'#d35d86',
				'#caf963',
				'#de54f9',
				'#aae06d',
				'#db76f2',
				'#ff0c51',
				'#b6f7a3',
				'#ea7598',
				'#09627c',
				'#2547dd',
				'#99bedb',
				'#b73331',
				'#aaffbd',
				'#ce0a04',
				'#dab0fc',
				'#e8d140',
				'#b1ef77',
				'#506cc9',
				'#ed07ca',
			);
			$color[count($a_sums['out']) - 1] = '#fff';
			
			$d_pie = intertopia\Classes\svg\SvgDiagram::newDiagram(intertopia\Classes\svg\SvgDiagram::TYPE_PIE);
			$d_pie->setData($a_sums['out']);
			$d_pie->setServerAspectRadio(false);
			$d_pie->setSetting('height', 600);
			$d_pie->setSetting('width', 600);
			$d_pie->setPieSetting('perExplanationLineBelow', 1);
			$d_pie->overrideColorArray($color);
			
			$d_adding_beam = intertopia\Classes\svg\SvgDiagram::newDiagram(
				intertopia\Classes\svg\SvgDiagram::TYPE_ADDINGBLOCK
			);
			$tmp = [];
			foreach ($p_added_beam_data['out'] as $key => $value){
				$tmp["$key - " . substr($p_d[$key]['name'], 0, 6) . '..'] = $value;
			}
			$d_adding_beam->setData($tmp);
			$d_adding_beam->setServerAspectRadio(false);
			$d_adding_beam->setSetting('height', 600);
			$d_adding_beam->overrideColorArray($color);
			$d_adding_beam->setAchsisDescription(array('y' => '€', 'x' => 'Projektposten'));
			
			$d_pie->generate();
			$d_adding_beam->generate();
			$out_html[] = [
				'headline' => 'Ausgaben',
				'image_pie' => $d_pie->getChart(),
				'image_adding_beam' => $d_adding_beam->getChart()
			];
		}
		return $out_html;
	}
	
	public function updateSavedData($data){
		// TODO: Implement updateSavedData() method.
	}
	
	public function setState($stateName){
		// TODO: Implement setState() method.
	}
	
	public function getNextPossibleStates(){
		// TODO: Implement getNextPossibleStates() method.
	}
}
