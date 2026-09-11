<?php

namespace App\Livewire\BudgetPlan;

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\TaxBudget;
use Cknow\Money\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The subtree-deletion confirmation shared by the two budget editors (OP#638): ⚡plan-edit, which
 * deletes for real, and ⚡amendment-edit, which drafts the deletion as change rows. $amendmentId
 * selects between the two policies — null means the plan editor.
 *
 * It is a component rather than a Blade partial for two reasons:
 *
 *  - Cost. Arming the dialog from the row menu used to be an action on the editor, so opening it
 *    re-rendered the whole editor behind it: measured at 6 queries on a 28-item plan, 5 of them
 *    spent rebuilding a tree nobody had changed. Here the row menu dispatches a browser event, so
 *    only this component renders and only the one subtree query runs. Opening the dialog to look
 *    and then backing out is the path this feature exists to serve, so that is the path to make
 *    cheap; the price is one extra round trip on an actual delete, when the editor has to be told
 *    to reload.
 *  - Duplication. confirmDelete()/cancelDelete()/subtree() were byte-identical in both editors,
 *    and only the write differed.
 *
 * The delete must stay in ONE request together with the re-sum of the ancestor chain, which is
 * why this component performs the write itself instead of handing it back to the editor: the
 * chain is read off the in-memory model of the row that was just deleted, and that knowledge does
 * not survive the response (see BudgetItem::reSumAncestorValues()).
 */
class DeleteSubtreeModal extends Component
{
    /** The plan being edited. */
    public int $planId;

    /** The amendment being drafted, when this sits in ⚡amendment-edit; null in the plan editor. */
    public ?int $amendmentId = null;

    /** The item whose deletion is being confirmed, or null while the dialog is disarmed. */
    public ?int $itemId = null;

    /**
     * Armed by the row menus via a plain browser event, so the click costs nothing on the editor,
     * and shown from here rather than client-side: the row menu would have to fire two events for
     * that, and the CSP Alpine build's parser has no sequence node to put two statements in one
     * attribute. Opening after the round trip is affordable precisely because this component
     * renders alone — one subtree query, no editor rebuild.
     */
    #[On('confirm-delete-item')]
    public function confirmDelete(int $itemId): void
    {
        $this->itemId = $itemId;

        Flux::modal('delete-item-modal')->show();
    }

    /** Wired to the modal's close event, so Abbrechen/Escape/backdrop disarm the server too. */
    public function cancelDelete(): void
    {
        $this->itemId = null;
    }

    public function render()
    {
        $rows = $this->subtree();
        $values = $rows instanceof Collection ? $this->values($rows) : [];

        return view('livewire.budget-plan.delete-subtree-modal', [
            'rows' => $rows,
            'values' => $values,
            // the leaves only, so a group is not counted on top of the children it rolls up
            'total' => $rows instanceof Collection ? $rows->reject->is_group->reduce(
                static fn (Money $carry, BudgetItem $item): Money => $carry->add($values[$item->id]),
                Money::EUR(0),
            ) : Money::EUR(0),
        ]);
    }

    /**
     * The armed item together with everything below it, in display order and annotated with the
     * accounting references that block a deletion — null while nothing is armed.
     *
     * ONE query does all of it. treeOf() seeds the recursive CTE at the armed item rather than
     * loading it first and walking descendantsAndSelf() off the instance, which would cost an
     * extra primary-key lookup; it is also the idiom BudgetPlan::budgetItemsTree() already uses.
     * The recursion follows parent_id and never filters by plan, which is what the amendment
     * editor needs: a base group comes back with the items the amendment hung below it, though
     * those live on the amendment's own plan row. `depth` arrives 0-based relative to the armed
     * item, so it drives the indent directly, and withCount folds both blocker counts into the
     * same statement instead of an EXISTS per row.
     *
     * The ordering is redone in PHP on purpose: `position_path` is positions joined with '.', so
     * ordering by it in SQL is a string sort that puts position 10 between 1 and 2. Comparing the
     * segments as an int array fixes that and still yields pre-order, because PHP orders a shorter
     * array before a longer one that starts with it — i.e. a parent before its children.
     *
     * @return Collection<int, BudgetItem>|null
     */
    private function subtree(): ?Collection
    {
        if ($this->itemId === null) {
            return null;
        }

        $itemId = $this->itemId;
        $rows = BudgetItem::treeOf(static fn (Builder $query) => $query->where('id', $itemId))
            ->withCount(['bookings', 'projectPosts'])
            ->get()
            ->sortBy(fn (BudgetItem $row): array => array_map(intval(...), explode('.', (string) $row->position_path)))
            ->values();

        // the item was deleted meanwhile (a second tab, a stale dialog)
        return $rows->isEmpty() ? null : $rows;
    }

    /**
     * Effective value per item id, resolved from the rows already in hand: the subtree holds every
     * descendant, so a group can be summed in memory instead of lazy-loading its children per
     * rendered row — the N+1 the blade's effectiveValue() calls used to cause. Mirrors the
     * computeValues() both editors use. A mount is the one case that still has to resolve through
     * the plan it references.
     *
     * @param  Collection<int, BudgetItem>  $rows
     * @return array<int, Money>
     */
    private function values(Collection $rows): array
    {
        $byParent = $rows->groupBy('parent_id');
        $map = [];

        $resolve = function (BudgetItem $item) use (&$resolve, &$map, $byParent): Money {
            if (isset($map[$item->id])) {
                return $map[$item->id];
            }
            if ($item->isMount()) {
                return $map[$item->id] = $item->effectiveValue();
            }
            if ($item->is_group) {
                $sum = Money::EUR(0);
                foreach ($byParent->get($item->id, collect()) as $child) {
                    $sum = $sum->add($resolve($child));
                }

                return $map[$item->id] = $sum;
            }

            return $map[$item->id] = $item->value ?? Money::EUR(0);
        };

        foreach ($rows as $row) {
            $resolve($row);
        }

        return $map;
    }

    /**
     * NOTE: must NOT be called `delete()`. Livewire's CSP-safe evaluator rewrites a
     * `wire:click="foo"` expression to `$wire.foo()` and parses it with a hand-written tokenizer
     * that treats `delete` as a reserved KEYWORD, so `$wire.delete()` fails to parse and the click
     * is swallowed with only a console warning — no request at all.
     */
    public function deleteItem(): void
    {
        $subtree = $this->subtree();
        if (! $subtree instanceof Collection) {
            $this->itemId = null;

            return;
        }

        // the whole subtree has to be clear, not just the item that was clicked: only leaves are
        // bookable, so a group's bookings always sit below it, where hasBookings() never looked
        if ($subtree->contains(fn (BudgetItem $row): bool => $row->blocksDeletion())) {
            Flux::toast(__('budget-plan.edit.delete-blocked'), variant: 'danger');

            return;
        }

        $this->amendmentId === null
            ? $this->purge($subtree)
            : $this->draftDeletions($subtree);

        $this->itemId = null;
        Flux::modal('delete-item-modal')->close();
        // trans_choice, not __(): the key is pluralized, and __() hands back the raw "{1} …|[2,*] …" source string
        $count = $subtree->count();
        Flux::toast(trans_choice('budget-plan.edit.deleted', $count, ['count' => $count]), variant: 'success');

        // the editor owns the tree and the ItemForm array bound to it, so it has to reload
        $this->dispatch('budget-subtree-deleted');
    }

    /**
     * Plan editor: the rows really go. Deepest first, because budget_item.parent_id is a RESTRICT
     * foreign key, and the two other RESTRICT references onto them clear first — the tax_budget
     * row of a VAT title, and any change row an amendment drafted against them.
     *
     * @param  Collection<int, BudgetItem>  $subtree
     */
    private function purge(Collection $subtree): void
    {
        $item = $subtree->firstOrFail();
        $ids = $subtree->pluck('id')->all();

        DB::transaction(static function () use ($subtree, $ids): void {
            TaxBudget::whereIn('budget_id', $ids)->delete();
            BudgetItemChange::whereIn('budget_item_id', $ids)->delete();
            $subtree->sortByDesc('depth')->each(static fn (BudgetItem $row) => $row->delete());
        });

        // both read the parent chain off the in-memory model, which outlives the deleted row —
        // and therefore have to happen here, in the same request as the delete
        $item->normalizeSiblingPositions();
        $item->reSumAncestorValues();
    }

    /**
     * Amendment editor: one `delete` change row per base item in the branch, rather than a single
     * collective change — the change set stays a flat list of per-item changes, so
     * AmendmentApplier, the revert path and the diff view need no special case, and a single title
     * can still be undone out of the branch.
     *
     * Items the amendment itself added never reached the plan, so they are dropped outright.
     * Restricting the purge to them stays FK-safe because they are always the LOWER end of the
     * subtree: a base item can never hang below an item the base plan does not have yet.
     *
     * No ancestor re-sum here: base rows are untouched by design (that is the overlay), and the
     * amendment editor sums a group's children live rather than reading the stored value.
     *
     * @param  Collection<int, BudgetItem>  $subtree
     */
    private function draftDeletions(Collection $subtree): void
    {
        $item = $subtree->firstOrFail();
        $isOwnAddition = fn (BudgetItem $row): bool => $row->budget_plan_id === $this->amendmentId;
        [$ownAdditions, $baseItems] = $subtree->partition($isOwnAddition);

        DB::transaction(function () use ($ownAdditions, $baseItems): void {
            foreach ($baseItems as $baseItem) {
                BudgetItemChange::updateOrCreate(
                    ['budget_plan_id' => $this->amendmentId, 'budget_item_id' => $baseItem->id],
                    ['action' => BudgetItemChangeAction::Delete, 'diff' => null],
                );
            }

            $ownIds = $ownAdditions->pluck('id')->all();
            if ($ownIds !== []) {
                TaxBudget::whereIn('budget_id', $ownIds)->delete();
                BudgetItemChange::whereIn('budget_item_id', $ownIds)->delete();
                $ownAdditions->sortByDesc('depth')->each(static fn (BudgetItem $row) => $row->delete());
            }
        });

        if ($isOwnAddition($item)) {
            $item->normalizeSiblingPositions([$this->planId, $this->amendmentId]);
        }
    }
}
