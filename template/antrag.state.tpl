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

?>
<div class="panel panel-default">
  <div class="panel-heading">Bearbeitungsstand</div>
  <div class="panel-body">
    <div style="font-size:36px;" class="col-xs-4">
<?php
echo htmlspecialchars($txt);
?>
    </div>

<?php
if (count($newStates) > 0) {
?>

    <div class="col-xs-8">
      <form id="stateantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax form-inline" data-toggle="validator">
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
    echo "          <option value=\"".htmlspecialchars($state)."\">".htmlspecialchars($txt2)."</option>\n";
  }
?>
          </select>
          <div class="help-block with-errors"></div>
        </div>
        <!-- form-group -->
        <input type="submit" name="absenden" value="Bearbeitungsstatus ändern" class="btn btn-primary pull-right">
      </form>
    </div>
    <!-- col-xs-6 -->
<?php
}
?>

  </div>
  <!-- panel-body -->
</div>
<!-- panel -->

<?php
# vim:syntax=php
