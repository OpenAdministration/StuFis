<?php

namespace App\Models;

use App\Models\Enums\BudgetType;
use Cknow\Money\Casts\MoneyDecimalCast;
use Database\Factories\BudgetItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

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
    use HasRecursiveRelationships;

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


    public function bookings(): HasMany
    {
        return $this->hasMany('tbd', 'titel_id');
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function orderedChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position', 'asc');
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'budget_type' => BudgetType::class,
            'value' => MoneyDecimalCast::class,
        ];
    }

    /**
     * Get the custom paths for the model.
     * @see https://github.com/staudenmeir/laravel-adjacency-list#custom-paths
     * Usable to sort the whole tree by position
     */
    public function getCustomPaths(): array
    {
        return [
            [
                'name' => 'position_path',
                'column' => 'position',
                'separator' => '.',
            ],
        ];
    }

    public function normalizeChildPositionValues(): void
    {
        $idx = 0;
        $this->orderedChildren()
            ->each(function($child) use (&$idx) {
                $child->update(['position' => $idx++]);
            });
    }


}
