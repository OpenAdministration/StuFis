<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $URIBASE, $antrag, $STORAGE;

require_once "../lib/inc.all.php";
requireAuth();
#requireGroup($ADMINGROUP);

?><!DOCTYPE html>
<html lang="en">
  <head>
    <title>FVS</title>
<?php   include("../template/head.tpl"); ?>
  </head>

  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">FVS - Finanz Verwaltungs System Interne Anträge</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <form class="navbar-form navbar-right">
            <div class="form-group">
              <input type="text" placeholder="Email" class="form-control">
            </div>
            <div class="form-group">
              <input type="password" placeholder="Password" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Sign in</button>
          </form>
        </div><!--/.navbar-collapse -->
      </div>
    </nav>



    <div class="container theme-showcase" role="main">


      <div class="jumbotron">
        <h1>Interne Anträge</h1>
        <p>This is a template showcasing the optional theme stylesheet included in Bootstrap. Use it as a starting point to create something more unique by building on or modifying it.</p>
      </div>
    </div>

  </body>
</html>
