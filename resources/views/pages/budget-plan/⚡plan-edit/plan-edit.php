<?php

use App\Livewire\BudgetPlan\ItemForm;
use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\Models\Setting;
use App\Models\TaxBudget;
use App\Support\Budget\TitleNumberer;
use Cknow\Money\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    public $organization;

    public $fiscal_year_id;

    #[Url(as: 'plan_id')]
    public $plan_id;

    public $resolution_date;

    public $approval_date;

    public $refresh = false;

    /** Mount picker state: the item being transformed into a mount, and the plan it references. */
    public $mount_item_id = null;

    public $mount_plan_id = null;

    /** @var list<array{id:int, label:string}> candidate plans, loaded only when the picker opens */
    public $mount_candidates = [];

    /**
     * @var array an array which holds Livewire ItemForm objects.
     *            $items[]
     */
    public $items;

    public function mount(int $plan_id): void
    {
        $plan = BudgetPlan::findOrFail($plan_id);
        $this->authorize('update', $plan);

        $this->organization = $plan->organization;
        $this->fiscal_year_id = $plan->fiscal_year_id;
        $this->resolution_date = $plan->resolution_date;
        $this->approval_date = $plan->approval_date;

        $this->loadItems();
    }

    /**
     * (Re)build the array of Livewire ItemForms for every item of the plan.
     * We don't keep the models as public properties, so each item is wrapped in
     * a Form keyed by its id (matching the `items.{id}` bindings in the view).
     */
    private function loadItems(): void
    {
        $this->items = [];

        foreach ($this->query()->without('children')->get() as $item) {
            $form = new ItemForm($this, 'items.'.$item->id);
            $form->setItem($item);
            $this->items[$item->id] = $form;
        }
    }

    public function query(BudgetType|int|null $budget_type = null): Builder
    {
        $query = BudgetItem::with('children')
            ->where('budget_plan_id', $this->plan_id);
        if ($budget_type) {
            $query = $query->where('budget_type', $budget_type);
        }

        return $query->orderBy('position');
    }

    public function with(): array
    {
        $fiscal_years = FiscalYear::all();

        // Load the whole plan in ONE query and assemble the tree in memory, so the recursive
        // blade walks pre-loaded relations instead of lazy-loading `orderedChildren` per node.
        $all = BudgetItem::where('budget_plan_id', $this->plan_id)
            ->orderBy('position')
            ->get();

        // attach each item's children as the `orderedChildren` relation the view recurses on
        $byParent = $all->groupBy('parent_id');
        foreach ($all as $item) {
            $item->setRelation('orderedChildren', $byParent->get($item->id, collect()));
        }

        // preload referenced plans for any mount items (one query for all of them)
        $refIds = $all->pluck('referenced_plan_id')->filter()->unique();
        $refPlans = $refIds->isNotEmpty()
            ? BudgetPlan::whereIn('id', $refIds)->get()->keyBy('id')
            : collect();
        foreach ($all as $item) {
            if ($item->referenced_plan_id !== null) {
                $item->setRelation('referencedPlan', $refPlans->get($item->referenced_plan_id));
            }
        }

        // compute every item's effective value once, bottom-up (memoized), instead of
        // re-summing each subtree at every level during render
        $values = $this->computeValues($all);

        $roots = $all->whereNull('parent_id');
        $rootsFor = fn (BudgetType $type) => $roots
            ->filter(fn (BudgetItem $i): bool => $i->budget_type === $type)
            ->values();

        return [
            'fiscal_years' => $fiscal_years,
            'values' => $values,
            'root_items' => [
                'in' => $rootsFor(BudgetType::INCOME),
                'out' => $rootsFor(BudgetType::EXPENSE),
            ],
            'in_total' => $this->sumRoots($rootsFor(BudgetType::INCOME), $values),
            'out_total' => $this->sumRoots($rootsFor(BudgetType::EXPENSE), $values),
        ];
    }

    /**
     * Compute the effective value of every item once, memoized into an [id => Money] map.
     * Groups sum their (already-loaded) children; mounts resolve through the referenced plan
     * (the only branch that still touches the DB, and only for the rare mount item).
     *
     * @param  Collection<int, BudgetItem>  $all  all items, with orderedChildren set
     * @return array<int, Money>
     */
    private function computeValues($all): array
    {
        $map = [];
        $resolve = function (BudgetItem $item) use (&$resolve, &$map): Money {
            if (isset($map[$item->id])) {
                return $map[$item->id];
            }
            if ($item->isMount()) {
                return $map[$item->id] = $item->effectiveValue();
            }
            if ($item->is_group) {
                $sum = Money::EUR(0);
                foreach ($item->orderedChildren as $child) {
                    $sum = $sum->add($resolve($child));
                }

                return $map[$item->id] = $sum;
            }

            return $map[$item->id] = $item->value ?? Money::EUR(0);
        };
        foreach ($all as $item) {
            $resolve($item);
        }

        return $map;
    }

    /**
     * @param  Collection<int, BudgetItem>  $roots
     * @param  array<int, Money>  $values
     */
    private function sumRoots($roots, array $values): Money
    {
        $sum = Money::EUR(0);
        foreach ($roots as $root) {
            $sum = $sum->add($values[$root->id]);
        }

        return $sum;
    }

    /**
     * Handle the updated event for an item's property.
     * This method processes changes to item properties and updates the corresponding record in the database.
     * If the updated property is `value`, it triggers a recalculation of the item's and its parents values.
     * After the update, the method refreshes the component state.
     *
     * @param  mixed  $value  The new value for the item's property.
     * @param  string  $property  The property identifier in the format "item_id.property_name".
     */
    public function updatedItems(mixed $value, string $property): void
    {
        [$item_id, $item_prop] = explode('.', $property, 2);
        if (in_array($item_prop, ['short_name', 'name', 'value'])) {
            $item = BudgetItem::findOrFail($item_id);
            $item->update([$item_prop => $value]);
            if ($item_prop === 'value') {
                $this->reSumItemValues($item);
            }
            Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
            $this->refresh();
        }
    }

    /**
     * Recalculate and update the values of parent budget items by summing the values of their child items.
     * This method propagates updates upwards through the hierarchy of budget items, starting from a given leaf item.
     * Each parent's value is recalculated based on the sum of its direct children's values, and the changes are saved to the database.
     *
     * @param  BudgetItem  $leafItem  The leaf budget item from which the upward recalculation begins.
     */
    public function reSumItemValues(BudgetItem $leafItem): void
    {
        $item = $leafItem;
        // iterate upwards until there is no parent left
        while (($item = $item->parent) !== null) {
            $amount = $item->children()->sum('value');
            $money = Money::EUR($amount, true);
            // update db model
            $item->value = $money;
            $item->save();
            // update frontend
            $this->items[$item->id]->value = $money;
        }
    }

    /**
     * Handle the updated event for the specified property.
     * This method is called whenever a property is updated.
     * It updates the corresponding property in the model and saves the changes.
     * Only the meta-data directly in the BudgetPlan Model is updated here.
     *
     * @param  string  $property  The property name that has been updated.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['organization', 'fiscal_year_id', 'resolution_date', 'approval_date'])) {
            // empty optional fields come back as '' (e.g. cleared fiscal-year listbox);
            // store them as null so nullable columns / FKs don't reject the empty string
            $value = $this->$property === '' ? null : $this->$property;
            $plan = BudgetPlan::findOrFail($this->plan_id);
            $plan->update([
                $property => $value,
            ]);
            Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
        }
    }

    public function sort($item_id, $new_position): void
    {
        $item = BudgetItem::findOrFail($item_id);

        $current_position = $item->position;

        if ($current_position === $new_position) {
            return;
        }

        // pickup all items between old and new position
        $block = $item->siblings()->whereBetween('position', [
            min($current_position, $new_position),
            max($current_position, $new_position),
        ]);

        DB::transaction(static function () use ($block, $item, $current_position, $new_position): void {
            if ($current_position < $new_position) {
                // if item is shifted down then shift everything up
                $block->decrement('position');
            } else {
                $block->increment('position');
            }

            $item->update(['position' => $new_position]);

        });

        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    public function createFiscalYear(): void
    {
        $this->redirect(route('fiscal-year.create'), navigate: true);
    }

    public function save()
    {
        // check if saveable
        // $this->validate();

        $plan = BudgetPlan::findOrFail($this->plan_id);
        // empty optional fields come back as ''; store them as null so nullable columns don't reject the empty string
        $plan->update([
            'resolution_date' => $this->resolution_date ?: null,
            'approval_date' => $this->approval_date ?: null,
            'organization' => $this->organization ?: null,
        ]);

        $this->redirect(route('budget-plan.view', $this->plan_id));
    }

    /**
     * Add the Umsatzsteuer (VAT) group and one title per configured tax rate to this plan, then
     * refresh the tree. Idempotent — re-running only adds what is missing. Gated by the global
     * tax.active setting and the plan-update policy.
     */
    public function addTaxTitles(): void
    {
        $plan = BudgetPlan::findOrFail($this->plan_id);
        $this->authorize('update', $plan);

        if (! Setting::get('tax.active', false)) {
            Flux::toast(__('budget-plan.edit.tax-inactive'), variant: 'warning');

            return;
        }

        $added = TaxBudget::addToPlan($this->plan_id);

        $this->loadItems();
        $this->refresh();

        Flux::toast(
            $added > 0
                ? __('budget-plan.edit.tax-added', ['count' => $added])
                : __('budget-plan.edit.tax-exists'),
            variant: $added > 0 ? 'success' : 'warning',
        );
    }

    public function addGroup(BudgetType $budget_type): void
    {
        $newPos = $this->query($budget_type)->whereNull('parent_id')->max('position') + 1;
        $new_item = BudgetItem::create([
            'parent_id' => null,
            'budget_plan_id' => $this->plan_id,
            'budget_type' => $budget_type,
            'is_group' => true,
            'position' => $newPos,
            // group value is derived from its children (sum); starts at 0 until the
            // child budget below is added — keeps the group == sum(children) invariant
            'value' => Money::EUR(0),
        ]);
        $this->autoNumber($new_item);
        $form = new ItemForm($this, 'items.'.$new_item->id);
        $form->setItem($new_item);
        $this->items[$new_item->id] = $form;

        $this->addBudget($new_item->id);
    }

    /** Add a plain budget line (leaf) at root level — no surrounding group. */
    public function addRootBudget(BudgetType $budget_type): void
    {
        $this->addItem(null, false, budget_type: $budget_type);
    }

    public function addBudget(int $parent_id, float $value = 0.0): void
    {
        $this->addItem($parent_id, false, $value);
    }

    public function addSubGroup(int $parent_id): void
    {
        $this->addItem($parent_id, true);
    }

    private function addItem(?int $parent_id, bool $is_group, $value = 0.0, ?BudgetType $budget_type = null): void
    {
        $parent = $parent_id !== null ? BudgetItem::findOrFail($parent_id) : null;
        if ($parent !== null && $parent->is_group === 0) {
            return;
        }

        // root items take their type from the caller; nested items inherit it from the parent
        $budget_type = $parent?->budget_type ?? $budget_type;
        $pos = $parent !== null
            ? $parent->children()->max('position')
            : $this->query($budget_type)->whereNull('parent_id')->max('position');

        $new_item = BudgetItem::create([
            'parent_id' => $parent_id,
            'budget_plan_id' => $parent?->budget_plan_id ?? $this->plan_id,
            'budget_type' => $budget_type,
            'is_group' => $is_group,
            'position' => $pos + 1,
            'value' => Money::EUR($value, true),
        ]);
        $this->autoNumber($new_item);
        $form = new ItemForm($this, 'items.'.$new_item->id);
        $form->setItem($new_item);
        $this->items[$new_item->id] = $form;
        $this->refresh();
    }

    /** Fill the new item's Titelnummer (short_name) from the surrounding numbering. */
    private function autoNumber(BudgetItem $item): void
    {
        $item->short_name = resolve(TitleNumberer::class)->next($item);
        $item->save();
    }

    public function convertToGroup(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        if ($item->is_group) {
            return;
        }
        if ($item->hasBookings()) {
            Flux::toast(__('budget-plan.edit.has-bookings'), variant: 'danger');

            return;
        }
        $item->update(['is_group' => true]);
        $this->addBudget($item->id, $item->value->getAmount() / 100);
    }

    public function convertToBudget(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);

        // un-mount: a mount becomes a plain budget line again
        if ($item->isMount()) {
            $item->update(['referenced_plan_id' => null]);
            $this->refresh();

            return;
        }
        if (! $item->is_group) {
            return;
        }
        if ($item->children()->count() === 0) {
            $item->update(['is_group' => false]);
        }
    }

    /** Open the picker to turn a childless item into a mount of another plan. */
    public function openMountPicker(int $item_id): void
    {
        $this->mount_item_id = $item_id;
        $this->mount_plan_id = null;

        $fiscalYearId = $this->fiscal_year_id ?: null;

        // candidates: other plans in the SAME fiscal year (null matches null) that wouldn't
        // create a reference cycle. Computed here (only on open) rather than in with(), which
        // runs on every edit-page render.
        $this->mount_candidates = BudgetPlan::where('id', '!=', $this->plan_id)
            ->when(
                $fiscalYearId === null,
                fn ($query) => $query->whereNull('fiscal_year_id'),
                fn ($query) => $query->where('fiscal_year_id', $fiscalYearId),
            )
            ->get()
            ->reject(fn (BudgetPlan $candidate): bool => $candidate->reachesPlan((int) $this->plan_id))
            ->map(fn (BudgetPlan $candidate): array => ['id' => $candidate->id, 'label' => $candidate->label()])
            ->values()
            ->all();

        Flux::modal('mount-plan')->show();
    }

    public function convertToMount(): void
    {
        $item = BudgetItem::findOrFail($this->mount_item_id);

        // a mount has no children of its own; otherwise it can sit anywhere in the tree
        if ($item->children()->count() > 0) {
            return;
        }
        if ($item->hasBookings()) {
            Flux::toast(__('budget-plan.edit.has-bookings'), variant: 'danger');

            return;
        }

        $referenced = BudgetPlan::find($this->mount_plan_id);
        if ($referenced === null || $referenced->reachesPlan((int) $this->plan_id)) {
            Flux::toast(__('budget-plan.edit.mount-cycle'), variant: 'danger');

            return;
        }

        $item->update(['referenced_plan_id' => $referenced->id, 'is_group' => false]);

        $this->mount_item_id = null;
        $this->mount_plan_id = null;
        Flux::modal('mount-plan')->close();
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
        $this->refresh();
    }

    public function copyItem(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        $this->copyTree($item, $item->parent_id, $item->budget_type, copyValues: true, nameSuffix: ' - '.__('budget-plan.edit.copy-suffix'));
        $this->loadItems();
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    /**
     * Mirror a root item (and its subtree) to the opposite budget side, with all
     * values reset to 0. Only roots can be mirrored.
     */
    public function copyInverse(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        if ($item->parent_id !== null) {
            return;
        }
        $this->copyTree($item, null, $item->budget_type->opposite(), copyValues: false);
        $this->loadItems();
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
    }

    /**
     * Deep-copy an item and its descendants. $budgetType overrides the type (for the
     * inverse copy); when $copyValues is false every value starts at 0. The new item's
     * Titelnummer is auto-generated, and it is appended after its target siblings.
     */
    private function copyTree(BudgetItem $item, ?int $parent_id, BudgetType $budgetType, bool $copyValues, string $nameSuffix = ''): void
    {
        $newItem = BudgetItem::create([
            'budget_plan_id' => $item->budget_plan_id,
            'budget_type' => $budgetType,
            'is_group' => $item->is_group,
            'parent_id' => $parent_id,
            'value' => $copyValues ? $item->value : Money::EUR(0),
            'position' => $this->nextPosition($item->budget_plan_id, $parent_id, $budgetType),
            'name' => $item->name.$nameSuffix,
        ]);
        $this->autoNumber($newItem);

        foreach ($item->orderedChildren as $child) {
            $this->copyTree($child, $newItem->id, $budgetType, $copyValues);
        }
    }

    /** Position after the last of the target siblings (a parent's children, or the roots of a type). */
    private function nextPosition(int $plan_id, ?int $parent_id, BudgetType $budgetType): int
    {
        $query = BudgetItem::query()->where('budget_plan_id', $plan_id);
        $parent_id !== null
            ? $query->where('parent_id', $parent_id)
            : $query->whereNull('parent_id')->where('budget_type', $budgetType);

        return (int) $query->max('position') + 1;
    }

    public function delete(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);

        if ($item->children()->count() > 0) {
            Flux::toast(__('budget-plan.edit.delete-has-children'), variant: 'danger');

            return;
        }
        if ($item->hasBookings()) {
            Flux::toast(__('budget-plan.edit.has-bookings'), variant: 'danger');

            return;
        }
        DB::transaction(function () use ($item): void {
            // a tax title is referenced by a tax_budget row (budget_id FK, RESTRICT);
            // drop it first so the item delete doesn't hit the constraint
            TaxBudget::where('budget_id', $item->id)->delete();
            $item->delete();
        });
        $this->reSumItemValues($item);
    }

    public function refresh(): void
    {
        $this->refresh = ! $this->refresh;
    }
};
