<?php

$classConfig = $form["_class"];
$classTitle = isset($classConfig["title"]) ? $classConfig["title"] : $form["type"];

$revConfig = $form["config"];
$revTitle = isset($revConfig["revisionTitle"]) ? $revConfig["revisionTitle"] : $form["revision"];

$targetRead = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."";
$targetEdit = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/edit";
$targetEditPartiell = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/editPartiell";
$targetPrint = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/print";
$targetExport = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/export";
$targetExportBank = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/exportBank";

$canEdit = hasPermission($form, $antrag, "canEdit");
if (!$canEdit)
    $targetEdit = false;
else
    $targetEditPartiell = false;

if (!hasPermission($form, $antrag, "canEditPartiell"))
    $targetEditPartiell = false;

$canBeCloned = hasPermission($form, $antrag, "canBeCloned", false);
$canBeLinked = hasPermission($form, $antrag, "canBeLinked", false);

if (!hasCategory($form, $antrag, "_export_bank"))
    $targetExportBank = false;

if (isset($antrag))
    $h = "[{$antrag["id"]}] {$classTitle}";
else
    $h = "{$classTitle}";

$stateString = $antrag["state"];
if (isset($classConfig["state"][$antrag["state"]]))
    $stateString = $classConfig["state"][$antrag["state"]][0];
$stateString .= " ({$antrag["stateCreator"]})";

$newStates = [];
foreach (array_keys($classConfig["state"]) as $newState) {
    $perm = "canStateChange.from.{$antrag["state"]}.to.{$newState}";
    if (!hasPermission($form, $antrag, $perm)) continue;
    $newStates[] = $newState;
}

$proposeNewState = [];
if (isset($classConfig["proposeNewState"]) && isset($classConfig["proposeNewState"][$antrag["state"]])) {
    $proposeNewState = array_unique(array_values(array_intersect($newStates, $classConfig["proposeNewState"][$antrag["state"]])));
}

$removeList = [];
foreach($proposeNewState as $state) {
    if (isValidNewState($antrag["id"], "postEdit", $state)) continue;
    $removeList[] = $state;
}
$newStates = array_diff($newStates, $proposeNewState);

if (count($newStates) > 0 || count($proposeNewState) > 0) {
?>

<!-- Modal Zustandsübergang zu anderem State -->
<form id="stateantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax" data-toggle="validator">
    <div class="modal fade" id="editStateModal" tabindex="-1" role="dialog" aria-labelledby="editStateModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editStateModalLabel">Bearbeitungsstand ändern</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="antrag.state"/>
                    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
                    <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
                    <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
                    <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>

                    <div class="form-group">
                        <label for="newantragstate">Neuer Bearbeitungsstatus</label>
                        <select class="selectpicker form-control" name="state" size="1" title="Neuer Bearbeitungsstatus" required="required" id="newantragstate">
                            <optgroup label="Empfohlen">
                                <?php
                                                           foreach ($proposeNewState as $newState) {
                                                               $newStateName = $classConfig["state"][$newState][0];

                                                               echo "<option ";
                                                               if (in_array($newState, $removeList)) {
                                                                   echo "disabled ";
                                                               }
                                                               echo "value=\"".htmlspecialchars($newState)."\">".htmlspecialchars($newStateName)."</option>\n";

                                                           }
                                ?>
                            </optgroup>
                            <optgroup label="Sonstige">
                                <?php

                                                           foreach ($newStates as $state) {
                                                               $newStateName = $classConfig["state"][$state][0];
                                                               $cls = [];
                                                               if (in_array($state, $removeList))
                                                                   $cls[] = "disabled-option";
                                                               echo "<option value=\"".htmlspecialchars($state)."\" class=\"".implode(" ", $cls)."\">".htmlspecialchars($newStateName)."</option>\n";
                                                           }
                                ?>
                            </optgroup>
                        </select>
                        <div class="help-block with-errors"></div>
                    </div>
                    <!-- form-group -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" name="absenden" class="btn btn-primary pull-right">Speichern</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
                                                          }
?>

<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($targetRead); ?>"><?php echo htmlspecialchars($h); ?></a>
            <p class="navbar-text navbar-left"><?php echo htmlspecialchars($revTitle); ?></p>
            <h4 class="navbar-text navbar-left"><span class="label label-primary">
                <?php if (count($newStates) > 0 || count($proposeNewState) > 0) { ?>
                <a class="text-white" href="#" data-toggle="modal" data-target="#editStateModal">
                    <i class="fa fa-fw fa-pencil" aria-hidden="true"></i>
                </a>
                <?php } ?>
                <?php echo htmlspecialchars($stateString);?></span></h4>

        </div><!-- /.navbar-collapse -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
                <?php if ($targetEditPartiell !== false) { ?>
                <!--        <li><a href="<?php echo htmlspecialchars($targetEditPartiell); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil-square" aria-hidden="true"></i></a></li> -->
                <li><a href="<?php echo htmlspecialchars($targetEditPartiell); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
                <?php } ?>
                <?php if ($targetEdit !== false) { ?>
                <li><a href="<?php echo htmlspecialchars($targetEdit); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
                <?php } ?>
                <li><a href="<?php echo htmlspecialchars($targetPrint); ?>" title="Drucken"><i class="fa fa-fw fa-print" aria-hidden="true"></i></a></li>
                <li><a href="<?php echo htmlspecialchars($targetExport); ?>" title="Exportieren"><i class="fa fa-fw fa-download" aria-hidden="true"></i></a></li>
                <?php if ($targetExportBank !== false) { ?>
                <li><a href="<?php echo htmlspecialchars($targetExportBank); ?>" title="Exportieren für Bank"><i class="fa fa-fw fa-money" aria-hidden="true"></i></a></li>
                <?php } ?>
                <?php if ($canBeCloned !== false) { ?>
                <li><a href="#" data-toggle="modal" data-target="#cloneFormModal" title="Neues (gleiches) Formular / Antrag anlegen"><i class="fa fw fa-clone"></i></a></li>
                <?php } ?>
                <?php if ($canBeLinked !== false) { ?>
                <li><a href="#" data-toggle="modal" data-target="#linkFormModal" title="Zugehöriges Formular / Antrag anlegen"><i class="fa fw fa-plus-square"></i></a></li>
                <?php } ?>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>

<?php /*
if (count($proposeNewState) > 0) {
?>
<div class="well">
    <?php

    foreach ($proposeNewState as $newState) {
        $txt3 = "Wechseln nach {$classConfig["state"][$newState][0]}";
        if (isset($classConfig["state"][$newState][1])) {
            $txt3 = ucfirst($classConfig["state"][$newState][1]);
        }

?>
    <form id="stateantrag<?php echo htmlspecialchars($newState); ?>" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax" data-toggle="validator" style="display:inline-block;">
        <input type="hidden" name="action" value="antrag.state"/>
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
        <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
        <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
        <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>
        <input type="hidden" name="state" value="<?php echo $newState; ?>"/>
        <button type="submit" name="absenden" class="btn btn-primary btn-sm" <?php if (in_array($newState, $removeList)) { echo "disabled title=\"Es werden noch Angaben im Formular benötigt.\" "; } ?>><?php echo $txt3; ?></button>
    </form>

    <?php
    } /* foreach */ /*
?>
</div>
<!-- well -->
<?php
} /* if count proposeNewState */
?>

<?php

# vim:syntax=php
