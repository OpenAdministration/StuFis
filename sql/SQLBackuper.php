<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 19.02.18
 * Time: 03:56
 */


include "../lib/inc.all.php";
require_once "../sql/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php";

echo "I am Alive!";
$b = new SQLBackuper();
echo date("Y-m-d",strtotime("-2 weeks"));
var_dump(scandir("../sql/dump/"));

class SQLBackuper{
    private $dumper;
    private $backupStrat;
    private $knownBackups;
    private $backupDirPath = "../sql/dump/";
    
    public function __construct($blacklisttbl = []){
        global $DB_PREFIX,$DB_DSN,$DB_USERNAME,$DB_PASSWORD,$scheme;

        $incTables = [];
        foreach ($scheme as $tblname => $content){
            $incTables[] = $DB_PREFIX.$tblname;
        }
        $incTables = array_diff($incTables,$blacklisttbl);
        $dumpSettings = array(
            'include-tables' => $incTables,
            'exclude-tables' => array(),
            'compress' => \Ifsnop\Mysqldump\Mysqldump::NONE,
            'init_commands' => array(),
            'no-data' => array(),
            'reset-auto-increment' => false,
            'add-drop-database' => false,
            'add-drop-table' => false,
            'add-drop-trigger' => true,
            'add-locks' => true,
            'complete-insert' => false,
            'databases' => false,
            'default-character-set' => \Ifsnop\Mysqldump\Mysqldump::UTF8,
            'disable-keys' => true,
            'extended-insert' => true,
            'events' => false,
            'hex-blob' => true, /* faster than escaped content */
            'net_buffer_length' => \Ifsnop\Mysqldump\Mysqldump::MAXLINESIZE,
            'no-autocommit' => true,
            'no-create-info' => false,
            'lock-tables' => true,
            'routines' => false,
            'single-transaction' => true,
            'skip-triggers' => false,
            'skip-tz-utc' => false,
            'skip-comments' => false,
            'skip-dump-date' => false,
            'skip-definer' => false,
            'where' => '',
            /* deprecated */
            'disable-foreign-keys-check' => true
        );
        $this->dumper = new Ifsnop\Mysqldump\Mysqldump($DB_DSN,$DB_USERNAME,$DB_PASSWORD,$dumpSettings);
        
    }
    
    public function setBackupStrategy($strategy){
        $this->backupStrat = $strategy;
    }
    public function startBackup($forceNow = false){
        
        if($forceNow === true){
            $this->dumper->start($this->backupDirPath.date("Y-m-d")."dump");
        }
    }
    
}
