<?php

namespace App\Livewire;

use App\Models\Legacy\Expense;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Services\Auth\AuthService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProjectOverview extends Component
{
    #[Url]
    public ?int $hhpId = null;

    #[Url]
    public string $tab = 'mygremium';

    public function mount(): void
    {
        if ($this->hhpId === null) {
            $this->hhpId = LegacyBudgetPlan::latest()?->id;
        }
    }

    #[Computed]
    public function budgetPlans(): Collection
    {
        return LegacyBudgetPlan::orderBy('id', 'desc')->get();
    }

    #[Computed]
    public function currentBudgetPlan(): ?LegacyBudgetPlan
    {
        return LegacyBudgetPlan::find($this->hhpId);
    }

    #[Computed]
    public function userCommittees(): array
    {
        return app(AuthService::class)->userCommittees()->toArray();
    }

    #[Computed]
    public function projectsByCommittee(): Collection
    {
        $budgetPlan = $this->currentBudgetPlan();
        if (!$budgetPlan) {
            return [];
        }

        $query = Project::query()
            ->with(['posts', 'expenses.receipts.posts'])
            ->withSum('posts as total_ausgaben', 'ausgaben')
            ->withSum('posts as total_einnahmen', 'einnahmen')
            ->where('createdat', '>=', $budgetPlan->von);

        if ($budgetPlan->bis) {
            $query->where('createdat', '<=', $budgetPlan->bis);
        }

        // Apply tab-specific filters
        switch ($this->tab) {
            case 'mygremium':
                $committees = $this->userCommittees;
                if (empty($committees)) {
                    return [];
                }
                $query->where(function ($q) use ($committees) {
                    $q->whereIn('org', $committees)
                        ->orWhereNull('org')
                        ->orWhere('org', '');
                });
                break;

            case 'allgremium':
                // No additional filter, show all
                break;

            case 'open-projects':
                $query->whereNotIn('state', ['terminated', 'revoked'])
                    ->where('state', 'not like', '%terminated%')
                    ->where('state', 'not like', '%revoked%');
                break;
        }

        $projects = $query->orderBy('org')->orderBy('id', 'desc')->get();

        // Group by committee
        return $projects->groupBy(fn($project) => $project->org ?: '');
    }

    #[Computed]
    public function expensesByProjectId(): array
    {
        $projectIds = collect($this->projectsByCommittee)
            ->flatten(1)
            ->pluck('id')
            ->toArray();

        if (empty($projectIds)) {
            return [];
        }

        return Expense::query()
            ->with(['receipts.posts'])
            ->whereIn('projekt_id', $projectIds)
            ->get()
            ->groupBy('projekt_id')
            ->toArray();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function setBudgetPlan(int $id): void
    {
        $this->hhpId = $id;
    }

    public function render()
    {
        return view('livewire.project-overview');
    }
}
