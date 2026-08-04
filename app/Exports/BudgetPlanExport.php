<?php

namespace App\Exports;

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Support\Budget\BudgetPlanMeasures;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Spreadsheet export of a (new) budget plan, mirroring the on-screen plan view: the income and
 * expense trees with their Plan (Soll), Gebucht (Ist) and Beschlossen (committed) figures.
 *
 * The same instance drives both the .xlsx and .ods download (the writer type is chosen by the
 * controller); the content is identical. Values are written as plain numbers so the EUR column
 * formatting below applies — no live formulas, the roll-ups come pre-computed from
 * {@see BudgetPlanMeasures::annotate()}.
 */
class BudgetPlanExport implements FromView, WithColumnFormatting, WithColumnWidths
{
    use Exportable;

    public function __construct(public BudgetPlan $plan) {}

    #[\Override]
    public function view(): View
    {
        return view('exports.budget-plan', [
            'plan' => $this->plan,
            'income' => new BudgetPlanMeasures($this->plan, BudgetType::INCOME)->annotate(),
            'expense' => new BudgetPlanMeasures($this->plan, BudgetType::EXPENSE)->annotate(),
        ]);
    }

    #[\Override]
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_EUR,
            'D' => NumberFormat::FORMAT_CURRENCY_EUR,
            'E' => NumberFormat::FORMAT_CURRENCY_EUR,
        ];
    }

    #[\Override]
    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 50,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
}
