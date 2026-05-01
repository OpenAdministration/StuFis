<?php

namespace App\Models;

use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function legacyBudgetPlan(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetPlan::class, 'hhp_id');
    }

    public function legacyBudgetTitle(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetItem::class, 'titel_id');
    }
}
