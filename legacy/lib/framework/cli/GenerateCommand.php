<?php

namespace framework\cli;

use Ahc\Cli\Application as App;
use framework\helper\EnvSetter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class GenerateCommand extends \Ahc\Cli\Input\Command
{
    public function __construct(?App $app = null)
    {
        parent::__construct('generate', 'Generate unset secrets', false, $app);
        $this->argument('[type]', 'Type of secret');
    }

    public function execute(): void
    {
        $this->generateChatKeys();
    }

    private function generateChatKeys(): void
    {
        if (! isset($_ENV['CHAT_PUBLIC_KEY'], $_ENV['CHAT_PRIVATE_KEY'])) {
            $this->io()->info('Generate Chat Keys ...', true);
            $config = [
                'digest_alg' => 'sha512',
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $res = openssl_pkey_new($config);
            openssl_pkey_export($res, $privKey);
            $pubKey = openssl_pkey_get_details($res);
            $pubKey = $pubKey['key'];

            $env = new EnvSetter(SYSBASE.'/.env', new Logger('env', [new StreamHandler('php://stdout')]));
            $env->setEnvVars([
                'CHAT_PUBLIC_KEY' => $pubKey,
                'CHAT_PRIVATE_KEY' => $privKey,
            ]);
        }
    }
}
