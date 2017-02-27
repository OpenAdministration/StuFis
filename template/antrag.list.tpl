<ul class="nav nav-tabs">
<?php
$activeType = false;
foreach ($antraege as $type => $l0) {
  if ($activeType === false)
    $activeType = $type;

  $classConfig = getFormClass($type);
  if ($classConfig === false) continue;

  $classTitle = "{$type}";
  if (isset($classConfig["title"]))
    $classTitle = "{$classConfig["title"]}";
  if (isset($classConfig["shortTitle"]))
    $classTitle = "{$classConfig["shortTitle"]}";

  $title = "{$classTitle}";
  echo '<li'.($activeType == $type ? ' class="active"':'').'><a data-toggle="tab" href="#'.md5($type).'">'.htmlspecialchars($title).'</a></li>';
}
?>
</ul>

<div class="tab-content">
<?php

foreach ($antraege as $type => $l0) {
  $classConfig = getFormClass($type);
  if ($classConfig === false) continue;

  $classTitle = "{$type}";
  if (isset($classConfig["title"]))
    $classTitle = "[{$type}] {$classConfig["title"]}";

  $title = "{$classTitle}";

  echo '<div id="'.md5($type).'" class="tab-pane fade'.($activeType == $type ? ' in active':'').'">';
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

  foreach ($l0 as $revision => $l1) {
    $revConfig = getFormConfig($type, $revision);
    if ($revConfig === false) continue;

    if (!isset($revConfig["captionField"]))
      $revConfig["captionField"] = [];
    if (!is_array($revConfig["captionField"]))
      $revConfig["captionField"] = [ $revConfig["captionField"] ];

    foreach ($l1 as $i => $antrag) {
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
?>
  </tbody>
</table>
</div>
<?php
}

# vim:syntax=php
