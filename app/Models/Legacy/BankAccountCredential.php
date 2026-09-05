<?php

namespace App\Models\Legacy;

use App\Models\FintsInstitute;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Legacy\KontoCredential
 *
 * @property int $id
 * @property string $blz
 * @property int $owner_id
 * @property string $name
 * @property string $bank_username
 * @property int $tan_mode
 * @property string $tan_medium_name
 * @property string $tan_mode_name
 * @property FintsInstitute $institute
 * @property User $user
 *
 * @method static Builder|BankAccountCredential newModelQuery()
 * @method static Builder|BankAccountCredential newQuery()
 * @method static Builder|BankAccountCredential query()
 * @method static Builder|BankAccountCredential whereBlz($value)
 * @method static Builder|BankAccountCredential whereBankUsername($value)
 * @method static Builder|BankAccountCredential whereId($value)
 * @method static Builder|BankAccountCredential whereName($value)
 * @method static Builder|BankAccountCredential whereOwnerId($value)
 * @method static Builder|BankAccountCredential whereTanMediumName($value)
 * @method static Builder|BankAccountCredential whereTanMode($value)
 * @method static Builder|BankAccountCredential whereTanModeName($value)
 *
 * @mixin \Eloquent
 */
class BankAccountCredential extends Model
{
    /**
     * Never set, so Eloquent guessed "bank_account_credentials" and every query failed. The
     * model had no callers to notice; it does now.
     */
    protected $table = 'konto_credentials';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['blz', 'owner_id', 'name', 'bank_username', 'tan_mode', 'tan_medium_name', 'tan_mode_name'];

    /**
     * The bank this access belongs to, straight from the synced bank list.
     */
    public function institute(): BelongsTo
    {
        return $this->belongsTo(FintsInstitute::class, 'blz', 'blz');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
