<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\LegacyBudgetItem
 *
 * @property int $id
 * @property int $hhpgruppen_id
 * @property string $titel_name
 * @property string $titel_nr
 * @property float $value
 * @property Booking[] $bookings
 * @property LegacyBudgetGroup $haushaltsgruppen
 * @property-read int|null $bookings_count
 * @property-read LegacyBudgetGroup $budgetGroup
 *
 * @method static Builder|LegacyBudgetItem newModelQuery()
 * @method static Builder|LegacyBudgetItem newQuery()
 * @method static Builder|LegacyBudgetItem query()
 * @method static Builder|LegacyBudgetItem whereHhpgruppenId($value)
 * @method static Builder|LegacyBudgetItem whereId($value)
 * @method static Builder|LegacyBudgetItem whereTitelName($value)
 * @method static Builder|LegacyBudgetItem whereTitelNr($value)
 * @method static Builder|LegacyBudgetItem whereValue($value)
 *
 * @mixin \Eloquent
 */
class LegacyBudgetItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltstitel';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['hhpgruppen_id', 'titel_name', 'titel_nr', 'value'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'titel_id');
    }

    public function budgetGroup(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetGroup::class, 'hhpgruppen_id');
    }

    public function bookingSum(): float
    {
        return $this->bookings()->sum('value');
    }

    public function bookingDiff(): float
    {
        return (float) bcsub($this->value, $this->bookings()->sum('value'));
    }
}
