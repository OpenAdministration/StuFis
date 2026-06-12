<?php

namespace App\Models;

use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TaxBudget extends Model
{
    protected $table = 'tax_budget';

    protected $fillable = [
        'hhp_id',
        'titel_id',
        'tax_percent',
    ];

    protected function casts(): array
    {
        return [
            'tax_percent' => 'decimal:2',
        ];
    }

    /**
     * Idempotently add the Umsatzsteuer group and its tax titles to a legacy
     * budget plan. Existing entries are left untouched, so it is safe to call
     * this repeatedly for the same plan.
     *
     * @param  array<int, array{titel_nr: string, titel_name: string}>  $taxTitles  Tax titles to ensure, keyed by tax percentage.
     * @param  int  $groupType  1 = Ausgabe (expenses), 0 = Einnahme (income).
     */
    public static function addToPlan(
        int $planId,
        string $groupName = 'Umsatzsteuer',
        int $groupType = 1,
        array $taxTitles = [
            7 => ['titel_nr' => 'A.99.1', 'titel_name' => '7% Umsatzsteuer'],
            19 => ['titel_nr' => 'A.99.2', 'titel_name' => '19% Umsatzsteuer'],
        ],
    ): void {
        DB::transaction(static function () use ($planId, $groupName, $groupType, $taxTitles): void {
            $group = LegacyBudgetGroup::firstOrCreate([
                'hhp_id' => $planId,
                'gruppen_name' => $groupName,
                'type' => $groupType,
            ]);

            foreach ($taxTitles as $percent => $title) {
                $item = LegacyBudgetItem::firstOrCreate(
                    [
                        'hhpgruppen_id' => $group->id,
                        'titel_nr' => $title['titel_nr'],
                    ],
                    [
                        'titel_name' => $title['titel_name'],
                        'value' => 0,
                    ],
                );

                self::firstOrCreate(
                    [
                        'hhp_id' => $planId,
                        'titel_id' => $item->id,
                    ],
                    [
                        'tax_percent' => $percent,
                    ],
                );
            }
        });
    }

    public function legacyBudgetPlan(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetPlan::class, 'hhp_id');
    }

    public function legacyBudgetTitle(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetItem::class, 'titel_id');
    }
}
