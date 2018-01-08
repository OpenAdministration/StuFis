<?php

global $HIBISCUSGROUP;

if (!hasGroup($HIBISCUSGROUP)) return;

?>
<div>
    <br/>

    <a href="<?php echo $URIBASE; ?>?tab=booking" class="btn btn-primary">Zahlungen verbuchen</a>



    <a href="<?php echo $URIBASE; ?>?tab=booking.history" class="btn btn-primary">Buchungsübersicht</a>
</div>
<?php
# vim:syntax=php
# vim: set syntax=php

