<?php

namespace App\Models;

use App\Models\Enums\BudgetPlanState;
use App\Models\Enums\BudgetType;
use Carbon\Carbon;
use Database\Factories\BudgetPlanFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BudgetPlan
 *
 * @property int $id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon $resolution_date
 * @property Carbon $approval_date
 * @property BudgetPlanState $state
 * @property BudgetPlan $parentPlan
 * @property BudgetItem[] $budgetItems
 * @property int|null $parent_plan_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int|null $budget_items_count
 *
 * @method static BudgetPlanFactory factory($count = null, $state = [])
 * @method static Builder|BudgetPlan newModelQuery()
 * @method static Builder|BudgetPlan newQuery()
 * @method static Builder|BudgetPlan query()
 *
 * @mixin Eloquent
 */
class BudgetPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_plan';

    /**
     * @var array
     */
    protected $fillable = ['organization', 'fiscal_year_id', 'resolution_date', 'approval_date', 'state', 'parent_plan'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'state' => BudgetPlanState::class,
            'resolution_date' => 'date',
            'approval_date' => 'date',
        ];
    }

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasMany{
        return $this->hasMany(BudgetItem::class);
    }
    public function budgetItemsTree(BudgetType $budgetType)
    {
        // $this is not accessible from the closure scope
        $plan_id = $this->id;

        $constraint =  static fn($query) =>
            $query->whereNull('parent_id')
                ->where('budget_plan_id', $plan_id)
                ->where('budget_type', $budgetType);
        // the full tree flattened out, the position path is a custom-built path
        return BudgetItem::treeOf($constraint)->orderBy('position_path')->get();
    }

    public function rootBudgetItems(): Builder|\Illuminate\Database\Eloquent\Relations\HasMany|BudgetPlan
    {
        return $this->hasMany(BudgetItem::class)->whereNull('parent_id');
    }

    public function fiscalYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Resets the position values of all children to be sequential starting from 0
     * Use in case of buggyness in the position values
     * @return void
     */
    public function normalizePositions() : void
    {
        $items = $this->rootBudgetItems()->get();
        while ($items->isNotEmpty()) {
            $item = $items->pop();
            $item->normalizeChildPositionValues();
            $items = $items->merge($item->children);
        }
    }
}
