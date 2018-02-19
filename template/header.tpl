<!DOCTYPE html>
<html lang="de">
<head>
    <!--        <title>FVS - Neuer Interner Antrag</title> -->
    <title>StuRa Finanzformulare</title>
    <?php include("../template/head.tpl"); ?>
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
        </div>
        <ul class="nav navbar-nav navbar-right">
            <li><a target="_blank"
                   href="<?php echo htmlspecialchars("https://wiki.stura.tu-ilmenau.de/leitfaden/finanzenantraege"); ?>">Hilfe</a>
            </li>
            <li><a href="<?php echo htmlspecialchars(AuthHandler::getInstance()->getLogoutURL()); ?>">Logout</a></li>
        </ul>
    </div>
</nav>
<div class col-md-2>
    <?php include "antrag.createpanel.tpl"; ?>
    <div class="profile-sidebar">
        <!-- SIDEBAR USER TITLE -->
        <div class="profile-usertitle">
            <div class="profile-usertitle-name">
                <?php echo AuthHandler::getInstance()->getUserfullname(); ?>
            </div>
            <?php if (AuthHandler::getInstance()->hasGroup($ADMINGROUP)){ ?>
                <div class="profile-usertitle-job">
                    Admin
                </div>
            <?php }else if (AuthHandler::getInstance()->hasGroup("ref-finanzen")){ ?>
                <div class="profile-usertitle-job">
                    Ref-Finanzen
                </div>
            <?php } ?>

        </div>
        <!-- END SIDEBAR USER TITLE -->
        <!-- SIDEBAR BUTTONS -->
        <div class="profile-userbuttons">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newFormModal">
                <i class="fa fa-fw fa-plus"></i>
                neues Projekt
            </button>
        </div>
        <!-- END SIDEBAR BUTTONS -->
        <!-- SIDEBAR MENU -->
        <div class="profile-usermenu">
            <ul class="nav">
                <li <?php if ($_REQUEST["tab"] == "mygremium") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "?tab=mygremium"); ?>">
                        <i class="fa fa-fw fa-home"></i>
                        Meine Gremien
                    </a>
                </li>
                <li <?php if ($_REQUEST["tab"] == "mykonto") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "?tab=mykonto"); ?>">
                        <i class="fa fa-fw fa-user-circle"></i>
                        Benutzerkonto
                    </a>
                </li>
                <?php
                if (AuthHandler::getInstance()->hasGroup("ref-finanzen")){
                    ?>
                    <li <?php if ($_REQUEST["tab"] == "hv") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "?tab=hv"); ?>">
                            <i class="fa fa-fw fa-legal"></i>
                            TODO HV
                        </a>
                    </li>
                    <li <?php if ($_REQUEST["tab"] == "kv") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "?tab=kv"); ?>">
                            <i class="fa fa-fw fa-calculator "></i>
                            TODO KV
                        </a>
                    </li>
                    <li <?php if ($_REQUEST["tab"] == "booking") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "?tab=booking"); ?>">
                            <i class="fa fa-fw fa-book "></i>
                            Buchungen
                        </a>
                    </li>
                <?php } ?>
                <li <?php if ($_REQUEST["tab"] == "stura") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "?tab=stura"); ?>">
                        <i class="fa fa-fw fa-users"></i>
                        StuRa-Sitzung
                    </a>
                </li>
                <li <?php if ($_REQUEST["tab"] == "allgremium") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "?tab=allgremium"); ?>">
                        <i class="fa fa-fw fa-globe"></i>
                        Alle Gremien
                    </a>
                </li>
                <li <?php if ($_REQUEST["tab"] == "hhp") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "?tab=hhp"); ?>">
                        <i class="fa fa-fw fa-bar-chart"></i>
                        Haushaltsplan
                    </a>
                </li>

            </ul>
        </div>
        <!-- END MENU -->
    </div>
</div>
<?php
# vim: set syntax=php:
