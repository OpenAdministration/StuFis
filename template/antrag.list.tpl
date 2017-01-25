<div class="well">
<form action="<?php echo $URIBASE; ?>?tab=antrag.create" method="POST">
<div class="col-xs-4">
<select class="selectpicker form-control" name="type" size="1" data-dep="revisionselect" title="Bitte auswählen">
<?php
  global $formulare;
  foreach ($formulare as $type => $list) {
    echo "<option value=\"".htmlspecialchars($type)."\" data-dep=\"".htmlspecialchars(json_encode(array_keys($list)))."\">".htmlspecialchars($type)."</option>\n";
  }
?>
</select>
</div> <!-- col-xs -->
<div class="col-xs-4">
<select class="selectpicker form-control" name="revision" size="1" title="Bitte auswählen" id="revisionselect">
</select>
</div> <!-- col-xs -->
<div class="col-xs-4">
<input type="submit" name="absenden" value="Antrag erstellen" class="form-control btn-primary">
</div> <!-- col-xs -->
</form>
<div class="clearfix"></div></div>

<table class="table table-striped">

<thead>

<tr><th>ID</th><th>Ersteller</th><th>Status</th><th>letztes Update</th><th>Token</th></tr>

</thead>
<tbody>

<?php

foreach ($antraege as $type => $l0) {
  foreach ($l0 as $revision => $l1) {
    echo "<tr><th colspan=\"4\">".htmlspecialchars("{$type} - {$revision}")."</th></tr>\n";
    foreach ($l1 as $antrag) {
      echo "<tr>";
      echo "<td>".htmlspecialchars($antrag["id"])."</td>";
      echo "<td>".htmlspecialchars($antrag["creator"])."</td>";
      echo "<td>".htmlspecialchars($antrag["state"])."</td>";
      echo "<td>".htmlspecialchars($antrag["lastupdated"])."</td>";
      echo "<td><a href=\"{$URIBASE}/".htmlspecialchars($antrag["token"])."\">".htmlspecialchars($antrag["token"])."</a></td>";
      echo "</tr>";
    }
  }
}
?>

</tbody>
</table>

<?php

# vim:syntax=php
