<!DOCTYPE html>
<html lang="de">
    <head>
<!--        <title>FVS - Neuer Interner Antrag</title> -->
        <title>StuRa Finanzformulare</title>
<?php   include("../template/head-print.tpl"); ?>
    </head>

    <body>

        <nav class="navbar navbar-inverse navbar-fixed-top no-print"

<?php
global $DEV;
 if ($DEV)
   echo " style=\"background-color:darkred;\"";

?>
>
            <div class="container">
                <div class="navbar-header">
<!--                    <a class="navbar-brand" href="#">FVS - Finanz Verwaltungs System Interne Anträge</a> -->
                    <a class="navbar-brand" href="#">StuRa-Finanzformulare
<?php 
if ($DEV)
   echo " TESTSYSTEM";
?>
                    </a>
                </div>
            </div>
        </nav>

        <div class="container">

<?php
# vim: set syntax=php:
