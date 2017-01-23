<?php
# vim: set syntax=php:

global $formid;
$formconfig = getFormConfig($antrag["type"],$antrag["revision"]);
if ($formconfig === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

?>

<form id="editantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.update"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $formid[0]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $formid[1]; ?>"/>

<?php

renderForm($formconfig, ["_values" => $antrag] );

?>
  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
  <button type="submit" class='btn btn-success pull-right' name="absenden" id="absenden">Speichern</button>

</form>


