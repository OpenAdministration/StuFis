<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\KontoCredential
 *
 * @property int $id
 * @property int $bank_id
 * @property int $owner_id
 * @property string $name
 * @property string $bank_username
 * @property int $tan_mode
 * @property string $tan_medium_name
 * @property string $tan_mode_name
 * @property Bank $kontoBank
 * @property User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereBankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereBankUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereTanMediumName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereTanMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccountCredential whereTanModeName($value)
 *
 * @mixin \Eloquent
 */
class BankAccountCredential extends Model
{
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['bank_id', 'owner_id', 'name', 'bank_username', 'tan_mode', 'tan_medium_name', 'tan_mode_name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kontoBank(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Legacy\Bank::class, 'bank_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }
}
