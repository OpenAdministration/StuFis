<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Regex\Regex;

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
    protected $description = 'Does legacy:budget-item-shift with multiple entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawInputs = $this->argument('inputs');
        $switch = [];
        // input validation
        foreach ($rawInputs as $input) {
            $reg = Regex::match('/^(\d)+->?(\d)+$/', $input);
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
                    $this->fail("Failed subprocess $oldId->$newId. Aborting...");
                }
            }
            \Schema::enableForeignKeyConstraints();

            return self::SUCCESS;
        });

    }
}
