<?php

namespace App\Livewire\Project;

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Project;
use App\States\Project\Draft;
use App\States\Project\ProjectState;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\Validation\ValidStateRule;

class ShowProject extends Component
{
    #[Url]
    public $project_id;

    public $newState;

    public $fileUrl;

    public function render()
    {
        $project = Project::findOrFail($this->project_id);
        $state = $project->state;

        $showApproval = \Auth::user()->getGroups()->has('ref-finanzen-hv') || !$state->equals(Draft::class);

        return view('livewire.project.show-project', compact('project', 'showApproval'));
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
}
