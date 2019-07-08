<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.06.18
 * Time: 02:02
 */

abstract class Renderer
    extends EscFunc
{
    abstract public function render();

    /**
     * @param array $header
     * @param array $groupedContent
     * @param array $keys
     * @param array $escapeFunctions
     * @param array $footer
     */
    protected function renderTable(
        array $header, array $groupedContent, array $keys = [], array $escapeFunctions = [], array $footer = []
    ){
        $defaultFunction = ["Renderer", "defaultEscapeFunction"];

        //throw away the keys (needed later), numeric keys need to be used
        $escapeFunctions = array_values($escapeFunctions);
        //set every function which is null or empty to default function
        array_walk(
            $escapeFunctions,
            function (&$val) use ($defaultFunction) {
                if (!isset($val) || empty($val)) {
                    $val = $defaultFunction;
                }
            }
        );
        //count the parameters of all functions, which can be used
        $reflectionsOfFunctions = [];
        $isReflectionMethods = [];
        $paramSum = 0;


        try {
            foreach ($escapeFunctions as $idx => $escapeFunction) {
                if (is_array($escapeFunction)) {
                    $rf = new ReflectionMethod($escapeFunction[0], $escapeFunction[1]);
                    $isReflectionMethods[] = true;
                } else {
                    $rf = new ReflectionFunction($escapeFunction);
                    $isReflectionMethods[] = false;
                }
                //let some space in the idx
                $reflectionsOfFunctions[$idx] = $rf;
                $paramSum += $rf->getNumberOfParameters();
            }
            //if there are to less parameters - add some default functions.
            $diff = count($header) - count($escapeFunctions);
            if ($diff > 0) {
                $paramSum += $diff;
                $escapeFunctions = array_merge(
                    $escapeFunctions,
                    array_fill(0, $diff, $defaultFunction)
                );
                $isReflectionMethods = array_merge($isReflectionMethods, array_fill(0, $diff, true));
                $reflectionsOfFunctions = array_merge(
                    $reflectionsOfFunctions,
                    array_fill(
                        0,
                        $diff,
                        new ReflectionMethod(
                            $defaultFunction[0], $defaultFunction[1]
                        )
                    )
                );
            }
            foreach ($groupedContent as $groupName => $content) {
                if (empty($content))
                    continue;
                if (count(reset($content)) != $paramSum && count($keys) != $paramSum) {
                    ErrorHandler::_errorExit(
                        "In Gruppe '$groupName' passt Spaltenzahl (" . count(
                            reset($content)
                        ) . ") bzw. Key Anzahl (" . count(
                            $keys
                        ) . ") nicht zur benötigten Parameterzahl $paramSum \n es wurden " . count(
                            $escapeFunctions
                        ) . " Funktionen übergeben " . $diff . " wurde(n) hinzugefügt."
                    );
                }
            }
        } catch (ReflectionException $reflectionException) {
            ErrorHandler::_errorExit("Reflection not working..." . $reflectionException->getMessage());
        }

        if (count($keys) == 0) {
            $keys = range(0, $paramSum);
            $assoc = false;
        } else {
            $assoc = true;
        }

        ?>
        <table class="table">
            <thead>
            <tr>
                <?php
                foreach ($header as $titel) {
                    echo "<th>$titel</th>";
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($groupedContent as $groupName => $rows) {
                if (!is_int($groupName)) { ?>
                    <tr>
                        <th class="bg-info" colspan="<?= count($header) ?>"><?php echo $groupName; ?></th>
                    </tr>
                <?php }
                foreach ($rows as $row) {
                    //echo count($row) . "-". count($escapeFunctions) . "-". count($header);
                    ?>
                    <tr>
                        <?php
                        //throw away keys
                        if (!$assoc) {
                            $row = array_values($row);
                        }

                        $shiftIdx = 0;
                        foreach ($reflectionsOfFunctions as $idx => $reflectionOfFunction) {
                            //var_export($keys);
                            $arg_keys = array_slice(
                                $keys,
                                $shiftIdx,
                                $reflectionOfFunction->getNumberOfParameters()
                            );
                            $args = [];
                            foreach ($arg_keys as $arg_key) {
                                $args[] = $row[$arg_key];
                            }
                            //var_export($args);
                            //var_export($row);
                            //var_export($reflectionOfFunction->getNumberOfParameters());
                            $shiftIdx += $reflectionOfFunction->getNumberOfParameters();
                            if ($isReflectionMethods[$idx]) {
                                echo "<td>" . call_user_func_array($escapeFunctions[$idx], $args) . "</td>";
                            } else {
                                echo "<td>" . $reflectionOfFunction->invokeArgs($args) . "</td>";
                            }

                        } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
            <?php if ($footer && is_array($footer) && count($footer) > 0) { ?>
                <tfoot> <?php
                if (!is_array(array_values($footer)[0])) {
                    $footer = [$footer];
                }
                foreach ($footer as $foot_line) {
                    echo '<tr>';
                    foreach ($foot_line as $foot) {
                        echo "<th>$foot</th>";
                    }
                    echo '</tr>';
                }
                ?>
                </tfoot>
            <?php } ?>
        </table>
    <?php }

    protected function renderHeadline($text, int $headlineNr = 1)
    {
        echo "<h" . htmlspecialchars($headlineNr) . ">" . htmlspecialchars($text) . "</h" . htmlspecialchars(
                $headlineNr
            ) . ">";
    }

    protected function formatDateToMonthYear($dateString)
    {
        return !empty($dateString) ? strftime("%b %G", strtotime($dateString)) : "";
    }

    protected function renderHiddenInput($name, $value)
    { ?>
        <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
        <?php
    }

    /**
     * @param $data array
     * @param $groupHeaderFun
     * @param $innerHeaderHeadlineFun
     * @param $innerHeaderFun
     * @param $innerContentFun
     */
    protected function renderAccordionPanels(array $data, $groupHeaderFun, $innerHeaderHeadlineFun, $innerHeaderFun, $innerContentFun)
    { ?>
        <div class="panel-group" id="accordion">
            <?php $i = 0;
            if (isset($data) && !empty($data) && $data) {
                foreach ($data as $groupHeadline => $groupContent) {
                    if (count($groupContent) == 0)
                        continue; ?>
                    <div class="panel panel-default">
                        <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                             href="#collapse<?php echo $i; ?>">
                            <h4 class="panel-title">
                                <i class="fa fa-fw fa-togglebox"></i>&nbsp;<?= $groupHeaderFun($groupHeadline) ?>
                            </h4>
                        </div>
                        <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php $j = 0; ?>
                                <div class="panel-group" id="accordion<?php echo $i; ?>">
                                    <?php foreach ($groupContent as $content) { ?>
                                        <div class="panel panel-default">
                                            <div class="panel-link">
                                                <?= $innerHeaderHeadlineFun($content); ?>
                                            </div>
                                            <div class="panel-heading collapsed <?= (!isset($content["subcontent"])
                                                || count($content["subcontent"]) === 0) ? "empty" : "" ?>"
                                                 data-toggle="collapse" data-parent="#accordion<?php echo $i ?>"
                                                 href="#collapse<?php echo $i . "-" . $j; ?>">
                                                <h4 class="panel-title">
                                                    <i class="fa fa-togglebox"></i>
                                                    <span class="panel-projekt-name">
                                                        <?= $innerHeaderFun($content); ?>
                                                    </span>
                                                </h4>
                                            </div>
                                            <?php if (isset($content["subcontent"]) && count($content["subcontent"]) > 0) { ?>
                                                <div id="collapse<?php echo $i . "-" . $j; ?>"
                                                     class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <?= $innerContentFun($content["subcontent"]) ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>

                                        <?php $j++;
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $i++;
                }
            } else {
                $this->renderAlert(
                    "Warnung",
                    "In deinen Gremien wurden bisher keine Projekte angelegt. Fange doch jetzt damit an! <a href='" . URIBASE . "projekt/create'>Neues Projekt erstellen</a>",
                    "warning"
                );
            } ?>
        </div>
        <?php
    }

    protected function renderNonce()
    {
        $this->renderHiddenInput("nonce", $GLOBALS["nonce"]);
        $this->renderHiddenInput("nononce", $GLOBALS["nonce"]);
    }

    /**
     *
     * @param $strongMsg
     * @param $msg
     * @param $type string has to be <i>"success"</i>, "info", "warning" or "danger"
     */
    protected function renderAlert($strongMsg, $msg, $type = "success")
    {
        if (!in_array($type, ["success", "info", "warning", "danger"])) {
            ErrorHandler::_renderError("Falscher Datentyp in renderAlert()", 405);
        }
        ?>
        <div class="alert alert-<?= $type ?>">
            <strong><?= $strongMsg ?></strong> <?= $msg ?>
        </div>
        <?php
    }

    protected function makeClickableMails($text)
    {
        //$text = htmlspecialchars($text);
        $matches = [];
        preg_match_all('#[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}#', $text, $matches);
        //var_dump($matches[0]);
        foreach ($matches[0] as $match) {
            $text = str_replace($match, $this->mailto($match), $text);
        }
        return $text;
    }

    protected function mailto($adress)
    {
        return "<a href='mailto:$adress'><i class='fa fa-fw fa-envelope'></i>$adress</a>";
    }

    protected function makeProjektsClickable($text)
    {
        $matches = [];
        $text = htmlspecialchars($text);
        preg_match("/IP-[0-9]{2,4}-[0-9]+-A[0-9]+/", $text, $matches);
        foreach ($matches as $match) {
            $array = explode("-", $match);
            $auslagen_id = substr(array_pop($array), 1);
            $projekt_id = array_pop($array);
            $text = str_replace(
                $match,
                "<a target='_blank' href='" . URIBASE . "projekt/$projekt_id/auslagen/$auslagen_id'><i class='fa fa-fw fa-chain'></i>$match</a>",
                $text
            );
        }
        return $text;
    }

    protected function renderHHPSelector($routeInfo, $urlPrefix = URIBASE, $urlSuffix = "/")
    {
        $hhps = DBConnector::getInstance()->dbFetchAll(
            "haushaltsplan",
            [
                DBConnector::FETCH_ASSOC,
                DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY
            ],
            [],
            [],
            [],
            ["von" => false]
        );
        if (!isset($hhps) || empty($hhps)){
            ErrorHandler::_errorExit("Konnte keine Haushaltspläne finden");
        }
        if (!isset($routeInfo["hhp-id"])){
            foreach (array_reverse($hhps, true) as $id => $hhp){
                if ($hhp["state"] === "final"){
                    $routeInfo["hhp-id"] = $id;
                }
            }
        }
        ?>
        <form action="<?= $urlPrefix . $routeInfo["hhp-id"] . $urlSuffix ?>"
              data-action='<?= $urlPrefix . "%%" . $urlSuffix ?>'>
            <div class="input-group col-xs-2 pull-right hhp-selector">
                <select class="selectpicker" id="hhp-id"><?php
                    foreach ($hhps as $id => $hhp){
                        $von = date_create($hhp["von"])->format("M Y");
                        $bis = !empty($hhp["bis"]) ? date_create($hhp["bis"])->format("M Y") : false;
                        $name = $bis ? $von . " bis " . $bis : "ab " . $von;
                        ?>
                        <option value="<?= $id ?>" <?= $id == $routeInfo["hhp-id"] ? "selected" : "" ?>
                                data-subtext="<?= $hhp["state"] ?>"><?= $name ?>
                        </option>
                    <?php } ?>
                </select>
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-primary load-hhp"><i class="fa fa-fw fa-refresh"></i>
                        Aktualisieren
                    </button>
                </div>
            </div>
        </form>
        <?php
        return [$hhps, $routeInfo["hhp-id"]];
    }
}
