<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.06.18
 * Time: 02:02
 */

abstract class Renderer{
    abstract public function render();
    
    protected function renderTable(array $header, array $groupedContent, array $escapeFunctions = [], array $footer = NULL){
    
        //default escape-funktion to use if nothing is
        $defaultFunction = function($val){
            if (empty($val)){
                return "<i>keine Angabe</i>";
            }else{
                return htmlspecialchars($val);
            }
        };
    
        //if there are to less functions - add some default functions.
        if (count($header) > count($escapeFunctions)){
            $escapeFunctions = array_merge(
                $escapeFunctions,
                array_fill(0, count($header) - count($escapeFunctions), $defaultFunction)
            );
        }
    
        //set every function which is null or empty to default function
        array_walk($escapeFunctions, function(&$val) use ($defaultFunction){
            if (!isset($val) || empty($val)){
                $val = $defaultFunction;
            }
        });
        
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
            <?php foreach ($groupedContent as $groupName => $rows){
                if (!is_int($groupName)){ ?>
                    <tr>
                        <th class="bg-info" colspan="<?= count($header) ?>"><?php echo $groupName; ?></th>
                    </tr>
                <?php }
                foreach ($rows as $row){ ?>
                    <tr>
                        <?php foreach (array_values($row) as $id => $cellContent){
                            echo "<td>" . $escapeFunctions[$id]($cellContent) . "</td>";
                        } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
            <?php if ($footer && is_array($footer) && count($footer > 0)){ ?>
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
    
    protected function renderInternalHyperLink($text, $dest){
        global $URIBASE;
        return "<a href='" . htmlspecialchars($URIBASE . $dest) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
    }
    
    protected function date2relstr($time){
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