<!DOCTYPE html>
<html lang="de">
    <head>
<!--        <title>FVS - Neuer Interner Antrag</title> -->
        <title>StuRa Finanzformulare</title>
<?php   include("../template/head.tpl"); ?>
    </head>

    <body>

        <nav class="navbar navbar-inverse navbar-fixed-top"

<?php
global $DEV;
 if ($DEV)
   echo " style=\"background-color:darkred;\"";

?>

>
            <div class="container">
                <div class="navbar-header">
<!--                    <a class="navbar-brand" href="#">FVS - Finanz Verwaltungs System Interne Anträge</a> -->
                    <a class="navbar-brand" href="<?php echo htmlspecialchars($URIBASE); ?>">StuRa-Finanzformulare
<?php 
if ($DEV)
   echo " TESTSYSTEM";
?>
                    </a>
        <ul class="nav navbar-nav navbar-right">
          <li><a href="<?php echo htmlspecialchars($logoutUrl); ?>">Logout</a></li>
        </ul>
                </div>
            </div>
        </nav>

        <div class="container">

<?php
# vim: set syntax=php:
