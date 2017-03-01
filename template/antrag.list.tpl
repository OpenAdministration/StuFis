<?php

$catList = [
  "need-action" => "zu erledigen",
  "wait-action" => "wartet",
  "report-stura" => "im StuRa berichten",
  "running-project" => "laufende Projekte",
  "expired-project" => "abgelaufende Projekte",
  "finished" => "erledigt",
  "plan" => "HHP/KP",
  "all" => "alle",
];

?>

<ul class="nav nav-tabs">
<?php
$activeCat = false;

$usedCats = array_keys($antraege);
$availCats = array_keys($catList);
$orderedCats = array_merge(array_intersect($availCats, $usedCats), array_diff($usedCats, $availCats));

foreach ($orderedCats as $cat) {
  if (substr($cat,0,1) == "_") continue;

  if ($activeCat === false)
    $activeCat = $cat;

  $title = "{$cat}";
  if (isset($catList[$cat]))
    $title = $catList[$cat];

  $num = 0;
  foreach ($antraege[$cat] as $type => $l1) {
    $num += count($l1);
  }

  echo '<li'.($activeCat == $cat ? ' class="active"':'').'><a data-toggle="tab" href="#'.md5($cat).'">'.htmlspecialchars($title).' ('.$num.')</a></li>';
}
?>
</ul>

<div class="tab-content">
<?php

foreach ($antraege as $cat => $l0) {
  if (substr($cat,0,1) == "_") continue;

  $title = "{$cat}";
  echo '<div id="'.md5($cat).'" class="tab-pane fade'.($activeCat == $cat ? ' in active':'').'">';


?>

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
  foreach ($l0 as $type => $l1) {
    $classConfig = getFormClass($type);
    if ($classConfig === false) continue;

    $classTitle = "{$type}";
    if (isset($classConfig["title"]))
      $classTitle = "[{$type}] {$classConfig["title"]}";
    $title = "{$classTitle}";

    echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";

    foreach ($l1 as $revision => $l2) {
      $revConfig = getFormConfig($type, $revision);
      if ($revConfig === false) continue;

      if (!isset($revConfig["captionField"]))
        $revConfig["captionField"] = [];
      if (!is_array($revConfig["captionField"]))
        $revConfig["captionField"] = [ $revConfig["captionField"] ];

      foreach ($l2 as $i => $antrag) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($antrag["id"])."</td>";
        $caption = getAntragDisplayTitle($antrag, $revConfig);
        $caption = trim(implode(" ", $caption));
        $url = str_replace("//","/", $URIBASE."/".$antrag["token"]);
        echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
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
          $txt = $classConfig["state"][$antrag["state"]][0];
        $txt .= " (".$antrag["stateCreator"].")";
        echo htmlspecialchars($txt);
        echo "</td>";
        echo "<td>".htmlspecialchars($antrag["lastupdated"])."</td>";
        echo "</tr>";
      }
    }
  }
?>
  </tbody>
</table>
</div>
<?php
}

# vim:syntax=php
