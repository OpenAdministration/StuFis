<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function createEmptyPlan(): BudgetPlan
{
    return BudgetPlan::create(['state' => Draft::class]);
}

it('renders and can add groups and items, save metadata, and prevent deleting non-empty groups', function (): void {
    $this->actingAs(budgetManager());

    $plan = createEmptyPlan();

    // start component
    $lw = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id]);
    $lw->assertSuccessful();

    // add income group -> should add group and one child budget automatically
    $lw->call('addGroup', BudgetType::INCOME)
        ->assertHasNoErrors();

    $incomeRoot = BudgetItem::where('budget_plan_id', $plan->id)
        ->whereNull('parent_id')
        ->where('budget_type', BudgetType::INCOME)
        ->first();
    expect($incomeRoot)->not->toBeNull();
    expect($incomeRoot->is_group)->toBeTrue();
    expect($incomeRoot->children()->count())->toBe(1);

    // add extra budget line to income group with specific value
    $lw->call('addBudget', $incomeRoot->id, 12.34)
        ->assertHasNoErrors();
    $child = $incomeRoot->children()->orderByDesc('id')->first();
    expect($child->is_group)->toBeFalse();
    // MoneyDecimalCast stores cents, so ensure amount matches (getAmount() returns a string)
    expect((int) $child->value->getAmount())->toBe(1234);

    // set meta data and save -> redirects to view route
    $fy = FiscalYear::factory()->create();
    $lw->set('organization', 'Test Org')
        ->set('fiscal_year_id', $fy->id)
        ->set('resolution_date', now()->toDateString())
        ->set('approval_date', now()->addDay()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('budget-plan.view', $plan->id));

    $plan->refresh();
    expect($plan->organization)->toBe('Test Org');
    expect($plan->fiscal_year_id)->toBe($fy->id);

    // try to delete a non-empty group (has children) -> refused (toast, no delete)
    $lw = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id]);
    $lw->call('delete', $incomeRoot->id)
        ->assertHasNoErrors();
    expect(BudgetItem::find($incomeRoot->id))->not->toBeNull();
});
