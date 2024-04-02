<?php

namespace App\Http\Controllers\Legacy;

use App\Exports\LegacyBudgetExport;
use App\Http\Controllers\Controller;
use App\Models\Legacy\LegacyBudgetPlan;
use Carbon\Carbon;
use Maatwebsite\Excel\Excel;

class ExportController extends Controller
{
    public function budgetPlan(int $id, string $filetype)
    {
        $writerType = match ($filetype){
            "xlsx", "xls" => Excel::XLSX,
            "ods" => Excel::ODS,
        };
        $plan = LegacyBudgetPlan::findOrFail($id);
        $today = today()->format("Y-m-d");
        $start = Carbon::make($plan->von)?->format("y-m");
        $end = Carbon::make($plan->bis)?->format("y-m");
        $fileName = "$today HHP $start" . ($end ? " bis $end" : '') . ".$filetype";
        return (new LegacyBudgetExport($plan))->download($fileName, $writerType);
    }
}
