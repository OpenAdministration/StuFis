<?php

namespace App\Models\Legacy;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Support\Facades\Date;

/**
 * App\Models\Legacy\LegacyBudgetPlan
 *
 * @property int $id
 * @property string $von
 * @property string $bis
 * @property string $state
 * @property LegacyBudgetGroup[] $budgetGroups
 * @property-read int|null $budget_groups_count
 * @property-read Collection<int, LegacyBudgetItem> $budgetItems
 * @property-read int|null $budget_items_count
 *
 * @method static Builder|LegacyBudgetPlan newModelQuery()
 * @method static Builder|LegacyBudgetPlan newQuery()
 * @method static Builder|LegacyBudgetPlan query()
 * @method static Builder|LegacyBudgetPlan whereBis($value)
 * @method static Builder|LegacyBudgetPlan whereId($value)
 * @method static Builder|LegacyBudgetPlan whereState($value)
 * @method static Builder|LegacyBudgetPlan whereVon($value)
 * @method HasOneOrManyThrough throughBudgetGroups()
 *
 * @mixin \Eloquent
 */
class LegacyBudgetPlan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsplan';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['von', 'bis', 'state'];

    public function budgetGroups(): HasMany
    {
        return $this->hasMany(LegacyBudgetGroup::class, 'hhp_id');
    }

    public function budgetItems(): HasManyThrough
    {
        return $this->throughBudgetGroups()->hasBudgetItems();
    }

    public static function latest(): \Eloquent|static|null
    {
        return self::orderBy('id', 'desc')->first();
    }

    public static function findByDate(?Carbon $date = null): ?static
    {
        $date ??= Date::now();

        // `bis` is the last day inside the plan and is stored at midnight, so
        // compare against the start of $date's day to include the whole `bis`
        // day (a date created at 14:00 on the bis date still belongs to the plan).
        return self::query()->where('von', '<=', $date)
            ->where(fn ($query) => $query->where('bis', '>=', $date->copy()->startOfDay())
                ->orWhereNull('bis'))
            ->first();
    }

    public function label(): string
    {
        $format = 'M y';
        if ($this->bis === null) {
            return "HPP$this->id ab {$this->von->format($format)}";
        } else {
            return "HHP$this->id {$this->von->format($format)} - {$this->bis->format($format)}";
        }
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'von' => 'date',
            'bis' => 'date',
        ];
    }
}
