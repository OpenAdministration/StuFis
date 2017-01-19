<?php
# vim: set syntax=php:

global $formid;
$formconfig = getFormConfig($formid[0],$formid[1]);

?>

<form id="newantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.create"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $formid[0]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $formid[1]; ?>"/>

<?php

  renderForm($formconfig);

?>

  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
  <button type="submit" class='btn btn-success pull-right' name="absenden" id="absenden">Absenden</button>

</form>


