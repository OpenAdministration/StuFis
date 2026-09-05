<?php

namespace App\Models;

use App\Models\Legacy\BankAccountCredential;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * A German bank's FinTS access data, synced from the public hbci4java bank list
 * by `stufis:fints-institutes-update`.
 *
 * @property string $blz
 * @property string $name
 * @property string|null $location
 * @property string|null $bic
 * @property string|null $checksum_method
 * @property string|null $rdh_address
 * @property string|null $pin_tan_address
 * @property string|null $rdh_version
 * @property string|null $pin_tan_version
 * @property Carbon $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FintsInstitute extends Model
{
    /**
     * Fields the upstream list owns, i.e. everything a sync may overwrite.
     */
    public const SYNCED_FIELDS = [
        'name',
        'location',
        'bic',
        'checksum_method',
        'rdh_address',
        'pin_tan_address',
        'rdh_version',
        'pin_tan_version',
    ];

    /**
     * The Bankleitzahl is the key, so there is no surrogate id to auto-increment.
     */
    protected $primaryKey = 'blz';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'blz',
        ...self::SYNCED_FIELDS,
        'synced_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    /**
     * The bank accesses configured for this institute. Their existence is what keeps
     * `--prune` from dropping an institute that is actually in use.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(BankAccountCredential::class, 'blz', 'blz');
    }

    /**
     * When the bank list was last pulled, or null while the table is still empty.
     */
    public static function listDate(): ?Carbon
    {
        $latest = static::max('synced_at');

        return $latest === null ? null : Date::parse($latest);
    }

    public static function findByBlz(string|int $blz): ?static
    {
        return static::query()->where('blz', self::normaliseBlz($blz))->first();
    }

    /**
     * Resolves a German IBAN to its institute: DE + 2 check digits + 8 digit BLZ + account.
     * Foreign IBANs have no BLZ to extract, so they yield null.
     */
    public static function findByIban(string $iban): ?static
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');

        if (! preg_match('/^DE\d{20}$/', $iban)) {
            return null;
        }

        return static::findByBlz(substr($iban, 4, 8));
    }

    /**
     * Only institutes we could actually open a PIN/TAN dialog with.
     */
    #[Scope]
    protected function pinTanCapable(Builder $query): Builder
    {
        return $query->whereNotNull('pin_tan_address');
    }

    /**
     * Whether a PIN/TAN endpoint is safe to open a dialog against.
     *
     * The PIN and every TAN travel over this URL, so plain `http://` would hand them to
     * anyone on the path. phpFinTS points this out in `FinTsOptions` but does not check
     * it, and the addresses reach us from two unverified places: the upstream bank list,
     * and - for instances upgrading - the URL somebody once typed into `konto_bank` by
     * hand, which the retiring migration carries over as-is.
     */
    public static function hasSecurePinTanAddress(?string $address): bool
    {
        return $address !== null && str_starts_with(strtolower(trim($address)), 'https://');
    }

    /**
     * Free-text lookup for a bank picker: name, BLZ or BIC.
     */
    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhere('blz', 'like', $term.'%')
                ->orWhere('bic', 'like', $term.'%');
        });
    }

    /**
     * Bankleitzahlen are stored as the 8 digit string the list uses, but callers may
     * hand us an int, as the legacy code does when it comes from a form.
     */
    public static function normaliseBlz(string|int $blz): string
    {
        return str_pad(trim((string) $blz), 8, '0', STR_PAD_LEFT);
    }
}
