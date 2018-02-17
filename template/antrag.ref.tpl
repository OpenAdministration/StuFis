<?php

if (count($antraegeRef) == 0) return;

?>

<div class="panel panel-default">
    <div class="panel-heading">Verweise auf dieses Formular</div>
    <div class="panel-body">

        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Bezeichnung</th>
                <th>Ersteller</th>
                <th>Status</th>
                <th>letztes Update</th>
            </tr>
            </thead>
            <tbody>
            <?php
            
            foreach ($antraegeRef as $type => $l0){
                foreach ($l0 as $revision => $l1){
                    $classConfig = getFormClass($type);
                    $revConfig = getFormConfig($type, $revision);
                    if ($classConfig === false) continue;
                    if ($revConfig === false) continue;
                    
                    $classTitle = "{$type}";
                    if (isset($classConfig["title"]))
                        $classTitle = "[{$type}] {$classConfig["title"]}";
                    
                    $revTitle = "{$revision}";
                    if (isset($revConfig["revisionTitle"]))
                        $revTitle = "[{$revision}] {$revConfig["revisionTitle"]}";
                    
                    $title = "{$classTitle} - {$revTitle}";
                    
                    if (!isset($revConfig["captionField"]))
                        $revConfig["captionField"] = [];
                    if (!is_array($revConfig["captionField"]))
                        $revConfig["captionField"] = [$revConfig["captionField"]];
                    echo "<tr><th colspan=\"5\">" . htmlspecialchars($title) . "</th></tr>\n";
                    foreach ($l1 as $i => $a){
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($a["id"]) . "</td>";
                        $caption = getAntragDisplayTitle($a, $revConfig);
                        $url = str_replace("//", "/", $URIBASE . "/" . $a["token"]);
                        echo "<td><a href=\"" . htmlspecialchars($url) . "\">" . implode(" ", $caption) . "</a></td>";
                        echo "<td>";
                        if (($a["creator"] == $a["creatorFullName"]) || empty($a["creatorFullName"])){
                            echo htmlspecialchars($a["creator"]);
                        }else{
                            echo "<span title=\"";
                            echo htmlspecialchars($a["creator"]);
                            echo "\">";
                            echo htmlspecialchars($a["creatorFullName"]);
                            echo "</span>";
                        }
                        echo "</td>";
                        echo "<td>";
                        $txt = $a["state"];
                        if (isset($classConfig["state"]) && isset($classConfig["state"][$a["state"]]))
                            $txt = $classConfig["state"][$a["state"]][0];
                        $txt .= " (" . $a["stateCreator"] . ")";
                        echo htmlspecialchars($txt);
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($a["lastupdated"]) . "</td>";
                        echo "</tr>";
                    }
                }
            }
            ?>
            </tbody>
        </table>

    </div>
</div>

<?php
# vim:syntax=php
