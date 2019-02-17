<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.06.18
 * Time: 02:02
 */

abstract class Renderer
	extends EscFunc{
	abstract public function render();
	
	protected function renderTable(
		array $header, array $groupedContent, array $keys, array $escapeFunctions = [], array $footer = []
	){
		
		$defaultFunction = ["Renderer", "defaultEscapeFunction"];
		
		//throw away the keys (needed later), numeric keys need to be used
		$escapeFunctions = array_values($escapeFunctions);
		//set every function which is null or empty to default function
		array_walk(
			$escapeFunctions,
			function(&$val) use ($defaultFunction){
				if (!isset($val) || empty($val)){
					$val = $defaultFunction;
				}
			}
		);
		//count the parameters of all functions, which can be used
		$reflectionsOfFunctions = [];
		$isReflectionMethods = [];
		$paramSum = 0;
		
		
		try{
			foreach ($escapeFunctions as $idx => $escapeFunction){
				if (is_array($escapeFunction)){
					$rf = new ReflectionMethod($escapeFunction[0], $escapeFunction[1]);
					$isReflectionMethods[] = true;
				}else{
					$rf = new ReflectionFunction($escapeFunction);
					$isReflectionMethods[] = false;
				}
				
				//let some space in the idx
				$reflectionsOfFunctions[$idx] = $rf;
				$paramSum += $rf->getNumberOfParameters();
				
			}
			//if there are to less parameters - add some default functions.
			$diff = count($header) - count($escapeFunctions);
			if ($diff > 0){
				$paramSum += $diff;
				$escapeFunctions = array_merge(
					$escapeFunctions,
					array_fill(0, $diff, $defaultFunction)
				);
				$isReflectionMethods = array_merge($isReflectionMethods, array_fill(0, $diff, true));
				$reflectionsOfFunctions = array_merge(
					$reflectionsOfFunctions,
					array_fill(
						0,
						$diff,
						new ReflectionMethod(
							$defaultFunction[0], $defaultFunction[1]
						)
					)
				);
			}
			foreach ($groupedContent as $groupName => $content){
				if (empty($content))
					continue;
				if (count(reset($content)) != $paramSum && count($keys) != $paramSum){
					ErrorHandler::_errorExit(
						"In Gruppe '$groupName' passt Spaltenzahl (" . count(
							reset($content)
						) . ") bzw. Key Anzahl (" . count(
							$keys
						) . ") nicht zur benötigten Parameterzahl $paramSum \n es wurden " . count(
							$escapeFunctions
						) . " Funktionen übergeben " . $diff . " wurde(n) hinzugefügt."
					);
				}
			}
		}catch (ReflectionException $reflectionException){
			ErrorHandler::_errorExit("Reflection not working..." . $reflectionException->getMessage());
		}
		
		if (count($keys) == 0){
			$keys = range(0, $paramSum);
			$assoc = false;
		}else{
			$assoc = true;
		}
		
		?>
        <table class="table">
            <thead>
            <tr>
				<?php
				foreach ($header as $titel){
					echo "<th>$titel</th>";
				}
				?>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach ($groupedContent as $groupName => $rows){
				if (!is_int($groupName)){ ?>
                    <tr>
                        <th class="bg-info" colspan="<?= count($header) ?>"><?php echo $groupName; ?></th>
                    </tr>
				<?php }
				foreach ($rows as $row){
					//echo count($row) . "-". count($escapeFunctions) . "-". count($header);
					?>
                    <tr>
						<?php
						//throw away keys
						if (!$assoc){
							$row = array_values($row);
						}
						
						$shiftIdx = 0;
						foreach ($reflectionsOfFunctions as $idx => $reflectionOfFunction){
							//var_export($keys);
							$arg_keys = array_slice(
								$keys,
								$shiftIdx,
								$reflectionOfFunction->getNumberOfParameters()
							);
							$args = [];
							foreach ($arg_keys as $arg_key){
								$args[] = $row[$arg_key];
							}
							//var_export($args);
							//var_export($row);
							//var_export($reflectionOfFunction->getNumberOfParameters());
							$shiftIdx += $reflectionOfFunction->getNumberOfParameters();
							if ($isReflectionMethods[$idx]){
								echo "<td>" . call_user_func_array($escapeFunctions[$idx], $args) . "</td>";
							}else{
								echo "<td>" . $reflectionOfFunction->invokeArgs($args) . "</td>";
							}
							
						} ?>
                    </tr>
				<?php } ?>
			<?php } ?>
            </tbody>
			<?php if ($footer && is_array($footer) && count($footer) > 0){ ?>
                <tfoot> <?php
				if (!is_array(array_values($footer)[0])){
					$footer = [$footer];
				}
				foreach ($footer as $foot_line){
					echo '<tr>';
					foreach ($foot_line as $foot){
						echo "<th>$foot</th>";
					}
					echo '</tr>';
				}
				?>
                </tfoot>
			<?php } ?>
        </table>
	<?php }
	
	protected function renderHeadline($text, int $headlineNr = 1){
		echo "<h" . htmlspecialchars($headlineNr) . ">" . htmlspecialchars($text) . "</h" . htmlspecialchars(
				$headlineNr
			) . ">";
	}
	
	protected function formatDateToMonthYear($dateString){
		return !empty($dateString) ? strftime("%b %G", strtotime($dateString)) : "";
	}
	
	protected function renderHiddenInput($name, $value){ ?>
        <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
		<?php
	}
	
	protected function renderNonce(){
		$this->renderHiddenInput("nonce", $GLOBALS["nonce"]);
		$this->renderHiddenInput("nononce", $GLOBALS["nonce"]);
	}
	
	/**
	 *
	 * @param $strongMsg
	 * @param $msg
	 * @param $type string has to be <i>"success"</i>, "info", "warning" or "danger"
	 */
	protected function renderAlert($strongMsg, $msg, $type = "success"){
		if (!in_array($type, ["success", "info", "warning", "danger"])){
			ErrorHandler::_renderError("Falscher Datentyp in renderAlert()", 405);
		}
		?>
        <div class="alert alert-<?= $type ?>">
            <strong><?= $strongMsg ?></strong> <?= $msg ?>
        </div>
		<?php
	}
	
	protected function makeClickableMails($text){
		//$text = htmlspecialchars($text);
		$matches = [];
		preg_match_all('#[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}#', $text, $matches);
		var_dump($matches[0]);
		foreach ($matches[0] as $match){
			$text = str_replace($match, $this->mailto($match), $text);
		}
		return $text;
	}
	
	protected function mailto($adress){
		return "<a href='mailto:$adress'><i class='fa fa-fw fa-envelope'></i>$adress</a>";
	}
}
