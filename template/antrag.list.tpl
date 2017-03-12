<?php

$catList = [
  "need-action" => "zu erledigen",
  "need-booking" => "zu buchen",
  "need-payment" => "zu bezahlen",
  "wait-stura" => "durch StuRa beschließen",
  "report-stura" => "im StuRa berichten",
  "running-project" => "laufende Projekte",
  "expired-project" => "abgelaufende Projekte",
  "wait-action" => "wartet",
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
    foreach ($l1 as $revision => $l2) {
      $num += count($l2);
    }
  }

  echo '<li'.($activeCat == $cat ? ' class="active"':'').'><a data-toggle="tab" href="#'.htmlspecialchars($cat).'">'.htmlspecialchars($title).' ('.$num.')</a></li>';
}
?>
</ul>

<div class="tab-content">
<?php

foreach ($antraege as $cat => $l0) {
  if (substr($cat,0,1) == "_") continue;

  $title = "{$cat}";
  echo '<div id="'.htmlspecialchars($cat).'" class="tab-pane fade'.($activeCat == $cat ? ' in active':'').'">';


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
  $wikiBeschlussliste = [];

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
        if ($cat == "report-stura" || $cat == "wait-stura") {
          $gremium = getAntragDisplayTitle($antrag, $revConfig, [ "projekt.org.name" ]);
          $gremium = trim(implode(" ", $gremium));

          $ctrl = ["_values" => $antrag, "render" => [ "no-form"] ];
          $form = getForm($type, $revision);
          ob_start();
          $success = renderFormImpl($form, $ctrl);
          ob_end_clean();
          $betrag = "XXX EUR";
          if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["ausgaben"])) {
            $value = $ctrl["_render"]->addToSumValue["ausgaben"];
            $value = number_format($value, 2, ".", "");
            if (isset($ctrl["_render"]->addToSumMeta["ausgaben"])) {
              $newMeta = $ctrl["_render"]->addToSumMeta["ausgaben"];

              unset($newMeta["addToSum"]);
              if (isset($newMeta["width"]))
                unset($newMeta["width"]);
              if (isset($newMeta["editWidth"]))
                unset($newMeta["editWidth"]);

              $newMeta["value"] = $value;

              if (isset($newMeta["printSum"]))
                unset($newMeta["printSum"]);
              if (isset($newMeta["printSumDefer"]))
                unset($newMeta["printSumDefer"]);
  
              $newCtrl = $ctrl;
              $newCtrl["suffix"][] = "listing";
              $newCtrl["render"][] = "no-form";
              $newCtrl["render"][] = "no-form-markup";
              unset($newCtrl["_values"]);
              ob_start();
              renderFormItem($newMeta, $newCtrl);
              $betrag = ob_get_contents();
              ob_end_clean();
              $betrag = processTemplates($betrag, $newCtrl);
            } else {
              $betrag = $value;
            }
          }
          if ($cat == "report-stura")
            $wikiBeschlussliste[] = "{{template>:vorlagen:stimmen|Titel=Der Haushaltsverantwortliche beschließt ein Budget in Höhe von $betrag für das Projekt \"{$caption}\" von $gremium.|J=|N=|E=|S=angenommen oder abgelehnt}}";

          if ($cat == "wait-stura")
            $wikiBeschlussliste[] = "{{template>:vorlagen:stimmen|Titel=Der Studierendenrat beschließt ein Budget in Höhe von $betrag für das Projekt \"{$caption}\" von $gremium.|J=|N=|E=|S=angenommen oder abgelehnt}}";
          
        }
      }
    }
  }
?>
  </tbody>
</table>

<?php

if (count($wikiBeschlussliste) > 0) {
  echo "<pre>";
  echo strip_tags(implode("\n", $wikiBeschlussliste));
  echo "</pre>";
}

?>

</div>
<?php
}

# vim:syntax=php
