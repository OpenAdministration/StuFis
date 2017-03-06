<?php

  $classConfig = $form["_class"];

  $newStates = [];
  foreach (array_keys($classConfig["state"]) as $newState) {
    $perm = "canStateChange.from.{$antrag["state"]}.to.{$newState}";
    if ($newState != $antrag["state"] && !hasPermission($form, $antrag, $perm)) continue;
    $newStates[] = $newState;
  }

  $proposeNewState = [];
  if (isset($classConfig["proposeNewState"]) && isset($classConfig["proposeNewState"][$antrag["state"]])) {
    $proposeNewState = array_values(array_intersect($newStates, $classConfig["proposeNewState"][$antrag["state"]]));
  }

  if (!in_array($antrag["state"], $proposeNewState))
    $proposeNewState[] = $antrag["state"];

  if (isset($_REQUEST["override"])) {
    $overrides = [];
    function addToOverrides ($key, $value, &$overrides) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          addToOverrides($key."[$k]", $v, $overrides);
        }
      } else {
        $overrides[$key] = $value;
      }
    }
    foreach ($_REQUEST["override"] as $key => $value) {
      addToOverrides($key, $value, $overrides);
    }
    $append = $overrides;
    foreach ($antrag["_inhalt"] as $i => $row) {
      if (!isset($overrides[$row["fieldname"]])) continue;
      $antrag["_inhalt"][$i]["value"] = $overrides[$row["fieldname"]];
      unset($append[$row["fieldname"]]);
    }
    foreach ($append as $k => $v) {
      $antrag["_inhalt"][] = [ "value" => $v, "fieldname" => $k, "contenttype" => null ];
    }
  }

?>

<form id="editantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.update"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
  <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>
  <input type="hidden" name="state" value="<?php echo $antrag["state"]; ?>"/>

<?php

renderForm($form, ["_values" => $antrag] );

?>

  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->

  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
  <div class="pull-right">
  <a class="btn btn-default" href="<?php echo htmlspecialchars($URIBASE."/".$antrag["token"]); ?>">Abbruch</a>
<?php

  foreach($proposeNewState as $state) {
    $isEditable = hasPermission($form, ["state" => $state], "canEdit", false);
    $stateTxt = $classConfig["state"][$state][0];
    if ($stateTxt == "")
      $stateTxt = $state;
    if ($state == $antrag["state"])
      $btnTxt = "Speichern";
    else
      $btnTxt = "Speichern als {$stateTxt}";

?>
    <a href="javascript:void(false);" class='btn btn-success submit-form <?php if ($isEditable) echo "no-validate"; else echo "validate"; ?>' data-name="state" data-value="<?php echo htmlspecialchars($state); ?>" id="state-<?php echo htmlspecialchars($state); ?>"><?php echo $btnTxt; ?></a>
    &nbsp;
<?php
  }
?>
  </div>

</form>


<form id="deleteantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.delete"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
  <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>
  <button type="submit" class='btn btn-danger' name="delete" id="delete">Löschen</button>
</form>

<?php
# vim: set syntax=php:
