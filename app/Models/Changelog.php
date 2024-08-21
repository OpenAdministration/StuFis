<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Changelog
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int $type_id
 * @property array $previous_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ChangelogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog query()
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog wherePreviousData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Changelog whereUserId($value)
 * @mixin \Eloquent
 */
class Changelog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'changelog';

    protected $fillable = ['user_id', 'type', 'type_id', 'previous_data'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'previous_data' => 'array',
    ];
}
