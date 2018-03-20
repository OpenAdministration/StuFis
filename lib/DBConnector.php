<?php

class DBConnector extends Singelton{
    
    private static $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX, $BUILD_DB;
    private $pdo;
    private $scheme;
    private $validFields;
    private $dbWriteCounter = 0;
    
    public function __construct(){
        prof_flag("init-db-connection");
        $this->initScheme();
        $this->pdo = new PDO(self::$DB_DSN, self::$DB_USERNAME, self::$DB_PASSWORD, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE', sql_mode = 'STRICT_ALL_TABLES';", PDO::MYSQL_ATTR_FOUND_ROWS => true]);
    }
    
    private function initScheme(){
        $scheme = [];
        $scheme["antrag"] = ["id" => "INT NOT NULL AUTO_INCREMENT",
            "version" => "BIGINT NOT NULL DEFAULT 0",
            "type" => "VARCHAR(256) NOT NULL",  # Projektantrag, Fahrtkostenantrag, etc.
            "revision" => "VARCHAR(256) NOT NULL", # Version des Formulars (welche Felder erwartet)
            "creator" => "VARCHAR(256) NOT NULL",
            "creatorFullName" => "VARCHAR(256) NOT NULL",
            "createdat" => "DATETIME NOT NULL",
            "lastupdated" => "DATETIME NOT NULL",
            "token" => "VARCHAR(32) NOT NULL",
            "state" => "VARCHAR(32) NOT NULL",
            "stateCreator" => "VARCHAR(32) NOT NULL",];
        
        $scheme["inhalt"] = ["id" => "INT NOT NULL AUTO_INCREMENT",
            "antrag_id" => "INT NOT NULL",
            "fieldname" => "VARCHAR(128) NOT NULL",
            "contenttype" => "VARCHAR(128) NOT NULL", # automatisch aus Formulardefinition, zur Darstellung alter Anträge (alte Revision) ohne Metadaten
            //"array_idx" => "INT NOT NULL",
            "value" => "TEXT NOT NULL",];
        
        $scheme["anhang"] = ["id" => "INT NOT NULL AUTO_INCREMENT",
            "antrag_id" => "INT NOT NULL",
            "fieldname" => "VARCHAR(128) NOT NULL",
            "mimetype" => "VARCHAR(128) NOT NULL",
            "path" => "VARCHAR(128) NOT NULL",
            "size" => "INT NOT NULL",
            "md5sum" => "VARCHAR(128) NOT NULL",
            "state" => "ENUM('active','revoked') DEFAULT 'active' NOT NULL",
            "filename" => "VARCHAR(256) NOT NULL",];
        
        $scheme["comments"] = ["id" => "INT NOT NULL AUTO_INCREMENT",
            "antrag_id" => "INT NOT NULL",
            "timestamp" => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "creator" => "VARCHAR(128) NOT NULL",
            "creatorFullName" => "VARCHAR(256) NOT NULL",
            "text" => "varchar(2048) NOT NULL",
            "type" => "tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 = state change, 1 = comment, 2 = admin only'",];
        
        $scheme["booking"] = ["id" => "INT NOT NULL PRIMARY KEY AUTO_INCREMENT",
            "titel_id" => "int NOT NULL",
            "kostenstelle" => "int NOT NULL",
            "zahlung_id" => "INT NOT NULL",
            "beleg_id" => "INT NOT NULL",
            "user_id" => "int NOT NULL",
            "timestamp" => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "comment" => "varchar(2048) NOT NULL",
            "value" => "FLOAT NOT NULL",
            "canceled" => "INT NOT NULL DEFAULT 0",];
        
        $scheme["user"] = ["id" => "INT NOT NULL AUTO_INCREMENT",
            "fullname" => "varchar(255) NOT NULL",
            "username" => "varchar(32) NOT NULL",
            "iban" => "varchar(32) NOT NULL",];
        
        $scheme['haushaltstitel'] = ["id" => " int NOT NULL AUTO_INCREMENT",
            "hhpgruppen_id" => " int NOT NULL",
            "titel_name" => " varchar(128) NOT NULL",
            "titel_nr" => " varchar(10) NOT NULL",
            "value" => "float NOT NULL",];
        
        $scheme['haushaltsgruppen'] = ["id" => "int NOT NULL AUTO_INCREMENT",
            "hhp_id" => " int NOT NULL",
            "gruppen_name" => " varchar(128) NOT NULL",
            "type" => "tinyint(1) NOT NULL",];
        $scheme["projektposten"] = ["id" => " INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
            "projekt_id" => " INT NOT NULL",
            "titel_id" => " INT NOT NULL",
            "einnahmen" => " FLOAT NULL",
            "ausgaben" => " FLOAT NULL",
            "name" => " VARCHAR(128) NOT NULL",
            "bemerkung" => " VARCHAR(256) NOT NULL",];
        $scheme["beleg_posten"] = ["beleg_id" => "INT NOT NULL",
            "posten_id" => "INT NOT NULL",
            "antrag_id" => "INT NOT NULL",
            "einnahmen" => "FLOAT NULL",
            "ausgaben" => "FLOAT NULL",];
        
        $this->scheme = $scheme;
        
        //build valid fields out of schemes
        $validFields = ["*"];
        $blacklist = ["log", "log_property"];
        foreach ($scheme as $tblname => $content){
            $validFields[] = "$tblname.*";
            if (!is_array($content)) continue;
            if (in_array($tblname, $blacklist)) continue;
            $colnames = array_keys($content);
            $validFields = array_merge($colnames, $validFields);
            $func = function(&$val, $key) use ($tblname){
                $val = $tblname . "." . $val;
            };
            array_walk($colnames, $func);
            $validFields = array_merge($colnames, $validFields);
        }
        $this->validFields = array_unique($validFields);
        
        if (self::$BUILD_DB){
            include "../sql/buildDB.php";
            prof_flag("build-db-finished");
        }
    }
    
    final static protected function static__set($name, $value){
        if (property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in " . get_class());
    }
    
    public function logThisAction(){
        $query = $this->pdo->prepare("INSERT INTO " . self::$DB_PREFIX . "log (action, responsible) VALUES (?, ?)");
        $query->execute(Array($_REQUEST["action"], AuthHandler::getInstance()->getUsername())) or httperror(print_r($query->errorInfo(), true));
        $logId = $this->pdo->lastInsertId();
        foreach ($_REQUEST as $key => $value){
            $key = "request_$key";
            DBConnector::getInstance()->logAppend($logId, $key, $value);
        }
        return $logId;
    }
    
    public function logAppend($logId, $key, $value){
        $query = $this->pdo->prepare("INSERT INTO " . self::$DB_PREFIX . "log_property (log_id, name, value) VALUES (?, ?, ?)");
        if (is_array($value)) $value = print_r($value, true);
        $query->execute(Array($logId, $key, $value)) or httperror(print_r($query->errorInfo(), true));
    }
    
    public function dbBegin(){
        return $this->pdo->beginTransaction();
    }
    
    public function dbCommit(){
        return $this->pdo->commit();
    }
    
    public function dbRollBack(){
        return $this->pdo->rollBack();
    }
    
    public function dbGetWriteCounter(){
        return $this->dbWriteCounter;
    }
    
    /**
     * @param $table    table in db
     * @param $fields   all fields which should be filled
     *
     * @return bool|string
     */
    public function dbInsert($table, $fields){
        $this->dbWriteCounter++;
        
        if (!isset($this->scheme[$table])) die("Unkown table $table");
        
        if (isset($fields["id"])) unset($fields["id"]);
        
        $fields = array_intersect_key($fields, $this->scheme[$table]);
        $p = array_fill(0, count($fields), "?");
        $sql = "INSERT " . self::$DB_PREFIX . "{$table} (" . implode(",", array_map([$this, "quoteIdent"], array_keys($fields))) . ") VALUES (" . implode(",", $p) . ")";
        
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_values($fields)) or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return $ret;
        return $this->pdo->lastInsertId();
    }
    
    /**
     * @param $table    table of db without prefix
     * @param $filter   after WHERE clause
     * @param $fields   fields which will be new set
     *
     * @return bool|int
     */
    public function dbUpdate($table, $filter, $fields){
        $this->dbWriteCounter++;
        
        if (!isset($this->scheme[$table])) die("Unkown table $table");
        $validFilterFields = ["id", "token", "antrag_id", "fieldname", "contenttype", "username"];
        $filter = array_intersect_key($filter, $this->scheme[$table], array_flip($validFilterFields)); # only fetch using id and url
        $fields = array_diff_key(array_intersect_key($fields, $this->scheme[$table]), array_flip($validFilterFields)); # do not update filter fields
        
        if (count($filter) == 0) die("No filter fields given.");
        if (count($fields) == 0) die("No fields given.");
        
        $u = [];
        foreach ($fields as $k => $v){
            $u[] = DBConnector::getInstance()->quoteIdent($k) . " = ?";
        }
        $c = [];
        foreach ($filter as $k => $v){
            $c[] = DBConnector::getInstance()->quoteIdent($k) . " = ?";
        }
        $sql = "UPDATE " . self::$DB_PREFIX . "{$table} SET " . implode(", ", $u) . " WHERE " . implode(" AND ", $c);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_merge(array_values($fields), array_values($filter))) or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        
        return $query->rowCount();
    }
    
    public function dbDelete($table, $filter){
        $this->dbWriteCounter++;
        
        if (!isset($this->scheme[$table])) die("Unkown table $table");
        $validFilterFields = ["id", "token", "antrag_id", "fieldname"];
        $filter = array_intersect_key($filter, $this->scheme[$table], array_flip($validFilterFields)); # only fetch using id and url
        
        if (count($filter) == 0) die("No filter fields given.");
        
        $c = [];
        foreach ($filter as $k => $v){
            $c[] = $this->quoteIdent($k) . " = ?";
        }
        $sql = "DELETE FROM " . self::$DB_PREFIX . "{$table} WHERE " . implode(" AND ", $c);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_values($filter)) or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        
        return $query->rowCount();
    }
    
    private function quoteIdent($field){
        return "`" . str_replace("`", "``", $field) . "`";
    }
    
    /**
     * @param string $tables            table which should be used in FROM statement
     *                                  if $tabels is array [t1,t2, ...]: FROM t1, t2, ...
     * @param array  $showColumns       if [] there will be all coulums (*) shown
     * @param array  $fields            val no array [colname => val,...]: WHERE colname = val AND ...
     *
     *                                  if val is array [colname => [operator,value],...]: WHERE colname operator value
     *                                  AND
     *                                  ...
     *
     *                                  if value is array [colname => [operator,[v1,v2,...]],...]: WHERE colname
     *                                  operator
     *                                  (v1,v2,...) AND ...
     * @param array  $joins             Fields which should be joined:
     *                                  ["type"="inner",table => "tblname","on" =>
     *                                  [["tbl1.col","tbl2.col"],...],"operator"
     *                                  => ["=",...]] Will be: FROM $table INNER JOIN tblname ON (tbl1.col = tbl2.col
     *                                  AND
     *                                  ... )
     *
     *                                  accepted values (<u>default</u>):
     *
     *                                   * type: <u>inner</u>, natural, left, right
     *
     *                                   * operator: <u>=</u>, <, >,<>, <=, >=
     *
     *                                  There can be multiple arrays of the above structure, there will be processed in
     *                                  original order from php
     * @param array  $sort              Order by key (field) with val===true ? asc : desc
     * @param bool   $groupByFirstCol   First coloum will be row idx
     * @param bool   $unique            First column is unique (better output with $groupByFirstCol=true)
     *
     * @return array|bool
     */
    public function dbFetchAll($tables, $showColumns = [], $fields = [], $joins = [], $sort = [], $groupByFirstCol = false, $unique = false){
        //check if all tables are known
        if (!isset($tables)){
            die("table not set");
        }
        if (!is_array($tables)){
            $tables = [$tables];
        }
        foreach ($tables as &$table){
            if (!isset($this->scheme[$table])) die("Unkown table $table");
            $table = self::$DB_PREFIX . $table;
        }
        
        //check if content of fields and sort are valid
        $fields = array_intersect_key($fields, array_flip($this->validFields));
        $sort = array_intersect_key($sort, array_flip($this->validFields));
        //check join
        $validJoinOnOperators = ["=", "<", ">", "<>", "<=", ">="];
        foreach (array_keys($joins) as $nr){
            if (!isset($joins[$nr]["table"])){
                die("no Jointable set in '" . $nr . "' use !");
            }else if (!in_array($joins[$nr]["table"], array_keys($this->scheme))){
                die("Unknown Table " . $joins[$nr]["table"]);
            }else if (isset($joins[$nr]["type"]) && !in_array(strtolower($joins[$nr]["type"]), ["inner", "left", "natural", "right"])){
                die("Unknown Join type " . $joins[$nr]["type"]);
            }
            if (!isset($joins[$nr]["on"])) $joins[$nr]["on"] = [];
            if (!is_array($joins[$nr]["on"])){
                die("on '{$joins[$nr]["on"]}' has to be an array!");
            }
            if (count($joins[$nr]["on"]) === 2 && count($joins[$nr]["on"][0]) === 1){
                $joins[$nr]["on"] = [$joins[$nr]["on"]]; //if only 1 "on" set bring it into an array-form
            }
            foreach ($joins[$nr]["on"] as $pkey => $pair){
                if (!is_array($pair)){
                    die("Join on '$pair' is not an array");
                }
                $newpair = array_intersect($pair, $this->validFields);
                if (count($newpair) !== 2){
                    die("unvalid joinon pair:" . $pair[0] . " and " . $pair[1]);
                }
                $joins[$nr]["on"][$pkey] = $newpair;
            }
            if (isset($joins[$nr]["operator"])){
                if (!is_array($joins[$nr]["operator"])) $joins[$nr]["operator"] = [$joins[$nr]["operator"]];
                foreach ($joins[$nr]["operator"] as $op){
                    if (!in_array($op, $validJoinOnOperators)){
                        die("unallowed join operator '$op' in {$nr}th join");
                    }
                }
            }else{
                $joins[$nr]["operator"] = array_fill(0, count($joins[$nr]["on"]), "=");
            }
            if (count($joins[$nr]["on"]) !== count($joins[$nr]["operator"])){
                die("not same amount of on-pairs(" . count($joins[$nr]["on"]) . ") and operators (" . count($joins[$nr]["operator"]) . ")!");
            }
        }
        
        //
        //prebuild sql
        //
        if (!empty($showColumns)){
            $cols = [];
            foreach ($showColumns as $col){
                if (in_array($col, $this->validFields)){
                    if (strpos($col, ".")){
                        $cols[] = self::$DB_PREFIX . $col;
                    }else{
                        $cols[] = $col;
                    }
                    
                }
                
            }
        }else{
            $cols = ["*"];
        }
        $c = [];
        $vals = [];
        foreach ($fields as $k => $v){
            if (is_array($v)){
                if (is_array($v[1])){
                    switch (strtolower($v[0])){
                        case "in":
                            $tmp = implode(',', array_fill(0, count($v[1]), '?'));
                            $c[] = quoteIdent($k) . " $v[0] (" . $tmp . ")";
                            break;
                        case "between":
                            $c[] = quoteIdent($k) . " $v[0] ? AND ?";
                            if (count($v[1]) !== 2){
                                die("To many values for " . $v[0]);
                            }
                            break;
                        default:
                            die("unknown identifier " . $v[0]);
                    }
                    $vals = array_merge($vals, $v[1]);
                    
                }else{
                    $c[] = $this->quoteIdent($k) . " " . $v[0] . " ?";
                    $vals[] = $v[1];
                }
            }else{
                $c[] = $this->quoteIdent($k) . " = ?";
                $vals[] = $v;
            }
        }
        $j = [];
        //var_dump($joins);
        foreach ($joins as $nr => $join){
            $jtype = isset($join["type"]) ? (strtoupper($join["type"]) . " JOIN") : "NATURAL JOIN";
            if (strcmp($jtype, "NATURAL JOIN") === true){
                $j[] = PHP_EOL . "NATURAL JOIN " . self::$DB_PREFIX . $join["table"];
            }else{
                $jon = [];
                for ($i = 0; $i < count($join["on"]); $i++){
                    if (strpos($join["on"][$i][0], ".") !== 0){
                        $join["on"][$i][0] = self::$DB_PREFIX . $join["on"][$i][0];
                    }
                    if (strpos($join["on"][$i][1], ".") !== 0){
                        $join["on"][$i][1] = self::$DB_PREFIX . $join["on"][$i][1];
                    }
                    $jon[] = $join["on"][$i][0] . " " . $join["operator"][$i] . " " . $join["on"][$i][1];
                }
                $j[] = PHP_EOL . $jtype . " " . self::$DB_PREFIX . $join["table"] . " ON " . implode(" AND ", $jon);
            }
        }
        
        $o = [];
        foreach ($sort as $k => $v){
            $o[] = $this->quoteIdent($k) . " " . ($v ? "ASC" : "DESC");
        }
        
        
        $sql = PHP_EOL . "SELECT " . implode(",", $cols) . PHP_EOL . "FROM " . implode(",", $tables);
        if (count($j) > 0){
            $sql .= " " . implode(" ", $j) . " ";
        }
        if (count($c) > 0){
            $sql .= PHP_EOL . "WHERE " . implode(" AND ", $c);
        }
        if (count($o) > 0){
            $sql .= PHP_EOL . "ORDER BY " . implode(", ", $o);
        }
        //prof_flag($sql);
        prof_flag("sql-start");
        //var_dump($sql);
        //var_dump($vals);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($vals) or httperror(print_r($query->errorInfo(), true));
        prof_flag("sql-done");
        if ($ret === false)
            return false;
        if ($groupByFirstCol && $unique)
            return $query->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        else if ($groupByFirstCol){
            return $query->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
        }else if ($unique){
            return $query->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        }else
            return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * @param $gremiumName
     *
     * @return array|bool
     */
    public function getProjectFromGremium($gremiumNames, $antrag_type){
        prof_flag("gremien-start");
        $sql =
            "SELECT a.gremium,a.type,a.revision,a.state,i2.fieldname,i2.value,i2.antrag_id, a.token
              FROM
                  (SELECT inhalt.value AS gremium, antrag.id,antrag.type, antrag.revision,antrag.token,antrag.state
                      FROM " . self::$DB_PREFIX . "antrag AS antrag,
                         " . self::$DB_PREFIX . "inhalt AS inhalt
                      WHERE antrag.id = inhalt.antrag_id
                      AND inhalt.fieldname = 'projekt.org.name'
                      AND inhalt.value REGEXP ?
                      AND antrag.type = ?
                    ORDER BY createdat ASC
                  ) a,
              " . self::$DB_PREFIX . "inhalt AS i2
              WHERE
                i2.antrag_id = a.id
              ORDER BY a.gremium DESC, a.id DESC";
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute([implode("|", $gremiumNames), $antrag_type]);
        if ($ret === false){
            var_dump($query->errorInfo());
            return false;
        }
        
        $projects = [];
        $projekt_ids = [];
        $tmp_id = null;
        while ($row = $query->fetch(PDO::FETCH_ASSOC)){
            //var_dump($row);
            $gremium = $row["gremium"];
            $tmp_id = $row["antrag_id"];
            $projects[$gremium][$tmp_id]["state"] = $row["state"];
            $projects[$gremium][$tmp_id]["revision"] = $row["revision"];
            $projects[$gremium][$tmp_id]["type"] = $row["type"];
            $projects[$gremium][$tmp_id]["token"] = $row["token"];
            $projects[$gremium][$tmp_id]["_inhalt"][$row["fieldname"]] = $row["value"];
            $projects[$gremium][$tmp_id]["_ref"] = [];
            $projekt_ids[$tmp_id] = $gremium;
        }
        
        if (empty($projekt_ids)){
            return [];
        }
        
        $sql = "
            SELECT i.projekt_id,i2.antrag_id, i2.fieldname, i2.value, a.token,a.state,a.type, a.revision
            FROM
            (SELECT value AS projekt_id,antrag_id AS auslagen_id FROM " . self::$DB_PREFIX . "inhalt
                WHERE fieldname = 'genehmigung'
                    AND contenttype = 'otherForm'
                    AND value IN (" . implode(",", array_keys($projekt_ids)) . ")
            ) AS i,
            " . self::$DB_PREFIX . "inhalt AS i2,
            " . self::$DB_PREFIX . "antrag AS a
            WHERE i.auslagen_id = i2.antrag_id
            AND a.id = i.auslagen_id
            ;";
        
        
        $query = $this->pdo->query($sql) or httperror(print_r($this->pdo->errorInfo(), true));
        if ($query === false){
            return $this->groupArrayKeysByRegExpArray($projects, $gremiumNames);
        }
        
        
        //fetch results in own structure
        while ($row = $query->fetch(PDO::FETCH_ASSOC)){
            $gremium = $projekt_ids[$row["projekt_id"]];
            $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["token"] = $row["token"];
            $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["type"] = $row["type"];
            $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["state"] = $row["state"];
            $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["revision"] = $row["revision"];
            $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["_inhalt"][$row["fieldname"]] = $row["value"];
        }
        
        
        return $this->groupArrayKeysByRegExpArray($projects, $gremiumNames);
    }
    
    
    /**
     * @param $array
     * @param $regexpArray
     *
     * @return array
     */
    private function groupArrayKeysByRegExpArray($array, $regexpArray){
        $retArray = [];
        
        foreach ($array as $gremium => $content){
            foreach ($regexpArray as $regExp){
                if (preg_match("/$regExp/i", $gremium)){
                    $name = str_replace(".*", "", $regExp);
                    if (isset($retArray[$name])){
                        //https://stackoverflow.com/questions/10305912/merge-array-without-loss-key-index
                        $retArray[$name] = $retArray[$name] + $content;
                    }else{
                        $retArray[$name] = $content;
                    }
                    break;
                }
            }
        }
        return $retArray;
    }
    
    public function dbGetLastHibiscus(){
        $sql = "SELECT MAX(CAST(i.value AS DECIMAL)) AS t_max FROM " . self::$DB_PREFIX . "antrag a INNER JOIN " . self::$DB_PREFIX . "inhalt i ON a.id = i.antrag_id AND i.fieldname = 'zahlung.hibiscus' AND a.type = 'zahlung' AND a.revision = 'v1-giro-hibiscus'";
        
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute() or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        if ($query->rowCount() != 1) return false;
        
        return $query->fetchColumn();
    }
    
    /**
     * @param $ktoId
     * @param $kpId
     *
     * @return bool
     */
    public function dbHasAnfangsbestand($ktoId, $kpId){
        $sql = "SELECT COUNT(*) FROM " . self::$DB_PREFIX . "antrag a
 INNER JOIN " . self::$DB_PREFIX . "inhalt i1 ON a.id = i1.antrag_id AND i1.fieldname = 'kontenplan.otherForm' AND a.type = 'zahlung' AND a.revision = 'v1-anfangsbestand' AND i1.value = ?
 INNER JOIN " . self::$DB_PREFIX . "inhalt i2 ON a.id = i2.antrag_id AND i2.fieldname = 'zahlung.konto' AND a.type = 'zahlung' AND a.revision = 'v1-anfangsbestand' AND i2.value = ?
";
        
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute([$kpId, $ktoId]) or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        if ($query->rowCount() != 1) return false;
        
        return $query->fetchColumn() > 0;
    }
    
    /*
     * @param $konto
     * @param $fromDate
     * @param $toDate
     * @return array|bool
    
    function dbFetchBookingHistory($konto, $fromDate, $toDate){
        global $this->pdo, self::$DB_PREFIX;
    
        if (($timeFrom = strtotime($fromDate)) !== false && ($timeTo = strtotime($toDate)) !== false)
        {
            $yearFrom = date("Y", $timeFrom); // see the date manual page for format options
            $yearTo   = date("Y", $timeTo); // see the date manual page for format options
            if($yearFrom !== $yearTo){
                die("not the same Years");
            }
            $year = $yearFrom;
        }
        else
        {
            die('Eingabe der Daten war nicht korrekt!');
        }
    
        $kontenplanID = dbFetchAll("antrag", [],["type"=>"kontenplan", "revision" => "2017", "state" => "final"])[0]['id'];
    
        //$zahlungIDs =
    
        $sql = 'SELECT GROUP_CONCAT(zahlungId) as zahlungId, max(datum) as datum, einnahmen, ausgaben, titel, belegId
    FROM
    (SELECT zahlungId,belegId, max(datum) as datum, sum(einnahmen) as einnahmen, sum(ausgaben) as ausgaben, titel
    FROM
    (SELECT id,antrag_id as zahlungId FROM finanzformular__inhalt WHERE value = ? AND contenttype = "ref" AND fieldname = "zahlung.konto") as z,
    (SELECT id,antrag_id,value as belegId FROM finanzformular__inhalt WHERE contenttype = "otherForm" AND fieldname like "zahlung.grund.beleg%") as b,
    (SELECT id,antrag_id,value as datum FROM finanzformular__inhalt WHERE contenttype = "date" AND fieldname ="zahlung.datum") as d,
    (SELECT id,antrag_id,fieldname,value as einnahmen FROM finanzformular__inhalt WHERE contenttype = "money" AND fieldname like "geld.einnahmen%") as e,
    (SELECT id,antrag_id,fieldname,value as ausgaben FROM finanzformular__inhalt WHERE contenttype = "money" AND fieldname like "geld.ausgaben%") as a,
    (SELECT t1.id,t1.antrag_id,t1.fieldname, CASE t1.value WHEN "" THEN t2.value ELSE t1.value END as titel
     FROM finanzformular__inhalt as t1 LEFT JOIN finanzformular__inhalt as t2 on t2.antrag_id = t1.antrag_id AND t2.fieldname = "genehmigung.titel"  where t1.fieldname like "geld.titel%") as  t
    WHERE zahlungId = b.antrag_id
      AND zahlungId = d.antrag_id
      AND   belegId = e.antrag_id
      AND   belegId = a.antrag_id
      AND   belegId = t.antrag_id
      AND SUBSTRING_INDEX(e.fieldname,"[",-2) = SUBSTRING_INDEX(a.fieldname,"[",-2)
      AND SUBSTRING_INDEX(t.fieldname,"[",-2) = SUBSTRING_INDEX(e.fieldname,"[",-2)
     GROUP by zahlungId,belegId, titel) as q group by belegId, titel, einnahmen, ausgaben ORDER by datum asc';
    
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute([$konto]);
        if ($ret === false)
            return false;
    
        return $query->fetchAll(PDO::FETCH_ASSOC);
    
    }*/
    
    function getUserIBAN(){
        $ret = $this->dbGet("user", ["username" => AuthHandler::getInstance()->getUsername()]);
        if ($ret === false){
            return false;
        }else{
            return $ret["iban"];
        }
    }
    
    public function dbGet($table, $fields){
        if (!isset($this->scheme[$table])) die("Unkown table $table");
        $validFields = ["id", "token", "antrag_id", "fieldname", "value", "contenttype", "username"];
        $fields = array_intersect_key($fields, $this->scheme[$table], array_flip($validFields)); # only fetch using id and url
        
        if (count($fields) == 0) die("No (valid) fields given.");
        
        $c = [];
        $vals = [];
        foreach ($fields as $k => $v){
            if (is_array($v)){
                $c[] = $this->quoteIdent($k) . " " . $v[0] . " ?";
                $vals[] = $v[1];
            }else{
                $c[] = $this->quoteIdent($k) . " = ?";
                $vals[] = $v;
            }
        }
        $sql = "SELECT * FROM " . self::$DB_PREFIX . "{$table} WHERE " . implode(" AND ", $c);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($vals) or httperror(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        if ($query->rowCount() != 1) return false;
        
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    
    function dbgetHHP($id){
        $sql = "
    SELECT t.hhpgruppen_id,t.id,g.type,g.gruppen_name,t.titel_nr,t.titel_name,t.value
    FROM " . self::$DB_PREFIX . "haushaltstitel AS t
    INNER JOIN " . self::$DB_PREFIX . "haushaltsgruppen AS g ON t.hhpgruppen_id = g.id
    WHERE `hhp_id` = ?
    ORDER BY `titel_nr` ASC";
        $query = $this->pdo->prepare($sql);
        $query->execute([$id]) or httperror(print_r($query->errorInfo(), true));
        $groups = [];
        $titelIdsToGroupId = [];
        while ($row = $query->fetch(PDO::FETCH_ASSOC)){
            $gId = array_shift($row); //hhpgruppen_id
            $tId = array_shift($row); //t.id
            $groups[$gId][$tId] = $row;
            $titelIdsToGroupId[$tId] = $gId;
        }
        if (empty($titelIdsToGroupId)){
            return $groups;
        }
        $sql = "
    SELECT b.titel_id, b.value, b.canceled
    FROM " . self::$DB_PREFIX . "booking AS b
    WHERE b.titel_id IN (" . implode(",", array_fill(0, count($titelIdsToGroupId), "?")) . ")
    ";
        $query = $this->pdo->prepare($sql);
        $query->execute(array_keys($titelIdsToGroupId)) or httperror(print_r($query->errorInfo(), true));
        while ($row = $query->fetch(PDO::FETCH_ASSOC)){
            $tId = array_shift($row);
            $val = $row["value"];
            if ($row["canceled"] == 1)
                $val = -$val;
            if (isset($groups[$titelIdsToGroupId[$tId]][$tId]["_booked"])){
                $groups[$titelIdsToGroupId[$tId]][$tId]["_booked"] += $val;
            }else{
                $groups[$titelIdsToGroupId[$tId]][$tId]["_booked"] = $val;
            }
        }
        /* ermittle alle buchungen von projekten die beendet sind + alle offenen Projekte
        $sql = "SELECT a.state, b.titel_id, b.value, p.titel_id,p.ausgaben
        FROM finanzformular__booking as b
          RIGHT JOIN finanzformular__beleg_posten as bp ON bp.beleg_id = b.beleg_id
          RIGHT JOIN finanzformular__projektposten as p ON bp.posten_id = p.id
          INNER JOIN finanzformular__antrag as a ON p.projekt_id = a.id
        WHERE b.titel_id IN (7)
        ";
        $query = $this->pdo->prepare($sql);
        $query->execute(array_keys($titelIdsToGroupId)) or httperror(print_r($query->errorInfo(), true));
        var_dump($query->fetchAll(PDO::FETCH_ASSOC));
        //var_dump($groups);*/
        return $groups;
    }
    
    private function dbQuote($string, $parameter_type = null){
        if ($parameter_type === null)
            return $this->pdo->quote($string);
        else
            return $this->pdo->quote($string, $parameter_type);
    }
}