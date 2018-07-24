<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 29.06.18
 * Time: 03:34
 */

class HHPHandler extends Renderer{
    
    private $routeInfo;
    private $hhps;
    private $stateStrings;
    
    
    public function __construct($routeInfo){
        $this->routeInfo = $routeInfo;
        $this->stateStrings = [
            "draft" => "Entwurf",
            "final" => "Rechtskräftig",
        ];
    }
    
    function render(){
        $this->hhps = DBConnector::getInstance()->dbFetchAll(
            "haushaltsplan",
            ["id", "id", "haushaltsplan.*"],
            [],
            [],
            ["von" => true],
            true,
            true
        );
        switch ($this->routeInfo["action"]){
            case "pick":
                $this->renderHHPPicker();
                break;
            case "view":
                $this->renderHaushaltsplan();
                break;
            default:
                ErrorHandler::_renderError("Action in HHP '{$this->routeInfo["action"]}' not known");
                break;
        }
    }
    
    private function renderHHPPicker(){
        $this->renderHeadline("Haushalspläne");
        $obj = $this;
        $this->renderTable(
            ["Id", "von", "bis", "Status"],
            [$this->hhps],
            [
                function($id){
                    return "<a href='hhp/$id'><i class='fa fa-fw fa-chain'></i>&nbsp;HP-$id</a>";
                },
                [$this, "formatDateToMonthYear"],
                [$this, "formatDateToMonthYear"],
                function($stateString) use ($obj){
                    return "<div class='label label-info'>" . htmlspecialchars($obj->stateStrings[$stateString]) . "</div>";
                }
            ]
        );
        
    }
    
    public function renderHaushaltsplan(){
        
        $hhp_id = $this->routeInfo["hhp-id"];
        if (!isset($this->hhps[$hhp_id])){
            ErrorHandler::_renderError("Haushaltsplan HP-$hhp_id ist nicht bekannt.");
            return;
        }
        $hhp = $this->hhps[$hhp_id];
        $groups = DBConnector::getInstance()->dbgetHHP($hhp_id);
        //var_dump($groups);
        ?>
        <h1>
            Haushaltsplan seit <?= $this->formatDateToMonthYear($hhp["von"]) ?></h1>
        <table class="table table-striped">
            <?php
            $group_nr = 1;
            $type = 0;
            foreach ($groups as $group){
                if (count($group) === 0) continue;
                if ($type !== array_values($group)[0]["type"])
                    $group_nr = 1;
                
                $type = array_values($group)[0]["type"];
                ?>
                <thead>
                <tr>
                    <th class="bg-info"
                        colspan="42"><?= ($type + 1) . "." . $group_nr++ . " " . array_values($group)[0]["gruppen_name"] ?></th>
                </tr>
                <tr>
                    <th></th>
                    <th>Titelnr</th>
                    <th>Titelname</th>
                    <th class="money"><?= "soll-" . (array_values($group)[0]["type"] == 0 ? "Einnahmen" : "Ausgaben") ?></th>
                    <th class="money"><?= "ist-" . (array_values($group)[0]["type"] == 0 ? "Einnahmen" : "Ausgaben") . " (gebucht)" ?></th>
                    <th class="money"><?= "ist-" . (array_values($group)[0]["type"] == 0 ? "Einnahmen" : "Ausgaben") . " (beschlossen)" ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $gsum_soll = 0;
                $gsum_ist = 0;
                foreach ($group as $row){
                    if (!isset($row["_booked"]))
                        $row["_booked"] = 0;
                    $gsum_soll += $row["value"];
                    $gsum_ist += $row["_booked"];
                    ?>
                    <tr>
                        <td></td>
                        <td><?= $row["titel_nr"] ?></td>
                        <td><?= $row["titel_name"] ?></td>
                        <td class="money"><?= DBConnector::getInstance()->convertDBValueToUserValue($row["value"], "money") ?></td>
                        <td class="money <?= $this->checkTitelBudget($row["value"], $row["_booked"]) ?>">
                            <?= DBConnector::getInstance()->convertDBValueToUserValue($row["_booked"], "money") ?>
                        </td>
                    </tr>
                    
                    
                    <?php
                } ?>
                <tr class="table-sum-footer">
                    <td colspan="3"></td>
                    <td class="money table-sum-hhpgroup"><?= DBConnector::getInstance()->convertDBValueToUserValue($gsum_soll, "money") ?></td>
                    <td class="money table-sum-hhpgroup"><?= DBConnector::getInstance()->convertDBValueToUserValue($gsum_ist, "money") ?></td>
                </tr>
                </tbody>
                
                <?php
            } ?>

        </table>
        <?php
        return;
    }
    
    /**
     * @param $should
     * @param $is
     *
     * @return string
     */
    private function checkTitelBudget($should, $is){
        if ($is > $should){
            if ($is > $should * 1.5){
                return "hhp-danger";
            }else{
                return "hhp-warning";
            }
        }else{
            return "";
        }
    }
    
    
}