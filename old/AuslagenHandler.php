<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:55
 */

class AuslagenHandler extends FormHandlerInterface{
    
    static private $emptyData;
    static private $states;
    static private $stateChanges;
    static private $printModes;
    static private $visibleFields;
    static private $writePermissionAll;
    static private $writePermissionFields;
    
    private $id;
    private $projekt_id;
    private $templater;
    private $permissionHandler;
    private $stateHandler;
    private $data;
    private $args;
    private $selectable_users;
    private $selectable_posten;
    
    public function __construct($args){
        self::initStaticVars();
        if (!isset($args) || empty($args)){
            $args = ["create", "edit"];
        }
        $this->args = $args;
        
        if (!is_numeric($args[0])){
            $this->data = self::$emptyData;
            $stateNow = "draft";
        }else{
            $this->id = $args[0];
            $res = DBConnector::getInstance()->dbFetchAll("projekte", [], ["projekte.id" => $this->id], [
                ["type" => "inner", "table" => "user", "on" => [["user.id", "projekte.creator_id"]]],
            ], ["version" => true]);
            if (!empty($res))
                $this->data = $res[0];
            else
                die("konnte Projekt nicht finden :(");
    
            $stateNow = $this->data["state"];
    
        }
        
        $editMode = false;
        if (isset($args[1]) && $args[1] === "edit")
            $editMode = true;
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
            "state" => "draft",
            "projekt-name" => "",
            "belege-ok" => false,
            "hv-ok" => false,
            "kv-ok" => false,
            "zahlung-user" => "",
            "zahlung-iban" => "",
            "zahlung-name" => "",
            "zahlung-vwzk" => "",
            "zahlung-typ" => "",
            "posten-name" => [[""]],
            "beleg-datum" => [],
            "beleg-beschreibung" => [],
            "beleg-file" => [],
            "beleg-posten-name" => [[""]],
            "beleg-posten-einnahmen" => [[0]],
            "beleg-posten-ausgaben" => [[0]],

        ];
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
        $this->renderAuslagenerstattung("Auslagenerstattung");
    }
    
    private function renderAuslagenerstattung($titel){
        $tablePartialEditable = true;//$this->permissionHandler->isEditable(["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"], "and");
        ?>
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
        <?php
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