<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\ExpenseReceipt
 *
 * @property int $id
 * @property int $auslagen_id
 * @property string $short
 * @property string $created_on
 * @property string $datum
 * @property string $beschreibung
 * @property int $file_id
 * @property ExpenseReceiptPost[] $posts
 * @property Expense $auslagen
 * @property-read \App\Models\Legacy\Expense|null $expenses
 * @property-read int|null $posts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereAuslagenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereBeschreibung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereCreatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereDatum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ExpenseReceipt whereShort($value)
 *
 * @mixin \Eloquent
 */
class ExpenseReceipt extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'belege';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['auslagen_id', 'short', 'created_on', 'datum', 'beschreibung', 'file_id'];

    public function posts(): HasMany
    {
        return $this->hasMany(\App\Models\Legacy\ExpenseReceiptPost::class, 'beleg_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'auslagen_id');
    }
}
