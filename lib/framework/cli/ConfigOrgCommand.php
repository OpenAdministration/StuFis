<?php


namespace framework\cli;


use Ahc\Cli\Application as App;
use Ahc\Cli\Output\Color;
use framework\ArrayHelper;
use framework\NewValidator;
use framework\Validator;
use JetBrains\PhpStorm\ArrayShape;

class ConfigOrgCommand extends \Ahc\Cli\Input\Command
{
    public function __construct(App $app = null)
    {
        parent::__construct('config:org', 'Validates Config Files', false, $app);
    }

    public function execute(): void
    {
        $color = new Color();
        $orgs = include SYSBASE . '/config/config.orgs.php';

        foreach ($orgs as $realmName => $org){
            if(strtolower($realmName) !== $realmName){
                echo $color->warn("Realm $realmName only small letters") . PHP_EOL;
            }
            $v = new NewValidator();
            [$error, $filteredOrg] = $v->validateArray($org, $this->getOrgValidationMap());
            $ignored = ArrayHelper::diff_recursive($org, $filteredOrg);
            if(!empty($ignored)){
                $ignored = ArrayHelper::convolve_keys($ignored);
                foreach ($ignored as $key => $value){
                    echo $color->warn("org:$realmName:$key unchecked (unused)") . PHP_EOL;
                }
            }
            if($error){
                foreach ($v->getErrors() as [$msg, $validator, $key, $val]){
                    echo $color->error("org:$realmName:$key $validator-Validation: $msg") . PHP_EOL;
                }
            }else{
                echo $color->ok("[ok] realm $realmName") . PHP_EOL;
            }
        }
    }

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    private function getOrgValidationMap() : array
    {
        return [
            'gremien' => [
                'array',
                'required' => true,
                'key' => 'text',
                'values' => [
                    'array',
                    'optional',
                    'empty',
                    'values' => 'text',
                ],
            ],
            'mailinglists' => [
                'array', 'empty', 'required'
            ],
            'impressum-url' => ['url', 'empty'],
            'datenschutz-url' => ['url', 'empty'],
            'issues-url' => ['url', 'empty'],
            'help-url' => ['url', 'empty'],
            'mail-domain' => 'domain',
            'finanzen-mail' => 'mail',
        ];
    }
}