<?php
global $pdo;
global $DB_DSN, $DB_USERNAME, $DB_PASSWORD, $DB_PREFIX;
global $scheme;

$pdo = new PDO($DB_DSN, $DB_USERNAME, $DB_PASSWORD, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, lc_time_names = 'de_DE', sql_mode = 'STRICT_ALL_TABLES';", PDO::MYSQL_ATTR_FOUND_ROWS => true]);

$scheme = [];

$scheme["antrag"] = [
  "id" => "INT NOT NULL AUTO_INCREMENT",
  "type" => "VARCHAR(256) NOT NULL",  # Projektantrag, Fahrtkostenantrag, etc.
  "revision" => "VARCHAR(256) NOT NULL", # Version des Formulars (welche Felder erwartet)
  "creator" => "VARCHAR(256) NOT NULL",
  "creatorFullName" => "VARCHAR(256) NOT NULL",
  "createdat" => "DATETIME NOT NULL",
  "lastupdated" => "DATETIME NOT NULL",
  "token" => "VARCHAR(32) NOT NULL",
  "version" => "BIGINT NOT NULL DEFAULT 0",
  "state" => "VARCHAR(32) NOT NULL",
  "stateCreator" => "VARCHAR(32) NOT NULL",
 ];

$scheme["inhalt"] = [
  "id" => "INT NOT NULL AUTO_INCREMENT",
  "antrag_id" => "INT NOT NULL",
  "fieldname" => "VARCHAR(128) NOT NULL",
  "contenttype" => "VARCHAR(128) NOT NULL", # automatisch aus Formulardefinition, zur Darstellung alter Anträge (alte Revision) ohne Metadaten
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
  "timestamp" => "DATETIME NOT NULL",
  "creator" => "VARCHAR(128) NOT NULL",
  "creatorFullName" => "VARCHAR(256) NOT NULL",
  "text" => "varchar(2048) NOT NULL",
  "adminonly" => "BOOLEAN NOT NULL DEFAULT FALSE",
];

#foreach(array_reverse(array_keys($scheme)) as $k)
#  $pdo->query("DROP TABLE {$DB_PREFIX}{$k}") or httperror(print_r($pdo->errorInfo(),true));

function buildColDef($fields) {
  $r = "";
  foreach ($fields as $key => $val) {
    $r .= "$key $val,";
  }
  return $r;
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}antrag");
if ($r === false) {
  $pdo->query( "CREATE TABLE {$DB_PREFIX}antrag (" .
                buildColDef($scheme["antrag"])."
                PRIMARY KEY (id),
                UNIQUE (token)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}inhalt");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}inhalt (".
                buildColDef($scheme["inhalt"])."
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}anhang");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}anhang (" .
                buildColDef($scheme["anhang"])."
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}


$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}comments");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}comments (".
                buildColDef($scheme["comments"])."
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES {$DB_PREFIX}antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));

}

# Log

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log");
if ($r === false) {
  $pdo->query("CREATE TABLE {$DB_PREFIX}log (
                id INT NOT NULL AUTO_INCREMENT,
                action VARCHAR(254) NOT NULL,
                evtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                responsible VARCHAR(254),
                PRIMARY KEY(id)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}log_property");
if ($r === false) {
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
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or httperror(print_r($pdo->errorInfo(),true));
}

function dbQuote($string , $parameter_type = NULL ) {
  global $pdo;
  if ($parameter_type === NULL)
    return $pdo->quote($string);
  else
    return $pdo->quote($string, $parameter_type);
}

function quoteIdent($field) {
    return "`".str_replace("`","``",$field)."`";
}

function logThisAction() {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log (action, responsible) VALUES (?, ?)");
  $query->execute(Array($_REQUEST["action"], getUsername())) or httperror(print_r($query->errorInfo(),true));
  $logId = $pdo->lastInsertId();
  foreach ($_REQUEST as $key => $value) {
    $key = "request_$key";
    logAppend($logId, $key, $value);
  }
  return $logId;
}

function logAppend($logId, $key, $value) {
  global $pdo, $DB_PREFIX;
  $query = $pdo->prepare("INSERT INTO {$DB_PREFIX}log_property (log_id, name, value) VALUES (?, ?, ?)");
  if (is_array($value)) $value = print_r($value, true);
  $query->execute(Array($logId, $key, $value)) or httperror(print_r($query->errorInfo(),true));
}

function dbBegin() {
  global $pdo;
  return $pdo->beginTransaction();
}

function dbCommit() {
  global $pdo;
  return $pdo->commit();
}

function dbRollBack() {
  global $pdo;
  return $pdo->rollBack();
}

function dbInsert($table, $fields) {
   global $pdo, $DB_PREFIX, $scheme;
   if (!isset($scheme[$table])) die("Unkown table $table");

   if (isset($fields["id"])) unset($fields["id"]);

   $fields = array_intersect_key($fields, $scheme[$table]);
   $p = array_fill(0, count($fields), "?");
   $sql = "INSERT {$DB_PREFIX}{$table} (".implode(",", array_map("quoteIdent", array_keys($fields))).") VALUES (".implode(",", $p).")";

   $query = $pdo->prepare($sql);
   $ret = $query->execute(array_values($fields)) or httperror(print_r($query->errorInfo(),true));
   if ($ret === false)
     return $ret;
   return $pdo->lastInsertId();
}

function dbUpdate($table, $filter, $fields) {
   global $pdo, $DB_PREFIX, $scheme;
   if (!isset($scheme[$table])) die("Unkown table $table");
   $validFilterFields = ["id","token","antrag_id"];
   $filter = array_intersect_key($filter, $scheme[$table], array_flip($validFilterFields)); # only fetch using id and url
   $fields = array_diff_key(array_intersect_key($fields, $scheme[$table]), array_flip($validFilterFields)); # do not update filter fields

   if (count($filter) == 0) die("No filter fields given.");
   if (count($fields) == 0) die("No fields given.");

   $u = [];
   foreach($fields as $k => $v) {
     $u[] = quoteIdent($k) . " = ?";
   }
   $c = [];
   foreach($filter as $k => $v) {
     $c[] = quoteIdent($k) . " = ?";
   }
   $sql = "UPDATE {$DB_PREFIX}{$table} SET ".implode(", ", $u)." WHERE ".implode(" AND ", $c);
   $query = $pdo->prepare($sql);
   $ret = $query->execute(array_merge(array_values($fields),array_values($filter))) or httperror(print_r($query->errorInfo(),true));
   if ($ret === false)
     return false;

   return $query->rowCount();
}

function dbDelete($table, $filter) {
   global $pdo, $DB_PREFIX, $scheme;
   if (!isset($scheme[$table])) die("Unkown table $table");
   $validFilterFields = ["id","token","antrag_id","fieldname"];
   $filter = array_intersect_key($filter, $scheme[$table], array_flip($validFilterFields)); # only fetch using id and url

   if (count($filter) == 0) die("No filter fields given.");

   $c = [];
   foreach($filter as $k => $v) {
     $c[] = quoteIdent($k) . " = ?";
   }
   $sql = "DELETE FROM {$DB_PREFIX}{$table} WHERE ".implode(" AND ", $c);
   $query = $pdo->prepare($sql);
   $ret = $query->execute(array_values($filter)) or httperror(print_r($query->errorInfo(),true));
   if ($ret === false)
     return false;

   return $query->rowCount();
}

function dbGet($table, $fields) {
   global $pdo, $DB_PREFIX, $scheme;
   if (!isset($scheme[$table])) die("Unkown table $table");
   $validFields = ["id","token","antrag_id", "fieldname"];
   $fields = array_intersect_key($fields, $scheme[$table], array_flip($validFields)); # only fetch using id and url

   if (count($fields) == 0) die("No fields given.");

   $c = [];
   foreach($fields as $k => $v) {
     $c[] = quoteIdent($k) . " = ?";
   }
   $sql = "SELECT * FROM {$DB_PREFIX}{$table} WHERE ".implode(" AND ", $c);
   $query = $pdo->prepare($sql);
   $ret = $query->execute(array_values($fields)) or httperror(print_r($query->errorInfo(),true));
   if ($ret === false)
     return false;
   if ($query->rowCount() != 1) return false;

   return $query->fetch(PDO::FETCH_ASSOC);
}

function dbFetchAll($table, $fields, $sort = []) {
   global $pdo, $DB_PREFIX, $scheme;
   if (!isset($scheme[$table])) die("Unkown table $table");
   $validFields = ["antrag_id","fieldname","value","contenttype","state","type"];
   $fields = array_intersect_key($fields, $scheme[$table], array_flip($validFields));
   $sort = array_intersect_key($sort, $scheme[$table]);

   $c = [];
   foreach($fields as $k => $v) {
     $c[] = quoteIdent($k) . " = ?";
   }
   $o = [];
   foreach($sort as $k => $v) {
     $o[] = quoteIdent($k) . " ".($v ? "ASC" : "DESC");
   }
   $sql = "SELECT * FROM {$DB_PREFIX}{$table}";
   if (count($c) > 0) {
     $sql .= " WHERE ".implode(" AND ", $c);
   }
   if (count($o) > 0) {
     $sql .= " ORDER BY ".implode(", ", $o);
   }
   $query = $pdo->prepare($sql);
   $ret = $query->execute(array_values($fields)) or httperror(print_r($query->errorInfo(),true));
   if ($ret === false)
     return false;

   return $query->fetchAll(PDO::FETCH_ASSOC);
}

# vim: set expandtab tabstop=8 shiftwidth=8 :

