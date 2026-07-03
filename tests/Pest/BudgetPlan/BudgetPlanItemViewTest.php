<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/** A plan with one bookable expense leaf; returns [plan, leaf]. */
function planWithLeaf(): array
{
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $leaf = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);

    return [$plan, $leaf];
}

/**
 * A konto transaction, returned as ['id' => …, 'konto_id' => …]. The konto PK isn't
 * auto-increment, so we set the id explicitly and never read it back off the model.
 */
function itemPayment(): array
{
    $account = BankAccount::factory()->create();
    BankTransaction::factory()->create(['konto_id' => $account->id, 'id' => 1]);

    return ['id' => 1, 'konto_id' => $account->id];
}

/** Book $euros against a leaf, creating a konto transaction so the FK holds. */
function bookItem(BudgetItem $leaf, string $euros, bool $canceled = false, ?array $payment = null): void
{
    $payment ??= itemPayment();
    Booking::create([
        'titel_id' => $leaf->id,
        'user_id' => user()->id,
        'kostenstelle' => 0,
        'zahlung_id' => $payment['id'],
        'zahlung_type' => $payment['konto_id'],
        'beleg_id' => 0,
        'beleg_type' => '',
        'comment' => 'Buchung',
        'value' => $euros,
        'canceled' => $canceled ? 1 : 0,
    ]);
}

it('lists the item\'s non-canceled bookings and links the transaction', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $payment = itemPayment();
    bookItem($leaf, '42', payment: $payment);
    bookItem($leaf, '7', canceled: true); // must not show up

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee($leaf->short_name)
        ->assertSee(__('budget-plan.item.bookings'))
        ->assertSee('42,00')
        ->assertDontSee('7,00')
        ->assertSee(route('bank-account.transaction', [$payment['konto_id'], $payment['id']]), false);
});

it('shows an empty state when the item has no bookings', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.no-bookings'));
});

it('404s when the item does not belong to the plan', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();
    $otherPlan = BudgetPlan::create(['state' => Draft::class]);

    $this->get(route('budget-plan.item.view', [$otherPlan->id, $leaf->id]))
        ->assertNotFound();
});
