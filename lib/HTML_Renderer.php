<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 03.02.18
 * Time: 14:43
 */

class HTML_Renderer
{
    private function __construct()
    {
        //This is a (static) singelton. This cannot be called from Outside.
    }

    public static function renderProjekte($gremien)
    {
        $projekte = getProjectFromGremium($gremien,"projekt-intern");
        if(AuthHandler::getInstance()->hasGroup("ref-finanzen")){
            $extVereine = ["Bergfest.*",".*KuKo.*",".*ILSC.*","Market Team.*",".*Second Unit Jazz.*", "hsf.*","hfc.*", "FuLM.*","KSG.*"];
            $ret = getProjectFromGremium($extVereine,"extern-express");
            if($ret !== false){
                //var_dump($ret);
                $projekte = array_merge($projekte,$ret);
            }
        }
        //var_dump($projekte);
        ?>
        <div class="col-md-9 container main">
            <div class="panel-group" id="accordion">
                <?php $i = 0;
                if (isset($projekte)) {
                    foreach ($projekte as $gremium => $inhalt) {
                        if (count($inhalt) == 0) continue; ?>
                        <div class="panel panel-default">
                            <div class="panel-heading collapsed" data-toggle="collapse" data-parent="#accordion"
                                 href="#collapse<?php echo $i; ?>">

                                <h4 class="panel-title">
                                    <i class="fa fa-fw fa-togglebox"></i> <?= $gremium ?>
                                </h4>
                            </div>
                            <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <?php $j = 0; ?>
                                    <div class="panel-group" id="accordion<?php echo $i; ?>">
                                        <?php foreach ($inhalt as $id => $projekt) { ?>
                                            <div class="panel panel-default">
                                                <div class="panel-link"><?php echo generateLinkFromID($id, $projekt["token"]) ?>
                                                </div>
                                                <div class="panel-heading collapsed <?= count($projekt["_ref"]) === 0 ? "empty" : "" ?>"
                                                     data-toggle="collapse" data-parent="#accordion<?php echo $i ?>"
                                                     href="#collapse<?php echo $i . "-" . $j; ?>">
                                                    <h4 class="panel-title">
                                                        <i class="fa fa-fw fa-togglebox"></i><span
                                                                class="panel-projekt-name"><?= $projekt["_inhalt"]["projekt.name"] ?></span>
                                                        <span class="label label-info project-state-label"><?php echo getStateString($projekt["type"], $projekt["revision"], $projekt["state"]); ?></span>
                                                    </h4>
                                                </div>
                                                <?php if (count($projekt["_ref"]) !== 0) {
                                                    ; ?>
                                                    <div id="collapse<?php echo $i . "-" . $j; ?>"
                                                         class="panel-collapse collapse">
                                                        <div class="panel-body">


                                                            <table class="table">
                                                                <thead>
                                                                <th>ID</th>
                                                                <th>Type</th>
                                                                <th>Antragsteller</th>
                                                                <th>Betrag</th>
                                                                <th>Status</th>
                                                                </thead>
                                                                <tbody>
                                                                <?php foreach ($projekt["_ref"] as $a_id => $a_inhalt) { ?>
                                                                    <tr>
                                                                        <td><?= generateLinkFromID($a_id, $a_inhalt["token"]) ?></td>
                                                                        <td><?= $a_inhalt["type"] ?></td>
                                                                        <td><?= $a_inhalt["_inhalt"]["antragsteller.name"] ?></td>
                                                                        <td>FIXME</td>
                                                                        <td>
                                                                            <span class="label label-info"><?php echo getStateString($a_inhalt["type"], $a_inhalt["revision"], $a_inhalt["state"]); ?></span>
                                                                        </td>
                                                                    </tr>
                                                                <?php } ?>
                                                                </tbody>
                                                            </table>

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
                }
                ?>
            </div>
        </div>
        <?php
    }
    /**
    * @param int $selected_id default: oldest HHP with state final
    *
    * @return bool|void false if error else void
    */
    public static function renderHaushaltsplan($selected_id = null){?>
        <div class="main container col-md-11"> <?php
        $hhps = dbFetchAll("antrag", [], ["type" => "haushaltsplan"], [], ["lastupdated" => 0], true, true);
        if(!isset($selected_id)){
            foreach ($hhps as $id => $hhp){
                if($hhp["state"] === "final"){
                    $selected_id = $id;
                }
            }
        } ?>
            <form>
                <div class="input-group col-xs-2 pull-right">
                    <!--<input type="number" class="form-control" name="year" value=<?=date("Y")?>>-->
                    <input type="hidden" name="tab" value="hhp">
                    <select class="selectpicker" name="id"><?php
                    foreach($hhps as $id => $hhp){?>
                        <option value="<?=$id?>" <?= $id == $selected_id ? "selected" : ""?> data-subtext="<?= getStateString($hhp["type"],$hhp["revision"],$hhp["state"])?>"><?=$hhp["revision"]?></option>
                    <?php } ?>
                    </select>
                    <div class="input-group-btn">
                        <button type="submit" class="btn btn-primary load-hhp"><i class="fa fa-fw fa-refresh"></i> Aktualisieren</button>
                    </div>
                </div>
            </form>
            <?php if(array_search($selected_id,array_keys($hhps)) === false){
                die("Konnte zugehörigen HHP nicht finden. :(");
                return false;
            }
            $editable = ($hhps[$selected_id]["state"] !== "final");
            ?>
            <button class="btn btn-danger">Diesen HHP löschen</button>
            <button class="btn btn-primary">neuen HHP anlegen</button>
            <button class="btn btn-warning">Statuswechsel</button>
            <?php
            $hhp = $hhps[$selected_id];
            $groups = dbFetchAll("haushaltstitel", ["hhpgruppen_id","gruppen_name","titel_nr","titel_name","einnahmen","ausgaben"], ["hhp_id" => $selected_id], [["table" => "haushaltsgruppen","on" => ["haushaltstitel.hhpgruppen_id","haushaltsgruppen.id"], "type" => "inner"]], ["titel_nr" => true], true, false);
            //var_dump($groups);
            ?>
            <h1>Haushaltsplan <?= $hhp["revision"]." (".getStateString($hhp["type"],$hhp["revision"],$hhp["state"]). ")" ?></h1>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th>Titel Nr</th>
                        <th>Titel Name</th>
                        <th class="money">soll-Einnahmen</th>
                        <th class="money">ist-Einnahmen</th>
                        <th class="money">soll-Ausgaben</th>
                        <th class="money">ist-Ausgaben</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($groups as $group){
                    if(count($group) === 0) continue;
                    ?>
                <tr>
                    <th class="bg-info" colspan="7"><?= $group[0]["gruppen_name"] ?></th>
                </tr>
                    <?php
                    foreach ($group as $row){ ?>
                        <tr>
                            <td></td>
                            <td><?= $row["titel_nr"]?></td>
                            <td><?= $row["titel_name"]?></td>
                            <td class="money"><?= $row["einnahmen"] != 0? convertDBValueToUserValue($row["einnahmen"],"money"):""?></td>
                            <td class="money"><?= 0 != 0? convertDBValueToUserValue($row["einnahmen"],"money"):""?></td>
                            <td class="money"><?= $row["ausgaben"] != 0 ? convertDBValueToUserValue($row["ausgaben"],"money"):""?></td>
                            <td class="money"><?= 0 != 0 ? convertDBValueToUserValue($row["ausgaben"],"money"):""?></td>
                        </tr>
                    <?php
                    }
                } ?>
                </tbody>
            </table>
        </div> <?php
        return;
    }

    public static function renderMyProfile($nonce)
    {
        $iban = getUserIBAN();
        $form = [
            "layout" => [
                ["id" => "myiban",
                    "type" => "iban",
                    "title" => "meine IBAN",
                    "value" => $iban ? $iban: "",
                    "placeholder" => "DE ...",
                    "width" => 12,
                    "opts" => ["required"],
                ],
            ],
        ];
        ?>
    
        <div class="container main col-md-6">
        <form id="editantrag" role="form" action="<?= $_SERVER["PHP_SELF"]; ?>" method="POST"
              enctype="multipart/form-data" class="ajax">
            <input type="hidden" name="action" value="mykonto.update"/>
            <input type="hidden" name="nonce" value="<?= $nonce; ?>"/>
            <?php renderForm($form); ?>
            <a href="javascript:void(false);" class='btn btn-success submit-form validate pull-right' data-name="iban"
               data-value="">Speichern</a>
        </form>
        
        <?php
        
    }
    public static function renderTable($groups,$mapping){
        $res = [];
        $header =  ["ID","Name","Organisation","Summe","Status","letzte Änderung"];

        if(!isset($groups)) return "groups leer";
        if(!isset($mapping)) return "Mapping leer";
        if(count($mapping) !== count($groups)) return "Mapping stimmt nicht mit Groups überein (Anzahl)";
        $name2Nr = [];
        foreach($groups as $nr => $data){
            $name2Nr[$data["name"]] = $nr;
            $fields = $data["fields"];
            $res[$data["name"]] = dbFetchAll("antrag", [], $fields, [], [], true, true);
            $ids = array_keys($res[$data["name"]]);
            foreach($ids as $id){
                $res[$data["name"]][$id]["_inhalt"] = betterValues(dbFetchAll("inhalt",[],["antrag_id" => $id]));
                //var_dump($res[$data["name"]]);
            }
            //var_dump($res);
        }
        //var_dump($name2Nr);
        //var_dump($mapping);
        ?>
        <div class="col-md-9 container main">
            <table class="table">
                <thead>
                    <tr>
                        <?php
                        foreach ($header as $titel){
                            echo "<th>$titel</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($res as $name => $inhalt){ ?>
                <tr><th id="par" colspan="6"><?php echo $name;?></th></tr>
                <?php foreach($inhalt as $id => $row){ ?>
                    <tr>
                        <td><?php echo $id;?></td>
                        <td><?php echo generateLinkFromID($row["_inhalt"][$mapping[$name2Nr[$name]]["p-name"]],$row["token"]); ?></td>
                        <td><?php echo $row["_inhalt"][$mapping[$name2Nr[$name]]["org-name"]]; ?></td>
                        <td>Beantragte Summe</td>
                        <td><div class="label label-primary"><?php echo getStateString($row["type"],$row["revision"],$row["state"]);?></div></td>
                        <td><?php echo $row["lastupdated"];?></td>
                    </tr>
                <?php }?>
            <?php }?>

                </tbody>
            </table>
        </div>
    <?php
    }

}