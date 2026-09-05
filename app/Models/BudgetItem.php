<?php

namespace App\Models;

use App\Models\Enums\BudgetItemKind;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\Booking;
use Cknow\Money\Casts\MoneyDecimalCast;
use Cknow\Money\Money;
use Database\Factories\BudgetItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;
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
 * @property Money $value
 * @property BudgetType $budget_type
 * @property bool $is_group
 * @property string $description
 * @property int|null $position
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, BudgetItem> $children
 * @property-read int|null $children_count
 * @property-read Collection<int, BudgetItem> $orderedChildren
 * @property-read int|null $ordered_children_count
 * @property-read BudgetItem|null $parent
 * @property-read int $depth
 * @property-read string $path
 * @property-read string $position_path
 * @property-read Collection<int, BudgetItem> $ancestors The model's recursive parents.
 * @property-read int|null $ancestors_count
 * @property-read Collection<int, BudgetItem> $ancestorsAndSelf The model's recursive parents and itself.
 * @property-read int|null $ancestors_and_self_count
 * @property-read Collection<int, BudgetItem> $bloodline The model's ancestors, descendants and itself.
 * @property-read int|null $bloodline_count
 * @property-read Collection<int, BudgetItem> $childrenAndSelf The model's direct children and itself.
 * @property-read int|null $children_and_self_count
 * @property-read Collection<int, BudgetItem> $descendants The model's recursive children.
 * @property-read int|null $descendants_count
 * @property-read Collection<int, BudgetItem> $descendantsAndSelf The model's recursive children and itself.
 * @property-read int|null $descendants_and_self_count
 * @property-read Collection<int, BudgetItem> $parentAndSelf The model's direct parent and itself.
 * @property-read int|null $parent_and_self_count
 * @property-read BudgetItem|null $rootAncestor The model's topmost parent.
 * @property-read Collection<int, BudgetItem> $siblings The parent's other children.
 * @property-read int|null $siblings_count
 * @property-read Collection<int, BudgetItem> $siblingsAndSelf All the parent's children.
 * @property-read int|null $siblings_and_self_count
 *
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem breadthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem depthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem doesntHaveChildren()
 * @method static Collection<int, static> get($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem getExpressionGrammar()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem hasChildren()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem hasParent()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem isLeaf()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem isRoot()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem tree($maxDepth = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|BudgetItem treeOf(Model|callable $constraint, $maxDepth = null)
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
    protected $fillable = ['budget_plan_id', 'short_name', 'name', 'value', 'budget_type', 'description', 'parent_id', 'is_group', 'position', 'referenced_plan_id'];

    /**
     * Bookings recorded against this item. Wired by titel_id == budget_item.id, which holds
     * for converted leaf items because the conversion preserves their legacy ids. Only
     * bookable (leaf) items ever carry bookings — see isBookable().
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'titel_id');
    }

    public function hasBookings(): bool
    {
        return $this->bookings()->exists();
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    /**
     * The change rows amendments have drafted against this item — several amendments may touch
     * the same item at once. An `add` row only surfaces here once its amendment was applied and
     * the new item got rehomed onto this plan.
     */
    public function amendmentChanges(): HasMany
    {
        return $this->hasMany(BudgetItemChange::class, 'budget_item_id');
    }

    /** The plan this item "mounts" (only set for mount items). */
    public function referencedPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'referenced_plan_id');
    }

    /** Derived discriminator (mount > group > budget) until/unless we add a physical column. */
    public function kind(): BudgetItemKind
    {
        if ($this->referenced_plan_id !== null) {
            return BudgetItemKind::Mount;
        }

        return $this->is_group ? BudgetItemKind::Group : BudgetItemKind::Budget;
    }

    public function isMount(): bool
    {
        return $this->referenced_plan_id !== null;
    }

    /**
     * Deepest allowed nesting level (0-based, matching the adjacency-list `depth`). A leaf may
     * sit at this depth; a group may not, since a group here would push its children one level
     * past it. Enforced server-side in the plan editor and mirrored by the read-only view.
     */
    public const int MAX_DEPTH = 3;

    /** Only plain budget leaves can be booked against — groups and mounts cannot. */
    public function isBookable(): bool
    {
        return $this->kind() === BudgetItemKind::Budget;
    }

    /**
     * Constrain to bookable leaves — the query-level counterpart of isBookable(): plain budget
     * items, excluding groups and mounts. This is exactly the set the legacy `haushaltstitel`
     * view exposed, so it defines which items a project post's titel_id may point at.
     */
    #[Scope]
    protected function bookable(Builder $query): void
    {
        $query->where('is_group', false)->whereNull('referenced_plan_id');
    }

    /**
     * This item's 0-based nesting depth, computed by walking the parent chain. Works on plainly
     * loaded models (no tree query needed); the chain is at most MAX_DEPTH links deep.
     */
    public function nestingDepth(): int
    {
        $depth = 0;
        for ($ancestor = $this->parent; $ancestor !== null; $ancestor = $ancestor->parent) {
            $depth++;
        }

        return $depth;
    }

    /** Whether a plain leaf at this depth may still be turned into a group without over-nesting. */
    public function canBecomeGroup(): bool
    {
        return $this->nestingDepth() <= self::MAX_DEPTH - 1;
    }

    /**
     * Ids of this item's ancestor groups (root → parent), read off the adjacency-list `path`.
     * Only groups can have children, so every ancestor id is a group id — exactly the set of
     * groups whose collapse should hide this row in the plan view. Requires the model to come
     * from a tree query (treeOf()/budgetItemsTree()) that populates the `path` attribute.
     *
     * @return list<int>
     */
    public function ancestorIds(): array
    {
        $path = (string) ($this->{$this->getPathName()} ?? '');
        if ($path === '') {
            return [];
        }

        $ids = array_map(intval(...), explode($this->getPathSeparator(), $path));
        array_pop($ids); // drop self, keep ancestors only

        return array_values($ids);
    }

    /**
     * The item's effective value: a mount resolves to the referenced plan's total for its side
     * (income/expense), everything else uses the stored value. $visited guards reference cycles.
     *
     * @param  array<int, int>  $visited  plan ids already entered, to stop mount cycles
     */
    public function effectiveValue(array $visited = []): Money
    {
        if ($this->isMount() && $this->referencedPlan !== null) {
            return $this->referencedPlan->sumForType($this->budget_type, $visited);
        }

        if ($this->is_group) {
            // a group's value is the LIVE sum of its children's effective values, so a mount
            // nested anywhere inside still rolls up (a mount's derived total can't be stored)
            $sum = Money::EUR(0);
            foreach ($this->children as $child) {
                $sum = $sum->add($child->effectiveValue($visited));
            }

            return $sum;
        }

        return $this->value ?? Money::EUR(0);
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
