<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\ExpenseReceiptPost
 *
 * @property int $beleg_id
 * @property int $short
 * @property int $id
 * @property int $projekt_posten_id
 * @property float $ausgaben
 * @property float $einnahmen
 * @property ExpenseReceipt $expensesReceipt
 *
 * @method static Builder|ExpenseReceiptPost newModelQuery()
 * @method static Builder|ExpenseReceiptPost newQuery()
 * @method static Builder|ExpenseReceiptPost query()
 * @method static Builder|ExpenseReceiptPost whereAusgaben($value)
 * @method static Builder|ExpenseReceiptPost whereBelegId($value)
 * @method static Builder|ExpenseReceiptPost whereEinnahmen($value)
 * @method static Builder|ExpenseReceiptPost whereId($value)
 * @method static Builder|ExpenseReceiptPost whereProjektPostenId($value)
 * @method static Builder|ExpenseReceiptPost whereShort($value)
 *
 * @mixin \Eloquent
 */
class ExpenseReceiptPost extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'beleg_posten';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['id', 'projekt_posten_id', 'ausgaben', 'einnahmen'];

    public function expensesReceipt(): BelongsTo
    {
        return $this->belongsTo(ExpenseReceipt::class, 'beleg_id', 'id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'beleg_id')->where('beleg_type', 'belegposten');
    }
}
