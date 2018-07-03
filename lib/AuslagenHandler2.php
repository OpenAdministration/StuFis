<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:55
 */

class AuslagenHandler2 extends FormHandlerInterface{
    
	//---------------------------------------------------------
	/* ------- MEMBER VARIABLES -------- */
	/**
     * error flag
     * set in constructor
     * @var boolean
     */
    private $error = NULL;
    
    /**
     * jeson result set
     * @var array
     */
    private $json_result = [];
    
    /**
     * 
     * @var DBConnector
     */
    private $db;
	
    /**
     * Projekt id
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
     * @var int
     */
	private $auslagen_id;
	
	/**
	 * auslagen data
	 * @var $data
	 */
	private $auslagen_data;
	
	/**
	 * additional title
	 * @var string
	 */
	private $title;
	
	/**
	 * routing info
	 * @var array
	 */
	private $routeInfo;
	
	/**
	 * current state info
	 * @var array
	 */
	private $stateInfo = [
		'state' => 'draft',
		'substate' => 'draft',
		'date'	=> '',
		'user'	=> '',
	];
	
	//---------------------------------------------------------
	/* ------- CLASS STATIC VARIABLES -------- */
	
	/**
	 * list of different groups
	 * and permission
	 * @var array
	 */
	private static $groups = [
		'editable' => [
			'draft' => ["groups" => ["sgis"]],
			'wip' 	=> ["groups" => ["ref-finanzen-hv"]],
			'ok' 	=> ["groups" => ["ref-finanzen-hv"]],
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
			'finanzen' => ["groups" => ["ref-finanzen"]]
		]
	];
	
	/**
	 * possible states inside db
	 * @var array
	 */
	private static $states = [
		'draft' 		=>	['Entwurf', 		'Als Entwurf speichern'],
		'wip' 			=>	['Eingereicht',	'Beantragen'], 
		'ok' 			=>	['Genehmigt',	'Genehmigen'], 
		'instructed'	=>	['Angewiesen',	'Anweisen'], 
		'booked'		=>	['Gebucht',		'Gezahlt und Gebucht'], 
		'revocation'	=>	['Nichtig',		''] 
	];
	
	/**
	 * possible substates
	 * may multile subStates are possible
	 * mostly only represented by flags
	 * needed for state diagram
	 * format
	 * 	substate => 	[state, required on statechange, label text, action text]
	 * @var array
	 */
	private static $subStates = [
		'ok-hv'		=>	['wip',			true,	'OK HV',			'als Haushaltsverantwortlicher genehmigen'],
		'ok-kv'		=>	['wip',			true,	'OK KV',			'als Kassenverantwortlicher genehmigen'],
		'ok-belege'	=>	['wip',			true,	'Belege vorhanden',	'Original Belege vorliegend'],
		'revoked'	=>	['revocation',	false,	'Zurückgezogen',	'Zurückziehen'],
		'rejected'	=>	['revocation',	false,	'Abgelehnt',		'Ablehnen'],
		'payed'		=>	['instructed',	true,	'Bezahlt',			'Bezahlt (lt. Kontoauszug)'], 
	];
	
	/**
	 * possible statechanges
	 * 	current state => next group => permission
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
		'ok-hv'		=>	[
			"wip" => ["groups" => ["ref-finanzen-hv"]],
		],
		'ok-kv'		=>	[
			"wip" => ["groups" => ["ref-finanzen-kv"]],
		],
		'ok-belege'		=>	[
			"wip" => ["groups" => ["ref-finanzen"]],
		],
		'payed'		=>	[
			"instructed" => ["groups" => ["ref-finanzen-kv"]],
		],
		'revoked'		=>	[
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
		'rejected'		=>	[
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
		'kv-ok' => '',
		'hv-ok' => '',
	];
	
	/**
	 * 
	 * @return multitype:NULL string number multitype:
	 */
	private function get_empty_auslage(){
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = AuthHandler::getInstance()->getUsername();
		return [
			"id" => NULL,
			"projekt_id" => $this->projekt_id,
			"created" => "{$newInfo['date']};{$newInfo['user']}",
			"name_suffix" => "",
			"state" => "{$newInfo['state']};{$newInfo['date']};{$newInfo['user']}",
			"ok-belege" => '',
			"ok-hv" => '',
			"ok-kv" => '',
			"payed" => '',
			"rejected" => '',
			"zahlung-iban" => '',
			"zahlung-name" => '',
			"zahlung-vwzk" => '',
			"last_change" => "{$newInfo['date']}",
			"last_change_by" => '',
			"version" => 0,
			"etag" => '',
			'belege' => [],
		];
	}
	
	//---------------------------------------------------------
	/* ---------- MEMBER GETTER ----------- */
	
	/**
	 * @return the $auslagen_id
	 */
	public function getID(){
		if (isset($this->auslagen_id))
			return $this->auslagen_id;
		else
			return null;
	}
	
	/**
	 * @return the $projekt_id
	 */
	public function getProjektID(){
		if (isset($this->projekt_id))
			return $this->projekt_id;
		else
			return null;
	}
	
	/**
	 * @return the $error
	 */
	public function getError()
	{
		return $this->error;
	}
	
	//---------------------------------------------------------
	/* ---------- PERMISSION ----------- */
	
	/**
	 * check permission of permission entry
	 * e.g entries from stateChanges, $group, ...
	 * 
	 * @param boolean|array $map
	 * @return bool
	 */
	private function checkPermissionByMap($map){
		if ($map === true){
			return true;
		}
		if (is_array($map)
			&&isset($map['groups'])){
			$g = (is_string($map['groups']))? $map['groups'] : implode(",", $map["groups"]);
			if (AuthHandler::getInstance()->hasGroup($g)){
					return true;
			}
		}
		if (is_array($map)
			&&isset($map['dynamic']) && $this->auslagen_id){
			//build dynamic data
			$owner = explode(';', $this->auslagen_data['created']);
			$owner = $owner[1];
			$dynamic = [
				'owner' => $owner,
				'plain_orga' => $this->auslagen_data['projekte.org'],
			];
			//check dynamic permissions
			$ah = AuthHandler::getInstance();
			$a = $ah->getAttributes();
			foreach ($map['dynamic'] as $type){
				if (!isset($dynamic[$type])) continue;
				switch($type){
					case 'owner':{
						if($ah->getUsername() == $dynamic[$type]){
							return true;
						}
					} break;
					case 'plain_orga': {
						if (!isset($a['gremien'])) continue;
						if (in_array($dynamic[$type], $a['gremien'])){
							return true;
						}
					}break;
				}
			}
		}
		return false;
	}
	
	/**
	 * check if state change into new state is possible
	 * check state
	 * check substate
	 * check permission
	 * 
	 * @param string $newState
	 * @return boolean
	 */
	private function state_change_possible($newState, $is_sub = false){
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
			if (!$is_sub) foreach ($required_sub as $required){
				if (strpos($this->stateInfo['substate'], $required)===false){
					return false;
				}
			}
			//state change permission
			if ($this->checkPermissionByMap(self::$stateChanges[$c][$newState])){
				return true;
			}
		// sub state changes ----------------------------------
		} else if (isset(self::$subStates[$newState])){
			//mainstatechange possible ?
			//same state || mainstate change possible
			if (self::$subStates[$newState][0] == $c || $this->state_change_possible(self::$subStates[$newState][0], $newState)){
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
	 * @return bool
	 */
	private function state_change($newState, $etag){
		if ($this->state_change_possible($newState)) {
			$newInfo = self::state2stateInfo($newState);
			$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
			$newInfo['user'] = AuthHandler::getInstance()->getUsername();
			//
			$set = [
				'version' => $this->auslagen_data['version'] + 1,
				'etag'	  => generateRandomString(16),
			];
			$where = [
				'id' => $this->auslagen_data['id'],
				'etag' => $etag
			];
			if($this->stateInfo['state'] != $newInfo['state']){
				$set['state'] 	= "{$newInfo['state']};{$newInfo['date']};{$newInfo['user']}";
			}
			//reset values
			if (isset(self::$states[$newState])){
				switch ($newState){
					case 'wip': {
						$set['ok-hv'] = '';
						$set['ok-kv'] = '';
						break;
					}
					case 'draft':{
						$set['rejected'] = '';
						break;
					}
					case 'instructed':{
						$set['payed'] = '';
						break;
					}
					case 'revocation': { //sonderfall nicht alleine setzbar, nur über substates
						return false;
					}
				}
			}
			if (isset(self::$subStates[$newState])){
				switch($newState){
					case 'ok-belege':
					case 'ok-hv':
					case 'ok-kv':
					case 'payed':
					case 'rejected':{
						$set[$newState] = "{$newInfo['date']};{$newInfo['user']}";
						break;
					}
				}
			}
			$this->db->dbUpdate('auslagen', $where, $set);
			return true;
		}
		return false;
	}
	
	//---------------------------------------------------------
	/* ------- HELPER FUNCTIONS -------- */
	
	/**
	 * create stateInfo from state
	 * state may be substate, state or db_state info
	 * @param array $state
	 */
	public static function state2stateInfo($state){
		$s = $state;
		$split = NULL;
		if (strpos($state, ';')){
			$split = explode(';', $s);
			$s = $split[0];
		}
		$out = [
			'state'		=> '',
			'substate'	=> '',
		];
		//state / substate
		if (isset(self::$subStates[$s])){
			$out['substate'] = $s;
			$out['state'] = self::$subStates[$s][0];
		} elseif (isset(self::$states[$s])) {
			$out['state'] = $s;
		} else {
			return $out;
		}
		//optional info - date
		if($split){
			$out['date'] = $split[1];
		}
		//optional info - user
		if($split){
			$out['user'] = $split[2];
		}
		return $out;
	}
	
	/**
	 * calculate stateInfo von aktueller auslage
	 */
	private function stateFromAuslagenData(){
		$this->stateInfo = self::state2stateInfo($this->auslagen_data['state']);
		if (!$this->auslagen_id) return;
		//sub states - revocation
		if ($this->stateInfo['state']=='revocation'){
			$this->stateInfo['substate'] .= 
				(($this->stateInfo['substate'])? ',': '')
					.($this->auslagen_data['rejected']?
						'rejected':'revoked');
		}
		//sub states - wip - ok_*
		if ($this->stateInfo['state']=='wip'){
			$this->stateInfo['substate'] .= 
				($this->auslagen_data['ok-belege']?
					(($this->stateInfo['substate'])? ',': '')
					.'ok-belege'
					:'');
			$this->stateInfo['substate'] .=
				($this->auslagen_data['ok-hv']?
					(($this->stateInfo['substate'])? ',': '')
					.'ok-hv'
					:'');
			$this->stateInfo['substate'] .=
				($this->auslagen_data['ok-kv']?
					(($this->stateInfo['substate'])? ',': '')
					.'ok-kv'
					:'');
		}
		//sub state - instructed
		if ($this->stateInfo['state']=='instructed'){
			$this->stateInfo['substate'] .=
				($this->auslagen_data['payed']?
					(($this->stateInfo['substate'])? ',': '')
					.'payed'
					:'');
		}
	}
	
	/**
	 * masks iban to format
	 * xxxx ... ... xx
	 * @param string $in iban string
	 * @return string
	 */
	public static function trimIban($in){
		$in = trim($in);
		if ($in === '') return '';
		if (mb_strlen($in)>=5) {
			return mb_substr($in, 0, 4).' ... ... '.mb_substr($in, -2);
		} else {
			return $in;
		}
	}
	
	//---------------------------------------------------------
	/* ------------- CONSTRUCTOR ------------- */
	
	/**
	 * class constructor
	 * check projekt id and auslagen id
	 * @param array $routeInfo
	 *  required keys: 
	 *  	action, pid
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
		if (!$this->getDbProject()) return; //set error
		// check auslage exists --------------------
		if (isset($this->routeInfo['aid'])){
			//check auslagen id exists --------------------
			$this->auslagen_id = $routeInfo['aid'];
			if (!$this->getDbAuslagen()) return;
			if (!$this->getDbBelegePostenFiles()) return;
			
			//current state
			$this->stateFromAuslagenData();
		} else {
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
		if ($this->stateInfo['editable'] && $this->routeInfo['action'] != 'create') {
			if (!$this->checkPermissionByMap(self::$groups['strict_editable'])){
				$this->stateInfo['editable'] = false;
			}
		}
		$this->stateInfo['project-editable'] = (
			$this->projekt_data['state'] == 'ok-by-stura' ||
			$this->projekt_data['state'] == 'done-hv' ||
			$this->projekt_data['state'] == 'done-other');
		
		//check if there auslage should be edited
		if (!$this->stateInfo['project-editable']){
			if (   $routeInfo['action']	=='create'
				|| $routeInfo['action']	=='edit'
				|| $routeInfo['action']	=='post' ){
				$this->error = 'Für das aktuelle Projekt sind (momentan) keine Auslagenerstattungen möglich.';
				return;
			}
		}
		
		switch ($routeInfo['action']){
			case 'create': {
				//page title
				$this->title = ' - Erstellen';
				break;
			}; 
			case 'edit': {
				//page title
				$this->title = ' - Bearbeiten';
				break;
			};
			case 'view': {
				$this->stateInfo['editable_link'] = $this->stateInfo['editable'];
				$this->stateInfo['editable'] = false;
				break;
			};
			case 'post': {
				break;
			};
			default: {
				$this->error = 'Ungültiger request in AuslagenHandler.php';
				return;
			}
		}
		// TODO -------------------------
		//was wird davon noch gebraucht?
		//TODO render auslagen
		if ($this->routeInfo['action'] != 'post'){
			$this->stateHandler = new StateHandler("projekte", self::$states, self::$stateChanges, [], [], $this->stateInfo['state']);
			$this->permissionHandler = new PermissionHandler(self::$validFieldKeys, $this->stateHandler, self::$writePermissionAll, self::$writePermissionFields, self::$visibleFields, $this->stateInfo['editable']);
			$this->templater = new FormTemplater($this->permissionHandler);
		}
		//TODO$this->selectable_users = FormTemplater::generateUserSelectable(false);
		//TODO$this->selectable_posten = FormTemplater::generateProjektpostenSelectable(8);
	}
	
	//---------------------------------------------------------
	/* ---------- DB FUNCTIONS ---------- */
	
	/**
	 * get project information from db
	 * @param boolean $renderError
	 */
	private function getDbProject(){
		$res = $this->db->dbFetchAll("projekte", [], ["projekte.id" => $this->projekt_id], [
		], ["version" => true]);
		if (!empty($res)){
			$this->projekt_data = $res[0];
			$this->projekt_data['auslagen'] = [];
		} else {
			$this->error = 'Das Projekt mit der ID: '.$this->projekt_id.' existiert nicht. :(<br>';
			return false;
		}
		// get auslagen liste
		$res = $this->db->dbFetchAll(['auslagen'], ['auslagen.id', 'auslagen.name_suffix', 'auslagen.state', 'auslagen.created'], ["auslagen.projekt_id" => $this->projekt_id], [], ['auslagen.id' => true]);
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
	 * @param boolean $renderError
	 */
	private function getDbAuslagen(){
		$res = $this->db->dbFetchAll("auslagen", [ 
 			'auslagen.*', 
			'projekte' => 'projekte.*',
		], ["auslagen.id" => $this->auslagen_id, "auslagen.projekt_id" => $this->projekt_id],
			[
				["type" => "inner", "table" => "projekte", "on" => [["projekte.id", "auslagen.projekt_id"]]],
			]);
		if (!empty($res)){
			$out = [];
			$this->auslagen_data = $res[0];
			return true;
		} else {
			$this->error = 'Eine Auslagenerstattung mit der ID: '.$this->auslagen_id.' existiert nicht. :(<br>';
			return false;
		}
	}
	
	private function getDbBelegePostenFiles(){
		$res = $this->db->dbFetchAll("belege", [
			'belege' => 'belege.*',
			'beleg_posten' => 'beleg_posten.*',
			'fileinfo' => 'fileinfo.*',
			'projektposten' => 'projektposten.*',
		], ["belege.auslagen_id" => $this->auslagen_id],
			[
				["type" => "left", "table" => "beleg_posten", "on" => [["belege.id", "beleg_posten.beleg_id"]]],
				["type" => "left", "table" => "fileinfo", "on" => [["fileinfo.id", "belege.file_id"]]],
				["type" => "left", "table" => "projektposten", "on" => [["beleg_posten.projekt_posten_id", "projektposten.id"]]],
			], ["belege.id" => true, "belege.short" => true, "beleg_posten.id" => true, "beleg_posten.short" => true, "projektposten.name" => true]);
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
						'file' => NULL,
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
							'data' => NULL,
						];$this->stateInfo['editable'] = false;
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
	 * get auslagen information from db
	 * @param boolean $renderError
	 */
	private function getDbProjektPosten(){
		$res = $this->db->dbFetchAll("projektposten", [], ["projekt_id" => $this->projekt_id]);
		$aus = [];
		if (!empty($res)){
			foreach ($res as $row){
				$aus[] = $row;
			}
		}
		$this->projekt_data['posten'] = $aus;
		return true;
	}
	
	//---------------------------------------------------------
	/* ---------- HANDLER FUNCTIONS ------------ */
	
	public function handlePost(){
		if ($this->error) $this->_renderPostError();
		if (!isset($this->routeInfo['mfunction'])){
			$this->error = 'mfunction not set.';
		}
		if (!$this->error) switch ($this->routeInfo['mfunction']) {
			case 'updatecreate': {
				if ($this->stateInfo['editable']){
					$this->post_createupdate();
				} else {
					$this->error = 'Die Auslagenerstattng kann nicht verändert werden.';
				}
			} break;
			case 'filedelete': {
				if ($this->stateInfo['editable']){
					$this->post_filedelete();
				} else {
					$this->error = 'Die Auslagenerstattng kann nicht verändert werden. Datei nicht gelöcht.';
				}
			} break;
			case 'state': {
				if ($this->stateInfo['project-editable'] && $this->auslagen_id){
					$this->post_statechange();
				} else {
					$this->error = 'Die Auslagenerstattng kann nicht verändert werden. Der status wurde nicht geändert.';
				}
			} break;
			default: {
				$this->error = 'Unknown Action.';
			} break;
		}
		if ($this->error) $this->_renderPostError();
		else {
			$this->_renderPostResult();
		}
	}
	
	//---------------------------------------------------------
	/* ---------- JSON FUNCTIONS ------------ */
	
	private function post_statechange() {
		$newState = $this->routeInfo['validated']['state'];
		if (!$this->state_change_possible($newState)||
			!$this->state_change($newState, $this->routeInfo['validated']['etag'])){
			$this->error = 'Diese Statusänderung ist momentan nicht möglich.';
		} else {
			$this->json_result = [
				'success' => true,
				'msg' => "Status geändert",
				'reload' => 2000,
				'type' => 'modal',
				'subtype' => 'server-success',
				'headline' => 'Erfolgreich',
				'redirect' => URIBASE.'index.php/projekt/'.$this->projekt_id.'/auslagen/'.$this->auslagen_data['id'],
			];
		}
		
	}
	
	//handle file delete request
	private function post_filedelete(){
		if ($this->auslagen_data['etag'] != $this->routeInfo['validated']['etag']) {
				$this->error = '<p>Die Prüfsumme der Auslagenerstattung stimmt nicht mit der gesendeten überein.</p>'.
					'<p>Die Auslagenerstattung wurde in der Zwischenzeit geändert, daher muss die Seite neu geladen werden...</p>'.
					'<p>Die übertragene Version liegt '.($this->auslagen_data['version'] - $this->routeInfo['validated']['version']).' Version(en) zurück.</p>';
			return;
		}
		//fill data
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = AuthHandler::getInstance()->getUsername();
		
		//check fileid exists
		$found_file_id = false;
		foreach($this->auslagen_data['belege'] as $b){
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
		$this->db->dbUpdate('belege', ['id' => $found_file_id['id']], ['file_id' => NULL]);
		$this->db->dbUpdate('auslagen', ['id' => $this->auslagen_data['id']], 
			[
				"last_change" => "{$newInfo['date']}",
				"last_change_by" => "{$newInfo['user']}",
				"version" => intval($this->auslagen_data['version']) + 1,
				"etag" => generateRandomString(16),
			]
		);
		$this->json_result = [
			'success' => true,
			'msg' => "Die Datei '{$found_file_id['file']['filename']}.{$found_file_id['file']['fileextension']}' wurde erfolgreich entfernt.",
			'reload' => 2000,
			'type' => 'modal',
			'subtype' => 'server-success',
			'headline' => 'Eingaben gespeichert',
			'redirect' => URIBASE.'index.php/projekt/'.$this->projekt_id.'/auslagen/'.$this->auslagen_data['id'].'/edit',
		];
	}
	
	//handle create or update auslage
	private function post_createupdate(){
		//auslage =============================================
		//check etag if no new auslage
		if ($this->routeInfo['validated']['auslagen-id'] != 'NEW' && 
			$this->auslagen_data['etag'] != $this->routeInfo['validated']['etag']) {
			$this->error = '<p>Die Prüfsumme der Auslagenerstattung stimmt nicht mit der gesendeten überein.</p>'.
							'<p>Die Auslagenerstattung wurde in der Zwischenzeit geändert, daher muss die Seite neu geladen werden...</p>'.
							'<p>Die übertragene Version liegt '.($this->auslagen_data['version'] - $this->routeInfo['validated']['version']).' Version(en) zurück.</p>';
			return;
		} elseif ($this->routeInfo['validated']['auslagen-id'] == 'NEW'){
			$this->auslagen_data = $this->get_empty_auslage();
		}
		//fill data
		$newInfo = $this->stateInfo;
		$newInfo['date'] = date_create()->format('Y-m-d H:i:s');
		$newInfo['user'] = AuthHandler::getInstance()->getUsername();
		
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
				if (strpos($kb, 'new_')!==false){
					$changed_belege_flag = true;
					$new_belege[$kb] = $b;
				} elseif (!isset($this->auslagen_data['belege'][$kb])) {
					$changed_belege_flag = true;
					//ignore this invalid elements
				} else {
					$ob = $this->auslagen_data['belege'][$kb];
					$changed_belege[$kb] = $ob;
					$fileIdx = 'beleg_'.$kb;
					if (!$ob['file_id'] && isset($_FILES[$fileIdx]['error']) && $_FILES[$fileIdx]['error'] === 0){
						$changed_belege_flag = true;
					}
				}
				if (isset($b['posten'])) foreach ($b['posten'] as $kp => $p){
					if (strpos($kp, 'new_')!==false){
						$changed_posten_flag = true;
						$new_posten[$kp] = ['posten' => $p, 'beleg_id' => $kb];
					} elseif (!isset($this->auslagen_data['belege'][$kb]['posten'][$kp])) {
						$changed_posten_flag = true;
						//ignore invalid elements
					} else {
						$op = $this->auslagen_data['belege'][$kb]['posten'][$kp];
						$changed_posten[$kb] = $op;
						if ($op['einnahmen'] != $p['in'] || $op['ausgaben'] != $p['out'] ){
							$changed_posten_flag = true;
						}
					}
				}
			}
		}
		//gelöschte elemente
		foreach($this->auslagen_data['belege'] as $kb => $b){
			if (!isset($this->routeInfo['validated']['belege'][$kb])){
				$changed_belege_flag = true;
				$removed_belege[$kb] = $b;
			} else {
				foreach($b['posten'] as $kp => $p){
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
			"ok-belege" => ($changed_belege_flag || $changed_posten_flag)? '' : $this->auslagen_data['ok-belege'],
			"ok-hv" => ($changed_belege_flag || $changed_posten_flag)? '' : $this->auslagen_data['ok-hv'],
			"ok-kv" => ($changed_belege_flag || $changed_posten_flag)? '' : $this->auslagen_data['ok-kv'],
			"payed" => $this->auslagen_data['payed'],
			"rejected" => $this->auslagen_data['rejected'],
			"zahlung-iban" => strpos($this->routeInfo['validated']['zahlung-iban'], '... ...')? $this->auslagen_data['zahlung-iban'] : $this->routeInfo['validated']['zahlung-iban'],
			"zahlung-name" => $this->routeInfo['validated']['zahlung-name'],
			"zahlung-vwzk" => $this->routeInfo['validated']['zahlung-vwzk'],
			"last_change" => "{$newInfo['date']}",
			"last_change_by" => "{$newInfo['user']}",
			"version" => intval($this->auslagen_data['version']) + 1,
			"etag" => generateRandomString(16),
		];
		//insert/update in db
		if ($this->auslagen_data['id']){
			$where = [
				'id' => $this->auslagen_data['id'],
				'etag' => $this->auslagen_data['etag'],
			];
			$this->db->dbUpdate('auslagen', $where, $db_auslage);
			$db_auslage['id'] = $this->auslagen_data['id'];
		} else {
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
			$fileIdx = 'beleg_'.$kb;
			if (!$b['file_id'] && isset($_FILES[$fileIdx]['error'][0]) && $_FILES[$fileIdx]['error'][0] === 0){
				$beleg_file_map[$kb] = [
					'file' => $fileIdx,
					'link' => $b['id'],
				];
			}
			//update values
			$db_beleg = [
         		'datum' => $this->routeInfo['validated']['belege'][$kb]['datum'],
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
         		'short' => $this->auslagen_data['version'].''.$beleg_shortcounter,
         		'created_on' => date_create()->format('Y-m-d H:i:s'),
         		'datum' => $b['datum'],
         		'beschreibung' => $b['beschreibung'],
				'auslagen_id' => $this->auslagen_data['id'],
         	];
			$idd = $this->db->dbInsert('belege', $db_beleg);
			$db_beleg['id'] = $idd;
			$map_new_beleg_beleg_idx[$kb] = $idd;
			$fileIdx = 'beleg_'.$kb;
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
			if (strpos($map['beleg_id'], 'new_'===false)
				&& isset($removed_belege[$map['beleg_id']])){
				continue;
			}
			$posten_shortcounter++;
			$db_posten = [
         		'short' => intval($this->auslagen_data['version'].''.$posten_shortcounter),
         		'projekt_posten_id' => $map['posten']['projekt-posten'],
         		'ausgaben' => $map['posten']['out'],
         		'einnahmen' => $map['posten']['in'],
         		'beleg_id' => strpos($map['beleg_id'], 'new_'!==false)? $map_new_beleg_beleg_idx[$map['beleg_id']] : $map['beleg_id'],
         	];
			$idd = $this->db->dbInsert('beleg_posten', $db_posten);
		}
		//new files ===============================================
		foreach ($beleg_file_map as $fileInfo){
			$file_id = 0;
			//handle file upload
			$res = $fh->upload(intval($fileInfo['link']), $fileInfo['file']);
			if (count($res['error']) > 0) {
				$emsg = '';
				foreach ($res['error'] as $e){
					$emsg .="<p>$e</p>";
				}
				$this->error = $emsg;
			} else {
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
			'redirect' => URIBASE.'index.php/projekt/'.$this->projekt_id.'/auslagen/'.$this->auslagen_data['id'],
		];
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
			'reload' => (strpos($this->error, 'not allowed') && strpos($this->error, 'files'))? 3000 : false
		];
		$this->_renderPostResult();
	}
	
	private function _renderPostResult(){
		JsonController::print_json($this->json_result);
	}
	
	/**
	 * render page
	 * @see Renderer::render()
	 */
	public function render(){
		if ($this->error){
			ErrorHandler::_renderError($this->error, 404);
			return -1;
		} else {
			return $this->renderAuslagenerstattung("Auslagenerstattung");
		}
	}
	
	/**
	 * render auslagenerstattung html page
	 * @param string $titel
	 */
	private function renderAuslagenerstattung($titel){
		 
		$editable = $this->stateInfo['editable'];
		?>
        	<h3><?= $titel . (($this->title)? $this->title: '') ?></h3>
             <?php //-------------------------------------------------------------------- ?>
            <label for="projekt-well">Projekt Information</label>
            <div id='projekt-well' class="well">
            	<?php $show_genemigung_state = ($this->routeInfo['action'] != 'create' || isset($this->auslagen_data['state']) && $this->auslagen_data['state'] != 'draft' ); ?>
            	<?= $this->templater->generateListGroup(
	            	[
	            		[	'html' => '<i class="fa fa-fw fa-chain"></i>&nbsp;'.$this->projekt_data['name'], 
	            	 	'attr' => ['href' => URIBASE.'projekt/'.$this->projekt_id, 'style' => 'color: #99000b;' ]	],
	            	],
	            	'Zugehöriges Projekt', false, $show_genemigung_state, '', 'a'); ?>
	            <?php 
	    			if(count($this->projekt_data['auslagen'])==0){
	    				echo '<label for="auslagen-vorhanden">Im Projekt vorhandene Auslagenerstattungen</label>';
	    				echo '<div  class="well" style="margin-bottom: 0px; background-color: white;"><span>Keine</span></div>';
		            } else {
		            	$tmpList = [];
		            	$show_creator = $this->checkPermissionByMap(self::$groups['stateless']['view_creator']);
		            	foreach ($this->projekt_data['auslagen'] as $auslage){
		            		$tmp_state = self::state2stateInfo($auslage['state']);
		            		$created = self::state2stateInfo('draft;'.$auslage['created']);
		            		$name = $auslage['id'].' - '.($auslage['name_suffix']?$auslage['name_suffix']:'(Ohne Namen)') . '<strong><small style="margin-left: 10px;">'.$created['date'].'</small>' . (($show_creator)?'<small style="margin-left: 10px;">['.$created['user'].']</small>':'').'</strong>';
		            		
		            		$tmpList[] = [
		            			'html' => $name.'<span class="label label-info pull-right"><span>Status: </span><span>'.self::$states[$tmp_state['state']][0].'</span></span>',
		            			'attr' => ['href' => URIBASE.'projekt/'.$this->projekt_id.'/auslagen/'.$auslage['id'],
		            						'style' => 'color: #3099c2;' ],
		            			
		            		];
		            	}
		            	echo $this->templater->generateListGroup($tmpList,
		            		'Im Projekt vorhandene Auslagenerstattungen', false, $show_genemigung_state, '', 'a', 'col-xs-12 col-md-8');
	    			} ?>
	    	</div>
	    	<?php //-------------------------------------------------------------------- ?>
	    	<?php if ($show_genemigung_state) { ?>
	        	<label for="genehmigung">Auslage Status</label>
	    		<div id='projekt-well' class="well">
	            	<label for="genehmigung">Status</label>
	            	<div class="col-xs-12 form-group">
	            		<div style="padding-bottom: 10px;"><?php 
	            			if ($this->stateInfo['state'] == 'instructed' && strpos($this->stateInfo['substate'], 'payed')!==false){
	            				echo 'Bezahlt';
	            			} else {
	            				echo self::$states[$this->stateInfo['state']][0];
	            			}
	            		?></div>
	            	</div>
	            	<div class="clearfix"></div>
	            	<label for="genehmigung">Original-Belege</label>
	            	<?php 
	            		if($this->auslagen_data['ok-belege']){
	            			echo '<div class="" title="'.$this->auslagen_data['ok-belege'].'" style="padding-left: 10px; padding-bottom: 10px;"><i class="fa fa-fw fa-2x fa-check-square-o"></i> <strong style="line-height: 1.6em; height: 2em; vertical-align: top;"> eingereicht</strong></div>';
	            		} else {
	            			echo '<div class="" style="padding-left: 10px; padding-bottom: 10px;"><i class="fa fa-fw fa-2x fa-square-o"></i> <strong style="line-height: 1.6em; height: 2em; vertical-align: top;"> nicht eingereicht</strong></div>';
	            		}
	            	?>
	            	<div class="clearfix"></div>
	            	<label for="genehmigung">Genehmigung</label>
	            	<br>
		                <?= $this->templater->getTextForm("hv-ok", $this->auslagen_data['ok-hv']? $this->auslagen_data['ok-hv']:'ausstehend', 6, "HV", "HV", []) ?>
		                <?= $this->templater->getTextForm("kv-ok", $this->auslagen_data['ok-kv']? $this->auslagen_data['ok-kv']:'ausstehend', 6, "KV", "KV", []) ?>
		           <div class="clearfix"></div>
		        </div>
	        <?php } ?>
	        	<input type="hidden" name="nononce" value="<?= $GLOBALS["nonce"] ?>">
	        	<input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
	        <form class="ajax" method="POST" enctype="multipart/form-data" action="<?= URIBASE ?>index.php/rest/forms/auslagen/updatecreate">
	        	<input type="hidden" name="projekt-id" value="<?= $this->projekt_id; ?>">
	        	<input type="hidden" name="auslagen-id" value="<?= ($this->routeInfo['action'] == 'create')? 'NEW':$this->auslagen_id; ?>">
	        	<input type="hidden" name="version" value="<?= ($this->routeInfo['action'] == 'create')? '1':$this->auslagen_data['version']; ?>">
	        	<input type="hidden" name="etag" value="<?= ($this->routeInfo['action'] == 'create')? '0':$this->auslagen_data['etag']; ?>">
	        	<?= $this->templater->getHiddenActionInput(''); ?>
	        <?php //-------------------------------------------------------------------- ?>
            <label for="projekt-well">Allgemein</label>
            <div id='projekt-well' class="well">
            	<label>Name der Auslagenerstattung</label>
            	<?= $this->templater->getTextForm("auslagen-name", (isset($this->auslagen_data['id']))?$this->auslagen_data['name_suffix']:'', 12, "optional", "", [], 'Auslagenname') ?>
	            
	            <?php if($this->routeInfo['action'] != 'create'){ ?>
	           		<div class="clearfix"></div>
	            	<label>Erstellt</label>
		            <div class="col-xs-12 form-group">
		            	<?php
		            	$cr = $this->auslagen_data['created'];
		            	$cr_split = explode(';', $cr);
		            	echo date_create($cr_split[0])->format('d.m.Y H:i:s'); ?>
		            </div>
		            <?php if($this->checkPermissionByMap(self::$groups['stateless']['view_creator'])){ ?>
		            	<div class="clearfix"></div>
		            	<div class="col-xs-12 form-group">
		            		<label>Von</label>
			            	<?= $cr_split[1] ?>
			            </div>
		        	<?php } ?>
		        	<div class="clearfix"></div>
	            	<label>Version</label>
		            <div class="col-xs-12">
		            	<?= $this->auslagen_data['version'] ?>
		            </div>
		        <?php } ?>
		        
	            <div class="clearfix"></div>
            </div>
            <?php //-------------------------------------------------------------------- ?>
            <label for="zahlung">Zahlungsinformationen</label>
            <div id="zahlung" class="well">
				<?= $this->templater->getTextForm("zahlung-name", $this->auslagen_data['zahlung-name'], [12,12,6], "Name Zahlungsempfänger", "anderen Zahlungsempfänger Name (neu)", [], []) ?>
				<?php // iban only show trimmed if not hv/kv important!
	            	$iban_text = $this->auslagen_data['zahlung-iban'];
	            	if (!AuthHandler::getInstance()->hasGroup('HV,KV')){
	            		$iban_text = self::trimIban($iban_text);
	            	} elseif ($iban_text != '') {
	            		$iban_text = chunk_split($iban_text, 4, ' ');
	            	}
				echo $this->templater->getTextForm("zahlung-iban", $iban_text, [12,12,6], "DE ...", "anderen Zahlungsempfänger IBAN (neu)") ?>
				<div class='clearfix'></div>
                <?= $this->templater->getTextForm("zahlung-vwzk", $this->auslagen_data['zahlung-vwzk'], 12, "z.B. Rechnungsnr. o.Ä.", "Verwendungszweck (verpflichtent bei Firmen)", [], []) ?>
                <div class="clearfix"></div>
            </div>
            <?php //-------------------------------------------------------------------- ?>
	        <?php
          
	            $belege = (isset($this->auslagen_data['belege']))? $this->auslagen_data['belege']: [];

	            $this->render_beleg_container($belege, $editable,  'Belege');

           		$beleg_nr = 0;
            	$tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
            
            if ($editable){ ?>
			<div class="row row-striped add-button-row" style="margin: 10px 0;">
	    		<div class="send" style="padding:5px;">
		    		<div class="text-center"><button type="submit" class="btn btn-success" style="min-width:100px; font-weight: bold;">Änderungen Speichern</button></div>
	    		</div>
    		</div>
    		<?php } ?>
		</form>
        <?php
        if ($this->routeInfo['action'] != 'edit'){
        	$this->render_state_links();
        }
        return;
    }
	
	//---------------------------------------------------------
	/* ---------- RENDER HELPER ------------ */
	
	/**
	 *
	 * @param array $beleg
	 * 	[
	 *   		'id' => NULL,
	 *   		'short' => '',
	 *   		'created_on' => date_create()->format('Y-m-d H:i:s'),
	 *   		'datum' => '',
	 *   		'beschreibung' => '',
	 *   		'file_id' => NULL,
	 *   		'file' => NULL,
	 *   		'posten' => []
	 *   	]
	 * @param boolean $hidden
	 */
	public function render_beleg_container($belege, $editable = true, $label = ''){
		if ($label){ echo '<label>'.$label.'</label>';} ?>
		<div class="beleg-table well<?= ($editable)? ' editable':'' ?>">
			<div class="hidden datalists">
				<datalist class="datalist-projekt">
					<option value="0" data-alias="Bitte Wählen">
				<?php foreach ($this->projekt_data['posten'] as $p){ ?>
					<option value="<?= $p['id']+1 //TODO remove +1 wenn db ok und projekthandler gefixed ?>" data-alias="<?= $p['name'] ?>">
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
	    	echo $this->render_beleg_row([
	    		'id' => '',
	    		'short' => '',
	    		'created_on' => '',
	    		'datum' => '',
	    		'beschreibung' => '',
	    		'file_id' => NULL,
	    		'file' => NULL,
	    		'posten' => []
	    	], $editable, true);
    	} if ($editable){ ?>
    			<div class="row row-striped add-button-row" style="margin: 10px 0;">
	    			<div class="add-belege" style="padding:5px;">
		    			<div class="text-center"><button type="button" class="btn btn-success" style="min-width:100px; font-weight: bold;">+ Beleg ergänzen</button></div>
	    			</div>
    			</div>
    	<?php } ?>
    	</div>
    	<?php
    }
    
    /**
     * render beleg line
     * @param array $beleg beleg data
     * @param bool $editable
     * @param bool $hidden
     * @return string
     */
    public function render_beleg_row($beleg, $editable = true,  $hidden = false){
    	ob_start();
    	$date = ($beleg['datum'])? date_create($beleg['datum'])->format('d.m.Y') : '';
    	$date_value = ($beleg['datum'])? date_create($beleg['datum'])->format('Y-m-d') : '';
    	$date_form = ($editable)? $this->templater->getDatePickerForm(($hidden)? '' : "belege[{$beleg['id']}][datum]", $date_value, 0, "", "", []): '<strong>am </strong>'.$date;
    
    	$file_form = '';
    	if (!$hidden) {
    		if ($beleg['file_id']) {
    			$file_form = '<div class="beleg-file btn-default" style=" border: 1px solid #ddd; border-radius: 5px; padding: 5px 10px; position: relative;" data-id="'.$beleg['file_id'].'">'.
    				'<a href="'.URIBASE.'files/get/'.$beleg['file']['hashname'].'">'.$beleg['file']['filename'].'.'.$beleg['file']['fileextension'].'</a>'.
    				'<button type="button" title="Löschen" class="file-delete btn btn-default pull-right">X</button>'.
    				'<div><small><span style="min-width: 50px; display: inline-block; font-weight: bold;">Size: </span>'.
    				'<span>'.FileHandler::formatFilesize($beleg['file']['size']).'</span></small>'.
    				'<small><span style="min-width: 50px; display: inline-block; margin-left: 10px; font-weight: bold;">Mime: </span>'.
    				'<span>'.$beleg['file']['mime'].'</span></small>'.
    				'</div>'.
    				'</div>';
    		} else {
    			if ($editable){
    				$file_form = $this->templater->getFileForm("files[beleg_{$beleg['id']}][]", 0, 0, "Datei...", "", []);
    			} else {
    				$file_form = '<span>Keine Datei verknüpft.</span>';
    			}
    		}
    	} else {
    		$file_form = $this->templater->getFileForm("", 0, 0, "Datei...", "", []);
    	}
    	 
    	$desc_form = '';
    	if ($editable) {
    		$desc_form = $this->templater->getTextareaForm(($hidden)? '' : "belege[{$beleg['id']}][beschreibung]", ($beleg['beschreibung'])?$beleg['beschreibung']:"", 0, "optional", "", [], 1);
    	} else {
    		$desc_form = '<span>'.($beleg['beschreibung'])?$beleg['beschreibung']:"keine".'</span>';
    	}
    	 
    	 
    	?>
    		<div class="row row-striped <?= ($hidden)? 'hidden' : 'bt-dark' ?>" style="padding: 5px;">
    			<div class="form-group<?= ($hidden)? ' beleg-template' : ' beleg-container' ?>" data-id="<?= $beleg['id']; ?>">
    				<div class="col-sm-1 beleg-idx-box">
    					<div class="form-group">
    						<div class="col-sm-6 beleg-idx"></div>
    						<div class="col-sm-1 beleg-nr"><?= $beleg['short']; ?><?=
    							($editable)? '<a href="#" class="delete-row"> <i class="fa fa-fw fa-trash"></i></a>' : '' ?></div>
    					</div>
    				</div>
    				<div class="col-sm-11 beleg-inner">
    					<div class="form-group">
    						<div class="col-sm-4"><strong>Datum des Belegs</strong></div>
    						<div class="col-sm-8"><strong>Scan des Belegs</strong></div>
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
	 * @param array $posten
	 * @param bool $editable
	 * @param bool $hidden
	 * @param bool $beleg_id
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
		$out .= '<div class="form-group posten-empty '.((count($posten) == 0)?'':' hidden').'">Keine Angaben</div>';
		 
		// if nonempty add lines
		foreach ($posten as $pline){
			$out .= '<div class="form-group posten-entry" data-id="'.$pline['id'].'" data-projekt-posten-id="'.$pline['projekt_posten_id'].'">';
			//position counter + trash bin
			$out .= '<div class="col-sm-1 posten-counter">
						'.(($editable)?'<i class="fa fa-fw fa-trash"></i>':'').'
					</div>';
			//short name / position
			$out .= '<div class="col-sm-1 posten-short">P'.$pline['short'].'</div>';
			//posten_name
			if ($editable){
				$out .= '<div class="col-sm-4 editable projekt-posten-select" data-value="'.$pline['projekt_posten_id'].'">'
					.'<div class="input-group form-group">'
						.'<span class="value">'.$pline['projekt.posten_name'].'</span>'
							.'<input type="hidden" name="belege['.$beleg_id.'][posten]['.$pline['id'].'][projekt-posten]" value="'.$pline['projekt_posten_id'].'">'
								.'</div>'
									.'</div>';
			} else {
				$out .= '<div class="col-sm-4 posten-name">'.$pline['projekt.posten_name'].'</div>';
			}
	
			//einnahmen
			if ($editable){
				$out .= '<div class="col-sm-3 posten-in">'
					.'<div class="input-group form-group">'
						.'<input class="form-control" name="belege['.$beleg_id.'][posten]['.$pline['id'].'][in]" type="number" step="0.01" min="0" value="'.$pline['einnahmen'].'">'
							.'<div class="input-group-addon">€</div>'
								.'</div>'
									.'</div>';
			} else {
				$out .= '<div class="col-sm-3 posten-in">'.$pline['einnahmen'].'</div>';
			}
			$sum_in += $pline['einnahmen'];
	
			//ausgaben
			if ($editable){
				$out .= '<div class="col-sm-3 posten-out">'
					.'<div class="input-group form-group">'
						.'<input class="form-control" name="belege['.$beleg_id.'][posten]['.$pline['id'].'][out]" type="number" step="0.01" min="0" value="'.$pline['ausgaben'].'">'
							.'<div class="input-group-addon">€</div>'
								.'</div>'
									.'</div>';
			} else {
				$out .= '<div class="col-sm-3 posten-out">'.$pline['ausgaben'].'</div>';
			}
			$sum_out += $pline['ausgaben'];
	
			$out .= '<div style="clear:both;"></div></div>';
		}
		 
		//if $ediatable add __auto add line__
		if ($editable) {
			$out .= '<div class="form-group posten-entry-new">';
			//position counter + trash bin
			$out .= '<div class="col-sm-1 posten-counter">
						<i class="hidden fa fa-fw fa-trash"></i>
           				<i class="fa fa-fw fa-plus text-success"></i>
					</div>';
			//short name / position
			$out .= '<div class="col-sm-1 posten-short"></div>';
			//posten_name
			$out .= '<div class="col-sm-4 editable projekt-posten-select" data-value="0">'
				.'<div class="input-group form-group">'
				.'<span class="value"></span>'
					.'<input type="hidden" value="0">'
						.'</div>'
							.'</div>';
	
			//einnahmen
			$out .= '<div class="col-sm-3 posten-in">'
				.'<div class="input-group form-group">'
				.'<input class="form-control" type="number" step="0.01" min="0" value="0">'
					.'<div class="input-group-addon">€</div>'
						.'</div>'
							.'</div>';
	
			//ausgaben
			$out .= '<div class="col-sm-3 posten-out">'
				.'<div class="input-group form-group">'
				.'<input class="form-control" type="number" step="0.01" min="0" value="0">'
					.'<div class="input-group-addon">€</div>'
						.'</div>'
							.'</div>';
	
			$out .= '<div style="clear:both;"></div></div>';
		}
		 
		$out .= '</div>';
		$out .= '<div class="row row-striped" style="padding: 5px; border-top: 2px solid #ddd;">
    				<div class="form-group posten-sum-line">
						<div class="col-sm-1"></div>
						<div class="col-sm-5"></div>
						<div class="col-sm-3 posten-sum-in"><strong><span style="width: 10%;">Σ</span><span class="text-right" style="display: inline-block; padding-right: 10px; width: 80%;">'.number_format($sum_in,2).'</span><span style="width: 10%;">€</span></strong></div>
						<div class="col-sm-3 posten-sum-out"><strong><span style="width: 10%;">Σ</span><span class="text-right" style="display: inline-block; padding-right: 10px; width: 80%;">'.number_format($sum_out,2).'</span><span style="width: 10%;">€</span></strong></div>
					</div>
    			</div>';
		return $out;
	}
	
	public function render_state_links(){ 
		?> 
		<label for="projekt-well">Links</label>
        <div id='projekt-well' class="well state-links">
        	<input type="hidden" name="projekt-id" value="<?= $this->projekt_id; ?>">
        	<input type="hidden" name="auslagen-id" value="<?= ($this->routeInfo['action'] == 'create')? 'NEW':$this->auslagen_id; ?>">
        	<input type="hidden" name="etag" value="<?= ($this->routeInfo['action'] == 'create')? '0':$this->auslagen_data['etag']; ?>">
			<input type="hidden" name="action" value="<?= URIBASE ?>index.php/rest/forms/auslagen/state">
		<?php if (isset($this->stateInfo['editable_link']) && $this->stateInfo['editable_link']) { ?>
			<div class="col-xs-12 form-group">
				<a class="btn btn-success" href="<?= URIBASE."index.php/projekt/{$this->projekt_id}/auslagen/{$this->auslagen_id}/edit" ?>">Bearbeiten</a>
			</div>
			<div class="clearfix"></div>
		<?php } 
		if ($this->auslagen_id){
			foreach(self::$stateChanges[$this->stateInfo['state']] as $k => $dev_null){
				if ($k == 'revocation')	continue;
				if (!$this->state_change_possible($k)) continue;
				
			?>
				<div class="col-xs-12 form-group">
					<button type="button" class="btn btn-default state-changes-now" title="<?= self::$states[$k][0] ?>" data-newstate="<?= $k ?>"><?= self::$states[$k][1] ?></button>
				</div>
				<div class="clearfix"></div>
			<?php
			}
			foreach(self::$subStates as $k => $info){
				if ($this->state_change_possible($k)){
				?>
					<div class="col-xs-12 form-group">
						<button type="button" class="btn btn-default state-changes-now" title="<?= $info[3] ?>" data-newstate="<?= $k ?>"><?= $info[2] ?></button>
					</div>
					<div class="clearfix"></div>
				<?php
				}
			}
		}
		?>
		</div>
	<?php 
	}
	
	//---------------------------------------------------------
	/* ---------- TODOS ------------ */
    
	public static function initStaticVars() {
		
	}
	
	
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
    		'auslagen-name'	=> ['groups' => ['sgis']],
    		'zahlung-name' 	=> ['groups' => ['sgis']],
    		'zahlung-iban' 	=> ['groups' => ['sgis']],
    		'zahlung-vwzk' 	=> ['groups' => ['sgis']],
    		'belege' 		=> ['groups' => ['sgis']],
    		'files'			=> ['groups' => ['sgis']],],
    	'wip' => [
    		'auslagen-name'	=> ['groups' => ['ref-finanzen']],
    		'zahlung-name' 	=> ['groups' => ['ref-finanzen']],
    		'zahlung-iban' 	=> ['groups' => ['ref-finanzen']],
    		'zahlung-vwzk' 	=> ['groups' => ['ref-finanzen']],
    		'belege' 		=> ['groups' => ['ref-finanzen']],
    		'files'			=> ['groups' => ['ref-finanzen']],],
    	"ok" => [],
    	"instructed" => [],
    	"booked" => [],
    	"revocation" => [],
    ];
    static private $visibleFields = [];
    
    static private $printModes;
    private $templater;
    private $permissionHandler;
    private $stateHandler;
    
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
    

    
    public static function getStateString($statename){
        return self::$states[$statename][0];
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
