<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;
use App\Models\FiscalYear;

class BudgetPlanController extends Controller
{
    public function index()
    {
        $years = FiscalYear::orderByDesc('start_date')->get();

        return view('budget-plan.index', ['years' => $years]);
    }

    public function show(BudgetPlan $plan)
    {
        return view('budget-plan.show');
    }

    public function create()
    {
        $plan = BudgetPlan::create(['state' => 'draft']);
        $groups = $plan->budgetItems()->createMany([
            ['is_group' => 1, 'budget_type' => 1, 'position' => 1, 'short_name' => 'A1'],
            ['is_group' => 1, 'budget_type' => -1, 'position' => 1, 'short_name' => 'E1'],
        ]);
        $groups->each(function ($group) use ($plan) {
            $group->children()->createMany([
                [
                    'budget_plan_id' => $plan->id,
                    'is_group' => 0, 'position' => 1,
                    'budget_type' => $group->budget_type,
                ],
            ]);
        });

        return redirect()->route('budget-plan.edit', ['plan_id' => $plan->id]);
    }
}
