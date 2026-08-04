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

it('annotates planned and rolls it up through the group', function (): void {
    [$plan, $group, $leaf] = expenseGroupWithLeaf(plannedEuros: 100);

    $items = new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate();

    // leaf carries its own value; the group is the live sum of its children
    expect($items->firstWhere('id', $leaf->id)->planned->getAmount())->toBe('10000')
        ->and($items->firstWhere('id', $group->id)->planned->getAmount())->toBe('10000');
});

it('rolls planned, booked and committed up through a mount', function (): void {
    // referenced plan: one leaf (100 planned) carrying a booking and an open commitment
    [$refPlan, , $refLeaf] = expenseGroupWithLeaf(plannedEuros: 100);
    bookLeaf($refLeaf, '25');
    commitOpen($refLeaf, 30);

    // parent plan mounts the referenced plan
    $parent = BudgetPlan::create(['state' => Draft::class]);
    $mount = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'M', 'name' => 'Mount', 'value' => Money::EUR(0),
        'referenced_plan_id' => $refPlan->id,
    ]);

    $mounted = new BudgetPlanMeasures($parent, BudgetType::EXPENSE)->annotate()->firstWhere('id', $mount->id);

    expect($mounted->planned->getAmount())->toBe('10000')
        ->and($mounted->booked->getAmount())->toBe('2500')
        ->and($mounted->committed->getAmount())->toBe('3000');
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

it('breaks the committed figure down per project and reconciles with the total', function (): void {
    [$plan, , $leaf] = expenseGroupWithLeaf();
    commitOpen($leaf, 30);   // open → its planned 30 counts
    commitClosed($leaf, 40); // terminated → only its billed 40 counts

    $measures = new BudgetPlanMeasures($plan, BudgetType::EXPENSE);
    $rows = $measures->committedBreakdown($leaf);

    expect($rows)->toHaveCount(2);

    $open = $rows->firstWhere('is_open', true);
    $closed = $rows->firstWhere('is_open', false);

    expect($open['planned']->getAmount())->toBe('3000')
        ->and($open['committed']->getAmount())->toBe('3000')   // open commits its planned figure
        ->and($closed['planned']->getAmount())->toBe('0')
        ->and($closed['billed']->getAmount())->toBe('4000')
        ->and($closed['committed']->getAmount())->toBe('4000'); // terminated commits only what was billed

    // Σ committed over the breakdown == the committed figure shown on the card
    $sum = $rows->reduce(fn (int $carry, array $row): int => $carry + (int) $row['committed']->getAmount(), 0);
    expect((string) $sum)->toBe($measures->forItem($leaf)['committed']->getAmount());
});

it('omits projects whose state does not commit from the breakdown', function (): void {
    [$plan, , $leaf] = expenseGroupWithLeaf();
    commitOpen($leaf, 30, state: 'draft'); // draft never commits

    expect(new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->committedBreakdown($leaf))->toBeEmpty();
});
