<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Legacy\FileInfo
 *
 * @property int $id
 * @property int $data
 * @property string $link
 * @property string $added_on
 * @property string $hashname
 * @property string $filename
 * @property int $size
 * @property string $fileextension
 * @property string $mime
 * @property string $encoding
 * @property FileData $filedatum
 * @property-read FileData|null $fileData
 *
 * @method static Builder|FileInfo newModelQuery()
 * @method static Builder|FileInfo newQuery()
 * @method static Builder|FileInfo query()
 * @method static Builder|FileInfo whereAddedOn($value)
 * @method static Builder|FileInfo whereData($value)
 * @method static Builder|FileInfo whereEncoding($value)
 * @method static Builder|FileInfo whereFileextension($value)
 * @method static Builder|FileInfo whereFilename($value)
 * @method static Builder|FileInfo whereHashname($value)
 * @method static Builder|FileInfo whereId($value)
 * @method static Builder|FileInfo whereLink($value)
 * @method static Builder|FileInfo whereMime($value)
 * @method static Builder|FileInfo whereSize($value)
 *
 * @mixin \Eloquent
 */
class FileInfo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fileinfo';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['data', 'link', 'added_on', 'hashname', 'filename', 'size', 'fileextension', 'mime', 'encoding'];

    public function fileData(): BelongsTo
    {
        return $this->belongsTo(FileData::class, 'data');
    }
}
