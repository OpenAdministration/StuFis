<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Active;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The booking history inside the legacy embed. Its Titel column links out to the modern Titel
 * view (⚡item-view) rather than staying inside the embed — booking.titel_id IS budget_item.id
 * since the legacy budget tables became views, so the link needs no translation step.
 */
uses(DatabaseTransactions::class);

it('links the Titel column of the booking history to the modern budget item view', function (): void {
    $actor = budgetManager();
    $this->actingAs($actor);

    $fy = FiscalYear::create(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]);
    $plan = BudgetPlan::create(['fiscal_year_id' => $fy->id, 'state' => Active::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A', 'name' => 'Sachkosten', 'value' => Money::EUR(0),
    ]);
    $leaf = $plan->budgetItems()->create([
        'parent_id' => $group->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);

    $account = BankAccount::factory()->create();
    // konto's PK is the composite (id, konto_id), so the id is not auto-incremented
    BankTransaction::factory()->create(['id' => 1, 'konto_id' => $account->id]);

    Booking::create([
        'titel_id' => $leaf->id,
        'zahlung_id' => 1,
        'zahlung_type' => $account->id,
        // the history renderer switches on beleg_type and rejects anything it doesn't know; the
        // beleg/auslagen joins are LEFT, so no receipt rows are needed to render the row
        'beleg_type' => 'belegposten',
        'beleg_id' => 0,
        'kostenstelle' => 0,
        'user_id' => $actor->id,
        'comment' => 'Testbuchung',
        'value' => 50,
        'canceled' => 0,
    ]);

    $this->get(route('legacy.booking.history', ['hhp_id' => $plan->id]))
        ->assertOk()
        ->assertSee(route('budget-plan.item.view', ['plan_id' => $plan->id, 'item_id' => $leaf->id]), false);
});
