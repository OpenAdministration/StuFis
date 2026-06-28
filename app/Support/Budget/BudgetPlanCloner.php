<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Support\Facades\DB;

/**
 * Deep-clones a whole budget plan — its full item forest (both budget types, groups, leaves
 * and mounts) — into a target plan. Used both by the "duplicate" entry point and by creating a
 * new plan "from an existing one"; the two are the same operation.
 *
 * Mounts are resolved per the caller's choices: a mounted sub-plan can be recursively cloned
 * into its own new draft ("copy") or flattened into an empty group ("drop").
 */
class BudgetPlanCloner
{
    /**
     * Clone the item forest of $source into $target (an already-created plan).
     *
     * $mountChoices is keyed by the *source* referenced_plan_id; an unlisted plan defaults to
     * 'drop'. The clone map memoizes sub-plan clones, which also guards reference cycles: a plan
     * already cloned (including $source itself) is reused, never re-entered.
     *
     * @param  array<int, 'copy'|'drop'>  $mountChoices  keyed by source referenced_plan_id
     */
    public function cloneInto(BudgetPlan $source, BudgetPlan $target, array $mountChoices = []): void
    {
        DB::transaction(function () use ($source, $target, $mountChoices): void {
            $cloneMap = [$source->id => $target];
            $this->cloneForest($source, $target, $mountChoices, $cloneMap);
        });
    }

    /**
     * Clone every root item of $source (both budget types) into $target.
     *
     * @param  array<int, 'copy'|'drop'>  $mountChoices
     * @param  array<int, BudgetPlan>  $cloneMap  sourcePlanId => cloned plan
     */
    private function cloneForest(BudgetPlan $source, BudgetPlan $target, array $mountChoices, array &$cloneMap): void
    {
        foreach ($source->rootBudgetItems()->orderBy('position')->get() as $root) {
            $this->cloneItem($root, $target, null, $mountChoices, $cloneMap);
        }
    }

    /**
     * Recursively clone $item (and its subtree) under $parentId in $target.
     *
     * @param  array<int, 'copy'|'drop'>  $mountChoices
     * @param  array<int, BudgetPlan>  $cloneMap
     */
    private function cloneItem(BudgetItem $item, BudgetPlan $target, ?int $parentId, array $mountChoices, array &$cloneMap): void
    {
        $attributes = [
            'budget_plan_id' => $target->id,
            'parent_id' => $parentId,
            'budget_type' => $item->budget_type,
            'short_name' => $item->short_name,
            'name' => $item->name,
            'is_group' => $item->is_group,
            'position' => $item->position,
            'value' => $item->value,
            'referenced_plan_id' => null,
        ];

        if ($item->isMount()) {
            if (($mountChoices[$item->referenced_plan_id] ?? 'drop') === 'copy') {
                $clone = $this->cloneSubPlan($item->referencedPlan, $target, $mountChoices, $cloneMap);
                $attributes['referenced_plan_id'] = $clone->id;
                $attributes['is_group'] = false;
            } else {
                // drop: keep the label, but turn it into a plain empty group with no value
                $attributes['is_group'] = true;
                $attributes['value'] = Money::EUR(0);
            }
        }

        $new = BudgetItem::create($attributes);

        // a mount is a leaf; everything else recurses into its children
        if (! $new->isMount()) {
            foreach ($item->orderedChildren as $child) {
                $this->cloneItem($child, $target, $new->id, $mountChoices, $cloneMap);
            }
        }
    }

    /**
     * Clone a mounted sub-plan into its own new draft (memoized), then clone its forest. The
     * clone inherits the target's fiscal year so the same-fiscal-year mount invariant holds.
     *
     * @param  array<int, 'copy'|'drop'>  $mountChoices
     * @param  array<int, BudgetPlan>  $cloneMap
     */
    private function cloneSubPlan(BudgetPlan $source, BudgetPlan $target, array $mountChoices, array &$cloneMap): BudgetPlan
    {
        if (isset($cloneMap[$source->id])) {
            return $cloneMap[$source->id];
        }

        $clone = BudgetPlan::create([
            'state' => Draft::class,
            'organization' => BudgetPlan::resolveOrganization($source->organization, $target->fiscal_year_id),
            'fiscal_year_id' => $target->fiscal_year_id,
        ]);
        $cloneMap[$source->id] = $clone;

        $this->cloneForest($source, $clone, $mountChoices, $cloneMap);

        return $clone;
    }
}
