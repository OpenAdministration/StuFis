<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BudgetPlan;
use App\Models\BudgetItem;

/**
 * @property integer $id
 * @property integer $hhp_id
 * @property string $gruppen_name
 * @property boolean $type
 * @property BudgetPlan $budgetPlan
 * @property BudgetItem[] $budgetItems
 */
class BudgetGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsgruppen';

    /**
     * @var array
     */
    protected $fillable = ['hhp_id', 'gruppen_name', 'type'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetPlan()
    {
        return $this->belongsTo(BudgetPlan::class, 'hhp_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetItems()
    {
        return $this->hasMany(BudgetItem::class, 'hhpgruppen_id');
    }
}
