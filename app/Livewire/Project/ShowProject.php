<?php

namespace App\Livewire\Project;

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Project;
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

    public function render()
    {
        $project = Project::findOrFail($this->project_id);

        return view('livewire.project.show-project', compact('project'));
    }

    public function changeState(): void
    {
        $project = Project::findOrFail($this->project_id);
        $filtered = $this->validate(['newState' => ['required', new ValidStateRule(ProjectState::class)]]);
        $newState = ProjectState::make($filtered['newState'], $project);
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
        } catch (CouldNotPerformTransition $e) {
            $this->addError('newState', $e->getMessage());
        }
    }
}
