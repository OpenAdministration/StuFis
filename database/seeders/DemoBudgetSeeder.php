<?php

namespace Database\Seeders;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\Support\Budget\BudgetPlanConverter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds the demo budget plans into the new budget_plan/budget_item structure (replacing the
 * legacy haushaltsplan/gruppen/titel inserts that used to live in the demo SQL dump — those
 * tables are now views). Leaf ids and plan ids are preserved, so the dump's booking and
 * projektposten rows (which reference titel_id == budget_item.id) still resolve. Runs before
 * DemoDataSeeder so those rows find their budget items.
 */
class DemoBudgetSeeder extends Seeder
{
    /** Next id for a group item — kept above the preserved leaf ids so the two never collide. */
    private int $nextGroupId;

    public function run(): void
    {
        /** @var array<int, array{von: string, bis: ?string, state: string, groups: array<int, array{name: string, type: int, titels: array<int, array{id: int, name: string, nr: string, value: string}>}>}> $plans */
        $plans = require database_path('seeders/data/demo_budget.php');

        $delta = DemoDataSeeder::yearShiftDelta();
        $converter = new BudgetPlanConverter;

        // group items get fresh ids above every preserved leaf id (mirrors the converter)
        $this->nextGroupId = collect($plans)
            ->flatMap(fn (array $plan) => collect($plan['groups'])->flatMap(fn (array $g) => array_column($g['titels'], 'id')))
            ->max() + 1;

        foreach ($plans as $planId => $plan) {
            $von = Carbon::parse($plan['von'])->addYears($delta);
            // a NULL legacy "bis" means open-ended; mirror the converter's one-year fallback
            $bis = $plan['bis'] !== null
                ? Carbon::parse($plan['bis'])->addYears($delta)
                : $von->copy()->addYear()->subDay();

            $fiscalYear = FiscalYear::create(['start_date' => $von, 'end_date' => $bis]);

            $budgetPlan = new BudgetPlan([
                'organization' => 'StuRa',
                'fiscal_year_id' => $fiscalYear->id,
                'state' => $converter->convertState($plan['state']),
            ]);
            $budgetPlan->id = $planId; // preserve the plan id
            $budgetPlan->save();

            $groupPosition = 0;
            foreach ($plan['groups'] as $group) {
                $this->seedGroup($budgetPlan, $group, $groupPosition++, $converter);
            }
        }
    }

    /**
     * Create one group's leaves (preserved ids) and the group item itself, mirroring the
     * converter: leaves first, then the group with the leaves re-parented under it.
     *
     * @param  array{name: string, type: int, titels: array<int, array{id: int, name: string, nr: string, value: string}>}  $group
     */
    private function seedGroup(BudgetPlan $plan, array $group, int $position, BudgetPlanConverter $converter): void
    {
        $type = $group['type'] == 0 ? BudgetType::INCOME : BudgetType::EXPENSE;

        $leafIds = [];
        $total = '0';
        $itemPosition = 0;
        foreach ($group['titels'] as $titel) {
            $item = new BudgetItem([
                'budget_plan_id' => $plan->id,
                'short_name' => $titel['nr'],
                'name' => $titel['name'],
                'value' => $titel['value'],
                'budget_type' => $type,
                'is_group' => false,
                'parent_id' => null,
                'position' => $itemPosition++,
            ]);
            $item->id = $titel['id']; // preserve the leaf id
            $item->save();

            $leafIds[] = $titel['id'];
            $total = bcadd($total, $titel['value'], 2);
        }

        $shortName = $converter->deriveGroupShortName(array_column($group['titels'], 'nr'))
            ?? $type->numberPrefix().'.'.($position + 1);

        $groupItem = new BudgetItem([
            'budget_plan_id' => $plan->id,
            'short_name' => $shortName,
            'name' => $group['name'],
            'value' => $total,
            'budget_type' => $type,
            'is_group' => true,
            'parent_id' => null,
            'position' => $position,
        ]);
        $groupItem->id = $this->nextGroupId++; // above the leaf ids, so no collision
        $groupItem->save();

        BudgetItem::whereIn('id', $leafIds)->update(['parent_id' => $groupItem->id]);
    }
}
