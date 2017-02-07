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
  if ($type != $antrag["type"]) continue;

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

<div class="clearfix"> </div>

<div class="panel panel-default">
  <div class="panel-heading">Dieses Formular als Vorlage für ein neues Formular verwenden</div>
  <div class="panel-body">
    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
      <div class="form-group">
        <label class="sr-only" for="newantragtype">Antrag</label>
        <select class="selectpicker form-control" name="type" size="1" data-dep="revisionselectcopy" title="1. Neuen Antrag auswählen..." required="required" id="newantragtypecopy">
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
        <select class="selectpicker form-control" name="revision" size="1" title="2. Revision des neuen Antrags auswählen..." id="revisionselectcopy" required="required"> </select>
        <div class="help-block with-errors"></div>
      </div>
      <!-- form-group -->
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
