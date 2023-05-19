<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\ExpensesReceiptPost
 *
 * @property integer $beleg_id
 * @property integer $short
 * @property integer $id
 * @property integer $projekt_posten_id
 * @property float $ausgaben
 * @property float $einnahmen
 * @property ExpensesReceipt $expensesReceipt
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereAusgaben($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereBelegId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereEinnahmen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereProjektPostenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceiptPost whereShort($value)
 * @mixin \Eloquent
 */
class ExpensesReceiptPost extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'beleg_posten';

    /**
     * @var array
     */
    protected $fillable = ['id', 'projekt_posten_id', 'ausgaben', 'einnahmen'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expensesReceipt()
    {
        return $this->belongsTo(ExpensesReceipt::class, 'beleg_id');
    }
}
