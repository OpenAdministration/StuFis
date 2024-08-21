<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ActorMail
 *
 * @property int $id
 * @property int $actor_id
 * @property string $value
 * @method static \Database\Factories\ActorMailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail whereValue($value)
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \App\Models\Actor|null $actor
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActorMail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActorMail extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function actor() : BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
