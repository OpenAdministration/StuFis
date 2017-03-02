<?php

global $HIBISCUSGROUP;

if (!hasGroup($HIBISCUSGROUP)) return;

?>

    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline">
      <input type="submit" name="absenden" value="Kontoauszug abrufen" class="btn btn-primary pull-right">
      <input type="hidden" name="action" value="hibiscus">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    </form>

<?php
# vim:syntax=php
# vim: set syntax=php

