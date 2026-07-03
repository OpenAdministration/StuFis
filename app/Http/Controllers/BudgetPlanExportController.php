<?php

namespace App\Http\Controllers;

use App\Exports\BudgetPlanExport;
use App\Models\BudgetPlan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;

class BudgetPlanExportController extends Controller
{
    /**
     * Download a budget plan as a spreadsheet. Same content either way — only the writer differs:
     * xlsx (Excel) or ods (LibreOffice Calc).
     */
    public function download(int $plan_id, string $filetype)
    {
        $plan = BudgetPlan::findOrFail($plan_id);
        Gate::authorize('view', $plan);

        $writerType = match ($filetype) {
            'xlsx' => Excel::XLSX,
            'ods' => Excel::ODS,
            default => abort(404),
        };

        $name = Str::slug(today()->format('Y-m-d').' HHP '.$plan->label()) ?: 'hhp';

        return new BudgetPlanExport($plan)->download("$name.$filetype", $writerType);
    }
}
