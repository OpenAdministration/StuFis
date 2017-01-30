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
  if ($type != $antrag["type"]) {
    if (!isset($classConfig["buildFrom"])) continue;
    if (!in_array($antrag["type"], $classConfig["buildFrom"])) continue;
    if (!hasPermission($form, $antrag, "canBeLinked")) continue;
  }

  $title = $type;
  if (isset($classConfig["title"]))
    $title = $classConfig["title"];

  $submenu = [];
  foreach ($list as $revision => $lForm) {
    if ($revision == "_class") continue;
    $rtitle = $revision;
    if (isset($lForm["config"]["revisionTitle"]))
      $rtitle = $lForm["config"]["revisionTitle"];
    $submenu[$revision] = $rtitle;
  }

  $menu[] = [ "value" => $type, "text" => $title, "submenu" => $submenu ];
}

if (count($menu) == 0)
  return;

$classConfig = getFormClass($antrag["type"]);
if (!isset($classConfig["state"])) return;

$newStates = [];
foreach (array_keys($classConfig["state"]) as $newState) {
  $perm = "canStateChange.from.{$antrag["state"]}.to.{$newState}";
  if (!hasPermission($form, $antrag, $perm)) continue;
  $newStates[] = $newState;
}

?>
<div class="panel panel-default">
  <div class="panel-heading">Neuen Antrag erstellen und dabei Angaben aus diesem Antrag übernehmen</div>
  <div class="panel-body">
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
      <div class="form-group">
        <label class="sr-only" for="newantragtype">Antrag</label>
        <select class="selectpicker form-control" name="type" size="1" data-dep="revisionselect" title="1. Neuen Antrag auswählen..." required="required" id="newantragtype">
<?php
foreach ($menu as $m) {
  echo "          <option value=\"".htmlspecialchars($m["value"])."\" data-dep=\"".htmlspecialchars(json_encode($m["submenu"]))."\">".htmlspecialchars($m["text"])."</option>\n";
}
?>
        </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
      <div class="form-group">
        <label class="sr-only" for="revisionselect">Version</label>
        <select class="selectpicker form-control" name="revision" size="1" title="2. Revision des neuen Antrags auswählen..." id="revisionselect" required="required"> </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
<?php
if (count($newStates) > 0) {
?>
      <div class="form-group">
        <label class="sr-only" for="newantragstate">Neuer Bearbeitungsstatus des aktuellen Antrags</label>
        <select class="selectpicker form-control" name="copy_from.state" size="1" title="3. Neuer Bearbeitungsstatus des aktuellen Antrags" id="newantragstate">
           <option value="">(unverändert)</option>
<?php
  foreach ($newStates as $state) {
    $txt2 = $classConfig["state"][$state];
    echo "          <option value=\"".htmlspecialchars($state)."\">".htmlspecialchars($txt2)."</option>\n";
  }
?>
        </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
<?php
} // count($newStates);
?>
      <input type="submit" name="absenden" value="Antrag erstellen" class="btn btn-primary pull-right">
      <input type="hidden" name="copy_from" value="<?php echo htmlspecialchars($antrag["id"]); ?>">
      <input type="hidden" name="action" value="antrag.copy">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
      <input type="hidden" name="copy_from_version" value="<?php echo $antrag["version"]; ?>"/>
    </form>
  </div>
</div>

<?php
# vim:syntax=php
