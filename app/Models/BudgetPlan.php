<?php

namespace App\Models;

use App\Models\Enums\BudgetPlanState;
use Carbon\Carbon;
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
 * @method static \Database\Factories\BudgetPlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetPlan query()
 * @mixin \Eloquent
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
    protected $fillable = ['start_date', 'end_date', 'resolution_date', 'approval_date', 'state', 'parent_plan'];

    protected $casts = [
        'state' => BudgetPlanState::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'resolution_date' => 'date',
        'approval_date' => 'date',

    ];

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
}
