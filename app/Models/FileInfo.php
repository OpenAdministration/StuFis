<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $data
 * @property string $link
 * @property string $added_on
 * @property string $hashname
 * @property string $filename
 * @property integer $size
 * @property string $fileextension
 * @property string $mime
 * @property string $encoding
 * @property FileData $filedatum
 */
class FileInfo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fileinfo';

    /**
     * @var array
     */
    protected $fillable = ['data', 'link', 'added_on', 'hashname', 'filename', 'size', 'fileextension', 'mime', 'encoding'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fileData()
    {
        return $this->belongsTo('App\Models\FileData', 'data');
    }
}
