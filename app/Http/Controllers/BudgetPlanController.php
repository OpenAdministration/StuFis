<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use Illuminate\Support\Facades\Gate;

class BudgetPlanController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', BudgetPlan::class);

        $years = FiscalYear::orderByDesc('start_date')->get();

        $orphaned_plans = BudgetPlan::doesntHave('fiscalYear')->get();

        return view('budget-plan.index', ['years' => $years, 'orphaned_plans' => $orphaned_plans]);
    }
}
