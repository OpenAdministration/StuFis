<?php

namespace App\Exports;

use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LegacyBudgetExport implements FromView, WithColumnFormatting, WithColumnWidths, WithPreCalculateFormulas
{
    use Exportable;

    public function __construct(public LegacyBudgetPlan $plan) {}

    public function view(): View
    {

        return view('exports.legacy.budget-plan', [
            'exporter' => $this,
            'plan' => $this->plan,
            'inGroup' => $this->plan->budgetGroups()->where('type', '=', '0')->get(),
            'outGroup' => $this->plan->budgetGroups()->where('type', '=', '1')->get(),
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_EUR,
            'D' => NumberFormat::FORMAT_CURRENCY_EUR,
            'E' => NumberFormat::FORMAT_CURRENCY_EUR,
        ];
    }

    public function sum(string $column, array|Collection $rows)
    {
        $rows = collect($rows);
        $fields = $rows->map(function ($row) use ($column) {
            return "$column$row";
        })->join(',');

        return '=SUM('.$fields.')';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 50,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
}
