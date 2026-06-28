<?php

namespace App\Models;

use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\BudgetPlanState;
use Carbon\Carbon;
use Cknow\Money\Money;
use Database\Factories\BudgetPlanFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

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
 *
 * @property string|null $organization
 * @property int|null $fiscal_year_id
 * @property-read FiscalYear|null $fiscalYear
 * @property-read Collection<int, BudgetItem> $rootBudgetItems
 * @property-read int|null $root_budget_items_count
 *
 * @method static Builder<static>|BudgetPlan whereApprovalDate($value)
 * @method static Builder<static>|BudgetPlan whereCreatedAt($value)
 * @method static Builder<static>|BudgetPlan whereFiscalYearId($value)
 * @method static Builder<static>|BudgetPlan whereId($value)
 * @method static Builder<static>|BudgetPlan whereOrganization($value)
 * @method static Builder<static>|BudgetPlan whereParentPlanId($value)
 * @method static Builder<static>|BudgetPlan whereResolutionDate($value)
 * @method static Builder<static>|BudgetPlan whereState($value)
 * @method static Builder<static>|BudgetPlan whereUpdatedAt($value)
 */
class BudgetPlan extends Model
{
    use HasFactory;
    use HasStates;

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

    /**
     * @return HasMany<BudgetItem> returns all budget items of this plan flattend
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * @return Collection<BudgetItem> returns all budget items of this plan in tree format
     */
    public function budgetItemsTree(BudgetType $budgetType): Collection
    {
        // $this is not accessible from the closure scope
        $plan_id = $this->id;

        $constraint = static fn ($query) => $query->whereNull('parent_id')
            ->where('budget_plan_id', $plan_id)
            ->where('budget_type', $budgetType);

        // the full tree flattened out, the position path is a custom-built path
        return BudgetItem::treeOf($constraint)->orderBy('position_path')->get();
    }

    public function rootBudgetItems(): Builder|HasMany|BudgetPlan
    {
        return $this->hasMany(BudgetItem::class)->whereNull('parent_id');
    }

    /**
     * Sum of all root-level item values for the given budget type.
     *
     * Sums each root's effective value: normal roots use their stored value (group values are
     * auto-maintained as the sum of their children), while a mount root resolves to the
     * referenced plan's total — so totals roll up across mounted sub-plans. $visited guards
     * against reference cycles.
     *
     * @param  array<int, int>  $visited  plan ids already entered while recursing through mounts
     */
    public function sumForType(BudgetType $budgetType, array $visited = []): Money
    {
        if (in_array($this->id, $visited, true)) {
            return Money::EUR(0); // reference cycle — stop recursing
        }
        $visited[] = $this->id;

        $sum = Money::EUR(0);
        foreach ($this->rootBudgetItems()->where('budget_type', $budgetType)->get() as $root) {
            $sum = $sum->add($root->effectiveValue($visited));
        }

        return $sum;
    }

    /**
     * Whether this plan reaches $planId through its mounts (transitively), or is it.
     * Used to reject mounts that would create a reference cycle.
     *
     * @param  array<int, int>  $visited
     */
    public function reachesPlan(int $planId, array $visited = []): bool
    {
        if ($this->id === $planId) {
            return true;
        }
        if (in_array($this->id, $visited, true)) {
            return false;
        }
        $visited[] = $this->id;

        foreach ($this->budgetItems()->whereNotNull('referenced_plan_id')->pluck('referenced_plan_id') as $refId) {
            $referenced = static::find($refId);
            if ($referenced !== null && $referenced->reachesPlan($planId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Distinct plans reachable through this plan's mounts (transitive). Used to ask the user,
     * per sub-plan, whether to copy or drop it when cloning. $visited guards reference cycles.
     *
     * @param  array<int, int>  $visited
     * @return \Illuminate\Support\Collection<int, BudgetPlan>
     */
    public function reachableMountedPlans(array $visited = []): \Illuminate\Support\Collection
    {
        if (in_array($this->id, $visited, true)) {
            return collect();
        }
        $visited[] = $this->id;

        $plans = collect();
        foreach ($this->budgetItems()->whereNotNull('referenced_plan_id')->pluck('referenced_plan_id')->unique() as $refId) {
            $referenced = static::find($refId);
            if ($referenced === null) {
                continue;
            }
            $plans->put($referenced->id, $referenced);
            foreach ($referenced->reachableMountedPlans($visited) as $deep) {
                $plans->put($deep->id, $deep);
            }
        }

        return $plans->values();
    }

    /**
     * Whether $organization is already used by a plan in $fiscalYearId. A blank name never
     * counts as taken. $ignoreId excludes a specific plan (e.g. the row being edited).
     */
    public static function organizationTaken(?string $organization, ?int $fiscalYearId, ?int $ignoreId = null): bool
    {
        if (blank($organization)) {
            return false;
        }

        return static::query()
            ->where('organization', $organization)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->when(
                $fiscalYearId === null,
                fn ($query) => $query->whereNull('fiscal_year_id'),
                fn ($query) => $query->where('fiscal_year_id', $fiscalYearId),
            )
            ->exists();
    }

    /**
     * Suggest a non-colliding organization name for a new plan in $fiscalYearId: the name as
     * given, unless a plan in that fiscal year already uses it — then append " (Kopie)" (numbered
     * on repeat collisions) so duplicates within a year stay distinguishable.
     */
    public static function resolveOrganization(?string $organization, ?int $fiscalYearId): ?string
    {
        if (blank($organization) || ! static::organizationTaken($organization, $fiscalYearId)) {
            return $organization;
        }

        $suffix = __('budget-plan.edit.copy-suffix');
        $candidate = $organization.' ('.$suffix.')';
        for ($n = 2; static::organizationTaken($candidate, $fiscalYearId); $n++) {
            $candidate = $organization.' ('.$suffix.' '.$n.')';
        }

        return $candidate;
    }

    /** Human label for the plan (organization, with a fallback). */
    public function label(): string
    {
        return $this->organization ?: __('budget-plan.view.no-organization');
    }

    public function incomeTotal(): Money
    {
        return $this->sumForType(BudgetType::INCOME);
    }

    public function expenseTotal(): Money
    {
        return $this->sumForType(BudgetType::EXPENSE);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Resets the position values of all children to be sequential starting from 0
     * Use in case of buggyness in the position values
     */
    public function normalizePositions(): void
    {
        $items = $this->rootBudgetItems()->get();
        while ($items->isNotEmpty()) {
            $item = $items->pop();
            $item->normalizeChildPositionValues();
            $items = $items->merge($item->children);
        }
    }
}
