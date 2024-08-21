<?php

namespace App\Models;

use Database\Factories\FinancePlanTopicFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FinancePlanTopic
 *
 * @property int $id
 * @property int $application_id
 * @property string $name
 * @property int $is_expense
 * @method static FinancePlanTopicFactory factory($count = null, $state = [])
 * @method static Builder|FinancePlanTopic newModelQuery()
 * @method static Builder|FinancePlanTopic newQuery()
 * @method static Builder|FinancePlanTopic query()
 * @method static Builder|FinancePlanTopic whereApplicationId($value)
 * @method static Builder|FinancePlanTopic whereId($value)
 * @method static Builder|FinancePlanTopic whereIsExpense($value)
 * @method static Builder|FinancePlanTopic whereName($value)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Application $application
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FinancePlanItem> $financePlanItems
 * @property-read int|null $finance_plan_items_count
 * @method static Builder|FinancePlanTopic whereCreatedAt($value)
 * @method static Builder|FinancePlanTopic whereUpdatedAt($value)
 * @mixin Eloquent
 */
class FinancePlanTopic extends Model
{
    use HasFactory;

    public function application() : BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function financePlanItems() : HasMany
    {
        return $this->hasMany(FinancePlanItem::class);
    }
}
