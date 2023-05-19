<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Legacy\ChatMessage
 *
 * @property integer $id
 * @property integer $target_id
 * @property string $target
 * @property string $timestamp
 * @property string $creator
 * @property string $creator_alias
 * @property string $text
 * @property boolean $type
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereCreator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereCreatorAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereType($value)
 * @mixin \Eloquent
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
