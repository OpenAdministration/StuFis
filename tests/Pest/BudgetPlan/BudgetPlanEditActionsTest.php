<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Draft;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function draftPlan(): BudgetPlan
{
    return BudgetPlan::create(['state' => Draft::class]);
}

function editComponent(BudgetPlan $plan)
{
    return Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id]);
}

it('only lets budget officers open the edit page', function (): void {
    $plan = draftPlan();

    $this->actingAs(user());
    editComponent($plan)->assertForbidden();

    $this->actingAs(budgetManager());
    editComponent($plan)->assertSuccessful();
});

it('adds a group of the requested budget type with an auto-numbered Titelnummer', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlan();

    editComponent($plan)->call('addGroup', BudgetType::EXPENSE)->assertHasNoErrors();

    $root = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->first();

    expect($root)->not->toBeNull()
        ->and($root->budget_type)->toBe(BudgetType::EXPENSE)
        ->and($root->short_name)->toBe('A1')
        ->and($root->orderedChildren()->first()->short_name)->toBe('A1.1');
});

it('mirrors a root to the opposite side with zeroed values via copy inverse', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlan();
    $lw = editComponent($plan);

    $lw->call('addGroup', BudgetType::EXPENSE);
    $expenseRoot = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->first();
    // a second child carrying a real value, to prove it gets zeroed on the mirror
    $lw->call('addBudget', $expenseRoot->id, 10.0)->assertHasNoErrors();

    $lw->call('copyInverse', $expenseRoot->id)->assertHasNoErrors();

    $incomeRoot = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::INCOME)->first();

    expect($incomeRoot)->not->toBeNull()
        ->and($incomeRoot->short_name)->toBe('E1')
        ->and((int) $incomeRoot->value->getAmount())->toBe(0)
        ->and($incomeRoot->orderedChildren()->count())->toBe($expenseRoot->orderedChildren()->count());

    // source kept its value; every mirrored child is zeroed
    expect($expenseRoot->orderedChildren()->where('value', '>', 0)->count())->toBeGreaterThan(0);
    $incomeRoot->orderedChildren->each(fn (BudgetItem $child) => expect((int) $child->value->getAmount())->toBe(0));
});

it('only mirrors root items (copy inverse is a no-op for children)', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlan();
    $lw = editComponent($plan);

    $lw->call('addGroup', BudgetType::EXPENSE);
    $child = BudgetItem::where('budget_plan_id', $plan->id)->whereNotNull('parent_id')->first();

    $lw->call('copyInverse', $child->id)->assertHasNoErrors();

    expect(BudgetItem::where('budget_plan_id', $plan->id)->where('budget_type', BudgetType::INCOME)->count())->toBe(0);
});

it('duplicates an item (and its subtree) via copy', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlan();
    $lw = editComponent($plan);

    $lw->call('addGroup', BudgetType::EXPENSE);
    $root = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->first();

    $rootsBefore = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->count();

    $lw->call('copyItem', $root->id)->assertHasNoErrors();

    expect(BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->count())->toBe($rootsBefore + 1);
});

it('blocks deleting a group with children but allows deleting a leaf', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlan();
    $lw = editComponent($plan);

    $lw->call('addGroup', BudgetType::EXPENSE);
    $root = BudgetItem::where('budget_plan_id', $plan->id)->whereNull('parent_id')
        ->where('budget_type', BudgetType::EXPENSE)->first();
    $leaf = $root->orderedChildren()->first();

    // group still has a child -> delete refused
    $lw->call('delete', $root->id)->assertHasNoErrors();
    expect(BudgetItem::find($root->id))->not->toBeNull();

    // leaf -> deleted
    $lw->call('delete', $leaf->id)->assertHasNoErrors();
    expect(BudgetItem::find($leaf->id))->toBeNull();
});

it('derives bookability and wires the bookings relation per item kind', function (): void {
    $plan = draftPlan();
    $group = BudgetItem::factory()->create(['budget_plan_id' => $plan->id, 'is_group' => true]);
    $leaf = BudgetItem::factory()->create(['budget_plan_id' => $plan->id, 'is_group' => false]);
    $mount = BudgetItem::factory()->create(['budget_plan_id' => $plan->id, 'is_group' => false, 'referenced_plan_id' => $plan->id]);

    expect($leaf->isBookable())->toBeTrue()
        ->and($group->isBookable())->toBeFalse()
        ->and($mount->isBookable())->toBeFalse()
        // no bookings yet, and the relation is wired to the booking table by titel_id
        ->and($leaf->hasBookings())->toBeFalse()
        ->and($leaf->bookings()->getRelated())->toBeInstanceOf(Booking::class)
        ->and($leaf->bookings()->getForeignKeyName())->toBe('titel_id');
});
