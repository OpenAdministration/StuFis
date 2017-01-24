<?php

$targetEdit = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/edit";
$targetPrint = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"])."/print";

?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="<?php echo htmlspecialchars($targetEdit); ?>" title="Bearbeiten"><i class="fa fa-fw fa-pencil" aria-hidden="true"></i></a></li>
        <li><a href="<?php echo htmlspecialchars($targetPrint); ?>" title="Drucken"><i class="fa fa-fw fa-print" aria-hidden="true"></i></a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<?

# vim:syntax=php
