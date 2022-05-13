<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $target_id
 * @property string $target
 * @property string $timestamp
 * @property string $creator
 * @property string $creator_alias
 * @property string $text
 * @property boolean $type
 */
class ChatMessage extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'comments';

    /**
     * @var array
     */
    protected $fillable = ['target_id', 'target', 'timestamp', 'creator', 'creator_alias', 'text', 'type'];
}
