<?php
global $pdo;
global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX;
global $scheme;
global $dbWriteCounter;

$pdo = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE', sql_mode = 'STRICT_ALL_TABLES';", PDO::MYSQL_ATTR_FOUND_ROWS => true]);

$dbWriteCounter = 0;

$scheme = [];

$scheme["antrag"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "version" => "BIGINT NOT NULL DEFAULT 0",
    "type" => "VARCHAR(256) NOT NULL",  # Projektantrag, Fahrtkostenantrag, etc.
    "revision" => "VARCHAR(256) NOT NULL", # Version des Formulars (welche Felder erwartet)
    "creator" => "VARCHAR(256) NOT NULL",
    "creatorFullName" => "VARCHAR(256) NOT NULL",
    "createdat" => "DATETIME NOT NULL",
    "lastupdated" => "DATETIME NOT NULL",
    "token" => "VARCHAR(32) NOT NULL",
    "state" => "VARCHAR(32) NOT NULL",
    "stateCreator" => "VARCHAR(32) NOT NULL",
];

$scheme["inhalt"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "antrag_id" => "INT NOT NULL",
    "fieldname" => "VARCHAR(128) NOT NULL",
    "contenttype" => "VARCHAR(128) NOT NULL", # automatisch aus Formulardefinition, zur Darstellung alter Anträge (alte Revision) ohne Metadaten
    //"array_idx" => "INT NOT NULL",
    "value" => "TEXT NOT NULL",
];

$scheme["anhang"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "antrag_id" => "INT NOT NULL",
    "fieldname" => "VARCHAR(128) NOT NULL",
    "mimetype" => "VARCHAR(128) NOT NULL",
    "path" => "VARCHAR(128) NOT NULL",
    "size" => "INT NOT NULL",
    "md5sum" => "VARCHAR(128) NOT NULL",
    "state" => "ENUM('active','revoked') DEFAULT 'active' NOT NULL",
    "filename" => "VARCHAR(256) NOT NULL",
];

$scheme["comments"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "antrag_id" => "INT NOT NULL",
    "timestamp" => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "creator" => "VARCHAR(128) NOT NULL",
    "creatorFullName" => "VARCHAR(256) NOT NULL",
    "text" => "varchar(2048) NOT NULL",
    "type" => "tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 = state change, 1 = comment, 2 = admin only'",
];

$scheme["booking"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "zahlung_id" => "INT NOT NULL",
    "beleg_id" => "INT NOT NULL",
    "timestamp" => "DATETIME NOT NULL",
    "creatorFullName" => "VARCHAR(256) NOT NULL",
    "comment" => "varchar(2048) NOT NULL",
    "value" => "INT NOT NULL",
    "titel" => "varchar(256) NOT NULL",
    "kostenstelle" => "varchar(256) NOT NULL",
    "canceled" => "INT NOT NULL DEFAULT 0",
];
/*
$scheme["money"] = [
    "antrag_id" => "INT NOT NULL",
    "antrag_version" => "BIGINT NOT NULL",
    "idx" => "INT NOT NULL",
    "ausgaben" => "INT NOT NULL DEFAULT 0",
    "einnahmen" => "INT NOT NULL DEFAULT 0",
    "titel" => "varchar(256) NOT NULL",
    "kostenstelle" => "varchar(256) NOT NULL",
];
*/
$scheme["user"] = [
    "id" => "INT NOT NULL AUTO_INCREMENT",
    "fullname" => "varchar(255) NOT NULL",
    "username" => "varchar(32) NOT NULL",
    "iban" => "varchar(32) NOT NULL",
];

#foreach(array_reverse(array_keys($scheme)) as $k)
#  $pdo->query("DROP TABLE {$DB_PREFIX}{$k}") or httperror(print_r($pdo->errorInfo(),true));

function buildColDef($fields){
    $r = "";
    foreach ($fields as $key => $val){
        $r .= "$key $val,";
    }
    return $r;
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}antrag");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}antrag (" .
        buildColDef($scheme["antrag"]) . "
                PRIMARY KEY (id,version),
                UNIQUE (token)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}inhalt");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}inhalt (" .
        buildColDef($scheme["inhalt"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}anhang");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}anhang (" .
        buildColDef($scheme["anhang"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}comments");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}comments (" .
        buildColDef($scheme["comments"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
    
}
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}user");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}user (" .
        buildColDef($scheme["user"]) . "
                PRIMARY KEY (id),
                UNIQUE (username)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

# Log
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}log (
                id INT NOT NULL AUTO_INCREMENT,
                action VARCHAR(254) NOT NULL,
                evtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                responsible VARCHAR(254),
                PRIMARY KEY(id)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log_property");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}log_property (
                id INT NOT NULL AUTO_INCREMENT,
                log_id INT NOT NULL,
                name VARCHAR(128) NOT NULL,
                value LONGTEXT,
                INDEX(log_id),
                INDEX(name),
                INDEX(name, value(256)),
                PRIMARY KEY(id),
                FOREIGN KEY (log_id) REFERENCES {$DB_PREFIX}log(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}booking");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}booking (" .
        buildColDef($scheme["booking"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (beleg_id) REFERENCES {$DB_PREFIX}antrag(id),
                FOREIGN KEY (zahlung_id) REFERENCES {$DB_PREFIX}antrag(id)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(), true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}money");
if ($r === false){
    /*$pdo->query("CREATE TABLE {$DB_PREFIX}money (".
                buildColDef($scheme["money"])."
                PRIMARY KEY (antrag_id,antrag_version,idx),
                FOREIGN KEY (antrag_id,antrag_version) REFERENCES {$DB_PREFIX}antrag(id,version)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));*/
}
/*
 CREATE TABLE `finanzen-spielwiese`.`finanzformular__haushaltstitel`
( `id` INT NOT NULL AUTO_INCREMENT ,
  `hhp_id` VARCHAR(128) NOT NULL ,
  `titel_name` VARCHAR(128) NOT NULL ,
  `titel_nr` INT NOT NULL ,
  `einnahmen` INT NULL DEFAULT NULL,
  `ausgaben` INT  NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`hhp_id`)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci
*/
function dbQuote($string, $parameter_type = null){
    global $pdo;
    if ($parameter_type === null)
        return $pdo->quote($string);
    else
        return $pdo->quote($string, $parameter_type);
}

function quoteIdent($field){
    return "`" . str_replace("`", "``", $field) . "`";
}

function logThisAction(){
    global $pdo, $DB_PREFIX;
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log (action, responsible) VALUES (?, ?)");
    $query->execute(Array($_REQUEST["action"], getUsername())) or httperror(print_r($query->errorInfo(), true));
    $logId = $pdo->lastInsertId();
    foreach ($_REQUEST as $key => $value){
        $key = "request_$key";
        logAppend($logId, $key, $value);
    }
    return $logId;
}

function logAppend($logId, $key, $value){
    global $pdo, $DB_PREFIX;
    $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log_property (log_id, name, value) VALUES (?, ?, ?)");
    if (is_array($value)) $value = print_r($value, true);
    $query->execute(Array($logId, $key, $value)) or httperror(print_r($query->errorInfo(), true));
}

function dbBegin(){
    global $pdo;
    return $pdo->beginTransaction();
}

function dbCommit(){
    global $pdo;
    return $pdo->commit();
}

function dbRollBack(){
    global $pdo;
    return $pdo->rollBack();
}

function dbGetWriteCounter(){
    global $dbWriteCounter;
    return $dbWriteCounter;
}

function dbInsert($table, $fields){
    global $pdo, $DB_PREFIX, $scheme, $dbWriteCounter;
    $dbWriteCounter++;
    
    if (!isset($scheme[$table])) die("Unkown table $table");
    
    if (isset($fields["id"])) unset($fields["id"]);
    
    $fields = array_intersect_key($fields, $scheme[$table]);
    $p = array_fill(0, count($fields), "?");
    $sql = "INSERT {$DB_PREFIX}{$table} (" . implode(",", array_map("quoteIdent", array_keys($fields))) . ") VALUES (" . implode(",", $p) . ")";
    
    $query = $pdo->prepare($sql);
    $ret = $query->execute(array_values($fields)) or httperror(print_r($query->errorInfo(), true));
    if ($ret === false)
        return $ret;
    return $pdo->lastInsertId();
}

function dbUpdate($table, $filter, $fields){
    global $pdo, $DB_PREFIX, $scheme, $dbWriteCounter;
    $dbWriteCounter++;
    
    if (!isset($scheme[$table])) die("Unkown table $table");
    $validFilterFields = ["id", "token", "antrag_id", "fieldname", "contenttype", "username"];
    $filter = array_intersect_key($filter, $scheme[$table], array_flip($validFilterFields)); # only fetch using id and url
    $fields = array_diff_key(array_intersect_key($fields, $scheme[$table]), array_flip($validFilterFields)); # do not update filter fields
    
    if (count($filter) == 0) die("No filter fields given.");
    if (count($fields) == 0) die("No fields given.");
    
    $u = [];
    foreach ($fields as $k => $v){
        $u[] = quoteIdent($k) . " = ?";
    }
    $c = [];
    foreach ($filter as $k => $v){
        $c[] = quoteIdent($k) . " = ?";
    }
    $sql = "UPDATE {$DB_PREFIX}{$table} SET " . implode(", ", $u) . " WHERE " . implode(" AND ", $c);
    $query = $pdo->prepare($sql);
    $ret = $query->execute(array_merge(array_values($fields), array_values($filter))) or httperror(print_r($query->errorInfo(), true));
    if ($ret === false)
        return false;
    
    return $query->rowCount();
}

function dbDelete($table, $filter){
    global $pdo, $DB_PREFIX, $scheme, $dbWriteCounter;
    $dbWriteCounter++;
    
    if (!isset($scheme[$table])) die("Unkown table $table");
    $validFilterFields = ["id", "token", "antrag_id", "fieldname"];
    $filter = array_intersect_key($filter, $scheme[$table], array_flip($validFilterFields)); # only fetch using id and url
    
    if (count($filter) == 0) die("No filter fields given.");
    
    $c = [];
    foreach ($filter as $k => $v){
        $c[] = quoteIdent($k) . " = ?";
    }
    $sql = "DELETE FROM {$DB_PREFIX}{$table} WHERE " . implode(" AND ", $c);
    $query = $pdo->prepare($sql);
    $ret = $query->execute(array_values($filter)) or httperror(print_r($query->errorInfo(), true));
    if ($ret === false)
        return false;
    
    return $query->rowCount();
}

function dbGet($table, $fields){
    global $pdo, $DB_PREFIX, $scheme;
    if (!isset($scheme[$table])) die("Unkown table $table");
    $validFields = ["id", "token", "antrag_id", "fieldname", "value", "contenttype", "username"];
    $fields = array_intersect_key($fields, $scheme[$table], array_flip($validFields)); # only fetch using id and url
    
    if (count($fields) == 0) die("No (valid) fields given.");
    
    $c = [];
    $vals = [];
    foreach ($fields as $k => $v){
        if (is_array($v)){
            $c[] = quoteIdent($k) . " " . $v[0] . " ?";
            $vals[] = $v[1];
        }else{
            $c[] = quoteIdent($k) . " = ?";
            $vals[] = $v;
        }
    }
    $sql = "SELECT * FROM {$DB_PREFIX}{$table} WHERE " . implode(" AND ", $c);
    $query = $pdo->prepare($sql);
    $ret = $query->execute($vals) or httperror(print_r($query->errorInfo(), true));
    if ($ret === false)
        return false;
    if ($query->rowCount() != 1) return false;
    
    return $query->fetch(PDO::FETCH_ASSOC);
}

/**
 * @param string $table
 * @param array  $fields
 * @param array  $sort
 * @param array  $showColumns
 * @param bool   $groupByFirstCol
 * @param bool   $unique
 *
 * @return array|bool
 */
function dbFetchAll($table, $fields, $sort = [], $showColumns = [], $groupByFirstCol = false, $unique = false){
    global $pdo, $DB_PREFIX, $scheme;
    
    if (!isset($scheme[$table])) die("Unkown table $table");
    $validFields = ["id", "antrag_id", "fieldname", "value", "contenttype", "state", "type", "value", "revision"];
    $fields = array_intersect_key($fields, $scheme[$table], array_flip($validFields));
    $sort = array_intersect_key($sort, $scheme[$table]);
    $c = [];
    $vals = [];
    foreach ($fields as $k => $v){
        if (is_array($v)){
            if (strcasecmp($v[0], "in") == 0 && is_array($v[1])){
                $tmp = implode(',', array_fill(0, count($v[1]), '?'));
                $c[] = quoteIdent($k) . " in (" . $tmp . ")";
                $vals = array_merge($vals, $v[1]);
            }else{
                $c[] = quoteIdent($k) . " " . $v[0] . " ?";
                $vals[] = $v[1];
            }
        }else{
            $c[] = quoteIdent($k) . " = ?";
            $vals[] = $v;
        }
    }
    $o = [];
    foreach ($sort as $k => $v){
        $o[] = quoteIdent($k) . " " . ($v ? "ASC" : "DESC");
    }
    
    if (!empty($showColumns)){
        $cols = [];
        foreach ($showColumns as $col){
            if (in_array($col, $validFields))
                $cols[] = $col;
        }
        $cols = implode(",", $cols);
    }else{
        $cols = "*";
    }
    $sql = "SELECT " . $cols . " FROM {$DB_PREFIX}{$table}";
    if (count($c) > 0){
        $sql .= " WHERE " . implode(" AND ", $c);
    }
    if (count($o) > 0){
        $sql .= " ORDER BY " . implode(", ", $o);
    }
    //var_dump($sql);
    //var_dump($vals);
    $query = $pdo->prepare($sql);
    $ret = $query->execute($vals) or httperror(print_r($query->errorInfo(), true));
    
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
function getProjectFromGremium($gremiumNames, $antrag_type){
    global $pdo, $DB_PREFIX;
    prof_flag("gremien-start");
    $sql =
        "SELECT a.gremium,a.type,a.revision,a.state,i2.fieldname,i2.value,i2.antrag_id, a.token
        FROM
          (SELECT inhalt.value as gremium, antrag.id,antrag.type, antrag.revision,antrag.token,antrag.state
            FROM {$DB_PREFIX}antrag as antrag, 
                 {$DB_PREFIX}inhalt as inhalt 
            WHERE antrag.id = inhalt.antrag_id 
              AND inhalt.fieldname = 'projekt.org.name'
              AND inhalt.value REGEXP ?
              AND antrag.type = ?
            ORDER by createdat asc
          ) a,
          {$DB_PREFIX}inhalt as i2
          WHERE 
            i2.antrag_id = a.id
          ORDER BY a.gremium desc, a.id desc
        ";
    //var_dump($sql);
    $query = $pdo->prepare($sql);
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
    
    $sql = "
    SELECT i.projekt_id,i2.antrag_id, i2.fieldname, i2.value, a.token,a.state,a.type, a.revision
    FROM 
    (SELECT value as projekt_id,antrag_id as auslagen_id FROM {$DB_PREFIX}inhalt 
    WHERE fieldname = 'genehmigung' 
    AND contenttype = 'otherForm'
    AND value IN (" . implode(",", array_keys($projekt_ids)) . ")
    ) as i,
    {$DB_PREFIX}inhalt as i2,
    {$DB_PREFIX}antrag as a
    WHERE i.auslagen_id = i2.antrag_id
    AND a.id = i.auslagen_id;";
    
    $query = $pdo->query($sql);
    if ($query === false)
        return groupArrayByRegExpArray($projects, $gremiumNames);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)){
        $gremium = $projekt_ids[$row["projekt_id"]];
        $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["token"] = $row["token"];
        $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["type"] = $row["type"];
        $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["state"] = $row["state"];
        $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["revision"] = $row["revision"];
        $projects[$gremium][$row["projekt_id"]]["_ref"][$row["antrag_id"]]["_inhalt"][$row["fieldname"]] = $row["value"];
    }
    
    return groupArrayByRegExpArray($projects, $gremiumNames);
}

/**
 * @param $array
 * @param $regexpArray
 */
function groupArrayByRegExpArray($array, $regexpArray){
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
    global $pdo, $DB_PREFIX;

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

    $kontenplanID = dbFetchAll("antrag", ["type"=>"kontenplan", "revision" => "2017", "state" => "final"])[0]['id'];

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

    $query = $pdo->prepare($sql);
    $ret = $query->execute([$konto]);
    if ($ret === false)
        return false;

    return $query->fetchAll(PDO::FETCH_ASSOC);

}*/

function dbGetLastHibiscus(){
    global $pdo, $DB_PREFIX;
    
    $sql = "SELECT MAX(CAST(i.value AS DECIMAL)) AS t_max FROM {$DB_PREFIX}antrag a INNER JOIN {$DB_PREFIX}inhalt i ON a.id = i.antrag_id AND i.fieldname = 'zahlung.hibiscus' AND a.type = 'zahlung' AND a.revision = 'v1-giro-hibiscus'";
    
    $query = $pdo->prepare($sql);
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
function dbHasAnfangsbestand($ktoId, $kpId){
    global $pdo, $DB_PREFIX;
    
    $sql = "SELECT COUNT(*) FROM {$DB_PREFIX}antrag a
 INNER JOIN {$DB_PREFIX}inhalt i1 ON a.id = i1.antrag_id AND i1.fieldname = 'kontenplan.otherForm' AND a.type = 'zahlung' AND a.revision = 'v1-anfangsbestand' AND i1.value = ?
 INNER JOIN {$DB_PREFIX}inhalt i2 ON a.id = i2.antrag_id AND i2.fieldname = 'zahlung.konto' AND a.type = 'zahlung' AND a.revision = 'v1-anfangsbestand' AND i2.value = ?
";
    
    $query = $pdo->prepare($sql);
    $ret = $query->execute([$kpId, $ktoId]) or httperror(print_r($query->errorInfo(), true));
    if ($ret === false)
        return false;
    if ($query->rowCount() != 1) return false;
    
    return $query->fetchColumn() > 0;
}

function getUserIBAN(){
    $ret = dbGet("user", ["username" => getUsername()]);
    if ($ret === false){
        return false;
    }else{
        return $ret["iban"];
    }
}
