<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 19.02.18
 * Time: 02:20
 *
 * Execute this file to build the database, scheme definition in: /lib/DBConnector.php
 */

require_once dirname(__FILE__,2)."/lib/inc.all.php";

#foreach(array_reverse(array_keys($this->scheme)) as $k)
#  $this->pdo->query("DROP TABLE ".self::$DB_PREFIX."{$k}") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(),true));


$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."antrag");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."antrag (" .
        $this->buildColDef($this->scheme["antrag"]) . "
                PRIMARY KEY (id,version),
                UNIQUE (token)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."inhalt");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."inhalt (" .
        $this->buildColDef($this->scheme["inhalt"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES ".self::$DB_PREFIX."antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."comments");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."comments (" .
        $this->buildColDef($this->scheme["comments"]) . "
                PRIMARY KEY (id),
                FOREIGN KEY (antrag_id) REFERENCES ".self::$DB_PREFIX."antrag(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
    
}
$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."user");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."user (" .
        $this->buildColDef($this->scheme["user"]) . "
                PRIMARY KEY (id),
                UNIQUE (username)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

# Log
$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."log");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."log (
                id INT NOT NULL AUTO_INCREMENT,
                action VARCHAR(254) NOT NULL,
                evtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_id INT NULL,
                PRIMARY KEY(id)
               ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."log_property");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."log_property (
                id INT NOT NULL AUTO_INCREMENT,
                log_id INT NOT NULL,
                name VARCHAR(128) NOT NULL,
                value LONGTEXT,
                INDEX(log_id),
                INDEX(name),
                INDEX(name, value(256)),
                PRIMARY KEY(id),
                FOREIGN KEY (log_id) REFERENCES ".self::$DB_PREFIX."log(id) ON DELETE CASCADE
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."money");
if ($r === false){
    /*$this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."money (".
                $this->buildColDef($this->scheme["money"])."
                PRIMARY KEY (antrag_id,antrag_version,idx),
                FOREIGN KEY (antrag_id,antrag_version) REFERENCES ".self::$DB_PREFIX."antrag(id,version)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(),true));*/
}

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."haushaltsgruppen");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."haushaltsgruppen (" .
        $this->buildColDef($this->scheme["haushaltsgruppen"]) . "
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}
$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."haushaltstitel");
if ($r === false){
    $this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."haushaltstitel (" .
        $this->buildColDef($this->scheme["haushaltstitel"]) . "
                PRIMARY KEY (`id`),
                UNIQUE (`hhpgruppen_id`,`titel_nr`),
                FOREIGN KEY (`hhpgruppen_id`) REFERENCES ".self::$DB_PREFIX."haushaltsgruppen(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."konto (" .
    $this->buildColDef($this->scheme["konto"]) .
      " PRIMARY KEY (id)
      )ENGINE = InnoDB DEFAULT CHARSET=utf8;
") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$r = $this->pdo->query("SELECT COUNT(*) FROM ".self::$DB_PREFIX."booking");
if ($r === false){
	$this->pdo->query("CREATE TABLE ".self::$DB_PREFIX."booking (" .
		$this->buildColDef($this->scheme["booking"]) . "
                FOREIGN KEY (beleg_id) REFERENCES ".self::$DB_PREFIX."antrag(id),
                FOREIGN KEY (zahlung_id) REFERENCES ".self::$DB_PREFIX."konto(id),
                FOREIGN KEY (titel_id) REFERENCES ".self::$DB_PREFIX."haushaltstitel(id),
                FOREIGN KEY (user_id) REFERENCES ".self::$DB_PREFIX."user(id)
              ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
}

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."projekte (" .
    $this->buildColDef($this->scheme["projekte"]) .
    " PRIMARY KEY (id),
    FOREIGN KEY (creator_id) REFERENCES ".self::$DB_PREFIX."user(id),
    FOREIGN KEY (stateCreator_id) REFERENCES ".self::$DB_PREFIX."user(id)
      )ENGINE = InnoDB DEFAULT CHARSET=utf8;
") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."projektposten (" .
    $this->buildColDef($this->scheme["projektposten"]) . "
    PRIMARY KEY (`id`,`projekt_id`),
    FOREIGN KEY (`titel_id`) REFERENCES ".self::$DB_PREFIX."haushaltstitel (`id`),
    FOREIGN KEY (`projekt_id`) REFERENCES ".self::$DB_PREFIX."projekte(`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."filedata (" .
	$this->buildColDef($this->scheme["filedata"]) . "
    PRIMARY KEY (`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."fileinfo (" .
	$this->buildColDef($this->scheme["fileinfo"]) . "
    PRIMARY KEY (`id`),
	UNIQUE INDEX `cachename_UNIQUE` (`hashname` ASC),
	INDEX `fk_silmph__attachments_2_idx` (`data` ASC),
	UNIQUE INDEX `id_UNIQUE` (`id` ASC),
	INDEX `index5` (`hashname` ASC),
	INDEX `index6` (`filename` ASC),
	FOREIGN KEY (`data`) REFERENCES ".self::$DB_PREFIX."filedata(`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."auslagen (" .
	$this->buildColDef($this->scheme["auslagen"]) . "
    PRIMARY KEY (`id`),
	UNIQUE INDEX `id_UNIQUE` (`id` ASC),
	INDEX `fk_auslagen_1_idx` (`projekt_id` ASC),
	FOREIGN KEY (`projekt_id`) REFERENCES ".self::$DB_PREFIX."projekte(`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."belege (" .
	$this->buildColDef($this->scheme["belege"]) . "
    PRIMARY KEY (`id`),
	UNIQUE INDEX `id_UNIQUE` (`id` ASC),
	INDEX `fk_belege_1_idx` (`auslagen_id` ASC),
	FOREIGN KEY (`auslagen_id`) REFERENCES ".self::$DB_PREFIX."auslagen(`id`)
)ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));

$this->pdo->query("
CREATE TABLE IF NOT EXISTS ".self::$DB_PREFIX."beleg_posten (" .
    $this->buildColDef($this->scheme["beleg_posten"]) . "
	PRIMARY KEY (`short`, `beleg_id`),
	INDEX `fk_beleg_posten_2_idx` (`beleg_id` ASC),
	UNIQUE INDEX `id_UNIQUE` (`id` ASC),
	FOREIGN KEY (`beleg_id`) REFERENCES ".self::$DB_PREFIX."belege(`id`)
    )ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
") or ErrorHandler::_errorExit(print_r($this->pdo->errorInfo(), true));
