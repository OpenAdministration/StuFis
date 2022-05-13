<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ExpensesReceipt;

/**
 * @property integer $beleg_id
 * @property integer $short
 * @property integer $id
 * @property integer $projekt_posten_id
 * @property float $ausgaben
 * @property float $einnahmen
 * @property ExpensesReceipt $expensesReceipt
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
