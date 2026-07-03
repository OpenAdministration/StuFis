<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\Models\Legacy\Expense;
use App\Models\Legacy\ExpenseReceipt;
use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\Project;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\BudgetPlanMeasures;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/** An expense group with a single leaf; returns [plan, group, leaf]. */
function expenseGroupWithLeaf(int $plannedEuros = 100): array
{
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Ausgaben', 'value' => Money::EUR(0),
    ]);
    $leaf = $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR($plannedEuros, true),
    ]);

    return [$plan, $group, $leaf];
}

/**
 * A konto transaction, returned as ['id' => …, 'konto_id' => …]. The konto PK isn't
 * auto-increment, so we set the id explicitly and never read it back off the model.
 */
function makePayment(): array
{
    $account = BankAccount::factory()->create();
    BankTransaction::factory()->create(['konto_id' => $account->id, 'id' => 1]);

    return ['id' => 1, 'konto_id' => $account->id];
}

/** Book $euros against a leaf. Creates a konto transaction so the (zahlung_id, zahlung_type) FK holds. */
function bookLeaf(BudgetItem $leaf, string $euros, bool $canceled = false, ?array $payment = null): Booking
{
    $payment ??= makePayment();

    return Booking::create([
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

/** Reserve $ausgabenEuros against a leaf via an open (not-yet-terminated) project posting. */
function commitOpen(BudgetItem $leaf, int $ausgabenEuros, string $state = 'ok-by-hv'): void
{
    $project = Project::factory()->withState($state)->create();
    $project->posts()->create([
        'titel_id' => $leaf->id,
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR($ausgabenEuros, true),
        'name' => 'Posten',
        'bemerkung' => '',
    ]);
}

/**
 * Reserve $ausgabenEuros against a leaf via a *terminated* project's receipt posting: a
 * projektposten holds the titel, and a beleg_posten (belege → auslagen → projekte) books the
 * actual receipt amount that closedPostings() sums. The auslage stays non-revocation.
 */
function commitClosed(BudgetItem $leaf, int $ausgabenEuros): void
{
    $project = Project::factory()->withState('terminated')->create();
    $post = $project->posts()->create([
        'titel_id' => $leaf->id,
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(0),
        'name' => 'Posten',
        'bemerkung' => '',
    ]);

    $expense = Expense::factory()->create(['projekt_id' => $project->id, 'state' => 'draft']);
    $receipt = ExpenseReceipt::factory()->create(['auslagen_id' => $expense->id]);
    ExpenseReceiptPost::factory()->create([
        'beleg_id' => $receipt->id,
        'projekt_posten_id' => $post->id,
        'ausgaben' => $ausgabenEuros,
        'einnahmen' => 0,
    ]);
}

it('sums booked against a leaf and ignores canceled bookings', function (): void {
    [$plan, $group, $leaf] = expenseGroupWithLeaf();
    bookLeaf($leaf, '25');
    bookLeaf($leaf, '5', canceled: true);

    $items = new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate();

    expect($items->firstWhere('id', $leaf->id)->booked->getAmount())->toBe('2500')
        ->and($items->firstWhere('id', $group->id)->booked->getAmount())->toBe('2500');
});

it('rolls the committed money from open projects up to the group', function (): void {
    [$plan, $group, $leaf] = expenseGroupWithLeaf();
    commitOpen($leaf, 30);

    $items = new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate();

    expect($items->firstWhere('id', $leaf->id)->committed->getAmount())->toBe('3000')
        ->and($items->firstWhere('id', $group->id)->committed->getAmount())->toBe('3000');
});

it('ignores committed money from draft (not-yet-approved) projects', function (): void {
    [$plan, , $leaf] = expenseGroupWithLeaf();
    commitOpen($leaf, 30, state: 'draft');

    $items = new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate();

    expect($items->firstWhere('id', $leaf->id)->committed->getAmount())->toBe('0');
});

it('counts committed money from terminated projects\' receipt postings', function (): void {
    [$plan, $group, $leaf] = expenseGroupWithLeaf();
    commitClosed($leaf, 40);

    $items = new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate();

    expect($items->firstWhere('id', $leaf->id)->committed->getAmount())->toBe('4000')
        ->and($items->firstWhere('id', $group->id)->committed->getAmount())->toBe('4000');
});

it('rolls booked and committed up through a mount', function (): void {
    // referenced plan: one leaf carrying a booking and an open commitment
    [$refPlan, , $refLeaf] = expenseGroupWithLeaf();
    bookLeaf($refLeaf, '25');
    commitOpen($refLeaf, 30);

    // parent plan mounts the referenced plan
    $parent = BudgetPlan::create(['state' => Draft::class]);
    $mount = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'M', 'name' => 'Mount', 'value' => Money::EUR(0),
        'referenced_plan_id' => $refPlan->id,
    ]);

    $items = new BudgetPlanMeasures($parent, BudgetType::EXPENSE)->annotate();

    expect($items->firstWhere('id', $mount->id)->booked->getAmount())->toBe('2500')
        ->and($items->firstWhere('id', $mount->id)->committed->getAmount())->toBe('3000');
});

it('renders the booked and committed columns with real amounts', function (): void {
    $this->actingAs(user());
    [$plan, , $leaf] = expenseGroupWithLeaf();
    bookLeaf($leaf, '25');
    commitOpen($leaf, 30);

    $this->get(route('budget-plan.view', $plan->id))
        ->assertOk()
        ->assertSee(__('budget-plan.view.col.booked'))
        ->assertSee(__('budget-plan.view.col.committed'))
        ->assertDontSee('Verfügbar') // the old "available" header is gone
        ->assertSee('25,00')
        ->assertSee('30,00');
});
