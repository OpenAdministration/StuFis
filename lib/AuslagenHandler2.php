<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:55
 */

class AuslagenHandler2 extends FormHandlerInterface{
    
	/**
     * error flag
     * set in constructor
     * dont render any text if error occures
     * @var boolean
     */
    private $error;
    
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
	private $id;
	
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
	private $args;
	
	/**
	 * auslagen state list
	 * @var array
	 */
    static private $states = [
		"draft" => ["Entwurf"],
		"wip" => ["Beantragt", "als beantragt speichern"],

		"ok-hv" => ["HV ok", "als Haushaltsverantwortlicher genehmigen",],
		"ok-kv" => ["KV ok", "als Kassenverantwortlicher genehmigen",],

		"ok-kv-hv" => ["Originalbelege fehlen", "als rechnerisch und sachlich richtig (Belege fehlen)",],
		"ok" => ["Genehmigt", "als rechnerisch und sachlich richtig (Belege vorhanden)",],

		"instructed" => ["Angewiesen",],
		"payed" => ["Bezahlt (lt. Kontoauszug)",],
		"booked" => ["Gezahlt und Gebucht"],

		"revoked" => ["Zurückgezogen", "zurückziehen",],
    ];
    
    
    
    static private $emptyData;
    
    static private $stateChanges;
    static private $printModes;
    static private $visibleFields;
    static private $writePermissionAll;
    static private $writePermissionFields;
    
    
    private $templater;
    private $permissionHandler;
    private $stateHandler;
    
    private $selectable_users;
    private $selectable_posten;
    
    public function __construct($args){
    	$this->error = false;
    	//errors ----------------------------
    	if (!isset($args['pid'])){
    		$this->error = true;
            ErrorHandler::_errorExit('missing parameter: pid - project id');
    	}
    	if (!isset($args['action'])){
    		$this->error = true;
            ErrorHandler::_errorExit('missing parameter: action');
    	}
    	if ($args['action'] == 'edit' && !isset($args['aid'])){
    		$this->error = true;
            ErrorHandler::_errorExit('missing parameter: aid - auslagen id');
    	}
    	// init variables ---------------------
        self::initStaticVars();
        $this->args = $args;
        $this->db = DBConnector::getInstance();
        $this->projekt_id = $args['pid'];
        
        // check projekt exists --------------------
        if (!$this->getDbProject()) return; //set error
        
        // create, view or edit -------------------------
        $editMode = false;
        $projekt_editable = (
        	$this->projekt_data['state'] == 'ok-by-stura' ||
        	$this->projekt_data['state'] == 'done-hv' || 
        	$this->projekt_data['state'] == 'done-other');
        if ($args['action']=='create'){
        	//check projekt editable
        	if (!$projekt_editable){
        		$msg = 'Eine Auslagenerstattung für dieses Projekt ist momentan nicht möglich.';
        		ErrorHandler::_renderError($msg, 403);
        		$this->error = true;
        		return false;
        	}
            $this->auslagen_data = self::$emptyData;
            $stateNow = "draft";
            $editMode = true;
            //page title
            $this->title = ' - Erstellen'; 
        } elseif($args['action'] == 'edit') {
        	//check projekt editable
        	if (!$projekt_editable){
        		$msg = 'Die Auslagenerstattung kann momentan nicht geändert werden.';
        		ErrorHandler::_renderError($msg, 403);
        		$this->error = true;
        		return false;
        	}
        	//TODO check auslage editable
            //load from db
           	$editMode = true;
           	$this->id = $args['aid'];
           	//check auslagen id exists --------------------
            if (!$this->getDbAuslagen()) return;
            if (!$this->getDbBelegePostenFiles()) return;
            $stateNow = $this->auslagen_data['state'];
            //page title
            $this->title = ' - Bearbeiten';
            echo '<pre>'; var_dump($this->auslagen_data); echo '</pre>';
            die();
        } elseif($args['action'] == 'view') {
        	//check auslagen id exists --------------------
        	if (!$this->getDbAuslagen()) return;
        	if (!$this->getDbBelegePostenFiles()) return;
        	$stateNow = $this->auslagen_data['state'];
        	
        } else {
        	$this->error = true;
        	$msg = 'Ungültiger request in AuslagenHandler.php';
        	ErrorHandler::_renderErrorPage(['action' => 404, 'msg' => $msg], true);
        	return;
        }
        
        //render auslagen
        $this->stateHandler = new StateHandler("projekte", self::$states, self::$stateChanges, [], [], $stateNow);
        $this->permissionHandler = new PermissionHandler(self::$emptyData, $this->stateHandler, self::$writePermissionAll, self::$writePermissionFields, self::$visibleFields, $editMode);
        $this->templater = new FormTemplater($this->permissionHandler);
    
        $this->selectable_users = FormTemplater::generateUserSelectable(false);
        $this->selectable_posten = FormTemplater::generateProjektpostenSelectable(8);
    }
    
    public static function initStaticVars(){
     
        self::$stateChanges = [
            "draft" => [
                "wip" => true,
            ],
            "wip" => [
                "ok-hv" => ["groups" => ["ref-finanzen-hv"]],
                "ok-kv" => ["groups" => ["ref-finanzen-kv"]],
                "draft" => true,
                "revoked" => true,
            ],
            "ok-hv" => [
                "ok-kv-hv" => ["groups" => ["ref-finanzen-kv"]],
                "ok" => ["groups" => ["ref-finanzen-kv"]],
                "wip" => ["groups" => ["ref-finanzen-kv", "ref-finanzen-hv"]],
                "draft" => true,
                "revoked" => true,
            ],
            "ok-kv" => [
                "ok-kv-hv" => ["groups" => ["ref-finanzen-hv"]],
                "ok" => ["groups" => ["ref-finanzen-hv"]],
                "wip" => ["groups" => ["ref-finanzen-kv", "ref-finanzen-hv"]],
                "draft" => true,
                "revoked" => true,
            ],
            "ok-kv-hv" => [
                "ok" => ["groups" => ["ref-finanzen-kv", "ref-finanzen-hv"]],
                "wip" => ["groups" => ["ref-finanzen-kv", "ref-finanzen-hv"]],
                "draft" => true,
                "revoked" => true,
            ],
            "ok" => [
                "instructed" => ["groups" => ["ref-finanzen-kv"]],
                "wip" => ["groups" => ["ref-finanzen-kv", "ref-finanzen-hv"]],
                "draft" => true,
                "revoked" => true,
            ],
            "instructed" => [
                "payed" => ["groups" => ["ref-finanzen-kv"]],
            ],
            "payed" => [
                "booked" => ["groups" => ["ref-finanzen-kv"]],
            ],
            "booked" => [],
            
            "revoked" => [
                "draft" => true,
                "wip" => true,
            ],
        ];
        self::$emptyData = [
        	"beleg" => [],
        	
        	
        	
        	"auslagen-name" => '',
        	"belege-ok" => false,
        	"hv-ok" => false,
        	"kv-ok" => false,
        	"zahlung-iban" => "",
        	"zahlung-name" => "",
        	"zahlung-vwzk" => "",
        	"zahlung-user" => "",
        	
        	
        	
        	
        	"beleg-datum" => [],
        	"beleg-file" => [],
        	"beleg-beschreibung" => [],
        	"posten-name" => [],
        	"beleg-posten-name" => [],
        	"beleg-posten-einnahmen" => [],
        	"beleg-posten-ausgaben" => [],
        ];
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
         
         'auslage' => [
         	"id" => NULL,
        	"projekt_id" => NULL,
        	"name_suffix" => "",
        	"state" => "draft",
      		"last_change" => '',
      		'etag' => ''
        	"belege-ok" => false,
            "hv-ok" => false,
            "kv-ok" => false,
            "zahlung-iban" => "",
            "zahlung-name" => "",
            "zahlung-vwzk" => "",
        	"belege" => []
         ]
         'belege' => [
         	[
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
        self::$writePermissionAll = [
            "draft" => [],
            "wip" => ["groups" => ["ref-finanzen-hv", "ref-finanzen-kv"]],
            
            "ok-hv" => [],
            "ok-kv" => [],
            
            "ok-kv-hv" => [],
            "ok" => [],
            
            "instructed" => [],
            "payed" => [],
            "booked" => [],
            
            "revoked" => [],
        ];
        self::$writePermissionFields = [
        	'draft' => ['belege-ok' => ["groups" => ['ref-finanzen-hv', 'ref-finanzen-kv']],
        				'auslagen-name' => ['groups' => ['sgis']],
      					'zahlung-name' => ['groups' => ['sgis']],
        				'zahlung-iban' => ['groups' => ['sgis']],
        				'zahlung-user' => ['groups' => ['sgis']],
        				'zahlung-vwzk' => ['groups' => ['sgis']],
        				'beleg-datum' => ['groups' => ['sgis']],
        				'beleg-file' => ['groups' => ['sgis']],
        				'beleg-beschreibung' => ['groups' => ['sgis']],
        				'beleg' => ['groups' => ['sgis']],
        				'auslagen' => ['groups' => ['sgis']],],
        	
            "ok-hv" => [],
            "ok-kv" => [],
            
            "ok-kv-hv" => [],
            "ok" => [],
            
            "instructed" => [],
            "payed" => [],
            "booked" => [],
        ];
        self::$visibleFields = [];
        return;
    }
    
    /**
     * get project information from db
     * @param boolean $renderError
     */
    private function getDbProject($renderError = true){
    	$res = $this->db->dbFetchAll("projekte", [], ["projekte.id" => $this->projekt_id], [
    		//TODO ["type" => "inner", "table" => "user", "on" => [["user.id", "projekte.creator_id"]]],
    	], ["version" => true]);
    	if (!empty($res)){
    		$this->projekt_data = $res[0];
    		$this->projekt_data['auslagen'] = [];
    	} else {
    		$this->error = true;
    		$msg = 'Das Projekt mit der ID: '.$this->projekt_id.' existiert nicht. :(<br>';
    		if ($renderError) ErrorHandler::_renderError($msg, 404);
    		return false;
    	}
    	// get auslagen liste
    	$res = $this->db->dbFetchAll(['auslagen'], ['auslagen.id', 'auslagen.name_suffix', 'auslagen.status'], ["auslagen.projekt_id" => $this->projekt_id], [], ['auslagen.id' => true]);
    	if (!empty($res)){
    		$aus = [];
    		foreach ($res as $row){
    			$aus[] = $row;
    		}
    		$this->projekt_data['auslagen'] = $aus;
    	}
    	//TODO remove
    	//$this->projekt_data['auslagen'][] = ['id' => 1, 'name_suffix' => 'mein name suffix', 'state' => 'draft'];
    	$this->getDbProjektPosten($renderError);
    	return true;
    }
    
    /**
     * get auslagen information from db
     * @param boolean $renderError
     */
    private function getDbAuslagen($renderError = true){
    	$res = $this->db->dbFetchAll("auslagen", [], ["auslagen.id" => $this->id, "auslagen.projekt_id" => $this->projekt_id],
    	 [
    	 //TODO ACL? ["type" => "inner", "table" => "user", "on" => [["user.id", "auslagen.creator_id"]]],
    	 ["type" => "inner", "table" => "projekte", "on" => [["projekte.id", "auslagen.projekt_id"]]],
    	 ]);
    	if (!empty($res)){
    		$this->auslagen_data = $res[0];
    		return true;
    	} else {
    		$this->error = true;
    		$msg = 'Eine Auslagenerstattung mit der ID: '.$this->id.' existiert nicht. :(<br>';
    		if ($renderError) ErrorHandler::_renderError($msg, 404);
    		return false;
    	}
    }
    
    private function getDbBelegePostenFiles($renderError = true){
    	$res = $this->db->dbFetchAll("belege", [], ["belege.auslagen_id" => $this->id],
    		[
    			["type" => "left", "table" => "beleg_posten", "on" => [["belege.id", "beleg_posten.beleg_id"]]],
    			["type" => "left", "table" => "fileinfo", "on" => [["fileinfo.id", "belege.file_id"]]],
    			["type" => "left", "table" => "projektposten", "on" => [["beleg_posten.projekt_posten_id", "projektposten.id"]]],
    		], ["belege.id" => true, "belege.short" => true, "beleg_posten.id" => true, "beleg_posten.short" => true, "projektposten.name" => true]);
    	if (!empty($res)){
    		$belege = [];
    		$last_beleg = -1;
    		$last_posten = -1;
    		foreach ($res as $row){
    			//belege
    			if ($last_beleg != $row['belege.id']){
    				$last_beleg == $row['belege.id'];
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
						];
					}
    			}
    			//posten
    			if ($last_posten != $row['beleg_posten.id']){
    				$last_posten = $row['beleg_posten.projekt_posten_id'];
    				if ($last_posten){
    					$belege[$last_beleg]['posten'][$last_posten] = [
			         		'id' => $row['beleg_posten.id'],
			         		'short' => $row['beleg_posten.short'],
			         		'projekt_posten_id' => $row['projektposten.id'],
			         		'projekt.posten_name' => $row['projektposten.name'],
			         		'ausgaben' => $row['beleg_posten.ausgaben'],
			         		'einnahmen' => $row['beleg_posten.einnahmen']
			         	];
    				}
    			}
    		}
    		$this->auslagen_data['belege'] = $belege;
    	}
    	return true;
    }
    
	/**
     * get auslagen information from db
     * @param boolean $renderError
     */
    private function getDbProjektPosten($renderError = true){
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
    
    public function render(){
        // TODO: Implement render() method.
        return $this->renderAuslagenerstattung("Auslagenerstattung");
    }
    
    /**
     *
     * @param array $beleg
     * 	[
         		'id' => NULL,
         		'short' => '',
         		'created_on' => date_create()->format('Y-m-d H:i:s'),
         		'datum' => '',
         		'beschreibung' => '',
         		'file_id' => NULL,
         		'file' => NULL,
         		'posten' => []
         	]
     * @param boolean $hidden
     */
    public function render_beleg_container($belege, $editable = true, $label = ''){
		if ($label){ echo '<label>'.$label.'</label>';} ?>
		<div class="beleg-table well<?= ($editable)? ' editable':'' ?>">
			<div class="hidden datalists">
				<datalist class="datalist-projekt">
					<option value="0" data-alias="Bitte Wählen">
				<?php foreach ($this->projekt_data['posten'] as $p){ ?>
					<option value="<?= $p['id'] ?>" data-alias="<?= $p['name'] ?>">
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
    
    public static function trimIban($in){
    	$in = trim($in);
    	if ($in === '') return '';
    	if (mb_strlen($in)>=5) {
    		return mb_substr($in, 0, 4).' ... ... '.mb_substr($in, -4);
    	} else {
    		return $in;
    	}
    }
    
    public function render_beleg_row($beleg, $editable = true,  $hidden = false){
    	ob_start();
    	$date = ($beleg['datum'])? date_create($beleg['datum'])->format('d.m.Y') : '';
    	$date_value = ($beleg['datum'])? date_create($beleg['datum'])->format('Y-m-d') : '';
    	$date_form = ($editable)? $this->templater->getDatePickerForm(($hidden)? '' : "beleg[{$beleg['id']}][datum]", $date_value, 0, "", "", []): '<strong>am </strong>'.$date;
		
    	$file_form = '';
    	if (!$hidden) {
    		if ($beleg['file_id']) {
    			$file_form = '<span class="beleg-file" data-id="'.$beleg['file_id'].'">'.
    				'<a href="'.URIBASE.'files/get/'.$beleg['file']['hashname'].'">'.$beleg['file']['filename'].'</a>'.
    				'<div><small><span style="min-width: 50px; font-weight: bold;">Size: </span>'.
    							'<span>'.FileHandler::formatFilesize($beleg['file']['size']).'</span></small>'.
    					 '<small><span style="min-width: 50px; font-weight: bold;">Mime: </span>'.
    							'<span>'.$beleg['file']['mime'].'</span></small>'.
    				'</div><button type="button" title="Löschen" class="file-delete btn btn-danger">X</button>'.
    			'</span>';
    		} else {
    			if ($editable){
    				$file_form = $this->templater->getFileForm("files[beleg_{$beleg['id']}]", 0, 0, "Datei...", "", []);
    			} else {
    				$file_form = '<span>Keine Datei verknüpft.</span>';
    			}
    		}
    	} else {
    		$file_form = $this->templater->getFileForm("", 0, 0, "Datei...", "", []);
    	}
    	
    	$desc_form = '';
    	if ($editable) {
    		$desc_form = $this->templater->getTextareaForm(($hidden)? '' : "beleg[{$beleg['id']}][beschreibung]", ($beleg['beschreibung'])?$beleg['beschreibung']:"", 0, "optional", "", [], 1);
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
						echo $this->beleg_posten_table($beleg['posten'], $editable, $hidden, $beleg['id']);
						?></div>
					</div>
				</div>
			</div>
		</div>
    	<?php
    	return ob_get_clean();
    }
    
    public function beleg_posten_table($posten, $editable, $hidden, $beleg_id){
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
    						.'<span class="value">'.$pline['projekt.posten_name'].'</span>'
    						.'<input type="hidden" name="beleg['.$beleg_id.'][posten]['.$pline['id'].'][projekt-posten]" value="'.$pline['projekt_posten_id'].'">'
    					.'</div>';
    		} else {
    			$out .= '<div class="col-sm-4 posten-name">'.$pline['projekt.posten_name'].'</div>';
    		}
    		
    		//einnahmen
    		if ($editable){
    			$out .= '<div class="col-sm-3 posten-in">'
    						.'<div class="input-group">'
    							.'<input class="form-control" name="beleg['.$beleg_id.'][posten]['.$pline['id'].'][in]" type="number" step="0.01" min="0" value="'.$pline['einnahmen'].'">'
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
    						.'<div class="input-group">'
    							.'<input class="form-control" name="beleg['.$beleg_id.'][posten]['.$pline['id'].'][out]" type="number" step="0.01" min="0" value="'.$pline['ausgaben'].'">'
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
    						.'<span class="value"></span>'
    						.'<input type="hidden" value="0">'
    					.'</div>';
    		
    		//einnahmen
			$out .= '<div class="col-sm-3 posten-in">'
						.'<div class="input-group">'
							.'<input class="form-control" type="number" step="0.01" min="0" value="0">'
							.'<div class="input-group-addon">€</div>'
						.'</div>'
					.'</div>';
    		
    		//ausgaben
			$out .= '<div class="col-sm-3 posten-out">'
						.'<div class="input-group">'
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
    
    private function renderAuslagenerstattung($titel){
    	if ($this->error) return -1;
    	$editable = ($this->args['action'] == 'create' || $this->args['action'] == 'edit');
        ?>
        	<h3><?= $titel . (($this->title)? $this->title: '') ?></h3>
             <?php //-------------------------------------------------------------------- ?>
            <label for="projekt-well">Projekt Information</label>
            <div id='projekt-well' class="well">
            	<?php $show_genemigung_state = ($this->args['action'] != 'create' || isset($this->auslagen_data['state']) && $this->auslagen_data['state'] != 'draft' ); ?>
            	<?= $this->templater->generateListGroup(
	            	[
	            		[	'html' => '<i class="fa fa-fw fa-chain"></i>&nbsp;'.$this->projekt_data['name'], 
	            	 	'attr' => ['href' => URIBASE.'projekt/'.$this->projekt_id ]	],
	            	],
	            	'Zugehöriges Projekt', false, $show_genemigung_state, '', 'a'); ?>
	            <?php 
	    			if(count($this->projekt_data['auslagen'])==0){
	    				echo '<label for="auslagen-vorhanden">Im Projekt vorhandene Auslagenerstattungen</label>';
	    				echo '<div  class="well" style="margin-bottom: 0px; background-color: white;"><span>Keine</span></div>';
		            } else {
		            	$tmpList = [];
		            	foreach ($this->projekt_data['auslagen'] as $auslage){
		            		$tmpList[] = [
		            			'html' => $auslage['name_suffix'].'<span class="label label-info pull-right"><span>Status: </span><span>'.self::$states[$auslage['state']][0].'</span></span>',
		            			'attr' => ['href' => URIBASE.'projekt/'.$this->projekt_id.'/auslagen/'.$auslage['id']],
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
	            	<div style="padding-bottom: 10px;"><?= self::$states[$this->auslagen_data['state']][0];?></div>
	            	<label for="genehmigung">Original-Belege</label>
	            	<?php 
	            		if($this->auslagen_data['belege-ok']){
	            			echo '<div class="" title="'.$this->auslagen_data['belege-ok'].'" style="padding-left: 10px; padding-bottom: 10px;"><i class="fa fa-fw fa-2x fa-check-square-o"></i> <strong style="line-height: 1.6em; height: 2em; vertical-align: top;"> eingereicht</strong></div>';
	            		} else {
	            			echo '<div class="" style="padding-left: 10px; padding-bottom: 10px;"><i class="fa fa-fw fa-2x fa-square-o"></i> <strong style="line-height: 1.6em; height: 2em; vertical-align: top;"> nicht eingereicht</strong></div>';
	            		}
	            	?>
	            	<label for="genehmigung">Genehmigung</label>
	            	<br>
		                <?= $this->templater->getTextForm("hv-ok", $this->auslagen_data['hv-ok']? $this->auslagen_data['hv-ok']:'ausstehend', 6, "HV", "HV", []) ?>
		                <?= $this->templater->getTextForm("kv-ok", $this->auslagen_data['kv-ok']? $this->auslagen_data['kv-ok']:'ausstehend', 6, "KV", "KV", []) ?>
		           <div class="clearfix"></div>
		        </div>
	        <?php } ?>
	        <input type="hidden" name="nononce" value="<?= $GLOBALS["nonce"] ?>">
	        <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
	        <form class="ajax" method="POST" enctype="multipart/form-data" action="<?= URIBASE ?>index.php/rest/forms/auslagen/updatecreate">
	        <?php //-------------------------------------------------------------------- ?>
            <label for="projekt-well">Allgemein</label>
            <div id='projekt-well' class="well">
            	<label>Name der Auslagenerstattung</label>
            	<?= $this->templater->getTextForm("auslagen-name", "", 12, "optional", "", [], 'Auslagenname') ?>
	            <div class="clearfix"></div>
            </div>
            <?php //-------------------------------------------------------------------- ?>
            <label for="zahlung">Zahlungsinformationen</label>
            <div id="zahlung" class="well">
				<?= $this->templater->getTextForm("zahlung-name", $editable&&$this->args['action']!='create'?$this->auslagen_data['zahlung-name']:'', 6, "Name Zahlungsempfänger", "anderen Zahlungsempfänger Name (neu)", [], []) ?>
				<?php //TODO iban only show trimmed if not hv/kv important!
	            	$iban_text = $editable&&$this->args['action']!='create'?$this->auslagen_data['zahlung-iban']:'';
	            	if (!AuthHandler::getInstance()->hasGroup('HV,KV')){
	            		$iban_text = self::trimIban($iban_text);
	            	}
					echo $this->templater->getTextForm("zahlung-iban", $iban_text, 6, "DE ...", "anderen Zahlungsempfänger IBAN (neu)") ?>
                <div class='clearfix'></div>
                <?= $this->templater->getTextForm("zahlung-vwzk", $editable&&$this->args['action']!='create'?$this->auslagen_data['zahlung-vwzk']:'', 12, "z.B. Rechnungsnr. o.Ä.", "Verwendungszweck (verpflichtent bei Firmen)", [], []) ?>
                <?= $this->templater->getHiddenActionInput('zahlung-user'); ?>
                <div class="clearfix"></div>
            </div>
            <?php //-------------------------------------------------------------------- ?>
	        <?php
          
	            $belege = (isset($this->auslagen_data['belege']))? $this->auslagen_data['belege']: [];
	            /*//TODO remove this comment
	             
	             $belege = [[
	         		'id' => '42',
	         		'short' => 'B2',
	         		'created_on' => date_create()->format('Y-m-d H:i:s'),
	         		'datum' => '2017-12-03',
	         		'beschreibung' => 'Einmal einkaufen für alle',
	         		'file_id' => NULL,
	         		'file' => NULL,
	         		'posten' => [
	         			[
	         				'id' => '13',
	         				'short' => '3',
	         				'projekt_posten_id' => 6,
	         				'projekt.posten_name' => 'Getränke',
	         				'ausgaben' => '30.77',
	         				'einnahmen' => '12.03'
	            		]
	         		]
         		]]; //*/
	       		
	            $this->render_beleg_container($belege, $editable,  'Belege');

           		$beleg_nr = 0;
            	$tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
            
            	?>
          
            
            
			<?php /* ?>
			
			$beleg_nr = 0;
            	$tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
			
            <table id="beleg-table"
                   class="table table-striped <?= ($tablePartialEditable ? "dynamic-table" : "dynamic-table-readonly") ?>">
                <thead>
                <tr>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="dynamic-cell">
                        <div class="col-xs-1 form-group">
                            <label for="belegnr-<?= $beleg_nr ?>">Beleg</label>
                            <div id="belegnr-<?= $beleg_nr ?>">B<?= $beleg_nr + 1 ?>&nbsp;<a href='' class='delete-row'><i
                                            class='fa fa-fw fa-trash'></i></a>
                            </div>
                        </div>
                        <?= $this->templater->getDatePickerForm("beleg-datum[]", "", 4, "Beleg-Datum", "Datum des Belegs", []) ?>
                        <?= $this->templater->getFileForm("beleg-file[]", 0, 7, "Datei...", "Scan des Belegs", []) ?>
                        <?= $this->templater->getTextareaForm("beleg-beschreibung[]", "", 12, "optional", "Beschreibung", [], 1) ?>
                        <table class="col-xs-12 table table-striped summing-table <?= ($tablePartialEditable ? "dynamic-table" : "dynamic-table-readonly") ?>">
                            <thead>
                            <tr>
                                <th></th><!-- Nr.       -->
                                <th></th><!-- Trashbin  -->
                                <th class="">Projektposten</th>
                                <th class="col-xs-3">Einnahmen</th>
                                <th class="col-xs-3">Ausgaben</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            for ($row_nr = 0; $row_nr <= count($this->auslagen_data["posten-name"]); $row_nr++){
                                $new_row = $row_nr === count($this->auslagen_data["posten-name"]);
                                if ($new_row && !$tablePartialEditable)
                                    continue;
                                ?>
                                <tr class="<?= $new_row ? "new-table-row" : "dynamic-table-row" ?>">
                                    <td class="row-number"> <?= $row_nr + 1 ?>.</td>
                                    <?php if ($tablePartialEditable){ ?>
                                        <td class='delete-row'><a href='' class='delete-row'><i
                                                        class='fa fa-fw fa-trash'></i></a></td>
                                    <?php }else{
                                        echo "<td></td>";
                                    } ?>
                                    <td><?= $this->templater->getDropdownForm("beleg-posten-name[$beleg_nr][]", $this->selectable_posten, 12, "Wähle Posten aus Projekt", "", [], false) ?></td>
                                    <td>
                                        <?= $this->templater->getMoneyForm("beleg-posten-einnahmen[$beleg_nr][]", 0, 12, "", "", [], "beleg-in-$beleg_nr") ?>
                                    </td>
                                    <td>
                                        <?= $this->templater->getMoneyForm("beleg-posten-ausgaben[$row_nr][]", 0, 12, "", "", [], "beleg-out-$beleg_nr") ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th></th><!-- Nr.       -->
                                <th></th><!-- Trashbin  -->
                                <th></th><!-- Name      -->
                                <th class="dynamic-table-cell cell-has-printSum">
                                    <div class="form-group no-form-grp">
                                        <div class="input-group input-group-static">
                                            <span class="input-group-addon">Σ</span>
                                            <div class="form-control-static nowrap text-right"
                                                 data-printsum="beleg-in-<?= $beleg_nr ?>">0,00
                                            </div>
                                            <span class="input-group-addon">€</span>
                                        </div>
                                    </div>
                                </th><!-- einnahmen -->
                                <th class="dynamic-table-cell cell-has-printSum">
                                    <div class="form-group no-form-grp">
                                        <div class="input-group input-group-static">
                                            <span class="input-group-addon">Σ</span>
                                            <div class="form-control-static nowrap text-right"
                                                 data-printsum="beleg-out-<?= $beleg_nr ?>">0,00
                                            </div>
                                            <span class="input-group-addon">€</span>
                                        </div>
                                    </div>
                                </th><!-- ausgaben -->
                            </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td>
                        <button onclick="" class="btn btn-success">
                            <i class="fa fa-fw fa-plus"></i>&nbsp;Neuen Beleg hinzufügen
                        </button>
                    </td>
                </tr>
                </tfoot>
            </table>
			<?php //*/ ?>
			<div class="row row-striped add-button-row" style="margin: 10px 0;">
	    		<div class="send" style="padding:5px;">
		    		<div class="text-center"><button type="submit" class="btn btn-success" style="min-width:100px; font-weight: bold;">Änderungen Speichern</button></div>
	    		</div>
    		</div>
		</form>
        <?php
        return;
    }
    
    public function getID(){
        if (isset($this->id))
            return $this->id;
        else
            return null;
    }
    
    public function getProjektID(){
        if (isset($this->projekt_id))
            return $this->projekt_id;
        else
            return null;
    }
}