<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\BudgetPlanCloner;
use App\Support\Budget\TitleNumberer;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layout.app', ['size' => 'md'])] class extends Component
{
    public ?string $organization = null;

    public ?int $fiscal_year_id = null;

    /** How the new plan is seeded: a blank template, or cloned from an existing plan. */
    public string $starting_point = 'template';

    public ?int $source_plan_id = null;

    /** Per mounted sub-plan: 'copy' (clone it too) or 'drop' (flatten to an empty group). Keyed by plan id. */
    public array $mountChoices = [];

    public function mount(): void
    {
        Gate::authorize('create', BudgetPlan::class);

        // deep-linked from a plan's "Duplizieren" action: preselect it as the clone source
        $sourceId = request()->integer('source') ?: null;
        if ($sourceId !== null && BudgetPlan::whereKey($sourceId)->exists()) {
            $this->starting_point = 'clone';
            $this->source_plan_id = $sourceId;
            $this->prefillFromSource();
        }
    }

    public function with(): array
    {
        $source = $this->starting_point === 'clone' && $this->source_plan_id
            ? BudgetPlan::find($this->source_plan_id)
            : null;

        return [
            'fiscal_years' => FiscalYear::orderByDesc('start_date')->get(),
            'source_plans' => BudgetPlan::with('fiscalYear')->orderByDesc('id')->get(),
            'mounted_plans' => $source ? $source->reachableMountedPlans() : collect(),
        ];
    }

    public function rules(): array
    {
        return [
            'organization' => [
                'nullable', 'string', 'max:255',
                // an organization may appear once per fiscal year (explicit, not silently renamed)
                function (string $attribute, $value, callable $fail): void {
                    if (BudgetPlan::organizationTaken($value, $this->fiscal_year_id)) {
                        $fail(__('budget-plan.create.organization-taken'));
                    }
                },
            ],
            'fiscal_year_id' => ['nullable', 'exists:fiscal_year,id'],
            'starting_point' => ['required', 'in:template,clone'],
            'source_plan_id' => ['nullable', 'required_if:starting_point,clone', 'exists:budget_plan,id'],
        ];
    }

    public function updatedSourcePlanId(): void
    {
        $this->prefillFromSource();
    }

    public function updatedStartingPoint(): void
    {
        $this->prefillFromSource();
    }

    /** Re-suggest a non-colliding organization name when the target year changes while cloning. */
    public function updatedFiscalYearId(): void
    {
        $this->suggestOrganization();
    }

    /** Jump to the fiscal-year creator, mirroring the editor's inline "new year" action. */
    public function createFiscalYear(): void
    {
        $this->redirect(route('fiscal-year.create'), navigate: true);
    }

    public function save(TitleNumberer $numberer, BudgetPlanCloner $cloner): void
    {
        Gate::authorize('create', BudgetPlan::class);
        $this->validate();

        $plan = BudgetPlan::create([
            'state' => Draft::class,
            'organization' => $this->organization ?: null,
            'fiscal_year_id' => $this->fiscal_year_id,
        ]);

        if ($this->starting_point === 'clone') {
            $source = BudgetPlan::findOrFail($this->source_plan_id);
            $cloner->cloneInto($source, $plan, $this->mountChoices);
        } else {
            $this->seedBlankTemplate($plan, $numberer);
        }

        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
        $this->redirect(route('budget-plan.edit', ['plan_id' => $plan->id]), navigate: true);
    }

    /**
     * Carry the chosen clone source's metadata into the form: prefill the (year-aware) suggested
     * organization name and fiscal year, and default every reachable mounted sub-plan to 'copy'.
     * Runs whenever the starting point or source changes.
     */
    private function prefillFromSource(): void
    {
        $this->mountChoices = [];

        if ($this->starting_point !== 'clone' || ! $this->source_plan_id) {
            return;
        }

        $source = BudgetPlan::find($this->source_plan_id);
        if ($source === null) {
            return;
        }

        $this->fiscal_year_id = $source->fiscal_year_id;
        $this->suggestOrganization();

        foreach ($source->reachableMountedPlans() as $sub) {
            $this->mountChoices[$sub->id] = 'copy';
        }
    }

    /**
     * Suggest the organization field from the clone source, appending " (Kopie)" only when the
     * source's name already exists in the selected year. No-op outside the clone flow, so a
     * blank/hand-typed name is left untouched.
     */
    private function suggestOrganization(): void
    {
        if ($this->starting_point !== 'clone' || ! $this->source_plan_id) {
            return;
        }

        $source = BudgetPlan::find($this->source_plan_id);
        if ($source !== null) {
            $this->organization = BudgetPlan::resolveOrganization($source->organization, $this->fiscal_year_id);
        }
    }

    /**
     * Seed each side with an empty group holding one budget line; Titelnummern (E1 / A1, then
     * E1.1 / A1.1) are derived the same way as in the editor.
     */
    private function seedBlankTemplate(BudgetPlan $plan, TitleNumberer $numberer): void
    {
        foreach (BudgetType::cases() as $type) {
            $group = $this->numbered($numberer, $plan->budgetItems()->make([
                'is_group' => true,
                'budget_type' => $type,
                'position' => 0,
            ]));

            $this->numbered($numberer, $group->children()->make([
                'budget_plan_id' => $plan->id,
                'is_group' => false,
                'budget_type' => $type,
                'position' => 0,
            ]));
        }
    }

    private function numbered(TitleNumberer $numberer, BudgetItem $item): BudgetItem
    {
        $item->save();
        $item->short_name = $numberer->next($item);
        $item->save();

        return $item;
    }
};
