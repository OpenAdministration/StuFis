<?php

use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Cknow\Money\Money;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

/**
 * `stufis:apply-due-amendments` — the daily job that activates an Approved amendment once its
 * activation_date has arrived, without anyone clicking "aktivieren" manually. Mirrors area F of
 * the OP#581 test plan.
 */
uses(DatabaseTransactions::class);

function nhhpDueParent(): BudgetPlan
{
    return BudgetPlan::factory()->create(['state' => Active::class]);
}

function nhhpDueAmendment(BudgetPlan $parent, string $state = Draft::class): BudgetPlan
{
    return BudgetPlan::create([
        'state' => $state,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('activates an Approved amendment whose activation_date is today (past-or-present) under an Active parent', function (): void {
    $parent = nhhpDueParent();
    $amendment = nhhpDueAmendment($parent, Resolved::class);
    $amendment->state->transitionTo(Approved::class);
    $amendment->forceFill(['activation_date' => now()->subDay()])->save();

    $this->artisan('stufis:apply-due-amendments')->assertExitCode(0);

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

it('leaves an Approved amendment with a future activation_date untouched', function (): void {
    $parent = nhhpDueParent();
    $amendment = nhhpDueAmendment($parent, Resolved::class);
    $amendment->state->transitionTo(Approved::class);
    $amendment->forceFill(['activation_date' => now()->addWeek()])->save();

    $this->artisan('stufis:apply-due-amendments')->assertExitCode(0);

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class);
});

it('leaves a Draft amendment with a past date untouched (state machine respected)', function (): void {
    $parent = nhhpDueParent();
    $amendment = nhhpDueAmendment($parent, Draft::class);
    // activation_date isn't even normally settable on a Draft, but force it to prove the command
    // still won't touch anything that isn't Approved
    $amendment->forceFill(['activation_date' => now()->subDay()])->save();

    $this->artisan('stufis:apply-due-amendments')->assertExitCode(0);

    expect($amendment->fresh()->state)->toBeInstanceOf(Draft::class);
});

it('reports failure and exits non-zero for a conflicting due amendment, while still processing the others', function (): void {
    $parent = nhhpDueParent();
    $group = $parent->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Ausgaben', 'value' => Money::EUR(10000),
    ]);
    $leaf = $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);

    // a conflicting amendment: modify drafted against the item's original value, then the live
    // value is changed directly, so applying it will hit a stale-item conflict
    $conflicting = nhhpDueAmendment($parent, Resolved::class);
    $conflicting->state->transitionTo(Approved::class);
    $conflicting->forceFill(['activation_date' => now()->subDay()])->save();
    BudgetItemChange::create([
        'budget_plan_id' => $conflicting->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'diff' => ['value' => ['from' => 10000, 'to' => 20000]],
    ]);
    $leaf->update(['value' => Money::EUR(30000)]); // drifted since drafting

    // a clean, unrelated due amendment on a second parent
    $otherParent = nhhpDueParent();
    $clean = nhhpDueAmendment($otherParent, Resolved::class);
    $clean->state->transitionTo(Approved::class);
    $clean->forceFill(['activation_date' => now()->subDay()])->save();

    $this->artisan('stufis:apply-due-amendments')->assertExitCode(1);

    expect($conflicting->fresh()->state)->toBeInstanceOf(Approved::class)
        ->and((int) $leaf->fresh()->value->getAmount())->toBe(30000)
        ->and($clean->fresh()->state)->toBeInstanceOf(Active::class);
});

it('prefills activation_date from approval_date on Approved when unset, and preserves an explicitly pre-set date', function (): void {
    $parent = nhhpDueParent();

    $unset = nhhpDueAmendment($parent, Resolved::class);
    $unset->forceFill(['approval_date' => now()->subDays(3)])->save();
    $unset->state->transitionTo(Approved::class);
    expect($unset->fresh()->activation_date->isSameDay($unset->fresh()->approval_date))->toBeTrue();

    $preset = nhhpDueAmendment($parent, Resolved::class);
    $explicitDate = now()->addDays(10)->startOfDay();
    $preset->forceFill(['activation_date' => $explicitDate])->save();
    $preset->state->transitionTo(Approved::class);
    expect($preset->fresh()->activation_date->isSameDay($explicitDate))->toBeTrue();
});

it('is registered in the schedule to run daily', function (): void {
    Artisan::call('schedule:list');

    /** @var Schedule $schedule */
    $schedule = resolve(Schedule::class);
    $events = collect($schedule->events())->filter(fn ($event): bool => str_contains($event->command ?? '', 'stufis:apply-due-amendments'));

    expect($events)->not->toBeEmpty();
    expect($events->first()->expression)->toBe('0 4 * * *');
});
