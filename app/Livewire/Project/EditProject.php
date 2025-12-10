<?php

namespace App\Livewire\Project;

use App\Models\Legacy\ExpenseReceiptPost;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use App\Models\Legacy\ProjectPost;
use App\States\Project\ProjectState;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProject extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $state_name;

    public ProjectForm $form;

    #[Url]
    public ?int $project_id = null;

    public bool $isNew;

    public Collection $posts;

    // UI state
    public string $selectedRechtKey = '';

    public array $attachments = [];

    public function mount(): void
    {
        $this->isNew = is_null($this->project_id);

        if ($this->isNew) {
            Gate::authorize('create', Project::class);
            $this->form->initializeNew();
            $this->posts = collect();
            $this->attachments = [];
            $this->state_name = 'draft';

            $this->addEmptyPost();
        } else {
            $project = Project::findOrFail($this->project_id);
            Gate::authorize('update', $project);
            $this->form->setProject($project);
            $this->state_name = $project->state->getValue();
            $this->posts = $project->posts->map(function (ProjectPost $post) {
                return [
                    'id' => $post->id,
                    'name' => $post->name,
                    'bemerkung' => $post->bemerkung ?? '',
                    'einnahmen' => $post->einnahmen,
                    'ausgaben' => $post->ausgaben,
                    'titel_id' => $post->titel_id,
                ];
            });
            $this->attachments = []; // FIXME: load Attachments
        }
    }

    public function isPostDeletable(int $index): bool
    {
        return
            $this->posts->count() > 1 && (
                (isset($this->posts[$index]['id']) && ExpenseReceiptPost::where('projekt_posten_id', $this->posts[$index]['id'])->doesntExist())
                || ! isset($this->posts[$index]['id']));
    }

    /**
     * Remove a post by index
     */
    public function removePost(int $index): void
    {
        if ($this->isPostDeletable($index)) {
            $this->posts->forget($index);
        }
    }

    /**
     * Save the project
     */
    public function save()
    {
        // $this->validate();
        // $this->form->validate();
        try {
            DB::beginTransaction();
            if ($this->isNew) {
                $project = Project::create([
                    'creator_id' => Auth::id(),
                    'stateCreator_id' => Auth::id(),
                    ...($this->form->getValues()),
                ]);
            } else {
                $project = Project::findOrFail($this->project_id);
                // Check if the project has been modified since the last load
                if ($project->version != $this->form->version) {
                    $this->addError('save', 'Das Projekt wurde zwischenzeitlich von jemand anderem bearbeitet. Bitte laden Sie die Seite neu.');

                    return;
                }
                $project->update([
                    ...$this->form->getValues(),
                    'version' => $project->version + 1,
                ]);
            }
            foreach ($this->posts as $post) {
                if (isset($post['id'])) {
                    $project->posts()->findOrFail($post['id'])->update($post);
                } else {
                    $project->posts()->create($post);
                }
            }
            DB::commit();

            return redirect()->route('project.show', $project->id);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('save', 'Fehler beim Speichern: '.$e->getMessage());
        }
    }

    /**
     * Update existing project
     */
    protected function updateProject()
    {
        $project = Project::findOrFail($this->project_id);

        $updateData = [
            'lastupdated' => now(),
            'version' => $project->version + 1,
        ];

        // Only update fields that the user has permission to edit
        foreach ($this->form->toArray() as $field => $value) {
            if (Gate::allows('update-field', [$project, $field])) {
                $updateData[$field] = $value ?: null;
            }
        }

        // FIXME: increment version
        // FIXME: save posts
        $project->update($updateData);
        $this->form->version = $project->version;

    }

    /**
     * Add an empty post row
     */
    public function addEmptyPost(): void
    {
        $this->posts->add([
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
        return $this->posts->reduce(function (?Money $carry, array $post) {
            return $carry ? $carry->add($post['einnahmen']) : $post['einnahmen'];
        }, Money::EUR(0));
    }

    /**
     * Get the sum of all expense posts
     */
    public function getTotalExpenses(): Money
    {
        return $this->posts->reduce(function (?Money $carry, array $post) {
            return $carry ? $carry->add($post['ausgaben']) : $post['ausgaben'];
        }, Money::EUR(0));
    }

    public function removeAttachment(int $index): void
    {
        $photo = $this->attachments[$index];
        $photo->delete();
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    /**
     * Get budget title options based on the project creation date
     */
    protected function getBudgetTitleOptions(): \Illuminate\Database\Eloquent\Collection
    {
        $plan = LegacyBudgetPlan::findOrFail($this->form->hhp_id);

        return $plan->budgetItems;
    }

    /**
     * Get Rechtsgrundlagen options
     */
    protected function getRechtsgrundlagenOptions(): array
    {
        $rechtsgrundlagen = config('stufis.project_legal', []);

        return collect($rechtsgrundlagen)->map(function ($def, $key) {
            return [
                'key' => $key,
                'label' => $def['label'] ?? $key,
                'placeholder' => $def['placeholder'] ?? '',
                'label_additional' => $def['label-additional'] ?? 'Zusatzinformationen',
                'hint' => $def['hint-text'] ?? '',
                'has_additional' => isset($def['placeholder'], $def['label-additional']),
            ];
        })->toArray();
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
        $state = ProjectState::make($this->state_name, new Project);
        $budgetPlans = LegacyBudgetPlan::all();

        return view('livewire.project.edit-project', compact(
            'gremien', 'mailingLists', 'budgetTitles', 'rechtsgrundlagen', 'state', 'budgetPlans'
        ));
    }
}
