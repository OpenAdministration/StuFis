<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.06.18
 * Time: 02:02
 */

abstract class Renderer{
    abstract public function render();
    
    protected function renderTable(array $header, array $groupedContent, array $escapeFunctions = [], array $footer = []){
        
        $defaultFunction = ["Renderer", "defaultEscapeFunction"];
        
        //throw away the keys (needed later), numeric keys need to be used
        $escapeFunctions = array_values($escapeFunctions);
        //set every function which is null or empty to default function
        array_walk($escapeFunctions, function(&$val) use ($defaultFunction){
            if (!isset($val) || empty($val)){
                $val = $defaultFunction;
            }
        });
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
                if ($rf->getNumberOfParameters() === 0){
                    ErrorHandler::_errorExit("Eine Funktion mit 0 benötigten Parametern wurde übergeben.");
                }
                //let some space in the idx
                $reflectionsOfFunctions[$idx] = $rf;
                $paramSum += $rf->getNumberOfParameters();
                
            }
            //if there are to less parameters - add some default functions.
            $diff = count($header) - count($escapeFunctions);
            if ($diff > 0){
                $paramSum += count($header) - count($escapeFunctions);
                $escapeFunctions = array_merge(
                    $escapeFunctions,
                    array_fill(0, count($header) - count($escapeFunctions), $defaultFunction)
                );
            }
            foreach ($groupedContent as $groupName => $content){
		if(empty($content)) 
			continue;
		if (count(reset($content)) != $paramSum){
                    ErrorHandler::_errorExit(
                        "In Gruppe '$groupName' passt Spaltenzahl (" . count(reset($content)) .
                        ") nicht zur benötigten Parameterzahl $paramSum - es wurden " . count($escapeFunctions) .
                        " Funktionen übergeben " . $diff . " wurde(n) hinzugefügt."
                    );
                }
            }
        }catch (ReflectionException $reflectionException){
            ErrorHandler::_errorExit("Reflection not working..." . $reflectionException->getMessage());
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
                        $row = array_values($row);
                        $shiftIdx = 0;
                        foreach ($reflectionsOfFunctions as $idx => $reflectionOfFunction){
                            $args = array_slice($row, $shiftIdx, $reflectionOfFunction->getNumberOfParameters());
                            //var_export([$row, $args]);
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
        echo "<h" . htmlspecialchars($headlineNr) . ">" . htmlspecialchars($text) . "</h" . htmlspecialchars($headlineNr) . ">";
    }
    
    protected function formatDateToMonthYear($dateString){
        return !empty($dateString) ? strftime("%b %G", strtotime($dateString)) : "";
    }
    
    protected function projektLinkEscapeFunction($id, $createdate, $name){
        $year = date("y", strtotime($createdate));
        return $this->renderInternalHyperLink("IP-$year-$id $name", "projekt/$id");
    }
    
    protected function renderInternalHyperLink($text, $dest){
        return "<a href='" . htmlspecialchars(URIBASE . $dest) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
    }
    
    protected function auslagenLinkEscapeFunction($projektId, $auslagenId, $name){
        return $this->renderInternalHyperLink(
            "A$auslagenId " . $this->defaultEscapeFunction($name),
            "projekt/$projektId/auslagen/$auslagenId"
        );
    }
    
    protected function defaultEscapeFunction($val){
        //default escape-funktion to use if nothing is
        if (empty($val)){
            return "<i>keine Angabe</i>";
        }else{
            return htmlspecialchars($val);
        }
    }
    
    protected function moneyEscapeFunction($money){
        return number_format($money, 2, ",", " ") . "&nbsp€";
    }
    
    protected function date2relstrEscapeFunction($time){
        if ($time === "")
            return $this->defaultEscapeFunction("");
        if (!ctype_digit($time))
            $time = strtotime($time);
        
        $diff = strtotime(date("Y-m-d")) - $time;
        
        $past = $diff > 0;
        $diff = abs($diff);
        $anzahlTage = floor($diff / (60 * 60 * 24));
        if ($anzahlTage > 1){
            return ($past ? "vor " : "in ") . $anzahlTage . " Tagen";
        }else if ($anzahlTage === 0){
            return "heute";
        }else{
            return $past ? "gestern" : "morgen";
        }
    }
}
