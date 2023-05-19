<?php

namespace App\Models\Legacy;

use App\Models\BelegPosten;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\ExpensesReceipt
 *
 * @property integer $id
 * @property integer $auslagen_id
 * @property string $short
 * @property string $created_on
 * @property string $datum
 * @property string $beschreibung
 * @property integer $file_id
 * @property ExpensesReceiptPost[] $posts
 * @property Expenses $auslagen
 * @property-read \App\Models\Legacy\Expenses|null $expenses
 * @property-read int|null $posts_count
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereAuslagenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereBeschreibung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereCreatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereDatum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpensesReceipt whereShort($value)
 * @mixin \Eloquent
 */
class ExpensesReceipt extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'belege';

    /**
     * @var array
     */
    protected $fillable = ['auslagen_id', 'short', 'created_on', 'datum', 'beschreibung', 'file_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(\App\Models\Legacy\ExpensesReceiptPost::class, 'beleg_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expenses()
    {
        return $this->belongsTo(Expenses::class);
    }
}
