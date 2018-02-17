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
        if(hasGroup("ref-finanzen")){
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
                                    <i class="fa fw fa-togglebox"></i> <?= $gremium ?>
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
                                                        <i class="fa fw fa-togglebox"></i><span
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
    public static function renderHaushaltsplan(){ ?>
        <div class="main container col-md-10">
            <!--<form>
                <div class="input-group col-md-3 pull-right">
                    <input type="number" class="form-control" name="year" value=<?=date("Y")?>>
                    <div class="input-group-btn">
                        <button type="submit" class="btn btn-primary"><i class="fa fw fa-refresh"></i> Aktualisieren</button>
                    </div>
                </div>
            </form>-->
            <h1>Haushaltspläne</h1>
            <?php

            $res = dbFetchAll("antrag",["type" => "haushaltsplan"],["lastupdated"]);
            $header = ["ID", "Jahr", "Status","zuletzt geändert"];
            ?>
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
                <?php foreach ($res as $row){ ?>
                <?php if(count($row) === 0) continue;?>
                    <tr>
                        <td><?php echo $row["id"];?></td>
                        <td><?php echo generateLinkFromID($row["revision"],$row["token"]); ?></td>
                        <td><div class="label label-primary"><?php echo getStateString($row["type"],$row["revision"],$row["state"]);?></div></td>
                        <td><?php echo $row["lastupdated"];?></td>
                    </tr>
                <?php }?>

                </tbody>
            </table>
        </div>
        <?php
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
            $res[$data["name"]] = dbFetchAll("antrag",$fields,[],[],true,true);
            $ids = array_keys($res[$data["name"]]);
            foreach($ids as $id){
                $res[$data["name"]][$id]["_inhalt"] = betterValues(dbFetchAll("inhalt",["antrag_id" => $id]));
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