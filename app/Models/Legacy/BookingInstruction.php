<?php

namespace App\Models\Legacy;

use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\BookingInstruction
 *
 * @property integer $id
 * @property integer $titel_id
 * @property integer $user_id
 * @property integer $kostenstelle
 * @property integer $zahlung_id
 * @property integer $zahlung_type
 * @property integer $beleg_id
 * @property string $beleg_type
 * @property string $timestamp
 * @property string $comment
 * @property float $value
 * @property integer $canceled
 * @property BudgetItem $budgetItem
 * @property User $user
 * @property int $zahlung
 * @property int $beleg
 * @property int $by_user
 * @property int $done
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction query()
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereBeleg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereBelegType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereByUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereZahlung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BookingInstruction whereZahlungType($value)
 * @mixin \Eloquent
 */
class BookingInstruction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'booking_instruction';

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'user_id', 'kostenstelle', 'zahlung_id', 'zahlung_type', 'beleg_id', 'beleg_type', 'timestamp', 'comment', 'value', 'canceled'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budgetItem()
    {
        return $this->belongsTo(BudgetItem::class, 'titel_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
