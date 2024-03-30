<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class LegacyBudgetGroup extends Model
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LegacyBudgetItem::class, 'hhpgruppen_id');
    }
}
