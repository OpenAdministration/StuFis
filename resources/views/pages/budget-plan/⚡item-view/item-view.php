<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Legacy\Booking;
use App\Support\Budget\BudgetPlanMeasures;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    #[Locked]
    public int $plan_id;

    #[Locked]
    public int $item_id;

    public function mount(int $plan_id, int $item_id): void
    {
        $this->plan_id = $plan_id;
        $this->item_id = $item_id;
        $this->authorize('view', $this->plan());
    }

    public function with(): array
    {
        $plan = $this->plan();
        $item = $this->item();
        $measure = new BudgetPlanMeasures($plan, $item->budget_type)->forItem($item);

        return [
            'plan' => $plan,
            'item' => $item,
            'planned' => $item->effectiveValue(),
            'booked' => $measure['booked'],
            'committed' => $measure['committed'],
            'rows' => $this->rows($item),
        ];
    }

    /**
     * The item's non-canceled bookings, flattened to a small view model so the Blade stays
     * simple: bank transaction (lazy access — never eager-loaded, see Booking::bankTransaction)
     * and, where resolvable, the originating project.
     */
    private function rows(BudgetItem $item): Collection
    {
        return $item->bookings()
            ->where('canceled', 0)
            ->orderByDesc('timestamp')
            ->get()
            ->map(static function (Booking $booking): array {
                $project = $booking->beleg_type === 'belegposten'
                    ? $booking->expensesReceiptPost?->expensesReceipt?->expense?->project
                    : null;

                return [
                    'amount' => Money::parseByDecimal((string) $booking->value, 'EUR'),
                    'timestamp' => $booking->timestamp,
                    'comment' => $booking->comment,
                    'transaction' => $booking->bankTransaction,
                    'project' => $project,
                ];
            });
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
