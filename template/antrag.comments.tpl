<?php

if (count($antrag["_comments"]) == 0) return;
global $ADMINGROUP;
?>
    <div class="clearfix"></div>

    <div class="panel panel-default">
        <div class="panel-heading">Kommentare</div>
        <div class="panel-body chat">
            <form id="comment" role="form" action="<?= $_SERVER["PHP_SELF"]; ?>" method="POST"
                  enctype="multipart/form-data" class="ajax">
                <input type="hidden" name="action" value="antrag.comment"/>
                <input type="hidden" name="nonce" value="<?= $nonce; ?>"/>
                <input type="hidden" name="userFullName" value="<?= getUserFullName(); ?>"/>
                <input type="hidden" name="username" value="<?= getUsername(); ?>"/>
                <div class="chat-container chat-own">
                    <b><?= htmlspecialchars(getUserFullName() . " (" . getUsername() . ")") ?></b>
                    <span class="chat-time">Jetzt gerade</span>
                    <textarea name="new-comment" class="chat-textarea form-control col-xs-10" rows="3"
                              required="required"></textarea>
                    <button href="javascript:void(false);" class='btn btn-success submit-form validate pull-right'>
                        Senden
                    </button>
                </div>
            </form>
            
            
            <?php
            foreach ($antrag["_comments"] as $c){
                $owner = ($c["creator"] === getUsername() ? "own" : "other");
                $creatorStr = ((($c["creator"] == $c["creatorFullName"]) || empty($c["creatorFullName"])) ?
                    $c["creator"] :
                    ($c["creatorFullName"] . " (" . $c["creator"] . ")")
                );
                switch ($c["type"]){
                    case 0: //status change
                        ?>
                        <div title="<?= htmlspecialchars("von " .
                            $creatorStr . " am " . $c["timestamp"]) ?>"
                             class="chat-info"><?= htmlspecialchars($c["text"]) ?></div>
                        <div class="clearfix"></div>
                        <?php
                        break;
                    case 2: //admin only
                        if (!hasGroup($ADMINGROUP)) continue;
                    //fall-through
                    case 1: //comment
                        ?>
                        <div class="chat-container chat-<?= $owner ?>">
                            <b><?= htmlspecialchars($creatorStr) ?></b><span
                                    class="chat-time"><?= htmlspecialchars($c["timestamp"]) ?></span>
                            <p><?= htmlspecialchars($c["text"]) ?></p>
                        </div>
                        <?php
                        break;
                    default:
                        break;
                    
                }
                ?>
            
            <?php } ?>
            <div class="clearfix"></div>

        </div>
    </div>

<?php
# vim:syntax=php
