<?php

namespace App\Models\Legacy;

use Database\Factories\Legacy\BankAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * App\Models\Legacy\Konto
 *
 * @property int $id
 * @property string $name
 * @property string $short
 * @property string $sync_from
 * @property string $sync_until
 * @property string $iban
 * @property string $last_sync
 * @property Collection $csv_import_settings
 * @property BankTransaction[] $kontos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BankTransaction> $kontoTransactions
 * @property-read int|null $konto_transactions_count
 *
 * @method static Builder|BankAccount newModelQuery()
 * @method static Builder|BankAccount newQuery()
 * @method static Builder|BankAccount query()
 * @method static Builder|BankAccount whereIban($value)
 * @method static Builder|BankAccount whereId($value)
 * @method static Builder|BankAccount whereLastSync($value)
 * @method static Builder|BankAccount whereName($value)
 * @method static Builder|BankAccount whereShort($value)
 * @method static Builder|BankAccount whereSyncFrom($value)
 * @method static Builder|BankAccount whereSyncUntil($value)
 *
 * @property int $manually_enterable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BankTransaction> $bankTransactions
 * @property-read int|null $bank_transactions_count
 *
 * @method static BankAccountFactory factory($count = null, $state = [])
 * @method static Builder|BankAccount whereCsvImportSettings($value)
 * @method static Builder|BankAccount whereManuallyEnterable($value)
 *
 * @mixin \Eloquent
 */
class BankAccount extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto_type';

    /**
     * @var array
     */
    protected $fillable = ['name', 'short', 'sync_from', 'sync_until', 'iban', 'last_sync', 'csv_import_settings', 'manually_enterable'];

    #[\Override]
    public function casts(): array
    {
        return [
            'manually_enterable' => 'boolean',
        ];
    }

    protected function csvImportSettings(): Attribute
    {
        return Attribute::make(
            get: static function (?string $value) {
                if (in_array($value, [null, '', '0'], true)) {
                    return [];
                }

                return json_decode($value, true);
            },
            set: static fn (array|Collection $value) => json_encode($value),
        );
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'konto_id');
    }

    /**
     * The account behind the shortened IBAN the FinTS URLs carry (first four characters plus
     * last four, see FintsConnectionHandler::shortenIban()). Null when no account is registered
     * for it - which is a normal state there, the bank lists accounts this installation does not
     * know yet.
     *
     * Several accounts can in principle share the four-and-four pattern; the lowest id wins, so
     * a label built from this at least stays the same between two page loads.
     */
    public static function findByShortIban(string $shortIban): ?static
    {
        // Guards the LIKE below against a route parameter carrying % or _ as much as it rejects
        // anything that is not shaped like a shortened IBAN in the first place.
        if (in_array(preg_match('/^[A-Z]{2}[A-Z0-9]{6}$/', $shortIban), [0, false], true)) {
            return null;
        }

        return static::query()
            ->where('iban', 'like', substr($shortIban, 0, 4).'%'.substr($shortIban, -4))
            ->orderBy('id')
            ->first();
    }
}
