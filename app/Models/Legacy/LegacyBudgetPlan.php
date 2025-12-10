<?php

namespace App\Models\Legacy;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;

/**
 * App\Models\Legacy\LegacyBudgetPlan
 *
 * @property int $id
 * @property string $von
 * @property string $bis
 * @property string $state
 * @property LegacyBudgetGroup[] $budgetGroups
 * @property-read int|null $budget_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Legacy\LegacyBudgetItem> $budgetItems
 * @property-read int|null $budget_items_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereBis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LegacyBudgetPlan whereVon($value)
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

    public function budgetGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LegacyBudgetGroup::class, 'hhp_id');
    }

    public function budgetItems(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->throughBudgetGroups()->hasBudgetItems();
    }

    public static function latest(): \Eloquent|static
    {
        return self::orderBy('id', 'desc')->first();
    }

    public static function findByDate(Carbon $date): static
    {
        return self::query()->where('von', '<=', $date)
            ->where(fn ($query) => $query->where('bis', '>=', $date)
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

    protected function casts(): array
    {
        return [
            'von' => 'date',
            'bis' => 'date',
        ];
    }
}
