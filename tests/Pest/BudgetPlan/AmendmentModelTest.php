<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Model-level basics for the Nachtragshaushaltsplan (amendment) relation: isAmendment()/
 * parentPlan()/amendments() round-trip, the original() scope that keeps amendments out of every
 * place a free-standing plan is expected (uniqueness checks, the mount picker, the plan index),
 * and BudgetPlan::newest().
 */
uses(DatabaseTransactions::class);

function originalPlan(array $attrs = []): BudgetPlan
{
    return BudgetPlan::create(array_merge(['state' => Active::class], $attrs));
}

function draftAmendmentOf(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('reports isAmendment() true iff parent_plan_id is set and round-trips parentPlan()/amendments()', function (): void {
    $parent = originalPlan();
    $amendment = draftAmendmentOf($parent);

    expect($parent->isAmendment())->toBeFalse()
        ->and($parent->parentPlan)->toBeNull()
        ->and($amendment->isAmendment())->toBeTrue()
        ->and($amendment->parentPlan->id)->toBe($parent->id)
        ->and($parent->amendments()->pluck('id')->all())->toBe([$amendment->id]);
});

it('excludes amendments from the original() scope and BudgetPlan::newest()', function (): void {
    $parent = originalPlan();
    $amendment = draftAmendmentOf($parent);

    $originalIds = BudgetPlan::query()->original()->pluck('id')->all();
    expect($originalIds)->toContain($parent->id)
        ->and($originalIds)->not->toContain($amendment->id);

    // the amendment is the most-recently-created row, but newest() must never surface it
    expect(BudgetPlan::newest()->id)->not->toBe($amendment->id);
});

it('lets an amendment share organization+fiscal year with its parent without tripping organizationTaken()', function (): void {
    $parent = originalPlan(['organization' => 'AStA']);
    draftAmendmentOf($parent); // same organization, same fiscal_year_id as $parent

    expect(BudgetPlan::organizationTaken('AStA', $parent->fiscal_year_id, ignoreId: $parent->id))->toBeFalse();
});

it('still flags a second ORIGINAL plan with the same organization+fiscal year as taken', function (): void {
    $parent = originalPlan(['organization' => 'AStA']);

    expect(BudgetPlan::organizationTaken('AStA', $parent->fiscal_year_id))->toBeTrue();
});

it('does not offer amendments as mount targets', function (): void {
    $this->actingAs(budgetManager());
    $mountable = originalPlan(['organization' => 'Mountable']);
    // Draft (not the originalPlan() default of Active) — F8 (OP#581) now guards ⚡plan-edit access
    // by state, and this test opens $parent's OWN editor, so it must stay in an editable state
    $parent = originalPlan(['fiscal_year_id' => $mountable->fiscal_year_id, 'state' => Draft::class]);
    draftAmendmentOf($parent); // an amendment sharing the same fiscal year as $mountable

    $lw = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $parent->id]);
    // trigger the mount picker query directly against a childless item so it doesn't early-return
    $item = BudgetItem::factory()->create(['budget_plan_id' => $parent->id, 'is_group' => false]);
    $lw->call('openMountPicker', $item->id);

    $candidateIds = collect($lw->get('mount_candidates'))->pluck('id')->all();
    expect($candidateIds)->toContain($mountable->id)
        ->and($candidateIds)->not->toContain($parent->id);
    // no amendment id leaked in either, since amendments never carry their own fiscal_year_id row here
    $amendmentIds = BudgetPlan::query()->whereNotNull('parent_plan_id')->pluck('id')->all();
    expect(array_intersect($candidateIds, $amendmentIds))->toBe([]);
});

/**
 * F3 (OP#581): an amendment has no organization of its own (it inherits its parent's) and, before
 * this, no name either — so label() is the single place the "Nachtrag vom {date}" fallback lives,
 * instead of scattering it across every view that lists amendments.
 */
it('labels an amendment by its optional name, falling back to "Nachtrag vom {created_at}"', function (): void {
    $parent = originalPlan(['organization' => 'AStA']);
    $named = draftAmendmentOf($parent);
    $named->update(['name' => 'Nachtrag Sommerfest']);
    $unnamed = draftAmendmentOf($parent);

    expect($named->label())->toBe('Nachtrag Sommerfest')
        ->and($unnamed->label())->toBe(__('budget-plan.amendment.unnamed-fallback', ['date' => $unnamed->created_at->format('d.m.Y')]))
        // an original plan is unaffected — it still uses organization, never the amendment fallback
        ->and($parent->label())->toBe('AStA');
});

it('shows the amendment label (name or fallback) on the parent plan-view\'s open-amendments list', function (): void {
    $this->actingAs(user());
    $parent = originalPlan(['organization' => 'AStA']);
    $named = draftAmendmentOf($parent);
    $named->update(['name' => 'Nachtrag Sommerfest']);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $parent->id])
        ->assertSee('Nachtrag Sommerfest');
});

it('does not list amendments as free-standing plans on the plan index (only nested under their parent)', function (): void {
    $this->actingAs(user());
    $fy = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    $parent = originalPlan(['organization' => 'Parent Org', 'fiscal_year_id' => $fy->id]);
    $amendment = draftAmendmentOf($parent);

    // the relation the index controller/view iterates over as "the fiscal year's plans" must
    // never surface the amendment as one of its own top-level rows
    expect($fy->fresh()->budgetPlans->pluck('id')->all())->toBe([$parent->id]);

    // it IS reachable from the page (nested under its parent, badge-marked), just not doubly
    // listed as a free-standing plan
    $this->get(route('budget-plan.index'))
        ->assertOk()
        ->assertSee('Parent Org')
        ->assertSeeInOrder(['Parent Org', __('budget-plan.amendment.badge')]);

    expect($amendment->parent_plan_id)->toBe($parent->id);
});
