<?php

class DBConnector extends Singleton{
    
    const SUM = 1;
    const SUM_ROUND2 = 2;
    const COUNT = 3;
    const MAX = 4;
    const MIN = 5;
    private static $DB_DSN;
    private static $DB_USERNAME;
    private static $DB_PASSWORD;
    private static $DB_PREFIX;
    private static $BUILD_DB;
    private $pdo;
    private $scheme;
    private $validFields;
    private $dbWriteCounter = 0;
    private $transactionCount = 0;
    
    public function __construct(){
        HTMLPageRenderer::registerProfilingBreakpoint("init-db-connection");
        $this->initScheme();
        try{
            $this->pdo = new PDO(
                self::$DB_DSN,
                self::$DB_USERNAME,
                self::$DB_PASSWORD,
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE', sql_mode = 'STRICT_ALL_TABLES';", PDO::MYSQL_ATTR_FOUND_ROWS => true]
            );
        }catch (PDOException $e){
            ErrorHandler::_errorExit("konnte nicht mit der Datenbank vebinden");
        }
        
        if (self::$BUILD_DB){
            include SYSBASE . "/sql/buildDB.php";
            HTMLPageRenderer::registerProfilingBreakpoint("build-db-finished");
        }
    }
    
    private function initScheme(){
        $scheme = [];
    
        $scheme["comments"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "target_id" => "INT NOT NULL",
            "target" => "VARCHAR(64)",
            "timestamp" => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "creator" => "VARCHAR(128) NOT NULL",
            "creator_alias" => "VARCHAR(256) NOT NULL",
            "text" => "TEXT NOT NULL",
            "type" => "tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 = comment, 1 = state_change, 2 = admin only'",];
        
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
    
        $scheme["user"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "fullname" => "varchar(255) NOT NULL",
            "username" => "varchar(32) NOT NULL",
            "email" => "varchar(128) NOT NULL",
            "iban" => "varchar(32) NOT NULL DEFAULT ''",];
        
        $scheme['haushaltstitel'] = ["id" => " int NOT NULL AUTO_INCREMENT",
            "hhpgruppen_id" => " int NOT NULL",
            "titel_name" => " varchar(128) NOT NULL",
            "titel_nr" => " varchar(10) NOT NULL",
            "value" => "float NOT NULL",];
    
        $scheme['haushaltsgruppen'] = [
            "id" => "int NOT NULL AUTO_INCREMENT",
            "hhp_id" => " int NOT NULL",
            "gruppen_name" => " varchar(128) NOT NULL",
            "type" => "tinyint(1) NOT NULL",];
    
        $scheme["projektposten"] = [
            "id" => " INT NOT NULL",
            "projekt_id" => " INT NOT NULL",
            "titel_id" => " INT NULL DEFAULT NULL",
            "einnahmen" => " FLOAT NOT NULL",
            "ausgaben" => " FLOAT NOT NULL",
            "name" => " VARCHAR(128) NOT NULL",
            "bemerkung" => " VARCHAR(256) NOT NULL",];
    
        $scheme["konto"] = [
            "id" => "INT NOT NULL",
            "konto_id" => "INT NOT NULL",
            "date" => "DATE NOT NULL",
            "valuta" => "DATE NOT NULL",
            "type" => "VARCHAR(128) NOT NULL",
            "empf_iban" => "VARCHAR(40) NOT NULL",
            "empf_bic" => "VARCHAR(11)",
            "empf_name" => "VARCHAR(128) NOT NULL",
            "primanota" => "float NOT NULL",
            "value" => "float NOT NULL",
            "saldo" => "float NOT NULL",
            "zweck" => "varchar(256) NOT NULL",
            "comment" => "varchar(128) NOT NULL",
            "gvcode" => "int NOT NULL",
            "customer_ref" => "varchar(128)"
        ];
        $scheme["projekte"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "creator_id" => "INT NOT NULL",
            "createdat" => "DATETIME NOT NULL",
            "lastupdated" => "DATETIME NOT NULL",
            "version" => "INT NOT NULL DEFAULT 1",
            "state" => "VARCHAR(32) NOT NULL",
            "stateCreator_id" => "INT NOT NULL",
            "name" => "VARCHAR(128) NULL",
            "responsible" => "VARCHAR(128) NULL COMMENT 'EMAIL'",
            "org" => "VARCHAR(64) NULL",
            "org-mail" => "VARCHAR(128) NULL",
            "protokoll" => "VARCHAR(256) NULL",
            "recht" => "VARCHAR(64) NULL",
            "recht-additional" => "VARCHAR(128) NULL",
            "date-start" => "DATE NULL",
            "date-end" => "DATE NULL",
            "beschreibung" => "TEXT NULL"
        ];
        
        // auslagen ---------------------
        $scheme["auslagen"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "projekt_id" => "INT NOT NULL",
            "name_suffix" => "VARCHAR(255) NULL",
            "state" => "VARCHAR(255) NOT NULL",
            "ok-belege" => "VARCHAR(255) NOT NULL DEFAULT ''",
            "ok-hv" => "VARCHAR(255) NOT NULL DEFAULT ''",
            "ok-kv" => "VARCHAR(255) NOT NULL DEFAULT ''",
            "payed" => "VARCHAR(255) NOT NULL DEFAULT ''",
            "rejected" => "VARCHAR(255) NOT NULL DEFAULT ''",
            "zahlung-iban" => "VARCHAR(1023) NOT NULL",
            "zahlung-name" => "VARCHAR(127) NOT NULL",
            "zahlung-vwzk" => "VARCHAR(127) NOT NULL",
        	"address" => "VARCHAR(1023) NOT NULL DEFAULT ''",
            'last_change' => 'DATETIME NOT NULL DEFAULT NOW()',
            'last_change_by' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'etag' => 'VARCHAR(255) NOT NULL',
            "version" => "INT NOT NULL DEFAULT 1",
            "created" => "VARCHAR(255) NOT NULL DEFAULT ''",
        ];
        $scheme["belege"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "auslagen_id" => "INT NOT NULL",
            "short" => "VARCHAR(45) NULL",
            "created_on" => "DATETIME NOT NULL DEFAULT NOW()",
            "datum" => "DATETIME NULL DEFAULT NULL",
            "beschreibung" => "TEXT NOT NULL",
            "file_id" => "INT NULL DEFAULT NULL",
        ];
        $scheme["beleg_posten"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "beleg_id" => "INT NOT NULL",
            "short" => "INT NOT NULL",
            "projekt_posten_id" => "INT NOT NULL",
            "ausgaben" => "DECIMAL(10,2) NOT NULL DEFAULT 0",
            "einnahmen" => "DECIMAL(10,2) NOT NULL DEFAULT 0",
        ];
        
        // dateinen ---------------------
        $scheme["fileinfo"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "link" => "VARCHAR(127) NOT NULL",
            "added_on" => "DATETIME NOT NULL DEFAULT NOW()",
            "hashname" => "VARCHAR(255) NOT NULL",
            "filename" => "VARCHAR(255) NOT NULL",
            "size" => "INT NOT NULL DEFAULT 0",
            "fileextension" => "VARCHAR(45) NOT NULL DEFAULT ''",
            "mime" => "VARCHAR(256) NULL",
            "encoding" => "VARCHAR(45) NULL",
            "data" => "INT NULL DEFAULT NULL",
        ];
        $scheme["filedata"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "data" => "LONGBLOB NULL DEFAULT NULL",
            "diskpath" => "VARCHAR(511) NULL DEFAULT NULL",
        ];
        $scheme["haushaltsplan"] = [
            "id" => "INT NOT NULL AUTO_INCREMENT",
            "von" => "DATE NULL",
            "bis" => "DATE NULL",
            "state" => "VARCHAR(64) NOT NULL",
        ];
        
        $this->scheme = $scheme;
        
        //build valid fields out of schemes
        $validFields = ["*"];
        $blacklist = ["log", "log_property"];
        foreach ($scheme as $tblname => $content){
            $validFields[] = "$tblname.*";
            if (!is_array($content)) continue;
            if (in_array($tblname, $blacklist)) continue;
            $colnames = array_keys($content);
            //all all colnames of this table
            $validFields = array_merge($colnames, $validFields);
            $func = function(&$val, $key) use ($tblname){
                $val = $tblname . "." . $val;
            };
            //add all colnames with tablename.colname
            array_walk($colnames, $func);
            $validFields = array_merge($colnames, $validFields);
        }
        $this->validFields = array_unique($validFields);
    }
    
    final static protected function static__set($name, $value){
        if (property_exists(get_class(), $name))
            self::$$name = $value;
        else{
            ErrorHandler::_errorExit("$name ist keine Variable in " . get_class());
        }
    }
    
    function convertDBValueToUserValue($value, $type){
        switch ($type){
            case "money":
                $value = (string)$value;
                if ($value === false || $value == "") return $value;
                return number_format($value, 2, ',', '&nbsp;');
            case "date":
            case "daterange":
                return htmlspecialchars(date("d.m.Y", strtotime($value)));
                break;
            default:
                return $value;
        }
    }
    
    function convertUserValueToDBValue($value, $type){
        switch ($type){
            case "titelnr":
                $value = trim(str_replace(" ", "", $value));
                $nv = "";
                for ($i = 0; $i < strlen($value); $i++){
                    if ($i % 4 == 1) $nv .= " ";
                    $nv .= $value[$i];
                }
                return $nv;
            case "kostennr":
                $value = trim(str_replace(" ", "", $value));
                $nv = "";
                for ($i = 0; $i < strlen($value); $i++){
                    if ($i % 3 == 2) $nv .= " ";
                    $nv .= $value[$i];
                }
                return $nv;
            case "kontennr":
                $value = trim(str_replace(" ", "", $value));
                $nv = "";
                for ($i = 0; $i < strlen($value); $i++){
                    if ($i % 2 == 0 && $i > 0) $nv .= " ";
                    $nv .= $value[$i];
                }
                return $nv;
            case "money":
                return str_replace(" ", "", str_replace(",", ".", str_replace(".", "", $value)));
            default:
                return $value;
        }
    }
    
    /**
     * @return PDO $pdo
     */
    public function getPdo(){
        return $this->pdo;
    }
    
    /**
     * @return string $DB_PREFIX
     */
    public function getDbPrefix(){
        return self::$DB_PREFIX;
    }
    
    public function logThisAction($data, $actionName = false){
        if ($actionName === false && isset($data["action"]))
            $actionName = $data["action"];
        else
            $actionName = "noGivenName";
        $query = $this->pdo->prepare("INSERT INTO " . self::$DB_PREFIX . "log (action, user_id) VALUES (?, ?)");
        $res = $query->execute([$actionName, DBConnector::getInstance()->getUser()["id"]]);
        if ($res === false){
            ErrorHandler::_errorExit("Log ist nicht möglich!" . print_r($query->errorInfo(), true));
        }
        $logId = $this->pdo->lastInsertId();
        foreach ($data as $key => $value){
            $key = "request_$key";
            $this->logAppend($logId, $key, $value);
        }
        return $logId;
    }
    
    function getUser(){
        $user = $this->dbFetchAll("user", [], ["username" => (AUTH_HANDLER)::getInstance()->getUsername()]);
        if (count($user) === 1){
            $user = $user[0];
        }else{
            if (count($user) === 0){
                $fields = [
                    "fullname" => (AUTH_HANDLER)::getInstance()->getUserFullName(),
                    "username" => (AUTH_HANDLER)::getInstance()->getUsername(),
                    "email" => (AUTH_HANDLER)::getInstance()->getUserMail(),
                ];
                //print_r($fields);
                $id = $this->dbInsert("user", $fields);
                $fields["id"] = $id;
                $user = $fields;
            }else{
                throw new PDOException("User ist mehr als einmal angelegt!");
            }
        }
        //print_r($user);
        return $user;
    }
    
    /**
     * @param string $tables                table which should be used in FROM statement
     *                                      if $tabels is array [t1,t2, ...]: FROM t1, t2, ...
     *
     * @param array  $showColumns           if empty array there will be all coulums (*) shown
     *                                      if keys are not numeric, key will be used as alias
     *                                      don't use same alias twice (ofc)
     *                                      renaming of tables is possible
     *                                      e.g.: newname => tablename.*, numerik keys(newname) will be ignored
     *                                      will be: newname.col1, newname.col2 ...
     *                                      if values of $showColumns are arrays, there can be aggregated functions as
     *                                      second value, fist value is the columnname e.g. alias => ["colname", SUM]
     *
     * @param array  $fields                val no array [colname => val,...]: WHERE colname = val AND ...
     *
     *                                  if val is array [colname => [operator,value],...]: WHERE colname operator value
     *                                  AND
     *                                  ...
     *
     *                                  if value is array [colname => [operator,[v1,v2,...]],...]: WHERE colname
     *                                  operator
     *                                  (v1,v2,...) AND ...
     *
     * @param array  $joins                 Fields which should be joined:
     *                                      ["type"="inner",table => "tblname","on" =>
     *                                      [["tbl1.col","tbl2.col"],...],"operator"
     *                                      => ["=",...]] Will be: FROM $table INNER JOIN tblname ON (tbl1.col =
     *                                      tbl2.col AND
     *                                      ... )
     *
     *                                  accepted values (<u>default</u>):
     *
     *                                   * type: <u>inner</u>, natural, left, right
     *
     *                                   * operator: <u>=</u>, <, >,<>, <=, >=
     *
     *                                  There can be multiple arrays of the above structure, there will be processed in
     *                                  original order from php
     *
     * @param array  $sort                  Order by key (field) with val===true ? asc : desc
     *
     * @param bool   $groupByFirstCol       First coloum will be row idx (if not $unique = true there will be an array
     *                                      as value for each first coloum entry as key)
     *
     * @param bool   $unique                First column is unique (better output with $groupByFirstCol=true) has
     *                                      unexpected results if first coulum isn't unique but $unique = true
     *
     * @param array  $groupBy               Array with columns which will be grouped by
     *
     * @return array|bool
     */
    public function dbFetchAll($tables, $showColumns = [], $fields = [], $joins = [], $sort = [], $groupByFirstCol = false, $unique = false, $groupBy = [], $numericIdx = false){
        //check if all tables are known
        
        if (!is_array($tables)){
            $tables = [$tables];
        }
        
        foreach ($tables as $table){
            if (!isset($this->scheme[$table])){
                ErrorHandler::_errorExit("Unkown table $table");
            }
        }
        
        //fill with everything if empty
        if (empty($showColumns)){
            $showColumns = ["*"];
        }
        
        //substitute * with tablename.*
        if (in_array("*", $showColumns)){
            unset($showColumns[array_search("*", $showColumns)]);
            foreach ($tables as $k => $t){
                $showColumns[] = "$t.*";
            }
            foreach ($joins as $j){
                $showColumns[] = "{$j['table']}.*";
            }
        }
        
        //apply alias for table.* and set everywhere an aggregate function (default: none)
        $newShowColumns = [];
        foreach ($showColumns as $alias => $content){
            if (is_array($content)){
                $col = $content[0];
                $aggregate = $content[1];
            }else{
                $col = $content;
                $aggregate = 0;
            }
            if (!is_int($alias) && ($pos = strpos($col, ".*")) !== false){
                $tname = substr($col, 0, $pos);
                $rename = $alias;
                foreach ($this->scheme[$tname] as $colName => $dev_null){
                    $newShowColumns[$rename . '.' . $colName] = [$tname . '.' . $colName, $aggregate];
                }
            }else{
                $newShowColumns[$alias] = [$col, $aggregate];
            }
        }
        
        foreach ($fields as $field => $value){
            if (!in_array($field, $this->validFields)){
                ErrorHandler::_errorExit("Unkown column $field in WHERE");
            }
        }
        
        //check join
        $validJoinOnOperators = ["=", "<", ">", "<>", "<=", ">="];
        foreach (array_keys($joins) as $nr){
            if (!isset($joins[$nr]["table"])){
                ErrorHandler::_errorExit("no Jointable set in '" . $nr . "' use !");
            }else if (!in_array($joins[$nr]["table"], array_keys($this->scheme))){
                ErrorHandler::_errorExit("Unknown Table " . $joins[$nr]["table"]);
            }else if (isset($joins[$nr]["type"]) && !in_array(strtolower($joins[$nr]["type"]), ["inner", "left", "natural", "right"])){
                ErrorHandler::_errorExit("Unknown Join type " . $joins[$nr]["type"]);
            }
            if (!isset($joins[$nr]["on"])) $joins[$nr]["on"] = [];
            if (!is_array($joins[$nr]["on"])){
                ErrorHandler::_errorExit("on '{$joins[$nr]["on"]}' has to be an array!");
            }
            if (count($joins[$nr]["on"]) === 2 && count($joins[$nr]["on"][0]) === 1){
                $joins[$nr]["on"] = [$joins[$nr]["on"]]; //if only 1 "on" set bring it into an array-form
            }
            foreach ($joins[$nr]["on"] as $pkey => $pair){
                if (!is_array($pair)){
                    ErrorHandler::_errorExit("Join on '$pair' is not an array");
                }
                $newpair = array_intersect($pair, $this->validFields);
                if (count($newpair) !== 2){
                    ErrorHandler::_errorExit("unvalid joinon pair:" . $pair[0] . " and " . $pair[1]);
                }
                $joins[$nr]["on"][$pkey] = $newpair;
            }
            if (isset($joins[$nr]["operator"])){
                if (!is_array($joins[$nr]["operator"])) $joins[$nr]["operator"] = [$joins[$nr]["operator"]];
                foreach ($joins[$nr]["operator"] as $op){
                    if (!in_array($op, $validJoinOnOperators)){
                        ErrorHandler::_errorExit("unallowed join operator '$op' in {$nr}th join");
                    }
                }
            }else{
                $joins[$nr]["operator"] = array_fill(0, count($joins[$nr]["on"]), "=");
            }
            if (count($joins[$nr]["on"]) !== count($joins[$nr]["operator"])){
                ErrorHandler::_errorExit("not same amount of on-pairs(" . count($joins[$nr]["on"]) . ") and operators (" . count($joins[$nr]["operator"]) . ")!");
            }
        }
        
        foreach ($sort as $field => $value){
            if (!in_array($field, $this->validFields)){
                ErrorHandler::_errorExit("Unkown column $field in ORDER");
            }
        }
        
        foreach ($groupBy as $field){
            if (!in_array($field, $this->validFields)){
                ErrorHandler::_errorExit("Unkown column $field in GROUP");
            }
        }
        
        //
        //prebuild sql
        //
        $cols = [];
        foreach ($newShowColumns as $alias => $content){
            $col = $content[0];
            $aggregateConst = $content[1];
            if (in_array($col, $this->validFields)){
                $as = (!is_int($alias)) ? " as `$alias`" : '';
                if (strpos($col, ".")){
                    $cols[] = $this->quoteIdent(self::$DB_PREFIX . $col, $aggregateConst) . $as;
                }else{
                    $cols[] = $this->quoteIdent($col, $aggregateConst) . $as;
                }
            }else{
                ErrorHandler::_errorExit("Unkown column $col in SELECT", 500);
            }
        }
        
        $c = [];
        $vals = [];
        foreach ($fields as $k => $v){
            if (strpos($k, ".") !== false){
                $k = self::$DB_PREFIX . $k;
            }
            if (is_array($v)){
                if (is_array($v[1])){
                    switch (strtolower($v[0])){
                        case "in":
                            $tmp = implode(',', array_fill(0, count($v[1]), '?'));
                            $c[] = $this->quoteIdent($k) . " $v[0] (" . $tmp . ")";
                            break;
                        case "between":
                            $c[] = $this->quoteIdent($k) . " $v[0] ? AND ?";
                            if (count($v[1]) !== 2){
                                ErrorHandler::_errorExit("To many values for " . $v[0]);
                            }
                            break;
                        default:
                            ErrorHandler::_errorExit("unknown identifier " . $v[0]);
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
                    $jon[] = $this->quoteIdent($join["on"][$i][0]) . " " . $join["operator"][$i] . " " . $this->quoteIdent($join["on"][$i][1]);
                }
                $j[] = PHP_EOL . $jtype . " " . self::$DB_PREFIX . $join["table"] . " ON " . implode(" AND ", $jon);
            }
        }
        
        $o = [];
        foreach ($sort as $k => $v){
            if (strpos($k, ".") !== false)
                $o[] = $this->quoteIdent(self::$DB_PREFIX . $k) . " " . ($v ? "ASC" : "DESC");
            else
                $o[] = $this->quoteIdent($k) . " " . ($v ? "ASC" : "DESC");
        }
        
        $g = [];
        foreach ($groupBy as $item){
            if (in_array($item, $this->validFields)){
                if (strpos($item, ".") !== false){
                    $g[] = $this->quoteIdent(self::$DB_PREFIX . $item);
                }else{
                    $g[] = $this->quoteIdent($item);
                }
            }else{
                ErrorHandler::_errorExit(["$item ist für sql nicht bekannt."]);
            }
        }
        
        foreach ($tables as $key => $table){
            $tables[$key] = self::$DB_PREFIX . $table;
        }
        
        $sql = PHP_EOL . "SELECT " . implode("," . PHP_EOL, $cols) . PHP_EOL . "FROM " . implode("," . PHP_EOL, $tables);
        if (count($j) > 0){
            $sql .= " " . implode(" ", $j) . " ";
        }
        if (count($c) > 0){
            $sql .= PHP_EOL . "WHERE " . implode(" AND ", $c);
        }
        if (count($groupBy) > 0){
            $sql .= PHP_EOL . "GROUP BY " . implode(",", $g);
        }
        if (count($o) > 0){
            $sql .= PHP_EOL . "ORDER BY " . implode(", ", $o);
        }
        
        //HTMLPageRenderer::registerProfilingBreakpoint($sql);
        HTMLPageRenderer::registerProfilingBreakpoint("sql-start");
        //var_dump($sql);
        //var_dump($vals);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute($vals);
        if (!$ret){
            $errormsg = ["error" => $query->errorInfo(), "sql" => $sql];
            ErrorHandler::_renderError(print_r($errormsg, true), 500);
        }
        HTMLPageRenderer::registerProfilingBreakpoint("sql-done");
        if ($ret === false)
            return false;
        if ($numericIdx){
            $type = PDO::FETCH_NUM;
        }else{
            $type = PDO::FETCH_ASSOC;
        }
        if ($groupByFirstCol && $unique)
            return $query->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | $type);
        else if ($groupByFirstCol){
            return $query->fetchAll(PDO::FETCH_GROUP | $type);
        }else if ($unique){
            return $query->fetchAll(PDO::FETCH_UNIQUE | $type);
        }else
            return $query->fetchAll($type);
    }
    
    private function quoteIdent($field, $aggregateConst = 0){
        switch ($aggregateConst){
            case $this::SUM:
                $aggregatePre = "SUM(";
                $aggregateSuf = ")";
                break;
            case $this::SUM_ROUND2:
                $aggregatePre = "ROUND(SUM(";
                $aggregateSuf = "),2)";
                break;
            case $this::COUNT:
                $aggregatePre = "COUNT(";
                $aggregateSuf = ")";
                break;
            case $this::MAX:
                $aggregatePre = "MAX(";
                $aggregateSuf = ")";
                break;
            case $this::MIN:
                $aggregatePre = "MIN(";
                $aggregateSuf = ")";
                break;
            default:
                $aggregatePre = "";
                $aggregateSuf = "";
                break;
        }
        $ret = "`" . str_replace("`", "``", $field) . "`";
        return $aggregatePre . str_replace(".", "`.`", $ret) . $aggregateSuf;
    }
    
    /**
     * @param $table    string  table in db
     * @param $fields   array   all fields which should be filled
     *
     * @return bool|string
     */
    public function dbInsert($table, $fields){
        $this->dbWriteCounter++;
        
        if (!isset($this->scheme[$table])){
            ErrorHandler::_errorExit("Unkown table $table");
        }
        //if (isset($fields["id"])) unset($fields["id"]);
        
        $fields = array_intersect_key($fields, $this->scheme[$table]);
        $p = array_fill(0, count($fields), "?");
        $sql = "INSERT " . self::$DB_PREFIX . "{$table} (" . implode(",", array_map([$this, "quoteIdent"], array_keys($fields))) . ") VALUES (" . implode(",", $p) . ")";
        
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_values($fields));
        //print_r($sql);
        //print_r(array_values($fields));
        if ($ret === false){
            ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
        }
        return $this->pdo->lastInsertId();
    }
    
    /**
     * @param array ...$pars
     *
     * @return DBConnector
     */
    public static function getInstance(...$pars){
        return parent::getInstance(...$pars);
    }
    
    public function logAppend($logId, $key, $value){
        $query = $this->pdo->prepare("INSERT INTO " . self::$DB_PREFIX . "log_property (log_id, name, value) VALUES (?, ?, ?)");
        if (is_array($value))
            $value = print_r($value, true);
        $query->execute(Array($logId, $key, $value)) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
    }
    
    public function dbBegin(){
        if (!$this->transactionCount++){
            return $this->pdo->beginTransaction();
        }
        $ret = $this->pdo->query('SAVEPOINT trans' . $this->transactionCount);
        return $ret && $this->transactionCount >= 0;
    }
    
    public function dbCommit(){
        if (!--$this->transactionCount){
            return $this->pdo->commit();
        }
        return $this->transactionCount >= 0;
    }
    
    public function dbRollBack(){
        if (--$this->transactionCount){
            $this->pdo->exec('ROLLBACK TO trans' . ($this->transactionCount + 1));
            return true;
        }
        return $this->pdo->rollback();
    }
    
    public function dbGetWriteCounter(){
        return $this->dbWriteCounter;
    }
    
    /**
     * @param $table  string tablename
     * @param $filter array where clause
     * @param $fields array new values
     *
     * @return bool|int
     */
    public function dbUpdate($table, $filter, $fields){
        $this->dbWriteCounter++;
        if (!isset($this->scheme[$table])){
            ErrorHandler::_errorExit("Unkown table $table");
        }
        
        $filter = array_intersect_key($filter, $this->scheme[$table], array_flip($this->validFields)); # only fetch using id and url
        //$fields = array_diff_key(array_intersect_key($fields, $this->scheme[$table]), array_flip($this->validFields)); # do not update filter fields
        $fields = array_intersect_key($fields, array_flip($this->validFields));
        if (count($filter) == 0){
            ErrorHandler::_errorExit("No filter fields given.");
        }
        if (count($fields) == 0){
            ErrorHandler::_errorExit("No fields given.");
        }
        $u = [];
        foreach ($fields as $k => $v){
            $u[] = $this->quoteIdent($k) . " = ?";
        }
        $c = [];
        foreach ($filter as $k => $v){
            $c[] = $this->quoteIdent($k) . " = ?";
        }
        $sql = "UPDATE " . self::$DB_PREFIX . "{$table} SET " . implode(", ", $u) . " WHERE " . implode(" AND ", $c);
        //print_r($sql);
        $query = $this->pdo->prepare($sql);
        $values = array_merge(array_values($fields), array_values($filter));
        
        $ret = $query->execute($values) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
        if ($ret === false){
            return false;
        }
        
        return $query->rowCount();
    }
    
    public function dbDelete($table, $filter){
        //$this->dbWriteCounter++;
        
        if (!isset($this->scheme[$table]))
            throw new PDOException("Unkown table $table");
        $filter = array_intersect_key($filter, $this->scheme[$table], array_flip($this->validFields)); # only fetch using id and url
        
        if (count($filter) == 0){
            ErrorHandler::_errorExit("No filter fields given.");
        }
        
        $c = [];
        foreach ($filter as $k => $v){
            $c[] = $this->quoteIdent($k) . " = ?";
        }
        $sql = "DELETE FROM " . self::$DB_PREFIX . "{$table} WHERE " . implode(" AND ", $c);
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute(array_values($filter));
        if ($ret === false)
            throw new PDOException(print_r($query->errorInfo(), true));;
        
        return $query->rowCount();
    }
    
    /**
     * @param $gremiumName
     *
     * @return array|bool
     */
    public function getProjectFromGremium($gremiumNames, $antrag_type){
        HTMLPageRenderer::registerProfilingBreakpoint("gremien-start");
        $ret = $this->dbFetchAll("projekte", ["id"], [], [], ["org" => true, "id" => true], true);
        
        
        $projects = [];
        $projekt_ids = [];
        
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
        
        
        $query = $this->pdo->query($sql) or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
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
    
    public function dbGetLastHibiscus(){
        $sql = "SELECT MAX(id) FROM " . self::$DB_PREFIX . "konto";
        $query = $this->pdo->prepare($sql);
        $ret = $query->execute() or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
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
        $ret = $query->execute([$kpId, $ktoId]) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
        if ($ret === false)
            return false;
        if ($query->rowCount() != 1) return false;
        
        return $query->fetchColumn() > 0;
    }
    
    function dbgetHHP($id){
        $sql = "
            SELECT t.hhpgruppen_id,t.id,g.type,g.gruppen_name,t.titel_nr,t.titel_name,t.value,g.type
            FROM " . self::$DB_PREFIX . "haushaltstitel AS t
            INNER JOIN " . self::$DB_PREFIX . "haushaltsgruppen AS g ON t.hhpgruppen_id = g.id
            WHERE `hhp_id` = ?
            ORDER BY `type` ASC,`g`.`id` ASC,`titel_nr` ASC";
        $query = $this->pdo->prepare($sql);
        $query->execute([$id]) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
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
            WHERE b.titel_id IN (" . implode(",", array_fill(0, count($titelIdsToGroupId), "?")) . ")";
        $query = $this->pdo->prepare($sql);
        $query->execute(array_keys($titelIdsToGroupId)) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
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
        $query->execute(array_keys($titelIdsToGroupId)) or ErrorHandler::_errorExit(print_r($query->errorInfo(), true));
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
    
    private function buildColDef($fields){
        $r = "";
        foreach ($fields as $key => $val){
            $r .= $this->quoteIdent($key) . " $val,";
        }
        return $r;
    }
}