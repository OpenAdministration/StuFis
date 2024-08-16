<?php

namespace framework\cli;

use Ahc\Cli\Application as App;
use Ahc\Cli\Output\Color;
use Dotenv\Dotenv;
use framework\NewMailHandler;

/**
 * Class MailTestCommand
 *
 * @property $recipient
 */
class MailTestCommand extends \Ahc\Cli\Input\Command
{
    public function __construct(?App $app = null)
    {
        parent::__construct('mail:test', 'Test Mail Configuration', false, $app);
        $this->argument('<recipient>', 'A valid mail address to write a test mail to');

        $dotenv = Dotenv::createImmutable(SYSBASE); // load .env
        $dotenv->load();
    }

    public function execute()
    {
        $cli = new Color;
        [$success, $msg] = NewMailHandler::getInstance()
            ->addTo($this->recipient)
            ->setSubject('TEST MAIL')
            ->sendText('Dies ist eine Testmail <script>alert(xss)</script>');
        if ($success) {
            echo $cli->ok('[ok] Mail wurde versendet').PHP_EOL;
        } else {
            echo $cli->error($msg).PHP_EOL;
        }
    }
}
