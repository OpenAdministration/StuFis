<?php

global $formulare;

$menu = [];

foreach ($formulare as $type => $list) {
  foreach ($list as $revision => $lForm) {
    if ($revision !== "_class" && hasPermission($lForm, null, "canCreate")) continue;
    unset($list[$revision]);
  }
  if (count($list) == 0) continue;
  ksort($list);

  $classConfig = getFormClass($type);
  if (isset($classConfig["buildFrom"])) continue;

  $title = $type;
  if (isset($classConfig["title"]))
    $title = $classConfig["title"];
  if (isset($classConfig["shortTitle"]))
    $title = $classConfig["shortTitle"];

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

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newFormModal">
  <i class="fa fw fa-plus"></i>
  Neuen Antrag / Formular erstellen
</button>

<br/>
<br/>


<!-- Modal -->
<form action="<?php echo $URIBASE; ?>" method="POST" role="form" data-toggle="validator" enctype="multipart/form-data" id="newantragform" class="ajax">
  <input type="hidden" name="action" value="antrag.create-import">
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>

<div class="modal fade" id="newFormModal" tabindex="-1" role="dialog" aria-labelledby="newFormModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="newFormModalLabel">Neuen Antrag / Formular erstellen</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="newantragtype">Antrag</label>
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
          <label for="revisionselect">Version</label>
          <select class="selectpicker form-control" name="revision" size="1" title="2. Bitte Revision des neuen Antrags auswählen..." id="revisionselect" required="required"> </select>
          <div class="help-block with-errors"></div>
        </div>
        <!-- form-group -->
        <div class="form-group">
          <label for="importfile">Import (optional)</label>
          <div class="single-file-container">
          <input class="form-control single-file" type="file" name="importfile" id="importfile"/>
          </div>
          <div class="help-block with-errors"></div>
        </div>
        <!-- form-group -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        <button type="submit" class="btn btn-primary">Antrag / Formular erstellen</button>
      </div>
    </div>
  </div>
</div>
 </form>


<?php
# vim:syntax=php
# vim: set syntax=php

