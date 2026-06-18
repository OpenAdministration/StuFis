<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
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
 * @property-read Expense|null $expenses
 * @property-read int|null $posts_count
 *
 * @method static Builder|ExpenseReceipt newModelQuery()
 * @method static Builder|ExpenseReceipt newQuery()
 * @method static Builder|ExpenseReceipt query()
 * @method static Builder|ExpenseReceipt whereAuslagenId($value)
 * @method static Builder|ExpenseReceipt whereBeschreibung($value)
 * @method static Builder|ExpenseReceipt whereCreatedOn($value)
 * @method static Builder|ExpenseReceipt whereDatum($value)
 * @method static Builder|ExpenseReceipt whereFileId($value)
 * @method static Builder|ExpenseReceipt whereId($value)
 * @method static Builder|ExpenseReceipt whereShort($value)
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
        return $this->hasMany(ExpenseReceiptPost::class, 'beleg_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'auslagen_id');
    }
}
