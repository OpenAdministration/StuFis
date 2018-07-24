<?php

//require_once (SYSBASE . "/lib/xmlrpc/xrpcClient.php");
require_once 'XML/RPC2/Client.php';

class HibiscusXMLRPCConnector extends Singleton{
    private static $HIBISCUS_PASSWORD;
    private static $HIBISCUS_USERNAME;
    private static $HIBISCUS_BASE_URL;
    private static $HIBISCUS_RPCPATH;
    
    //private $xmlClient;
    
    /**
     * HibiscusXMLRPCConnector constructor.
     */
    protected function __construct(){
        //remove trailing slashes from URL
        if (substr(self::$HIBISCUS_BASE_URL, -1, 1) === "/"){
            self::$HIBISCUS_BASE_URL = substr(self::$HIBISCUS_BASE_URL, 0, strlen(self::$HIBISCUS_BASE_URL) - 1);
        }
        //DELETEME for new client
        self::$HIBISCUS_BASE_URL = self::$HIBISCUS_BASE_URL . self::$HIBISCUS_RPCPATH;
        //DELETEME END
    
        /*$xmlClient = new \xmlrpc\xrpcClient(
            self::$HIBISCUS_BASE_URL,
            self::$HIBISCUS_USERNAME,
            self::$HIBISCUS_PASSWORD,
            self::$HIBISCUS_RPCPATH
        );*/
    }
    
    /**
     * @param $name
     * @param $value
     *
     * @throws Exception
     */
    final static protected function static__set($name, $value){
        if (property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in " . get_class());
    }
    
    public function fetchFromHibiscus(){
        // get data from RPC
        try{
            $client = XML_RPC2_Client::create(
                "https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) .
                "@" . rawurldecode(self::$HIBISCUS_BASE_URL) . "xmlrpc/hibiscus.xmlrpc.konto",
                ["sslverify" => false, "debug" => false, "prefix" => "hibiscus.xmlrpc.konto."]
            );
            $kto = $client->find();
            if (!isset($kto)){
                die("");
            }else if (count($kto) == 0){
                die("Kein Bankkonto auf FINTS eingerichtet.");
            }else if (count($kto) > 1){
                die("Mehr als ein Bankkonto auf FINTS eingerichtet.");
            }
        
            $kto = $kto[0];
            $ktoid = $kto["id"];
            $letzteBankSync =
                substr($kto["saldo_datum"], 6, 4) . "-" .
                substr($kto["saldo_datum"], 3, 2) . "-" .
                substr($kto["saldo_datum"], 0, 2);
            $letzteBankSyncTS = strtotime($letzteBankSync);
            $showStatus = false;
            if ($letzteBankSyncTS < time() - 2 * 24 * 60 * 60){
                echo '<div class="alert alert-danger">FINTS hat die letzten 24h keine Synchronisation mit der Bank durchgeführt. Die angezeigten Umsätze können unvollständig sein.</div>';
            }
        
            $umsopt = ["konto_id" => $ktoid];
        
            /*$url = "https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/webadmin/rest/system/status";
            echo htmlspecialchars($url);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response === false){
                echo '<div class="alert alert-warning">FINTS hat keinen Status geliefert.</div>';
            }else{
                print_r($response);
                
                $response = json_decode($response, true);
                if ($response === null || !is_array($response) || !isset($response["type"]) || !isset($response["title"]) || !isset($response["text"])){
                    echo '<div class="alert alert-warning">FINTS hat keinen parsbaren Status geliefert.</div>';
                }else{
                    switch ($response["type"]){ # see src/de/willuhn/jameica/messaging/StatusBarMessage.java
                        case 0: # OK
                        case 2: # INFO
                            $cls = "success";
                            break;
                        case 1: # ERROR
                        default:
                            $cls = "danger";
                            $showStatus = true;
                    }
                    if ($showStatus){
                        echo '<div class="alert alert-' . $cls . '">' . htmlspecialchars("FINTS (" . $response["title"] . "): " . $response["text"]) . '</div>';
                    }
                }
            }*/
        
            # letzter abgerufener Umsatz
            $lastUmsatzId = DBConnector::getInstance()->dbGetLastHibiscus();
            if ($lastUmsatzId === null)
                $lastUmsatzId = false;
        
            $umsopt["datum:min"] = "2017-01-01";
            if ($lastUmsatzId !== false)
                $umsopt["id:min"] = 1 + $lastUmsatzId;
        
            $client = XML_RPC2_Client::create("https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_BASE_URL) . "/xmlrpc/hibiscus.xmlrpc.umsatz", ["sslverify" => false, "debug" => false, "prefix" => "hibiscus.xmlrpc.umsatz."]);
            $umsatz = $client->list($umsopt);
            usort($umsatz, function($a, $b){
                if ($a["id"] < $b["id"]) return -1;
                if ($a["id"] > $b["id"]) return 1;
                return 0;
            });
        
            return $umsatz;
        
        }catch (XML_RPC2_CurlException $e){
            return false;
        }
    }
    
    public function fetchFromHibiscusAnfangsbestand(){
        $year = date("Y");
        /*
        $f = ["type" => "kontenplan"];
        $f["state"] = "final";
        $f["revision"] = $year;
        $al = DBConnector::getInstance()->dbFetchAll("antrag", [], $f);
        if (count($al) != 1) die("Kontenplan nicht gefunden: " . print_r($f, true));
        $kpId = $al[0]["id"];
        
        // check anfangsbestand already saved
        if (DBConnector::getInstance()->dbHasAnfangsbestand("01 01", $kpId)){
            return [];
        }*/
    
        $client = XML_RPC2_Client::create("https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_BASE_URL) . "/xmlrpc/hibiscus.xmlrpc.konto", ["sslverify" => false, "debug" => false, "prefix" => "hibiscus.xmlrpc.konto."]);
        $kto = $client->find();
        
        if (count($kto) == 0){
            die("Kein Bankkonto auf FINTS eingerichtet.");
        }
        if (count($kto) > 1){
            die("Mehr als ein Bankkonto auf FINTS eingerichtet.");
        }
        
        $kto = $kto[0];
        $ktoid = $kto["id"];
        $showStatus = false;
        
        $umsopt = ["konto_id" => $ktoid];
        
        /*$url = "https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/webadmin/rest/system/status";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === false){
            echo '<div class="alert alert-warning">FINTS hat keinen Status geliefert.</div>';
        }else{
            echo "terst";
            echo $response;
            echo "3ews";
            $response = json_decode($response, true);
            if ($response === null || !is_array($response) || !isset($response["type"]) || !isset($response["title"]) || !isset($response["text"])){
                echo '<div class="alert alert-warning">FINTS hat keinen parsbaren Status geliefert.</div>';
            }else{
                switch ($response["type"]){ # see src/de/willuhn/jameica/messaging/StatusBarMessage.java
                    case 0: # OK
                    case 2: # INFO
                        $cls = "success";
                        break;
                    case 1: # ERROR
                    default:
                        $cls = "danger";
                        $showStatus = true;
                }
                if ($showStatus){
                    echo '<div class="alert alert-' . $cls . '">' . htmlspecialchars("FINTS (" . $response["title"] . "): " . $response["text"]) . '</div>';
                }
            }
        }*/
        /*
        // brauche umsatz vor $year-01-01 und nach $(year-1)-12-31
        $umsopt["datum:min"] = "$year-01-01";
        $umsopt["datum:max"] = "$year-12-31";
        
        $client = XML_RPC2_Client::create("https://" . rawurldecode(self::$HIBISCUS_USERNAME) . ":" . rawurlencode(self::$HIBISCUS_PASSWORD) . "@" . rawurldecode(self::$HIBISCUS_URL) . "/xmlrpc/hibiscus.xmlrpc.umsatz",
            ["sslverify" => false, "debug" => false, "prefix" => "hibiscus.xmlrpc.umsatz."]);
        $umsatzImJahr = $client->list($umsopt);
        
        $yearBefore = $year - 1;
        $umsopt["datum:min"] = "$yearBefore-01-01";
        $umsopt["datum:max"] = "$yearBefore-12-31";
        $umsatzImJahrDavor = $client->list($umsopt);
        
        if (count($umsatzImJahr) == 0 || count($umsatzImJahrDavor) == 0) // noch keine Umsätze
            return [];
        
        // lezter Umsatz im Jahr davor
        usort($umsatzImJahrDavor, function($a, $b){
            if ($a["id"] < $b["id"]) return -1;
            if ($a["id"] > $b["id"]) return 1;
            return 0;
        });
        
        $uLastBefore = array_pop($umsatzImJahrDavor);
        $saldo = $this->tofloatHibiscus($uLastBefore['saldo']);
        echo $saldo;
        /*
        $newForms = [];
        
        $datum = "$year-01-01";
        
        $inhalt = [];
        
        $inhalt[] = ["fieldname" => "zahlung.einnahmen", "contenttype" => "money", "value" => $saldo];
        
        $inhalt[] = ["fieldname" => "zahlung.datum", "contenttype" => "date", "value" => $datum];
        
        $inhalt[] = ["fieldname" => "zahlung.konto", "contenttype" => "ref", "value" => "01 01"];
        
        $inhalt[] = ["fieldname" => "kontenplan.otherForm", "contenttype" => "otherForm", "value" => $kpId];
        
        $newForms[] = $inhalt;
        */
        $newForms = [];
        return $newForms;
    }
    
    private function tofloatHibiscus($num){
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        
        if (($dotPos === false) && ($commaPos === false)){
            $sep = false;
        }else if ($dotPos !== false){
            $sep = $dotPos;
        }else if ($commaPos !== false){
            $sep = $commaPos;
        }else if ($commaPos > $dotPos){
            $sep = $commaPos;
            die("impossible");
        }else{
            $sep = $dotPos;
            die("impossible");
        }
        
        if ($sep === false){
            return floatval(preg_replace("/[^0-9\+\-]/", "", $num));
        }
        
        return floatval(
            preg_replace("/[^0-9\+\-]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9\+\-]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }
}
