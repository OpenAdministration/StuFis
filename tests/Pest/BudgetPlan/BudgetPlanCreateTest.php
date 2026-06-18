<?php

use App\Http\Controllers\BudgetPlanController;
use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetPlanState;
use App\Models\Enums\BudgetType;

beforeEach(function (): void {
    // enable dev routes where budget plan routes live
    config()->set('stufis.features', 'dev');
});

it('creates a new draft budget plan with default groups and items and redirects to edit', function (): void {
    $this->actingAs(user());

    expect(config('stufis.features'))->toBe('dev');

    $response = $this->get(action([BudgetPlanController::class, 'create']));

    // should redirect to edit route with created id
    $plan = BudgetPlan::orderByDesc('id')->first();
    $response->assertRedirect(route('budget-plan.edit', ['plan_id' => $plan->id]));

    // plan exists and is draft
    expect($plan)->not->toBeNull();
    expect($plan->state)->toBe(BudgetPlanState::DRAFT);

    // two root groups created: income and expense
    $rootIncome = $plan->rootBudgetItems()->where('budget_type', BudgetType::INCOME)->get();
    $rootExpense = $plan->rootBudgetItems()->where('budget_type', BudgetType::EXPENSE)->get();

    expect($rootIncome->count())->toBe(1);
    expect($rootExpense->count())->toBe(1);

    // each root group gets one child budget item
    /** @var BudgetItem $inGroup */
    $inGroup = $rootIncome->first();
    /** @var BudgetItem $outGroup */
    $outGroup = $rootExpense->first();

    expect($inGroup->is_group)->toBeTrue();
    expect($outGroup->is_group)->toBeTrue();

    expect($inGroup->children()->count())->toBe(1);
    expect($outGroup->children()->count())->toBe(1);

    // check default short_name format as implemented in controller (E1/A1 and .1 child)
    expect($inGroup->short_name)->toBe('E1');
    expect($outGroup->short_name)->toBe('A1');

    expect($inGroup->children()->first()->short_name)->toBe('E1.1');
    expect($outGroup->children()->first()->short_name)->toBe('A1.1');
})->todo('budget-plan is a dev-only feature; enable once it graduates out of dev (preview/stable)');
