<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $data
 * @property string $diskpath
 * @property FileInfo[] $fileinfos
 */
class FileData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filedata';

    /**
     * @var array
     */
    protected $fillable = ['data', 'diskpath'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileInfo()
    {
        return $this->hasMany(FileInfo::class, 'data');
    }
}
