<?php

namespace App\Console\Commands;

use App\Models\LegalBasis;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class MigrateConfigToDatabase extends Command
{
    protected $signature = 'settings:import-from-legacy-config';

    protected $description = 'Migrate legacy PHP array config into the settings and legal_bases tables';

    public function handle(): int
    {
        $config = $this->getLegacyConfig();

        if (empty($config)) {
            $this->error('No legacy config found.');

            return self::FAILURE;
        }

        $this->migrateSettings($config);
        $this->migrateLegalBases($config);
        $this->migrateGremien($config);

        $this->newLine();
        $this->info('Migration complete.');

        return self::SUCCESS;
    }

    private function migrateSettings(array $config): void
    {
        $this->components->task('Migrating settings', function () use ($config): void {
            $mapping = [
                'mail_domain' => $config['mail-domain'] ?? '',
                'finance_mail' => $config['finance-mail'] ?? '',
                'project.protocol_url.prefix' => data_get($config, 'projekt-form.protokoll-prefix', ''),
                'project.protocol_url.label' => data_get($config, 'projekt-form.protokoll-label', ''),
                'project.protocol_url.active' => ! empty(data_get($config, 'projekt-form.protokoll-label', '')),
                'project.description.min_length' => data_get($config, 'projekt-form.description-min-length', 50),
                'project.description.max_length' => data_get($config, 'projekt-form.description-max-length', 99999),
            ];

            foreach ($mapping as $key => $value) {
                Setting::set($key, $value);
            }
        });
    }

    private function migrateLegalBases(array $config): void
    {
        $rechtsgrundlagen = $config['rechtsgrundlagen'] ?? [];

        if (empty($rechtsgrundlagen)) {
            $this->components->warn('No rechtsgrundlagen found in config, skipping.');

            return;
        }

        $this->components->task('Migrating legal bases ('.count($rechtsgrundlagen).')', function () use ($rechtsgrundlagen): void {
            $order = 0;

            foreach ($rechtsgrundlagen as $slug => $entry) {
                LegalBasis::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $entry['label'] ?? '',
                        'label_additional' => $entry['label-additional'] ?? null,
                        'hint_text' => $entry['hint-text'] ?? null,
                        'placeholder' => $entry['placeholder'] ?? null,
                        'sort_order' => $order++,
                        'is_active' => true,
                    ]
                );
            }
        });
    }

    private function migrateGremien(array $config): void
    {
        $gremien = $config['gremien'] ?? [];

        if (empty($gremien)) {
            $this->components->warn('No gremien found in config, skipping.');

            return;
        }

        // Flatten the categorized structure into a single list
        $flat = Arr::flatten($gremien);

        $this->components->task('Migrating visible gremien ('.count($flat).')', function () use ($flat): void {
            Setting::set('user.committees.data', $flat);
            Setting::set('user.committees.mode', 'filter');
        });
    }

    /**
     * Load the legacy config array.
     */
    private function getLegacyConfig(): array
    {
        $realm = config('stufis.realm');

        // Allow an environment-specific orgs file (e.g. config.orgs.testing.php),
        if ($realm === 'testing') {
            $file = base_path('legacy/config/config.orgs.testing.php');
        } else {
            $file = base_path('legacy/config/config.orgs.php');
        }

        return (include $file)[$realm];
    }
}
