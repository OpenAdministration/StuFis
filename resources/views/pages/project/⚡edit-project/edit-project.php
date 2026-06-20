<?php

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectAttachment;
use App\Models\Legacy\ProjectPost;
use App\Models\LegalBasis;
use App\Models\Setting;
use App\Models\TaxBudget;
use App\States\Project\ProjectState;
use App\States\Project\Terminated;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    use WithFileUploads;

    #[Url]
    public ?int $project_id = null;

    /** Source project + kind ('copy' | 'leftovers'): entry params and persisted backlink. */
    #[Url]
    public ?int $sourceId = null;

    #[Url]
    public ?string $sourceKind = null;

    #[Locked]
    public string $state_name;

    #[Locked]
    public bool $isNew;

    // Form data
    public string $name = '';

    public string $responsible = '';

    public string $org = '';

    public string $org_mail = '';

    public string $protokoll = '';

    public string $beschreibung = '';

    public string $recht = '';

    public string $recht_additional = '';

    public array $dateRange = [];

    public int $hhp_id;

    public int $version = 1;

    public array $posts;

    public array $existingAttachments = [];

    public array $newAttachments = [];

    public array $deletedAttachmentIds = [];

    /** Source attachment ids to physically duplicate into a copied project on save. */
    public array $carryAttachmentIds = [];

    public function mount(): void
    {
        $this->isNew = is_null($this->project_id);

        if (! $this->isNew) {
            $project = Project::findOrFail($this->project_id);
            Gate::authorize('update', $project);
            $this->populateData($project);

            return;
        }

        Gate::authorize('create', Project::class);

        $source = $this->sourceId !== null ? Project::find($this->sourceId) : null;

        if ($source !== null && $this->sourceKind === 'copy') {
            Gate::authorize('view', $source);
            $this->populateFromCopy($source);
        } elseif ($source !== null && $this->sourceKind === 'leftovers') {
            Gate::authorize('view', $source);
            // Leftovers can only be carried over from a finished (terminated) project.
            abort_unless($source->state->equals(Terminated::class), 403);
            $this->populateFromLeftovers($source);
        } else {
            // Ignore any stray/invalid source params for a plain new project.
            $this->sourceId = null;
            $this->sourceKind = null;
            $this->populateData(new Project);
            $this->addEmptyPost();
        }
    }

    /**
     * Copy the editable meta fields from a source project (excluding state,
     * version, posts, attachments and the budget plan binding).
     */
    private function copyMetaFrom(Project $source): void
    {
        $this->name = $source->name ?? '';
        $this->responsible = $source->responsible ?? '';
        $this->org = $source->org ?? '';
        $this->org_mail = $source->org_mail ?? '';
        $this->protokoll = $source->protokoll ?? '';
        $this->beschreibung = $source->beschreibung ?? '';
        $this->recht = $source->recht ?? '';
        $this->recht_additional = $source->recht_additional ?? '';
        $this->dateRange = [
            'start' => $source->date_start ?? null,
            'end' => $source->date_end ?? null,
        ];
    }

    /**
     * Prefill the form as a fresh draft duplicating an existing project. Stays
     * in the source's budget plan, so post titel_ids remain valid.
     */
    private function populateFromCopy(Project $source): void
    {
        $this->populateData(new Project);
        $this->copyMetaFrom($source);
        $this->name = trim($source->name.__('project.view.edit.name_copy_suffix'));
        $this->hhp_id = LegacyBudgetPlan::findByDate($source->createdat)?->id ?? $this->hhp_id;
        $this->sourceId = $source->id;
        $this->sourceKind = 'copy';

        $this->posts = $source->posts->map(fn (ProjectPost $post) => [
            'name' => $post->name,
            'bemerkung' => $post->bemerkung ?? '',
            'einnahmen' => $post->einnahmen,
            'ausgaben' => $post->ausgaben,
            'titel_id' => $post->titel_id,
            'readonly' => false,
        ])->all();

        if ($this->posts === []) {
            $this->addEmptyPost();
        }

        // Show the source attachments as carried over; they are physically
        // duplicated into the new project on save (see saveAs()).
        $this->existingAttachments = $source->attachments->map(
            fn (ProjectAttachment $attachment) => $attachment->only('id', 'path', 'name', 'mime_type', 'size')
        )->all();
        $this->carryAttachmentIds = $source->attachments->pluck('id')->all();
    }

    /**
     * Prefill the form as a fresh draft carrying the unspent remainder of an
     * existing project into the latest budget plan. Titel are remapped across
     * plans by titel_nr; fully-spent posts are dropped.
     */
    private function populateFromLeftovers(Project $source): void
    {
        $this->populateData(new Project);
        $this->copyMetaFrom($source);
        $this->name = trim($source->name.__('project.view.edit.name_leftovers_suffix'));
        $this->sourceId = $source->id;
        $this->sourceKind = 'leftovers';

        $targetPlanId = LegacyBudgetPlan::latest()->id;
        $this->hhp_id = $targetPlanId;

        $this->posts = $source->posts
            ->map(function (ProjectPost $post) use ($targetPlanId): ?array {
                $isIncome = $post->ausgaben->isZero();
                $planned = $isIncome ? $post->einnahmen : $post->ausgaben;
                $remaining = $planned->subtract($post->expendedSum());
                if (! $remaining->isPositive()) {
                    return null;
                }

                return [
                    'name' => $post->name,
                    'bemerkung' => $post->bemerkung ?? '',
                    'einnahmen' => $isIncome ? $remaining : Money::EUR(0),
                    'ausgaben' => $isIncome ? Money::EUR(0) : $remaining,
                    'titel_id' => $this->remapTitelId($post->titel_id, $targetPlanId),
                    'readonly' => false,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($this->posts === []) {
            $this->addEmptyPost();
        }
    }

    /**
     * Map a titel_id from its current plan onto the matching titel in the target
     * plan via the stable titel_nr. Returns null when there is no match.
     */
    public function remapTitelId(?int $oldTitelId, int $targetPlanId): ?int
    {
        if ($oldTitelId === null) {
            return null;
        }

        $titelNr = LegacyBudgetItem::find($oldTitelId)?->titel_nr;
        if ($titelNr === null) {
            return null;
        }

        return LegacyBudgetPlan::find($targetPlanId)
            ?->budgetItems
            ->firstWhere('titel_nr', $titelNr)?->id;
    }

    /**
     * When the budget plan changes, remap every post's titel into the newly
     * selected plan (e.g. a finance officer moving the project to another plan).
     */
    public function updatedHhpId(): void
    {
        $this->posts = collect($this->posts)->map(function (array $post): array {
            $post['titel_id'] = $this->remapTitelId(
                $post['titel_id'] !== null ? (int) $post['titel_id'] : null,
                $this->hhp_id,
            );

            return $post;
        })->all();
    }

    private function populateData(Project $project): void
    {
        $this->name = $project->name ?? '';
        $this->responsible = $project->responsible ?? '';
        $this->org = $project->org ?? '';
        $this->org_mail = $project->org_mail ?? '';
        $this->protokoll = $project->protokoll ?? '';
        $this->beschreibung = $project->beschreibung ?? '';
        $this->recht = $project->recht ?? '';
        $this->recht_additional = $project->recht_additional ?? '';
        $this->dateRange = [
            'start' => $project->date_start ?? null,
            'end' => $project->date_end ?? null,
        ];
        $this->version = $project->version ?? 1;
        $this->hhp_id = LegacyBudgetPlan::findByDate($project->createdat)?->id;
        $this->state_name = $project->state->getValue();

        $bookedExpenses = $project->expenses()->where('state', 'like', 'booked%')->get();
        $readOnlyPosts = $bookedExpenses->flatMap->posts->pluck('projekt_posten_id')->unique();
        $this->posts = $project->posts->map(fn (ProjectPost $post) => [
            'id' => $post->id,
            'name' => $post->name,
            'bemerkung' => $post->bemerkung ?? '',
            'einnahmen' => $post->einnahmen,
            'ausgaben' => $post->ausgaben,
            'titel_id' => $post->titel_id,
            'readonly' => $readOnlyPosts->contains($post->id),
        ])->all();
        $this->existingAttachments = $project->attachments->map(
            fn ($attachment) => $attachment->only('id', 'path', 'name', 'mime_type', 'size')
        )->all();
    }

    /**
     * Translates the livewire properties into the ones expected by the validator and the project model.
     *
     * @return array<string, mixed>
     */
    private function getValues(): array
    {
        return [
            'name' => $this->name,
            'responsible' => $this->responsible,
            'org' => $this->org,
            'org_mail' => $this->org_mail,
            'protokoll' => $this->protokoll,
            'beschreibung' => $this->beschreibung,
            'recht' => $this->recht,
            'recht_additional' => $this->recht_additional,
            // make compatible with legacy database
            'date_start' => $this->dateRange['start'] ?? null,
            'date_end' => $this->dateRange['end'] ?? null,
            'version' => $this->version,
            'createdat' => Date::parse(LegacyBudgetPlan::find($this->hhp_id)->von)->addDays(7),
            'posts' => $this->posts,
        ];
    }

    /**
     * Add an empty post row
     */
    public function addEmptyPost(): void
    {
        $this->posts[] = ([
            'name' => '',
            'bemerkung' => '',
            'einnahmen' => Money::EUR(0),
            'ausgaben' => Money::EUR(0),
            'titel_id' => null,
            'readonly' => false,
        ]);
    }

    public function addTaxPosts(): void
    {
        TaxBudget::where('hhp_id', $this->hhp_id)->get()->each(function (TaxBudget $taxBudget): void {
            $budgetTitle = $taxBudget->legacyBudgetTitle;
            $this->posts[] = ([
                'name' => $budgetTitle->titel_name.' - Einnahmen',
                'bemerkung' => 'Steuer',
                'einnahmen' => Money::EUR($taxBudget->tax_percent),
                'ausgaben' => Money::EUR(0),
                'titel_id' => $budgetTitle->id,
                'readonly' => false,
            ]);
            $this->posts[] = ([
                'name' => $budgetTitle->titel_name.' - Ausgaben',
                'bemerkung' => 'Steuer',
                'einnahmen' => Money::EUR(0),
                'ausgaben' => Money::EUR($taxBudget->tax_percent),
                'titel_id' => $budgetTitle->id,
                'readonly' => false,
            ]);
        });
    }

    public function isPostDeletable(int $index): bool
    {
        return count($this->posts) > 1 && (
            (isset($this->posts[$index]['id']) && ExpenseReceiptPost::where('projekt_posten_id', $this->posts[$index]['id'])->doesntExist())
            || ! isset($this->posts[$index]['id'])
        );
    }

    /**
     * Remove a post by index
     */
    public function removePost(int $index): void
    {
        if ($this->isPostDeletable($index)) {
            unset($this->posts[$index]);
        }
    }

    /**
     * Save the project
     */
    public function saveAs($stateName)
    {
        try { // rollback on failure
            // check if user is allowed to save
            if ($this->isNew) {
                $this->authorize('create', Project::class);
            } else {
                $this->authorize('update', $this->getProject());
                // Check optimistic locking before validation
                if ($this->getProject()->version !== $this->version) {
                    $this->addError('save', __('project.error.version-mismatch'));

                    return;
                }
            }
            $project = $this->getProject();
            $canUpdateBudget = Auth::user()->can('update-budget', $project);
            $canUpdateApproval = Auth::user()->can('update-approval', $project);
            // prepare data and rules
            $state = ProjectState::make($stateName, $project);
            $data = $this->getValues() + [
                'uploads' => $this->newAttachments,
                'deletedAttachments' => $this->deletedAttachmentIds,
            ];
            $rules = $state->basicRules() + [
                'uploads.*' => File::types(['pdf', 'xlsx', 'ods'])
                    ->extensions(['pdf', 'xlsx', 'ods'])->max('5 Mb'),
                'deletedAttachments' => 'array',
                'deletedAttachments.*' => 'integer',
            ];
            $rules += $canUpdateBudget ? $state->budgetRules() : [];
            $rules += $canUpdateApproval ? $state->approvalRules() : [];

            // validate data and prepare filtered data to be saved
            $validator = Validator::make($data, $rules);
            $filteredData = collect($validator->validate());
            $filteredPosts = $filteredData->pull('posts') ?? [];
            $newAttachments = $filteredData->pull('uploads') ?? [];
            $deletedAttachmentIds = $filteredData->pull('deletedAttachments') ?? [];
            $filteredMeta = $filteredData->all();

            // save data
            DB::beginTransaction();
            if ($this->isNew) {
                $hasValidSource = in_array($this->sourceKind, ['copy', 'leftovers'], true) && $this->sourceId !== null;
                $project = Project::create([
                    'creator_id' => Auth::id(),
                    'stateCreator_id' => Auth::id(),
                    'source_id' => $hasValidSource ? $this->sourceId : null,
                    'source_kind' => $hasValidSource ? $this->sourceKind : null,
                    ...$filteredMeta,
                ]);
            } else {
                $project = Project::findOrFail($this->project_id);
                $project->update([
                    ...$filteredMeta,
                    'version' => $project->version + 1,
                ]);
            }

            if (! $project->state->equals($state)) {
                ChatMessage::create([
                    'text' => "{$project->state->label()} -> {$state->label()}",
                    'type' => ChatMessageType::SYSTEM,
                    'target' => 'projekt',
                    'target_id' => $project->id,
                    'creator' => \Auth::user()->username,
                    'creator_alias' => \Auth::user()->name,
                    'timestamp' => now(),
                ]);
                $project->state->transitionTo($state);
            }

            foreach ($filteredPosts as $post) {
                if (isset($post['id'])) {
                    $project->posts()->findOrFail($post['id'])->update($post);
                } else {
                    $project->posts()->create($post);
                }
            }
            // delete posts that are not in the filtered posts array
            // TODO: write a test for this edge case (post is not deletable)
            $bookedExpenses = $project->expenses()->where('state', 'like', 'booked%')->get();
            $readOnlyPosts = $bookedExpenses->flatMap->posts->pluck('projekt_posten_id')->unique();
            $project->posts()->whereNotIn('id', collect($filteredPosts)->pluck('id'))
                ->whereNotIn('id', $readOnlyPosts)->delete();

            foreach ($newAttachments as $attachment) {
                // Read metadata BEFORE store(): when the default disk equals the
                // Livewire temp-upload disk (both "local" in production), store()
                // *moves* the temp file away, after which getSize()/getMimeType()
                // throw "Unable to retrieve metadata" on the gone livewire-tmp file.
                // (Masked in tests by Livewire's runningUnitTests meta-file shim.)
                $name = $attachment->getClientOriginalName();
                $mimeType = $attachment->getMimeType();
                $size = $attachment->getSize();

                $path = $attachment->store('projects/'.$project->id);

                $project->attachments()->create([
                    'path' => $path,
                    'name' => $name,
                    'mime_type' => $mimeType,
                    'size' => $size,
                ]);
            }

            // Duplicate carried-over attachments (project copy) into the new
            // project's storage, leaving the source files untouched.
            foreach ($this->carryAttachmentIds as $sourceId) {
                $source = ProjectAttachment::find($sourceId);
                if ($source === null || ! Storage::exists($source->path)) {
                    continue;
                }
                $extension = pathinfo((string) $source->path, PATHINFO_EXTENSION);
                $newPath = 'projects/'.$project->id.'/'.uniqid('', true).($extension !== '' ? '.'.$extension : '');
                Storage::copy($source->path, $newPath);

                $project->attachments()->create([
                    'path' => $newPath,
                    'name' => $source->name,
                    'mime_type' => $source->mime_type,
                    'size' => $source->size,
                ]);
            }

            foreach ($deletedAttachmentIds as $id) {
                $pa = ProjectAttachment::where('id', $id)->where('projekt_id', $this->project_id)->findOrFail();
                Storage::delete($pa->path);
                $pa->delete();
            }

            DB::commit();

            return to_route('project.show', $project->id);
        } catch (Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->addError('save', 'Fehler beim Speichern: '.$e->getMessage());
        }
    }

    /**
     * Get the sum of all income posts
     */
    public function getTotalIncome(): Money
    {
        return collect($this->posts)->reduce(fn (?Money $carry, array $post) => $carry instanceof Money ? $carry->add($post['einnahmen']) : $post['einnahmen'], Money::EUR(0));
    }

    /**
     * Get the sum of all expense posts
     */
    public function getTotalExpenses(): Money
    {
        return collect($this->posts)->reduce(fn (?Money $carry, array $post) => $carry instanceof Money ? $carry->add($post['ausgaben']) : $post['ausgaben'], Money::EUR(0));
    }

    public function removeExistingAttachment(int $id): void
    {
        if ($this->isNew) {
            // Carried-over (copied) attachment: just drop it from the carry set,
            // never schedule the source's own attachment row for deletion.
            $this->carryAttachmentIds = array_values(array_filter(
                $this->carryAttachmentIds,
                fn (int $carryId) => $carryId !== $id
            ));
        } else {
            $this->deletedAttachmentIds[] = $id;
        }
        $this->existingAttachments = array_filter(
            $this->existingAttachments,
            fn ($a) => $a['id'] !== $id
        );
    }

    public function removeNewAttachment(int|string $index): void
    {
        $this->newAttachments[$index]->delete();
        unset($this->newAttachments[$index]);
        $this->newAttachments = array_values($this->newAttachments);
    }

    /**
     * Get budget title options based on the project creation date
     */
    protected function getBudgetTitleOptions(): Illuminate\Database\Eloquent\Collection
    {
        $plan = LegacyBudgetPlan::findOrFail($this->hhp_id);

        return $plan->budgetItems;
    }

    /**
     * Get Rechtsgrundlagen options
     */
    protected function getRechtsgrundlagenOptions(): Collection
    {
        return LegalBasis::ordered()->active()->get()->keyBy('slug');
    }

    public function with(): array
    {
        // variables
        if (Auth::user()->can('pick-any-committee', Project::class)) {
            $gremien = Setting::get('user.committees.data');
        } else {
            $gremien = Auth::user()->getCommittees();
        }
        $rechtsgrundlagen = $this->getRechtsgrundlagenOptions();
        $state = $this->getState();
        $budgetTitles = $this->getBudgetTitleOptions();
        $budgetPlans = LegacyBudgetPlan::all();
        // settings
        $protocolLinkSetting = Setting::get('project.protocol_url');
        // permissions
        $canUpdateBudget = Auth::user()->can('update-budget', $this->getProject());
        $canUpdateBudgetPlan = $canUpdateBudget
            && collect($this->posts)->filter(fn ($post) => $post['readonly'])->isEmpty();
        $canUpdateApproval = Auth::user()->can('update-approval', $this->getProject());

        $hasTaxTitels = TaxBudget::where('hhp_id', $this->hhp_id)->exists();
        $canAddTaxTitles = collect($this->posts)->filter(fn ($post) => $post['bemerkung'] === 'Steuer')->isEmpty();

        // Backlink to the origin project: for a new copy/leftovers draft it comes
        // from the entry params; for an existing project it comes from the record.
        $backlinkSourceId = $this->isNew ? $this->sourceId : $this->getProject()->source_id;
        $backlinkSourceKind = $this->isNew ? $this->sourceKind : $this->getProject()->source_kind;

        return compact(
            'gremien', 'budgetTitles', 'rechtsgrundlagen', 'state',
            'budgetPlans', 'canUpdateBudget', 'canUpdateApproval', 'canUpdateBudgetPlan', 'protocolLinkSetting',
            'hasTaxTitels', 'canAddTaxTitles', 'backlinkSourceId', 'backlinkSourceKind',
        );
    }

    #[Computed]
    public function getState(): ProjectState
    {
        return ProjectState::make($this->state_name, $this->getProject());
    }

    #[Computed]
    public function getProject(): Project
    {
        return Project::find($this->project_id) ?? new Project;
    }
};
