<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\Enums\BudgetType;
use Cknow\Money\Money;
use Illuminate\Support\Collection;

/**
 * Builds an amendment's FULL plan view: the parent plan's item tree merged with the amendment's
 * own overlay (see App\Models\BudgetItemChange / AmendmentApplier for the change-set design),
 * every node annotated with what it looked like before the amendment and what it will look like
 * after it. The counterpart to the changed-items-only diff on the same page.
 *
 * The merge deliberately reads the same union of plans as the editor (⚡amendment-edit's
 * allItems()): base items live on the parent plan, this amendment's own additions on the
 * amendment — and apply()/revert() only ever re-home rows between the two (never reassigning
 * ids), so the very same union reconstructs the tree before AND after the amendment was applied.
 * Field-level before/after always comes from the change row's `diff` pair rather than the live
 * row, for the same reason: the pair reads identically in both directions, while the live row
 * holds `from` before the apply and `to` after it.
 *
 * Annotations set on every returned item (view-only, non-persisted attributes, mirroring
 * BudgetPlanMeasures::assign()):
 *
 *  - `amendment_action` — the BudgetItemChangeAction touching this item, or null when untouched
 *  - `amendment_reason` — the reason recorded for that change, if any
 *  - `before_value` / `after_value` — Money, or null when the item does not exist on that side
 *  - `name_after` / `name_before` — the Titelname to display, and the previous one when this
 *    amendment renames the item (null when it doesn't)
 *  - `tree_level` — 0-based nesting depth, for the row indent
 *  - `ancestor_ids` — ids of the ancestor groups, for the collapse behaviour
 */
class AmendmentTree
{
    /** @var array<string, Collection<int, BudgetItem>> flattened, annotated forest per side */
    private array $annotated = [];

    /** @var Collection<int, BudgetItemChange>|null this amendment's change rows, keyed by item id */
    private ?Collection $changes = null;

    /** @var array<int, Money|null> */
    private array $before = [];

    /** @var array<int, Money|null> */
    private array $after = [];

    /** @var array<int, list<BudgetItem>> effective children per parent id (0 = roots) */
    private array $children = [];

    public function __construct(private readonly BudgetPlan $amendment) {}

    /**
     * The merged forest for one side, flattened in display (pre-order) order and annotated.
     *
     * @return Collection<int, BudgetItem>
     */
    public function annotate(BudgetType $type): Collection
    {
        if (isset($this->annotated[$type->value])) {
            return $this->annotated[$type->value];
        }

        $this->loadChildren($type);

        $rows = collect();
        foreach ($this->childrenOf(0) as $root) {
            $this->walk($root, 0, [], $rows);
        }

        return $this->annotated[$type->value] = $rows;
    }

    /**
     * This side's plan totals before and after the amendment — the sum of the roots' figures,
     * with a root that only exists on one side counting as zero on the other.
     *
     * @return array{before: Money, after: Money}
     */
    public function totals(BudgetType $type): array
    {
        $before = Money::EUR(0);
        $after = Money::EUR(0);
        foreach ($this->annotate($type)->where('tree_level', 0) as $root) {
            $before = $before->add($root->before_value ?? Money::EUR(0));
            $after = $after->add($root->after_value ?? Money::EUR(0));
        }

        return ['before' => $before, 'after' => $after];
    }

    /**
     * How many items this amendment touches on one side, per action plus the total. Feeds the tab
     * badges, so a side that changed is recognisable without opening it first.
     *
     * @return array{total: int, add: int, modify: int, delete: int}
     */
    public function changeCounts(BudgetType $type): array
    {
        $counts = ['total' => 0, 'add' => 0, 'modify' => 0, 'delete' => 0];

        foreach ($this->annotate($type) as $row) {
            if ($row->amendment_action === null) {
                continue;
            }
            $counts[$row->amendment_action->value]++;
            $counts['total']++;
        }

        return $counts;
    }

    /**
     * Load the merged item set for one side and index it by effective parent, each sibling list
     * ordered by effective position — "effective" meaning the overlay's value when this amendment
     * moves the item, so the tree renders in the order the amendment establishes.
     */
    private function loadChildren(BudgetType $type): void
    {
        $planIds = array_filter([$this->amendment->parent_plan_id, $this->amendment->id]);

        $items = BudgetItem::whereIn('budget_plan_id', $planIds)
            ->where('budget_type', $type)
            ->get();

        $this->children = [];
        foreach ($items as $item) {
            $parentId = $this->effectiveField($item, 'parent_id');
            $this->children[$parentId === null ? 0 : (int) $parentId][] = $item;
        }
        foreach (array_keys($this->children) as $parentId) {
            usort(
                $this->children[$parentId],
                fn (BudgetItem $a, BudgetItem $b): int => (int) $this->effectiveField($a, 'position') <=> (int) $this->effectiveField($b, 'position'),
            );
        }
    }

    /**
     * Annotate $item and append it, then recurse into its children — pre-order, so the flattened
     * collection reads exactly as the table renders it.
     *
     * @param  list<int>  $ancestorIds
     * @param  Collection<int, BudgetItem>  $rows
     */
    private function walk(BudgetItem $item, int $level, array $ancestorIds, Collection $rows): void
    {
        $change = $this->changeFor($item);
        $nameChange = $change?->fieldChange('name');

        $item->amendment_action = $change?->action;
        $item->amendment_reason = $change?->reason;
        $item->before_value = $this->beforeValue($item);
        $item->after_value = $this->afterValue($item);
        // the live row still holds the OLD name until the amendment is applied (and the new one
        // after), so the name to display comes from the overlay too — never from the row alone
        $item->name_before = $nameChange['from'] ?? null;
        $item->name_after = $nameChange['to'] ?? $item->name;
        $item->tree_level = $level;
        $item->ancestor_ids = $ancestorIds;

        $rows->push($item);

        foreach ($this->childrenOf($item->id) as $child) {
            $this->walk($child, $level + 1, [...$ancestorIds, $item->id], $rows);
        }
    }

    /** The item's value before this amendment, or null when the amendment is what creates it. */
    private function beforeValue(BudgetItem $item): ?Money
    {
        if (array_key_exists($item->id, $this->before)) {
            return $this->before[$item->id];
        }

        if ($this->actionFor($item) === BudgetItemChangeAction::Add) {
            return $this->before[$item->id] = null;
        }

        return $this->before[$item->id] = $this->rollUp(
            $item,
            fn (BudgetItem $child): ?Money => $this->beforeValue($child),
            'from',
        );
    }

    /** The item's value once this amendment is effective, or null when the amendment removes it. */
    private function afterValue(BudgetItem $item): ?Money
    {
        if (array_key_exists($item->id, $this->after)) {
            return $this->after[$item->id];
        }

        if ($this->actionFor($item) === BudgetItemChangeAction::Delete) {
            return $this->after[$item->id] = null;
        }

        return $this->after[$item->id] = $this->rollUp(
            $item,
            fn (BudgetItem $child): ?Money => $this->afterValue($child),
            'to',
        );
    }

    /**
     * One side's figure for a single node: a mount resolves to the referenced plan's total (an
     * amendment never touches mounts, so both sides read the same), a group sums whichever of its
     * children exist on that side, and a leaf reads the change row's $side of the `value` pair,
     * falling back to its stored value when this amendment doesn't touch it.
     *
     * @param  callable(BudgetItem): ?Money  $resolveChild
     * @param  'from'|'to'  $side
     */
    private function rollUp(BudgetItem $item, callable $resolveChild, string $side): Money
    {
        if ($item->isMount()) {
            return $item->effectiveValue();
        }

        if ($item->is_group) {
            $sum = Money::EUR(0);
            foreach ($this->childrenOf($item->id) as $child) {
                $sum = $sum->add($resolveChild($child) ?? Money::EUR(0));
            }

            return $sum;
        }

        $pair = $this->changeFor($item)?->fieldChange('value');

        return $pair !== null ? Money::EUR((int) $pair[$side]) : ($item->value ?? Money::EUR(0));
    }

    /**
     * The value $field effectively holds under this amendment — the overlay's `to` when a modify
     * change touches it, the stored value otherwise. Mirrors ⚡amendment-edit's effectiveField().
     */
    private function effectiveField(BudgetItem $item, string $field): mixed
    {
        $pair = $this->changeFor($item)?->fieldChange($field);

        return $pair !== null ? $pair['to'] : $item->getAttribute($field);
    }

    /** @return list<BudgetItem> */
    private function childrenOf(int $parentId): array
    {
        return $this->children[$parentId] ?? [];
    }

    private function actionFor(BudgetItem $item): ?BudgetItemChangeAction
    {
        return $this->changeFor($item)?->action;
    }

    private function changeFor(BudgetItem $item): ?BudgetItemChange
    {
        $this->changes ??= $this->amendment->itemChanges()->get()->keyBy('budget_item_id');

        $change = $this->changes->get($item->id);

        // a modify row only carries an overlay while it still has fields — an empty one is
        // pruned by the editor, but never treat a stray one as a marked change either
        return $change !== null && $change->isEmpty() ? null : $change;
    }
}
