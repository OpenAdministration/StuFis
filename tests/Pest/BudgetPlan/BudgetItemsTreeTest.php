<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Regression coverage for BudgetPlan::budgetItemsTree() (OP#581): treeOf()'s $constraint only
 * seeds the roots, so without a plan filter on the recursive descent too, a base plan's tree
 * would pull in items that live under an amendment (or vice versa for a parked deletion rehomed
 * onto the amendment) just because they're parented under one of its own items. See the fix's
 * comment in BudgetPlan::budgetItemsTree() for the withRecursiveQueryConstraint() mechanics.
 */
uses(DatabaseTransactions::class);

it('leaks a nested amendment-added item into the base tree after revert', function (): void {
    $base = BudgetPlan::factory()->create(['state' => Active::class]);
    $group = BudgetItem::factory()->create([
        'budget_plan_id' => $base->id, 'is_group' => true, 'parent_id' => null,
        'budget_type' => BudgetType::EXPENSE, 'short_name' => 'G1', 'position' => 1,
    ]);
    $amendment = BudgetPlan::factory()->create([
        'state' => Active::class,
        'parent_plan_id' => $base->id,
    ]);
    $added = BudgetItem::factory()->create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $group->id,
        'budget_type' => BudgetType::EXPENSE, 'short_name' => 'NEW', 'position' => 1, 'is_group' => false,
    ]);

    $tree = $base->budgetItemsTree(BudgetType::EXPENSE);
    expect($tree->pluck('id'))->not->toContain($added->id);
});

it('prunes a whole subtree hanging below an item excluded for belonging to another plan', function (): void {
    $base = BudgetPlan::factory()->create(['state' => Active::class]);
    $group = BudgetItem::factory()->create([
        'budget_plan_id' => $base->id, 'is_group' => true, 'parent_id' => null,
        'budget_type' => BudgetType::EXPENSE, 'short_name' => 'G1', 'position' => 1,
    ]);
    $amendment = BudgetPlan::factory()->create([
        'state' => Active::class,
        'parent_plan_id' => $base->id,
    ]);
    // the amendment's own group, nested under the base plan's group ...
    $foreignGroup = BudgetItem::factory()->create([
        'budget_plan_id' => $amendment->id, 'is_group' => true, 'parent_id' => $group->id,
        'budget_type' => BudgetType::EXPENSE, 'short_name' => 'NEWGRP', 'position' => 1,
    ]);
    // ... with its own grandchild leaf, also owned by the amendment
    $grandchild = BudgetItem::factory()->create([
        'budget_plan_id' => $amendment->id, 'is_group' => false, 'parent_id' => $foreignGroup->id,
        'budget_type' => BudgetType::EXPENSE, 'short_name' => 'NEWGRP.1', 'position' => 1,
    ]);

    $ids = $base->budgetItemsTree(BudgetType::EXPENSE)->pluck('id');

    // a naive post-filter of the flat result would keep $grandchild (its own budget_plan_id was
    // never checked against $foreignGroup's exclusion) — the recursive descent itself must never
    // walk past the excluded node in the first place, so there's no orphan to promote
    expect($ids)->toContain($group->id)
        ->and($ids)->not->toContain($foreignGroup->id)
        ->and($ids)->not->toContain($grandchild->id);
});
