<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\TitleNumberer;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function numberer(): TitleNumberer
{
    return resolve(TitleNumberer::class);
}

function emptyPlan(): BudgetPlan
{
    return BudgetPlan::create(['state' => Draft::class]);
}

/** Persist a budget item, controlling only the numbering-relevant fields. */
function numberedItem(BudgetPlan $plan, array $attrs = []): BudgetItem
{
    return BudgetItem::factory()->create(array_merge([
        'budget_plan_id' => $plan->id,
        'budget_type' => BudgetType::EXPENSE,
        'parent_id' => null,
        'is_group' => true,
        'position' => 0,
        'short_name' => null,
    ], $attrs));
}

it('seeds the first root of a type from the budget-type prefix', function (): void {
    $plan = emptyPlan();

    $expenseRoot = numberedItem($plan, ['budget_type' => BudgetType::EXPENSE]);
    $incomeRoot = numberedItem($plan, ['budget_type' => BudgetType::INCOME]);

    expect(numberer()->next($expenseRoot))->toBe('A1');
    expect(numberer()->next($incomeRoot))->toBe('E1');
});

it('continues numbering from the preceding root sibling', function (): void {
    $plan = emptyPlan();
    numberedItem($plan, ['short_name' => 'A1', 'position' => 0]);
    $newRoot = numberedItem($plan, ['short_name' => null, 'position' => 1]);

    expect(numberer()->next($newRoot))->toBe('A2');
});

it('only counts siblings of the same budget type for roots', function (): void {
    $plan = emptyPlan();
    // an income root must not bump the expense numbering and vice versa
    numberedItem($plan, ['budget_type' => BudgetType::INCOME, 'short_name' => 'E1', 'position' => 0]);
    $newExpenseRoot = numberedItem($plan, ['budget_type' => BudgetType::EXPENSE, 'short_name' => null, 'position' => 0]);

    expect(numberer()->next($newExpenseRoot))->toBe('A1');
});

it('hangs the first child off the parent number', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1']);
    $child = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0]);

    expect(numberer()->next($child))->toBe('A1.1');
});

it('increments from the preceding child sibling', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1']);
    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0, 'short_name' => 'A1.1']);
    $child = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 1]);

    expect(numberer()->next($child))->toBe('A1.2');
});

it('continues whatever numeric scheme the preceding sibling uses', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1']);
    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0, 'short_name' => 'Pers-1']);
    $child = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 1]);

    expect(numberer()->next($child))->toBe('Pers-2');
});

it('increments only the last number in a deep multi-level number', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1.2']);
    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0, 'short_name' => 'A1.2.9']);
    $child = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 1]);

    expect(numberer()->next($child))->toBe('A1.2.10');
});

it('preserves zero-padding when incrementing (07 -> 08)', function (): void {
    $plan = emptyPlan();
    numberedItem($plan, ['short_name' => '07', 'position' => 0]);
    $newRoot = numberedItem($plan, ['short_name' => null, 'position' => 1]);

    expect(numberer()->next($newRoot))->toBe('08');
});

it('carries within the padded width and only grows on overflow (09 -> 10, 99 -> 100)', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1']);

    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0, 'short_name' => 'A1.09']);
    $tenth = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 1]);
    expect(numberer()->next($tenth))->toBe('A1.10');

    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 2, 'short_name' => 'A1.99']);
    $hundredth = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 3]);
    expect(numberer()->next($hundredth))->toBe('A1.100');
});

it('falls back to parent numbering when the preceding sibling has no number', function (): void {
    $plan = emptyPlan();
    $parent = numberedItem($plan, ['short_name' => 'A1']);
    numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 0, 'short_name' => 'Personalkosten']);
    $child = numberedItem($plan, ['parent_id' => $parent->id, 'is_group' => false, 'position' => 1]);

    expect(numberer()->next($child))->toBe('A1.1');
});
