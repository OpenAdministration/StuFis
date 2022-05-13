<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $hhpgruppen_id
 * @property string $titel_name
 * @property string $titel_nr
 * @property float $value
 * @property Booking[] $bookings
 * @property Haushaltsgruppen $haushaltsgruppen
 */
class BudgetItem extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'haushaltstitel';

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
    public function haushaltsgruppen()
    {
        return $this->belongsTo('App\Models\Haushaltsgruppen', 'hhpgruppen_id');
    }
}
