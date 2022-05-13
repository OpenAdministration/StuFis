<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BudgetGroup;

/**
 * @property integer $id
 * @property string $von
 * @property string $bis
 * @property string $state
 * @property BudgetGroup[] $budgetGroups
 */
class BudgetPlan extends Model
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetGroups()
    {
        return $this->hasMany(BudgetGroup::class, 'hhp_id');
    }

}
