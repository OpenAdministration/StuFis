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

it('shows an explanatory subtitle and the parent group path in the breadcrumbs', function (): void {
    $this->actingAs(user());
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'E', 'name' => 'Erträge', 'value' => Money::EUR(0),
    ]);
    $leaf = $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'E.1', 'name' => 'Material', 'value' => Money::EUR(50, true),
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.subtitle'))
        ->assertSeeInOrder([$plan->label(), 'Erträge', 'Material']); // breadcrumb: plan › group › titel
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

it('renders the committed breakdown tab and links each committing project', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $project = Project::factory()->withState('ok-by-hv')->create();
    $project->posts()->create([
        'titel_id' => $leaf->id,
        'einnahmen' => Money::EUR(0),
        'ausgaben' => Money::EUR(60, true),
        'name' => 'Bus',
        'bemerkung' => '',
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.tab.committed'))
        ->assertSee(route('project.show', $project->id), false)
        ->assertSee('60,00');
});

it('shows an empty committed state when nothing commits to the item', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.no-committed'));
});

it('keeps the active tab in a URL query parameter', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $lw = Livewire::test('pages::budget-plan.item-view', ['plan_id' => $plan->id, 'item_id' => $leaf->id]);

    $lw->assertSet('tab', 'bookings')       // default
        ->set('tab', 'committed')
        ->assertSet('tab', 'committed');

    // #[Url] exposes it to the query string so a reload/link lands on the same tab
    expect((new ReflectionProperty($lw->instance(), 'tab'))->getAttributes(Livewire\Attributes\Url::class))->not->toBeEmpty();
});

it('sorts the bookings table by the clicked column and flips on a repeat click', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();
    bookItem($leaf, '10');
    bookItem($leaf, '90');

    Livewire::test('pages::budget-plan.item-view', ['plan_id' => $plan->id, 'item_id' => $leaf->id])
        ->call('sortBookings', 'amount')          // first click → ascending
        ->assertSeeInOrder(['10,00', '90,00'])
        ->call('sortBookings', 'amount')          // repeat click → descending
        ->assertSeeInOrder(['90,00', '10,00']);
});

it('labels the meter remainder Überzogen once the item is overspent', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf(); // planned 100 EUR

    Project::factory()->withState('ok-by-hv')->create()->posts()->create([
        'titel_id' => $leaf->id, 'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(150, true),
        'name' => 'Bus', 'bemerkung' => '',
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.meter.overspent'))
        ->assertDontSee(__('budget-plan.item.meter.available'));
});

it('shows the billed sum for a terminated project in the committed tab', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = planWithLeaf();

    $project = Project::factory()->withState('terminated')->create();
    $post = $project->posts()->create([
        'titel_id' => $leaf->id, 'einnahmen' => Money::EUR(0), 'ausgaben' => Money::EUR(0),
        'name' => 'Bus', 'bemerkung' => '',
    ]);
    $expense = Expense::factory()->create(['projekt_id' => $project->id, 'state' => 'draft']);
    $receipt = ExpenseReceipt::factory()->create(['auslagen_id' => $expense->id]);
    ExpenseReceiptPost::factory()->create([
        'beleg_id' => $receipt->id, 'projekt_posten_id' => $post->id, 'ausgaben' => 40, 'einnahmen' => 0,
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(route('project.show', $project->id), false)
        ->assertSee('40,00');
});
