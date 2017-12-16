<?php

global $HIBISCUSGROUP;

if (!hasGroup($HIBISCUSGROUP)) return;

?>
<div>
<br/>

    <a href="<?php echo $URIBASE; ?>?tab=booking" class="btn btn-primary">Zahlungen verbuchen</a>

    <form action="<?php echo $URIBASE; ?>" method="POST" role="form" class="form-inline ajax d-inline-block">
      <input type="submit" name="absenden" value="Kontoauszug abrufen" class="btn btn-primary">
      <input type="hidden" name="action" value="hibiscus">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    </form>

</div>
<?php
# vim:syntax=php
# vim: set syntax=php

