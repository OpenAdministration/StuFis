<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\Expense
 *
 * @property int $id
 * @property int $projekt_id
 * @property string $name_suffix
 * @property string $state
 * @property string $ok_belege
 * @property string $ok_hv
 * @property string $ok_kv
 * @property string $payed
 * @property string $rejected
 * @property string $zahlung_iban
 * @property string $zahlung_name
 * @property string $zahlung_vwzk
 * @property string $address
 * @property string $last_change
 * @property string $last_change_by
 * @property string $etag
 * @property int $version
 * @property string $created
 * @property Project $project
 * @property ExpenseReceipt[] $beleges
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExpenseReceipt> $receipts
 * @property-read int|null $receipts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereEtag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereLastChangeBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereNameSuffix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereOkBelege($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereOkHv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereOkKv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense wherePayed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereProjektId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereZahlungIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereZahlungName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereZahlungVwzk($value)
 *
 * @mixin \Eloquent
 */
class Expense extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auslagen';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['projekt_id', 'name_suffix', 'state', 'ok_belege', 'ok_hv', 'ok_kv', 'payed', 'rejected', 'zahlung_iban', 'zahlung_name', 'zahlung_vwzk', 'address', 'last_change', 'last_change_by', 'etag', 'version', 'created'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ExpenseReceipt::class, 'auslagen_id');
    }
}
