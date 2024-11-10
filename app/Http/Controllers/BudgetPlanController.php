<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;

class BudgetPlanController extends Controller
{
    public function index()
    {
        $plans = BudgetPlan::orderByDesc('start_date')->get();

        return view('budget-plan.index', ['plans' => $plans]);
    }

    public function show(BudgetPlan $plan)
    {
        return view('budget-plan.show');
    }
}
