<form id="editantragpartiell" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.updatePartiell"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
  <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>

<?php

renderForm($form, ["_values" => $antrag, "render" => ["no-form-cb"], "no-form-cb" => function($layout, $ctrl) use($form, $antrag){
  if ($layout["type"] == "table") return true; // dynamic tables cannot be edited with editPartiell right now
  $perm = "canEditPartiell.field.{$layout["id"]}";
  $hasPerm = hasPermission($form, $antrag, $perm);
  return !$hasPerm;
} ] );

?>

  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
  <button type="submit" class='btn btn-success pull-right' name="absenden" id="absenden">Speichern</button>
  <span class="pull-right">&nbsp;</span>
  <a class="btn btn-default pull-right" href="<?php echo htmlspecialchars($URIBASE."/".$antrag["token"]); ?>">Abbruch</a>

</form>

<?php
# vim: set syntax=php:
