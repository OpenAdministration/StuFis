<?php

namespace App\Models\Legacy;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\Project
 *
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
 * @property Expenses[] $expenses
 * @property User $user
 * @property ProjectPost[] $posts
 * @property-read User $creator
 * @property-read int|null $expenses_count
 * @property-read int|null $posts_count
 * @property-read User $stateCreator
 * @method static \Illuminate\Database\Eloquent\Builder|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereBeschreibung($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatedat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDateEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereDateStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereLastupdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereOrg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereOrgMail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereProtokoll($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereRecht($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereRechtAdditional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereResponsible($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereStateCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Project whereVersion($value)
 * @mixin \Eloquent
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
        return $this->hasMany('App\Models\Legacy\Expenses', 'projekt_id');
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
