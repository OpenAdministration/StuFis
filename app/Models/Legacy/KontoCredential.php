<?php

namespace App\Models\Legacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $bank_id
 * @property integer $owner_id
 * @property string $name
 * @property string $bank_username
 * @property integer $tan_mode
 * @property string $tan_medium_name
 * @property string $tan_mode_name
 * @property Bank $kontoBank
 * @property User $user
 */
class KontoCredential extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['bank_id', 'owner_id', 'name', 'bank_username', 'tan_mode', 'tan_medium_name', 'tan_mode_name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kontoBank()
    {
        return $this->belongsTo('App\Models\Legacy\Bank', 'bank_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'owner_id');
    }
}
