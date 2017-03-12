<?php

global $HIBISCUSGROUP;

if (!hasGroup($HIBISCUSGROUP)) return;

?>
    <a href="<?php echo $URIBASE; ?>?tab=hibiscus.sct" class="btn btn-primary pull-right">Überweisungen exportieren</a>

<span class="pull-right">&nbsp;</span>

    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax">
      <input type="submit" name="absenden" value="Kontoauszug abrufen" class="btn btn-primary pull-right">
      <input type="hidden" name="action" value="hibiscus">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    </form>

<span class="pull-right">&nbsp;</span>

    <a href="<?php echo $URIBASE; ?>?tab=booking" class="btn btn-primary pull-right">Zahlungen verbuchen</a>

<?php
# vim:syntax=php
# vim: set syntax=php

