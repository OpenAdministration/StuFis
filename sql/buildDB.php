<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 19.02.18
 * Time: 02:20
 *
 * Execute this file to build the database, scheme definition in: /lib/inc.db.php
 */

include "../lib/inc.all.php";

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
                FOREIGN KEY (beleg_id) REFERENCES {$DB_PREFIX}antrag(id),
                FOREIGN KEY (zahlung_id) REFERENCES {$DB_PREFIX}antrag(id),
                FOREIGN KEY (titel_id) REFERENCES {$DB_PREFIX}haushaltstitel(id),
                FOREIGN KEY (user_id) REFERENCES {$DB_PREFIX}user(id)
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

$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}haushaltsgruppen");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}haushaltsgruppen (" .
        buildColDef($scheme["haushaltsgruppen"]) . "
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or httperror(print_r($pdo->errorInfo(), true));
}
$r = $pdo->query("SELECT COUNT(*) FROM {$DB_PREFIX}haushaltstitel");
if ($r === false){
    $pdo->query("CREATE TABLE {$DB_PREFIX}haushaltstitel (" .
        buildColDef($scheme["haushaltstitel"]) . "
                PRIMARY KEY (`id`),
                UNIQUE (`hhpgruppen_id`,`titel_nr`),
                FOREIGN KEY (`hhpgruppen_id`) REFERENCES {$DB_PREFIX}haushaltsgruppen(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or httperror(print_r($pdo->errorInfo(), true));
}

$pdo->query("
CREATE TABLE IF NOT EXISTS {$DB_PREFIX}projektposten (" .
    buildColDef($scheme["projektposten"]) . "
    PRIMARY KEY (`id`),
    FOREIGN KEY (`title_id`) REFERENCES {$DB_PREFIX}haushaltsplanposten` (`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8;");

$pdo->query("
CREATE TABLE IF NOT EXISTS {$DB_PREFIX}beleg_posten (" .
    buildColDef($scheme["beleg_posten"]) . "
  PRIMARY KEY (`posten_id`, `beleg_nr`, `antrag_id`),
    FOREIGN KEY (posten_id) REFERENCES {$DB_PREFIX}posten (id)
    )ENGINE = InnoDB DEFAULT CHARSET=utf8;

");
