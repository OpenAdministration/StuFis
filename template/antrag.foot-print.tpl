<?php

$classConfig = $form["_class"];
$revConfig = $form["config"];

?>
<div class="col-xs-12">

<hr class="blackhr"/>

<table class="table small-table table-condensed">

<thead>

<tr><th>ID</th><th>Bezeichnung</th><th>Ersteller</th><th>Status</th><th>letztes Update</th></tr>

</thead>
<tbody>

<?php

$classTitle = "{$form["type"]}";
if (isset($classConfig["title"]))
  $classTitle = "[{$form["type"]}] {$classConfig["title"]}";

$revTitle = "{$form["revision"]}";
if (isset($revConfig["revisionTitle"]))
  $revTitle = "[{$form["revision"]}] {$revConfig["revisionTitle"]}";

$title = "{$classTitle} - {$revTitle}";

if (!isset($revConfig["captionField"]))
  $revConfig["captionField"] = [];
if (!is_array($revConfig["captionField"]))
  $revConfig["captionField"] = [ $revConfig["captionField"] ];
echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";

echo "<tr>";
echo "<td>".htmlspecialchars($antrag["id"])."</td>";
$caption = getAntragDisplayTitle($antrag, $revConfig);
echo "<td><a href=\"{$URIBASEREF}{$URIBASE}/".htmlspecialchars($antrag["token"])."\">".implode(" ", $caption)."</a></td>";
echo "<td>";
 if (($antrag["creator"] == $antrag["creatorFullName"]) || empty($antrag["creatorFullName"])) {
   echo htmlspecialchars($antrag["creator"]);
 } else {
   echo "<span title=\"";
   echo htmlspecialchars($antrag["creator"]);
   echo "\">";
   echo htmlspecialchars($antrag["creatorFullName"]);
   echo "</span>";
 }
echo "</td>";
echo "<td>";
 $txt = $antrag["state"];
 if (isset($classConfig["state"]) && isset($classConfig["state"][$antrag["state"]]))
   $txt = $classConfig["state"][$antrag["state"]];
 $txt .= " (".$antrag["stateCreator"].")";
 echo htmlspecialchars($txt);
echo "</td>";
echo "<td>".htmlspecialchars($antrag["lastupdated"])."</td>";
echo "</tr>";
?>

</tbody>
</table>
</div>

<?php

# vim:syntax=php
