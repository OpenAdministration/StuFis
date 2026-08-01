<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\BudgetItemChange
 *
 * One delta row of a Nachtragshaushaltsplan (amendment) against a single budget_item, keyed by
 * (budget_plan_id, budget_item_id) — see the Architecture section of OP#581 for the full change-set
 * design. `action` is one of:
 *
 *  - modify: the item already existed on the parent plan; `changes` holds
 *            {field: {"from": ..., "to": ...}} for every touched field.
 *  - add:    `budget_item_id` points at a real BudgetItem row created under the amendment plan;
 *            `changes` is typically empty (the item itself carries the new data).
 *  - delete: `budget_item_id` points at a live item slated for removal (only allowed when it has
 *            no bookings); `changes` is typically empty.
 *
 * @property int $id
 * @property int $budget_plan_id
 * @property int $budget_item_id
 * @property string $action
 * @property array<string, array{from: mixed, to: mixed}>|null $changes
 * @property string|null $reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read BudgetPlan $amendmentPlan
 * @property-read BudgetItem $budgetItem
 */
class BudgetItemChange extends Model
{
    public const string ACTION_MODIFY = 'modify';

    public const string ACTION_ADD = 'add';

    public const string ACTION_DELETE = 'delete';

    protected $table = 'budget_item_change';

    protected $fillable = ['budget_plan_id', 'budget_item_id', 'action', 'changes', 'reason'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'changes' => 'array',
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
     * NOTE: this reads via `getAttribute('changes')` rather than `$this->changes` — Eloquent's
     * own HasAttributes trait already declares a `protected $changes` property for its dirty-
     * tracking bookkeeping, which shadows our `changes` JSON column when accessed from INSIDE the
     * model class (magic `__get()` only kicks in for external access, so `$this->changes` here
     * would silently read Eloquent's internal array instead of our cast attribute).
     *
     * @return array{from: mixed, to: mixed}|null
     */
    public function fieldChange(string $field): ?array
    {
        return $this->getAttribute('changes')[$field] ?? null;
    }

    /** Whether this row currently touches any field at all (an empty `changes` should be pruned). */
    public function isEmpty(): bool
    {
        return $this->action === self::ACTION_MODIFY && blank($this->getAttribute('changes'));
    }
}
