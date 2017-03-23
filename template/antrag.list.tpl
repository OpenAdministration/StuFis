<?php

$tabList = [
  "need-action" => [
    "title" => "zu erledigen",
    "category" => [ "need-action" => null, ],
    "showIfEmpty" => true,
  ],
  "need-booking" => [
    "title" => "zu buchen",
    "category" => [ "need-booking" => null, ],
    "otherTemplate" => "antrag.actions",
    "showIfGroup" => [ "ref-finanzen" ],
  ],
  "need-payment" => [
    "title" => "zu bezahlen",
    "category" => [ "need-payment" => null, ],
    "showIfGroup" => [ "ref-finanzen" ],
  ],
  "stura" => [
    "title" => "StuRa-Sitzung",
    "category" => [ "wait-stura" => "durch StuRa beschließen",
                    "report-stura" => "im StuRa berichten", ],
    "wiki" => [
      "report-stura" => "{{template>:vorlagen:stimmen|Titel=Der Haushaltsverantwortliche beschließt ein Budget in Höhe von %betrag% für das Projekt %caption% von %gremium%.|J=|N=|E=|S=angenommen oder abgelehnt}}",
      "wait-stura"   => "{{template>:vorlagen:stimmen|Titel=Der Studierendenrat beschließt ein Budget in Höhe von %betrag% für das Projekt %caption% von %gremium%.|J=|N=|E=|S=angenommen oder abgelehnt}}",
    ],
    "showIfEmptyGroup" => [ "stura", "ref-finanzen" ],
  ],
  "running-project" => [
    "title" => "laufende Projekte",
    "category" => [ "running-project" => null, ],
    "showIfEmpty" => true,
  ],
  "expired-project" => [
    "title" => "abgelaufende Projekte",
    "category" => [ "expired-project" => null, ],
    "showIfEmpty" => true,
  ],
  "wait-action" => [
    "title" => "wartet",
    "category" => [ "wait-action" => null, ],
  ],
  "finished" => [
    "title" => "erledigt",
    "category" => [ "finished" => null, ],
  ],
  "plan" => [
    "title" => "HHP/KP",
    "category" => [ "plan" => null, ],
    "showIfEmpty" => true,
  ],
  "all" => [
    "title" => "alle",
    "category" => [ "all" => null, ],
  ],
];

?>

<ul class="nav nav-tabs" role="tablist">
<?php
$activeTab = false;

$usedCats = array_keys($antraege);
$tabHead = [];

foreach ($tabList as $tabId => $tabDesc) {

  if ($activeTab === false)
    $activeTab = $cat;

  $num = 0;
  foreach ($tabDesc["category"] as $catId => $catName) {
    if (!isset($antraege[$catId])) continue;
    foreach ($antraege[$catId] as $type => $l1) {
      foreach ($l1 as $revision => $l2) {
        $num += count($l2);
      }
    } 
  }
  $showIfEmpty = isset($tabDesc["showIfEmpty"]) ? $tabDesc["showIfEmpty"] : false;
  if (isset($tabDesc["showIfEmptyGroup"])) {
    foreach ($tabDesc["showIfEmptyGroup"] as $grp)
      $showIfEmpty |= hasGroup($grp);
  }
  if ($num == 0 && !$showIfEmpty) continue;
  $tabDesc["_num"] = $num;
  $tabHead[$tabId] = $tabDesc;
  $usedCats = array_diff($usedCats, array_keys($tabDesc["category"]));
}

foreach ($usedCats as $catId) {
  if (substr($catId,0,1) == "_") continue;

  $num = 0;
  foreach ($antraege[$catId] as $type => $l1) {
    foreach ($l1 as $revision => $l2) {
      $num += count($l2);
    }
  }
  if ($num == 0) continue;

  $tabId = "auto-$catId";
  if ($activeTab === false)
    $activeTab = $tabId;

  $title = "{$catId}";
  $tabHead[$tabId] = [ "title" => $title, "category" => [ $catId => null ], "_num" => $num ];
}

foreach ($tabHead as $tabId => $tabDesc) {
  $title = $tabDesc["title"];
  $num = $tabDesc["_num"];

  echo '<li'.($activeTab == $tabId ? ' class="active"':'').'><a data-toggle="tab" href="#'.htmlspecialchars($tabId).'">'.htmlspecialchars($title).' ('.$num.')</a></li>';
}
?>
</ul>

<div class="tab-content">
<?php

foreach ($tabHead as $tabId => $tabDesc) {
  $title = $tabDesc["title"];
  $num = $tabDesc["_num"];

  echo '<div id="'.htmlspecialchars($tabId).'" class="tab-pane fade'.($activeTab == $tabId ? ' in active':'').'" role="tabpanel">';

  if (isset($tabDesc["otherTemplate"])) {
    include "../template/{$tabDesc["otherTemplate"]}.tpl";
  } else if ($num > 0) {
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

  foreach ($tabDesc["category"] as $catId => $caption) {
    if (!isset($antraege[$catId])) continue;
    foreach ($antraege[$catId] as $type => $l1) {
      $classConfig = getFormClass($type);
      if ($classConfig === false) continue;
  
      $classTitle = "{$type}";
      if (isset($classConfig["title"]))
        $classTitle = "[{$type}] {$classConfig["title"]}";
      $title = "{$classTitle}";
  
      echo "    <tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";
  
      foreach ($l1 as $revision => $l2) {
        $revConfig = getFormConfig($type, $revision);
        if ($revConfig === false) continue;
  
        if (!isset($revConfig["captionField"]))
          $revConfig["captionField"] = [];
        if (!is_array($revConfig["captionField"]))
          $revConfig["captionField"] = [ $revConfig["captionField"] ];
  
        foreach ($l2 as $i => $antrag) {
          echo "    <tr>";
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
          echo "</tr>\n";

          if (isset($tabDesc["wiki"]) && isset($tabDesc["wiki"][$catId])) {
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
            $map = [ "%betrag%" => $betrag, "%caption%" => $caption, "%gremium" => $gremium ];
            $wikiBeschlussliste[] = str_replace(array_keys($map), array_values($map), $tabDesc["wiki"][$catId]);
          }
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

} /* if num > 0 */

?>

</div> <!-- tab -->
<?php
}
?>
</div> <!-- tab-content -->
<?php

# vim:syntax=php
