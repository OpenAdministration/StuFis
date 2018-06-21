<?php
# vim: set syntax=php:

$createState = "draft";
if (isset($form["_class"]["createState"]))
    $createState = $form["_class"]["createState"];

$classConfig = $form["_class"];

$newStates = [];
foreach (array_keys($classConfig["state"]) as $newState){
    $perm = "canStateChange.from.{$createState}.to.{$newState}";
    if ($newState != $createState && !hasPermission($form, null, $perm)) continue;
    $newStates[] = $newState;
}

$proposeNewState = [];
if (isset($classConfig["proposeNewState"]) && isset($classConfig["proposeNewState"][$createState])){
    $proposeNewState = array_values(array_intersect($newStates, $classConfig["proposeNewState"][$createState]));
}
if (!in_array($createState, $proposeNewState))
    $proposeNewState[] = $createState;

?>

<form id="newantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
    <input type="hidden" name="action" value="antrag.create"/>
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
    <input type="hidden" name="type" value="<?php echo htmlspecialchars($_REQUEST["type"]); ?>"/>
    <input type="hidden" name="revision" value="<?php echo htmlspecialchars($_REQUEST["revision"]); ?>"/>
    <input type="hidden" name="state" value="<?php echo htmlspecialchars($createState); ?>" required="required"/>
    
    <?php
    
    renderForm($form, ["state" => "createState"]);
    
    ?>

    <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
    <div class="pull-right">
        <?php
        
        foreach ($proposeNewState as $state){
            $isEditable = hasPermission($form, ["state" => $state], "canEdit");
            $stateTxt = $classConfig["state"][$state][0];
            
            ?>
            <a href="javascript:void(false);"
               class='btn btn-success submit-form <?php if ($isEditable) echo "no-validate";else echo "validate"; ?>'
               data-name="state" data-value="<?php echo htmlspecialchars($state); ?>"
               id="state-<?php echo htmlspecialchars($state); ?>">Speichern als <?php echo $stateTxt; ?></a>
            &nbsp;
            <?php
        }
        ?>
    </div>

</form>


