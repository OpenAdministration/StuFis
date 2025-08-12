<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;

class BudgetPlanController extends Controller
{
    public function index()
    {
        $years = FiscalYear::orderByDesc('start_date')->get();

        $orphaned_plans = BudgetPlan::doesntHave('fiscalYear')->get();

        return view('budget-plan.index', ['years' => $years, 'orphaned_plans' => $orphaned_plans]);
    }

    public function show(int $plan_id)
    {
        $plan = BudgetPlan::findOrFail($plan_id);

        return view('budget-plan.view', ['plan' => $plan]);
    }

    public function create()
    {
        $plan = BudgetPlan::create(['state' => 'draft']);
        $groups = $plan->budgetItems()->createMany([
            ['is_group' => 1, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1'],
            ['is_group' => 1, 'budget_type' => BudgetType::EXPENSE, 'position' => 0, 'short_name' => 'A1'],
        ]);
        $groups->each(function ($group) use ($plan): void {
            $group->children()->createMany([
                [
                    'budget_plan_id' => $plan->id,
                    'is_group' => 0, 'position' => 0,
                    'budget_type' => $group->budget_type,
                    'short_name' => $group->short_name.'.1',
                ],
            ]);
        });

        return redirect()->route('budget-plan.edit', ['plan_id' => $plan->id]);
    }
}
