<?php

namespace App\Models\Legacy;

use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $hhpgruppen_id
 * @property string $titel_name
 * @property string $titel_nr
 * @property float $value
 * @property Booking[] $bookings
 * @property LegacyBudgetGroup $haushaltsgruppen
 */
class LegacyBudgetItem extends Model
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
        return $this->hasMany(Booking::class, 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetGroup()
    {
        return $this->belongsTo(LegacyBudgetGroup::class, 'hhpgruppen_id');
    }

    public function bookingSum() : string
    {
        return $this->bookings()->sum('value');
    }

    public function bookingDiff() : string
    {
        return (float) bcsub($this->value, $this->bookings()->sum('value'));
    }
}
