<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\Konto
 *
 * @property integer $id
 * @property string $name
 * @property string $short
 * @property string $sync_from
 * @property string $sync_until
 * @property string $iban
 * @property string $last_sync
 * @property KontoTransaction[] $kontos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\KontoTransaction> $kontoTransactions
 * @property-read int|null $konto_transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|Konto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Konto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Konto query()
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereLastSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereSyncFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Konto whereSyncUntil($value)
 * @mixin \Eloquent
 */
class Konto extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto_type';

    /**
     * @var array
     */
    protected $fillable = ['name', 'short', 'sync_from', 'sync_until', 'iban', 'last_sync'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kontoTransactions()
    {
        return $this->hasMany('App\Models\Legacy\KontoTransaction', 'konto_id');
    }
}
