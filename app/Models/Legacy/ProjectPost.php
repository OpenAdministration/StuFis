<?php

namespace App\Models\Legacy;

use App\Events\UpdatingModel;
use Cknow\Money\Casts\MoneyDecimalCast;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\ProjectPost
 *
 * @property int $id
 * @property int $projekt_id
 * @property int $titel_id
 * @property Money $einnahmen
 * @property Money $ausgaben
 * @property string $name
 * @property string $bemerkung
 * @property Project $projekte
 * @property-read Project $project
 * @property-read BudgetItem $budgetItem
 *
 * @method static Builder|ProjectPost newModelQuery()
 * @method static Builder|ProjectPost newQuery()
 * @method static Builder|ProjectPost query()
 * @method static Builder|ProjectPost whereAusgaben($value)
 * @method static Builder|ProjectPost whereBemerkung($value)
 * @method static Builder|ProjectPost whereEinnahmen($value)
 * @method static Builder|ProjectPost whereId($value)
 * @method static Builder|ProjectPost whereName($value)
 * @method static Builder|ProjectPost whereProjektId($value)
 * @method static Builder|ProjectPost whereTitelId($value)
 *
 * @mixin \Eloquent
 */
class ProjectPost extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projektposten';

    public $timestamps = false;

    protected $dispatchesEvents = [
        'updating' => UpdatingModel::class,
    ];

    protected $guarded = ['id', 'projekt_id'];

    protected function casts(): array
    {
        return [
            'einnahmen' => MoneyDecimalCast::class,
            'ausgaben' => MoneyDecimalCast::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'projekt_id', 'id');
    }

    /**
     * @return HasMany all expense receipt posts for this project post
     */
    public function expensePosts(): HasMany
    {
        return $this->hasMany(ExpenseReceiptPost::class, 'projekt_posten_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(LegacyBudgetItem::class, 'titel_id');
    }

    public function expendedSum(): Money
    {
        if ($this->ausgaben->isZero()) {
            return Money::EUR($this->expensePosts()->sum('einnahmen'), true);
        }

        return Money::EUR($this->expensePosts()->sum('ausgaben'), true);
    }

    public function expendedRatio(): int
    {
        if ($this->expensePosts()->exists() && ! $this->ausgaben->isZero()) {
            return (int) ($this->expendedSum()->ratioOf($this->ausgaben) * 100);
        }
        if ($this->expensePosts()->exists() && ! $this->einnahmen->isZero()) {
            return (int) ($this->expendedSum()->ratioOf($this->einnahmen) * 100);
        }

        return 0;
    }
}
