<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $von
 * @property string $bis
 * @property string $state
 * @property LegacyBudgetGroup[] $budgetGroups
 */
class LegacyBudgetPlan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsplan';

    /**
     * @var array
     */
    protected $fillable = ['von', 'bis', 'state'];

    public function budgetGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LegacyBudgetGroup::class, 'hhp_id');
    }

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(LegacyBudgetItem::class, LegacyBudgetGroup::class);
    }
}
