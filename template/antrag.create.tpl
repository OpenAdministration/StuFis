<?php

global $formconfig;
# vim: set syntax=php:
?>

<form id="newantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.create"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>

<?php

  renderForm($formconfig);

?>

  <button type="submit" class='btn btn-success pull-right' name="submit" id="submit">Absenden</button>

</form>


