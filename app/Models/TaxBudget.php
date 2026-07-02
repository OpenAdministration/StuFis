<?php

namespace App\Models;

use App\Models\Enums\BudgetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TaxBudget extends Model
{
    protected $table = 'tax_budget';

    protected $fillable = [
        'plan_id',
        'budget_id',
        'tax_percent',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'tax_percent' => 'decimal:2',
        ];
    }

    /**
     * Idempotently add the Umsatzsteuer group and one tax title per VAT rate to a budget plan, in
     * the new budget_item structure. Existing entries are left untouched, so it is safe to call
     * repeatedly. Returns the number of tax titles newly added (0 if all already existed).
     *
     * @param  list<int|float>|null  $rates  VAT rates in percent; defaults to the `tax.rates` setting.
     */
    public static function addToPlan(
        int $planId,
        ?array $rates = null,
        string $groupName = 'Umsatzsteuer',
        string $groupShortName = 'A.99',
        BudgetType $groupType = BudgetType::EXPENSE,
    ): int {
        $rates = collect($rates ?? Setting::get('tax.rates', [7, 19]))
            ->map(fn ($rate): int => (int) $rate)
            ->filter(fn (int $rate): bool => $rate > 0)
            ->unique()
            ->sort()
            ->values();

        return DB::transaction(function () use ($planId, $rates, $groupName, $groupShortName, $groupType): int {
            $group = BudgetItem::firstOrCreate(
                ['budget_plan_id' => $planId, 'short_name' => $groupShortName, 'is_group' => true],
                [
                    'name' => $groupName,
                    'budget_type' => $groupType,
                    'value' => 0,
                    'position' => BudgetItem::where('budget_plan_id', $planId)->whereNull('parent_id')
                        ->where('budget_type', $groupType)->max('position') + 1,
                ],
            );

            $added = 0;
            foreach ($rates as $index => $percent) {
                $item = BudgetItem::firstOrCreate(
                    ['budget_plan_id' => $planId, 'short_name' => $groupShortName.'.'.($index + 1)],
                    [
                        'name' => $percent.'% '.$groupName,
                        'value' => 0,
                        'budget_type' => $groupType,
                        'is_group' => false,
                        'parent_id' => $group->id,
                        'position' => $index,
                    ],
                );

                $taxBudget = self::firstOrCreate(
                    ['plan_id' => $planId, 'budget_id' => $item->id],
                    ['tax_percent' => $percent],
                );

                if ($taxBudget->wasRecentlyCreated) {
                    $added++;
                }
            }

            return $added;
        });
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'plan_id');
    }

    public function budgetTitle(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'budget_id');
    }
}
