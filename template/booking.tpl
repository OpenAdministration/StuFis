<?php
global $URIBASE, $nonce;
?>

    <div class="col-md-10 container main">

    <div class="alert alert-info">
        Bitte wähle nur eine Zahlung oder nur einen Grund und dann beliebig viele zugehörige Gründe bzw. Zahlungen aus.
    </div>
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax d-inline-block">
        <button type="submit" name="absenden" class="btn btn-primary"><i class="fa fa-fw fa-refresh"></i> neue
            Kontoauszüge
            abrufen
        </button>
        <input type="hidden" name="action" value="hibiscus">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    </form>
    <a href="<?php echo $URIBASE; ?>?tab=booking.history" class="btn btn-primary"><i class="fa fa-fw fa-list "></i>
        Buchungsübersicht</a>
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
        <input type="hidden" name="action" value="booking">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
        <?php //var_dump($alZahlung);?>
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
                        $value = min([$alZahlung[$idxZahlung]["_value"], $alGrund[$idxGrund]["_value"]]);
                    }else{
                        $value = $alZahlung[$idxZahlung]["_value"];
                    }
                }else{
                    $value = $alGrund[$idxGrund]["_value"];
                }
            
                echo "<td>";
                while (isset($alZahlung[$idxZahlung]) && $alZahlung[$idxZahlung]["_value"] === $value){
                    echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"" . $value . "\" name=\"zahlungId[]\" value=\"" . htmlspecialchars($alZahlung[$idxZahlung]["id"]) . "\">";
                    $revConfig = $alZahlung[$idxZahlung]["_form"]["config"];
                    $caption = getAntragDisplayTitle($alZahlung[$idxZahlung], $revConfig);
                    $caption = trim(implode(" ", $caption));
                    //$caption = "id";
                    $url = str_replace("//", "/", $URIBASE . "/" . $alZahlung[$idxZahlung]["token"]);
                    echo "<a href=\"" . htmlspecialchars($url) . "\">" . $caption . "</a>";
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

                            foreach ($alZahlung as $a) {
                                $value = $a["_value"];
                                $fvalue = convertDBValueToUserValue($value, "money");

                                echo "<tr>";
                                echo "<td>";
                                echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"".$fvalue."\" name=\"zahlungId[]\" value=\"".htmlspecialchars($a["id"])."\">";
                                echo "</td>";
                                echo "<td class=\"text-right nowrap\">";
                                echo htmlspecialchars($fvalue." €");
                                echo "<input type=\"hidden\" name=\"zahlungValue[".$a["id"]."]\" value=\"$value\">";
                                echo "</td>";
                                echo "<td valign=\"top\">";
                                echo $a["id"];
                                echo "</td>";
                                $revConfig = $a["_form"]["config"];
                                $caption = getAntragDisplayTitle($a, $revConfig);
                                $caption = trim(implode(" ", $caption));
                                $url = str_replace("//","/", $URIBASE."/".$a["token"]);
                                echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
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

                            foreach ($alGrund as $a) {
                                $value = $a["_value"];
                                $fvalue = convertDBValueToUserValue($value, "money");

                                echo "<tr>";
                                echo "<td>";
                                echo "<input type=\"checkbox\" class=\"checkbox-summing\" data-summing-value=\"".$fvalue."\" name=\"grundId[]\" value=\"".htmlspecialchars($a["id"])."\">";
                                echo "</td>";
                                echo "<td class=\"text-right nowrap\">";
                                echo htmlspecialchars($fvalue." €");
                                echo "<input type=\"hidden\" name=\"grundValue[".$a["id"]."]\" value=\"$value\">";
                                echo "</td>";
                                echo "<td valign=\"top\">";
                                echo $a["id"];
                                echo "</td>";
                                $revConfig = $a["_form"]["config"];
                                $caption = getAntragDisplayTitle($a, $revConfig);
                                $caption = trim(implode(" ", $caption));
                                $url = str_replace("//","/", $URIBASE."/".$a["token"]);
                                echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
                                echo "</tr>";

                            }

                            ?>

                        </tbody>
                    </table>

                    </td></tr>
            </tbody>
        </table>-->

        <div style="height:5cm;">&nbsp;</div>

        <nav class="navbar navbar-default navbar-fixed-bottom"
             <?php
             global $DEV;
             if ($DEV)
                 echo " style=\"background-color:darkred;\"";
             ?>
             role="navigation">
            <div class="container">
                <input type="submit" name="absenden" value="ausgewählte Buchungen zuordnen" class="btn btn-primary navbar-right navbar-btn">
            </div><!-- /.container -->
        </nav>

    </form>

    <?php
    # vim: set syntax=php:

