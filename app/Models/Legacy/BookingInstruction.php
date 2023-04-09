<?php

namespace App\Models\Legacy;

use App\Models\BudgetItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
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
