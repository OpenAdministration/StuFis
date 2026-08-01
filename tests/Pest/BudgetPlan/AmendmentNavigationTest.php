<?php

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * F4 (OP#581): navigation to/from the amendment editor. Before this, the editor had no back
 * affordance besides the browser button, and its breadcrumb trail skipped straight from the plan
 * index to "Nachtrag bearbeiten" — the amendment's own view was never a step of its own, so there
 * was no way to get "back one level" to the diff/state view via breadcrumbs either.
 */
uses(DatabaseTransactions::class);

function navParentAndAmendment(): array
{
    $parent = BudgetPlan::create(['state' => Active::class, 'organization' => 'AStA']);
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
        'name' => 'Nachtrag Sommerfest',
    ]);

    return [$parent, $amendment];
}

it('shows a back-to-view button in the amendment editor headline', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $amendment] = navParentAndAmendment();

    $html = Livewire::test('pages::budget-plan.amendment-edit', ['plan_id' => $parent->id, 'amendment_id' => $amendment->id])->html();

    expect($html)->toContain(route('budget-plan.view', $amendment->id))
        ->and($html)->toContain(__('budget-plan.amendment.back-to-view'));
});

it('nests the amendment editor\'s breadcrumb under the amendment\'s own view, not the parent plan\'s', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $amendment] = navParentAndAmendment();

    $crumbs = Breadcrumbs::generate('budget-plan.amendment.edit', $parent->id, $amendment->id);
    $titles = $crumbs->pluck('title')->all();
    $urls = $crumbs->pluck('url')->all();

    // Home > Budget-Plans > AStA (parent) > Nachtrag Sommerfest (amendment view) > Nachtrag bearbeiten
    expect($titles)->toContain('Nachtrag Sommerfest')
        ->and($urls)->toContain(route('budget-plan.view', $amendment->id))
        ->and($urls)->toContain(route('budget-plan.view', $parent->id));

    // the amendment's OWN view crumb must come directly before the "editing" leaf crumb
    $amendmentViewIndex = array_search(route('budget-plan.view', $amendment->id), $urls, true);
    expect($amendmentViewIndex)->toBe(count($urls) - 2);
});

it('nests the amendment\'s own view under its parent plan\'s view in the breadcrumb trail', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $amendment] = navParentAndAmendment();

    $crumbs = Breadcrumbs::generate('budget-plan.view', $amendment->id);
    $urls = $crumbs->pluck('url')->all();
    $titles = $crumbs->pluck('title')->all();

    expect($urls)->toContain(route('budget-plan.view', $parent->id))
        ->and($titles)->toContain('Nachtrag Sommerfest');

    // parent plan's crumb comes directly before the amendment's own crumb
    $parentIndex = array_search(route('budget-plan.view', $parent->id), $urls, true);
    expect($parentIndex)->toBe(count($urls) - 2);
});
