<?php

namespace App\Console\Commands\stufis;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;

/**
 * Regenerates storage/demo/stufis-demo-data.sql from a fully-migrated demo database (credentials
 * from .env). Prepare the demo data on your DB, run this, review the diff, commit.
 *
 * Beyond a plain dump it:
 *  - filters to the demo tables only (budget_plan/budget_item/fiscal_year are seeded by
 *    DemoBudgetSeeder, not the dump, and infra tables like `migrations` are excluded);
 *  - normalises the year-shifted dates back to the base years (2023-2025) that DemoDataSeeder
 *    re-shifts on load, so the committed dump is delta-independent.
 */
class RedumpDemoData extends Command
{
    protected $signature = 'stufis:demo-redump
        {--connection= : Connection to read from (default: the configured default connection)}
        {--output= : Target file (default: the demo disk\'s stufis-demo-data.sql)}
        {--force : Allow running when the app environment is production}';

    protected $description = 'Regenerate the demo SQL dump (storage/demo/stufis-demo-data.sql) from the current database.';

    /**
     * Demo tables not to export, especially views and laravel framework specific tables.
     */
    private const array EXCLUDED_TABLES = [
        'failed_jobs', 'migrations', 'haushaltsplan', 'haushaltsgruppen', 'haushaltstitel', 'legal_bases', 'settings',
    ];

    public function handle(): int
    {
        if ($this->getLaravel()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');

            return self::FAILURE;
        }

        $db = DB::connection($this->option('connection') ?: config('database.default'));
        $prefix = $db->getTablePrefix();
        $delta = DemoDataSeeder::yearShiftDelta();

        MySql::create()
            // connection
            ->setDbName($db->getDatabaseName())
            ->setHost($db->getConfig('host'))
            ->setPort($db->getConfig('port'))
            ->setUserName($db->getConfig('username'))
            ->setPassword($db->getConfig('password'))
            // options
            ->excludeTables(Arr::map(self::EXCLUDED_TABLES, fn ($table) => $prefix.$table))
            ->doNotCreateTables()
            ->addExtraOption('--skip-comments --skip-add-locks --complete-insert')
            ->dumpToFile(Storage::disk('demo')->path('stufis-demo-data.sql.tmp'));

        $body = Storage::disk('demo')->get('stufis-demo-data.sql.tmp');
        // Inverse of DemoDataSeeder's year-shift: normalise the shifted years back to the base
        // years so the committed dump is delta-independent. Digit boundaries keep us off years
        // embedded in longer numbers (amounts, IBANs, refs), matching the forward transform.
        if ($delta !== 0) {
            $shifted = [2023 + $delta, 2024 + $delta, 2025 + $delta];
            $body = preg_replace_callback(
                '/(?<!\d)('.implode('|', $shifted).')(?!\d)/',
                fn (array $m): string => (string) ((int) $m[1] - $delta),
                (string) $body,
            );
        }
        // normalize table prefixes to demo__
        $body = str_replace($prefix, 'demo__', $body);

        $output = $this->option('output') ?: Storage::disk('demo')->path('stufis-demo-data.sql');
        file_put_contents($output, $body);
        Storage::disk('demo')->delete('stufis-demo-data.sql.tmp');

        $this->info("Wrote {$output} (year-shift delta {$delta} normalised out).");

        return self::SUCCESS;
    }
}
