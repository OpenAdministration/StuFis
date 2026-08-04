<?php

use App\Models\FiscalYear;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'md'])] class extends Component
{
    #[Locked]
    public ?int $id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount($year_id = null): void
    {
        Gate::authorize('budget-officer', User::class);

        $this->id = $year_id;

        if ($this->id) {
            // edit an existing fiscal year
            $fiscalYear = FiscalYear::findOrFail($this->id);
            $this->start_date = $fiscalYear->start_date->format('Y-m-d');
            $this->end_date = $fiscalYear->end_date->format('Y-m-d');

            return;
        }

        // create: suggest the year directly following the latest one
        $lastYear = FiscalYear::orderBy('end_date', 'desc')->first();
        if ($lastYear) {
            $nextStart = $lastYear->end_date->copy()->addDay();
            $this->start_date = $nextStart->format('Y-m-d');
            // a fiscal year spans one full year, e.g. 01.04.24 – 31.03.25
            $this->end_date = $nextStart->copy()->addYear()->subDay()->format('Y-m-d');
        }
    }

    /**
     * Fiscal years are meant to tile time without gaps. Detect whether the
     * currently entered range leaves a hole to the nearest neighbouring fiscal
     * year on either side, so the form can warn (not block) about it.
     *
     * @return list<array{start: Carbon, end: Carbon}>
     */
    #[Computed]
    public function gaps(): array
    {
        if (! $this->start_date || ! $this->end_date) {
            return [];
        }

        try {
            $start = Date::parse($this->start_date)->startOfDay();
            $end = Date::parse($this->end_date)->startOfDay();
        } catch (Throwable) {
            return [];
        }

        if ($end->lessThan($start)) {
            return [];
        }

        $gaps = [];

        $previous = FiscalYear::query()
            ->when($this->id, fn ($query) => $query->whereKeyNot($this->id))
            ->whereDate('end_date', '<', $start)
            ->latest('end_date')
            ->first();

        if ($previous && $previous->end_date->copy()->addDay()->lessThan($start)) {
            $gaps[] = ['start' => $previous->end_date->copy()->addDay(), 'end' => $start->copy()->subDay()];
        }

        $next = FiscalYear::query()
            ->when($this->id, fn ($query) => $query->whereKeyNot($this->id))
            ->whereDate('start_date', '>', $end)
            ->oldest('start_date')
            ->first();

        if ($next && $next->start_date->copy()->subDay()->greaterThan($end)) {
            $gaps[] = ['start' => $end->copy()->addDay(), 'end' => $next->start_date->copy()->subDay()];
        }

        return $gaps;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                // fiscal years must not overlap: they partition time without gaps or overlaps
                function (string $attribute, $value, callable $fail): void {
                    $overlaps = FiscalYear::query()
                        ->when($this->id, fn ($query) => $query->whereKeyNot($this->id))
                        ->whereDate('start_date', '<=', $this->end_date)
                        ->whereDate('end_date', '>=', $this->start_date)
                        ->exists();

                    if ($overlaps) {
                        $fail(__('budget-plan.fiscal-year.overlap-error'));
                    }
                },
            ],
        ];
    }

    public function save(): void
    {
        Gate::authorize('budget-officer', User::class);
        $this->validate();

        $fiscalYear = $this->id ? FiscalYear::findOrFail($this->id) : new FiscalYear;
        $fiscalYear->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ])->save();

        Flux::toast(__('budget-plan.fiscal-year.saved'), variant: 'success');

        $this->redirect(route('budget-plan.index'), navigate: true);
    }
};
