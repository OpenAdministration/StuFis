<?php

$classConfig = $form["_class"];
$classTitle = isset($classConfig["title"]) ? $classConfig["title"] : $form["type"];

$revConfig = $form["config"];
$revTitle = isset($revConfig["revisionTitle"]) ? $revConfig["revisionTitle"] : $form["revision"];

?>
<nav class="navbar navbar-default no-print">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="#"><?php echo htmlspecialchars($classTitle); ?></a>
    </div>
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <p class="navbar-text navbar-right"><?php echo htmlspecialchars($revTitle); ?></p>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<?php

# vim:syntax=php
