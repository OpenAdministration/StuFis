<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BudgetItem
 *
 * @property-read \App\Models\BudgetPlan|null $budgetPlan
 * @method static \Database\Factories\BudgetItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BudgetItem query()
 * @mixin \Eloquent
 */
class BudgetItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'budget_item';

    /**
     * @var array
     */
    protected $fillable = ['hhpgruppen_id', 'titel_name', 'titel_nr', 'value'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany('App\Models\Booking', 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetPlan()
    {
        return $this->belongsTo(\App\Models\BudgetPlan::class, 'budget_plan_id');
    }
}
