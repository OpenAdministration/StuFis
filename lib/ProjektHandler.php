<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 29.04.18
 * Time: 01:29
 */

class ProjektHandler
    extends FormHandlerInterface
{
    static private $emptyData;
    static private $selectable_recht;
    static private $states;
    static private $stateChanges;
    static private $printModes;
    static private $visibleFields;
    static private $writePermissionAll;
    static private $writePermissionFields;

    private $templater;
    private $stateHandler;
    /**
     * @var PermissionHandler
     */
    private $permissionHandler;
    private $id;
    private $action;
    private $data;

    function __construct($pathInfo)
    {
        //print_r($pathInfo);
        self::initStaticVars();
        if (!isset($pathInfo["action"]))
            ErrorHandler::_errorExit("Aktion nicht gesetzt");
        $this->action = $pathInfo["action"];

        if ($this->action === "create" || !isset($pathInfo["pid"])) {
            $this->data = self::$emptyData;
            $stateNow = "draft";
        } else {
            $this->id = $pathInfo["pid"];
            $res = DBConnector::getInstance()->dbFetchAll(
                "projekte",
                [DBConnector::FETCH_ASSOC],
                [],
                ["projekte.id" => $this->id],
                [
                    ["type" => "left", "table" => "user", "on" => [["user.id", "projekte.creator_id"]]],
                ],
                ["version" => true]
            );
            if (!empty($res))
                $this->data = $res[0];
            else
                die("konnte Projekt nicht finden :(");
            $tmp = DBConnector::getInstance()->dbFetchAll(
                "projektposten",
                [DBConnector::FETCH_ASSOC],
                [],
                ["projekt_id" => $this->id]
            );
            foreach ($tmp as $row) {
                $idx = $row["id"];
                $this->data["posten-name"][$idx] = $row["name"];
                $this->data["posten-bemerkung"][$idx] = $row["bemerkung"];
                $this->data["posten-einnahmen"][$idx] = $row["einnahmen"];
                $this->data["posten-ausgaben"][$idx] = $row["ausgaben"];
                $this->data["posten-titel"][$idx] = $row["titel_id"];
            }
            $stateNow = $this->data["state"];
        }

        $editMode = $this->action === "create" || $this->action === "edit";
        $this->stateHandler = new StateHandler("projekte", self::$states, self::$stateChanges, [], [], $stateNow);
        $this->permissionHandler = new PermissionHandler(
            self::$emptyData,
            $this->stateHandler,
            self::$writePermissionAll,
            self::$writePermissionFields,
            self::$visibleFields,
            $editMode
        );
        $this->templater = new FormTemplater($this->permissionHandler);
    }

    static function initStaticVars()
    {
        if (isset(self::$states))
            return false;
        self::$states = [
            "draft" => ["Entwurf",],
            "wip" => ["Beantragt", "beantragen"],
            "ok-by-hv" => ["Genehmigt durch HV (nicht verkündet)",],
            "need-stura" => ["Warte auf StuRa-Beschluss",],
            "ok-by-stura" => ["Genehmigt durch StuRa-Beschluss",],
            "done-hv" => ["verkündet durch HV",],
            "done-other" => ["Genehmigt (Verkündung nicht nötig)",],
            "revoked" => [
                "Abgelehnt / Zurückgezogen (KEINE Genehmigung oder Antragsteller verzichtet)",
                "zurückziehen / ablehnen",
            ],
            "terminated" => ["Abgeschlossen (keine weiteren Ausgaben)", "beenden",],
        ];
        self::$stateChanges = [
            "draft" => [
                "wip" => ["groups" => ["sgis"]],
            ],
            "wip" => [
                "draft" => true,
                "need-stura" => ["groups" => ["ref-finanzen-hv"]],
                "ok-by-hv" => ["groups" => ["ref-finanzen-hv"]],
                "done-other" => ["groups" => ["ref-finanzen-hv"]],
                "revoked" => ["groups" => ["sgis"]],
            ],
            "ok-by-hv" => [
                "done-hv" => ["groups" => ["ref-finanzen-hv"]],
                "need-stura" => ["groups" => ["ref-finanzen-hv"]],
            ],
            "need-stura" => [
                "ok-by-stura" => ["groups" => ["ref-finanzen-hv"]],
                "ok-by-hv" => ["groups" => ["ref-finanzen-hv"]],
                "revoked" => ["groups" => ["ref-finanzen-hv"]],
            ],
            "done-hv" => [
                "terminated" => true,
            ],
            "done-other" => [
                "terminated" => true,
            ],
            "ok-by-stura" => [
                "terminated" => true,
            ],
            "revoked" => [
                "wip" => ["groups" => ["sgis"]],
            ],
            "terminated" => [
                "done-hv" => ["groups" => ["ref-finanzen-hv"]],
                "done-other" => ["groups" => ["ref-finanzen-hv"]],
                "ok-by-stura" => ["groups" => ["ref-finanzen-hv"]],
            ],
        ];
        self::$printModes = [
            "zahlungsanweisung" =>
                [
                    "title" => "Titelseite drucken",
                    "condition" => [
                        ["state" => "draft", "group" => "ref-finanzen"],
                        ["state" => "ok-by-stura", "group" => "ref-finanzen"],
                    ],
                ],
        ];
        self::$selectable_recht =
            [
                "values" => "",
                "groups" =>
                    [
                        [
                            //"label" => "Gruppenname",
                            "options" => [
                                [
                                    "label" => "Büromaterial",
                                    "value" => "buero",
                                ],
                                [
                                    "label" => "Fahrtkosten",
                                    "value" => "fahrt",
                                ],
                                [
                                    "label" => "Verbrauchsmaterial",
                                    "value" => "verbrauch",
                                ],
                                [
                                    "label" => "Beschluss StuRa-Sitzung",
                                    "value" => "stura",
                                ],
                                [
                                    "label" => "Beschluss Fachschaftsrat/Referat/AG bis zu 250 EUR",
                                    "value" => "fsr-ref",
                                ],
                                [
                                    "label" => "Gremienkleidung",
                                    "value" => "kleidung",
                                ],
                                [
                                    "label" => "Bahncard",
                                    "value" => "bahn-card",
                                ],
                                [
                                    "label" => "Andere Rechtsgrundlage",
                                    "value" => "andere",
                                ],
                            ],
                        ],
                    ],
            ];

        self::$emptyData = [
            'id' => '',
            'creator_id' => '',
            'createdat' => '',
            'lastupdated' => '',
            'version' => '1',
            'state' => 'draft',
            'stateCreator_id' => '',
            'name' => '',
            'responsible' => '',
            'org' => '',
            'org-mail' => '',
            'protokoll' => '',
            'beschreibung' => '',
            'recht' => '',
            'recht-additional' => '',
            'posten-name' => [1 => ""],
            'posten-bemerkung' => [1 => ""],
            'posten-titel' => [1 => ""],
            'posten-einnahmen' => [1 => 0],
            'posten-ausgaben' => [1 => 0],
            'date-start' => '',
            'date-end' => '',
        ];
        self::$visibleFields = [
            "recht" => [
                "wip",
                "ok-by-hv",
                "need-stura",
                "ok-by-stura",
                "done-hv",
                "done-other",
                "terminated",
            ],
            "posten-titel" => [
                "wip",
                "ok-by-hv",
                "need-stura",
                "ok-by-stura",
                "done-hv",
                "done-other",
                "terminated",
            ],
            "createdat" => [
                "wip",
                "ok-by-hv",
                "need-stura",
                "ok-by-stura",
                "done-hv",
                "done-other",
                "terminated",
            ],
        ];
        self::$writePermissionAll = [
            "draft" => ["groups" => ["sgis"]],
            "wip" => ["groups" => ["ref-finanzen-hv"]],
            "ok-by-hv" => ["groups" => ["ref-finanzen-hv"]],
            "need-stura" => ["groups" => ["ref-finanzen-hv"]],
            "ok-by-stura" => ["groups" => ["ref-finanzen-hv"]],
            "done-hv" => ["groups" => ["ref-finanzen-hv"]],
            "done-other" => ["groups" => ["ref-finanzen-hv"]],
            "terminated" => [],
            "revoked" => [],
        ];
        self::$writePermissionFields = [
            "ok-by-hv" => [
                "recht-additional" => ["groups" => ["ref-finanzen-hv"]],
            ],
        ];
        return true;
    }


    /**
     * @param $data
     *
     * @return ProjektHandler
     * @throws InvalidDataException
     * @throws PDOException
     */
    public static function createNewProjekt($data): ProjektHandler
    {

        $maxRows = max(
            count($data["posten-name"]),
            count($data["posten-bemerkung"]),
            count($data["posten-einnahmen"]),
            count($data["posten-ausgaben"])
        );
        $minRows = min(
            count($data["posten-name"]),
            count($data["posten-bemerkung"]),
            count($data["posten-einnahmen"]),
            count($data["posten-ausgaben"])
        );

        if ($maxRows !== $minRows) {
            throw new InvalidDataException("Projekt-Zeilen ungleichmäßig übertragen");
        }

        $user_id = DBConnector::getInstance()->getUser()["id"];
        $projekt_id = DBConnector::getInstance()->dbInsert(
            "projekte",
            [
                "creator_id" => $user_id,
                "createdat" => date("Y-m-d H:i:s"),
                "lastupdated" => date("Y-m-d H:i:s"),
                "version" => 1,
                "state" => "draft",
                "stateCreator_id" => $user_id,
                "name" => $data["name"],
                "responsible" => $data["responsible"],
                "org" => $data["org"],
                "org-mail" => $data["org-mail"],
                "protokoll" => $data["protokoll"],
                "beschreibung" => $data["beschreibung"],
                "date-start" => $data["date-start"],
                "date-end" => $data["date-end"],
            ]
        );

        for ($i = 0; $i < $minRows - 1; $i++) {
            if (floatval($data["posten-ausgaben"][$i]) > 0 && floatval($data["posten-einnahmen"][$i]) > 0) {
                throw new InvalidDataException(
                    "Projektposten dürfen nicht gleichzeitig Einnahmen und Ausgaben enthalten."
                );
            }
            DBConnector::getInstance()->dbInsert(
                "projektposten",
                [
                    "id" => $i + 1,
                    "projekt_id" => $projekt_id,
                    "einnahmen" => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data["posten-einnahmen"][$i],
                        "money"
                    ),
                    "ausgaben" => DBConnector::getInstance()->convertUserValueToDBValue(
                        $data["posten-ausgaben"][$i],
                        "money"
                    ),
                    "name" => $data["posten-name"][$i],
                    "bemerkung" => $data["posten-bemerkung"][$i]
                ]
            );
        }

        return new ProjektHandler(["pid" => $projekt_id, "action" => "none"]);
    }

    public static function getStateStringFromName($statename)
    {
        self::initStaticVars();
        return self::$states[$statename][0];
    }

    /**
     * @param $data
     *
     * @return bool|int
     * @throws PDOException
     * @throws WrongVersionException
     * @throws InvalidDataException
     */
    public function updateSavedData($data)
    {
        $data = array_intersect_key($data, self::$emptyData);
        $version = $data["version"];

        //check if version is the same
        if ($version !== $this->data["version"])
            throw new WrongVersionException("Projekt wurde zwischenzeitlich schon von jemand anderem bearbeitet!");
        //check if row count is everywhere the same
        $maxRows = $minRows = 0;
        if (isset($data["posten-name"]) && isset($data["posten-bemerkung"]) && isset($data["posten-einnahmen"]) && isset($data["posten-ausgaben"])) {
            $maxRows = max(
                count($data["posten-name"]),
                count($data["posten-bemerkung"]),
                count($data["posten-einnahmen"]),
                count($data["posten-ausgaben"])
            );
            $minRows = min(
                count($data["posten-name"]),
                count($data["posten-bemerkung"]),
                count($data["posten-einnahmen"]),
                count($data["posten-ausgaben"])
            );
        }
        //wenn posten-titel nicht mit übertragen setze dummy an seine stelle
        if (!isset($data["posten-titel"])) {
            $data["posten-titel"] = array_fill(0, $maxRows, null);
        }

        //wenn anzahl der rows nicht identisch -> error
        if ($maxRows !== $minRows || count($data["posten-titel"]) !== $minRows) {
            throw new InvalidDataException("Projekt-Zeilen ungleichmäßig übertragen");
        }
        //remove some Autogenerated values
        $generatedFields = [
            "id" => $this->id,
            "lastupdated" => date("Y-m-d H:i:s"),
            "version" => ($this->data["version"] + 1)
        ];
        //extract some fields for other db destination
        $extractFields = ["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben", "posten-titel"];
        $extractFields = array_intersect_key($data, array_flip($extractFields));
        $data = array_diff_key($data, $generatedFields, $extractFields);

        $recht_unset = false;
        if (isset($data["recht-additional"])) {
            if (!isset($data["recht"]) && isset($this->data['recht'])) {
                $data["recht"] = $this->data['recht'];
                $recht_unset = true;
            }
            if (!isset($data["recht"])) {
                $data["recht-additional"] = "";
            } else if (isset($data["recht-additional"][$data["recht"]])) {
                $data["recht-additional"] = $data["recht-additional"][$data["recht"]];
            } else {
                $data["recht-additional"] = "";
            }
        }

        if ($recht_unset) {
            unset($data["recht"]);
        }

        //check if fields editable
        $fields = $generatedFields;
        foreach ($data as $name => $content) {
            if ($this->permissionHandler->isEditable($name) && $this->permissionHandler->isVisibleField($name)) {
                if (!empty($content)) {
                    $fields[$name] = $content;
                } else {
                    $fields[$name] = null;
                }
            } else {
                ErrorHandler::_renderJson(["code" => 403, "msg" => "Du hast keine Berechtigung '$name' zu schreiben."]);
            }
        }
        $update_rows = DBConnector::getInstance()->dbUpdate(
            "projekte",
            ["id" => $this->id, "version" => $version],
            $fields
        );

        if ($this->permissionHandler->isEditable(
            ['posten-name', 'posten-bemerkung', 'posten-einnahmen', 'posten-ausgaben'],
            'and'
        )) {
            //set new posten values, *delete* old
            DBConnector::getInstance()->dbDelete("projektposten", ["projekt_id" => $this->id]);
            for ($i = 0; $i < $minRows - 1; $i++) {
                //would throw exception if not working
                DBConnector::getInstance()->dbInsert(
                    "projektposten",
                    [
                        "id" => $i + 1,
                        "projekt_id" => $this->id,
                        "titel_id" => $extractFields["posten-titel"][$i] === "" ? null : $extractFields["posten-titel"][$i],
                        "einnahmen" => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields["posten-einnahmen"][$i],
                            "money"
                        ),
                        "ausgaben" => DBConnector::getInstance()->convertUserValueToDBValue(
                            $extractFields["posten-ausgaben"][$i],
                            "money"
                        ),
                        "name" => $extractFields["posten-name"][$i],
                        "bemerkung" => $extractFields["posten-bemerkung"][$i]
                    ]
                );
            }
        }
        return $update_rows === 1; //true falls nur ein Eintrag geändert
    }

    /**
     * @param $stateName
     *
     * @return  bool
     * @throws IllegalStateException
     * @throws IllegalTransitionException
     */
    public function setState($stateName)
    {
        if (!in_array($stateName, $this->getNextPossibleStates(), true))
            throw new IllegalStateException("In den Status $stateName kann nicht gewechselt werden");

        $user_id = DBConnector::getInstance()->getUser()["id"];
        $logID = DBConnector::getInstance()->logThisAction(
            [
                "user_id" => $user_id,
                "newState" => $stateName,
                "id" => $this->id,
                "version_before" => $this->data["version"]
            ],
            "changeState"
        );
        DBConnector::getInstance()->dbUpdate(
            "projekte",
            ["id" => $this->id, "version" => $this->data["version"]],
            [
                "state" => $stateName,
                "stateCreator_id" => $user_id,
                "lastupdated" => date("Y-m-d H:i:s"),
                "version" => ($this->data["version"] + 1)
            ]
        );
        $chat = new ChatHandler('projekt', $this->id);
        $chat->_createComment(
            'projekt',
            $this->id,
            date_create()->format('Y-m-d H:i:s'),
            'system',
            '',
            self::$states[$this->data['state']][0] . ' -> ' . self::$states[$stateName][0],
            1
        );
        $this->stateHandler->transitionTo($stateName);
        return true;
    }

    public function getNextPossibleStates()
    {
        return $this->stateHandler->getNextStates(true);
    }

    function render()
    {
        if ($this->action === "create" || !isset($this->id)) {
            $this->renderProjekt("neues Projekt anlegen");
            return;
        }

        switch ($this->action) {
            case "edit":
                $this->renderBackButton();
                $this->renderProjekt("Projekt bearbeiten");
                break;
            case "view":
                $this->renderInteractionPanel();
                //echo $this->templater->getStateChooser($this->stateHandler);
                $this->renderProjekt("Internes Projekt");
                $this->render_chat_box();
                $this->renderProjektSizeGrafic();
                $this->renderAuslagenList();
                break;
            default:
                ErrorHandler::_renderError("Aktion: $this->action bei Projekt $this->id nicht bekannt.", 404);
                break;
        }
    }

    private function renderProjekt($title)
    {
        $auth = (AUTH_HANDLER);
        /* @var $auth AuthHandler */
        $auth = $auth::getInstance();
        $validateMe = false;
        $editable = $this->permissionHandler->isAnyDataEditable();

        //build dropdowns
        $selectable_gremien = FormTemplater::generateGremienSelectable($auth->hasGroup("ref-finanzen"));
        $selectable_gremien["values"] = $this->data['org'];

        $mail_selector = $auth->hasGroup("ref-finanzen") ? "alle-mailinglists" : "mailinglists";
        $selectable_mail = FormTemplater::generateSelectable($auth->getAttributes()[$mail_selector]);
        $selectable_mail["values"] = $this->data['org-mail'];

        $sel_recht = self::$selectable_recht;
        $sel_recht["values"] = $this->data['recht'];
        if (isset($this->data["createdat"]) && !empty($this->data["createdat"])) {
            $createDate = $this->data["createdat"];
        } else {
            $createDate = date_create()->format("Y-m-d");
        }
        $hhpId = DBConnector::getInstance()->dbFetchAll(
            "haushaltsplan",
            [DBConnector::FETCH_ASSOC],
            ["id"],
            [
                ["von" => ["<=", $createDate], "bis" => [">=", $createDate], "state" => "final"],
                ["von" => ["<=", $createDate], "bis" => ["is", null], "state" => "final"]
            ]
        );
        if (empty($hhpId)) {
            ErrorHandler::_errorExit("HHP-id kann nicht ermittelt werden. Bitte benachritigen sie den Administrator");
        }
        $hhpId = $hhpId[0]["id"];
        $selectable_titel = FormTemplater::generateTitelSelectable($hhpId);

        ?>
        <div class='col-xs-12 col-md-10'>
            <?php if ($editable){ ?>
            <form role="form" action="<?= URIBASE . "rest/forms/projekt" ?>" method="POST"
                  enctype="multipart/form-data" class="ajax">
                <?= $this->templater->getHiddenActionInput(isset($this->id) ? "update" : "create") ?>
                <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
                <input type="hidden" name="version" value="<?= $this->data["version"] ?>">
                <?php if (isset($this->id)) { ?>
                    <input type="hidden" name="id" value="<?= $this->id ?>">
                <?php } ?>
                <?php } //endif editable
                ?>
                <?php if ($this->permissionHandler->isVisibleField("recht")) { ?>
                    <h2>Genehmigung</h2>
                    <div class="well">
                        <div class="hide-wrapper">
                            <div class="hide-picker">
                                <?= $this->templater->getDropdownForm(
                                    "recht",
                                    $sel_recht,
                                    6,
                                    "Wähle Rechtsgrundlage...",
                                    "Rechtsgrundlage",
                                    ["required"],
                                    false
                                ) ?>
                            </div>
                            <div class="hide-items">
                                <div id="buero" class="form-group" style="display: none;">
                                    <div class="col-xs-12">Finanzordnung §11: bis zu 150 EUR</div>
                                </div>
                                <div id="fahrt" class="form-group" style="display: none;">
                                    <span class="col-xs-12">StuRa-Beschluss 21/20-08: Fahrtkosten</span>
                                </div>
                                <div id="verbrauch" class="" style="display: none;">
                                    <div class="form-group col-xs-12">StuRa-Beschluss 21/20-07: bis zu 50 EUR</div>
                                </div>
                                <div id="stura" style="display: none;">
                                    <?= $this->templater->getTextForm(
                                        "recht-additional[stura]",
                                        $this->data["recht-additional"],
                                        4,
                                        "",
                                        "StuRa Beschluss",
                                        []
                                    ) ?>
                                    <span class="col-xs-12">Für FSR-Titel ist zusätzlich zum StuRa Beschluss zusätzlich ein FSR Beschluss notwendig.</span>
                                </div>
                                <div id="fsr-ref" style="display: none;">
                                    <?= $this->templater->getTextForm(
                                        "recht-additional[fsr-ref]",
                                        $this->data["recht-additional"],
                                        4,
                                        "",
                                        "StuRa Beschluss (Verkündung)",
                                        []
                                    ) ?>
                                    <span class="col-xs-12">StuRa-Beschluss 21/21-05: für ein internes Projekt bis zu (inkl.) 250 EUR
                                    Muss auf der nächsten StuRa Sitzung vom HV bekannt gemacht werden</span>
                                </div>
                                <div id="kleidung" style="display: none;">
                                    <span class="col-xs-12">StuRa Beschluss 24/04-09 bis zu 25€ pro Person für das teuerste Kleidungsstück (pro Gremium und Legislatur). Für Aktive ist ein Beschluss des Fachschaftsrates / Referates notwendig.</span>
                                </div>
                                <div id="bahn-card" style="display: none">
                                    <span class="col-xs-12">StuRa Beschluss 29/21-W01: Privat gekaufte Bahn-Cards
                                        können nachträglich gefördert werden, falls für den StuRa die
                                        Anschaffung der Bahncard nachweislich günstiger war. Antrag ist bis zu einem
                                        Semester nach Exma möglich.</span>
                                </div>
                                <div id="andere" style="display: none;">
                                    <?= $this->templater->getTextForm(
                                        "recht-additional[andere]",
                                        $this->data["recht-additional"],
                                        5,
                                        "",
                                        "Andere Rechtsgrundlage angeben",
                                        []
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>
                <?php } ?>
                <h2><?= $title ?></h2>
                <div class="well">
                    <?= $this->templater->getTextForm(
                        "name",
                        $this->data["name"],
                        6,
                        "",
                        "Projektname",
                        ["required"]
                    ) ?>
                    <?= $this->templater->getMailForm(
                        "responsible",
                        $this->data["responsible"],
                        6,
                        "vorname.nachname",
                        "Projektverantwortlich (Mail)",
                        ["required", "email"],
                        "@tu-ilmenau.de"
                    ) ?>
                    <div class="clearfix"></div>
                    <?= $this->templater->getDropdownForm(
                        "org",
                        $selectable_gremien,
                        6,
                        "Wähle Gremium ...",
                        "Organisation",
                        ["required"],
                        true
                    ) ?>
                    <?= $this->templater->getDropdownForm(
                        "org-mail",
                        $selectable_mail,
                        6,
                        "Wähle Mailingliste ...",
                        "Organisations-Mail",
                        ["required"],
                        true
                    ) ?>
                    <?= $this->templater->getWikiLinkForm(
                        "protokoll",
                        $this->data["protokoll"],
                        12,
                        "...",
                        "Beschluss (Wiki-Direktlink)",
                        ["required"],
                        "https://wiki.stura.tu-ilmenau.de/protokoll/"
                    ) ?>
                    <?= $this->templater->getDatePickerForm(
                        ["date-start", "date-end"],
                        [$this->data["date-start"], $this->data["date-end"]],
                        12,
                        ["Projekt-Start", "Projekt-Ende"],
                        "Projektzeitraum",
                        ["required"],
                        true,
                        "today"
                    ); ?>
                    <?= $this->templater->getDatePickerForm(
                        "createdat",
                        $this->data["createdat"],
                        12,
                        "",
                        "Projekt erstellt am"
                    ); ?>

                    <div class='clearfix'></div>
                </div>
                <?php $tablePartialEditable = $this->permissionHandler->isEditable(
                    ["posten-name", "posten-bemerkung", "posten-einnahmen", "posten-ausgaben"],
                    "and"
                ); ?>
                <table class="table table-striped summing-table <?= ($tablePartialEditable ? "dynamic-table" : "dynamic-table-readonly") ?>">
                    <thead>
                    <tr>
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th class="">Ein/Ausgabengruppe</th>
                        <th class="">Bemerkung</th>
                        <th class=""><?= $this->permissionHandler->isVisibleField("posten-titel") ? "Titel" : "" ?></th>
                        <th class="col-xs-2">Einnahmen</th>
                        <th class="col-xs-2">Ausgaben</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    $this->data["posten-name"][] = '';

                    foreach ($this->data["posten-name"] as $row_nr => $null) {
                        $new_row = ($row_nr) === count($this->data["posten-name"]);
                        if ($new_row && !$tablePartialEditable)
                            continue;
                        $sel_titel = $selectable_titel;
                        if (isset($this->data['posten-titel'][$row_nr])) {
                            $sel_titel["values"] = $this->data['posten-titel'][$row_nr];
                        }
                        ?>
                        <tr class="<?= $new_row ? "new-table-row" : "dynamic-table-row" ?>">
                            <td class="row-number"> <?= $row_nr ?>.</td>
                            <?php if ($tablePartialEditable) { ?>
                                <td class='delete-row'><a href='' class='delete-row'><i
                                                class='fa fa-fw fa-trash'></i></a></td>
                            <?php } else {
                                echo "<td></td>";
                            } ?>
                            <td><?= $this->templater->getTextForm(
                                    "posten-name[]",
                                    !$new_row ? $this->data["posten-name"][$row_nr] : "",
                                    null,
                                    "Name des Postens",
                                    "",
                                    ["required"]
                                ) ?></td>
                            <td><?= $this->templater->getTextForm(
                                    "posten-bemerkung[]",
                                    !$new_row ? $this->data["posten-bemerkung"][$row_nr] : "",
                                    null,
                                    "optional",
                                    "",
                                    []
                                ) ?></td>
                            <td><?= $this->templater->getDropdownForm(
                                    "posten-titel[]",
                                    $sel_titel,
                                    null,
                                    "HH-Titel",
                                    "",
                                    [],
                                    true
                                ) ?></td>
                            <td><?= $this->templater->getMoneyForm(
                                    "posten-einnahmen[]",
                                    !$new_row ? $this->data["posten-einnahmen"][$row_nr] : 0,
                                    null,
                                    "",
                                    "",
                                    ["required"],
                                    "einnahmen"
                                ) ?></td>
                            <td><?= $this->templater->getMoneyForm(
                                    "posten-ausgaben[]",
                                    !$new_row ? $this->data["posten-ausgaben"][$row_nr] : 0,
                                    null,
                                    "",
                                    "",
                                    ["required"],
                                    "ausgaben"
                                ) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th><!-- Nr.       -->
                        <th></th><!-- Trashbin  -->
                        <th></th><!-- Name      -->
                        <th></th><!-- Bemerkung -->
                        <th></th><!-- Titel -->
                        <th class="dynamic-table-cell cell-has-printSum">
                            <div class="form-group no-form-grp">
                                <div class="input-group input-group-static">
                                    <span class="input-group-addon">Σ</span>
                                    <div class="form-control-static nowrap text-right"
                                         data-printsum="einnahmen">0,00
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
                                         data-printsum="ausgaben">0,00
                                    </div>
                                    <span class="input-group-addon">€</span>
                                </div>
                            </div>
                        </th><!-- ausgaben -->
                    </tr>
                    </tfoot>
                </table>
                <?= $this->templater->getTextareaForm(
                    "beschreibung",
                    $this->data["beschreibung"],
                    12,
                    "In unserem Projekt geht es um ... \nHat einen Nutzen für die Studierendenschaft weil ... \nFindet dort und dort statt...\nusw.",
                    "Projektbeschreibung",
                    ["required", "min-length" => 100],
                    5
                ) ?>

                <?php if ($editable){ ?>
                <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
                <div class="pull-right">
                    <?php

                    //foreach ($proposeNewState as $state){
                    //$isEditable = hasPermission($form, ["state" => $state], "canEdit");
                    //$stateTxt = "Entwurf";
                    //$state = "draft";

                    ?>
                    <a href="javascript:void(true);"
                       class='btn btn-success submit-form <?= !$validateMe ? "no-validate" : "validate" ?>'
                       data-name="state" data-value="<?= htmlspecialchars($this->stateHandler->getActualState()) ?>"
                       id="state-<?= htmlspecialchars($this->stateHandler->getActualState()) ?>">Speichern
                        als <?= htmlspecialchars($this->stateHandler->getFullStateName()) ?></a>
                </div>
            </form>
        <?php } ?>
        </div><!-- main-container -->
        <?php
    }

    private function renderBackButton()
    {
        ?>
        <div class="">
            <a href="./">
                <button class="btn btn-primary"><i class="fa fa-fw fa-arrow-left"></i>&nbsp;Zurück</button>
            </a>
        </div>
        <?php
    }

    private function renderInteractionPanel()
    {
        $url = str_replace("//", "/", URIBASE . "projekt/" . $this->id . "/");
        $nextValidStates = $this->stateHandler->getNextStates(true);
        $disabledStates = array_diff($this->stateHandler->getAllAllowedTransitionableStates(), $nextValidStates);
        ?>
        <div>
            <ul class="nav nav-pills nav-stacked navbar-right navbar-fixed-right">
                <li class="label-info">
                    <?php echo htmlspecialchars($this->stateHandler->getFullStateName()); ?>
                </li>

                <?php if (count($nextValidStates) > 0) { ?>
                    <li><a href="#" data-toggle="modal" data-target="#editStateModal">Status ändern <i
                                    class="fa fa-fw fa-refresh"></i></a></li>
                <?php } ?>
                <?php if (in_array($this->stateHandler->getActualState(), ["ok-by-stura", "done-hv", "done-other"])) { ?>
                    <li><a href="<?= $url ?>auslagen" title="Neue Abrechnung/Rechnung">neue Abrechnung/Rechnung&nbsp;<i
                                    class="fa fa-fw fa-plus" aria-hidden="true"></i></a></li>
                <?php } ?>
                <?php if ($this->permissionHandler->isAnyDataEditable(true) != false) { ?>
                    <li><a href="<?= $url ?>edit" title="Bearbeiten">Bearbeiten&nbsp;<i
                                    class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
                <?php } ?>

                <!--<li><a href="<?php echo ""; ?>" title="Drucken"><i class="fa fa-fw fa-print" aria-hidden="true"></i></a></li> -->
                <!--<li><a href="<?php echo ""; ?>" title="Exportieren"><i class="fa fa-fw fa-download" aria-hidden="true"></i></a></li>-->

                <!-- FIXME LIVE COMMENT ONLY
                <li><a href="<?= $url ?>history" title="Verlauf">Historie <i class="fa fa-fw fa-history"
                                                                             aria-hidden="true"></i></a></li>
                <li><a href="<?= $url ?>delete">Antrag löschen <i class="fa fa-trash" aria-hidden="true"></i></a></li>
                <li><a href="https://wiki.stura.tu-ilmenau.de/leitfaden/finanzenantraege">Hilfe
                        <i class="fa fa-question" aria-hidden="true"></i></a></li> -->
            </ul>
        </div>
        <?php if (count($nextValidStates) > 0) { ?>
        <!-- Modal Zustandsübergang zu anderem State -->
        <form id="stateantrag" role="form" action="<?= URIBASE . "rest/forms/projekt"; ?>"
              method="POST" enctype="multipart/form-data" class="ajax" data-toggle="validator">
            <div class="modal fade" id="editStateModal" tabindex="-1" role="dialog"
                 aria-labelledby="editStateModalLabel">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="editStateModalLabel">Status wechseln</h4>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="changeState">
                            <input type="hidden" name="nonce" value="<?= $GLOBALS['nonce'] ?>">
                            <input type="hidden" name="version" value="<?= $this->data["version"] ?>">
                            <input type="hidden" name="id" value="<?= $this->getID() ?>">
                            <div class="form-group">
                                <label for="newantragstate">Neuer Bearbeitungsstatus</label>
                                <select class="selectpicker form-control" name="newState" size="1"
                                        title="Neuer Bearbeitungsstatus" required="required" id="newantragstate">
                                    <optgroup label="Statuswechsel möglich">
                                        <?php
                                        foreach ($nextValidStates as $state) {
                                            echo "<option value=\"" . htmlspecialchars(
                                                    $state
                                                ) . "\">" . htmlspecialchars(
                                                    $this->stateHandler->getFullStateNameFrom($state)
                                                ) . "</option>" . PHP_EOL;
                                        }
                                        ?>
                                    </optgroup>
                                    <optgroup label="Daten unvollständig">
                                        <?php

                                        foreach ($disabledStates as $state) {
                                            echo "<option disabled>" . $this->stateHandler->getFullStateNameFrom(
                                                    $state
                                                ) . "</option>" . PHP_EOL;
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" name="absenden" class="btn btn-primary pull-right">Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php
    }
    }

    public function getID()
    {
        return $this->id;
    }

    private function render_chat_box()
    { ?>
        <div class='clearfix'></div>
        <div class="col-xs-12 col-md-10" id="projektchat">
            <?php
            $auth = (AUTH_HANDLER);
            /* @var $auth AuthHandler */
            $auth = $auth::getInstance();
            $btns = [];
            $pdate = date_create(substr($this->data['createdat'], 0, 4) . '-01-01 00:00:00');
            $pdate->modify('+1 year');
            $now = date_create();
            //allow chat only 90 days into next year
            if ($now->getTimestamp() - $pdate->getTimestamp() <= 86400 * 90) {
                $btns[] = ['label' => 'Senden', 'color' => 'success', 'type' => '0'];
                if ($auth->hasGroup('ref-finanzen') || $auth->getUsername() == $this->data['username']) {
                    $btns[] = [
                        'label' => 'Private Nachricht',
                        'color' => 'warning',
                        'type' => '-1',
                        'hover-title' => 'Private Nachricht zwischen Ref-Finanzen und dem Projekt-Ersteller'
                    ];
                }
                if ($auth->hasGroup('ref-finanzen')) {
                    $btns[] = ['label' => 'Finanz Nachricht', 'color' => 'primary', 'type' => '3'];
                }
                if ($auth->hasGroup('admin')) {
                    $btns[] = ['label' => 'Admin Nachricht', 'color' => 'danger', 'type' => '2'];
                }
            }
            ChatHandler::renderChatPanel(
                'projekt',
                $this->id,
                $auth->getUserFullName() . " (" . $auth->getUsername() . ")",
                $btns
            ); ?>
        </div>
        <?php
    }

    private function renderProjektSizeGrafic()
    {
        echo '<div class="clearfix"></div>' . PHP_EOL;
        $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
        $ah->render_auslagen_beleg_diagrams('Nice Diagrams');
    }

    private function renderAuslagenList()
    { ?>
        <div class="clearfix"></div>
        <div id='projekt-well' class="well col-xs-12 col-md-10">
            <?php
            $ah = new AuslagenHandler2(['pid' => $this->id, 'action' => 'view']);
            $ah->render_project_auslagen(true); ?>
        </div>
        <?php
    }
}
