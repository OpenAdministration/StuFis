<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Active;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * @return array{0: BankAccount, 1: BankTransaction, 2: BudgetItem}
 */
function transactionWithBooking(int $userId): array
{
    $account = BankAccount::factory()->create();
    // konto's PK is the composite (id, konto_id), so the id is not auto-incremented: it has to be
    // supplied (unique within this freshly created account), and the created model reads back a 0
    // lastInsertId — hence the re-read, so the returned transaction actually carries its id.
    BankTransaction::factory()->create(['id' => 1, 'konto_id' => $account->id]);
    $transaction = BankTransaction::where(['id' => 1, 'konto_id' => $account->id])->firstOrFail();

    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $item = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);

    Booking::create([
        'titel_id' => $item->id,
        // a booking is tied to its payment by the (zahlung_id, zahlung_type) pair, where the type
        // is the account id — see BankTransaction::bookings()
        'zahlung_id' => $transaction->id,
        'zahlung_type' => $account->id,
        // the legacy booking table has no defaults on these, so every one has to be spelled out
        'kostenstelle' => 0,
        'beleg_id' => 0,
        'beleg_type' => '',
        'user_id' => $userId,
        'comment' => 'Testbuchung',
        'value' => 50,
        'canceled' => 0,
    ]);

    return [$account, $transaction, $item];
}

/**
 * The transaction page is a modern Blade page, not part of the legacy embed, so its Titel link
 * belongs on the modern Titel view rather than the legacy HHP page.
 */
it('links a booking to the modern budget item view, not the legacy one', function (): void {
    $this->actingAs($actor = cashOfficer());
    [$account, $transaction, $item] = transactionWithBooking($actor->id);

    $this->get(route('bank-account.transaction', [$account->id, $transaction->id]))
        ->assertOk()
        ->assertSee('A1 Material')
        ->assertSee(route('budget-plan.item.view', [$item->budget_plan_id, $item->id]), false)
        ->assertDontSee(route('legacy.budget-item', ['titel_id' => $item->id]), false);
});
