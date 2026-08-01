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
 * Regression coverage for the Published -> Active state rename (OP#581 prep): the linear
 * BudgetPlanState workflow must still allow the full forward/backward chain, the stored
 * column value must round-trip as 'active' (not 'published'), the data migration must have
 * rewritten any existing 'published' rows, and legacy readers (haushaltsplan view) must keep
 * reporting 'final'/'draft' exactly as before.
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

it('persists and rehydrates state = active (not published)', function (): void {
    $plan = activeStatePlan(Active::class);

    $raw = DB::table('budget_plan')->where('id', $plan->id)->value('state');
    expect($raw)->toBe('active');

    $fresh = BudgetPlan::findOrFail($plan->id);
    expect($fresh->state)->toBeInstanceOf(Active::class)
        ->and($fresh->state::$name)->toBe('active');
});

it('rewrites legacy published rows to active via the data migration, no published literal reachable', function (): void {
    // simulate a pre-migration row by writing the raw literal directly, bypassing the enum cast
    $plan = activeStatePlan(Draft::class);
    DB::table('budget_plan')->where('id', $plan->id)->update(['state' => 'published']);

    (new (require base_path('database/migrations/2026_08_01_000000_rename_budget_plan_published_state_to_active.php')))->up();

    expect(DB::table('budget_plan')->where('id', $plan->id)->value('state'))->toBe('active')
        ->and(DB::table('budget_plan')->where('state', 'published')->exists())->toBeFalse();
});

it('reports final for an active plan and draft for a draft plan in the legacy haushaltsplan view', function (): void {
    $fy = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    $active = BudgetPlan::create(['state' => Active::class, 'fiscal_year_id' => $fy->id]);
    $draft = BudgetPlan::create(['state' => Draft::class, 'fiscal_year_id' => $fy->id]);

    expect(DB::table('haushaltsplan')->where('id', $active->id)->value('state'))->toBe('final')
        ->and(DB::table('haushaltsplan')->where('id', $draft->id)->value('state'))->toBe('draft');
});
