<?php

namespace App\Models\Legacy;

use App\Models\BelegPosten;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $auslagen_id
 * @property string $short
 * @property string $created_on
 * @property string $datum
 * @property string $beschreibung
 * @property integer $file_id
 * @property BelegPosten[] $belegPostens
 * @property Expenses $auslagen
 */
class ExpensesReceipt extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'belege';

    /**
     * @var array
     */
    protected $fillable = ['auslagen_id', 'short', 'created_on', 'datum', 'beschreibung', 'file_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function belegPostens()
    {
        return $this->hasMany('App\Models\BelegPosten', 'beleg_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expenses()
    {
        return $this->belongsTo(Expenses::class);
    }
}
