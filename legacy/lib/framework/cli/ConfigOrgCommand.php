<?php

namespace framework\cli;

use Ahc\Cli\Application as App;
use Ahc\Cli\Output\Writer;
use framework\ArrayHelper;
use framework\NewValidator;

class ConfigOrgCommand extends \Ahc\Cli\Input\Command
{
    public function __construct(?App $app = null)
    {
        parent::__construct('config:org', 'Validates Config Files', false, $app);
    }

    public function execute(): void
    {
        $out = new Writer;
        $orgs = include SYSBASE.'/config/config.orgs.php';

        foreach ($orgs as $realmName => $org) {
            if (strtolower($realmName) !== $realmName) {
                $out->warn("Realm $realmName only small letters", true);
            }
            $v = new NewValidator;
            [$error, $filteredOrg] = $v->validateArray($org, $this->getOrgValidationMap());
            $ignored = ArrayHelper::diff_recursive($org, $filteredOrg);
            if (! empty($ignored)) {
                $ignored = ArrayHelper::convolve_keys($ignored);
                foreach ($ignored as $key => $value) {
                    $out->warn("org:$realmName:$key unchecked (unused)", true);
                }
            }
            if ($error) {
                foreach ($v->getErrors() as [$msg, $validator, $key, $val]) {
                    $out->error("org:$realmName:$key $validator-Validation: $msg", true);
                }
            } else {
                $out->ok("[ok] realm $realmName", true);
            }
        }
    }

    private function getOrgValidationMap(): array
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
                'array', 'empty', 'required',
            ],
            'projekt-form' => [
                'array',
                'required' => false,
                'values' => [
                    'in',
                    ['hide-protokoll'],
                ],
            ],
            'rechtsgrundlagen' => [
                'array',
                'key' => 'text',
                'values' => [
                    'array',
                    'required' => true,
                    'label' => 'text',
                    'label-additional' => 'text',
                    'placeholder' => 'text',
                    'hint-text' => 'text',
                ],
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
