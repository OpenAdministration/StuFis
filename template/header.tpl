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
<div>
    <?php //include "antrag.createpanel.tpl"; ?>
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
            <a href="<?= $URIBASE ?>projekt/create/edit" type="button" class="btn btn-primary btn-sm">
                <i class="fa fa-fw fa-plus"></i>
                neues Projekt
            </a>
        </div>
        <!-- END SIDEBAR BUTTONS -->
        <!-- SIDEBAR MENU -->
        <div class="profile-usermenu">
            <ul class="nav">
                <li <?php if ($subtype == "mygremium") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "menu/mygremium"); ?>">
                        <i class="fa fa-fw fa-home"></i>
                        Meine Gremien
                    </a>
                </li>
                <li <?php if ($subtype == "mykonto") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "menu/mykonto"); ?>">
                        <i class="fa fa-fw fa-user-circle"></i>
                        Benutzerkonto
                    </a>
                </li>
                <?php
                if (AuthHandler::getInstance()->hasGroup("ref-finanzen")){
                    ?>
                    <li <?php if ($subtype == "hv") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "menu/hv"); ?>">
                            <i class="fa fa-fw fa-legal"></i>
                            TODO HV
                        </a>
                    </li>
                    <li <?php if ($subtype == "kv") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "menu/kv"); ?>">
                            <i class="fa fa-fw fa-calculator "></i>
                            TODO KV
                        </a>
                    </li>
                    <li <?php if ($subtype == "booking") echo "class='active'"; ?>>
                        <a href="<?php echo htmlspecialchars($URIBASE . "menu/booking"); ?>">
                            <i class="fa fa-fw fa-book "></i>
                            Buchungen
                        </a>
                    </li>
                    <li <?php if ($subtype == "konto") echo "class='active'"; ?>>
                        <a href="<?= htmlspecialchars($URIBASE . "menu/konto"); ?>">
                            <i class="fa fa-fw fa-bar-chart"></i>
                            Konto
                        </a>
                    </li>
                <?php } ?>
                <li <?php if ($subtype == "stura") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "menu/stura"); ?>">
                        <i class="fa fa-fw fa-users"></i>
                        StuRa-Sitzung
                    </a>
                </li>
                <li <?php if ($subtype == "allgremium") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "menu/allgremium"); ?>">
                        <i class="fa fa-fw fa-globe"></i>
                        Alle Gremien
                    </a>
                </li>
                <li <?php if ($subtype == "hhp") echo "class='active'"; ?>>
                    <a href="<?php echo htmlspecialchars($URIBASE . "menu/hhp"); ?>">
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
