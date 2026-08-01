<?php

namespace App\Models;

use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\BudgetPlanState;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use App\Support\Budget\AmendmentDeltaSummary;
use Carbon\Carbon;
use Cknow\Money\Money;
use Database\Factories\BudgetPlanFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
 * @property Carbon|null $activation_date
 * @property string|null $justification
 * @property string|null $name
 * @property BudgetPlanState $state
 * @property BudgetPlan|null $parentPlan
 * @property BudgetItem[] $budgetItems
 * @property int|null $parent_plan_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int|null $budget_items_count
 * @property-read Collection<int, BudgetPlan> $amendments
 * @property-read int|null $amendments_count
 * @property-read Collection<int, BudgetItemChange> $itemChanges
 * @property-read int|null $item_changes_count
 *
 * @method static BudgetPlanFactory factory($count = null, $state = [])
 * @method static Builder|BudgetPlan newModelQuery()
 * @method static Builder|BudgetPlan newQuery()
 * @method static Builder|BudgetPlan query()
 * @method static Builder|BudgetPlan original()
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
    protected $fillable = ['organization', 'name', 'fiscal_year_id', 'resolution_date', 'approval_date', 'state', 'parent_plan_id', 'activation_date', 'justification'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'state' => BudgetPlanState::class,
            'resolution_date' => 'date',
            'approval_date' => 'date',
            'activation_date' => 'date',
        ];
    }

    /**
     * When an amendment reaches Approved with no activation_date set yet, default it to the
     * approval_date (still editable afterwards, and may be set earlier too — both are allowed).
     * A plain model event rather than transition-specific logic, so it fires regardless of which
     * arc reaches Approved (Resolved -> Approved, or back from Active -> Approved).
     */
    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (self $plan): void {
            if ($plan->isAmendment() && $plan->activation_date === null && $plan->state instanceof Approved) {
                $plan->activation_date = $plan->approval_date;
            }
        });
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

        // treeOf()'s $constraint only seeds the roots; the recursive CTE step that then walks
        // parent_id has no plan filter of its own. An amendment's own items are parented under a
        // base-plan group (and a parked deletion is rehomed the other way), so without this the
        // base plan's tree would pull in the amendment's drafted additions (and vice versa) — a
        // group's value is the live sum of its children (see BudgetItem::effectiveValue()), so
        // this isn't just a display glitch, it silently changes the running plan's totals.
        // withRecursiveQueryConstraint() adds this where to every step of the recursive join, so
        // an excluded item's descendants never get a matching CTE row to join against either —
        // no post-filtering, no orphan promotion. Column must be qualified: the recursive step
        // joins budget_item against the CTE (which also selects budget_item.*), so a bare
        // "budget_plan_id" is ambiguous between the two.
        return BudgetItem::withRecursiveQueryConstraint(
            static fn ($query) => $query->where($query->getModel()->qualifyColumn('budget_plan_id'), $plan_id),
            static fn () => BudgetItem::treeOf($constraint)->orderBy('position_path')->get(),
        );
    }

    public function rootBudgetItems(): Builder|HasMany|BudgetPlan
    {
        return $this->hasMany(BudgetItem::class)->whereNull('parent_id');
    }

    /** The plan this amendment supplements (null for an original plan). */
    public function parentPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_plan_id');
    }

    /** This plan's own Nachtragshaushaltspläne (amendments), if any. */
    public function amendments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_plan_id');
    }

    /** The delta rows drafted against this plan (only meaningful when this IS an amendment). */
    public function itemChanges(): HasMany
    {
        return $this->hasMany(BudgetItemChange::class);
    }

    /**
     * This amendment's net income/expense delta, aggregated once here (F5, OP#581) and shown in
     * both the editor's Begründungen tab and the amendment's plan-view diff section.
     *
     * @return array{income: Money, expense: Money, saldo: Money}
     */
    public function amendmentDeltaSummary(): array
    {
        return resolve(AmendmentDeltaSummary::class)->compute($this);
    }

    /**
     * Whether a normal (non-amendment) plan may still go through ⚡plan-edit (F8, OP#581):
     * editable in Draft/Resolved, frozen from Approved onward — Approved is the point past which
     * the plan is meant to be a stable, agreed-upon document, and Active/Completed plans are live
     * or done. An amendment follows its own, stricter rule (Draft only) enforced directly in
     * ⚡amendment-edit, not this method.
     */
    public function isEditable(): bool
    {
        return $this->state instanceof Draft || $this->state instanceof Resolved;
    }

    /** Whether this plan is a Nachtragshaushaltsplan (supplements another plan) rather than an original plan. */
    public function isAmendment(): bool
    {
        return $this->parent_plan_id !== null;
    }

    /**
     * Original plans only — excludes amendments. Amendments are drafted/approved as independent
     * objects but must never surface where a free-standing plan is expected (plan lists, the
     * mount picker, clone sources, uniqueness checks, ...).
     */
    #[Scope]
    protected function original(Builder $query): void
    {
        $query->whereNull('parent_plan_id');
    }

    /** Whether this plan has amendments that are (or were) live-effective — Active or Completed. */
    public function hasAppliedAmendments(): bool
    {
        return $this->appliedAmendments()->exists();
    }

    /** This plan's amendments that are (or were) live-effective, i.e. applied at least once. */
    public function appliedAmendments(): HasMany
    {
        return $this->amendments()->whereIn('state', [Active::$name, Completed::$name]);
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

        return static::query()->original()
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

    /**
     * The most recently created plan, or null when none exist yet.
     *
     * Deliberately not named `latest()`: that would shadow Eloquent's builder
     * scope, so callers expecting a query would silently get a model instead.
     */
    public static function newest(): ?static
    {
        return static::query()->original()->orderByDesc('id')->first();
    }

    /**
     * Human label for the plan. An amendment has no organization of its own (it inherits its
     * parent's), so it uses its optional `name` (F3, OP#581) instead, falling back to
     * "Nachtrag vom {created_at}" — the single place this fallback is decided, rather than
     * scattering it across every view that lists amendments.
     */
    public function label(): string
    {
        if ($this->isAmendment()) {
            return $this->name ?: __('budget-plan.amendment.unnamed-fallback', ['date' => $this->created_at->format('d.m.Y')]);
        }

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
