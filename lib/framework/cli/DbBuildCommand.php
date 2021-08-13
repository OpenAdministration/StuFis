<?php


namespace framework\cli;


use Ahc\Cli\Application as App;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Output\Color;
use framework\DBConnector;

class DbBuildCommand extends \Ahc\Cli\Input\Command
{
    public function __construct(App $app = null)
    {
        parent::__construct('db:build', 'Builds non existent Database tables', false, $app);
    }

    public function execute() : void
    {
        $ret = DBConnector::getInstance()->buildDB();
        $cli = new Color();
        if($ret){
            $cli->ok('[ok] built db');
        }else{
            $cli->error('failed to build db');
        }
    }
}