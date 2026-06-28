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
     * Idempotently add the Umsatzsteuer group and its tax titles to a budget plan, in the new
     * budget_item structure. Existing entries are left untouched, so it is safe to call this
     * repeatedly for the same plan.
     *
     * @param  array<int, array{titel_nr: string, titel_name: string}>  $taxTitles  Tax titles to ensure, keyed by tax percentage.
     */
    public static function addToPlan(
        int $planId,
        string $groupName = 'Umsatzsteuer',
        string $groupShortName = 'A.99',
        BudgetType $groupType = BudgetType::EXPENSE,
        array $taxTitles = [
            7 => ['titel_nr' => 'A.99.1', 'titel_name' => '7% Umsatzsteuer'],
            19 => ['titel_nr' => 'A.99.2', 'titel_name' => '19% Umsatzsteuer'],
        ],
    ): void {
        DB::transaction(static function () use ($planId, $groupName, $groupShortName, $groupType, $taxTitles): void {
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

            $position = 0;
            foreach ($taxTitles as $percent => $title) {
                $item = BudgetItem::firstOrCreate(
                    ['budget_plan_id' => $planId, 'short_name' => $title['titel_nr']],
                    [
                        'name' => $title['titel_name'],
                        'value' => 0,
                        'budget_type' => $groupType,
                        'is_group' => false,
                        'parent_id' => $group->id,
                        'position' => $position++,
                    ],
                );

                self::firstOrCreate(
                    ['plan_id' => $planId, 'budget_id' => $item->id],
                    ['tax_percent' => $percent],
                );
            }
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
