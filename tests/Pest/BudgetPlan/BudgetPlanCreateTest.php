<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

it('renders the create page (layout + breadcrumb) for a budget officer', function (): void {
    $this->actingAs(budgetManager());

    $this->get(route('budget-plan.create'))
        ->assertOk()
        ->assertSee(__('budget-plan.create.headline'));
});

it('creates a blank-template draft plan and redirects to edit', function (): void {
    $this->actingAs(budgetManager());

    Livewire::test('pages::budget-plan.plan-create')
        ->set('organization', 'Fachschaft')
        ->set('starting_point', 'template')
        ->call('save')
        ->assertHasNoErrors();

    $plan = BudgetPlan::orderByDesc('id')->first();
    expect($plan->state)->toBeInstanceOf(Draft::class)
        ->and($plan->organization)->toBe('Fachschaft');

    $inGroup = $plan->rootBudgetItems()->where('budget_type', BudgetType::INCOME)->first();
    $outGroup = $plan->rootBudgetItems()->where('budget_type', BudgetType::EXPENSE)->first();

    expect($inGroup->is_group)->toBeTrue()
        ->and($inGroup->short_name)->toBe('E1')
        ->and($inGroup->children()->first()->short_name)->toBe('E1.1')
        ->and($outGroup->short_name)->toBe('A1')
        ->and($outGroup->children()->first()->short_name)->toBe('A1.1');
});

it('clones the items of an existing plan', function (): void {
    $this->actingAs(budgetManager());

    $source = BudgetPlan::create(['state' => Draft::class, 'organization' => 'Quelle']);
    $group = $source->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);
    $group->children()->create([
        'budget_plan_id' => $source->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 0, 'short_name' => 'E1.1', 'value' => Money::EUR(200, true),
    ]);

    Livewire::test('pages::budget-plan.plan-create')
        ->set('starting_point', 'clone')
        ->set('source_plan_id', $source->id)
        ->set('organization', 'Kopie-Ziel')
        ->call('save')
        ->assertHasNoErrors();

    $plan = BudgetPlan::orderByDesc('id')->first();
    expect($plan->id)->not->toBe($source->id)
        ->and($plan->budgetItems()->count())->toBe($source->budgetItems()->count())
        ->and($plan->incomeTotal()->getAmount())->toBe('20000');
});

it('rejects a duplicate organization within the same fiscal year', function (): void {
    $this->actingAs(budgetManager());
    $year = FiscalYear::factory()->create();
    BudgetPlan::create(['state' => Draft::class, 'organization' => 'Fachschaft', 'fiscal_year_id' => $year->id]);

    // same org + same year → explicit validation error, no second plan created
    Livewire::test('pages::budget-plan.plan-create')
        ->set('starting_point', 'template')
        ->set('organization', 'Fachschaft')
        ->set('fiscal_year_id', $year->id)
        ->call('save')
        ->assertHasErrors('organization');

    expect(BudgetPlan::where('fiscal_year_id', $year->id)->count())->toBe(1);
});

it('allows the same organization in a different fiscal year', function (): void {
    $this->actingAs(budgetManager());
    $year1 = FiscalYear::factory()->create();
    $year2 = FiscalYear::factory()->create();
    BudgetPlan::create(['state' => Draft::class, 'organization' => 'Fachschaft', 'fiscal_year_id' => $year1->id]);

    Livewire::test('pages::budget-plan.plan-create')
        ->set('starting_point', 'template')
        ->set('organization', 'Fachschaft')
        ->set('fiscal_year_id', $year2->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(BudgetPlan::where('organization', 'Fachschaft')->count())->toBe(2);
});

it('preselects the clone source from the ?source query param', function (): void {
    $this->actingAs(budgetManager());
    $year = FiscalYear::factory()->create();
    $source = BudgetPlan::create([
        'state' => Draft::class, 'organization' => 'Vorlage', 'fiscal_year_id' => $year->id,
    ]);

    // prefill suggests a non-colliding name: the source already occupies "Vorlage" in this year
    $suffix = __('budget-plan.edit.copy-suffix');
    Livewire::withQueryParams(['source' => $source->id])
        ->test('pages::budget-plan.plan-create')
        ->assertSet('starting_point', 'clone')
        ->assertSet('source_plan_id', $source->id)
        ->assertSet('organization', "Vorlage ($suffix)")
        ->assertSet('fiscal_year_id', $year->id);
});

it('duplicates a preselected source: suggested name saved verbatim', function (): void {
    $this->actingAs(budgetManager());
    $year = FiscalYear::factory()->create();
    $source = BudgetPlan::create([
        'state' => Draft::class, 'organization' => 'Vorlage', 'fiscal_year_id' => $year->id,
    ]);
    $suffix = __('budget-plan.edit.copy-suffix');

    Livewire::withQueryParams(['source' => $source->id])
        ->test('pages::budget-plan.plan-create')
        ->assertSet('organization', "Vorlage ($suffix)") // suggested, collision-free
        ->call('save')
        ->assertHasNoErrors();

    $copy = BudgetPlan::orderByDesc('id')->first();
    expect($copy->id)->not->toBe($source->id)
        ->and($copy->organization)->toBe("Vorlage ($suffix)")
        ->and($copy->fiscal_year_id)->toBe($year->id);
});

it('re-suggests the organization name when the target year changes', function (): void {
    $this->actingAs(budgetManager());
    $year = FiscalYear::factory()->create();
    $freeYear = FiscalYear::factory()->create();
    $source = BudgetPlan::create([
        'state' => Draft::class, 'organization' => 'Vorlage', 'fiscal_year_id' => $year->id,
    ]);
    $suffix = __('budget-plan.edit.copy-suffix');

    Livewire::withQueryParams(['source' => $source->id])
        ->test('pages::budget-plan.plan-create')
        ->assertSet('organization', "Vorlage ($suffix)") // collides in the source's year
        ->set('fiscal_year_id', $freeYear->id)
        ->assertSet('organization', 'Vorlage');           // no collision in the new year
});

it('shows a per-mount copy/drop chooser when the clone source has mounts', function (): void {
    $this->actingAs(budgetManager());

    $sub = BudgetPlan::create(['state' => Draft::class, 'organization' => 'Sub']);
    $sub->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'value' => Money::EUR(500, true),
    ]);
    $source = BudgetPlan::create(['state' => Draft::class, 'organization' => 'Haupt']);
    $source->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'referenced_plan_id' => $sub->id,
    ]);

    Livewire::test('pages::budget-plan.plan-create')
        ->set('starting_point', 'clone')
        ->set('source_plan_id', $source->id)
        ->assertSet('mountChoices', [$sub->id => 'copy']) // defaults to copy
        ->assertSee('Sub')
        ->assertSee(__('budget-plan.create.mount.copy'));
});

it('requires a source plan when cloning', function (): void {
    $this->actingAs(budgetManager());

    Livewire::test('pages::budget-plan.plan-create')
        ->set('starting_point', 'clone')
        ->set('source_plan_id', null)
        ->call('save')
        ->assertHasErrors('source_plan_id');
});
