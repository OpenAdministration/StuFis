<?php

namespace booking;

use framework\auth\AuthHandler;
use framework\CSVBuilder;
use framework\DBConnector;
use framework\render\ErrorHandler;
use framework\render\Renderer;

class HHPHandler
	extends Renderer{
	
	protected $routeInfo;
	private $hhps;
	private $stateStrings;
	
	
	public function __construct($routeInfo){
		$this->routeInfo = $routeInfo;
		$this->stateStrings = [
			"draft" => "Entwurf",
			"final" => "Rechtskräftig",
		];
        $this->hhps = DBConnector::getInstance()->dbFetchAll(
            "haushaltsplan",
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ["id", "id", "haushaltsplan.*"],
            [],
            [],
            ["von" => true]
        );
	}
	
	public function render() : void
    {

		if (isset($this->routeInfo["hhp-id"])){
			$hhp_id = $this->routeInfo["hhp-id"];
			if (!isset($this->hhps[$hhp_id])){
                ErrorHandler::handleError(500,"Haushaltsplan HP-$hhp_id ist nicht bekannt.");
				return;
			}
		}
		switch ($this->routeInfo["action"]){
			case "pick-hhp":
				$this->renderHHPPicker();
			break;
			case "view-hhp":
				$this->renderHaushaltsplan();
			break;
			case "export-csv":
				$this->exportCSV();
			break;
			case "view-titel":
				$this->renderTitelDetails();
			break;
            case "import":
                $this->renderImporter();
                break;
            case "preview":
                $this->renderImportPreview();
                break;
            case "save":
                $this->saveNewHHP();
                break;
			default:
                ErrorHandler::handleError(500,"Action in HHP '{$this->routeInfo["action"]}' not known");
			break;
		}
	}
	
	private function renderHHPPicker(): void
    {
		$this->renderHeadline("Haushaltspläne");
		$obj = $this;
		$this->renderTable(
			["Id", "von", "bis", "Status"],
			[array_reverse($this->hhps,true)],
			[],
			[
				function($id){
					return "<a href='hhp/$id'><i class='fa fa-fw fa-chain'></i>&nbsp;HP-$id</a>";
				},
				[$this, "formatDateToMonthYear"],
				[$this, "formatDateToMonthYear"],
				function($stateString) use ($obj){
					return "<div class='label label-info'>" . htmlspecialchars(
							$obj->stateStrings[$stateString]
						) . "</div>";
				}
			]
		);
		?>
            <a href="<?= URIBASE ?>hhp/import" class="btn btn-primary" <?=
                AuthHandler::getInstance()->hasGroup("ref-finanzen-hv")? "" : "disabled"?>>
                <span class="fa fa-fw fa-plus"></span>Neu Importieren
            </a>
        <?php

		
	}
	
	private function exportCSV(): void
    {
		
		$hhp_id = $this->routeInfo["hhp-id"];
		$hhp = $this->hhps[$hhp_id];
		$groups = DBConnector::getInstance()->dbgetHHP($hhp_id);
		$header = [
			"group" => "Gruppe",
			"titel-nr" => "Titelnummer",
			"titel-name" => "Titelname",
			"plan-value" => "Plan-Wert",
			"booked-value" => "Gebucht-Wert",
			"decided-value" => "Beschlossen-Wert"
		];
		$group_nr = 1;
		$type = 0;
		$data = [];
		foreach ($groups as $group){
			if (count($group) === 0) {
                continue;
            }
			if ($type !== array_values($group)[0]["type"]) {
                $group_nr = 1;
            }
			
			$type = array_values($group)[0]["type"];
			$data[] = [
                "group" => ($type + 1) . "." . $group_nr++ . " " . array_values($group)[0]["gruppen_name"],
                "titel-nr" => "",
                "titel-name" => "",
                "plan-value" => "",
                "booked-value" => "",
                "decided-value" => "",
            ];
			$rowsBefore = count($data) + 2;
			foreach ($group as /*$titel_id =>*/ $row){
				if (!isset($row["_booked"])) {
                    $row["_booked"] = 0;
                }
				if (!isset($row["_saved"])) {
                    $row["_saved"] = 0;
                }
				$data[] = [
					"group" => "",
					"titel-nr" => $row["titel_nr"],
					"titel-name" => $row["titel_name"],
					"plan-value" => $row["value"],
					"booked-value" => $row["_booked"],
					"decided-value" => $row["_saved"],
				];
			}
			$rowsAfter = count($data) + 1;
			$data[] = [
				"group" => "",
				"titel-nr" => "",
				"titel-name" => "Summe",
				"plan-value" => "=SUMME(D$rowsBefore:D$rowsAfter)",
				"booked-value" => "=SUMME(E$rowsBefore:E$rowsAfter)",
				"decided-value" => "=SUMME(F$rowsBefore:F$rowsAfter)",
			];
		}
		
		$csvBuilder = new CSVBuilder($data, $header);
		$von = date_create($hhp["von"])->format("Y-m");
		$bis = date_create($hhp["bis"])->format("Y-m");
		$csvBuilder->echoCSV(date_create()->format("Y-m-d") . "-HHA-" . $von . "-bis-" . $bis);
	}
	
	public function renderImporter(): void
    {
        $this->renderHeadline('Importiere HHP per CSV');
        $this->renderAlert('Hinweis', [
            'CSV mit ; getrennt, Umlaute ok. Zellen können mit " " escaped sein müssen aber nicht, keine Leerzeilen, die erste Zeile (header) wird aktiv weggeworfen, dort sollten keine wichtigen Infos stehen',
            'Als Vorlage sollte der Download des HHP von letztem Jahr dienen (Encoding beachten!)',
            'Zeitpunkt der Gültigkeit des HHP eintragen. Der vorherige HHP wird dann zum Vortag beendet',
            'Fehler können nur durch Administratoren in der Datenbank selbst behoben werden'
        ], 'info')
        ?>
	    <form action="<?= URIBASE ?>hhp/import/preview" method="POST" role="form">
            <div class="input-group form-group input-daterange" data-provide="datepicker" data-date-format="yyyy-mm-dd" data-date-calendar-weeks="true" data-date-language="de">
                <div class="input-group-addon" style="background-color: transparent; border: none;">von
                </div><div class="input-group">
                    <input class="form-control" name="date-start" type="text">
                    <div class="input-group-addon">
                        <span class="fa fa-fw fa-calendar"></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <textarea class="form-control" name="importCSV" rows="20"></textarea>
            </div>
            <?php $this->renderNonce() ?>
            <button type="submit"
                    class="btn btn-primary <?= AuthHandler::getInstance()->hasGroup(
                        "ref-finanzen-hv"
                    ) ? "" : "user-is-not-hv" ?>"
                <?= AuthHandler::getInstance()->hasGroup("ref-finanzen-hv") ? "" : "disabled" ?>>
                Vorschau anzeigen
            </button>
        </form>
        <?php
    }

    public function renderImportPreview(): void
    {
        [$groups, $titel,] = $this->reverseCSV($_POST['importCSV']);
        $dateStart = date_create($_POST['date-start'])->format("Y-m-d");
        $mergedGroups = [];
        foreach ($groups as $number => $group){
            $typeName = $group['type'] === 0 ? "[EINNAHME]" : "[AUSGABE]";
            $mergedGroups[$typeName . " " . $group['gruppen_name']] = $titel[$number];
        }
        $this->renderHeadline('Vorschau Import HHP ab ' . htmlspecialchars($dateStart));
        $this->renderTable(['Titel Nr', 'Titelname', 'Wert'], $mergedGroups,[],[
                null,null, [$this, 'moneyEscapeFunction']
        ]);

        $this->renderHeadline('Speichern');
        ?>
        <form action="<?= URIBASE ?>rest/forms/hhp/save-import" method="POST" role="form" class="ajax-form">
            <?php $this->renderHiddenInput('importCSV', htmlspecialchars($_POST['importCSV'])) ?>
            <?php $this->renderHiddenInput('date-start', htmlspecialchars($_POST['date-start'])) ?>
            <?php $this->renderNonce() ?>
            <button type="submit"
                    class="btn btn-primary  <?= AuthHandler::getInstance()->hasGroup(
                        "ref-finanzen-hv"
                    ) ? "" : "user-is-not-hv" ?>"
                <?= AuthHandler::getInstance()->hasGroup("ref-finanzen-hv") ? "" : "disabled" ?>>
                Entgültig abspeichern
            </button>
        </form>
        <?php
    }

    public function saveNewHHP(): bool
    {

        [$groups, $titels, $newHHPid] = $this->reverseCSV($_POST['importCSV']);
        $dateStart = date_create($_POST['date-start'])->format("Y-m-d");
        $db = DBConnector::getInstance();

        $db->dbInsert('haushaltsplan', [
            "id" => $newHHPid,
            "von" => $dateStart,
            "state" => "final",
        ]);

        $db->dbUpdate('haushaltsplan', [
            "id" => $newHHPid-1,
            "state" => "final",
        ],[
            "bis" => date_create($dateStart . " -1 day")->format("Y-m-d"),
        ]);

        foreach ($groups as $groupNr => $group){
            $groupDBnr = $db->dbInsert('haushaltsgruppen', $group);
            $titelInGroup = $titels[$groupNr];
            foreach ($titelInGroup as $titel){
                $titel['hhpgruppen_id'] = $groupDBnr;
                $db->dbInsert('haushaltstitel', $titel);
            }
        }
        return true;
    }
	
	public function reverseCSV($csvString): array
    {
	    $newHHPid = max(array_keys($this->hhps)) +1;
	    $rows = explode(PHP_EOL, $csvString);
	    $rows = array_slice($rows,1); // throw away header
	    $activeGroupId = '';
	    $groups = [];
	    $titel = [];
	    foreach ($rows as $row){
	        $cells = explode(';', str_replace('"','',$row));
	        //new group
            if(!empty($cells[0])){
                $groupString = $cells[0];
                [$activeGroupId, $groupName] = explode(" ", $groupString, 2);
                $groupType = explode(".", $activeGroupId)[0] === '1' ? 0 /* Einnahme */ : 1 /* Ausgabe */;
                $groups[$activeGroupId] = [
                    'hhp_id' => $newHHPid,
                    'gruppen_name' => $groupName,
                    'type' => $groupType,
                ];
            }else{
                $titelnr = $cells[1];
                if(empty($titelnr)) {
                    continue;
                } // group sum here, trash this info
                $titel[$activeGroupId][] = [
                    // hhpgruppen_id not available here, set later
                    'titel_nr' => $titelnr,
                    'titel_name' => $cells[2],
                    'value' => (int) $cells[3],
                ];
            }
        }
	    return [$groups, $titel, $newHHPid];
    }
	
	public function renderHaushaltsplan(): void
    {
		
		$hhp_id = $this->routeInfo["hhp-id"];
		$hhp = $this->hhps[$hhp_id];
		$groups = DBConnector::getInstance()->dbgetHHP($hhp_id);
		//var_dump($groups);
		$this->renderHeadline("Haushaltsplan seit " . $this->formatDateToMonthYear($hhp["von"]));
		?>
        <table class="table table-striped">
			<?php
			$group_nr = 1;
			$type = 0;
			foreach ($groups as $group){
				if (count($group) === 0) {
                    continue;
                }
				if ($type !== array_values($group)[0]["type"]) {
                    $group_nr = 1;
                }
		
				$type = array_values($group)[0]["type"];
				?>
                <thead>
                <tr>
                    <th class="bg-info"
                        colspan="6"><?= ($type + 1) . "." . $group_nr++ . " " . array_values(
							$group
						)[0]["gruppen_name"] ?></th>
                </tr>
                <tr>
                    <th></th>
                    <th>Titelnr</th>
                    <th>Titelname</th>
                    <th class="money">
                        <?= "soll-" . (array_values($group)[0]["type"] === 0 ? "Einnahmen" : "Ausgaben") ?>
                    </th>
                    <th class="money">
                        <?= "ist-" . (array_values($group)[0]["type"] === 0 ? "Einnahmen" : "Ausgaben") . " (gebucht)" ?>
                    </th>
                    <th class="money">
                        <?= "ist-" . (array_values($group)[0]["type"] === 0 ? "Einnahmen" : "Ausgaben") . " (beschlossen)" ?>
                    </th>
                </tr>
                </thead>
                <tbody>
				<?php
				$gsum_soll = 0;
				$gsum_ist = 0;
				$gsum_saved = 0;
				foreach ($group as $titel_id => $row){
					if (!isset($row["_booked"])) {
                        $row["_booked"] = 0;
                    }
					if (!isset($row["_saved"])) {
                        $row["_saved"] = 0;
                    }
					$gsum_soll += $row["value"];
					$gsum_ist += $row["_booked"];
					$gsum_saved += $row["_saved"];
					?>
                    <tr>
                        <td></td>
                        <td><?= $row["titel_nr"] ?></td>
                        <td><a href="<?= URIBASE . "hhp/$hhp_id/titel/$titel_id" ?>">
                                <i class="fa fa-fw fa-search-plus"></i><?= $row["titel_name"] ?>
                            </a>
                        </td>
                        <td class="money">
                            <?= DBConnector::getInstance()->convertDBValueToUserValue($row["value"], "money") ?>
                        </td>
                        <td <?= $this->checkTitelBudget($row["value"], $row["_booked"]) ?>>
							<?= DBConnector::getInstance()->convertDBValueToUserValue($row["_booked"], "money") ?>
                        </td>
                        <td <?= $this->checkTitelBudget($row["value"], $row["_saved"]) ?>>
							<?= DBConnector::getInstance()->convertDBValueToUserValue($row["_saved"], "money") ?>
                        </td>
                    </tr>
	
					<?php
				} ?>
                <tr class="table-sum-footer">
                    <td colspan="3"></td>
                    <td class="money table-sum-hhpgroup">
                        <?= DBConnector::getInstance()->convertDBValueToUserValue($gsum_soll,"money") ?>
                    </td>
                    <td class="money table-sum-hhpgroup">
                        <?= DBConnector::getInstance()->convertDBValueToUserValue($gsum_ist, "money") ?>
                    </td>
                    <td class="money table-sum-hhpgroup">
                        <?= DBConnector::getInstance()->convertDBValueToUserValue($gsum_saved, "money") ?>
                    </td>
                </tr>
                </tbody>
				<?php
			} ?>
        </table>
        <a class="btn btn-primary" target="_blank" href="<?= URIBASE ?>export/hhp/<?= $hhp_id ?>/csv">
            <i class="fa fa-fw fa-download"></i> HHA als CSV (WINDOWS-1252 encoded)
        </a>
		<?php

    }

	private function checkTitelBudget(float $should,float $is): string
    {
		$str = "";
		if ($should !== 0.0) {
            $str = "title='Titel ist zu " . number_format((float)($is / $should) * 100, 0) . "% ausgelastet'";
        }
		if ($is > $should){
			if ($is > $should * 1.5){
				return $str . " class='money hhp-danger'";
			}
            return $str . " class='money hhp-warning'";
        }
        return $str . " class='money'";
    }
	
	private function renderTitelDetails(): void
    {
		$hhp_id = $this->routeInfo["hhp-id"];
		$hhp = $this->hhps[$hhp_id];
		$titel_id = $this->routeInfo["titel-id"];
		$titel = DBConnector::getInstance()->dbFetchAll(
			"haushaltstitel",
			[DBConnector::FETCH_ASSOC],
			["titel_nr", "titel_name"],
			["id" => $titel_id]
		);
		if (count($titel) === 0){
            ErrorHandler::handleError(404,"Titel $titel_id kann nicht gefunden werden");
		}else{
			$titel = $titel[0];
		}
		$this->renderHeadline(
			"HHP seit " . $this->formatDateToMonthYear(
				$hhp["von"]
			) . " - Titel {$titel["titel_nr"]} - {$titel["titel_name"]}"
		);
		
		$this->renderHeadline("Buchungen", 4);
		$booked = DBConnector::getInstance()->dbFetchAll(
			"booking",
			[DBConnector::FETCH_ASSOC],
			[
				"booking.zahlung_id",
				"auslagen.projekt_id",
				"auslagen.id",
				"auslagen.name_suffix",
				"booking.beleg_id",
				//"booking.beleg_type",
				"booking.value"
			],
			["titel_id" => $titel_id, "booking.canceled" => 0],
			[
				["type" => "left", "table" => "beleg_posten", "on" => ["beleg_posten.id", "booking.belegposten_id"]],
				["type" => "left", "table" => "belege", "on" => ["beleg_posten.beleg_id", "belege.id"]],
				["type" => "left", "table" => "auslagen", "on" => ["belege.auslagen_id", "auslagen.id"]],
			]
		);
		$this->renderTable(
			["Zahlung", "Auslage", "Belegposten", "Betrag",],
			[$booked],
			[],
			[
				null,
				[$this, "auslagenLinkEscapeFunction"],
				null,
				[$this, "moneyEscapeFunction"],
			]
		);
		
		[$closedMoney, $openMoney] = DBConnector::getInstance()->getMoneyByTitle($hhp_id, false, $titel_id);
		$this->renderHeadline("Aus nicht beendeten Projekten", 4);
		//var_dump($openMoney);
		$this->renderTable(
			["Projekt", "Posten", "Betrag"],
			[$openMoney],
			["projekte.id", "projekte.createdat", "projekte.name", "name", "value"],
			[
				[$this, "projektLinkEscapeFunction"],
				null,
				[$this, "moneyEscapeFunction"],
			]
		);
		$this->renderHeadline("Aus beendeten Projekten", 4);
		//var_dump($closedMoney[0]);
		$this->renderTable(
			["Projekt", "Auslage", "Betrag"],
			[$closedMoney],
			[
				"projekte.id",
				"projekte.createdat",
				"projekte.name",
				"projekte.id",
				"auslagen.id",
				"auslagen.name_suffix",
				"value"
			],
			[
				[$this, "projektLinkEscapeFunction"],
				[$this, "auslagenLinkEscapeFunction"],
				[$this, "moneyEscapeFunction"],
			]
		);
	}
}
