<?php
global $URIBASE, $nonce, $HIBISCUSGROUP;

AuthHandler::getInstance()->requireGroup($HIBISCUSGROUP);
if (isset($_GET["id"]))
    $selected_id = $_GET["id"];

?>
<div class="col-md-10 col-xs-12 container main">
    <?php
    $hhps = DBConnector::getInstance()->dbFetchAll("antrag", [], ["type" => "haushaltsplan"], [], ["lastupdated" => 0], true, true);
    if (!isset($selected_id)){
        foreach (array_reverse($hhps, true) as $id => $hhp){
            if ($hhp["state"] === "final"){
                $selected_id = $id;
            }
        }
    }
    
    $year = $hhps[$selected_id]["revision"];
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $alZahlung = DBConnector::getInstance()->dbFetchAll("konto", [], ["date" => ["BETWEEN", [$startDate, $endDate]]], [], ["value" => true]);
    $alGrund = [];
    
    ?>
    <form>
        <div class="input-group col-xs-2 pull-right">
            <!--<input type="number" class="form-control" name="year" value=<?= date("Y") ?>>-->
            <input type="hidden" name="tab" value="booking">
            <select class="selectpicker" name="id"><?php
                foreach ($hhps as $id => $hhp){
                    ?>
                    <option value="<?= $id ?>" <?= $id == $selected_id ? "selected" : "" ?>
                            data-subtext="<?= getStateString($hhp["type"], $hhp["revision"], $hhp["state"]) ?>"><?= $hhp["revision"] ?>
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
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax d-inline-block">
        <button type="submit" name="absenden" class="btn btn-primary"><i class="fa fa-fw fa-refresh"></i> neue
            Kontoauszüge
            abrufen
        </button>
        <input type="hidden" name="action" value="hibiscus">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    </form>
    <a href="<?php echo $URIBASE; ?>menu/booking-history" class="btn btn-primary"><i class="fa fa-fw fa-list "></i>
        Buchungsübersicht</a>
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
        <input type="hidden" name="action" value="booking">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
        <?php //var_dump($alZahlung[0]);?>
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
                        $value = min([floatval($alZahlung[$idxZahlung]["value"]), $alGrund[$idxGrund]["_value"]]);
                    }else{
                        //var_dump($alZahlung[$idxZahlung]);
                        $value = floatval($alZahlung[$idxZahlung]["value"]);
                    }
                }else{
                    $value = $alGrund[$idxGrund]["_value"];
                }
    
                echo "<td>";
                while (isset($alZahlung[$idxZahlung]) && floatval($alZahlung[$idxZahlung]["value"]) === $value){
                    echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"" . $value . "\" name=\"zahlungId[]\" value=\"" . htmlspecialchars($alZahlung[$idxZahlung]["id"]) . "\">";
                    $caption = "";
                    $title = $alZahlung[$idxZahlung]["valuta"] . " - " . $alZahlung[$idxZahlung]["empf_iban"] .
                        PHP_EOL . $alZahlung[$idxZahlung]["zweck"];
                    //print_r($alZahlung[$idxZahlung]);
                    switch ($alZahlung[$idxZahlung]["type"]){
                        case "FOLGELASTSCHRIFT":
                            $caption = "LASTSCHRIFT an ";
                            break;
                        case "ONLINE-UEBERWEISUNG":
                            $caption .= "ÜBERWEISUNG an ";
                            break;
                        case "GUTSCHRIFT":
                            $caption = "GUTSCHRIFT von ";
                            break;
                        default: //Buchung, Entgeldabschluss,...
                            $caption = $alZahlung[$idxZahlung]["type"] . " an ";
                            break;
            
                    }
                    $caption .= $alZahlung[$idxZahlung]["empf_name"];
                    $url = str_replace("//", "/", $URIBASE . "/zahlung/" . $alZahlung[$idxZahlung]["id"]);
                    echo "<a href='" . htmlspecialchars($url) . "' title='" . htmlspecialchars($title) . "'>" . htmlspecialchars($caption) . "</a>";
                    $idxZahlung++;
                    echo "<br>";
                }
                echo "</td><td>";
                echo convertDBValueToUserValue($value, "money") . " €";
                echo "</td><td>";
                while (isset($alGrund[$idxGrund]) && $alGrund[$idxGrund]["_value"] === $value){
                    echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"" . $value . "\" name=\"zahlungId[]\" value=\"" . htmlspecialchars($alGrund[$idxGrund]["id"]) . "\">";
                    $revConfig = $alGrund[$idxGrund]["_form"]["config"];
                    $caption = getAntragDisplayTitle($alGrund[$idxGrund], $revConfig);
                    $caption = trim(implode(" ", $caption));
                    //$caption = "id";
                    $url = str_replace("//", "/", $URIBASE . "/" . $alGrund[$idxGrund]["token"]);
                    echo "<a href=\"" . htmlspecialchars($url) . "\">" . $caption . "</a>";
                    $idxGrund++;
                    echo "<br>";
                }
                echo "</td>";
                echo "</tr>";
            }

            ?>
        </table>


        <!--<table>
            <thead><tr><th class="text-center col-xs-6">Zahlungen</th><th class="text-center col-xs-6">Zahlungsgründe</th></tr></thead>
            <tbody>
                <tr><td valign="top">

                    <div>Ausgewählte Summe: <span class="checkbox-summing-output">0,00</span> €</div>

                    <table class="table table-striped checkbox-summing">
                        <thead><tr><th></th><th class="col-xs-2">Betrag</th><th>ID</th><th>Verwendungszweck</th></tr></thead>
                        <tbody>
                            <?php

        foreach ($alZahlung as $a){
            $value = $a["_value"];
            $fvalue = convertDBValueToUserValue($value, "money");
    
            echo "<tr>";
            echo "<td>";
            echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"" . $fvalue . "\" name=\"zahlungId[]\" value=\"" . htmlspecialchars($a["id"]) . "\">";
            echo "</td>";
            echo "<td class=\"text-right nowrap\">";
            echo htmlspecialchars($fvalue . " €");
            echo "<input type=\"hidden\" name=\"zahlungValue[" . $a["id"] . "]\" value=\"$value\">";
            echo "</td>";
            echo "<td valign=\"top\">";
            echo $a["id"];
            echo "</td>";
            $revConfig = $a["_form"]["config"];
            $caption = getAntragDisplayTitle($a, $revConfig);
            $caption = trim(implode(" ", $caption));
            $url = str_replace("//", "/", $URIBASE . "/" . $a["token"]);
            echo "<td><a href=\"" . htmlspecialchars($url) . "\">" . $caption . "</a></td>";
            echo "</tr>";
    
        }

        ?>

                        </tbody>
                    </table>


                    </td><td valign="top">

                    <div>Ausgewählte Summe: <span class="checkbox-summing-output">0,00</span> €</div>

                    <table class="table table-striped checkbox-summing">
                        <thead><tr><th></th><th class="col-xs-2">Betrag</th><th>ID</th><th>Verwendungszweck</th></tr></thead>
                        <tbody>
                            <?php

        foreach ($alGrund as $a){
            $value = $a["_value"];
            $fvalue = convertDBValueToUserValue($value, "money");
    
            echo "<tr>";
            echo "<td>";
            echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"" . $fvalue . "\" name=\"grundId[]\" value=\"" . htmlspecialchars($a["id"]) . "\">";
            echo "</td>";
            echo "<td class=\"text-right nowrap\">";
            echo htmlspecialchars($fvalue . " €");
            echo "<input type=\"hidden\" name=\"grundValue[" . $a["id"] . "]\" value=\"$value\">";
            echo "</td>";
            echo "<td valign=\"top\">";
            echo $a["id"];
            echo "</td>";
            $revConfig = $a["_form"]["config"];
            $caption = getAntragDisplayTitle($a, $revConfig);
            $caption = trim(implode(" ", $caption));
            $url = str_replace("//", "/", $URIBASE . "/" . $a["token"]);
            echo "<td><a href=\"" . htmlspecialchars($url) . "\">" . $caption . "</a></td>";
            echo "</tr>";
    
        }

        ?>

                        </tbody>
                    </table>

                    </td></tr>
            </tbody>
        </table>-->

        <div style="height:5cm;">&nbsp;</div>

        <!--<nav class="navbar navbar-default navbar-fixed-bottom"
             <?php
        global $DEV;
        if ($DEV)
            echo " style=\"background-color:darkred;\"";
        ?>
             role="navigation">
            <div class="container">
                <input type="submit" name="absenden" value="ausgewählte Buchungen zuordnen" class="btn btn-primary navbar-right navbar-btn">
            </div>
        </nav>-->

    </form>
