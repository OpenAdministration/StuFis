<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Legacy\LegacyBudgetGroup
 *
 * @property int $id
 * @property int $hhp_id
 * @property string $gruppen_name
 * @property int $type
 * @property-read Collection<int, LegacyBudgetItem> $budgetItems
 * @property-read int|null $budget_items_count
 *
 * @method static Builder|LegacyBudgetGroup newModelQuery()
 * @method static Builder|LegacyBudgetGroup newQuery()
 * @method static Builder|LegacyBudgetGroup query()
 * @method static Builder|LegacyBudgetGroup whereGruppenName($value)
 * @method static Builder|LegacyBudgetGroup whereHhpId($value)
 * @method static Builder|LegacyBudgetGroup whereId($value)
 * @method static Builder|LegacyBudgetGroup whereType($value)
 *
 * @mixin \Eloquent
 */
class LegacyBudgetGroup extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'haushaltsgruppen';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['hhp_id', 'gruppen_name', 'type'];

    public function budgetItems(): HasMany
    {
        return $this->hasMany(LegacyBudgetItem::class, 'hhpgruppen_id');
    }
}
