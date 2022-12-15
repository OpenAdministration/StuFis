<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\KontoCredential;

/**
 * @property integer $id
 * @property string $url
 * @property integer $blz
 * @property string $name
 * @property KontoCredential[] $finanzformularKontoCredentials
 */
class Bank extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konto_bank';

    /**
     * @var array
     */
    protected $fillable = ['url', 'blz', 'name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kontoCredentials()
    {
        return $this->hasMany(KontoCredential::class, 'bank_id');
    }
}
