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

it('lists fiscal years without plans using a placeholder row', function (): void {
    $this->actingAs(user());

    $empty = FiscalYear::factory()->create(['start_date' => '2024-04-01', 'end_date' => '2025-03-31']);

    $this->get(route('budget-plan.index'))
        ->assertOk()
        ->assertSee($empty->label())
        ->assertSee(__('budget-plan.index.no-plans'));
});
