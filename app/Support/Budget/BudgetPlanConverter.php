<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use App\States\BudgetPlan\BudgetPlanState;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Published;
use Illuminate\Support\Collection;

/**
 * Converts legacy budget plans (haushaltsplan/haushaltsgruppen/haushaltstitel) into the new
 * budget_plan/budget_item structure, preserving leaf budget-item ids so existing bookings,
 * project posts and tax titles keep resolving. Group items get fresh ids above the global
 * leaf maximum. Idempotent: a plan whose id already exists in the new structure is skipped,
 * so it is safe to run repeatedly (e.g. from a migration and again from the console).
 */
class BudgetPlanConverter
{
    /** Map of legacy group id to new group-item id and metadata. */
    private array $groupIdMapping = [];

    /** Next available group id (shared across all plans in one run). */
    private int $nextGroupId;

    /** @param  callable(string):void|null  $log  optional line logger for progress output */
    public function __construct(private $log = null) {}

    /**
     * Convert all legacy plans (or a single one). Does not manage its own transaction — the
     * caller wraps it as needed (the console command for dry-run, the migration implicitly).
     */
    public function convert(?int $planId = null, string $organization = 'default'): void
    {
        $legacyPlans = $planId
            ? LegacyBudgetPlan::where('id', $planId)->get()
            : LegacyBudgetPlan::all();

        if ($legacyPlans->isEmpty()) {
            $this->log('No legacy budget plans found to convert.');

            return;
        }

        $this->log("Found {$legacyPlans->count()} legacy budget plan(s) to convert.");

        // CRITICAL: find the global maximum item id across ALL plans before starting, so group
        // ids (assigned sequentially from here) never collide with a preserved leaf id.
        $this->nextGroupId = $this->findGlobalMaxItemId($legacyPlans) + 1;

        foreach ($legacyPlans as $legacyPlan) {
            $this->convertPlan($legacyPlan, $organization);
        }
    }

    private function log(string $message): void
    {
        if ($this->log !== null) {
            ($this->log)($message);
        }
    }

    /** Find the global maximum legacy item id across the plans (and existing budget items). */
    private function findGlobalMaxItemId(Collection $legacyPlans): int
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

        $existingMaxId = BudgetItem::max('id') ?? 0;

        return max($maxId, $existingMaxId);
    }

    private function convertPlan(LegacyBudgetPlan $legacyPlan, string $organization): void
    {
        // already converted — skip (idempotent)
        if (BudgetPlan::find($legacyPlan->id)) {
            $this->log("Budget Plan ID {$legacyPlan->id} already exists in new structure. Skipping.");

            return;
        }

        $fiscalYear = $this->getOrCreateFiscalYear($legacyPlan);
        $state = $this->convertState($legacyPlan->state);

        $newPlan = new BudgetPlan([
            'organization' => $organization,
            'fiscal_year_id' => $fiscalYear->id,
            'state' => $state,
            'resolution_date' => null,
            'approval_date' => null,
        ]);
        $newPlan->id = $legacyPlan->id; // preserve the plan id
        $newPlan->save();

        $this->log("Created Budget Plan (ID: {$newPlan->id}, State: {$state::$name})");

        $legacyGroups = $legacyPlan->budgetGroups()->with('budgetItems')->get();

        // PASS 1: create leaf items with preserved ids, temporarily parentless
        $groupPosition = 0;
        foreach ($legacyGroups as $legacyGroup) {
            $this->convertItemsFirstPass($legacyGroup, $newPlan, $groupPosition);
            $groupPosition++;
        }

        // PASS 2: create the group items and re-parent the leaves under them
        $this->createGroupItems($legacyGroups, $newPlan);
    }

    private function convertItemsFirstPass(LegacyBudgetGroup $legacyGroup, BudgetPlan $newPlan, int $groupPosition): void
    {
        $budgetType = $legacyGroup->type == 0 ? BudgetType::INCOME : BudgetType::EXPENSE;

        $futureGroupId = $this->nextGroupId;
        $this->nextGroupId++;

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
                $this->log("Budget Item ID {$legacyItem->id} already exists. Skipping.");

                continue;
            }

            $newItem = new BudgetItem([
                'budget_plan_id' => $newPlan->id,
                'short_name' => $legacyItem->titel_nr ?? $this->generateShortName($legacyItem->titel_name),
                'name' => $legacyItem->titel_name,
                'value' => $legacyItem->value,
                'budget_type' => $budgetType,
                'description' => null,
                'parent_id' => null, // set in pass 2
                'is_group' => false,
                'position' => $itemPosition,
            ]);
            $newItem->id = $legacyItem->id; // preserve the leaf id
            $newItem->save();

            $this->groupIdMapping[$legacyGroup->id]['items'][] = $legacyItem->id;
            $itemPosition++;
        }
    }

    private function createGroupItems(Collection $legacyGroups, BudgetPlan $newPlan): void
    {
        // Legacy groups have no number of their own; resolve each one up front.
        $shortNames = $this->resolveGroupShortNames($legacyGroups);

        foreach ($legacyGroups as $legacyGroup) {
            $groupInfo = $this->groupIdMapping[$legacyGroup->id];
            $groupId = $groupInfo['new_id'];
            $totalValue = $legacyGroup->budgetItems()->sum('value');

            $groupItem = new BudgetItem([
                'budget_plan_id' => $newPlan->id,
                'short_name' => $shortNames[$legacyGroup->id],
                'name' => $groupInfo['name'],
                'value' => $totalValue,
                'budget_type' => $groupInfo['type'],
                'description' => null,
                'parent_id' => null,
                'is_group' => true,
                'position' => $groupInfo['position'],
            ]);
            $groupItem->id = $groupId;
            $groupItem->save();

            if (! empty($groupInfo['items'])) {
                BudgetItem::whereIn('id', $groupInfo['items'])->update(['parent_id' => $groupId]);
            }
        }
    }

    private function getOrCreateFiscalYear(LegacyBudgetPlan $legacyPlan): FiscalYear
    {
        $fiscalYear = FiscalYear::where('start_date', $legacyPlan->von)
            ->where('end_date', $legacyPlan->bis)
            ->first();

        if (! $fiscalYear) {
            $fiscalYear = new FiscalYear([
                'start_date' => $legacyPlan->von,
                'end_date' => $legacyPlan->bis ?? $legacyPlan->von->copy()->addYear()->subDay(),
            ]);
            $fiscalYear->save();

            $this->log("Created Fiscal Year: {$fiscalYear->start_date} to {$fiscalYear->end_date}");
        }

        return $fiscalYear;
    }

    /**
     * Convert a legacy state value to a new BudgetPlanState class.
     *
     * @return class-string<BudgetPlanState>
     */
    public function convertState(?string $state): string
    {
        return match ($state) {
            'final', 'approved', '1' => Published::class,
            default => Draft::class,
        };
    }

    /**
     * Resolve each legacy group's Titelnummer.
     *
     * Preferred: derive from the group's children (E.1.1 -> E.1). When a group has no numbered
     * children, fall back to auto-counting the next free number per budget type (as the legacy
     * system did), skipping any number already taken by a derived group so they never collide.
     *
     * @return array<int, string> legacy group id => Titelnummer
     */
    private function resolveGroupShortNames(Collection $legacyGroups): array
    {
        $resolved = [];
        $usedByPrefix = [];
        $needsFallback = [];

        foreach ($legacyGroups as $group) {
            $derived = $this->deriveGroupShortName($group->budgetItems->pluck('titel_nr')->all());

            if ($derived === null) {
                $needsFallback[] = $group->id;

                continue;
            }

            $resolved[$group->id] = $derived;

            if (preg_match('/^(\D+)\.(\d+)$/', $derived, $m)) {
                $usedByPrefix[$m[1]][(int) $m[2]] = true;
            }
        }

        foreach ($needsFallback as $groupId) {
            $prefix = $this->groupIdMapping[$groupId]['type']->numberPrefix();

            $resolved[$groupId] = $this->nextFreeGroupNumber($prefix, $usedByPrefix[$prefix] ?? []);
            $usedByPrefix[$prefix][(int) substr(strrchr($resolved[$groupId], '.'), 1)] = true;
        }

        return $resolved;
    }

    /**
     * Derive a group's Titelnummer from the parent prefix of the shallowest numbered child
     * (["E.1.1", "E.1.2"] -> "E.1"), or null when no child is numbered.
     *
     * @param  array<int, string|null>  $childTitelNrs
     */
    public function deriveGroupShortName(array $childTitelNrs): ?string
    {
        $numbered = array_values(array_filter(
            $childTitelNrs,
            static fn ($nr): bool => filled($nr) && str_contains((string) $nr, '.'),
        ));

        if ($numbered === []) {
            return null;
        }

        usort($numbered, static fn ($a, $b): int => substr_count($a, '.') <=> substr_count($b, '.'));

        return substr($numbered[0], 0, (int) strrpos($numbered[0], '.'));
    }

    /**
     * The first "{prefix}.{n}" whose number is not already used (auto-counting fallback).
     *
     * @param  array<int, bool>  $used  numbers already taken, keyed by the number
     */
    public function nextFreeGroupNumber(string $prefix, array $used): string
    {
        $n = 1;
        while (isset($used[$n])) {
            $n++;
        }

        return "{$prefix}.{$n}";
    }

    private function generateShortName(string $fullName): string
    {
        $words = explode(' ', $fullName);
        $shortName = implode(' ', array_slice($words, 0, 3));

        return substr($shortName, 0, 20);
    }
}
