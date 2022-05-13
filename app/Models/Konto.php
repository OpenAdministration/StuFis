<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $name
 * @property string $short
 * @property string $sync_from
 * @property string $sync_until
 * @property string $iban
 * @property string $last_sync
 * @property KontoTransaction[] $kontos
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
        return $this->hasMany('App\Models\KontoTransaction', 'konto_id');
    }
}
