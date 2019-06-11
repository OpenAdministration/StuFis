<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 26.02.19
 * Time: 19:34
 */

class BookingTableManager
	extends Renderer{
	
	private $col_zahlung;
	private $col_auslagen;
	private $col_posten;
	private $col_rest;
	
	private $zahlung_lastValue;
	private $auslage_lastValue;
	private $posten_lastValue;
	
	private $actual_instruction;
	private $table_tmp;
	private $table;
	private $executed;
	
	private $instructions;
	private $zahlungDB;
	private $belegeDB;
	private $kontoTypes;
	
	public function __construct($instructionsWhitelist = []){
		$this->col_zahlung = 0;
		$this->col_auslagen = 0;
		$this->col_posten = 0;
		$this->col_rest = 0;
		$this->table = [];
		$this->table_tmp = [];
		$this->posten_lastValue = "";
		$this->auslage_lastValue = "";
		$this->zahlung_lastValue = "";
		$this->executed = false;
		$this->kontoTypes = [];
		
		$this->fetchFromDB($instructionsWhitelist);
	}
	
	private function fetchFromDB($instructionsWhitelist){
		$zahlungenDB = [];
		$belegeDB = [];
		
		if (!empty($instructionsWhitelist)){
			$where = ["booking_instruction.id" => ["IN", $instructionsWhitelist], "booking_instruction.done" => 0];
		}else{
			$where = ["booking_instruction.done" => 0];
		}
		
		$this->kontoTypes = DBConnector::getInstance()->dbFetchAll(
			"konto_type",
			[DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY]
		);
		
		$this->instructions = DBConnector::getInstance()->dbFetchAll(
			"booking_instruction",
			[DBConnector::FETCH_GROUPED],
			["booking_instruction.id", "zahlung", "beleg", "beleg_type", "user.id", "user.fullname", "zahlung_type"],
			$where,
			[
				["table" => "user", "type" => "left", "on" => ["booking_instruction.by_user", "user.id"]],
			],
			["booking_instruction.id" => true]
		);
		
		//$this->instructions = array_intersect_key($this->instructions, array_flip([11, 6]));  // FIXME DELETEME
		
		foreach ($this->instructions as $instruct_id => $instruction){
			$zahlungen = [];
			$extern_ids = [];
			$auslagen_ids = [];
			foreach ($instruction as $row){
				$zahlungen[$row["zahlung_type"]][] = $row["zahlung"];
				switch ($row["beleg_type"]){
					case "belegposten":
						$auslagen_ids[] = $row["beleg"];
					break;
					case "extern":
						$extern_ids[] = $row["beleg"];
					break;
					default:
						ErrorHandler::_errorExit("False whatever ... " . $row["beleg_type"]);
					break;
				}
			}
			$where = [];
			foreach ($zahlungen as $type => $z){
				$where[] = ["id" => ["IN", $z], "konto_id" => $type];
			}
			//titel_id, kostenstelle, zahlung_id, beleg_id, user_id, comment, value
			$zahlungenDB[$instruct_id] = DBConnector::getInstance()->dbFetchAll(
				"konto",
				[DBConnector::FETCH_ASSOC],
				[],
				$where,
				[],
				["value" => false]
			);
			if (!empty($auslagen_ids)){
				$auslagen = DBConnector::getInstance()->dbFetchAll(
					"auslagen",
					[DBConnector::FETCH_ASSOC],
					[
						"auslagen.projekt_id",
						"auslagen_id" => "auslagen.id",
						"belege_id" => "belege.id",
						"titel_name",
						"projekt_name" => "projekte.name",
						"projekt_createdate" => "projekte.createdat",
						"auslagen_name" => "name_suffix",
						"titel_nr",
						"titel_id" => "haushaltstitel.id",
						"titel_type" => "haushaltsgruppen.type",
						"posten_id" => "beleg_posten.id",
						"posten_short" => "beleg_posten.short",
						"belege_short" => "belege.short",
						"beleg_posten.einnahmen",
						"beleg_posten.ausgaben",
						"etag",
					],
					["auslagen.id" => ["IN", $auslagen_ids]],
					[
						["table" => "projekte", "type" => "inner", "on" => ["projekte.id", "auslagen.projekt_id"]],
						["table" => "belege", "type" => "inner", "on" => ["belege.auslagen_id", "auslagen.id"]],
						["table" => "beleg_posten", "type" => "inner", "on" => ["beleg_posten.beleg_id", "belege.id"]],
						[
							"table" => "projektposten",
							"type" => "inner",
							"on" => [
								["projektposten.id", "beleg_posten.projekt_posten_id"],
								["auslagen.projekt_id", "projektposten.projekt_id"]
							],
						],
						[
							"table" => "haushaltstitel",
							"type" => "left",
							"on" => ["projektposten.titel_id", "haushaltstitel.id"],
						],
						[
							"table" => "haushaltsgruppen",
							"type" => "left",
							"on" => ["haushaltstitel.hhpgruppen_id", "haushaltsgruppen.id"],
						],
					]
				);
				foreach ($auslagen as $id => $row){
					$auslagen[$id]["value"] = floatval($row["einnahmen"]) - floatval($row["ausgaben"]);
					$auslagen[$id]["type"] = "belegposten";
				}
			}else{
				$auslagen = [];
			}
			if (!empty($extern_ids)){
				$extern = DBConnector::getInstance()->dbFetchAll(
					"extern_data",
					[DBConnector::FETCH_ASSOC],
					[
						"extern_data.id",
						"extern_id",
						"vorgang_id",
						"projekt_name",
						"org_name",
						"flag_vorkasse",
						"flag_rueckforderung",
						"flag_pruefbescheid",
						"titel_nr",
						"titel_name",
						"titel_id" => "haushaltstitel.id",
						"titel_type" => "haushaltsgruppen.type",
						"extern_data.value",
						"etag",
					],
					["extern_data.id" => ["IN", $extern_ids]],
					[
						[
							"table" => "extern_meta",
							"type" => "inner",
							"on" => ["extern_meta.id", "extern_data.extern_id"]
						],
						[
							"table" => "haushaltstitel",
							"type" => "left",
							"on" => ["extern_data.titel_id", "haushaltstitel.id"]
						],
						[
							"table" => "haushaltsgruppen",
							"type" => "left",
							"on" => ["haushaltstitel.hhpgruppen_id", "haushaltsgruppen.id"],
						],
					]
				);
				foreach ($extern as $id => $row){
					$extern[$id]["type"] = "extern";
					$vz = $row["flag_vorkasse"] == 1 ? -1 : ($row["flag_pruefbescheid"] == 1 ? -1 : 1);
					$extern[$id]["value"] = $vz * floatval($row["value"]);
				}
			}else{
				$extern = [];
			}
			//var_dump($auslagen_ids);
			$belegeDB[$instruct_id] = array_merge($auslagen, $extern);
			//var_dump($belegeDB[$instruct_id]);
			usort(
				$belegeDB[$instruct_id],
				function($a, $b){
					return $b["value"] <=> $a["value"];
				}
			);
			
			//$belegeDB[$instruct_id] = array_reverse($belegeDB[$instruct_id]); //FIXME DELETEME
			//$zahlungenDB[$instruct_id] = array_reverse($zahlungenDB[$instruct_id]); //FIXME DELETEME
			
		}
		
		$this->zahlungDB = $zahlungenDB;
		$this->belegeDB = $belegeDB;
		
	}
	
	/**
	 * @return array
	 */
	public function getZahlungDB(){
		return $this->zahlungDB;
	}
	
	/**
	 * @return array "instruct_id" => [
	 *                  [beleg]
	 *               ]
	 */
	public function getBelegeDB(){
		return $this->belegeDB;
	}
	
	/**
	 * @return mixed
	 */
	public function getInstructions(){
		return $this->instructions;
	}
	
	public function render(){
		if ($this->executed === false){
			$this->run();
		}
		$header = [
			"zahlung" => "Zahlung",
			"zahlung-value" => "Betrag Zahlung",
			"beleg" => "Beleg",
			"posten" => "Posten/Vorgang",
			"titel" => "Titel",
			"posten-ist" => "Betrag Buchung",
			"posten-soll" => "Betrag Beleg",
			"text" => "Buchungstext",
		];
		$table = $this->getTable(); ?>
        <form method="POST" action="<?= URIBASE ?>rest/booking/save" class="ajax-form">
            <table class="table">
                <thead>
                <tr>
					<?php
					foreach ($header as $name){
						echo "<th>$name</th>";
					} ?>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ($this->instructions as $instruct_id => $instruction){
					echo "<tr><td class='bg-info' colspan='" . count($header) . "'>";
					$zCount = count($this->zahlungDB[$instruct_id]);
					$bCount = count($this->belegeDB[$instruct_id]);
					echo "<strong>Angewiesener Vorgang $instruct_id</strong> - " . $zCount . " Zahlung" . ($zCount === 1
							? "" : "en") . " und " . $bCount . " Belegposten";
					echo " - Angewiesen von: " . array_values($instruction)[0]["fullname"];
					$this->renderHiddenInput("instructions[]", $instruct_id);
					echo "</td></tr>";
					
					foreach ($table[$instruct_id] as $nr_of_rows => $row){
						echo "<tr>";
						foreach ($header as $key => $text){
							if (isset($row[$key])){
								$cell = $row[$key];
								$title = isset($cell["title"]) ? $cell["title"] : "";
								$colspan = isset($cell["colspan"]) ? $cell["colspan"] : 1;
								$id = "booking-table_" . $key . "-" . $nr_of_rows;
								echo "<td id='$id' class='vertical-center no-wrap' colspan='$colspan' rowspan='{$cell["rowspan"]}' title='$title'>
{$cell["val"]}</td>";
							}
						}
						echo "</tr>";
					}
				} ?>
                </tbody>
            </table>
			<?php
			$this->renderNonce();
			?>
            <button class="btn btn-primary pull-right"
				<?= !(AUTH_HANDLER)::getInstance()->hasGroup('ref-finanzen-kv') ? "disabled" : "" ?>
				<?php
				if (!(AUTH_HANDLER)::getInstance()->hasGroup('ref-finanzen-kv')){
					echo "title='Nur Kassenverantwortliche können eine Buchung durchführen!'";
				} ?>
            >
                Buchung durchführen
            </button>
        </form>
		<?php
	}
	
	public function run(){
		foreach ($this->instructions as $instruction_id => $someNotUsedValue){
			$this->nextInstruction($instruction_id);
			$bIdx = 0;
			$bValDone = 0;
			$bVal = 0;
			$b = null;
			$exceededLastBeleg = false;
			foreach ($this->zahlungDB[$instruction_id] as $z){
				$zVal = floatval($z["value"]); //Restbetrag der Zahlung
				while (abs($zVal) >= 0.01){
					//process akt zahlung + akt beleg
					if ($bIdx < count($this->belegeDB[$this->actual_instruction])){
						$b = $this->belegeDB[$instruction_id][$bIdx];
					}else{
						$exceededLastBeleg = true;
					}
					$bVal = $b["value"];
					list($zVal, $bValDone) = $this->process($z, $b, $zVal, $bValDone, $exceededLastBeleg);
					//count up if beleg is done (no rest)
					if (abs($bValDone - $bVal) < 0.01){
						//beautification only (at last beleg)
						if ($bIdx === count($this->belegeDB[$this->actual_instruction]) - 1 && abs($zVal) >= 0.01){
							$this->manipulateLastPostenIst(
								$zVal + $bVal
							);
							$zVal = 0;
						}
						//do the counting
						$bIdx++;
						$bValDone = 0;
						$bVal = 0;
					}
				}
			}
			$lastZ = array_slice($this->zahlungDB[$instruction_id], -1)[0];
			//beautification only
			if ($bIdx < count($this->belegeDB[$this->actual_instruction]) && abs($bValDone - $bVal) > 0.01){
				$this->manipulateLastPostenIst($bVal - $bValDone + $this->getLastPostenIst());
				$bIdx++;
				$bValDone = 0;
			}
			//add missed belege
			for ($i = $bIdx; $i < count($this->belegeDB[$this->actual_instruction]); $i++){
				$b = $this->belegeDB[$this->actual_instruction][$i];
				$this->process($lastZ, $b, $bVal, 0, false);
			}
			
		}
		$this->executed = true;
	}
	
	
	private function process($z, $b, $zSum, $bValDone, bool $exceededLastBeleg){
		switch ($b["type"]){
			case "belegposten":
				$prefilledText = $b["projekt_name"] . " - " . $b["auslagen_name"];
				$newPostenName = "P" . $b["posten_short"];
				$newPostenNameRaw = $b["posten_id"];
				$newBelegName = $this->auslagenLinkEscapeFunction(
					$b["projekt_id"],
					$b["auslagen_id"],
					"B" . $b["belege_short"]
				);
			break;
			case "extern":
				$prefilledText = $b["projekt_name"] . " - " . $b["org_name"];
				$newPostenName = "V" . $b["vorgang_id"];
				$newPostenNameRaw = $b["id"];
				$newBelegName = "E" . $b["extern_id"];
			break;
			default:
				ErrorHandler::_errorExit("Unbekannter Typ: " . $b["type"] . var_export($b, true));
			break;
		}
		
		if ($exceededLastBeleg){
			//add new Zahlung, extend last Beleg and Posten
			$this->pushZahlung($z["id"], $z["konto_id"], $z["value"]);
			$this->extendLastBeleg();
			$this->extendLastPosten();
			$this->pushNewPostenIst($z["value"], $b["titel_type"], $prefilledText);
			return [0, 0];
		}
		
		$bVal = $b["value"];
		$zVal = $z["value"];
		$zSumNew = $zSum - ($bVal - $bValDone);
		$bValDoneNew = $bVal;
		
		$this->pushPosten(
			$newPostenName,
			$newPostenNameRaw,
			$newBelegName,
			$b["titel_id"],
			$b["titel_nr"],
			$b["titel_name"],
			$bVal
		);
		$this->pushZahlung($z["id"], $z["konto_id"], $z["value"]);
		$this->pushBeleg($newBelegName, $b["type"]);
		$type = $this->identifyType($zSum, $zVal, $bValDone, $bVal);
		
		switch ($type){
			case 0: //ging auf: next zahlung + next beleg
				$this->pushNewPostenIst($bVal - $bValDone, $b["titel_type"], $prefilledText);
			break;
			case 1: //gleiche Zahlung, neuer Beleg
				$this->pushNewPostenIst($bVal - $bValDone, $b["titel_type"], $prefilledText);
				$bValDoneNew = $bVal;
			break;
			case 2: //übertrag -> next zahlung, same beleg
				$this->pushNewPostenIst($zSum, $b["titel_type"], $prefilledText);
				$bValDoneNew = $bValDone + $zSum;
				$zSumNew = 0;
			break;
			case 3: //zahlung wird (absolut) mehr
				$this->pushNewPostenIst($bVal - $bValDone, $b["titel_type"], $prefilledText);
			break;
			case 4: //beleg wird absolut mehr
				$this->pushNewPostenIst($zSum, $b["titel_type"], $prefilledText);
			break;
			case 5: //negative Zahlung, positiver Beleg
				$this->pushNewPostenIst($bVal - $bValDone, $b["titel_type"], $prefilledText);
			break;
		}
		
		return [$zSumNew, $bValDoneNew];
	}
	
	private function identifyType(float $zSum, float $zVal, float $bSum, float $Val){
		return 0;
		/*
		if (abs($zSum) < 0.01){
			return 0;
		}
		if ((($zVal <=> 0) * ($zSum <=> 0)) === 1){
			//falls gleiches vorzeichen
			if (abs($zVal) > abs($zSum)){
				//falls näher zur null als vorher
				return 1;
			}
			if((($zVal <=> 0) * ($bSum <=> 0)) === -1){
				return 4;
			}
		}else{
			// unterschiedliches vorzeichen
			return 2;
		}*/
	}
	
	private function sameSign(float $a, float $b){
		return ((($a <=> 0) * ($b <=> 0)) === 1);
	}
	
	public function nextInstruction(int $i){
		if (isset($this->actual_instruction)){
			$this->table[$this->actual_instruction] = $this->table_tmp;
			$this->table_tmp = [];
			$this->col_zahlung = 0;
			$this->col_auslagen = 0;
			$this->col_posten = 0;
			$this->col_rest = 0;
			$this->posten_lastValue = "";
			$this->auslage_lastValue = "";
			$this->zahlung_lastValue = "";
		}
		$this->actual_instruction = $i;
	}
	
	public function getTable($fullRows = false){
		$this->table[$this->actual_instruction] = $this->table_tmp;
		if ($fullRows === false){
			return $this->table;
		}else{
			$ret_table = [];
			foreach ($this->table as $instruction_id => $rowGroups){
				foreach ($rowGroups as $id => $row){
					foreach (array_keys($row) as $key){
						$rowspan = $row[$key]["rowspan"];
						unset($row[$key]["rowspan"], $row[$key]["colspan"]);
						$idx = 0;
						while ($idx < $rowspan){
							$ret_table[$instruction_id][$id + $idx][$key] = $row[$key];
							$idx++;
						}
					}
				}
			}
			return $ret_table;
		}
	}
	
	public function pushBeleg(string $belegValue, string $type){
		
		if ($this->auslage_lastValue === $belegValue){
			$this->extendLastBeleg();
		}else{
			if (isset($this->table_tmp[$this->col_auslagen]["beleg"]["rowspan"])){
				$this->col_auslagen += $this->table_tmp[$this->col_auslagen]["beleg"]["rowspan"];
			}
			$this->table_tmp[$this->col_auslagen]["beleg"] = [
				"val" => $belegValue,
				"rowspan" => 1,
				"colspan" => 1,
				"beleg-type" => $type,
			];
			$this->auslage_lastValue = $belegValue;
		}
	}
	
	public function pushZahlung(int $zahlungId, int $zahlungIdType, float $zahlungValue){
		//FIXME with DB querry
		if (isset($this->kontoTypes[$zahlungIdType])){
			$prefix = $this->kontoTypes[$zahlungIdType]["short"];
		}else{
			ErrorHandler::_errorExit("Konto Type $zahlungIdType nicht bekannt.");
		}
		$newValue = $prefix . $zahlungId;
		if ($this->zahlung_lastValue === $newValue){
			$this->extendLastZahlung();
		}else{
			if (isset($this->table_tmp[$this->col_zahlung]["zahlung"]["rowspan"])){
				$this->col_zahlung += $this->table_tmp[$this->col_zahlung]["zahlung"]["rowspan"];
			}
			$this->table_tmp[$this->col_zahlung]["zahlung"] = [
				"val" => $newValue,
				"val-raw" => $zahlungId,
				"rowspan" => 1,
				"colspan" => 1,
				"zahlung-type" => $zahlungIdType,
			];
			$this->table_tmp[$this->col_zahlung]["zahlung-value"] = [
				"val" => $this->moneyEscapeFunction($zahlungValue),
				"val-raw" => $zahlungValue,
				"rowspan" => 1,
				"colspan" => 1,
			];
			$this->zahlung_lastValue = $newValue;
		}
	}
	
	public function pushPosten(
		$newValue, $newValueRaw, $belegName, $titelId, $titelNr, $titelName, $postenSoll
	){
		
		if ($this->posten_lastValue === $newValue && $belegName === $this->auslage_lastValue){
			$this->extendLastPosten();
		}else{
			if (isset($this->table_tmp[$this->col_posten]["posten"]["rowspan"])){
				$this->col_posten += $this->table_tmp[$this->col_posten]["posten"]["rowspan"];
			}
			$this->table_tmp[$this->col_posten]["posten"] = [
				"val" => $newValue,
				"val-raw" => $newValueRaw,
				"rowspan" => 1,
				"colspan" => 1,
			];
			$this->table_tmp[$this->col_posten]["posten-soll"] = [
				"val" => $this->moneyEscapeFunction($postenSoll),
				"val-raw" => $postenSoll,
				"rowspan" => 1,
				"colspan" => 1,
			];
			$this->table_tmp[$this->col_posten]["titel"] = [
				"val" => $titelNr,
				"val-raw" => $titelId,
				"rowspan" => 1,
				"colspan" => 1,
				"title" => $titelName,
			];
			$this->posten_lastValue = $newValue;
		}
	}
	
	public function pushNewPostenIst($postenIstValue, $titel_type, $prefilledText = ""){
		if ($titel_type === "0"){
			$postenIstValue = -$postenIstValue;
		}
		$this->table_tmp[$this->col_rest]["posten-ist"] = [
			"val" => $this->moneyEscapeFunction($postenIstValue),
			"val-raw" => $postenIstValue,
			"rowspan" => 1,
			"colspan" => 1,
		];
		$this->table_tmp[$this->col_rest]["text"] = [
			"val" => $this->textAreaEscapeFunction("text[]", $prefilledText, true),
			"rowspan" => 1,
			"colspan" => 1,
		];
		$this->col_rest++;
	}
	
	private function getLastPostenIst(){
		if ($this->col_rest <= 0){
			return false;
		}
		return $this->table_tmp[$this->col_rest - 1]["posten-ist"]["val-raw"];
	}
	
	private function manipulateLastPostenIst($newVal){
		if ($this->col_rest <= 0){
			return false;
		}
		$this->table_tmp[$this->col_rest - 1]["posten-ist"]["val"] = $this->moneyEscapeFunction($newVal);
		$this->table_tmp[$this->col_rest - 1]["posten-ist"]["val-raw"] = $newVal;
		return true;
	}
	
	public function extendLastBeleg(){
		if (isset($this->table_tmp[$this->col_auslagen]["beleg"])){
			$this->table_tmp[$this->col_auslagen]["beleg"]["rowspan"]++;
			return true;
			
		}
		return false;
	}
	
	public function extendLastZahlung(){
		if (isset($this->table_tmp[$this->col_zahlung]["zahlung"]) && isset
			(
				$this->table_tmp[$this->col_zahlung]["zahlung-value"]
			)){
			$this->table_tmp[$this->col_zahlung]["zahlung"]["rowspan"]++;
			$this->table_tmp[$this->col_zahlung]["zahlung-value"]["rowspan"]++;
			return true;
		}
		return false;
	}
	
	public function extendLastPosten(){
		if (isset($this->table_tmp[$this->col_posten]["posten"])){
			$this->table_tmp[$this->col_posten]["posten"]["rowspan"]++;
			$this->table_tmp[$this->col_posten]["posten-soll"]["rowspan"]++;
			$this->table_tmp[$this->col_posten]["titel"]["rowspan"]++;
			return true;
		}
		return false;
	}
	
	
}