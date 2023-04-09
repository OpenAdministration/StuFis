<?php

namespace App\Models\Legacy;

use BeyondCode\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $projekt_id
 * @property string $name_suffix
 * @property string $state
 * @property string $ok-belege
 * @property string $ok-hv
 * @property string $ok-kv
 * @property string $payed
 * @property string $rejected
 * @property string $zahlung-iban
 * @property string $zahlung-name
 * @property string $zahlung-vwzk
 * @property string $address
 * @property string $last_change
 * @property string $last_change_by
 * @property string $etag
 * @property integer $version
 * @property string $created
 * @property Project $project
 * @property ExpensesReceipt[] $beleges
 */
class Expenses extends Model
{
    use HasComments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'auslagen';

    /**
     * @var array
     */
    protected $fillable = ['projekt_id', 'name_suffix', 'state', 'ok-belege', 'ok-hv', 'ok-kv', 'payed', 'rejected', 'zahlung-iban', 'zahlung-name', 'zahlung-vwzk', 'address', 'last_change', 'last_change_by', 'etag', 'version', 'created'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receipts()
    {
        return $this->hasMany('App\Models\Receipts');
    }
}
