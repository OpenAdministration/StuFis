<?php

namespace App\Models\Legacy;

use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @method static Builder|Expense newModelQuery()
 * @method static Builder|Expense newQuery()
 * @method static Builder|Expense query()
 * @method static Builder|Expense whereAddress($value)
 * @method static Builder|Expense whereCreated($value)
 * @method static Builder|Expense whereEtag($value)
 * @method static Builder|Expense whereId($value)
 * @method static Builder|Expense whereLastChange($value)
 * @method static Builder|Expense whereLastChangeBy($value)
 * @method static Builder|Expense whereNameSuffix($value)
 * @method static Builder|Expense whereOkBelege($value)
 * @method static Builder|Expense whereOkHv($value)
 * @method static Builder|Expense whereOkKv($value)
 * @method static Builder|Expense wherePayed($value)
 * @method static Builder|Expense whereProjektId($value)
 * @method static Builder|Expense whereRejected($value)
 * @method static Builder|Expense whereState($value)
 * @method static Builder|Expense whereVersion($value)
 * @method static Builder|Expense whereZahlungIban($value)
 * @method static Builder|Expense whereZahlungName($value)
 * @method static Builder|Expense whereZahlungVwzk($value)
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

    public function totalIn(): Money
    {
        return Money::parseByDecimal($this->throughReceipts()->has('posts')->sum('einnahmen'), 'EUR');
    }

    public function totalOut(): Money
    {
        return Money::parseByDecimal($this->throughReceipts()->has('posts')->sum('ausgaben'), 'EUR');
    }

    protected function okKv(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function okHv(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function okBelege(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function payed(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function rejected(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function state(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => explode(';', $value)[0],
        );
    }

    protected function casts(): array
    {
        return [
            'zahlung_iban' => 'encrypted',
            'last_change' => 'datetime',
            'state' => 'string',
        ];
    }
}
