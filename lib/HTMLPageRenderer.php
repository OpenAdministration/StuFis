<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 21.06.18
 * Time: 22:00
 */

class HTMLPageRenderer{
    private static $profiling_timing, $profiling_names, $profiling_sources;
    private static $errorPage;
    private static $dev = true;
    private $uribase;
    private $bodycontent;
    private $titel;
    private $routeInfo;
    
    public function __construct($routeInfo, $bodycontent = ""){
        $this->routeInfo = $routeInfo;
        if (isset($this->routeInfo["titel"])){
            $this->titel = $this->routeInfo["titel"];
        }else{
            $this->titel = "StuRa Finanzen";
        }
        $this->bodycontent = $bodycontent;
        $this->uribase = $GLOBALS["URIBASE"];
    }
    
    /**
     * @param $name string Name des Profiling Flags
     */
    static public function registerProfilingBreakpoint(string $name){
        self::$profiling_timing[] = microtime(true);
        $bt = debug_backtrace();
        self::$profiling_sources[] = array_shift($bt);
        self::$profiling_names[] = $name;
    }
    
    static public function setErrorPage($param){
        ob_start();
        include SYSBASE . "/template/error.phtml";
        self::$errorPage = ob_get_clean();
    
    }
    
    /**
     * @return string
     */
    public function getBodycontent(): string{
        return $this->bodycontent;
    }
    
    /**
     * @param string $bodycontent
     */
    public function setBodycontent(string $bodycontent){
        $this->bodycontent = $bodycontent;
    }
    
    public function appendRendererContent(Renderer $renderObject){
        ob_start();
        $renderObject->render();
        $content = ob_get_clean();
        $this->appendToBody($content);
    }
    
    public function appendToBody($htmlcontent){
        $this->bodycontent .= $htmlcontent;
    }
    
    public function render(){
        if (isset(self::$errorPage)){
            $this->renderErrorPage();
        }
        $this->renderHtmlHeader();
        $this->renderNavbar();
        $this->renderSiteNavigation();
        echo "<div class='container main col-xs-12 col-md-10'>";
        echo $this->bodycontent;
        echo "</div>";
        $this->renderModals();
        if ($GLOBALS["DEV"]){
            $this->renderProfiling();
        }
        $this->renderFooter();
    }
    
    private function renderErrorPage(){
        $this->renderHtmlHeader();
        $this->renderNavbar();
        $this->renderSiteNavigation();
        echo "<div class='container main col-xs-12 col-md-10'>";
        echo self::$errorPage;
        echo "</div>";
        $this->renderProfiling();
        $this->renderFooter();
        exit();
    }
    
    private function renderHtmlHeader(){
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
            <meta name="author" content="">
            <meta charset="utf-8">
        </head>
        <body>
        <?php
    }
    
    private function setPhpHeader(){
        // by micha-dev
        // http://people.mozilla.com/~bsterne/content-security-policy/details.html
        // https://wiki.mozilla.org/Security/CSP/Specification
        header("X-Content-Security-Policy: allow 'self'; options inline-script eval-script");
        header("X-Frame-Options: DENY");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
    
    private function includeCSS(){
        $out = "";
        $defaultCssFiles = ["bootstrap.min", "font-awesome.min"];
        $cssFiles = $defaultCssFiles;
        if (isset($this->routeInfo["load"])){
            foreach ($this->routeInfo["load"] as $loadgroupEnum){
                $cssFiles = array_merge($cssFiles, $loadgroupEnum["css"]);
            }
        }
        foreach ($cssFiles as $cssFile){
            $out .= "<link rel='stylesheet' href='{$this->uribase}css/$cssFile.css'>" . PHP_EOL;
        }
        $out .= "<link rel='stylesheet' href='{$this->uribase}css/main.css'>" . PHP_EOL;
        return $out;
        
    }
    
    private function includeJS(){
        $out = "";
        $defaultJsFiles = [
            "jquery.min",
            "bootstrap.min",
            "validator",
            "numeral.min",
            "numeral-locales.min",
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
        if (isset($this->routeInfo["load"])){
            foreach ($this->routeInfo["load"] as $loadgroupEnum){
                $jsFiles = array_merge($jsFiles, $loadgroupEnum["js"]);
            }
        }
        //var_dump($this->routeInfo["load"]);
        foreach ($jsFiles as $jsFile){
            $out .= "<script src='{$this->uribase}js/$jsFile.js'></script>" . PHP_EOL;
        }
        $out .= "<script src='{$this->uribase}js/main.js'></script>" . PHP_EOL;
        return $out;
    }
    
    private function renderNavbar(){
        ?>

        <nav class="navbar navbar-inverse navbar-fixed-top"
            <?php
            global $DEV;
            if ($DEV)
                echo " style='background-color:darkred;'";
            ?>
        >
            <div class="container">
                <div class="navbar-header">
                    <!--                    <a class="navbar-brand" href="#">FVS - Finanz Verwaltungs System Interne Anträge</a> -->
                    <a class="navbar-brand" href="<?php echo htmlspecialchars($this->uribase); ?>">StuRa-Finanzformulare
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
                    <li>
                        <a href="<?php echo htmlspecialchars((AUTH_HANDLER)::getInstance()->getLogoutURL()); ?>">Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
        <?php
    }
    
    private function renderSiteNavigation(){
        if (isset($this->routeInfo["navigation"])){
            $activeButton = $this->routeInfo["navigation"];
        }else{
            $activeButton = "";
        }
        ?>
        <div>
            <div class="profile-sidebar">
                <!-- SIDEBAR USER TITLE -->
                <div class="profile-usertitle">
                    <div class="profile-usertitle-name">
                        <?php echo (AUTH_HANDLER)::getInstance()->getUserfullname(); ?>
                    </div>
                    <?php if ((AUTH_HANDLER)::getInstance()->isAdmin()){ ?>
                        <div class="profile-usertitle-job">
                            Admin
                        </div>
                    <?php }else if ((AUTH_HANDLER)::getInstance()->hasGroup("ref-finanzen")){ ?>
                        <div class="profile-usertitle-job">
                            Referat Finanzen
                        </div>
                    <?php } ?>

                </div>
                <!-- END SIDEBAR USER TITLE -->
                <!-- SIDEBAR BUTTONS -->
                <div class="profile-userbuttons">
                    <a href="<?= $this->uribase ?>projekt/create/edit" type="button" class="btn btn-primary btn-sm">
                        <i class="fa fa-fw fa-plus"></i>
                        neues Projekt
                    </a>
                </div>
                <!-- END SIDEBAR BUTTONS -->
                <!-- SIDEBAR MENU -->
                <div class="profile-usermenu">
                    <ul class="nav">
                        <li <?php if ($activeButton == "mygremium") echo "class='active'"; ?>>
                            <a href="<?php echo htmlspecialchars($this->uribase . "menu/mygremium"); ?>">
                                <i class="fa fa-fw fa-home"></i>
                                Meine Gremien
                            </a>
                        </li>
                        <!-- LIVE COMMENT ONLY
                        <li <?php if ($activeButton == "mykonto") echo "class='active'"; ?>>
                            <a href="<?php echo htmlspecialchars($this->uribase . "menu/mykonto"); ?>">
                                <i class="fa fa-fw fa-user-circle"></i>
                                Benutzerkonto
                            </a>
                        </li>-->
                        <?php
                        if ((AUTH_HANDLER)::getInstance()->hasGroup("ref-finanzen")){
                            ?>

                            <li <?php if ($activeButton == "hv") echo "class='active'"; ?>>
                                <a href="<?php echo htmlspecialchars($this->uribase . "menu/hv"); ?>">
                                    <i class="fa fa-fw fa-legal"></i>
                                    TODO HV
                                </a>
                            </li>
                            <li <?php if ($activeButton == "kv") echo "class='active'"; ?>>
                                <a href="<?php echo htmlspecialchars($this->uribase . "menu/kv"); ?>">
                                    <i class="fa fa-fw fa-calculator "></i>
                                    TODO KV
                                </a>
                            </li>
                            <!-- LIVE COMMENT ONLY
                            <li <?php if ($activeButton == "booking") echo "class='active'"; ?>>
                                <a href="<?php echo htmlspecialchars($this->uribase . "menu/booking"); ?>">
                                    <i class="fa fa-fw fa-book "></i>
                                    Buchungen
                                </a>
                            </li>
                            <li <?php if ($activeButton == "konto") echo "class='active'"; ?>>
                                <a href="<?= htmlspecialchars($this->uribase . "menu/konto"); ?>">
                                    <i class="fa fa-fw fa-bar-chart"></i>
                                    Konto
                                </a>
                            </li> -->
                        <?php } ?>
                        <li <?php if ($activeButton == "stura") echo "class='active'"; ?>>
                            <a href="<?php echo htmlspecialchars($this->uribase . "menu/stura"); ?>">
                                <i class="fa fa-fw fa-users"></i>
                                StuRa-Sitzung
                            </a>
                        </li>
                        <li <?php if ($activeButton == "allgremium") echo "class='active'"; ?>>
                            <a href="<?php echo htmlspecialchars($this->uribase . "menu/allgremium"); ?>">
                                <i class="fa fa-fw fa-globe"></i>
                                Alle Gremien
                            </a>
                        </li>
                        <li <?php if ($activeButton == "hhp") echo "class='active'"; ?>>
                            <a href="<?php echo htmlspecialchars($this->uribase . "hhp"); ?>">
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
    }
    
    /**
     * Print all Profiling Flags from HTMLPageRenderer::registerProfilingBreakpoint()
     */
    private function renderProfiling(){
        $sum = 0;
        $size = count(self::$profiling_timing);
        $out = "";
        for ($i = 0; $i < $size - 1; $i++){
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
    
    private function renderFooter(){
        ?>
        </body>
        </html>
        <?php
    }
    
    private function renderModals(){
        
        $this->buildModal("please-wait", "Bitte warten - Die Anfrage wird verarbeitet.", "".
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
        	NULL,
        	NULL,
        	'danger'
        );
        $this->buildModal(
        	"server-success",
        	"<div class='default-head'>Request erfolgreich.</div><div class='js-head'></div>",
        	"<div class='default-content'><div class='msg'></div></div><div class='js-content'></div>",
        	NULL,
        	NULL,
        	'success'
        );
        $this->buildModal(
        	"server-file",
        	"<div class='js-head'></div>",
        	"<div class='js-content'></div>",
        	NULL,
        	NULL,
        	'success'
        );
    }
    
    private function buildModal($id, $titel, $bodycontent, $abortLabel = null, $actionLabel = null, $danger = false){
        if ($danger=='danger'){
            $buttonType1 = "primary";
            $buttonType2 = "danger";
        }else{
            $buttonType1 = "default";
            $buttonType2 = "primary";
        }
        $hasFooter = isset($abortLabel) || isset($actionLabel);
        ?>
        <div class='modal fade' id='<?= $id ?>-dlg' tabindex='-1' role='dialog'
             aria-labelledby='<?= $id ?>-label'>
            <div class='modal-dialog' <?= ($danger=='danger')? 'style="min-width: 75%;"':'' ?> role='document'>
                <div class='modal-content'>
                    <div class='modal-header<?= 
                    	(($danger)? " btn-{$danger}' style='border-top-left-radius: 5px; border-top-right-radius: 5px;":'') ?>'>
                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span
                                    aria-hidden='true'>&times;</span>
                        </button>
                        <h4 class='modal-title' id='<?= $id ?>-label'><?= $titel ?></h4>
                    </div>
                    <div class='modal-body' id='<?= $id ?>-content'>
                        <?= $bodycontent ?>
                    </div>
                    <?php if ($hasFooter){ ?>
                        <div class='modal-footer'>
                            <?php if (isset($abortLabel)){ ?>
                                <button type='button' class='btn btn-<?= $buttonType1 ?>'
                                        data-dismiss='modal'><?= $abortLabel ?></button>
                            <?php } ?>
                            <?php if (isset($actionLabel)){ ?>
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
    
}

