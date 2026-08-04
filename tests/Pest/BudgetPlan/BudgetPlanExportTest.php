<?php

use App\Exports\BudgetPlanExport;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

uses(DatabaseTransactions::class);

/** A plan with one expense leaf, so the export has something to render. */
function exportablePlan(): BudgetPlan
{
    $plan = BudgetPlan::create(['organization' => 'StuRa', 'state' => Draft::class]);
    $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);

    return $plan;
}

it('downloads the plan as an xlsx spreadsheet', function (): void {
    ExcelFacade::fake();
    ExcelFacade::matchByRegex();
    $this->actingAs(user());
    $plan = exportablePlan();

    $this->get(route('budget-plan.export', [$plan->id, 'xlsx']))->assertOk();

    ExcelFacade::assertDownloaded('/\.xlsx$/', fn (BudgetPlanExport $export): bool => $export->plan->is($plan));
});

it('downloads the plan as an ods spreadsheet', function (): void {
    ExcelFacade::fake();
    ExcelFacade::matchByRegex();
    $this->actingAs(user());
    $plan = exportablePlan();

    $this->get(route('budget-plan.export', [$plan->id, 'ods']))->assertOk();

    ExcelFacade::assertDownloaded('/\.ods$/');
});

it('404s for an unsupported file type', function (): void {
    $this->actingAs(user());
    $plan = exportablePlan();

    $this->get(route('budget-plan.export', [$plan->id, 'csv']))->assertNotFound();
});

it('requires authentication', function (): void {
    $plan = exportablePlan();

    $this->get(route('budget-plan.export', [$plan->id, 'xlsx']))->assertRedirect(route('login'));
});

// The facade-faked downloads above never render the Blade, so exercise the view directly.

it('sums a section total from fractional leaf amounts', function (): void {
    $plan = BudgetPlan::create(['state' => Draft::class]);
    foreach ([10, 20] as $i => $cents) {
        $plan->budgetItems()->create([
            'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => $i,
            'short_name' => "A$i", 'name' => "Leaf $i", 'value' => Money::EUR($cents),
        ]);
    }

    $html = new BudgetPlanExport($plan)->view()->render();

    // 0.10 + 0.20 = 0.30 on the (bold) section total row; the leaves render 0.1 and 0.2 unbold
    expect(substr_count($html, '<b>0.3</b>'))->toBe(1);
});

it('rolls leaf values up into the group and section total', function (): void {
    // group value is stored as 0; the export must roll up the children live
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A', 'name' => 'Ausgaben', 'value' => Money::EUR(0),
    ]);
    foreach ([100, 50] as $i => $euros) {
        $group->children()->create([
            'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
            'position' => $i, 'short_name' => "A.$i", 'name' => "Leaf $i", 'value' => Money::EUR($euros, true),
        ]);
    }

    $html = new BudgetPlanExport($plan)->view()->render();

    // 100 + 50 rolled up: appears once on the group row and once on the section total row
    expect(substr_count($html, '<b>150</b>'))->toBe(2);
});
