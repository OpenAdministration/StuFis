<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $projekt_id
 * @property integer $titel_id
 * @property float $einnahmen
 * @property float $ausgaben
 * @property string $name
 * @property string $bemerkung
 * @property Project $projekte
 */
class ProjectPost extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projektposten';

    /**
     * @var array
     */
    protected $fillable = ['titel_id', 'einnahmen', 'ausgaben', 'name', 'bemerkung'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'projekt_id');
    }
}
