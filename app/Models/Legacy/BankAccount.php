<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
 * @property Collection $csv_import_settings
 * @property BankTransaction[] $kontos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\BankTransaction> $kontoTransactions
 * @property-read int|null $konto_transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereLastSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereSyncFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankAccount whereSyncUntil($value)
 * @mixin \Eloquent
 */
class BankAccount extends Model
{
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
    protected $fillable = ['name', 'short', 'sync_from', 'sync_until', 'iban', 'last_sync', 'csv_import_settings'];

    public function csvImportSettings() : Attribute
    {
        return Attribute::make(
            get: static function (?string $value){
                if(empty($value)){
                    return [];
                }
                return json_decode($value, true);
            } ,
            set: static fn (array|Collection $value) => json_encode($value),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kontoTransactions()
    {
        return $this->hasMany('App\Models\Legacy\BankTransaction', 'konto_id');
    }
}
