<?php

namespace App\Livewire\Project;

use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectPost;
use App\States\Project\ProjectState;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
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
        $this->posts = $project->posts->map(fn (ProjectPost $post) => [
            'id' => $post->id,
            'name' => $post->name,
            'bemerkung' => $post->bemerkung ?? '',
            'einnahmen' => $post->einnahmen,
            'ausgaben' => $post->ausgaben,
            'titel_id' => $post->titel_id,
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

    public function isPostDeletable(int $index): bool
    {
        return
            count($this->posts) > 1 && (
                (isset($this->posts[$index]['id']) && ExpenseReceiptPost::where('projekt_posten_id', $this->posts[$index]['id'])->doesntExist())
                || ! isset($this->posts[$index]['id']));
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
        $this->authorize('update', $this->getProject());
        $state = ProjectState::make($stateName, $this->getProject() ?? new Project);
        $validator = Validator::make(
            $this->getValues() + [
                'uploads' => $this->newAttachments,
                'deletedAttachments' => $this->deletedAttachmentIds
            ],
            $state->rules() + ['uploads.*' =>
                File::types(['pdf', 'xlsx', 'ods'])->extensions(['pdf', 'xlsx', 'ods'])->max("5 Mb"),
                'deletedAttachments' => 'array',
                'deletedAttachments.*' => 'integer',
            ]
        );
        $filtered = collect($validator->validate());
        $filteredPosts = $filtered->pull('posts') ?? [];
        $newAttachments = $filtered->pull('uploads') ?? [];
        $deletedAttachmentIds = $filtered->pull('deletedAttachments') ?? [];
        $filteredMeta = $filtered->all();

        try {
            DB::beginTransaction();
            if ($this->isNew) {
                $project = Project::create([
                    'creator_id' => Auth::id(),
                    'stateCreator_id' => Auth::id(),
                    ...$filteredMeta,
                ]);
            } else {
                $project = Project::findOrFail($this->project_id);
                // Check if the project has been modified since the last load
                if ($project->version !== $this->version) {
                    $this->addError('save', 'Das Projekt wurde zwischenzeitlich von jemand anderem bearbeitet. Bitte laden Sie die Seite neu.');

                    return;
                }
                $project->update([
                    ...$filteredMeta,
                    'version' => $project->version + 1,
                ]);
            }

            if(!$project->state->equals($state)){
                $project->state->transitionTo($state);
            }

            foreach ($filteredPosts as $post) {
                if (isset($post['id'])) {
                    $project->posts()->findOrFail($post['id'])->update($post);
                } else {
                    $project->posts()->create($post);
                }
            }

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
            DB::rollBack();
            $this->addError('save', 'Fehler beim Speichern: '.$e->getMessage());
        }
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
        ]);
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
    protected function getRechtsgrundlagenOptions(): array
    {
        $rechtsgrundlagen = config('stufis.project_legal', []);

        return collect($rechtsgrundlagen)->map(fn ($def, $key) => [
            'key' => $key,
            'label' => $def['label'] ?? $key,
            'placeholder' => $def['placeholder'] ?? '',
            'label_additional' => $def['label-additional'] ?? 'Zusatzinformationen',
            'hint' => $def['hint-text'] ?? '',
            'has_additional' => isset($def['placeholder'], $def['label-additional']),
        ])->all();
    }

    /**
     * Get mailing list options
     */
    protected function getMailingListOptions(): array
    {
        $hasFinanceGroup = Auth::user()->getGroups()->contains('ref-finanzen');

        if ($hasFinanceGroup) {
            return config('org_data.mailinglists', []);
        }

        // Return only user's mailing lists
        return Auth::user()->mailinglists ?? [];
    }

    public function render()
    {
        $gremien = Auth::user()->getCommittees();
        $mailingLists = [];
        $rechtsgrundlagen = $this->getRechtsgrundlagenOptions();
        $budgetTitles = $this->getBudgetTitleOptions();
        $state = $this->getState();
        $budgetPlans = LegacyBudgetPlan::all();

        return view('livewire.project.edit-project', compact(
            'gremien', 'mailingLists', 'budgetTitles', 'rechtsgrundlagen', 'state', 'budgetPlans'
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
