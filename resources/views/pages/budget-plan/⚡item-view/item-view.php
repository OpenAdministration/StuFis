<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Legacy\Booking;
use App\Support\Budget\BudgetPlanMeasures;
use Cknow\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    #[Locked]
    public int $plan_id;

    #[Locked]
    public int $item_id;

    /** Active detail tab, kept in the URL so it survives reloads and is linkable. */
    #[Url]
    public string $tab = 'bookings';

    public string $bookingSort = 'date';

    public string $bookingDir = 'desc';

    public string $committedSort = 'project';

    public string $committedDir = 'asc';

    public function mount(int $plan_id, int $item_id): void
    {
        $this->plan_id = $plan_id;
        $this->item_id = $item_id;
        $this->authorize('view', $this->plan());
    }

    public function sortBookings(string $column): void
    {
        [$this->bookingSort, $this->bookingDir] = $this->nextSort($this->bookingSort, $this->bookingDir, $column);
    }

    public function sortCommitted(string $column): void
    {
        [$this->committedSort, $this->committedDir] = $this->nextSort($this->committedSort, $this->committedDir, $column);
    }

    public function with(): array
    {
        $plan = $this->plan();
        $item = $this->item();
        $measures = new BudgetPlanMeasures($plan, $item->budget_type);
        $measure = $measures->forItem($item);

        return [
            'plan' => $plan,
            'item' => $item,
            'planned' => $measure['planned'],
            'booked' => $measure['booked'],
            'committed' => $measure['committed'],
            'rows' => $this->sort($this->rows($item), $this->bookingSort, $this->bookingDir, [
                'id' => static fn (array $r): int => $r['id'],
                'date' => static fn (array $r): int => $r['date']?->getTimestamp() ?? 0,
                'project' => static fn (array $r): string => $r['project']?->name ?? '',
                'amount' => static fn (array $r): int => (int) $r['amount']->getAmount(),
            ]),
            'committedRows' => $this->sort($measures->committedBreakdown($item), $this->committedSort, $this->committedDir, [
                'project' => static fn (array $r): string => $r['project_name'],
                'planned' => static fn (array $r): int => (int) $r['planned']->getAmount(),
                'billed' => static fn (array $r): int => (int) $r['billed']->getAmount(),
                'state' => static fn (array $r): string => $r['project_state'],
            ]),
        ];
    }

    /**
     * The item's non-canceled bookings, flattened to a view model. The receipt chain
     * (Belegposten → Beleg → Abrechnung → Projekt) is eager-loaded; the bank transaction is not —
     * its composite (zahlung_id, zahlung_type) key breaks eager loading (see Booking::bankTransaction).
     */
    private function rows(BudgetItem $item): Collection
    {
        return $item->bookings()
            ->where('canceled', 0)
            ->with('expensesReceiptPost.expensesReceipt.expense.project')
            ->get()
            ->map(static function (Booking $booking): array {
                $post = $booking->beleg_type === 'belegposten' ? $booking->expensesReceiptPost : null;
                $expense = $post?->expensesReceipt?->expense;

                return [
                    'id' => $booking->id,
                    // legacy timestamps are plain strings and may hold unparseable zero-dates
                    'date' => rescue(static fn (): Carbon => Carbon::parse($booking->timestamp), null, report: false),
                    'transaction' => $booking->bankTransaction,
                    'project' => $expense?->project,
                    'expense' => $expense,
                    'post' => $post,
                    'amount' => Money::parseByDecimal((string) $booking->value, 'EUR'),
                ];
            });
    }

    /**
     * Sort a view-model collection by one of a whitelist of columns. Unknown columns fall back to
     * the first accessor, so a tampered sort field can never reach arbitrary data.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, callable>  $accessors
     * @return Collection<int, array<string, mixed>>
     */
    private function sort(Collection $rows, string $by, string $dir, array $accessors): Collection
    {
        $accessor = $accessors[$by] ?? reset($accessors);

        return $rows->sortBy($accessor, SORT_REGULAR, $dir === 'desc')->values();
    }

    /**
     * Next [column, direction] for a header click: a new column starts ascending, clicking the
     * active column again flips the direction.
     *
     * @return array{0: string, 1: string}
     */
    private function nextSort(string $current, string $dir, string $column): array
    {
        if ($current === $column) {
            return [$column, $dir === 'asc' ? 'desc' : 'asc'];
        }

        return [$column, 'asc'];
    }

    private function plan(): BudgetPlan
    {
        return BudgetPlan::findOrFail($this->plan_id);
    }

    private function item(): BudgetItem
    {
        return BudgetItem::where('budget_plan_id', $this->plan_id)->findOrFail($this->item_id);
    }
};
