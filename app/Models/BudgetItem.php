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
 *
 * @property int $id
 * @property int $budget_plan_id
 * @property string|null $short_name
 * @property string|null $name
 * @property \Cknow\Money\Money $value
 * @property \App\Models\Enums\BudgetType $budget_type
 * @property bool $is_group
 * @property string $description
 * @property int|null $position
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $children
 * @property-read int|null $children_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, BudgetItem> $orderedChildren
 * @property-read int|null $ordered_children_count
 * @property-read \App\Models\BudgetItem|null $parent
 * @property-read int $depth
 * @property-read string $path
 * @property-read string $position_path
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $ancestors The model's recursive parents.
 * @property-read int|null $ancestors_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $ancestorsAndSelf The model's recursive parents and itself.
 * @property-read int|null $ancestors_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $bloodline The model's ancestors, descendants and itself.
 * @property-read int|null $bloodline_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $childrenAndSelf The model's direct children and itself.
 * @property-read int|null $children_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $descendants The model's recursive children.
 * @property-read int|null $descendants_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $descendantsAndSelf The model's recursive children and itself.
 * @property-read int|null $descendants_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $parentAndSelf The model's direct parent and itself.
 * @property-read int|null $parent_and_self_count
 * @property-read \App\Models\BudgetItem|null $rootAncestor The model's topmost parent.
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $siblings The parent's other children.
 * @property-read int|null $siblings_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\BudgetItem> $siblingsAndSelf All the parent's children.
 * @property-read int|null $siblings_and_self_count
 *
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, static> all($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem breadthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem depthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem doesntHaveChildren()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, static> get($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem getExpressionGrammar()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem hasChildren()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem hasParent()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem isLeaf()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem isRoot()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem tree($maxDepth = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem treeOf(\Illuminate\Database\Eloquent\Model|callable $constraint, $maxDepth = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereBudgetPlanId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereBudgetType($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereCreatedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereDepth($operator, $value = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereDescription($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereIsGroup($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereName($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereParentId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem wherePosition($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereShortName($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereUpdatedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem whereValue($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem withGlobalScopes(array $scopes)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem withRelationshipExpression($direction, callable $constraint, $initialDepth, $from = null, $maxDepth = null)
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
     *
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
            ->each(function ($child) use (&$idx): void {
                $child->update(['position' => $idx++]);
            });
    }
}
