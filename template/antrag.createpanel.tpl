 <?php

global $formulare;

$menu = [];

foreach ($formulare as $type => $list) {
  foreach ($list as $revision => $lForm) {
    if (hasPermission($lForm, null, "canCreate")) continue;
    unset($list[$revision]);
  }
  if (count($list) == 0) continue;

  $classConfig = getFormClass($type);
  if (isset($classConfig["buildFrom"])) continue;

  $title = $type;
  if (isset($classConfig["title"]))
    $title = $classConfig["title"];

  $submenu = [];
  foreach ($list as $revision => $lForm) {
    if ($revision == "_class") continue;
    $rtitle = $revision;
    if (isset($lForm["config"]["revisionTitle"]))
      $rtitle = $lForm["config"]["revisionTitle"];
    $submenu[$revision] = [ "value" => $revision, "text" => $rtitle, "submenu" => [] ];
  }

  $menu[] = [ "value" => $type, "text" => $title, "submenu" => $submenu ];
}

if (count($menu) == 0)
  return;

?>
<div class="panel panel-default">
  <div class="panel-heading">Neuen Antrag erstellen</div>
  <div class="panel-body">
    <form action="<?php echo $URIBASE; ?>?tab=antrag.create" method="POST" role="form" data-toggle="validator" class="form-inline">
      <div class="form-group">
        <label class="sr-only" for="newantragtype">Antrag</label>
        <select class="selectpicker form-control" name="type" size="1" data-dep="revisionselect" title="Bitte Antragstyp auswählen..." required="required" id="newantragtype">
<?php
foreach ($menu as $m) {
  echo "          <option value=\"".htmlspecialchars($m["value"])."\" data-dep=\"".htmlspecialchars(json_encode($m["submenu"]))."\">".htmlspecialchars($m["text"])."</option>\n";
}
?>
        </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
      <div class="form-group optional-select">
        <label class="sr-only" for="revisionselect">Version</label>
        <select class="selectpicker form-control" name="revision" size="1" title="2. Bitte Revision des neuen Antrags auswählen..." id="revisionselect" required="required"> </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
      <input type="submit" name="absenden" value="Antrag erstellen" class="btn btn-primary pull-right">
    </form>
  </div>
</div>

<?php
# vim:syntax=php
