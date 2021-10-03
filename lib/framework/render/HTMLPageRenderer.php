<?php

namespace framework\render;

use Composer\InstalledVersions;
use DateTime;
use framework\auth\AuthHandler;

class HTMLPageRenderer
{
    private static $profiling_timing, $profiling_names, $profiling_sources;
    private static $errorHandler;
    private static $tabsConfig;
    private $bodyContent;
    private $titel;
    protected $routeInfo;

    public function __construct($routeInfo, $bodyContent = "")
    {
        $this->routeInfo = $routeInfo;
        if (isset($this->routeInfo["titel"])) {
            $this->titel = $this->routeInfo["titel"];
        } else {
            $this->titel = "StuRa Finanzen";
        }
        $this->bodyContent = $bodyContent;
    }

    /**
     * @param $name string Name des Profiling Flags
     */
    public static function registerProfilingBreakpoint(string $name): void
    {
        self::$profiling_timing[] = microtime(true);
        $bt = debug_backtrace();
        self::$profiling_sources[] = array_shift($bt);
        self::$profiling_names[] = $name;
    }

    /**
     * @param $tabs      array keys are tabnames, values are htmlcode inside tab header
     * @param $linkbase  string linkbase - where clicked link will go - followed by tabnames
     * @param $activeTab string
     */
    public static function setTabs(array $tabs, string $linkbase, string $activeTab): void
    {
        self::$tabsConfig = ["tabs" => $tabs, "linkbase" => $linkbase, "active" => $activeTab];
    }

    public static function showErrorAndDie(ErrorHandler $errorHandler): void
    {
        self::$errorHandler = $errorHandler;
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            self::$errorHandler->renderJson();
            die();
        }
        (new self([]))->render();
        die();
    }

    private function hasError() : bool
    {
        return isset(self::$errorHandler);
    }

    private function renderError(): void
    {
        self::$errorHandler->render();
    }

    public function render(): void
    {
        $this->renderHtmlHeader();
        $this->renderNavbar();
        $this->renderSiteNavigation();
        echo "<div class='container main col-xs-12 col-lg-10'>";
        if ($this->hasError()) {
            $this->renderError();
        } else {
            if (!empty(self::$tabsConfig)) {
                $this->renderPanelTabs(self::$tabsConfig["tabs"], self::$tabsConfig["linkbase"], self::$tabsConfig["active"]);
            }
            echo $this->bodyContent;
            if (!empty(self::$tabsConfig)) {
                echo "</div></div>";
            }
        }
        echo "</div>";
        if (!$this->hasError()) {
            $this->renderModals();
            $this->renderCookieAlert();
        }
        if (DEV) {
            $this->renderProfiling();
        }
        $this->renderFooter();
    }

    private function renderHtmlHeader(): void
    {
        $this->setPhpHeader();
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <title><?= $this->titel ?></title>
            <?= $this->includeCSS() ?>
            <?= $this->includeJS() ?>
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="description" content="">
            <meta name="author" content="Open-Administration">
            <link rel="icon" type="image/png" sizes="86x86" href="<?= URIBASE?>favicon86.png">
            <meta charset="utf-8">
        </head>
        <body>
        <?php
    }

    private function renderCookieAlert(): void
    {
        ?>
        <!-- START Bootstrap-Cookie-Alert -->
        <div class="alert text-center cookiealert" role="alert">
            <b>Das Finanztool mag &#x1F36A;!</b>
            Hier hast du einen &#x1F36A;. Den brauchst du, damit das Finanztool für dich arbeitet.
            <a href="https://cookiesandyou.com/" target="_blank">Mehr Informationen</a>
            <button type="button" class="btn btn-primary btn-sm acceptcookies" aria-label="Close">
                Ich werde ihn nicht selbst essen, versprochen &#x1F612;
            </button>
        </div>
        <!-- END Bootstrap-Cookie-Alert -->
        <?php
    }

    private function setPhpHeader(): void
    {
        // by micha-dev
        // https://people.mozilla.com/~bsterne/content-security-policy/details.html
        // https://wiki.mozilla.org/Security/CSP/Specification
        header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
        header("X-Frame-Options: DENY");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }

    private function includeCSS(): string
    {
        $out = "";
        $defaultCssFiles = ["bootstrap.min", "font-awesome.min", "cookiealert"];
        $cssFiles = $defaultCssFiles;
        if (isset($this->routeInfo["load"])) {
            $cssFiles = array_merge($cssFiles, ...array_column($this->routeInfo["load"], 'css'));
        }
        foreach ($cssFiles as $cssFile) {
            $out .= "<link rel='stylesheet' href='" . URIBASE . "css/$cssFile.css'>" . PHP_EOL;
        }
        $out .= "<link rel='stylesheet' href='" . URIBASE . "css/main.css'>" . PHP_EOL;
        return $out;
    }

    private function includeJS(): string
    {
        $out = "";
        $defaultJsFiles = [
            "jquery.min",
            "bootstrap.min",
            "validator",
            "numeral.min",
            "numeral-locales.min",
            "cookiealert"
        ];
        /*
        jquery-3.1.1.min.js
        bootstrap.min.js
        bootstrap-select.min.js
        validator.js
        bootstrap-datepicker.min.js
        bootstrap-datepicker.de.min.js
        bootstrap-treeview.js
        fileinput.min.js
        fileinput.de.js
        fileinput-themes/gly/theme.js
        iban.js
        numeral.min.js
        numeral-locales.min.js
        main.js
        */
        $jsFiles = $defaultJsFiles;
        if (isset($this->routeInfo["load"])) {
            $jsFiles = array_merge($jsFiles, ...array_column($this->routeInfo["load"], 'js'));
        }
        //var_dump($this->routeInfo["load"]);
        foreach ($jsFiles as $jsFile) {
            $out .= "<script src='" . URIBASE . "js/$jsFile.js'></script>" . PHP_EOL;
        }
        $out .= "<script src='" . URIBASE . "js/main.js'></script>" . PHP_EOL;
        return $out;
    }

    private function renderNavbar(): void
    {
        //https://stackoverflow.com/questions/7447472/
        $gitBasePath = SYSBASE . '/.git'; // e.g in laravel: base_path().'/.git';

        $gitStr = file_get_contents($gitBasePath . '/HEAD');
        $gitBranchName = rtrim(preg_replace("/(.*?\/){2}/", '', $gitStr));
        $gitPathBranch = $gitBasePath . '/refs/heads/' . $gitBranchName;
        $gitHash = file_get_contents($gitPathBranch);
        $gitDate = new DateTime();
        $gitDate->setTimestamp(filemtime($gitPathBranch));

        $prettyVersionString = InstalledVersions::getRootPackage()["pretty_version"];
        $versionString = InstalledVersions::getRootPackage()["version"];

        ?>
        <nav class="navbar navbar-inverse navbar-fixed-top"
            <?php
            if (DEV) {
                echo " style='background-color:darkred;'";
            }
            ?>
        >
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand" href="<?php echo htmlspecialchars(URIBASE); ?>">
                        <span class="logo-bg"></span>
                        Finanzformulare<?= DEV ? " TESTSYS" : "" ?>
                    </a>
                </div>
                <ul class="nav navbar-nav navbar-right col-xs-6">
                    <li>
                        <a target="_blank"
                           href="<?php echo htmlspecialchars(ORG_DATA['datenschutz-url']); ?>">
                            <i class="fa fa-fw fa-lock"></i>
                            <span class="hidden-sm hidden-xs">Datenschutz</span>
                        </a>
                    </li>
                    <li>
                        <a target="_blank"
                           href="<?php echo htmlspecialchars(ORG_DATA['impressum-url']); ?>">
                            <i class="fa fa-fw fa-info"></i>
                            <span class="hidden-sm hidden-xs">Impressum</span>
                        </a>
                    </li>
                    <li>
                        <a target="_blank" href="<?=  htmlspecialchars(ORG_DATA['issues-url']) ?>"
                           title="<?= htmlspecialchars(
                               "Commit:\t" . substr($gitHash,0,7) . PHP_EOL .
                               "Branch:\t" . $gitBranchName . PHP_EOL .
                               "From:\t" . $gitDate->format(DATE_ATOM) . PHP_EOL .
                               "Version:\t" . $versionString
                           )?>">
                            <i class="fa fa-fw fa-gitlab"></i>
                            <span class="hidden-sm hidden-xs">Fehler melden - v<?= $prettyVersionString ?></span>
                        </a>
                    </li>
                    <li>
                        <a target="_blank"
                           href="<?php echo htmlspecialchars(htmlspecialchars(ORG_DATA['help-url'])); ?>">
                            <i class="fa fa-fw fa-question"></i>
                            <span class="hidden-sm hidden-xs">Hilfe</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= URIBASE . "menu/logout" ?>">
                            <i class="fa fa-fw fa-sign-out"></i>
                            <span class="hidden-sm hidden-xs">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <?php
    }

    private function renderSiteNavigation(): void
    {
        $auth = AuthHandler::getInstance();
        $activeButton = $this->routeInfo["navigation"] ?? "";
        $navButtons = [
            [
                "title" => "Übersicht",
                "show" => true,
                "fa-icon" => "fa-home",
                "target" => URIBASE . "menu/mygremium",
                "tabname" => "overview",
            ],
            [
                "title" => "Benutzerkonto",
                "show" => false,
                "fa-icon" => "fa-user-circle",
                "target" => URIBASE . "menu/mykonto",
                "tabname" => "mykonto",
            ],
            [
                "title" => "TODO HV",
                "show" => $auth->hasGroup("ref-finanzen"),
                "fa-icon" => "fa-legal",
                "target" => URIBASE . "menu/hv",
                "tabname" => "hv",
            ],
            [
                "title" => "TODO KV",
                "show" => $auth->hasGroup("ref-finanzen"),
                "fa-icon" => "fa-calculator",
                "target" => URIBASE . "menu/kv",
                "tabname" => "kv",
            ],
            [
                "title" => "Buchungen",
                "show" => $auth->hasGroup("ref-finanzen"),
                "fa-icon" => "fa-book",
                "target" => URIBASE . "booking",
                "tabname" => "booking",
            ],
            [
                "title" => "Konto",
                "show" => $auth->hasGroup("ref-finanzen"),
                "fa-icon" => "fa-bar-chart",
                "target" => URIBASE . "konto/",
                "tabname" => "konto",
            ],
            [
                "title" => "StuRa Sitzung",
                "show" => true,
                "fa-icon" => "fa-users",
                "target" => URIBASE . "menu/stura",
                "tabname" => "stura",
            ],
            [
                "title" => "Haushaltspläne",
                "show" => true,
                "fa-icon" => "fa-bar-chart",
                "target" => URIBASE . "hhp",
                "tabname" => "hhp",
            ],
        ];
        ?>
        <div>
            <div class="profile-sidebar">
                <!-- SIDEBAR USER TITLE -->
                <div class="profile-usertitle">
                    <div class="profile-usertitle-name">
                        <?= $auth->getUserfullname() ?>
                    </div>
                    <div class="profile-usertitle-job">
                    <?php if ($auth->isAdmin()) { ?>
                            Admin
                    <?php } else if ($auth->hasGroup("ref-finanzen-hv")) { ?>
                            Haushaltsverantwortlich
                    <?php } else if ($auth->hasGroup("ref-finanzen-kv")) { ?>
                            Kassenverantwortlich
                    <?php } else if ($auth->hasGroup("ref-finanzen")) { ?>
                            Referat Finanzen
                    <?php } else { ?>
                            Support Level 1
                    <?php } ?>
                    </div>
                </div>
                <!-- END SIDEBAR USER TITLE -->
                <!-- SIDEBAR BUTTONS -->
                <div class="profile-userbuttons">
                    <a href="<?= URIBASE ?>projekt/create/edit" type="button" class="btn btn-primary btn-sm">
                        <i class="fa fa-fw fa-plus"></i>
                        neues Projekt
                    </a>
                </div>
                <!-- END SIDEBAR BUTTONS -->
                <!-- SIDEBAR MENU -->
                <div class="profile-usermenu">
                    <ul class="nav">
                        <?php foreach ($navButtons as $navButton) {
                            if (!$navButton["show"]) {
                                continue;
                            }
                            ?>
                            <li <?=  ($activeButton === $navButton["tabname"]) ? "class='active'" : ""?>>
                                <a href="<?php echo htmlspecialchars($navButton["target"]); ?>">
                                    <i class="fa fa-fw <?= $navButton["fa-icon"] ?>"></i>
                                    <?= $navButton["title"] ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- END MENU -->
            </div>
        </div>
        <?php
    }

    private function renderPanelTabs($tabs, $linkbase, $activeTab): void
    {
        ?>
        <div class="panel panel-default">
            <div class="panel-heading panel-heading-with-tabs">
                <ul class="nav nav-tabs">
                    <?php
                    foreach ($tabs as $link => $fullname) {
                        echo "<li class='" . (($link === $activeTab) ? "active" : "") . "'><a href='$linkbase$link'>$fullname</a></li>";
                    }
                    ?>
                </ul>
            </div>
            <div class="panel-body">
        <?php
    }

    private function renderModals(): void
    {
        $this->buildModal("please-wait", "Bitte warten - Die Anfrage wird verarbeitet.", "" .
            '<div class="planespinner"><div class="rotating-plane"></div></div>');
        $this->buildModal("server-message", "Antwort vom Server", "...");
        $this->buildModal("server-question", "Antwort vom Server", "...", "Ok", "Fenster schließen");
        $this->buildModal(
            "rename-file",
            "Datei umbenennen",
            "<div class='form-group'>
                            <label for='rename-file-oldname'>Ursprünglicher Name</label>
                            <input type='text' class='form-control' id='rename-file-oldname' readonly='readonly'>
                        </div>
                        <div class='form-group'>
                            <label for='rename-file-newname'>Neuer Name</label>
                            <input type='text' class='form-control' id='rename-file-newname'>
                        </div>"
        );
        $this->buildModal(
            "delete-file",
            "Datei löschen",
            "<div class='form-group'>
                            <label for='delete-file-name'>Name</label>
                            <input type='text' class='form-control' id='delete-file-name' readonly='readonly'>
                        </div>
                        <div class='form-group'>
                            <label for='delete-file-size'>Größe</label>
                            <input type='text' class='form-control' id='delete-file-size' readonly='readonly'>
                        </div>",
            "Abbruch",
            "Datei löschen",
            true
        );
        $this->buildModal(
            "please-reload",
            "Bitte Formular speichern und neu zum Bearbeiten öffnen",
            "Dieses Formularelement verändert die Bearbeitbarkeit von Formularfeldern. Das Formular sollte daher nun gespeichert und neu zum Bearbeiten geöffnet werden.",
            "Abbruch",
            "Formular speichern und neu zum Bearbeiten öffnen"
        );
        $this->buildModal(
            "server-error",
            "<div class='default-head'>Es ist ein unerwarteter Fehler aufgetreten.</div><div class='js-head'></div>",
            "<div class='default-content'>Die Seite wird gleich automatisch neu geladen.<div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'danger'
        );
        $this->buildModal(
            "server-success",
            "<div class='default-head'>Request erfolgreich.</div><div class='js-head'></div>",
            "<div class='default-content'><div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'success'
        );
        $this->buildModal(
            "server-warning",
            "<div class='default-head'>Warnung</div><div class='js-head'></div>",
            "<div class='default-content'><div class='msg'></div></div><div class='js-content'></div>",
            null,
            null,
            'warning'
        );
        $this->buildModal(
            "server-file",
            "<div class='js-head'></div>",
            "<div class='js-content'></div>",
            null,
            null,
            'success'
        );
    }

    private function buildModal($id, $titel, $bodycontent, $abortLabel = null, $actionLabel = null, $danger = false): void
    {
        if ($danger === 'danger') {
            $buttonType1 = "primary";
            $buttonType2 = "danger";
        } else {
            $buttonType1 = "default";
            $buttonType2 = "primary";
        }
        $hasFooter = isset($abortLabel) || isset($actionLabel);
        ?>
        <div class='modal fade' id='<?= $id ?>-dlg' tabindex='-1' role='dialog'
             aria-labelledby='<?= $id ?>-label'>
            <div class='modal-dialog' <?= ($danger === 'danger') ? 'style="min-width: 75%;"' : '' ?> role='document'>
                <div class='modal-content'>
                    <div class='modal-header<?=
                    (($danger) ? " btn-$danger' style='border-top-left-radius: 5px; border-top-right-radius: 5px;" : '') ?>'>
                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span
                                    aria-hidden='true'>&times;</span>
                        </button>
                        <h4 class='modal-title' id='<?= $id ?>-label'><?= $titel ?></h4>
                    </div>
                    <div class='modal-body' id='<?= $id ?>-content'>
                        <?= $bodycontent ?>
                    </div>
                    <?php if ($hasFooter) { ?>
                        <div class='modal-footer'>
                            <?php if (isset($abortLabel)) { ?>
                                <button type='button' class='btn btn-<?= $buttonType1 ?>'
                                        data-dismiss='modal'><?= $abortLabel ?></button>
                            <?php } ?>
                            <?php if (isset($actionLabel)) { ?>
                                <button type='button' class='btn btn-<?= $buttonType2 ?>'
                                        id='<?= $id ?>-btn-action'><?= $actionLabel ?></button>

                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Print all Profiling Flags from HTMLPageRenderer::registerProfilingBreakpoint()
     */
    private function renderProfiling(): void
    {
        $sum = 0;
        $size = isset(self::$profiling_timing) ? count(self::$profiling_timing) : 0;
        if($size === 0) {
            return;
        }
        $out = "";
        for ($i = 0; $i < $size - 1; $i++) {
            $out .= "<span class='profiling-names'><strong>" . self::$profiling_names[$i] . "</strong></span>";
            $out .= "<i class='profiling-source'>" .
                basename(self::$profiling_sources[$i]["file"]) . ":" .
                self::$profiling_sources[$i]["line"] . "
                </i>";
            $sum += self::$profiling_timing[$i + 1] - self::$profiling_timing[$i];
            $out .= "<div>" . sprintf("&nbsp;&nbsp;&nbsp;%f<br>", self::$profiling_timing[$i + 1] - self::$profiling_timing[$i]) . "</div>";
        }
        $out .= "<span class='profiling-names'><strong>" . self::$profiling_names[$size - 1] . "</strong></span>";
        $out .= "<i class='profiling-source'>" .
            basename(self::$profiling_sources[$size - 1]["file"]) . ":" .
            self::$profiling_sources[$size - 1]["line"] . "
                </i>";
        //Wrapp all output till now with div
        $out = '<div class="profiling-output"><h3><i class="fa fa-fw fa-angle-toggle"></i> Ladezeit: ' . sprintf("%f", $sum) . '</h3>' . $out;
        $out .= "</div>";
        echo $out;
    }

    private function renderFooter() : void
    {
        ?>
        </body>
        </html>
        <?php
    }

    /**
     * @return string
     */
    public function getBodyContent() : string
    {
        return $this->bodyContent;
    }

    public function appendRendererContent(Renderer $renderObject) : void
    {
        ob_start();
        $renderObject->render();
        $content = ob_get_clean();
        $this->appendToBody($content);
    }

    public function appendToBody($htmlContent) : void
    {
        $this->bodyContent .= $htmlContent;
    }

    public static function redirect($url) : void
    {
        header('Location: ' . $url);
        die();
    }


}

