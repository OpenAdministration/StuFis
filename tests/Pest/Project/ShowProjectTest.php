<?php

namespace Tests\Pest\Project;

use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use App\Models\Legacy\Project;
use App\States\BudgetPlan\Published;
use Livewire\Livewire;

beforeEach(function (): void {
    // relatedBudgetPlan()->label() / the budget-plan link need a covering plan. The legacy
    // haushaltsplan is now a view over budget_plan, so seed the new structure (published =>
    // "final" in the view) with a fiscal year covering today.
    $fiscalYear = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    BudgetPlan::create(['fiscal_year_id' => $fiscalYear->id, 'state' => Published::class]);
    $this->actingAs(user());
});

it('shows a backlink to the source project on a copied project', function (): void {
    $source = Project::factory()->by(user())->create();
    $copy = Project::factory()->by(user())->create([
        'source_id' => $source->id,
        'source_kind' => 'copy',
    ]);

    Livewire::test('pages::project.show-project', ['project_id' => $copy->id])
        ->assertOk()
        ->assertSee('Kopiert aus')
        ->assertSee("P#{$source->id}");
});

it('shows forward references to derived leftover projects on the source', function (): void {
    $source = Project::factory()->by(user())->create();
    $leftover = Project::factory()->by(user())->create(['source_id' => $source->id, 'source_kind' => 'leftovers']);

    Livewire::test('pages::project.show-project', ['project_id' => $source->id])
        ->assertOk()
        ->assertSee('Restmittel übertragen nach')
        ->assertSee("P#{$leftover->id}");
});

it('links the budget plan to the legacy plan view', function (): void {
    $project = Project::factory()->by(user())->create();
    $plan = $project->relatedBudgetPlan();

    Livewire::test('pages::project.show-project', ['project_id' => $project->id])->assertOk()->assertSeeHtml(route('legacy.hhp.view', $plan->id));
});
