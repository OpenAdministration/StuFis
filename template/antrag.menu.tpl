<?php

$classConfig = $form["_class"];
$classTitle = isset($classConfig["title"]) ? $classConfig["title"] : $form["type"];

$revConfig = $form["config"];
$revTitle = isset($revConfig["revisionTitle"]) ? $revConfig["revisionTitle"] : $form["revision"];

$targetEdit = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/edit";
$targetEditPartiell = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/editPartiell";
$targetPrint = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/print";

if (!hasPermission($form, $antrag, "canEdit"))
  $targetEdit = false;
else
  $targetEditPartiell = false;

if (!hasPermission($form, $antrag, "canEditPartiell"))
  $targetEditPartiell = false;

?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#"><?php echo htmlspecialchars($classTitle); ?></a>
      <p class="navbar-text navbar-right"><?php echo htmlspecialchars($revTitle); ?></p>
    </div><!-- /.navbar-collapse -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
<?php if ($targetEditPartiell !== false) { ?>
        <li><a href="<?php echo htmlspecialchars($targetEditPartiell); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil-square" aria-hidden="true"></i></a></li>
<?php } ?>
<?php if ($targetEdit !== false) { ?>
        <li><a href="<?php echo htmlspecialchars($targetEdit); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
<?php } ?>
        <li><a href="<?php echo htmlspecialchars($targetPrint); ?>" title="Drucken"><i class="fa fa-fw fa-print" aria-hidden="true"></i></a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<?php

# vim:syntax=php
