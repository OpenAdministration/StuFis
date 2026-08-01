<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * The legacy haushaltsplan/haushaltsgruppen/haushaltstitel tables are now read-only VIEWS over
 * budget_plan/budget_item (see 2026_07_02_..._swap_legacy_budget_tables_for_views). These tests
 * lock in the view contract the legacy PHP app still relies on.
 */
uses(DatabaseTransactions::class);

/** A published plan with a fiscal year covering today, so it appears in the haushaltsplan view. */
function viewPlan(string $state = Active::class): BudgetPlan
{
    $fy = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);

    return BudgetPlan::create(['fiscal_year_id' => $fy->id, 'state' => $state]);
}

function viewItem(BudgetPlan $plan, array $attrs): BudgetItem
{
    return BudgetItem::create(array_merge([
        'budget_plan_id' => $plan->id,
        'parent_id' => null,
        'is_group' => false,
        'budget_type' => BudgetType::EXPENSE,
        'position' => 0,
        'value' => Money::EUR(0),
    ], $attrs));
}

it('maps a nested leaf to its parent group', function (): void {
    $plan = viewPlan();
    $group = viewItem($plan, ['is_group' => true, 'name' => 'Gruppe', 'budget_type' => BudgetType::EXPENSE]);
    $leaf = viewItem($plan, ['parent_id' => $group->id, 'name' => 'Titel', 'short_name' => 'A1.1', 'value' => Money::EUR(300)]);

    // the group shows up as a haushaltsgruppen row; EXPENSE (-1) maps to legacy type 1
    $g = DB::table('haushaltsgruppen')->where('id', $group->id)->sole();
    expect($g->hhp_id)->toBe($plan->id)
        ->and($g->gruppen_name)->toBe('Gruppe')
        ->and((int) $g->type)->toBe(1);

    // the leaf points at its parent group
    $t = DB::table('haushaltstitel')->where('id', $leaf->id)->sole();
    expect((int) $t->hhpgruppen_id)->toBe($group->id)
        ->and($t->titel_name)->toBe('Titel')
        ->and($t->titel_nr)->toBe('A1.1')
        ->and((float) $t->value)->toBe(3.0);
});

it('gives a root-level leaf a phantom group and points it at itself', function (): void {
    $plan = viewPlan();
    // INCOME (1) maps to legacy type 0
    $leaf = viewItem($plan, ['name' => 'Wurzeltitel', 'short_name' => 'E1', 'budget_type' => BudgetType::INCOME, 'value' => Money::EUR(500)]);

    // a phantom group is synthesized, reusing the leaf's own id
    $g = DB::table('haushaltsgruppen')->where('id', $leaf->id)->sole();
    expect($g->hhp_id)->toBe($plan->id)
        ->and($g->gruppen_name)->toBe('Wurzeltitel')
        ->and((int) $g->type)->toBe(0);

    // the title's hhpgruppen_id points back at that phantom group (its own id), so the legacy join holds
    $t = DB::table('haushaltstitel')->where('id', $leaf->id)->sole();
    expect((int) $t->hhpgruppen_id)->toBe($leaf->id);

    $joined = DB::table('haushaltstitel as ht')
        ->join('haushaltsgruppen as hg', 'ht.hhpgruppen_id', '=', 'hg.id')
        ->where('ht.id', $leaf->id)
        ->exists();
    expect($joined)->toBeTrue();
});

it('excludes mount items from both views', function (): void {
    $plan = viewPlan();
    $mount = viewItem($plan, ['name' => 'Mount', 'referenced_plan_id' => $plan->id]);
    // a group that is also a mount must not leak into haushaltsgruppen either
    $groupMount = viewItem($plan, ['is_group' => true, 'name' => 'GroupMount', 'referenced_plan_id' => $plan->id]);

    expect(DB::table('haushaltstitel')->where('id', $mount->id)->exists())->toBeFalse()
        ->and(DB::table('haushaltsgruppen')->where('id', $mount->id)->exists())->toBeFalse()
        ->and(DB::table('haushaltsgruppen')->where('id', $groupMount->id)->exists())->toBeFalse();
});

it('flags an active plan as final and a draft plan as draft', function (): void {
    $active = viewPlan(Active::class);
    $draft = viewPlan(Draft::class);

    $row = DB::table('haushaltsplan')->where('id', $active->id)->sole();
    expect($row->state)->toBe('final')
        ->and($row->von)->not->toBeNull()
        ->and($row->bis)->not->toBeNull();

    // a draft plan is still in the view (INNER JOIN on fiscal year is satisfied) but flagged draft
    expect(DB::table('haushaltsplan')->where('id', $draft->id)->value('state'))->toBe('draft');
});

it('hides a plan without a fiscal year from the haushaltsplan view', function (): void {
    $plan = BudgetPlan::create(['state' => Active::class]); // no fiscal_year_id

    expect(DB::table('haushaltsplan')->where('id', $plan->id)->exists())->toBeFalse();
});

/**
 * Amendment-awareness (OP#581, 2026_08_01_..._nachtragshaushaltsplan): an amendment never
 * surfaces as its own plan, and its as-yet-unapplied additions/deletions don't leak into the
 * parent plan's legacy rows.
 */
function viewAmendmentOf(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('never lists a draft amendment in haushaltsplan, while its parent stays visible exactly once', function (): void {
    $parent = viewPlan();
    $amendment = viewAmendmentOf($parent);

    expect(DB::table('haushaltsplan')->where('id', $amendment->id)->exists())->toBeFalse()
        ->and(DB::table('haushaltsplan')->where('id', $parent->id)->count())->toBe(1);
});

it('hides amendment-owned items (drafted additions, and parked deletions) from haushaltstitel/haushaltsgruppen', function (): void {
    $parent = viewPlan();
    $amendment = viewAmendmentOf($parent);

    // a drafted addition: a real budget_item already living under the amendment plan
    $addedGroup = viewItem($amendment, ['is_group' => true, 'name' => 'Neue Gruppe']);
    $addedLeaf = viewItem($amendment, ['name' => 'Neuer Titel', 'short_name' => 'A9.1', 'value' => Money::EUR(100)]);

    // a parked deletion: an item AmendmentApplier already re-homed onto the amendment plan
    $parkedLeaf = viewItem($amendment, ['name' => 'Geparkt', 'short_name' => 'A9.2']);

    expect(DB::table('haushaltsgruppen')->where('id', $addedGroup->id)->exists())->toBeFalse()
        ->and(DB::table('haushaltstitel')->where('id', $addedLeaf->id)->exists())->toBeFalse()
        ->and(DB::table('haushaltstitel')->where('id', $parkedLeaf->id)->exists())->toBeFalse();
});

it('shows modified values, applied additions, but not parked deletions, once an amendment has applied', function (): void {
    $parent = viewPlan();
    $amendment = viewAmendmentOf($parent);
    $leaf = viewItem($parent, ['name' => 'Titel', 'short_name' => 'A1.1', 'value' => Money::EUR(300)]);

    // simulate AmendmentApplier's effects directly at the row level: a modify writes the new
    // value in place, an add/delete re-homes budget_plan_id between amendment <-> parent
    $leaf->update(['value' => Money::EUR(500)]);
    $appliedAddition = viewItem($parent, ['name' => 'Angewandt', 'short_name' => 'A1.2', 'value' => Money::EUR(50)]);
    $parkedDeletion = viewItem($amendment, ['name' => 'Gestrichen', 'short_name' => 'A1.3']);

    $t = DB::table('haushaltstitel')->where('id', $leaf->id)->sole();
    expect((float) $t->value)->toBe(5.0)
        ->and(DB::table('haushaltstitel')->where('id', $appliedAddition->id)->exists())->toBeTrue()
        ->and(DB::table('haushaltstitel')->where('id', $parkedDeletion->id)->exists())->toBeFalse();
});

it('keeps the phantom-group logic for root leaves intact when an unrelated amendment exists', function (): void {
    $parent = viewPlan();
    viewAmendmentOf($parent); // present, but must not interfere
    $leaf = viewItem($parent, ['name' => 'Wurzeltitel', 'short_name' => 'E1', 'budget_type' => BudgetType::INCOME, 'value' => Money::EUR(500)]);

    $g = DB::table('haushaltsgruppen')->where('id', $leaf->id)->sole();
    expect($g->hhp_id)->toBe($parent->id)
        ->and($g->gruppen_name)->toBe('Wurzeltitel');
});
