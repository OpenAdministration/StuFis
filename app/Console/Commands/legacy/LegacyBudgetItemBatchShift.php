<?php

namespace App\Console\Commands\legacy;

use Illuminate\Console\Command;
use Spatie\Regex\Regex;

/**
 * @deprecated Legacy HHP tooling, slated for deletion. The legacy budget tables are now read-only
 * views over budget_plan/budget_item (migration swap_legacy_budget_tables_for_views), so this
 * command can no longer write them. Manage budget plans in the new budget plan module.
 */
class LegacyBudgetItemBatchShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:budget-id-batch-shift
        {inputs* : The pairs to shift in the form x->y or x-y}
        {--bypass-validation : dont check if the titels exist }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[DEPRECATED] Does legacy:budget-item-shift with multiple entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  DEPRECATED: the legacy budget tables are now views; this command is slated for deletion and will fail against them.');

        $rawInputs = $this->argument('inputs');
        $switch = [];
        // input validation
        foreach ($rawInputs as $input) {
            $reg = Regex::match('/^(\d+)->?(\d+)$/', $input);
            if (! $reg->hasMatch()) {
                $this->fail("$input is malformed");
            }
            $switch[] = [$reg->group(1), $reg->group(2)];
        }

        $this->info('Transforming '.count($switch).' Legacy Titles');

        return \DB::transaction(function () use ($switch): int {
            \Schema::disableForeignKeyConstraints();
            foreach ($switch as [$oldId, $newId]) {
                $res = $this->call('legacy:budget-id-shift', [
                    'old_id' => $oldId,
                    'new_id' => $newId,
                    '--non-interactive' => true,
                    '--bypass-validation' => $this->option('bypass-validation'),
                ]);
                if ($res === self::FAILURE) {
                    $this->fail("Failed subprocess $oldId->$newId. Aborting & Roling back...");
                }
            }
            \Schema::enableForeignKeyConstraints();

            return self::SUCCESS;
        });

    }
}
