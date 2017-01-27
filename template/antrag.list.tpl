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

foreach ($antraege as $type => $l0) {
  foreach ($l0 as $revision => $l1) {
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
      $revConfig["captionField"] = [ $revConfig["captionField"] ];
    echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";
    foreach ($l1 as $i => $antrag) {
      echo "<tr>";
      echo "<td>".htmlspecialchars($antrag["id"])."</td>";
      $caption = [ htmlspecialchars($antrag["token"]) ];
      if (count($revConfig["captionField"]) > 0) {
        if (!isset($antrag["_inhalt"])) {
          $antrag["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"] ]);
          $antraege[$type][$revision][$i] = $antrag;
        }
        foreach ($revConfig["captionField"] as $j => $fname) {
          $rows = getFormEntries($fname, null, $antrag["_inhalt"]);
          $row = count($rows) > 0 ? $rows[0] : false;
          if ($row !== false) {
            ob_start();
            $formlayout = [ [ "type" => $row["contenttype"], "id" => $fname ] ];
            $form = [ "layout" => $formlayout, "config" => [] ];
            renderForm($form, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]] );
            $val = ob_get_contents();
            ob_end_clean();
            $caption[$j] = $val;
          }
        }
      }
      echo "<td><a href=\"{$URIBASE}/".htmlspecialchars($antrag["token"])."\">".implode(" ", $caption)."</a></td>";
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
<?php

# vim:syntax=php
