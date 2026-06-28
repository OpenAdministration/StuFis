<?php

namespace App\Console\Commands\legacy;

use App\Support\Budget\BudgetPlanConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertLegacyBudgetPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:convert-budget-plans
                            {--plan-id= : Convert only a specific plan by ID}
                            {--dry-run : Run without making changes}
                            {--organization=default : Organization name for the new budget plans}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert legacy budget plans (haushaltsplan) to new budget plan structure, preserving budget item IDs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $planId = $this->option('plan-id') !== null ? (int) $this->option('plan-id') : null;
        $organization = (string) $this->option('organization');

        if ($dryRun) {
            $this->warn('🔍 Running in DRY-RUN mode - no changes will be made');
        }

        $converter = new BudgetPlanConverter(fn (string $line) => $this->line('  '.$line));

        DB::beginTransaction();

        try {
            $converter->convert($planId, $organization);

            if ($dryRun) {
                DB::rollBack();
                $this->warn("\n✅ Dry run completed - no changes were made");
            } else {
                DB::commit();
                $this->info("\n✅ Conversion completed successfully!");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error during conversion: ".$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
