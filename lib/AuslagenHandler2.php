<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:55
 */

class AuslagenHandler2 implements FormHandlerInterface{
    
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
    		throw new Exception('missing parameter: pid - project id');
    	}
    	if (!isset($args['action'])){
    		$this->error = true;
    		throw new Exception('missing parameter: action');
    	}
    	if ($args['action'] == 'edit' && !isset($args['aid'])){
    		$this->error = true;
    		throw new Exception('missing parameter: aid - auslagen id');
    	}
    	// init variables ---------------------
        self::initStaticVars();
        $this->args = $args;
        $this->db = DBConnector::getInstance();
        $this->projekt_id = $args['pid'];
        
        // check projekt exists --------------------
        if (!$this->getProject()) return; //set error
        
        // create, view or edit -------------------------
        $editMode = false;
        if ($args['action']=='create'){
            $this->auslagen_data = self::$emptyData;
            $stateNow = "draft";
            $editMode = true;
            //page title
            $this->title = ' - Erstellen'; 
        } elseif($args['action'] == 'edit') {
            //load from db
           	$editMode = true;
           	$this->id = $args['aid'];
           	//check auslagen id exists --------------------
            if (!$this->getAuslagen()) return;
            if (!$this->getBelegePostenFiles()) return;
            $stateNow = $this->auslagen_data['state'];
            //page title
            $this->title = ' - Bearbeiten';
            echo '<pre>'; var_dump($this->auslagen_data); echo '</pre>';
            die();
        } elseif($args['action'] == 'view') {
        	//check auslagen id exists --------------------
        	if (!$this->getAuslagen()) return;
        	if (!$this->getBelegePostenFiles()) return;
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
        				'zahlung-vwzk' => ['groups' => ['sgis']]],
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
    private function getProject($renderError = true){
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
    	return true;
    }
    
    /**
     * get auslagen information from db
     * @param boolean $renderError
     */
    private function getAuslagen($renderError = true){
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
    
    private function getBelegePostenFiles($renderError = true){
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
    
    private function renderAuslagenerstattung($titel){
    	if ($this->error) return -1;
        ?>
        <div class="main container col-xs-12 col-md-10">
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
				<?= $this->templater->getTextForm("zahlung-name", "", 6, "Name Zahlungsempfänger", "anderen Zahlungsempfänger Name (neu)", [], []) ?>
				<?= $this->templater->getTextForm("zahlung-iban", "", 6, "DE ...", "anderen Zahlungsempfänger IBAN (neu)", ["iban" => true]) ?>
                <div class='clearfix'></div>
                <?= $this->templater->getTextForm("zahlung-vwzk", "", 12, "z.B. Rechnungsnr. o.Ä.", "Verwendungszweck (verpflichtent bei Firmen)", [], []) ?>
                <?= $this->templater->getHiddenActionInput('zahlung-user'); ?>
                <div class="clearfix"></div>
            </div>
            <?php //-------------------------------------------------------------------- ?>
            <?php 
            
           		$beleg_nr = 0;
            	$tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
            
            ?>
            <div class="col-xs-12">Falls mehrere Posten pro Beleg vorhanden sind bitte auf dem Beleg (vor Scan!)
                kenntlich machen welcher Teil zu welchem u.g. Posten gehört.
            </div>
            <?php ?>
            <div class="beleg-table table table-striped">
            	<div class=""></div>
            
            </div>
            
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

        </div>
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