<div class="well">
  <form action="<?php echo $URIBASE; ?>?tab=antrag.create" method="POST" role="form" data-toggle="validator">
    <div class="col-xs-4">
      <div class="form-group">
        <select class="selectpicker form-control" name="type" size="1" data-dep="revisionselect" title="Bitte auswählen" required="required">
<?php
  global $formulare;
  foreach ($formulare as $type => $list) {
    echo "          <option value=\"".htmlspecialchars($type)."\" data-dep=\"".htmlspecialchars(json_encode(array_keys($list)))."\">".htmlspecialchars($type)."</option>\n";
  }
?>
        </select>
        <div class="help-block with-errors"></div>
      </div> <!-- form-group -->
    </div> <!-- col-xs -->
    <div class="col-xs-4">
      <div class="form-group">
        <select class="selectpicker form-control" name="revision" size="1" title="Bitte auswählen" id="revisionselect" required="required">
        </select>
        <div class="help-block with-errors"></div>
      </div> <!-- form-group -->
    </div> <!-- col-xs -->
    <div class="col-xs-4">
      <input type="submit" name="absenden" value="Antrag erstellen" class="form-control btn-primary">
    </div> <!-- col-xs -->
  </form>
  <div class="clearfix"></div>
</div>

<table class="table table-striped">

<thead>

<tr><th>ID</th><th>Bezeichnung</th><th>Ersteller</th><th>Status</th><th>letztes Update</th></tr>

</thead>
<tbody>

<?php

foreach ($antraege as $type => $l0) {
  foreach ($l0 as $revision => $l1) {
    $config = getFormConfig($type, $revision);
    if ($config === false) continue;
    if (isset($config["title"]))
      $title = "[{$type}] {$config["title"]} - {$revision}";
    else
      $title = "{$type} - {$revision}";
    if (!isset($config["caption-field"]))
      $config["caption-field"] = [];
    if (!is_array($config["caption-field"]))
      $config["caption-field"] = [ $config["caption-field"] ];
    echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";
    foreach ($l1 as $i => $antrag) {
      echo "<tr>";
      echo "<td>".htmlspecialchars($antrag["id"])."</td>";
      $caption = [ htmlspecialchars($antrag["token"]) ];
      if (count($config["caption-field"]) > 0) {
        if (!isset($antrag["_inhalt"])) {
          $antrag["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"] ]);
          $antraege[$type][$revision][$i] = $antrag;
        }
        foreach ($config["caption-field"] as $j => $fname) {
          $rows = getFormEntries($fname, null, $antrag["_inhalt"]);
          $row = count($rows) > 0 ? $rows[0] : false;
          if ($row !== false) {
            ob_start();
            $formlayout = [ [ "type" => $row["contenttype"], "id" => $fname ] ];
            renderForm($formlayout, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]] );
            $val = ob_get_contents();
            ob_end_clean();
            $caption[$j] = $val;
          }
        }
      }
      echo "<td><a href=\"{$URIBASE}/".htmlspecialchars($antrag["token"])."\">".implode(" ", $caption)."</a></td>";
      echo "<td>".htmlspecialchars($antrag["creator"])."</td>";
      echo "<td>".htmlspecialchars($antrag["state"])."</td>";
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
