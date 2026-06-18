<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\FileData
 *
 * @property int $id
 * @property string $data
 * @property string $diskpath
 * @property FileInfo[] $fileinfos
 * @property-read Collection<int, FileInfo> $fileInfo
 * @property-read int|null $file_info_count
 *
 * @method static Builder|FileData newModelQuery()
 * @method static Builder|FileData newQuery()
 * @method static Builder|FileData query()
 * @method static Builder|FileData whereData($value)
 * @method static Builder|FileData whereDiskpath($value)
 * @method static Builder|FileData whereId($value)
 *
 * @mixin \Eloquent
 */
class FileData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'filedata';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['data', 'diskpath'];

    public function fileInfo(): HasMany
    {
        return $this->hasMany(FileInfo::class, 'data');
    }
}
