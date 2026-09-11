<?php

namespace App\Models;

use App\Models\Enums\BudgetItemChangeAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\BudgetItemChange
 *
 * One delta row of an amendment against a single budget_item, keyed by (budget_plan_id,
 * budget_item_id) — see the Architecture section of OP#581 for the full change-set design.
 * `action` is one of:
 *
 *  - modify: the item already existed on the parent plan; `diff` holds
 *            {field: {"from": ..., "to": ...}} for every touched field.
 *  - add:    `budget_item_id` points at a real BudgetItem row created under the amendment plan;
 *            `diff` is typically empty (the item itself carries the new data).
 *  - delete: `budget_item_id` points at a live item slated for removal (only allowed when it has
 *            no bookings); `diff` is typically empty.
 *
 * The column is named `diff`, not `changes`: Eloquent's own HasAttributes trait already declares a
 * `protected $changes` property for its dirty-tracking bookkeeping, and a `changes` column silently
 * shadows it when read from INSIDE the model (magic `__get()` only kicks in for external access, so
 * `$this->changes` there would read Eloquent's internal array instead of the cast attribute). This
 * already caused a production bug once; renaming the column removes the trap entirely instead of
 * routing around it.
 *
 * @property int $id
 * @property int $budget_plan_id
 * @property int $budget_item_id
 * @property BudgetItemChangeAction $action
 * @property array<string, array{from: mixed, to: mixed}>|null $diff
 * @property string|null $reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read BudgetPlan $amendmentPlan
 * @property-read BudgetItem $budgetItem
 */
class BudgetItemChange extends Model
{
    protected $table = 'budget_item_change';

    protected $fillable = ['budget_plan_id', 'budget_item_id', 'action', 'diff', 'reason'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'action' => BudgetItemChangeAction::class,
            'diff' => 'array',
        ];
    }

    public function amendmentPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'budget_item_id');
    }

    /**
     * The {from, to} pair recorded for a single field, or null when this change row doesn't
     * (or no longer) touches that field.
     *
     * @return array{from: mixed, to: mixed}|null
     */
    public function fieldChange(string $field): ?array
    {
        return $this->diff[$field] ?? null;
    }

    /** Whether this row currently touches any field at all (an empty `diff` should be pruned). */
    public function isEmpty(): bool
    {
        return $this->action === BudgetItemChangeAction::Modify && blank($this->diff);
    }
}
