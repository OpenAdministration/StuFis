<?php

namespace App\Models\Legacy;

use App\Events\UpdatingModel;
use App\Models\BudgetPlan;
use App\Models\User;
use App\States\Project\ProjectState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Cknow\Money\Money;
use Spatie\ModelStates\HasStates;

/**
 * App\Models\Legacy\Project
 *
 * @property int $id
 * @property int $creator_id
 * @property Carbon $createdat
 * @property Carbon $lastupdated
 * @property int $version
 * @property ProjectState $state
 * @property int $stateCreator_id
 * @property string $name
 * @property string $org
 * @property string $org_mail
 * @property string $protokoll
 * @property string $recht
 * @property string $recht_additional
 * @property Carbon $date_start
 * @property Carbon $date_end
 * @property string $beschreibung
 * @property Collection<Expense> $expenses
 * @property User $user
 * @property-read Collection<ProjectPost> $posts
 * @property-read int|null $posts_count
 * @property-read User $creator
 * @property-read int|null $expenses_count
 * @property-read User $stateCreator
 *
 * @method static Builder|Project newModelQuery()
 * @method static Builder|Project newQuery()
 * @method static Builder|Project query()
 * @method static Builder|Project whereBeschreibung($value)
 * @method static Builder|Project whereCreatedat($value)
 * @method static Builder|Project whereCreatorId($value)
 * @method static Builder|Project whereDateEnd($value)
 * @method static Builder|Project whereDateStart($value)
 * @method static Builder|Project whereId($value)
 * @method static Builder|Project whereLastupdated($value)
 * @method static Builder|Project whereName($value)
 * @method static Builder|Project whereOrg($value)
 * @method static Builder|Project whereOrgMail($value)
 * @method static Builder|Project whereProtokoll($value)
 * @method static Builder|Project whereRecht($value)
 * @method static Builder|Project whereRechtAdditional($value)
 * @method static Builder|Project whereResponsible($value)
 * @method static Builder|Project whereState($value)
 * @method static Builder|Project whereStateCreatorId($value)
 * @method static Builder|Project whereVersion($value)
 * @method static \Database\Factories\Legacy\ProjectFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 *
 * @property string $responsible The responsible person's email (domain will be automatically appended if missing)
 */
class Project extends Model
{
    use HasFactory;
    use HasStates;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projekte';

    const CREATED_AT = 'createdat';
    const UPDATED_AT = 'lastupdated';

    public $casts = [
        'state' => ProjectState::class,
        'createdat' => 'datetime',
        'lastupdated' => 'datetime',
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    /**
     * @var array
     */
    protected $fillable = ['creator_id', 'createdat', 'lastupdated', 'version', 'state', 'stateCreator_id', 'name', 'responsible', 'org', 'org_mail', 'protokoll', 'recht', 'recht_additional', 'date_start', 'date_end', 'beschreibung'];

    protected function responsible(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => empty($value) || str_contains($value, '@') ? $value : $value . '@' . config('stufis.mail_domain'),
            set: fn(string $value) => empty($value) || str_contains($value, '@') ? $value : $value . '@' . config('stufis.mail_domain'),
        );
    }

    public function getLegal() : array
    {
        return config("stufis.project_legal.$this->recht", []);
    }

    protected $dispatchesEvents = [
        'updating' => UpdatingModel::class,
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'projekt_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function stateCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stateCreator_id');
    }

    public function relatedBudgetPlan() : LegacyBudgetPlan
    {
        return LegacyBudgetPlan::findByDate($this->createdat);
    }

    /**
     * Get the ordered posts associated with the project.
     *
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ProjectPost::class, 'projekt_id')->orderBy('position');
    }

    public function expensePosts() : HasManyThrough
    {
        return $this->throughPosts()->hasExpensePosts();
    }

    public function totalAusgaben() : Money
    {
        return $this->posts()->sumMoney('ausgaben');
    }

    public function totalUsedAusgaben() : Money
    {
        return $this->expensePosts()->sumMoney('beleg_posten.ausgaben');
    }

    public function totalRemainingAusgaben(): Money
    {
        return $this->posts()->sumMoney('ausgaben')->subtract($this->totalUsedAusgaben());
    }

    public function totalRatioAusgaben() : int
    {
        $out = $this->totalAusgaben();
        if($out->isZero()){
            return 0;
        }
        return (int) ($this->totalUsedAusgaben()->ratioOf($out) * 100);
    }

    public function totalEinnahmen() : Money
    {
        return $this->posts()->sumMoney('einnahmen');
    }

    public function totalUsedEinnahmen() : Money
    {
        return $this->expensePosts()->sumMoney('beleg_posten.einnahmen');
    }

    public function totalRemainingEinnahmen(): Money
    {
        return $this->posts()->sumMoney('einnahmen')->subtract($this->totalUsedEinnahmen());
    }

    public function totalRatioEinnahmen() : int
    {
        $in = $this->totalEinnahmen();
        if($in->isZero()){
            return 0;
        }
        return (int) ($this->totalUsedEinnahmen()->ratioOf($in) * 100);
    }

}
