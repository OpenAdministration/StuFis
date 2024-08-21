<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\LegalBasis
 *
 * @property string $uuid
 * @property int $has_details
 * @property int $active
 * @method static \Database\Factories\LegalBasisFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis whereHasDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis whereUuid($value)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegalBasis whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LegalBasis extends Model
{
    use HasFactory;

    public function applications() : BelongsToMany {
        return $this->belongsToMany(Application::class);
    }
}
