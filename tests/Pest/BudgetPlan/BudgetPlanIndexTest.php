<?php

use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('renders the index with plan labels and state, without debug strings or raw keys', function (): void {
    $this->actingAs(user());

    $year = FiscalYear::factory()->create();
    $assigned = BudgetPlan::create([
        'state' => Draft::class, 'fiscal_year_id' => $year->id, 'organization' => 'AStA',
    ]);
    BudgetPlan::create([
        'state' => Draft::class, 'organization' => 'Waisenplan',
    ]);

    $response = $this->get(route('budget-plan.index'));

    $response->assertOk()
        ->assertSee('AStA')
        ->assertSee('Waisenplan')
        ->assertSee(__('budget-plan.index.orphaned-plans'))
        ->assertSee($assigned->state->label())
        // the old debug placeholders are gone
        ->assertDontSee('ohhneee')
        ->assertDontSee('budget-plan.plan?');
});
