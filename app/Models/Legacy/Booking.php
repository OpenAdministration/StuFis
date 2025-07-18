<?php

namespace App\Models\Legacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * App\Models\Legacy\Booking
 *
 * @property int $id
 * @property int $titel_id
 * @property int $user_id
 * @property int $kostenstelle
 * @property int $zahlung_id
 * @property int $zahlung_type
 * @property int $beleg_id
 * @property string $beleg_type
 * @property string $timestamp
 * @property string $comment
 * @property float $value
 * @property int $canceled
 * @property LegacyBudgetItem $haushaltstitel
 * @property User $user
 * @property-read LegacyBudgetItem $budgetItem
 *
 * @method static Builder|Booking newModelQuery()
 * @method static Builder|Booking newQuery()
 * @method static Builder|Booking query()
 * @method static Builder|Booking whereBelegId($value)
 * @method static Builder|Booking whereBelegType($value)
 * @method static Builder|Booking whereCanceled($value)
 * @method static Builder|Booking whereComment($value)
 * @method static Builder|Booking whereId($value)
 * @method static Builder|Booking whereKostenstelle($value)
 * @method static Builder|Booking whereTimestamp($value)
 * @method static Builder|Booking whereTitelId($value)
 * @method static Builder|Booking whereUserId($value)
 * @method static Builder|Booking whereValue($value)
 * @method static Builder|Booking whereZahlungId($value)
 * @method static Builder|Booking whereZahlungType($value)
 *
 * @mixin \Eloquent
 */
class Booking extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'booking';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'user_id', 'kostenstelle', 'zahlung_id', 'zahlung_type', 'beleg_id', 'beleg_type', 'timestamp', 'comment', 'value', 'canceled'];

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetItem::class, 'titel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function expensesReceiptPost(): BelongsTo
    {
        return $this->belongsTo(ExpensesReceiptPost::class, 'beleg_id');
    }

    public function expenseReceipt(): HasOneThrough
    {
        return $this->hasOneThrough(ExpensesReceipt::class, ExpensesReceiptPost::class, 'beleg_id', 'id');
    }

    public function expense(): BelongsTo
    {
        // this is probably not very performant...
        return $this->expensesReceiptPost->expensesReceipt->expense();
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'zahlung_id')->where('konto_id', $this->zahlung_type);
    }
}
