<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:55
 */

class AuslagenHandler2 implements FormHandlerInterface{
    
    static private $emptyData;
    static private $states;
    static private $stateChanges;
    static private $printModes;
    static private $visibleFields;
    static private $writePermissionAll;
    static private $writePermissionFields;
    
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
    
    private $id;
    private $projekt_id;
    private $data;
    private $projekt_data;
    private $templater;
    private $permissionHandler;
    private $stateHandler;
    private $args;
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
        
        //check projekt exists --------------------
        if (!$this->getProject()) return; //set error

        // create or edit -------------------------
        $editMode = false;
        if ($args['action']=='create'){
            $this->data = self::$emptyData;
            $stateNow = "draft";
        } elseif($args['action'] == 'edit') {
            //load from db
           	$editMode = true;
           	$this->id = $args['aid'];
           	//check auslagen id exists --------------------
            if (!$this->getAuslagen()) return;
            if (!$this->getBelegePostenFiles()) return;
            
            echo '<pre>'; var_dump($this->data); echo '</pre>';
            die();
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
        if (isset(self::$states))
            return;
        self::$states = [
            "draft" => ["Entwurf"],
            "wip" => ["Beantrag", "als beantragt speichern"],
            
            "ok-hv" => ["HV ok", "als Haushaltsverantwortlicher genehmigen",],
            "ok-kv" => ["KV ok", "als Kassenverantwortlicher genehmigen",],
            
            "ok-kv-hv" => ["Originalbelege fehlen", "als rechnerisch und sachlich richtig (Belege fehlen)",],
            "ok" => ["Genehmigt", "als rechnerisch und sachlich richtig (Belege vorhanden)",],
            
            "instructed" => ["Angewiesen",],
            "payed" => ["Bezahlt (lt. Kontoauszug)",],
            "booked" => ["Gezahlt und Gebucht"],
            
            "revoked" => ["Zurückgezogen", "zurückziehen",],
        ];
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
        	"id" => NULL,
        	"projekt_id" => NULL,
        	"name_suffix" => "",
        	"state" => "draft",
        	"belege-ok" => false,
            "hv-ok" => false,
            "kv-ok" => false,
            "zahlung-iban" => "",
            "zahlung-name" => "",
            "zahlung-vwzk" => "",
        	"belege" => []
        ];
        /*
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
            "draft" => true,
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
    		return true;
    	} else {
    		$this->error = true;
    		$msg = 'Das Projekt mit der ID: '.$this->projekt_id.' existiert nicht. :(<br>';
    		if ($renderError) ErrorHandler::_renderError($msg, 404);
    		return false;
    	}
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
    		$this->data = $res[0];
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
    		$this->data['belege'] = $belege;
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
        $tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
        ?>
        <div class="main container col-xs-12 col-md-10">
            <h3><?= $titel ?></h3>
            <label for="genehmigung">Genehmigung</label>
            <div id='genehmigung' class="well">
                <?= $this->templater->getCheckboxForms("belege-ok", true, 12,
                    "Original-Belege sind abgegeben worden", []) ?>
                <?= $this->templater->getTextForm("hv-ok", "", 6, "HV", "Genehmigt durch HV", []) ?>
                <?= $this->templater->getTextForm("kv-ok", "", 6, "KV", "Genehmigt durch KV", []) ?>
                <div class="clearfix"></div>
            </div>
            <label for="projekt-well">Zugehöriges Projekt</label>
            <div id='projekt-well' class="well">
    
                <?= $this->templater->getTextForm("projekt-name", "", 12, "optional", "Projekt Name | Zusätzlicher Name für Auslagenerstattung", [], $this->templater->getHyperLink("projektname hier!!", "projekt", $this->id)) ?>

                <div class="clearfix"></div>
            </div>
            <label for="zahlung">Zahlungsinformationen</label>
            <div id="zahlung" class="well">
                <div class="hide-wrapper">
                    <div class="hide-picker">
                        <?= $this->templater->getDropdownForm("zahlung-typ",
                            ["groups" => [[
                                "options" => [
                                    ["label" => "bekannter Zahlungsempfänger", "value" => "knownPerson"],
                                    ["label" => "Neuer Nutzer", "value" => "newPerson"],
                                ]
                            ]]], 12, "neuer oder bekannter Nutzer", "", ["required"], false) ?>
                    </div>
                    <div class="hide-items">
                        <div id="knownPerson" style="display: none;">
                            <?= $this->templater->getDropdownForm("zahlung-user", $this->selectable_users, 12, "Angelegten User auswählen", "Zahlungsempfänger", [], true) ?>
                        </div>
                        <div id="newPerson" style="display: none;">
                            <?= $this->templater->getTextForm("zahlung-name", "", 6, "Name Zahlungsempfänger", "anderen Zahlungsempfänger Name (neu)", [], []) ?>
                            <?= $this->templater->getTextForm("zahlung-iban", "", 6, "DE ...", "anderen Zahlungsempfänger IBAN (neu)", ["iban" => true]) ?>
                        </div>
                    </div>
                </div>
                <div class='clearfix'></div>
    
                <?= $this->templater->getTextForm("zahlung-vwzk", "", 12, "z.B. Rechnungsnr. o.Ä.", "Verwendungszweck (verpflichtent bei Firmen)", [], []) ?>
                <div class="clearfix"></div>
            </div>
            <?php $beleg_nr = 0; ?>
            <div class="col-xs-12">Falls mehrere Posten pro Beleg vorhanden sind bitte auf dem Beleg (vor Scan!)
                kenntlich machen welcher Teil zu welchem u.g. Posten gehört.
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
                            for ($row_nr = 0; $row_nr <= count($this->data["posten-name"]); $row_nr++){
                                $new_row = $row_nr === count($this->data["posten-name"]);
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