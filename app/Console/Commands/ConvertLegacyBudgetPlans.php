<?php

namespace App\Console\Commands;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetPlanState;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
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
     * Map of legacy group ID to new group item ID
     */
    protected array $groupIdMapping = [];

    /**
     * Next available group ID (shared across all plans)
     */
    protected int $nextGroupId;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $planId = $this->option('plan-id');
        $organization = $this->option('organization');

        if ($dryRun) {
            $this->warn('🔍 Running in DRY-RUN mode - no changes will be made');
        }

        // Get legacy plans to convert
        $legacyPlans = $planId
            ? LegacyBudgetPlan::where('id', $planId)->get()
            : LegacyBudgetPlan::all();

        if ($legacyPlans->isEmpty()) {
            $this->error('No legacy budget plans found to convert.');

            return self::FAILURE;
        }

        $this->info("Found {$legacyPlans->count()} legacy budget plan(s) to convert.");

        // CRITICAL: Find global maximum item ID across ALL plans before starting
        $this->nextGroupId = $this->findGlobalMaxItemId($legacyPlans) + 1;
        $this->line('🔢 Global max legacy item ID: '.($this->nextGroupId - 1));
        $this->line("🔢 Group IDs will start from: {$this->nextGroupId}");

        DB::beginTransaction();

        try {
            foreach ($legacyPlans as $legacyPlan) {
                $this->info("\n📋 Converting Legacy Plan ID: {$legacyPlan->id}");

                $this->convertPlan($legacyPlan, $organization, $dryRun);
            }

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

    /**
     * Find the global maximum legacy item ID across ALL plans to be converted
     */
    protected function findGlobalMaxItemId($legacyPlans): int
    {
        $maxId = 0;

        foreach ($legacyPlans as $plan) {
            $planMaxId = LegacyBudgetItem::whereHas('budgetGroup', function ($query) use ($plan): void {
                $query->where('hhp_id', $plan->id);
            })->max('id');

            if ($planMaxId > $maxId) {
                $maxId = $planMaxId;
            }
        }

        // Also check existing budget items in case we're adding to existing data
        $existingMaxId = BudgetItem::max('id') ?? 0;

        return max($maxId, $existingMaxId);
    }

    /**
     * Convert a single legacy budget plan
     */
    protected function convertPlan(LegacyBudgetPlan $legacyPlan, string $organization, bool $dryRun): void
    {
        // Check if plan already exists in new structure
        if (BudgetPlan::find($legacyPlan->id)) {
            $this->warn("  ⚠️  Budget Plan ID {$legacyPlan->id} already exists in new structure. Skipping.");

            return;
        }

        // Create or find fiscal year
        $fiscalYear = $this->getOrCreateFiscalYear($legacyPlan, $dryRun);

        // Convert state
        $state = $this->convertState($legacyPlan->state);

        // Create new budget plan with the same ID
        $newPlan = new BudgetPlan([
            'organization' => $organization,
            'fiscal_year_id' => $fiscalYear->id,
            'state' => $state,
            'resolution_date' => $legacyPlan->von,
            'approval_date' => null, // Legacy doesn't have this
            'parent_plan' => null,
        ]);

        // Force the ID to match the legacy plan
        $newPlan->id = $legacyPlan->id;

        if (! $dryRun) {
            $newPlan->save();
        }

        $this->line("  ✓ Created Budget Plan (ID: {$newPlan->id}, State: {$state->value})");

        // Get all legacy groups and items
        $legacyGroups = $legacyPlan->budgetGroups()->with('budgetItems')->get();
        $this->line("  📁 Processing {$legacyGroups->count()} budget groups...");

        // PASS 1: Create all budget items with preserved IDs
        $groupPosition = 0;
        foreach ($legacyGroups as $legacyGroup) {
            $this->convertItemsFirstPass($legacyGroup, $newPlan, $groupPosition, $dryRun);
            $groupPosition++;
        }

        // PASS 2: Create group items and update parent_id references
        $this->createGroupItems($legacyGroups, $newPlan, $dryRun);
    }

    /**
     * First pass: Create budget items with their original IDs, temporarily with no parent
     */
    protected function convertItemsFirstPass(
        LegacyBudgetGroup $legacyGroup,
        BudgetPlan $newPlan,
        int $groupPosition,
        bool $dryRun
    ): void {
        // Determine budget type from legacy type field (0 = income, 1 = expense)
        $budgetType = $legacyGroup->type == 0 ? BudgetType::INCOME : BudgetType::EXPENSE;

        // Assign the next available group ID and increment for next group
        $futureGroupId = $this->nextGroupId;
        $this->nextGroupId++;

        // Store the mapping for later
        $this->groupIdMapping[$legacyGroup->id] = [
            'new_id' => $futureGroupId,
            'name' => $legacyGroup->gruppen_name,
            'type' => $budgetType,
            'position' => $groupPosition,
            'items' => [],
        ];

        $itemPosition = 0;
        foreach ($legacyGroup->budgetItems as $legacyItem) {
            if (BudgetItem::find($legacyItem->id)) {
                $this->warn("      ⚠️  Budget Item ID {$legacyItem->id} already exists. Skipping.");

                continue;
            }

            $newItem = new BudgetItem([
                'budget_plan_id' => $newPlan->id,
                'short_name' => $legacyItem->titel_nr ?? $this->generateShortName($legacyItem->titel_name),
                'name' => $legacyItem->titel_name,
                'value' => $legacyItem->value,
                'budget_type' => $budgetType,
                'description' => null,
                'parent_id' => null, // Will be updated in pass 2
                'is_group' => false,
                'position' => $itemPosition,
            ]);

            // Force the ID to match the legacy item
            $newItem->id = $legacyItem->id;

            if (! $dryRun) {
                $newItem->save();
            }

            $this->line("      ✓ Item {$legacyItem->id}: {$legacyItem->titel_name}");

            // Store the legacy group ID this item belongs to
            $this->groupIdMapping[$legacyGroup->id]['items'][] = $legacyItem->id;

            $itemPosition++;
        }

        $this->line("    ✓ Group items created: {$legacyGroup->gruppen_name} (will be ID: {$futureGroupId})");
    }

    /**
     * Second pass: Create group items and update parent references
     */
    protected function createGroupItems($legacyGroups, BudgetPlan $newPlan, bool $dryRun): void
    {
        $this->line("\n  📦 Creating group items and updating parent references...");

        foreach ($legacyGroups as $legacyGroup) {
            $groupInfo = $this->groupIdMapping[$legacyGroup->id];
            $groupId = $groupInfo['new_id'];

            // Calculate total value for the group
            $totalValue = $legacyGroup->budgetItems()->sum('value');

            // Create the group item with the predetermined ID
            $groupItem = new BudgetItem([
                'budget_plan_id' => $newPlan->id,
                'short_name' => $this->generateShortName($groupInfo['name']),
                'name' => $groupInfo['name'],
                'value' => $totalValue,
                'budget_type' => $groupInfo['type'],
                'description' => null,
                'parent_id' => null,
                'is_group' => true,
                'position' => $groupInfo['position'],
            ]);

            $groupItem->id = $groupId;

            if (! $dryRun) {
                $groupItem->save();
            }

            $this->line("    ✓ Created Group Item ID {$groupId}: {$groupInfo['name']}");

            // Update all child items to point to this group
            if (! empty($groupInfo['items']) && ! $dryRun) {
                $itemCount = BudgetItem::whereIn('id', $groupInfo['items'])
                    ->update(['parent_id' => $groupId]);

                $this->line("      ↳ Updated {$itemCount} child items to parent ID {$groupId}");
            } elseif (! empty($groupInfo['items'])) {
                $this->line('      ↳ Would update '.count($groupInfo['items'])." child items to parent ID {$groupId}");
            }
        }
    }

    /**
     * Get or create fiscal year based on legacy plan dates
     */
    protected function getOrCreateFiscalYear(LegacyBudgetPlan $legacyPlan, bool $dryRun): FiscalYear
    {
        // Try to find existing fiscal year that matches the dates
        $fiscalYear = FiscalYear::where('start_date', $legacyPlan->von)
            ->where('end_date', $legacyPlan->bis)
            ->first();

        if (! $fiscalYear) {
            // Create a new fiscal year
            $fiscalYear = new FiscalYear([
                'start_date' => $legacyPlan->von,
                'end_date' => $legacyPlan->bis,
            ]);

            if (! $dryRun) {
                $fiscalYear->save();
            } else {
                // In dry-run, we need a mock ID
                $fiscalYear->id = 9999;
            }

            $this->line("  ✓ Created Fiscal Year: {$legacyPlan->von} to {$legacyPlan->bis}");
        }

        return $fiscalYear;
    }

    /**
     * Convert legacy state to new BudgetPlanState enum
     */
    protected function convertState(?string $state): BudgetPlanState
    {
        // Adjust this mapping based on your legacy state values
        return match ($state) {
            'final', 'approved', '1' => BudgetPlanState::FINAL,
            default => BudgetPlanState::DRAFT,
        };
    }

    /**
     * Generate a short name from a full name
     */
    protected function generateShortName(string $fullName): string
    {
        // Take first 3 words or first 20 characters
        $words = explode(' ', $fullName);
        $shortName = implode(' ', array_slice($words, 0, 3));

        return substr($shortName, 0, 20);
    }
}
