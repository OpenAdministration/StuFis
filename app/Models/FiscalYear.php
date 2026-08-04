<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fiscal_year';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'start_date',
        'end_date',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class);
    }

    /**
     * Human-readable label for the fiscal year's range.
     *
     * When both boundaries align to whole months (start on the 1st, end on the
     * last day of a month) the range is rendered compactly as localized
     * "MMM yy" per boundary (e.g. "Apr 22 – Mär 23"), collapsing to a single
     * token when start and end fall in the same month. Otherwise it falls back
     * to full "d.m.Y" dates.
     */
    public function label(): string
    {
        $start = $this->start_date;
        $end = $this->end_date;

        $wholeMonths = $start->isSameDay($start->copy()->startOfMonth())
            && $end->isSameDay($end->copy()->endOfMonth());

        if (! $wholeMonths) {
            return $start->format('d.m.Y').' – '.$end->format('d.m.Y');
        }

        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('M y');
        }

        return $start->translatedFormat('M y').' – '.$end->translatedFormat('M y');
    }
}
