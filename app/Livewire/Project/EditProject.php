<?php

namespace App\Livewire\Project;

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectAttachment;
use App\Models\Legacy\ProjectPost;
use App\Models\LegalBasis;
use App\Models\Setting;
use App\Models\TaxBudget;
use App\States\Project\ProjectState;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProject extends Component
{
    use WithFileUploads;

    #[Url]
    public ?int $project_id = null;

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

    public function mount(): void
    {
        $this->isNew = is_null($this->project_id);

        if ($this->isNew) {
            Gate::authorize('create', Project::class);
            $project = new Project;
            $this->populateData($project);
            $this->addEmptyPost();
        } else {
            $project = Project::findOrFail($this->project_id);
            Gate::authorize('update', $project);
            $this->populateData($project);
        }
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
            fn($attachment) => $attachment->only('id', 'path', 'name', 'mime_type', 'size')
        )->all();
    }

    /**
     * Translates the livewire properties into the ones expected by the validator and the project model.
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

    public function addTaxPosts() : void
    {
        TaxBudget::where('hhp_id', $this->hhp_id)->get()->each(function(TaxBudget$taxBudget){
            $budgetTitle = $taxBudget->legacyBudgetTitle;
            $this->posts[] = ([
                'name' => $budgetTitle->titel_name . ' - Einnahmen',
                'bemerkung' => 'Steuer',
                'einnahmen' => Money::EUR($taxBudget->tax_percent),
                'ausgaben' => Money::EUR(0),
                'titel_id' => $budgetTitle->id,
                'readonly' => false,
            ]);
            $this->posts[] = ([
                'name' => $budgetTitle->titel_name . ' - Ausgaben',
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
            $project = $this->getProject() ?? new Project;
            $canUpdateBudget = Auth::user()->can('update-budget', $project);
            $canUpdateApproval = Auth::user()->can('update-approval', $project);
            // prepare data and rules
            $state = ProjectState::make($stateName, $project);
            $data = $this->getValues() + [
                    'uploads' => $this->newAttachments,
                    'deletedAttachments' => $this->deletedAttachmentIds
                ];
            $rules = $state->basicRules() + [
                    'uploads.*' => File::types(['pdf', 'xlsx', 'ods'])
                        ->extensions(['pdf', 'xlsx', 'ods'])->max("5 Mb"),
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
                $project = Project::create([
                    'creator_id' => Auth::id(),
                    'stateCreator_id' => Auth::id(),
                    ...$filteredMeta,
                ]);
            } else {
                $project = Project::findOrFail($this->project_id);
                $project->update([
                    ...$filteredMeta,
                    'version' => $project->version + 1,
                ]);
            }

            if(!$project->state->equals($state)){
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
                ->whereNotIn('projekt_posten_id', $readOnlyPosts)->delete();

            foreach ($newAttachments as $attachment){
                $attachment->store('projects/'.$project->id);
                $project->attachments()->create([
                    'path' => "projects/$project->id/{$attachment->hashName()}",
                    'name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size' => $attachment->getSize(),
                ]);
            }

            foreach ($deletedAttachmentIds as $id){
                $pa = ProjectAttachment::where('id', $id)->where('projekt_id', $this->project_id)->findOrFail();
                \Storage::delete($pa->path);
                $pa->delete();
            }

            DB::commit();

            return to_route('project.show', $project->id);
        } catch (\Exception $e) {
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
        return collect($this->posts)->reduce(fn (?Money $carry, array $post) => $carry ? $carry->add($post['einnahmen']) : $post['einnahmen'], Money::EUR(0));
    }

    /**
     * Get the sum of all expense posts
     */
    public function getTotalExpenses(): Money
    {
        return collect($this->posts)->reduce(fn (?Money $carry, array $post) => $carry ? $carry->add($post['ausgaben']) : $post['ausgaben'], Money::EUR(0));
    }

    public function removeExistingAttachment(int $id): void
    {
        $this->deletedAttachmentIds[] = $id;
        $this->existingAttachments = array_filter(
            $this->existingAttachments,
            fn($a) => $a['id'] !== $id
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
    protected function getBudgetTitleOptions(): \Illuminate\Database\Eloquent\Collection
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

    public function render()
    {
        // variables
        if(Auth::user()->can('pick-any-committee', Project::class)){
            $gremien = Setting::get('user.committees.data');
        }else{
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
            && collect($this->posts)->filter(fn($post) => $post['readonly'])->isEmpty();
        $canUpdateApproval = Auth::user()->can('update-approval', $this->getProject());

        $hasTaxTitels = TaxBudget::where('hhp_id', $this->hhp_id)->exists();
        $canAddTaxTitles = collect($this->posts)->filter(fn($post) => $post['bemerkung'] === 'Steuer')->isEmpty();

        return view('livewire.project.edit-project', compact(
            'gremien', 'budgetTitles', 'rechtsgrundlagen', 'state',
            'budgetPlans', 'canUpdateBudget', 'canUpdateApproval', 'canUpdateBudgetPlan', 'protocolLinkSetting',
            'hasTaxTitels', 'canAddTaxTitles',
        ));
    }

    #[Computed]
    public function getState(): ProjectState
    {
        return ProjectState::make($this->state_name, $this->getProject() ?? new Project);
    }

    #[Computed]
    public function getProject(): ?Project
    {
        return Project::find($this->project_id);
    }
}
