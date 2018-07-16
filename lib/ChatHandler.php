<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 16.07.18
 * Time: 16:21
 */

class ChatHandler extends Renderer{
    
    private $comments;
    private $action;
    
    public function __construct($pathinfo){
        $controller = $pathinfo["controller"];
        $this->action = $pathinfo["action"];
        if ($controller === "projekt"){
            $id = $pathinfo["pid"];
        }else if ($controller === "auslage"){
            $id = $pathinfo["aid"];
        }else{
            $id = null;
        }
        $this->comments = DBConnector::getInstance()->dbFetchAll(
            "comments",
            [],
            ["controller" => $controller, "antrag_id" => $id],
            [],
            ["timestamp" => false]
        );
    }
    
    public function render(){
        if ($this->action === "view")
            $this->renderCommentPanel();
    }
    
    private function renderCommentPanel(){ ?>

        <div class='clearfix'></div>
        <div class="col-xs-12 col-md-10">
            <div class='panel panel-default'>
                <div class='panel-heading'>Kommentare</div>
                <div class='panel-body chat'>
                    <form id='comment' role='form' action="<?= $_SERVER["PHP_SELF"]; ?>" method="POST"
                          enctype='multipart/form-data' class='ajax'>
                        <input type='hidden' name='action' value='new-comment'>
                        <input type='hidden' name='nonce' value='<?= $GLOBALS['nonce'] ?>'>

                        <div class='chat-container chat-own'>
                            <span class='chat-time'>Jetzt gerade</span>
                            <label for='new-comment'>
                                <?= htmlspecialchars((AUTH_HANDLER)::getInstance()->getUserFullName() .
                                    " (" . (AUTH_HANDLER)::getInstance()->getUsername() . ")") ?>
                            </label>
                            <textarea name='new-comment' id='new-comment'
                                      class='chat-textarea form-control col-xs-10'
                                      rows='3'
                                      required></textarea>
                            <button href='javascript:void(false);'
                                    class='btn btn-success submit-form validate pull-right'>
                                Senden
                            </button>
                        </div>
                    </form>
                    
                    
                    <?php
                    foreach ($this->comments as $c){
                        $owner = ($c["creator"] === (AUTH_HANDLER)::getInstance()->getUsername() ? "own" : "other");
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
                                if (!(AUTH_HANDLER)::getInstance()->isAdmin())
                                    break;
                            //fall-through
                            case 1: //comment
                                ?>
                            <div class="chat-container chat-<?= $owner ?>">
                                <span class="chat-time"><?= htmlspecialchars($c["timestamp"]) ?></span>
                                <label><?= htmlspecialchars($creatorStr) ?></label>
                                <p><?= htmlspecialchars($c["text"]) ?></p>
                                </div><?php
                                break;
                            default:
                                break;
                            
                        }
                        ?>
                    
                    <?php } ?>
                    <div class="clearfix"></div>

                </div>
            </div>
        </div>
        <?php
    }
}