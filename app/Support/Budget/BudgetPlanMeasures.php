<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\Project\ApprovedByFinance;
use App\States\Project\ApprovedByOrg;
use App\States\Project\ApprovedByOther;
use App\States\Project\NeedFinanceApproval;
use App\States\Project\Terminated;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes the "Ist" (booked) and "Beschlossen" (committed) figures for one side (income or
 * expense) of a budget plan, mirroring the legacy DBConnector::dbgetHHP / getMoneyByTitle:
 *
 *  - booked    = Σ booking.value for the leaf, ignoring canceled bookings
 *  - committed = money reserved by projects: open projects' postings (projektposten) plus
 *                terminated projects' receipt postings (beleg_posten, excluding revocations)
 *
 * Both roll up the item tree exactly like BudgetItem::effectiveValue()/BudgetPlan::sumForType():
 * a group is the sum of its children, a mount is the referenced plan's total for this side.
 *
 * The per-leaf figures are fetched once (one query for booked, two for committed) and cached,
 * so annotating the whole tree touches the DB a constant number of times rather than per leaf.
 */
class BudgetPlanMeasures
{
    /** @var array<int, Money> booked per leaf item id (titel_id) */
    private array $booked;

    /** @var array<int, Money> committed per leaf item id (titel_id) */
    private array $committed;

    /**
     * @param  array<int, int>  $visited  plan ids already entered while recursing through mounts
     */
    public function __construct(private readonly BudgetPlan $plan, private readonly BudgetType $type, private array $visited = [])
    {
        $this->visited[] = $plan->id;

        $leafIds = $plan->budgetItems()
            ->where('budget_type', $type)
            ->where('is_group', false)
            ->whereNull('referenced_plan_id')
            ->pluck('id')
            ->all();

        $this->booked = $this->bookedMap($leafIds);
        $this->committed = $this->committedMap($leafIds);
    }

    /**
     * Load this side's item tree and annotate every node with `planned`, `booked` and `committed`
     * Money attributes (rolled up through groups and mounts). Returns the flattened, ordered tree.
     */
    public function annotate(): Collection
    {
        $items = $this->plan->budgetItemsTree($this->type);
        $childrenByParent = $items->groupBy('parent_id');

        foreach ($items->whereNull('parent_id') as $root) {
            $this->measure($root, $childrenByParent);
        }

        return $items;
    }

    /**
     * Planned/booked/committed figures for a single item (rolled up, so it also works for
     * groups/mounts).
     *
     * @return array{planned: Money, booked: Money, committed: Money}
     */
    public function forItem(BudgetItem $item): array
    {
        $items = $this->plan->budgetItemsTree($this->type);
        $childrenByParent = $items->groupBy('parent_id');

        return $this->measure($item, $childrenByParent);
    }

    /**
     * Plan-level planned/booked/committed totals for this side (sum of the roots' effective
     * figures). Used when a mount resolves to the referenced plan's total.
     *
     * @return array{planned: Money, booked: Money, committed: Money}
     */
    public function totals(): array
    {
        $items = $this->plan->budgetItemsTree($this->type);
        $childrenByParent = $items->groupBy('parent_id');

        $planned = Money::EUR(0);
        $booked = Money::EUR(0);
        $committed = Money::EUR(0);
        foreach ($items->whereNull('parent_id') as $root) {
            $measure = $this->measure($root, $childrenByParent);
            $planned = $planned->add($measure['planned']);
            $booked = $booked->add($measure['booked']);
            $committed = $committed->add($measure['committed']);
        }

        return ['planned' => $planned, 'booked' => $booked, 'committed' => $committed];
    }

    /**
     * Set planned/booked/committed on $item and return them: a mount resolves to the referenced
     * plan's totals, a group sums its children, a leaf reads its own value and the precomputed
     * per-titel maps. Planned mirrors BudgetItem::effectiveValue() so the numbers match the tree.
     *
     * @param  Collection<int|string, Collection<int, BudgetItem>>  $childrenByParent
     * @return array{planned: Money, booked: Money, committed: Money}
     */
    private function measure(BudgetItem $item, Collection $childrenByParent): array
    {
        if ($item->isMount()) {
            $referenced = $item->referencedPlan;
            if ($referenced === null) {
                // dangling reference: effectiveValue() falls back to the stored value
                return $this->assign($item, $item->value ?? Money::EUR(0), Money::EUR(0), Money::EUR(0));
            }
            if (in_array($referenced->id, $this->visited, true)) {
                return $this->assign($item, Money::EUR(0), Money::EUR(0), Money::EUR(0)); // cycle
            }
            $totals = new self($referenced, $this->type, $this->visited)->totals();

            return $this->assign($item, $totals['planned'], $totals['booked'], $totals['committed']);
        }

        if ($item->is_group) {
            // a group's planned figure is the LIVE sum of its children (mirrors effectiveValue),
            // so a mount nested anywhere inside still rolls up even though its total can't be stored
            $planned = Money::EUR(0);
            $booked = Money::EUR(0);
            $committed = Money::EUR(0);
            foreach ($childrenByParent->get($item->id, collect()) as $child) {
                $measure = $this->measure($child, $childrenByParent);
                $planned = $planned->add($measure['planned']);
                $booked = $booked->add($measure['booked']);
                $committed = $committed->add($measure['committed']);
            }

            return $this->assign($item, $planned, $booked, $committed);
        }

        return $this->assign(
            $item,
            $item->value ?? Money::EUR(0),
            $this->booked[$item->id] ?? Money::EUR(0),
            $this->committed[$item->id] ?? Money::EUR(0),
        );
    }

    /**
     * Attach the three computed figures to the item as (view-only, non-persisted) Money attributes
     * and return them. These are dynamic attributes — not table columns — read as `$item->planned`
     * / `->booked` / `->committed` in the plan view, item view and export. Returning them as well
     * lets measure() roll a child's figures into its parent without re-reading the attributes.
     *
     * @return array{planned: Money, booked: Money, committed: Money}
     */
    private function assign(BudgetItem $item, Money $planned, Money $booked, Money $committed): array
    {
        $item->planned = $planned;
        $item->booked = $booked;
        $item->committed = $committed;

        return ['planned' => $planned, 'booked' => $booked, 'committed' => $committed];
    }

    /**
     * Σ booking.value per leaf, ignoring canceled bookings.
     *
     * @param  array<int, int>  $leafIds
     * @return array<int, Money>
     */
    private function bookedMap(array $leafIds): array
    {
        if ($leafIds === []) {
            return [];
        }

        return DB::table('booking')
            ->whereIn('titel_id', $leafIds)
            ->where('canceled', 0)
            ->groupBy('titel_id')
            ->selectRaw('titel_id, SUM(value) as total')
            ->get()
            ->mapWithKeys(static fn ($row): array => [$row->titel_id => Money::parseByDecimal((string) $row->total, 'EUR')])
            ->all();
    }

    /**
     * Committed money per leaf: open projects' postings plus terminated projects' receipt
     * postings. Sign follows the side (income = einnahmen − ausgaben, expense = ausgaben −
     * einnahmen) so both columns read as positive amounts.
     *
     * @param  array<int, int>  $leafIds
     * @return array<int, Money>
     */
    private function committedMap(array $leafIds): array
    {
        if ($leafIds === []) {
            return [];
        }

        $committed = [];
        foreach ([$this->openPostings($leafIds), $this->closedPostings($leafIds)] as $rows) {
            foreach ($rows as $row) {
                $einnahmen = Money::parseByDecimal((string) ($row->einnahmen ?? 0), 'EUR');
                $ausgaben = Money::parseByDecimal((string) ($row->ausgaben ?? 0), 'EUR');

                $value = $this->type === BudgetType::EXPENSE
                    ? $ausgaben->subtract($einnahmen)
                    : $einnahmen->subtract($ausgaben);

                $committed[$row->titel_id] = isset($committed[$row->titel_id])
                    ? $committed[$row->titel_id]->add($value)
                    : $value;
            }
        }

        return $committed;
    }

    /**
     * Postings of not-yet-terminated (approved/ongoing) projects, summed per titel.
     *
     * @param  array<int, int>  $leafIds
     */
    private function openPostings(array $leafIds): Collection
    {
        $openStates = [
            NeedFinanceApproval::$name, // ok-by-hv
            ApprovedByOrg::$name,       // ok-by-stura
            ApprovedByFinance::$name,   // done-hv
            ApprovedByOther::$name,     // done-other
        ];

        // DB::table (not the Eloquent model) keeps einnahmen/ausgaben as raw decimals rather than
        // Money. titel_id/einnahmen/ausgaben only exist on projektposten here, so the raw SUM(...)
        // can stay unqualified and sidestep the environment table prefix.
        return DB::table('projektposten')
            ->join('projekte', 'projekte.id', '=', 'projektposten.projekt_id')
            ->whereIn('projekte.state', $openStates)
            ->whereIn('projektposten.titel_id', $leafIds)
            ->groupBy('projektposten.titel_id')
            ->selectRaw('titel_id, SUM(einnahmen) as einnahmen, SUM(ausgaben) as ausgaben')
            ->get();
    }

    /**
     * Receipt postings of terminated projects (excluding revoked expenses), summed per titel.
     *
     * @param  array<int, int>  $leafIds
     */
    private function closedPostings(array $leafIds): Collection
    {
        // einnahmen/ausgaben exist on both beleg_posten and projektposten, so they must be qualified
        // to beleg_posten — raw table refs need the runtime prefix. titel_id is unique to
        // projektposten and stays unqualified.
        $bp = DB::getTablePrefix().'beleg_posten';

        return DB::table('beleg_posten')
            ->join('belege', 'belege.id', '=', 'beleg_posten.beleg_id')
            ->join('auslagen', 'auslagen.id', '=', 'belege.auslagen_id')
            ->join('projekte', 'projekte.id', '=', 'auslagen.projekt_id')
            ->join('projektposten', function ($join): void {
                $join->on('projektposten.projekt_id', '=', 'projekte.id')
                    ->on('projektposten.id', '=', 'beleg_posten.projekt_posten_id');
            })
            ->where('projekte.state', Terminated::$name)
            ->where('auslagen.state', 'NOT LIKE', 'revocation%')
            ->whereIn('projektposten.titel_id', $leafIds)
            ->groupBy('projektposten.titel_id')
            ->selectRaw("titel_id, SUM({$bp}.einnahmen) as einnahmen, SUM({$bp}.ausgaben) as ausgaben")
            ->get();
    }
}
