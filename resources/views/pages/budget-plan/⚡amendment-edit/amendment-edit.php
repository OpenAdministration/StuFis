<?php

use App\Livewire\BudgetPlan\ItemForm;
use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\Enums\BudgetType;
use App\Support\Budget\TitleNumberer;
use App\Support\Money\DefaultMoneyFormater;
use Cknow\Money\Money;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The amendment editor — OP#581. Renders the PARENT plan's item tree
 * merged with this amendment's overlay (see App\Models\BudgetItemChange / App\Support\Budget\
 * AmendmentApplier for the change-set design) and lets the user draft modify/add/delete changes
 * against it. The live parent-plan items are never written to directly here — every edit against
 * a base (parent-plan) item is recorded as a `modify` BudgetItemChange row instead; only items
 * this amendment itself created (`budget_plan_id` = the amendment) are written to directly, same
 * as a normal draft plan.
 *
 * Mirrors ⚡plan-edit's structure/conventions (the `items` ItemForm array, `updatedItems()`,
 * `sort()`) — see that component for the baseline this overlays.
 */
new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    public int $plan_id;

    public int $amendment_id;

    public $name;

    public $justification;

    /** @var array<int, string> budget_item_change.id => reason, bound to the "Begründungen" tab */
    public array $reasonInputs = [];

    /** @var array<int, ItemForm> keyed by budget_item.id (base AND own-addition items) */
    public $items = [];

    public function mount(int $plan_id, int $amendment_id): void
    {
        $amendment = BudgetPlan::findOrFail($amendment_id);
        $this->authorize('update', $amendment);

        abort_unless($amendment->parent_plan_id === $plan_id, 404);

        // the editor is only available while the amendment is still a draft — once it has moved
        // on in its own workflow, plan-view's diff view is the read-only place to look at it. Goes
        // through the model's isEditable() (BudgetPlan::isEditable() folds in this amendment-only
        // Draft-only rule) rather than re-checking the state directly.
        if (! $amendment->isEditable()) {
            $this->redirect(route('budget-plan.view', $amendment->id), navigate: true);

            return;
        }

        $this->plan_id = $plan_id;
        $this->amendment_id = $amendment_id;
        $this->name = $amendment->name;
        $this->justification = $amendment->justification;
        $this->reasonInputs = $amendment->itemChanges()->pluck('reason', 'id')->all();

        $this->loadItems();
    }

    private function amendment(): BudgetPlan
    {
        return BudgetPlan::findOrFail($this->amendment_id);
    }

    /** This amendment's change rows, keyed by the budget_item_id they touch. */
    private function changesByItem(): Collection
    {
        return BudgetItemChange::where('budget_plan_id', $this->amendment_id)->get()->keyBy('budget_item_id');
    }

    /** The merged forest: the parent plan's own items plus this amendment's own additions. */
    private function allItems(): Collection
    {
        return BudgetItem::whereIn('budget_plan_id', [$this->plan_id, $this->amendment_id])
            ->orderBy('position')
            ->get();
    }

    /**
     * (Re)build the ItemForm array the blade binds inputs to. Base items are pre-filled with
     * their EFFECTIVE (overlay `to`, when a modify change touches the field) values, so the input
     * shows the drafted value while the underlying row is untouched.
     */
    private function loadItems(): void
    {
        $this->items = [];
        $changes = $this->changesByItem();

        foreach ($this->allItems() as $item) {
            $form = new ItemForm($this, 'items.'.$item->id);
            $form->setItem($item);

            $change = $changes->get($item->id);
            if ($change !== null && $change->action === BudgetItemChangeAction::Modify) {
                if (($pair = $change->fieldChange('value')) !== null) {
                    $form->value = Money::EUR((int) $pair['to']);
                }
                if (($pair = $change->fieldChange('name')) !== null) {
                    $form->name = $pair['to'];
                }
                // no short_name overlay here — F2 (OP#581) made short_name immutable for base
                // items, so a modify change row can never carry it
            }

            $this->items[$item->id] = $form;
        }
    }

    public function with(): array
    {
        $all = $this->allItems();
        $changes = $this->changesByItem();

        $byParent = $all->groupBy('parent_id');
        foreach ($all as $item) {
            $item->setRelation('orderedChildren', $byParent->get($item->id, collect())->sortBy('position')->values());
        }

        $values = $this->computeValues($all, $changes);

        $roots = $all->whereNull('parent_id');
        $rootsFor = fn (BudgetType $type) => $roots
            ->filter(fn (BudgetItem $i): bool => $i->budget_type === $type)
            ->sortBy('position')->values();

        return [
            'parentPlan' => BudgetPlan::findOrFail($this->plan_id),
            'amendment' => $this->amendment(),
            'root_items' => ['in' => $rootsFor(BudgetType::INCOME), 'out' => $rootsFor(BudgetType::EXPENSE)],
            'values' => $values,
            'changes' => $changes,
            'delta_summary' => $this->amendment()->amendmentDeltaSummary(),
        ];
    }

    /**
     * Effective value of every item, memoized — a group sums its (effective) children live, a
     * leaf uses the overlay `to` value when this amendment modifies it, otherwise its stored
     * value. Mirrors ⚡plan-edit's computeValues(), plus the overlay lookup.
     *
     * @param  Collection<int, BudgetItem>  $all
     * @param  Collection<int, BudgetItemChange>  $changes
     * @return array<int, Money>
     */
    private function computeValues(Collection $all, Collection $changes): array
    {
        $map = [];
        $resolve = function (BudgetItem $item) use (&$resolve, &$map, $changes): Money {
            if (isset($map[$item->id])) {
                return $map[$item->id];
            }
            if ($item->is_group) {
                $sum = Money::EUR(0);
                foreach ($item->orderedChildren as $child) {
                    $sum = $sum->add($resolve($child));
                }

                return $map[$item->id] = $sum;
            }

            $change = $changes->get($item->id);
            if ($change !== null && $change->action === BudgetItemChangeAction::Modify && ($pair = $change->fieldChange('value')) !== null) {
                return $map[$item->id] = Money::EUR((int) $pair['to']);
            }

            return $map[$item->id] = $item->value ?? Money::EUR(0);
        };
        foreach ($all as $item) {
            $resolve($item);
        }

        return $map;
    }

    /** The value BudgetItem::$field currently effectively holds, considering this amendment's overlay. */
    private function effectiveField(BudgetItem $item, string $field): mixed
    {
        $change = $this->changesByItem()->get($item->id);
        if ($change !== null && $change->action === BudgetItemChangeAction::Modify && ($pair = $change->fieldChange($field)) !== null) {
            return $pair['to'];
        }

        return $item->getAttribute($field);
    }

    /**
     * Write $field := $value on $itemId — directly for this amendment's own additions, as a
     * modify change row for base (parent-plan) items. `short_name` (Titelnummer) is immutable
     * for base items (F2, OP#581): the numbering scheme is the parent plan's, and letting an
     * amendment silently renumber it would drift out of sync the moment the amendment applies or
     * is abandoned. The blade also renders the input readonly for base items — this is defense
     * in depth for direct component calls that bypass the UI.
     */
    private function setField(int $itemId, string $field, mixed $value): void
    {
        $item = BudgetItem::findOrFail($itemId);
        if ($item->budget_plan_id === $this->amendment_id) {
            $item->update([$field => $value]);

            return;
        }
        if ($field === 'short_name') {
            return;
        }
        $this->recordModify($item, $field, $value);
    }

    /**
     * Upsert a `modify` change row for $field on the base item $item. Storage form: `value` as
     * integer cents (Money-safe canonical form, matches AmendmentApplier), `position`/`parent_id`
     * as int, everything else as a string. When the new value equals the base (live) value, the
     * field is dropped again — and the whole row once it has no fields left, so a fully-undone
     * edit leaves no trace.
     */
    private function recordModify(BudgetItem $item, string $field, mixed $value): void
    {
        $live = $this->normalize($field, $item->getAttribute($field));
        $new = $this->normalize($field, $value);

        $change = BudgetItemChange::firstOrNew([
            'budget_plan_id' => $this->amendment_id,
            'budget_item_id' => $item->id,
        ]);
        if (! $change->exists) {
            $change->action = BudgetItemChangeAction::Modify;
        }
        if ($change->action !== BudgetItemChangeAction::Modify) {
            return; // e.g. marked for deletion — the UI disables field edits on that row
        }

        $changes = $change->diff ?? [];
        if ($new === $live) {
            unset($changes[$field]);
        } else {
            $changes[$field] = ['from' => $changes[$field]['from'] ?? $live, 'to' => $new];
        }

        if ($changes === []) {
            if ($change->exists) {
                $change->delete();
            }

            return;
        }
        $change->diff = $changes;
        $change->save();
    }

    private function normalize(string $field, mixed $value): mixed
    {
        if ($field === 'value') {
            // In practice MoneySynth already hydrates the wire value into a Money instance before
            // updatedItems() sees it, but defend against any other caller passing a raw
            // euro-decimal/formatted string (e.g. "300", "300,50", "1.500,00", "152,05 €") instead
            // of hand-rolling a cents cast — that would silently misread euros as cents.
            if ($value instanceof Money) {
                return (int) $value->getAmount();
            }

            return (int) (new DefaultMoneyFormater)->inverse((string) $value)->getAmount();
        }
        if ($field === 'position' || $field === 'parent_id') {
            return $value === null ? null : (int) $value;
        }

        return (string) ($value ?? '');
    }

    public function updatedItems(mixed $value, string $property): void
    {
        [$itemId, $prop] = explode('.', $property, 2);
        if (! in_array($prop, ['short_name', 'name', 'value'], true)) {
            return;
        }

        $this->setField((int) $itemId, $prop, $value);
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
        $this->loadItems();
    }

    public function updatedName(): void
    {
        $this->amendment()->update(['name' => $this->name ?: null]);
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    public function updatedJustification(): void
    {
        $this->amendment()->update(['justification' => $this->justification ?: null]);
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    /** @param  array<int, string>  $value */
    public function updatedReasonInputs(mixed $value, string $property): void
    {
        // Livewire's updated{Property} hook passes only the path AFTER the first dot, so for the
        // top-level array property "reasonInputs.{change_id}" $property IS just "{change_id}"
        // (no dot left to split on) — mirrors updatedItems()'s handling of "items.{id}.field".
        $changeId = $property;
        BudgetItemChange::whereKey($changeId)->where('budget_plan_id', $this->amendment_id)
            ->update(['reason' => $this->reasonInputs[$changeId] ?: null]);
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    /**
     * Reorder $item_id to $new_position among its siblings (same parent_id, spans base AND this
     * amendment's own additions). Mirrors ⚡plan-edit's shift-block algorithm, but every touched
     * base item's position is recorded as a modify change instead of written live.
     */
    public function sort($item_id, $new_position): void
    {
        $item_id = (int) $item_id;
        $new_position = (int) $new_position;
        $item = BudgetItem::findOrFail($item_id);
        $current = (int) $this->effectiveField($item, 'position');
        if ($current === $new_position) {
            return;
        }

        $siblings = $item->parent_id !== null
            ? BudgetItem::where('parent_id', $item->parent_id)->get()
            : BudgetItem::whereNull('parent_id')
                ->where('budget_type', $item->budget_type)
                ->whereIn('budget_plan_id', [$this->plan_id, $this->amendment_id])
                ->get();

        foreach ($siblings as $sibling) {
            if ($sibling->id === $item_id) {
                continue;
            }
            $pos = (int) $this->effectiveField($sibling, 'position');
            if ($current < $new_position && $pos > $current && $pos <= $new_position) {
                $this->setField($sibling->id, 'position', $pos - 1);
            } elseif ($current > $new_position && $pos >= $new_position && $pos < $current) {
                $this->setField($sibling->id, 'position', $pos + 1);
            }
        }
        $this->setField($item_id, 'position', $new_position);

        $this->loadItems();
    }

    public function addGroup(BudgetType $budget_type): void
    {
        $newItem = $this->addItem(null, true, $budget_type);
        if ($newItem instanceof BudgetItem) {
            $this->addBudget($newItem->id);
        }
    }

    public function addRootBudget(BudgetType $budget_type): void
    {
        $this->addItem(null, false, $budget_type);
    }

    public function addBudget(int $parent_id): void
    {
        $this->addItem($parent_id, false);
    }

    public function addSubGroup(int $parent_id): void
    {
        $this->addItem($parent_id, true);
    }

    private function addItem(?int $parent_id, bool $is_group, ?BudgetType $budget_type = null): ?BudgetItem
    {
        $parent = $parent_id !== null ? BudgetItem::findOrFail($parent_id) : null;

        $newDepth = $parent !== null ? $parent->nestingDepth() + 1 : 0;
        $maxForKind = $is_group ? BudgetItem::MAX_DEPTH - 1 : BudgetItem::MAX_DEPTH;
        if ($newDepth > $maxForKind) {
            return null; // guards the depth invariant server-side — the UI already hides the button here
        }

        $budget_type ??= $parent?->budget_type;
        $pos = $parent !== null
            ? (int) (BudgetItem::where('parent_id', $parent_id)->max('position') ?? -1)
            : (int) (BudgetItem::whereNull('parent_id')->where('budget_type', $budget_type)
                ->whereIn('budget_plan_id', [$this->plan_id, $this->amendment_id])->max('position') ?? -1);

        $newItem = BudgetItem::create([
            'budget_plan_id' => $this->amendment_id,
            'parent_id' => $parent_id,
            'budget_type' => $budget_type,
            'is_group' => $is_group,
            'position' => $pos + 1,
            'value' => Money::EUR(0),
        ]);
        $newItem->short_name = resolve(TitleNumberer::class)->next($newItem);
        $newItem->save();

        BudgetItemChange::create([
            'budget_plan_id' => $this->amendment_id,
            'budget_item_id' => $newItem->id,
            'action' => BudgetItemChangeAction::Add,
        ]);

        $this->loadItems();

        return $newItem;
    }

    /**
     * Delete $item_id: a base item without bookings gets a `delete` change row (the item itself
     * is untouched until apply — reversible via undoDelete()); an item this amendment itself
     * added is removed outright, together with its `add` change row.
     *
     * NOTE: must NOT be called `delete()`. Livewire's CSP-safe evaluator rewrites a
     * `wire:click="foo(1)"` expression to `$wire.foo(1)` and parses it with a hand-written
     * tokenizer that treats `delete` as a reserved KEYWORD, so `$wire.delete(1)` fails to parse
     * and the click is swallowed with only a console warning — no request at all.
     */
    public function deleteItem(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        if ($item->children()->count() > 0) {
            Flux::toast(__('budget-plan.edit.delete-has-children'), variant: 'danger');

            return;
        }

        if ($item->budget_plan_id === $this->amendment_id) {
            BudgetItemChange::where('budget_plan_id', $this->amendment_id)->where('budget_item_id', $item_id)->delete();
            $item->delete();
        } else {
            if ($item->hasBookings()) {
                Flux::toast(__('budget-plan.edit.has-bookings'), variant: 'danger');

                return;
            }
            BudgetItemChange::updateOrCreate(
                ['budget_plan_id' => $this->amendment_id, 'budget_item_id' => $item_id],
                ['action' => BudgetItemChangeAction::Delete, 'diff' => null],
            );
        }

        $this->loadItems();
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    /** Undo a pending deletion of a base item — simply drops its `delete` change row. */
    public function undoDelete(int $item_id): void
    {
        BudgetItemChange::where('budget_plan_id', $this->amendment_id)
            ->where('budget_item_id', $item_id)
            ->where('action', BudgetItemChangeAction::Delete)
            ->delete();

        $this->loadItems();
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }
};
