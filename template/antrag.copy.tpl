 <?php

global $formulare;

if (!hasPermission($form, $antrag, "canBeCloned", false)) return;

$type = $antrag["type"];
$list = $formulare[$type];
foreach ($list as $revision => $lForm) {
  if (hasPermission($lForm, null, "canCreate")) continue;
  unset($list[$revision]);
}
if (count($list) == 0) return;

$classConfig = getFormClass($type);

$title = $type;
if (isset($classConfig["title"]))
  $title = $classConfig["title"];

$submenu = [];
foreach ($list as $revision => $lForm) {
  if ($revision == "_class") continue;
  $rtitle = $revision;
  if (isset($lForm["config"]["revisionTitle"]))
    $rtitle = $lForm["config"]["revisionTitle"];
  $submenu[] = [ "value" => $revision, "text" => $rtitle ];
}

#  $menu[] = [ "value" => $type, "text" => $title, "submenu" => $submenu ];

if (count($submenu) == 0)
  return;

?>


<form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-horizontal ajax" id="cloneform">
<input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>"/>

<div class="modal fade" id="cloneFormModal" tabindex="-1" role="dialog" aria-labelledby="cloneFormModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="cloneFormModalLabel">Dieses Formular als Vorlage für ein neues gleiches Formular verwenden</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="control-label col-sm-4">Formular</label>
          <div class="col-sm-8">
            <?php echo htmlspecialchars($title); ?>
          </div>
        </div>
        <!-- form-group -->
<?php
if (count($submenu) == 1) {
?>
        <input type="hidden" name="revision" value="<?php echo htmlspecialchars($submenu[0]["value"]); ?>"/>

<?php
} else {
?>
        <div class="form-group">
          <label class="control-label col-sm-4" for="copyantragrevision">Antrag</label>
          <div class="col-sm-8">
            <select class="selectpicker form-control" name="revision" size="1" title="Revision des neuen Formulars auswählen" required="required" id="copyantragrevision">
<?php
foreach ($submenu as $m) {
  echo "            <option value=\"".htmlspecialchars($m["value"])."\">".htmlspecialchars($m["text"])."</option>\n";
}
?>
            </select>
            <div class="help-block with-errors"></div>
           </div>
        </div>
        <!-- form-group -->
<?php
} /* else: count submenu > 1 */
?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        <input type="submit" name="absenden" value="Formular erstellen" class="btn btn-primary pull-right">
        <input type="hidden" name="copy_from" value="<?php echo htmlspecialchars($antrag["id"]); ?>">
        <input type="hidden" name="action" value="antrag.copy">
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
        <input type="hidden" name="copy_from_version" value="<?php echo $antrag["version"]; ?>"/>
      </div>
    </div>
  </div>
</div>
 </form>

<?php
# vim:syntax=php
