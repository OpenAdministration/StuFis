<?php

global $HIBISCUSGROUP, $URIBASE;

if (!AuthHandler::getInstance()->hasGroup($HIBISCUSGROUP)) return;

?>
<div>
    <br/>
    <a href="<?php echo $URIBASE; ?>?tab=booking" class="btn btn-primary"><i class="fa fw fa-inbox "></i> Zahlungen
        verbuchen</a>


</div>
<?php
# vim:syntax=php
# vim: set syntax=php

