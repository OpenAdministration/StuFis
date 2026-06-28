<?php

use App\Models\FiscalYear;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
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
