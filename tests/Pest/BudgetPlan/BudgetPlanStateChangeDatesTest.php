<?php

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

/**
 * OP#588: resolution_date/approval_date/activation_date are no longer freely editable in
 * ⚡plan-edit or on an amendment's plan-view — they are captured as OPTIONAL fields inside the
 * state-change modal (⚡plan-view's state-modal), offered only for the target state that gives
 * each date its meaning:
 *   - into Resolved  -> resolution_date
 *   - into Approved  -> approval_date, plus activation_date for an amendment
 *   - into Active    -> activation_date for an amendment, only if still unset
 * Never more than one of these groups at once, never on a backward step (only a forward step,
 * per BudgetPlanState::advancesTo(), demands or even offers a date), and a supplied date is
 * persisted in the very same write as the transition itself.
 */
uses(DatabaseTransactions::class);

function stateChangeDatesParent(): BudgetPlan
{
    return BudgetPlan::factory()->create(['state' => Active::class]);
}

function stateChangeDatesAmendment(BudgetPlan $parent, string $state = Draft::class): BudgetPlan
{
    return BudgetPlan::create([
        'state' => $state,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('offers resolution_date, and only resolution_date, when the target state is Resolved', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->html();

    expect($html)->toContain('wire:model="resolution_date"')
        ->and($html)->not->toContain('wire:model="approval_date"')
        ->and($html)->not->toContain('wire:model="activation_date"');
});

it('offers approval_date, but not activation_date, when the target is Approved for an ordinary plan', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Resolved::class]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'approved')
        ->html();

    expect($html)->toContain('wire:model="approval_date"')
        ->and($html)->not->toContain('wire:model="activation_date"')
        ->and($html)->not->toContain('wire:model="resolution_date"');
});

it('offers both approval_date and activation_date, but not resolution_date, when the target is Approved for an amendment', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Resolved::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->html();

    expect($html)->toContain('wire:model="approval_date"')
        ->and($html)->toContain('wire:model="activation_date"')
        ->and($html)->not->toContain('wire:model="resolution_date"');
});

it('offers activation_date when the target is Active for an amendment with none set yet', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Approved::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->html();

    expect($html)->toContain('wire:model="activation_date"');
});

it('does not offer activation_date for Active once the amendment already has one', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Approved::class);
    $amendment->forceFill(['activation_date' => now()])->save();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->html();

    expect($html)->not->toContain('wire:model="activation_date"');
});

it('never offers activation_date for an ordinary (non-amendment) plan, even moving into Active', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Approved::class]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'active')
        ->html();

    expect($html)->not->toContain('wire:model="activation_date"')
        ->and($html)->not->toContain('wire:model="approval_date"')
        ->and($html)->not->toContain('wire:model="resolution_date"');
});

it('persists a supplied resolution_date together with the transition into Resolved', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->set('resolution_date', '2026-03-15')
        ->call('changeState')
        ->assertHasNoErrors();

    $plan->refresh();
    expect($plan->state)->toBeInstanceOf(Resolved::class)
        ->and($plan->resolution_date->format('Y-m-d'))->toBe('2026-03-15');
});

it('persists both approval_date and activation_date together with an amendment reaching Approved', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Resolved::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->set('approval_date', '2026-04-01')
        ->set('activation_date', '2026-05-01')
        ->call('changeState')
        ->assertHasNoErrors();

    $amendment->refresh();
    expect($amendment->state)->toBeInstanceOf(Approved::class)
        ->and($amendment->approval_date->format('Y-m-d'))->toBe('2026-04-01')
        ->and($amendment->activation_date->format('Y-m-d'))->toBe('2026-05-01');
});

it('lets the transition succeed with the date field left blank', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertHasNoErrors();

    $plan->refresh();
    expect($plan->state)->toBeInstanceOf(Resolved::class)
        ->and($plan->resolution_date)->toBeNull();
});

it('shows no date fields, and requires nothing, for a backward transition', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Approved::class]);

    $lw = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved');

    expect($lw->html())->not->toContain('wire:model="resolution_date"')
        ->and($lw->html())->not->toContain('wire:model="approval_date"');

    $lw->call('changeState')->assertHasNoErrors();
    expect($plan->fresh()->state)->toBeInstanceOf(Resolved::class);
});

it('no longer offers the free resolution_date/approval_date inputs on the plan-edit form', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);

    $html = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id])->html();

    expect($html)->not->toContain('resolution_date')
        ->and($html)->not->toContain('approval_date');
});

it('no longer offers free date inputs on an amendment detail view before a target state is picked', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Draft::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->not->toContain('wire:model="resolution_date"')
        ->and($html)->not->toContain('wire:model="approval_date"')
        ->and($html)->not->toContain('wire:model="activation_date"');
});

it('still prefills activation_date from approval_date when an amendment reaches Approved via the modal without one set', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Resolved::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->set('approval_date', '2026-04-01')
        // activation_date deliberately left blank
        ->call('changeState')
        ->assertHasNoErrors();

    $amendment->refresh();
    expect($amendment->activation_date->format('Y-m-d'))->toBe('2026-04-01');
});

it('still lets stufis:apply-due-amendments activate an amendment whose activation_date was set through the modal', function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Resolved::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->set('activation_date', now()->subDay()->toDateString())
        ->call('changeState')
        ->assertHasNoErrors();

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class);

    $this->artisan('stufis:apply-due-amendments')->assertExitCode(0);

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

/**
 * Now that capture lives exclusively in the state-change modal, the three dates need a read-only
 * display somewhere or they'd be set-able but invisible — the amendment's frozen <dl> (approval_date
 * / activation_date) and the ordinary plan's header (resolution_date / approval_date), both with the
 * `d.m.Y` formatting and the `—` placeholder the rest of the app already uses for an unset date.
 */
it("shows an amendment's approval_date and activation_date read-only on its plan-view, with a placeholder when unset", function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Resolved::class);
    $amendment->forceFill(['approval_date' => '2026-04-01', 'activation_date' => '2026-05-01'])->save();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->toContain('01.04.2026')
        ->and($html)->toContain('01.05.2026');
});

it("shows a — placeholder for an amendment's unset approval_date/activation_date", function (): void {
    $this->actingAs(budgetManager());
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Draft::class);
    expect($amendment->approval_date)->toBeNull()->and($amendment->activation_date)->toBeNull();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    // the dl's <dd> renders the literal '—' fallback with no formatted date around it — a bare
    // assertSee('—') would also pass on unrelated em-dashes elsewhere on the page, so this pins it
    // to the exact markup the read-only dl produces, twice (approval_date and activation_date)
    expect(substr_count($html, '<dd>—</dd>'))->toBeGreaterThanOrEqual(2);
});

it("shows an ordinary plan's resolution_date and approval_date read-only in its header", function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Resolved::class]);
    $plan->forceFill(['resolution_date' => '2026-02-10', 'approval_date' => '2026-03-20'])->save();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->toContain('10.02.2026')
        ->and($html)->toContain('20.03.2026');
});

it("shows a — placeholder for an ordinary plan's unset resolution_date/approval_date", function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);
    expect($plan->resolution_date)->toBeNull()->and($plan->approval_date)->toBeNull();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->toContain(__('budget-plan.edit.resolution-date').': —')
        ->and($html)->toContain(__('budget-plan.edit.approval-date').': —');
});

it('shows no wire:model date input anywhere outside the state-change modal, for either plan type', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $parent = stateChangeDatesParent();
    $amendment = stateChangeDatesAmendment($parent, Draft::class);

    foreach ([$plan->id, $amendment->id] as $planId) {
        // default render: no target state picked yet, so the modal itself shows none of its date
        // fields either — the assertion covers the whole page, not just the read-only display
        $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $planId])->html();

        expect($html)->not->toContain('wire:model="resolution_date"')
            ->and($html)->not->toContain('wire:model="approval_date"')
            ->and($html)->not->toContain('wire:model="activation_date"')
            ->and($html)->not->toContain('wire:model.live.blur="resolution_date"')
            ->and($html)->not->toContain('wire:model.live.blur="approval_date"')
            ->and($html)->not->toContain('wire:model.live.blur="activation_date"');
    }

    $editHtml = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id])->html();
    expect($editHtml)->not->toContain('wire:model.live.blur="resolution_date"')
        ->and($editHtml)->not->toContain('wire:model.live.blur="approval_date"');
});
