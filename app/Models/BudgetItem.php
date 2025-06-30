<?php

namespace App\Models;

use App\Models\Enums\BudgetType;
use Database\Factories\BudgetItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\BudgetItem
 *
 * @property-read BudgetPlan|null $budgetPlan
 *
 * @method static BudgetItemFactory factory($count = null, $state = [])
 * @method static Builder|BudgetItem newModelQuery()
 * @method static Builder|BudgetItem newQuery()
 * @method static Builder|BudgetItem query()
 *
 * @mixin Eloquent
 */
class BudgetItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_item';

    /**
     * @var array
     */
    protected $fillable = ['budget_plan_id', 'short_name', 'name', 'value', 'budget_type', 'description', 'parent_id', 'is_group', 'position'];

    protected $casts = [
        'is_group' => 'boolean',
        'budget_type' => BudgetType::class,
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany('tbd', 'titel_id');
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function orderedChildren(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id')->orderBy('position', 'asc');
    }
}
