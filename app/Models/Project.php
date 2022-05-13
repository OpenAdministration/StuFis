<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProjectPost;

/**
 * @property integer $id
 * @property integer $creator_id
 * @property string $createdat
 * @property string $lastupdated
 * @property integer $version
 * @property string $state
 * @property integer $stateCreator_id
 * @property string $name
 * @property string $responsible
 * @property string $org
 * @property string $org-mail
 * @property string $protokoll
 * @property string $recht
 * @property string $recht-additional
 * @property string $date-start
 * @property string $date-end
 * @property string $beschreibung
 * @property Auslagen[] $auslagens
 * @property User $user
 * @property Projektposten[] $projektpostens
 */
class Project extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projekte';

    /**
     * @var array
     */
    protected $fillable = ['creator_id', 'createdat', 'lastupdated', 'version', 'state', 'stateCreator_id', 'name', 'responsible', 'org', 'org-mail', 'protokoll', 'recht', 'recht-additional', 'date-start', 'date-end', 'beschreibung'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function expenses()
    {
        return $this->hasMany('App\Models\Expenses', 'projekt_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'creator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stateCreator()
    {
        return $this->belongsTo('App\Models\User', 'stateCreator_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(ProjectPost::class, 'projekt_id');
    }
}
