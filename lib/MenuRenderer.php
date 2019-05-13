<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 03.02.18
 * Time: 14:43
 */

class MenuRenderer
	extends Renderer{
	const DEFAULT = "mygremium";
	
	private $pathinfo;
	
	public function __construct($pathinfo = []){
		if (!isset($pathinfo) || empty($pathinfo) || !isset($pathinfo["action"])){
			$pathinfo["action"] = self::DEFAULT;
		}
		$this->pathinfo = $pathinfo;
	}
	
	public function render(){
		
		
		switch ($this->pathinfo["action"]){
			case "mygremium":
			case "allgremium":
				HTMLPageRenderer::registerProfilingBreakpoint("start-rendering");
				$this->renderProjekte($this->pathinfo["action"]);
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
			default:
				ErrorHandler::_errorExit("{$this->pathinfo['action']} kann nicht interpretiert werden");
			break;
		}
	}
	
	public function renderProjekte($active){
		$attributes = (AUTH_HANDLER)::getInstance()->getAttributes();
		$gremien = $attributes["gremien"];
		$gremien = array_filter(
			$gremien,
			function($val){
				global $GremiumPrefix;
				foreach ($GremiumPrefix as $prefix){
					if (substr($val, 0, strlen($prefix)) === $prefix){
						return true;
					}
				}
				return false;
			}
		);
		rsort($gremien, SORT_STRING | SORT_FLAG_CASE);
		switch ($active){
			case "allgremium":
				$where = [];
			break;
			case "mygremium":
				if (empty($gremien)){
					$this->renderAlert(
						"Schade!",
						$this->makeClickableMails(
							"Leider scheinst du noch kein Gremium zu haben. Solltest du dich ungerecht behandelt fühlen, schreib am besten eine Mail an konsul@tu-ilmenau.de oder an ref-it@tu-ilmenau.de"
						),
						"warning"
					);
					return;
				}
				$where = [["org" => ["in", $gremien]], ["org" => ["is", null]], ["org" => ""]];
			break;
			default:
				ErrorHandler::_errorExit("Not known active Tab: " . $active);
			break;
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
			$where,
			[
				["table" => "projektposten", "type" => "left", "on" => ["projektposten.projekt_id", "projekte.id"]],
			],
			["org" => true],
			["projekte.id"]
		);
		$pids = [];
		array_walk(
			$projekte,
			function($array, $gremien) use (&$pids){
				array_walk(
					$array,
					function($res, $key) use (&$pids){
						$pids[] = $res["id"];
					}
				);
			}
		);
        if(!empty($pids)){
            $auslagen = DBConnector::getInstance()->dbFetchAll(
                "auslagen",
                [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
                [
                    "projekt_id",  // group idx
                    "projekt_id",
                    "auslagen.id",
                    "name_suffix", //auslagen Link
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
                ["auslagen.id" => true],
                ["auslagen_id"]
            );
        }

        //var_dump(end(end($projekte)));
		$this->setOverviewTabs($active);
		?>

        <div class="panel-group" id="accordion">
			<?php $i = 0;
			if (isset($projekte) && !empty($projekte) && $projekte){
				foreach ($projekte as $gremium => $inhalt){
					if (count($inhalt) == 0)
						continue; ?>
                    <div class="panel panel-default">
                        <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                             href="#collapse<?php echo $i; ?>">
                            <h4 class="panel-title">
								<?php
								$titel = empty($gremium) ? "Nicht zugeordnete Projekte" :
									(in_array($gremium, $attributes["alle-gremien"]) ? "" : "[INAKTIV] ") . $gremium;
								?>
                                <i class="fa fa-fw fa-togglebox"></i>&nbsp;<?= $titel ?>
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
                                            <div class="panel-link"><?= generateLinkFromID(
													"IP-$year-$id",
													"projekt/" . $id
												) ?>
                                            </div>
                                            <div class="panel-heading collapsed <?= (!isset($auslagen[$id]) || count(
													$auslagen[$id]
												) === 0) ? "empty" : "" ?>"
                                                 data-toggle="collapse" data-parent="#accordion<?php echo $i ?>"
                                                 href="#collapse<?php echo $i . "-" . $j; ?>">
                                                <h4 class="panel-title">
                                                    <i class="fa fa-togglebox"></i><span
                                                            class="panel-projekt-name"><?= $projekt["name"] ?></span>
                                                    <span class="panel-projekt-money text-muted hidden-xs ">
                                                        <?= number_format($projekt["ausgaben"], 2, ",", ".") ?>
                                                    </span>
                                                    <span class="label label-info project-state-label"><?=
														ProjektHandler::getStateStringFromName($projekt["state"]) ?>
                                                    </span>
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
															if (substr(
																	$a['state'],
																	0,
																	6
																) == 'booked' || substr(
																	$a['state'],
																	0,
																	10
																) == 'instructed'){
																$sum_a_in += $a['einnahmen'];
																$sum_a_out += $a['ausgaben'];
															}
															if (substr(
																	$a['state'],
																	0,
																	10
																) != 'revocation' && substr(
																	$a['state'],
																	0,
																	5
																) != 'draft'){
																$sum_e_in += $a['einnahmen'];
																$sum_e_out += $a['ausgaben'];
															}
														}
														
														$this->renderTable(
															[
																"Name",
																"Zahlungsempfänger",
																"Einnahmen",
																"Ausgaben",
																"Status"
															],
															[$auslagen[$id]],
															[],
															[
																[$this, "auslagenLinkEscapeFunction"],
																// 3 Parameter
																null,
																// 1 parameter
																[$this, "moneyEscapeFunction"],
																[$this, "moneyEscapeFunction"],
																function($stateString){
																	$text = AuslagenHandler2::getStateStringFromName(
																		AuslagenHandler2::state2stateInfo(
																			$stateString
																		)['state']
																	);
																	return "<div class='label label-info'>$text</div>";
																}
															
															],
															[
																[
																	'',
																	'Eingereicht:',
																	'&Sigma;: ' . number_format(
																		$sum_e_in,
																		2
																	) . '&nbsp;€',
																	'&Sigma;: ' . number_format(
																		$sum_e_out,
																		2
																	) . '&nbsp;€',
																	'&Delta;: ' . number_format(
																		$sum_e_out - $sum_e_in,
																		2
																	) . '&nbsp;€',
																],
																[
																	'',
																	'Ausgezahlt:',
																	'&Sigma;: ' . number_format(
																		$sum_a_in,
																		2
																	) . '&nbsp€',
																	'&Sigma;: ' . number_format(
																		$sum_a_out,
																		2
																	) . '&nbsp€',
																	'&Delta;: ' . number_format(
																		$sum_a_out - $sum_a_in,
																		2
																	) . '&nbsp€',
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
					<?php $this->renderNonce(); ?>
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
				"projekte.id",
				"createdat",
				"projekte.name",
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
				"projekte.id",
				"createdat",
				"name", //Projekte Link
				"projekte.id",
				"auslagen.id",
				"auslagen.name_suffix", // Auslagen Link
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
				"projekte.id",
				"auslagen.id",
				"auslagen.name_suffix", // Auslagenlink
				"auslagen.zahlung-name",
				"auslagen.zahlung-iban",
				"projekte.id",
				"projekte.createdat",
				"auslagen.id",
				"auslagen.zahlung-vwzk",
				"auslagen.name_suffix",
				"projekte.name", //verwendungszweck
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
				if (!$p)
					return '';
				$p = Crypto::decrypt_by_key_pw($p, Crypto::get_key_from_file(SYSBASE . '/secret.php'), URIBASE);
				$p = Crypto::unpad_string($p);
				return $p;
			},                                                       // 1 Parameter
			function($pId, $pCreate, $aId, $vwdzweck, $aName, $pName){  // 6 Parameter - Verwendungszweck
				$year = date("y", strtotime($pCreate));
				$ret = ["IP-$year-$pId-A$aId", $vwdzweck, $aName, $pName];
				$ret = array_filter(
					$ret,
					function($val){
						return !empty(trim($val));
					}
				);
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
}

