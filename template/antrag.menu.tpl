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

if (!hasPermission($form, $antrag, "canEdit"))
  $targetEdit = false;
else
  $targetEditPartiell = false;

if (!hasPermission($form, $antrag, "canEditPartiell"))
  $targetEditPartiell = false;

$canBeCloned = hasPermission($form, $antrag, "canBeCloned", false);
$canBeLinked = hasPermission($form, $antrag, "canBeLinked", false);

if (isset($antrag))
  $h = "[{$antrag["id"]}] {$classTitle}";
else
  $h = "{$classTitle}";

?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="<?php echo htmlspecialchars($targetRead); ?>"><?php echo htmlspecialchars($h); ?></a>
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
        <li><a href="<?php echo htmlspecialchars($targetExport); ?>" title="Exportieren"><i class="fa fa-fw fa-download" aria-hidden="true"></i></a></li>
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

<?php

# vim:syntax=php
