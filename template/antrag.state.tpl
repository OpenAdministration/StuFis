<?php

$classConfig = getFormClass($antrag["type"]);
if (!isset($classConfig["state"])) return;

$txt = $antrag["state"];
if (isset($classConfig["state"][$antrag["state"]]))
  $txt = $classConfig["state"][$antrag["state"]];
$txt .= " ({$antrag["stateCreator"]})";

$newStates = [];
foreach (array_keys($classConfig["state"]) as $newState) {
  $perm = "canStateChange.from.{$antrag["state"]}.to.{$newState}";
  if (!hasPermission($form, $antrag, $perm)) continue;
  $newStates[] = $newState;
}

$proposeNewState = [];
if (isset($classConfig["proposeNewState"]) && isset($classConfig["proposeNewState"][$antrag["state"]])) {
  $proposeNewState = array_values(array_intersect($newStates, $classConfig["proposeNewState"][$antrag["state"]]));
}

if (count($newStates) > 0) {

?>

<!-- Modal -->
<form id="stateantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax" data-toggle="validator">
  <div class="modal fade" id="editStateModal" tabindex="-1" role="dialog" aria-labelledby="editStateModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="editStateModalLabel">Bearbeitungsstand ändern</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="antrag.state"/>
          <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
          <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
          <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
          <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>

          <div class="form-group">
            <label for="newantragstate">Neuer Bearbeitungsstatus</label>
            <select class="selectpicker form-control" name="state" size="1" title="Neuer Bearbeitungsstatus" required="required" id="newantragstate">
  <?php
    foreach ($newStates as $state) {
      $txt2 = $classConfig["state"][$state];
      echo "            <option value=\"".htmlspecialchars($state)."\">".htmlspecialchars($txt2)."</option>\n";
    }
  ?>
            </select>
            <div class="help-block with-errors"></div>
          </div>
          <!-- form-group -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" name="absenden" class="btn btn-primary pull-right">Speichern</button>
        </div>
      </div>
    </div>
  </div>
</form>

<?php

}

?>
<div class="panel panel-default">
  <div class="panel-heading">
    Bearbeitungsstand
<?php if (count($newStates) > 0) { ?>
    <a href="#" class="pull-right" data-toggle="modal" data-target="#editStateModal">
      <i class="fa fa-fw fa-pencil" aria-hidden="true"></i>
    </a>
<?php } ?>
  </div>
  <div class="panel-body">
    <span style="font-size:36px;">
<?php
echo htmlspecialchars($txt);
?>
    </span>
<?php

foreach ($proposeNewState as $newState) {
  $txt3 = $classConfig["state"][$newState];

?>
    <form id="stateantrag<?php echo htmlspecialchars($newState); ?>" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax" data-toggle="validator" style="display:inline-block;">
      <input type="hidden" name="action" value="antrag.state"/>
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
      <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
      <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
      <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>
      <input type="hidden" name="state" value="<?php echo $newState; ?>"/>
      <button type="submit" name="absenden" class="btn btn-primary pull-right">Wechsel nach: <?php echo $txt3; ?></button>
    </form>

<?php

}


?>
  </div>
  <!-- panel-body -->
</div>
<!-- panel -->

<?php
# vim:syntax=php
