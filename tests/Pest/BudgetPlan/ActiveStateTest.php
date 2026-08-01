<?php

use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Coverage for the linear BudgetPlanState workflow: the full forward/backward chain must be
 * walkable, the stored column value must round-trip as 'active', and legacy readers (the
 * haushaltsplan view) must keep reporting 'final'/'draft'.
 */
uses(DatabaseTransactions::class);

function activeStatePlan(string $state = Draft::class): BudgetPlan
{
    return BudgetPlan::create(['state' => $state]);
}

it('allows the full linear forward chain Draft->Resolved->Approved->Active->Completed', function (): void {
    $plan = activeStatePlan();

    $plan->state->transitionTo(Resolved::class);
    expect($plan->state)->toBeInstanceOf(Resolved::class);

    $plan->state->transitionTo(Approved::class);
    expect($plan->state)->toBeInstanceOf(Approved::class);

    $plan->state->transitionTo(Active::class);
    expect($plan->state)->toBeInstanceOf(Active::class);

    $plan->state->transitionTo(Completed::class);
    expect($plan->state)->toBeInstanceOf(Completed::class);
});

it('allows each backward step of the chain', function (): void {
    $plan = activeStatePlan(Completed::class);

    $plan->state->transitionTo(Active::class);
    expect($plan->state)->toBeInstanceOf(Active::class);

    $plan->state->transitionTo(Approved::class);
    expect($plan->state)->toBeInstanceOf(Approved::class);

    $plan->state->transitionTo(Resolved::class);
    expect($plan->state)->toBeInstanceOf(Resolved::class);

    $plan->state->transitionTo(Draft::class);
    expect($plan->state)->toBeInstanceOf(Draft::class);
});

it('persists and rehydrates state = active', function (): void {
    $plan = activeStatePlan(Active::class);

    $raw = DB::table('budget_plan')->where('id', $plan->id)->value('state');
    expect($raw)->toBe('active');

    $fresh = BudgetPlan::findOrFail($plan->id);
    expect($fresh->state)->toBeInstanceOf(Active::class)
        ->and($fresh->state::$name)->toBe('active');
});

it('reports final for an active plan and draft for a draft plan in the legacy haushaltsplan view', function (): void {
    $fy = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    $active = BudgetPlan::create(['state' => Active::class, 'fiscal_year_id' => $fy->id]);
    $draft = BudgetPlan::create(['state' => Draft::class, 'fiscal_year_id' => $fy->id]);

    expect(DB::table('haushaltsplan')->where('id', $active->id)->value('state'))->toBe('final')
        ->and(DB::table('haushaltsplan')->where('id', $draft->id)->value('state'))->toBe('draft');
});
