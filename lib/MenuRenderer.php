<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 03.02.18
 * Time: 14:43
 */

class MenuRenderer extends Renderer{
    const DEFAULT = "mygremium";
    
    private $pathinfo;
    
    public function __construct($pathinfo = []){
        if (!isset($pathinfo) || empty($pathinfo) || !isset($pathinfo["action"])){
            $pathinfo["action"] = self::DEFAULT;
        }
        $this->pathinfo = $pathinfo;
    }
    
    public function render(){
        $attributes = (AUTH_HANDLER)::getInstance()->getAttributes();
        
        switch ($this->pathinfo["action"]){
            case "mygremium":
            case "allgremium":
                if ($this->pathinfo["action"] === "allgremium")
                    $gremien = $attributes["alle-gremien"];
                else
                    $gremien = $attributes["gremien"];
                
                $gremien = array_filter($gremien, function($val){
                    global $GremiumPrefix;
                    foreach ($GremiumPrefix as $prefix){
                        if (substr($val, 0, strlen($prefix)) === $prefix){
                            return true;
                        }
                    }
                    return false;
                });
                rsort($gremien, SORT_STRING | SORT_FLAG_CASE);
                $gremien[] = "";
                HTMLPageRenderer::registerProfilingBreakpoint("start-rendering");
                //print_r($this->pathinfo["action"]);
            $this->renderProjekte($gremien, $this->pathinfo["action"]);
            break;
            case "search":
                $this->setOverviewTabs($this->pathinfo["action"]);
                $this->renderSearch();
                break;
            case "mystuff":
                $this->setOverviewTabs($this->pathinfo["action"]);
                $this->renderAlert("Hinweis", "Dieser Bereich befindet sich noch im Aufbau", "info");
                break;
            case "mykonto":
                $this->renderMyProfile();
                break;
            case "stura":
                $this->renderStuRaView();
                break;
            case "hv":
                $this->renderHVView();
                break;
            case "kv":
                $this->renderKVView();
                break;
            case "exportBank":
                $this->renderExportBank();
                break;
            case "instruct":
                $this->renderBooking("instruct");
                break;
            case "booking-text":
                $this->setBookingTabs("text", $this->pathinfo["hhp-id"]);
                $this->renderBookingText();
                break;
            case "kasse":
            case "bank":
            case "sparbuch":
                (AUTH_HANDLER)::getInstance()->requireGroup(HIBISCUSGROUP);
                $this->renderKonto($this->pathinfo["action"]);
                break;
            case "save-booking":
                (AUTH_HANDLER)::getInstance()->requireGroup("ref-finanzen-kv");
                $this->saveBooking();
                break;
            //fall through
            case "history":
                $this->renderBookingHistory("history");
                break;
            default:
                ErrorHandler::_errorExit("{$this->pathinfo['action']} kann nicht interpretiert werden");
                break;
        }
    }
    
    public function renderProjekte($gremien, $active){
        //$enwuerfe = DBConnector::getInstance()->dbFetchAll("antrag",["state" => "draft","creator" => (AUTH_HANDLER)::getInstance()->getUserName()]);
        //$projekte = DBConnector::getInstance()->getProjectFromGremium($gremien, "projekt-intern");
        if (empty($gremien)){
            $this->renderAlert("Schade!", $this->makeClickableMails("Leider scheinst du noch kein Gremium zu haben. Solltest du dich ungerecht behandelt fühlen, schreib am besten eine Mail an konsul@tu-ilmenau.de oder an ref-it@tu-ilmenau.de"), "warning");
            return;
        }
        $projekte = DBConnector::getInstance()->dbFetchAll(
            "projekte",
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            [
                "org",
                "projekte.*",
                "ausgaben" => ["projektposten.ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "einnahmen" => ["projektposten.einnahmen", DBConnector::GROUP_SUM_ROUND2],
            ],
            [["org" => ["in", $gremien]], ["org" => ["is", null]]],
            [
                ["table" => "projektposten", "type" => "left", "on" => ["projektposten.projekt_id", "projekte.id"]],
            ],
            ["org" => true],
            ["id"]
        );
        $pids = [];
        array_walk($projekte, function($array, $gremien) use (&$pids){
            array_walk($array, function($res, $key) use (&$pids){
                $pids[] = $res["id"];
            });
        });
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            [
                "projekt_id",  // group idx
                "projekt_id", "auslagen.id", "name_suffix", //auslagen Link
                "zahlung-name", // Empf. Name
                "einnahmen" => ["einnahmen", DBConnector::GROUP_SUM_ROUND2],
                "ausgaben" => ["ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "state"
            ],
            ["projekt_id" => ["IN", $pids]],
            [
                ["table" => "belege", "type" => "LEFT", "on" => ["belege.auslagen_id", "auslagen.id"]],
                ["table" => "beleg_posten", "type" => "LEFT", "on" => ["beleg_posten.beleg_id", "belege.id"]],
            ],
            ["id" => true],
            ["auslagen_id"]
        );
        
        //FIXME: do later :)
        /*if ((AUTH_HANDLER)::getInstance()->hasGroup("ref-finanzen")){
            $extVereine = ["Bergfest.*", ".*KuKo.*", ".*ILSC.*", "Market Team.*", ".*Second Unit Jazz.*", "hsf.*", "hfc.*", "FuLM.*", "KSG.*", "ISWI.*"]; //TODO: From external source
            $ret = DBConnector::getInstance()->getProjectFromGremium($extVereine, "extern-express");
            if ($ret !== false){
                //var_dump($ret);
                $projekte = array_merge($projekte, $ret);
            }
        }*/
        
        //var_dump(end(end($projekte)));
        $this->setOverviewTabs($active);
        ?>

        <div class="panel-group" id="accordion">
            <?php $i = 0;
            if (isset($projekte) && !empty($projekte) && $projekte){
                foreach ($projekte as $gremium => $inhalt){
                    if (count($inhalt) == 0) continue; ?>
                    <div class="panel panel-default">
                        <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                             href="#collapse<?php echo $i; ?>">
                            <h4 class="panel-title">
                                <i class="fa fa-fw fa-togglebox"></i>&nbsp;<?= empty($gremium) ? "Nicht zugeordnete Projekte" : $gremium ?>
                            </h4>
                        </div>
                        <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php $j = 0; ?>
                                <div class="panel-group" id="accordion<?php echo $i; ?>">
                                    <?php foreach ($inhalt as $projekt){
                                        $id = $projekt["id"];
                                        $year = date("y", strtotime($projekt["createdat"])); ?>
                                        <div class="panel panel-default">
                                            <div class="panel-link"><?= generateLinkFromID("IP-$year-$id", "projekt/" . $id) ?>
                                            </div>
                                            <div class="panel-heading collapsed <?= (!isset($auslagen[$id]) || count($auslagen[$id]) === 0) ? "empty" : "" ?>"
                                                 data-toggle="collapse" data-parent="#accordion<?php echo $i ?>"
                                                 href="#collapse<?php echo $i . "-" . $j; ?>">
                                                <h4 class="panel-title">
                                                    <i class="fa fa-togglebox"></i><span
                                                            class="panel-projekt-name"><?= $projekt["name"] ?></span>
                                                    <span class="panel-projekt-money text-muted hidden-xs"><?= number_format($projekt["ausgaben"], 2, ",", ".") ?></span>
                                                    <span class="label label-info project-state-label"><?= ProjektHandler::getStateString($projekt["state"]) ?></span>
                                                </h4>
                                            </div>
                                            <?php if (isset($auslagen[$id]) && count($auslagen[$id]) > 0){ ?>
                                                <div id="collapse<?php echo $i . "-" . $j; ?>"
                                                     class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <?php
                                                        $sum_a_in = 0;
                                                        $sum_a_out = 0;
                                                        $sum_e_in = 0;
                                                        $sum_e_out = 0;
                                                        foreach ($auslagen[$id] as $a){
                                                            if (substr($a['state'], 0, 6) == 'booked' || substr($a['state'], 0, 10) == 'instructed'){
                                                                $sum_a_in += $a['einnahmen'];
                                                                $sum_a_out += $a['ausgaben'];
                                                            }
                                                            if (substr($a['state'], 0, 10) != 'revocation' && substr($a['state'], 0, 5) != 'draft'){
                                                                $sum_e_in += $a['einnahmen'];
                                                                $sum_e_out += $a['ausgaben'];
                                                            }
                                                        }
                                                        
                                                        $this->renderTable(
                                                            ["Name", "Zahlungsempfänger", "Einnahmen", "Ausgaben", "Status"], [$auslagen[$id]], [], [
                                                            [$this, "auslagenLinkEscapeFunction"], // 3 Parameter
                                                            null,  // 1 parameter
                                                            [$this, "moneyEscapeFunction"],
                                                            [$this, "moneyEscapeFunction"],
                                                            function($stateString){
                                                                $text = AuslagenHandler2::getStateString(AuslagenHandler2::state2stateInfo($stateString)['state']);
                                                                return "<div class='label label-info'>$text</div>";
                                                            }

                                                        ], [
                                                                [
                                                                    '',
                                                                    'Eingereicht:',
                                                                    '&Sigma;: ' . number_format($sum_e_in, 2) . '&nbsp€',
                                                                    '&Sigma;: ' . number_format($sum_e_out, 2) . '&nbsp€',
                                                                    '&Delta;: ' . number_format($sum_e_out - $sum_e_in, 2) . '&nbsp€',
                                                                ],
                                                                [
                                                                    '',
                                                                    'Ausgezahlt:',
                                                                    '&Sigma;: ' . number_format($sum_a_in, 2) . '&nbsp€',
                                                                    '&Sigma;: ' . number_format($sum_a_out, 2) . '&nbsp€',
                                                                    '&Delta;: ' . number_format($sum_a_out - $sum_a_in, 2) . '&nbsp€',
                                                                ]
                                                            ]
                                                        ); ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        
                                        <?php $j++;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $i++;
                }
            }else{ ?>
                <h2>Bisher wurden leider noch keine Projekte angelegt. :(</h2>
            <?php } ?>
        </div>
        
        
        <?php
    }
    
    public function setOverviewTabs($active){
        $linkbase = URIBASE . "menu/";
        $tabs = [
            "mygremium" => "<i class='fa fa-fw fa-home'></i> Meine Gremien",
            "allgremium" => "<i class='fa fa-fw fa-globe'></i> Alle Gremien",
            "mystuff" => "<i class='fa fa-fw fa-user-o'></i> Meine Anträge",
            "search" => "<i class='fa fa-fw fa-search'></i> Suche",
        ];
        HTMLPageRenderer::setTabs($tabs, $linkbase, $active);
    }
    
    private function renderSearch(){
        $this->renderAlert("Hinweis", "Dieser Bereich befindet sich noch im Aufbau", "info");
        ?>
        <div class="input-group">
            <div class="input-group-addon"><i class="fa fa-fw fa-search"></i></div>
            <input class="form-control" placeholder="Suche ...">
        </div>
        <?php
    }
    
    public function renderMyProfile(){
        
        $user = DBConnector::getInstance()->getUser();
        if (isset($user["iban"])){
            $iban = $user["iban"];
        }else{
            $iban = "";
        }
        ?>

        <form id="editantrag" role="form" action="<?= $_SERVER["PHP_SELF"]; ?>" method="POST"
              enctype="multipart/form-data" class="ajax">
            <?php $this->renderAlert("Hinweis", "Dieses Formular funktioniert noch nicht :(", "info") ?>
            <div class="panel panel-default">
                <div class="panel-heading">Meine Daten aktualisieren</div>
                <div class="panel-body">
                    <input type="hidden" name="action" value="mykonto.update"/>
                    <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"]; ?>"/>
                    <div class="form-group">
                        <label for="my-iban">Meine IBAN</label>
                        <input class="form-control" type="text" name="my-iban">
                    </div>
                    <div class="form-group">
                        <label for="my-adress">Meine Adresse</label>
                        <textarea class="form-control" type="text" name="my-adress"
                                  placeholder="Straße Nr&#10;98693 Ilmenau"></textarea>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="javascript:void(false);" class='btn btn-success submit-form validate pull-right'
                       data-name="iban"
                       data-value="" disabled="">Speichern</a>
                    <div class="clearfix"></div>
                </div>
            </div>
        </form>
        
        <?php
    }
    
    private function renderStuRaView(){
        $header = ["Projekte", "Organisation", "Projektbeginn", /*"Einnahmen", "Ausgaben"*/];
        
        //TODO: also externe Anträge
        // $groups[] = ["name" => "Externe Anträge", "fields" => ["type" => "extern-express", "state" => "need-stura",]];
        list($header, $internContent, $escapeFunctions) = $this->fetchProjectsWithState("need-stura");
        list(, $internContentHV,) = $this->fetchProjectsWithState("ok-by-hv");
        $groups = [
            "Vom StuRa abzustimmen" => $internContent,
            "zur Verkündung (genehmigt von HV)" => $internContentHV,
        ];
        $this->renderHeadline("Projekte für die nächste StuRa Sitzung");
        $this->renderTable($header, $groups, [], $escapeFunctions);
    }
    
    /**
     * @param $statestring
     *
     * @return array [$header, $dbres, $escapeFunctions]
     */
    private function fetchProjectsWithState($statestring){
        $header = ["Projekt", "Organisation", "Einnahmen", "Ausgaben", "Projektbeginn"];
        $dbres = DBConnector::getInstance()->dbFetchAll(
            "projekte",
            [DBConnector::FETCH_NUMERIC],
            [
                "projekte.id", "createdat", "projekte.name",
                "org",
                "einnahmen" => ["projektposten.einnahmen", DBConnector::GROUP_SUM_ROUND2],
                "ausgaben" => ["projektposten.ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "createdat",
            ],
            ["state" => "$statestring"],
            [["type" => "inner", "table" => "projektposten", "on" => ["projektposten.projekt_id", "projekte.id"]]],
            ["date-start" => true],
            ["projekte.id"]
        );
        $escapeFunctionsIntern = [
            [$this, "projektLinkEscapeFunction"],
            null,
            [$this, "moneyEscapeFunction"],
            [$this, "moneyEscapeFunction"],
            [$this, "date2relstrEscapeFunction"],
        ];
        return [$header, $dbres, $escapeFunctionsIntern];
    }
    
    private function renderHVView(){
    
        //Projekte -------------------------------------------------------------------------------------------------
        list($headerIntern, $internWIP, $escapeFunctionsIntern) = $this->fetchProjectsWithState("wip");
        $groupsIntern["zu prüfende Interne Projekte"] = $internWIP;
    
        //Auslagenerstattungen -------------------------------------------------------------------------------------
        list($headerAuslagen, $auslagenWIP, $escapeFunctionsAuslagen) = $this->fetchAuslagenWithState("wip", "hv");
        $groupsAuslagen["Auslagenerstattungen HV fehlt"] = $auslagenWIP;
        list(, $auslagenWIP,) = $this->fetchAuslagenWithState("wip", "belege");
        $groupsAuslagen["Auslagenerstattungen Belege fehlen"] = $auslagenWIP;
    
        //TODO: Implementierung vom rest
        //$groups[] = ["name" => "Externe Projekte für StuRa Situng vorbereiten", "fields" => ["type" => "extern-express", "state" => "draft"]];
    
        $this->renderHeadline("Von den Haushaltsverantwortlichen zu erledigen");
        $this->renderTable($headerIntern, $groupsIntern, [], $escapeFunctionsIntern);
        $this->renderTable($headerAuslagen, $groupsAuslagen, [], $escapeFunctionsAuslagen);
    }
    
    /**
     * @param $stateString
     * @param $missingColumn string  can be: hv, kv, belege
     *
     * @return array [$header, $auslagen, $escapeFunctionAuslagen]
     */
    private function fetchAuslagenWithState($stateString, $missingColumn){
        $headerAuslagen = ["Projekt", "Auslage", "Organisation", "Einnahmen", "Ausgaben", "zuletzt geändert"];
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_NUMERIC],
            [
                "projekte.id", "createdat", "name", //Projekte Link
                "projekte.id", "auslagen.id", "auslagen.name_suffix", // Auslagen Link
                "projekte.org", // Org
                "einnahmen" => ["beleg_posten.einnahmen", DBConnector::GROUP_SUM_ROUND2],
                "ausgaben" => ["beleg_posten.ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "last_change"  // letzte änderung
            ],
            [
                "auslagen.state" => ["LIKE", "$stateString%"],
                "auslagen.ok-$missingColumn" => "",
            ],
            [
                ["table" => "projekte", "type" => "inner", "on" => ["projekte.id", "auslagen.projekt_id"]],
                ["table" => "belege", "type" => "inner", "on" => ["belege.auslagen_id", "auslagen.id"]],
                ["table" => "beleg_posten", "type" => "inner", "on" => ["belege.id", "beleg_posten.beleg_id"]],
            ],
            ["last_change" => true],
            ["auslagen.id"]
        );
        $escapeFunctionsAuslagen = [
            [$this, "projektLinkEscapeFunction"],
            [$this, "auslagenLinkEscapeFunction"],
            null,
            [$this, "moneyEscapeFunction"],
            [$this, "moneyEscapeFunction"],
            [$this, "date2relstrEscapeFunction"],
        ];
        return [$headerAuslagen, $auslagen, $escapeFunctionsAuslagen];
    }
    
    public function renderKVView(){
        //Auslagenerstattungen
        $headerAuslagen = ["Projekt", "Auslage", "Organisation", "zuletzt geändert"];
        
        list($headerAuslagen, $auslagenWIP, $escapeFunctionsAuslagen) = $this->fetchAuslagenWithState("wip", "kv");
        $groupsAuslagen["Auslagenerstattungen KV fehlt"] = $auslagenWIP;
        list(/**/, $auslagenWIP,/**/) = $this->fetchAuslagenWithState("wip", "belege");
        $groupsAuslagen["Auslagenerstattungen Belege fehlen"] = $auslagenWIP;
        
        //TODO: Implementierung vom rest
        //$groups[] = ["name" => "Externe Projekte für StuRa Situng vorbereiten", "fields" => ["type" => "extern-express", "state" => "draft"]];
        
        $this->renderHeadline("Von den Kassenverantwortlichen zu erledigen");
        $this->renderTable($headerAuslagen, $groupsAuslagen, [], $escapeFunctionsAuslagen);
        
        $this->renderExportBankButton();
    }
    
    private function renderExportBankButton(){
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_ASSOC],
            ["count" => ["id", DBConnector::GROUP_COUNT]],
            ["auslagen.state" => ["LIKE", "ok%"], "auslagen.payed" => ""],
            [],
            [],
            ["auslagen.id"]
        );
        
        ?>
        <form action="<?= URIBASE ?>menu/kv/exportBank">
            <button class="btn btn-primary" <?= end($auslagen)["count"] === 0 ? "disabled" : "" ?>>
                <i class="fa fa-fw fa-money"></i>&nbsp;Exportiere Überweisungen
            </button>
        </form>
        
        <?php
    }
    
    private function renderExportBank(){
        $header = ["Auslage", "Empfänger", "IBAN", "Verwendungszweck", "Auszuzahlen"];
        $auslagen = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_NUMERIC],
            [
                "projekte.id", "auslagen.id", "auslagen.name_suffix", // Auslagenlink
                "auslagen.zahlung-name",
                "auslagen.zahlung-iban",
                "projekte.id", "projekte.createdat", "auslagen.id", "auslagen.zahlung-vwzk", "auslagen.name_suffix", "projekte.name", //verwendungszweck
                "ausgaben" => ["beleg_posten.ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "einnahmen" => ["beleg_posten.einnahmen", DBConnector::GROUP_SUM_ROUND2]
            ],
            ["auslagen.state" => ["LIKE", "ok%"], "auslagen.payed" => ""],
            [
                ["type" => "inner", "table" => "projekte", "on" => ["projekte.id", "auslagen.projekt_id"]],
                ["type" => "inner", "table" => "belege", "on" => ["belege.auslagen_id", "auslagen.id"]],
                ["type" => "inner", "table" => "beleg_posten", "on" => ["beleg_posten.beleg_id", "belege.id"]],
            ],
            [],
            ["auslagen.id"]
        );
        $obj = $this;
        $escapeFunctions = [
            [$this, "auslagenLinkEscapeFunction"],                      // 3 Parameter
            null,                                                       // 1 Parameter
            function($str){
                $p = $str;
                if (!$p) return '';
                $p = Crypto::decrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE . '/secret.php'), URIBASE);
                $p = Crypto::unpad_string($p);
                return $p;
            },                                                       // 1 Parameter
            function($pId, $pCreate, $aId, $vwdzweck, $aName, $pName){  // 6 Parameter - Verwendungszweck
                $year = date("y", strtotime($pCreate));
                $ret = ["IP-$year-$pId-A$aId", $vwdzweck, $aName, $pName];
                $ret = array_filter($ret, function($val){
                    return !empty(trim($val));
                });
                $ret = implode(" - ", $ret);
                if (strlen($ret) > 140){
                    $ret = substr($ret, 0, 140);
                }
                return $ret;
            },
            function($ausgaben, $einnahmen) use ($obj){                 // 2 Parameter
                return $obj->moneyEscapeFunction(floatval($ausgaben) - floatval($einnahmen));
            }
        ];
        if (count($auslagen) > 0){
            $this->renderTable($header, [$auslagen], [], $escapeFunctions);
        }else{
            $this->renderHeadline("Aktuell liegen keine Überweisungen vor.", 2);
        }
    }
    
    private function renderBooking($active){
        
        list($hhps, $hhp_id) = $this->renderHHPSelector();
        $this->setBookingTabs($active, $hhp_id);
        $startDate = $hhps[$hhp_id]["von"];
        $endDate = $hhps[$hhp_id]["bis"];
        
        $bookedZahlungen = DBConnector::getInstance()->dbFetchAll("booking", [DBConnector::FETCH_ONLY_FIRST_COLUMN], ["zahlung_id"], ["canceled" => 0]);
        if (empty($bookedZahlungen)){
            //only remove nothing - if not set there would be an sql error
            $bookedZahlungen = [0];
        }
        if (!isset($endDate) || empty($endDate)){
            $alZahlung = DBConnector::getInstance()->dbFetchAll("konto", [DBConnector::FETCH_ASSOC], [], ["date" => [">=", $startDate], "id" => ["NOT IN", $bookedZahlungen]], [], ["value" => true]);
        }else{
            $alZahlung = DBConnector::getInstance()->dbFetchAll("konto", [DBConnector::FETCH_ASSOC], [], ["date" => ["BETWEEN", [$startDate, $endDate]], "id" => ["NOT IN", $bookedZahlungen]], [], ["value" => true]);
        }
        
        $this->renderKontoRefreshButton();
        
        $alGrund = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_ASSOC],
            [
                "auslagen.*",
                "projekte.name",
                "ausgaben" => ["beleg_posten.ausgaben", DBConnector::GROUP_SUM_ROUND2],
                "einnahmen" => ["beleg_posten.einnahmen", DBConnector::GROUP_SUM_ROUND2]
            ],
            ["auslagen.state" => ["LIKE", "instructed%"]],
            [
                ["type" => "inner", "table" => "projekte", "on" => ["projekte.id", "auslagen.projekt_id"]],
                ["type" => "inner", "table" => "belege", "on" => ["belege.auslagen_id", "auslagen.id"]],
                ["type" => "inner", "table" => "beleg_posten", "on" => ["beleg_posten.beleg_id", "belege.id"]],
            ],
            ["einnahmen" => true],
            ["auslagen.id"]
        );
        array_walk($alGrund, function(&$grund){
            $grund["value"] = floatval($grund["einnahmen"]) - floatval($grund["ausgaben"]);
        });
        
        //sort with reverse order
        usort($alGrund, function($e1, $e2){
            if ($e1["value"] === $e2["value"]){
                return 0;
            }else if ($e1["value"] > $e2["value"]){
                return 1;
            }else{
                return -1;
            }
        });
        ?>
        
        <?php //var_dump($alZahlung[0]);
        ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Zahlungen</th>
                <th class="col-md-1">Beträge</th>
                <th>Belege</th>
            </tr>
            </thead>
            <?php
            $idxZahlung = 0;
            $idxGrund = 0;
            while ($idxZahlung < count($alZahlung) || $idxGrund < count($alGrund)){
    
                echo "<tr>";
                if (isset($alZahlung[$idxZahlung])){
                    if (isset($alGrund[$idxGrund])){
                        $value = min([floatval($alZahlung[$idxZahlung]["value"]), $alGrund[$idxGrund]["value"]]);
                    }else{
                        //var_dump($alZahlung[$idxZahlung]);
                        $value = floatval($alZahlung[$idxZahlung]["value"]);
                    }
                }else{
                    $value = $alGrund[$idxGrund]["value"];
                }
                echo "<td>";
    
                while (isset($alZahlung[$idxZahlung]) && floatval($alZahlung[$idxZahlung]["value"]) === $value){
                    echo "<input type='checkbox' class='booking__form-zahlung' data-value='{$value}' data-id='{$alZahlung[$idxZahlung]["id"]}'>";
    
    
                    //print_r($alZahlung[$idxZahlung]);
                    switch ($alZahlung[$idxZahlung]['konto_id']){
                        case 1: //girokonto
                            $title = "VALUTA: " . $alZahlung[$idxZahlung]["valuta"] . PHP_EOL .
                                "IBAN: " . $alZahlung[$idxZahlung]["empf_iban"] . PHP_EOL .
                                "BIC: " . $alZahlung[$idxZahlung]["empf_bic"];
                            $caption = "Z{$alZahlung[$idxZahlung]['id']} - ";
                            switch ($alZahlung[$idxZahlung]["type"]){
                                case "FOLGELASTSCHRIFT":
                                    $caption .= "LASTSCHRIFT an ";
                                    break;
                                case "ONLINE-UEBERWEISUNG":
                                    $caption .= "ÜBERWEISUNG an ";
                                    break;
                                case "GUTSCHRIFT":
                                    $caption .= "GUTSCHRIFT von ";
                                    break;
                                default: //Buchung, Entgeldabschluss,KARTENZAHLUNG...
                                    $caption .= $alZahlung[$idxZahlung]["type"] . " an ";
                                    break;
                            }
                            $caption .= $alZahlung[$idxZahlung]["empf_name"] . " - " . explode("DATUM", $alZahlung[$idxZahlung]["zweck"])[0];
                            break;
                        case 2: //kasse
                            $caption = "K{$alZahlung[$idxZahlung]['id']} - {$alZahlung[$idxZahlung]["type"]} - {$alZahlung[$idxZahlung]["zweck"]}";
                            $title = "BELEG: {$alZahlung[$idxZahlung]["comment"]}" . PHP_EOL . "DATUM: {$alZahlung[$idxZahlung]["date"]}";
                            break;
                    }
                    
                    $url = str_replace("//", "/", URIBASE . "/zahlung/" . $alZahlung[$idxZahlung]["id"]);
                    echo "<a href='" . htmlspecialchars($url) . "' title='" . htmlspecialchars($title) . "'>" . htmlspecialchars($caption) . "</a>";
                    $idxZahlung++;
                    echo "<br>";
                }
                echo "</td><td class='money'>";
                echo DBConnector::getInstance()->convertDBValueToUserValue($value, "money");
                echo "</td><td>";
                while (isset($alGrund[$idxGrund]) && $alGrund[$idxGrund]["value"] === $value){
                    echo "<input type='checkbox' class='booking__form-beleg' data-value='{$value}' data-id='{$alGrund[$idxGrund]['id']}' >";
    
                    $caption = "A" . $alGrund[$idxGrund]["id"] . " - " . $alGrund[$idxGrund]["name"] . " - " . $alGrund[$idxGrund]["name_suffix"];
                    $url = str_replace("//", "/", URIBASE . "/projekt/{$alGrund[$idxGrund]['projekt_id']}/auslagen/" . $alGrund[$idxGrund]["id"]);
                    echo "<a href=\"" . htmlspecialchars($url) . "\">" . $caption . "</a>";
                    $idxGrund++;
                    echo "<br>";
                }
                echo "</td>";
                echo "</tr>";
            }

            ?>
        </table>
        <!--<form id="instruct-booking" role="form" action="<?= URIBASE ?>rest/booking/cancel" method="POST"
                                      enctype="multipart/form-data" class="ajax">-->
        <form action="<?= URIBASE ?>rest/booking" method="POST" role="form" class="ajax-form">
            <div class="booking__panel-form col-xs-2">
                <h4>ausgewählte Zahlungen</h4>
                <div class="booking__zahlung">
                    <div id="booking__zahlung-not-selected">
                        <span><i>keine ID</i></span>
                        <span class="money">0.00</span>
                    </div>
                    <div class="booking__zahlung-sum text-bold">
                        <span>&Sigma;</span>
                        <span class="money">0.00</span>
                    </div>
                </div>
                <h4>ausgewählte Belege</h4>
                <div class="booking__belege">
                    <div id="booking__belege-not-selected">
                        <span><i>keine ID</i></span>
                        <span class="money">0.00</span>
                    </div>
                    <div class="booking__belege-sum text-bold">
                        <span>&Sigma;</span>
                        <span class="money">0.00</span>
                    </div>
                </div>
                <!--<div>
                    <label>Buchungstext</label>
                    <textarea name="booking-text" rows="3" class="form-control"></textarea>
                </div>-->
                <input type="hidden" name="action" value="instruct-booking">
                <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
                <button type="submit" id="booking__check-button" class="btn btn-primary">Buchung anweisen</button>
            </div>
        </form>
        
        <?php
    }
    
    private function renderHHPSelector(){
        $hhps = DBConnector::getInstance()->dbFetchAll("haushaltsplan", [DBConnector::FETCH_ASSOC, DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY], [], [], [], ["von" => false]);
        if (!isset($hhps) || empty($hhps)){
            ErrorHandler::_errorExit("Konnte keine Haushaltspläne finden");
        }
        if (!isset($this->pathinfo["hhp-id"])){
            foreach (array_reverse($hhps, true) as $id => $hhp){
                if ($hhp["state"] === "final"){
                    $this->pathinfo["hhp-id"] = $id;
                }
            }
        }
        ?>
        <form action="<?= $this->pathinfo["hhp-id"] ?>">
            <div class="input-group col-xs-2 pull-right">
                <select class="selectpicker" id="hhp-id"><?php
                    foreach ($hhps as $id => $hhp){
                        $name = !empty($hhp["bis"]) ? $hhp["von"] . " bis " . $hhp["bis"] : "ab " . $hhp["von"];
                        ?>
                        <option value="<?= $id ?>" <?= $id == $this->pathinfo["hhp-id"] ? "selected" : "" ?>
                                data-subtext="<?= $hhp["state"] ?>"><?= $name ?>
                        </option>
                    <?php } ?>
                </select>
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-primary load-hhp"><i class="fa fa-fw fa-refresh"></i>
                        Aktualisieren
                    </button>
                </div>
            </div>
        </form>
        <?php
        return [$hhps, $this->pathinfo["hhp-id"]];
    }
    
    public function setBookingTabs($active, $active_hhp_id){
        $linkbase = URIBASE . "booking/$active_hhp_id/";
        $tabs = [
            "instruct" => "<i class='fa fa-fw fa-legal'></i> Anweisen",
            "text" => "<i class='fa fa-fw fa-file-text-o'></i> Durchführen",
            "history" => "<i class='fa fa-fw fa-history'></i> Historie",
        ];
        HTMLPageRenderer::setTabs($tabs, $linkbase, $active);
    }
    
    private function renderKontoRefreshButton(){ ?>
        <form action="<?= URIBASE ?>rest/hibiscus" method="POST" role="form" class="form-inline ajax d-inline-block">
            <button type="submit" name="absenden" class="btn btn-primary">
                <i class="fa fa-fw fa-refresh"></i> neue Kontoauszüge abrufen
            </button>
            <input type="hidden" name="action" value="hibiscus">
            <input type="hidden" name="nonce" value="<?= $GLOBALS["nonce"] ?>">
        </form>
        <?php
    }
    
    private function renderBookingText(){
        
        $allInstructions = DBConnector::getInstance()->dbFetchAll(
            "booking_instruction",
            [DBConnector::FETCH_GROUPED],
            ["id", "zahlung", "beleg"],
            [],
            [],
            ["id" => true]
        );
        $result = [];
        foreach ($allInstructions as $vorgang_id => $instruction){
            $zahlungen = [];
            $belege = [];
            foreach ($instruction as $row){
                $zahlungen[] = $row["zahlung"];
                $belege[] = $row["beleg"];
            }
            
            //titel_id, kostenstelle, zahlung_id, beleg_id, user_id, comment, value
            $zahlungenDB = DBConnector::getInstance()->dbFetchAll("konto", [DBConnector::FETCH_ASSOC], [], ["id" => ["IN", $zahlungen]]);
            $belegeDB = DBConnector::getInstance()->dbFetchAll(
                "auslagen",
                [DBConnector::FETCH_ASSOC],
                [
                    "auslagen.projekt_id",
                    "auslagen_id" => "auslagen.id",
                    "belege_id" => "belege.id",
                    "titel_name",
                    "projekt_name" => "projekte.name",
                    "auslagen_name" => "name_suffix",
                    "titel_nr",
                    "posten_id" => "beleg_posten.id",
                    "posten_short" => "beleg_posten.short",
                    "beleg_posten.einnahmen",
                    "beleg_posten.ausgaben",
                ],
                ["auslagen.id" => ["IN", $belege]],
                [
                    ["table" => "projekte", "type" => "inner", "on" => ["projekte.id", "auslagen.projekt_id"]],
                    ["table" => "belege", "type" => "inner", "on" => ["belege.auslagen_id", "auslagen.id"]],
                    ["table" => "beleg_posten", "type" => "inner", "on" => ["beleg_posten.beleg_id", "belege.id"]],
                    ["table" => "projektposten", "type" => "inner", "on" =>
                        [
                            ["projektposten.id", "beleg_posten.projekt_posten_id"],
                            ["auslagen.projekt_id", "projektposten.projekt_id"]
                        ]
                    ],
                    ["table" => "haushaltstitel", "type" => "left", "on" => ["projektposten.titel_id", "haushaltstitel.id"]],
                ]
            );
            $title_empty = false;
            $sum_zahlung = 0;
            $sum_beleg = 0;
            $res = [];
            foreach ($zahlungenDB as $zahlung){
                $sum_zahlung += $zahlung["value"];
                $rowZahlung = [
                    $zahlung["id"],
                    $zahlung["value"],
                ];
                foreach ($belegeDB as $beleg){
                    $sum_beleg += floatval($beleg["einnahmen"]);
                    $sum_beleg -= floatval($beleg["ausgaben"]);
                    $rowBeleg = [
                        $beleg["projekt_id"],
                        $beleg["auslagen_id"],
                        " ", // show no name in auslagenLinkEscapeFunction
                        $beleg["posten_short"],
                        $beleg["titel_nr"],
                        $beleg["titel_name"],
                    ];
                    if (empty($beleg["titel_nr"])){
                        $title_empty = true;
                    }
                    if (floatval($beleg["einnahmen"]) != 0){
                        $rowBeleg[] = $beleg["einnahmen"];
                    }
                    if (floatval($beleg["ausgaben"]) != 0){
                        $rowBeleg[] = -$beleg["ausgaben"];
                    }
                    $rowBeleg[] = $beleg["posten_id"];
                    $rowBeleg[] = $beleg["projekt_name"];
                    $rowBeleg[] = $beleg["auslagen_name"];
                    
                    $res[] = array_merge($rowZahlung, $rowBeleg);
                }
            }
            
            /*if (abs($sum_zahlung - $sum_beleg) >= 0.01){
                ErrorHandler::_errorExit("Summe Zahlung ($sum_zahlung) und Summe Beleg ($sum_beleg) passen nicht zusammen!");
            }*/
            $result["Angewiesender Vorgang " . $vorgang_id] = $res;
            ?>
            
            <?php
        }
        $header = [
            "Zahlung", "Zahlung-Betrag", "Auslage", "Beleg-Posten", "Titel Nr", "Titel", "Posten-Betrag", "Buchungstext",
        ];
        ?>
        <form method="POST" action="<?= URIBASE ?>rest/booking/save" class="ajax-form">
            <?php
            //var_dump($res);
            $obj = $this;
            $title_empty = false;
            $this->renderTable($header, $result, [], [
                function($zahlung_id) use ($obj){
                    return $obj->defaultEscapeFunction($zahlung_id);
                },
                [$this, "moneyEscapeFunction"],
                [$this, "auslagenLinkEscapeFunction"],
                null,
                function($titelnr) use (&$title_empty){
                    if (trim($titelnr) === ""){
                        $title_empty = true;
                    }
                    return str_replace(" ", "&nbsp;", trim($titelnr));
                },
                null,
                [$this, "moneyEscapeFunction"],
                function($posten_id, $p_name, $a_name){
                    if (empty($a_name)){
                        return "<textarea required name='text[$posten_id]' placeholder='$p_name' class='warning-empty form-control'></textarea>";
                    }else{
                        return "<textarea required name='text[$posten_id]' class='warning-empty has-feedback form-control'>$p_name - $a_name</textarea>";
                    }
                }
            ]);
            
            ?>
            <button class="btn btn-primary pull-right" <?= $title_empty ? "disabled title='Ein Titel wurde im Projekt nicht gesetzt. Buchung nicht möglich!'" : "" ?>>
                Buchung durchführen
            </button>
        </form>
        <?php
    }
    
    public function renderKonto($activeTab){
        
        list($hhps, $selected_id) = $this->renderHHPSelector();
        $startDate = $hhps[$selected_id]["von"];
        $endDate = $hhps[$selected_id]["bis"];
        if (is_null($endDate) || empty($endDate)){
            $alZahlung = DBConnector::getInstance()->dbFetchAll(
                "konto",
                [DBConnector::FETCH_ASSOC],
                [],
                ["valuta" => [">", $startDate]],
                [],
                ["id" => false]
            );
        }else{
            $alZahlung = DBConnector::getInstance()->dbFetchAll(
                "konto",
                [DBConnector::FETCH_ASSOC],
                [],
                ["valuta" => ["BETWEEN", [$startDate, $endDate]]],
                [],
                ["id" => false]
            );
        }
        
        $this->setKontoTabs($activeTab, $selected_id);
        
        switch ($activeTab){
            case "bank":
                $this->renderKontoRefreshButton(); ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Empfänger</th>
                        <th class="visible-md visible-lg">Verwendungszweck</th>
                        <th class="visible-md visible-lg">IBAN</th>
                        <th class="money">Betrag</th>
                        <th class="money">Saldo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($alZahlung as $zahlung){ ?>
                        <tr title="<?= htmlspecialchars($zahlung["type"] . " - IBAN: " . $zahlung["empf_iban"] . " - BIC: " . $zahlung["empf_bic"]
                            . PHP_EOL . $zahlung["zweck"]) ?>">
                            <td><?= htmlspecialchars($zahlung["id"]) ?></td>
                            <td><?= htmlspecialchars($zahlung["valuta"]) ?></td>
                            <td><?= htmlspecialchars($zahlung["empf_name"]) ?></td>
                            <td class="visible-md visible-lg"><?= $this->makeProjektsClickable(explode("DATUM", $zahlung["zweck"])[0]) ?></td>
                            <td class="visible-md visible-lg"><?= htmlspecialchars($zahlung["empf_iban"]) ?></td>
                            <td class="money"><?= DBConnector::getInstance()->convertDBValueToUserValue($zahlung["value"], "money") ?></td>
                            <td class="money"><?= DBConnector::getInstance()->convertDBValueToUserValue($zahlung["saldo"], "money") ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php
                break;
            case "kasse":
                $rows = DBConnector::getInstance()->dbFetchAll("konto", [DBConnector::FETCH_ASSOC], ["*"], ["konto_id" => 2], [], ["id" => true]);
                ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th class="col-xs-2">Lfd</th>
                        <th>Datum</th>
                        <th class="col-xs-3">Beschreibung</th>
                        <th class="col-xs-2">Betrag</th>
                        <th class="col-xs-2">neues Saldo</th>
                        <th class="col-xs-2">Erstattung / Aktion</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $lastId = null;
                    foreach ($rows as $row){
                        $lastId = $row["id"];
                        echo "<tr>";
                        echo "<td>{$row["id"]}</td>";
                        echo "<td>{$row["date"]}</td>";
                        echo "<td>{$row["type"]} - {$row["zweck"]}</td>";
                        echo "<td class='money'>" . DBConnector::getInstance()->convertDBValueToUserValue($row["value"], "money") . "</td>";
                        echo "<td class='money'>" . DBConnector::getInstance()->convertDBValueToUserValue($row["saldo"], "money") . "</td>";
                        echo "<td>FIXME</td>";
                        echo "</tr>";
                    } ?>
                    <form id="new-entry" role="form" action="<?= URIBASE ?>rest/kasse/new" method="POST"
                          enctype="multipart/form-data" class="ajax">
                        <tr>
                            <td><input type="number" class="form-control" name="new-nr"
                                       value="<?= isset($lastId) ? $lastId + 1 : 1 ?>" min="1">
                            </td>
                            <td><input type="date" class="form-control" name="new-date"
                                       value="<?= date("Y-m-d") ?>"></td>
                            <td><input type="text" class="form-control" name="new-desc"
                                       placeholder="Text aus Kassenbuch"></td>
                            <td><input type="number" class="form-control" name="new-money" value="0" step="0.01">
                            </td>
                            <td><input type="number" class="form-control" name="new-saldo" value="0" step="0.01">
                            </td>
                            <td>
                                <button type="submit" class="btn btn-success no-validate">Speichern</button>
                            </td>
                        </tr>
                    </form>
                    </tbody>
                </table>
                
                <?php
                break;
            case "sparbuch":
                $this->renderAlert("Hinweis", "Diese Funktion ist noch nicht implementiert.", "info");
                break;
            default:
                ErrorHandler::_errorExit("{$this->pathinfo['action']} kann nicht interpretiert werden - something went horrible wrong!");
                break;
        }
    }
    
    public function setKontoTabs($active, $selected_hhp_id){
        $linkbase = URIBASE . "konto/$selected_hhp_id/";
        $tabs = [
            "bank" => "<i class='fa fa-fw fa-credit-card'></i> Bank",
            "kasse" => "<i class='fa fa-fw fa-money'></i> Kasse",
            "sparbuch" => "<i class='fa fa-fw fa-bank'></i> Sparbuch",
        ];
        HTMLPageRenderer::setTabs($tabs, $linkbase, $active);
    }
    
    private function makeProjektsClickable($text){
        $matches = [];
        $text = htmlspecialchars($text);
        preg_match("/IP-[0-9]{2,4}-[0-9]+-A[0-9]+/", $text, $matches);
        foreach ($matches as $match){
            $array = explode("-", $match);
            $auslagen_id = substr(array_pop($array), 1);
            $projekt_id = array_pop($array);
            $text = str_replace($match, "<a target='_blank' href='" . URIBASE . "projekt/$projekt_id/auslagen/$auslagen_id'><i class='fa fa-fw fa-chain'></i>$match</a>", $text);
        }
        return $text;
    }
    
    private function saveBooking(){
    
        $zahlungen = $_REQUEST["zahlung"];
        $belege = $_REQUEST["beleg"];
        $text = $_REQUEST["text"];
    
        $maxBookingId = DBConnector::getInstance()->dbFetchAll("booking", [DBConnector::FETCH_ASSOC], ["id" => ["id", DBConnector::GROUP_MAX]]);
        
        if (is_array($maxBookingId) && !empty($maxBookingId)){
            $maxBookingId = intval($maxBookingId[0]["id"]);
        }else{
            $maxBookingId = 1;
        }
        //check if allready booked
        $bookingDBbelege = DBConnector::getInstance()->dbFetchAll(
            "booking",
            [DBConnector::FETCH_ASSOC],
            ["belegposten_id"],
            ["canceled" => 0, "belege.auslagen_id" => ["IN", $belege],],
            [
                ["table" => "beleg_posten", "type" => "inner", "on" => ["beleg_posten.id", "booking.belegposten_id"]],
                ["table" => "belege", "type" => "inner", "on" => ["belege.id", "beleg_posten.beleg_id"]],
            ]
        );
        $bookingDBzahlung = DBConnector::getInstance()->dbFetchAll(
            "booking",
            [DBConnector::FETCH_ASSOC],
            ["zahlung_id"],
            ["canceled" => 0, "zahlung_id" => ["IN", $zahlungen],]
        );
    
        if (count($bookingDBbelege) + count($bookingDBzahlung) > 0){
            ErrorHandler::_renderErrorPage(["msg" => "Beleg oder Zahlung bereits verknüpft - " . print_r(array_merge($bookingDBzahlung, $bookingDBbelege), true), "code" => "500 Interner Fehler"]);
        }
    
        $zahlungenDB = DBConnector::getInstance()->dbFetchAll("konto", [DBConnector::FETCH_ASSOC], ["id", "value"], ["id" => ["IN", $zahlungen]]);
        $belegeDB = DBConnector::getInstance()->dbFetchAll("auslagen", [DBConnector::FETCH_ASSOC], [], ["id" => ["IN", $belege]]);
        $belegPostenDB = DBConnector::getInstance()->dbFetchAll(
            "auslagen",
            [DBConnector::FETCH_ASSOC],
            [
                "auslagen.id",
                "auslagen.projekt_id",
                "titel_id",
                "titel_type" => "haushaltsgruppen.type",
                "posten_id" => "beleg_posten.id",
                "beleg_posten.einnahmen",
                "beleg_posten.ausgaben",
                "etag",
            
            ],
            ["auslagen.id" => ["IN", $belege]],
            [
                ["table" => "belege", "type" => "inner", "on" => ["belege.auslagen_id", "auslagen.id"]],
                ["table" => "beleg_posten", "type" => "inner", "on" => ["beleg_posten.beleg_id", "belege.id"]],
                ["table" => "projektposten", "type" => "inner", "on" =>
                    [
                        ["projektposten.id", "beleg_posten.projekt_posten_id"],
                        ["auslagen.projekt_id", "projektposten.projekt_id"]
                    ],
                ],
                ["table" => "haushaltstitel", "type" => "inner", "on" => ["projektposten.titel_id", "haushaltstitel.id"]],
                ["table" => "haushaltsgruppen", "type" => "inner", "on" => ["haushaltsgruppen.id", "haushaltstitel.hhpgruppen_id"]],
            ]
        );
        //start write action
        DBConnector::getInstance()->dbBegin();
        //check if transferable to new States (payed => booked)
        $stateChangeOk = true;
        foreach ($belegeDB as $beleg){
            $ah = new AuslagenHandler2(["aid" => $beleg["id"], "pid" => $beleg["projekt_id"], "action" => "none"]);
            var_export($stateChangeOk);
            $stateChangeOk = $stateChangeOk && ($ah->state_change("booked", $beleg["etag"]) === true);
            var_export($stateChangeOk);
        }
        if ($stateChangeOk !== true){
            DBConnector::getInstance()->dbRollBack();
            ErrorHandler::_renderErrorPage(["msg" => "Beleg kann nicht in Status 'gebucht' überführt werden - evtl. wurde noch nicht auf gezahlt gesetzt? ", "code" => "500 Interner Fehler"]);
        }
        
        $zahlung_sum = 0;
        foreach ($zahlungenDB as $zahlung){
    
            $zahlung_sum += $zahlung["value"];
            $belege_sum = 0;
            foreach ($belegPostenDB as $beleg){
                $value = 0;
                if (floatval($beleg["einnahmen"]) != 0){
                    $value = $beleg["einnahmen"];
                }
                if (floatval($beleg["ausgaben"]) != 0){
                    $value = -$beleg["ausgaben"];
                }
                //sum without changed sign
                $belege_sum += $value;
                //verändere Vorzeichen für ausgabe Titel
                if (intval($beleg["titel_type"]) !== 0){
                    //ausgabetitel
                    $value = -$value;
                }
                DBConnector::getInstance()->dbInsert("booking", [
                    "id" => ++$maxBookingId,
                    "titel_id" => $beleg["titel_id"],
                    "zahlung_id" => $zahlung["id"],
                    "belegposten_id" => $beleg["posten_id"],
                    "user_id" => DBConnector::getInstance()->getUser()["id"],
                    "comment" => $text[$beleg["posten_id"]],
                    "value" => $value,
                    "kostenstelle" => 0,
                ]);
            }
        }
        
        //check if user input was correct
        $diff = abs($zahlung_sum - $belege_sum);
        if ($diff >= 0.01){
            DBConnector::getInstance()->dbRollBack();
            ErrorHandler::_errorExit("Falsche Daten wurden übvertragen: Differenz der Posten = $diff (" . var_export($diff >= 0.01, true) . ")");
        }else{
            DBConnector::getInstance()->dbCommit();
            header('Location: ./booking-history');
        }
    }
    
    public function renderBookingHistory($active){
        list($hhps, $hhp_id) = $this->renderHHPSelector();
        $this->setBookingTabs($active, $hhp_id);
        $ret = DBConnector::getInstance()->dbFetchAll("booking",
            [DBConnector::FETCH_ASSOC],
            ["booking.id", "titel_nr", "zahlung_id", "booking.value", "canceled", "beleg_posten.short", "auslagen_id", "projekt_id", "timestamp", "username", "fullname", "kostenstelle", "comment"],
            ["hhp_id" => $hhp_id],
            [
                ["type" => "left", "table" => "user", "on" => ["booking.user_id", "user.id"]],
                ["type" => "left", "table" => "haushaltstitel", "on" => ["booking.titel_id", "haushaltstitel.id"]],
                ["type" => "left", "table" => "haushaltsgruppen", "on" => ["haushaltsgruppen.id", "haushaltstitel.hhpgruppen_id"]],
                ["type" => "left", "table" => "beleg_posten", "on" => ["booking.belegposten_id", "beleg_posten.id"]],
                ["type" => "left", "table" => "belege", "on" => ["belege.id", "beleg_posten.beleg_id"]],
                ["type" => "left", "table" => "auslagen", "on" => ["belege.auslagen_id", "auslagen.id"]],
            ],
            ["timestamp" => true, "id" => true]
        );
        
        if (!empty($ret)){
            //var_dump(reset($ret));
            $this->renderHeadline("Buchungshistorie");
            ?>
            <table class="table" align="right">
                <thead>
                <tr>
                    <th>B-Nr</th>
                    <th class="col-xs-1">Betrag (EUR)</th>
                    <th class="col-xs-1">Titel</th>
                    <th>Beleg</th>
                    <th>Buchungs-Datum</th>
                    <th>Zahlung</th>
                    <th>Stornieren</th>
                    <th>Kommentar</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($ret as $lfdNr => $row){
                    $userStr = isset($row["fullname"]) ? $row["fullname"] . " (" . $row["username"] . ")" : $row["username"];
                    $projektId = $row["projekt_id"];
                    $auslagenId = $row["auslagen_id"]
                    ?>
                    <tr class=" <?= $row["canceled"] != 0 ? "booking__canceled-row" : "" ?>">

                        <td><a class="link-anchor" name="<?= $row["id"] ?>"></a><?= $row["id"]/*$lfdNr + 1*/ ?></td>

                        <td class="money <?= TextStyle::BOLD ?>"><?= DBConnector::getInstance()->convertDBValueToUserValue($row['value'], "money") ?></td>

                        <td class="<?= TextStyle::PRIMARY . " " . TextStyle::BOLD ?>"><?= str_replace(" ", "&nbsp;", trim(htmlspecialchars($row['titel_nr']))) ?></td>

                        <td><?= generateLinkFromID("A$auslagenId&nbsp;-&nbsp;" . $row['short'], "projekt/$projektId/auslagen/$auslagenId", TextStyle::BLACK) ?></td>

                        <td value="<?= $row['timestamp'] ?>">
                            <?= date("d.m.Y", strtotime($row['timestamp'])) ?>&nbsp;<!--
                        --><i title="<?= $row['timestamp'] . " von " . $userStr ?>"
                              class="fa fa-fw fa-question-circle" aria-hidden="true"></i>
                        </td>

                        <td><?= generateLinkFromID($row['zahlung_id'], "", TextStyle::BLACK) ?></td>
                        <?php if ($row["canceled"] == 0){ ?>
                            <td>
                                <form id="cancel" role="form" action="<?= URIBASE ?>rest/booking/cancel" method="POST"
                                      enctype="multipart/form-data" class="ajax">
                                    <input type="hidden" name="action" value="cancel-booking"/>
                                    <input type="hidden" name="nonce" value="<?= $GLOBALS['nonce']; ?>"/>
                                    <input type="hidden" name="booking.id" value="<?= $row["id"]; ?>"/>
                                    <input type="hidden" name="hhp.id" value="<?= $hhp_id; ?>"/>

                                    <a href="javascript:void(false);" class='submit-form <?= TextStyle::DANGER ?>'>
                                        <i class='fa fa-fw fa-ban'></i>&nbsp;Stornieren
                                    </a>
                                </form>
                            </td>
                        <?php }else{ ?>
                            <td>Durch <a href='#<?= $row['canceled'] ?>'>B-Nr: <?= $row['canceled'] ?></a></td>
                        <?php } ?>
                        <td class="col-xs-4 <?= TextStyle::SECONDARY ?>"><?= htmlspecialchars($row['comment']) ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php
        }else{
            $this->renderHeadline("bisher keine Buchungen in diesem HH-Jahr vorhanden.", 2);
        }
    }
}
