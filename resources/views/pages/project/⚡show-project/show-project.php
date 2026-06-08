<?php

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Project;
use App\Models\Setting;
use App\States\Project\Draft;
use App\States\Project\ProjectState;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\Validation\ValidStateRule;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    #[Url]
    public $project_id;

    public $newState;

    public $fileUrl;

    public function with(): array
    {
        $project = Project::findOrFail($this->project_id);
        $state = $project->state;

        $showApproval = \Auth::user()->getGroups()->has('ref-finanzen-hv') || !$state->equals(Draft::class);
        $showLink = Setting::get('project.protocol_url.active');

        $userCanDelete = \Auth::user()->can('delete', $project);
        $deletionAllowed = $project->expenses()->count() === 0;

        return compact('project', 'showApproval', 'showLink', 'userCanDelete', 'deletionAllowed');
    }

    public function changeState(): void
    {
        // check if given state string a valid state for this project
        $project = Project::findOrFail($this->project_id);
        $filtered = $this->validate(['newState' => ['required', new ValidStateRule(ProjectState::class)]]);
        $newState = ProjectState::make($filtered['newState'], $project);
        // Business Logic check: are some values missing for the new state
        $v = $newState->getValidator();
        $v->validate();
        // Authorization check: can the user transition to this state
        $this->authorize('transition-to', [$project, $newState]);

        try {
            $oldState = $project->state;
            $project->state->transitionTo($this->newState);
            ChatMessage::create([
                'text' => "{$oldState->label()} -> {$newState->label()}",
                'type' => ChatMessageType::SYSTEM,
                'target' => 'projekt',
                'target_id' => $project->id,
                'creator' => \Auth::user()->username,
                'creator_alias' => \Auth::user()->name,
                'timestamp' => now(),
            ]);
            Flux::modal('state-modal')->close();
            $this->reset('newState');
        } catch (CouldNotPerformTransition $e) {
            $this->addError('newState', $e->getMessage());
        }
    }

    public function delete(): void
    {
        $project = Project::findOrFail($this->project_id);
        $this->authorize('delete', $project);
        if ($project->expenses()->count() > 0) {
            $this->addError('delete', 'Cannot delete project with expenses');
            return;
        }

        $project->posts()->delete();
        $project->attachments()->delete();
        Storage::deleteDirectory('projects/'.$project->id);
        $project->delete();
        $this->redirect(route('home'));
    }
};
