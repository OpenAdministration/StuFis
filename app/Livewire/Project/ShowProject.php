<?php

namespace App\Livewire\Project;

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use App\Models\Legacy\Project;
use App\States\Project\ProjectState;
use Cknow\Money\Money;
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
        $postTable = [
            'footer' => ['used' => Money::EUR(0), 'ration' => null],
        ];
        foreach ($project->posts as $post){

            $in  = Money::EUR($post->expensePosts()->sum('einnahmen'));
            $out = Money::EUR($post->expensePosts()->sum('ausgaben'));
            $used = $out->subtract($in);

            $postTable[$post->id]['used'] = $used;
            $postTable[$post->id]['ration'] = !$used->isZero() ? $post->ausgaben->ratioOf($used) : 0;

            $postTable['footer']['used'] = $postTable['footer']['used']->add($used);
        }
        $postTable['footer']['in'] = Money::EUR($project->posts()->sum('einnahmen'));
        $postTable['footer']['out'] = Money::EUR($project->posts()->sum('ausgaben'));
        if ($postTable['footer']['out']->isZero()) {
            $postTable['footer']['ratio'] = 0;
        }else{
            $postTable['footer']['ratio'] = (int) $postTable['footer']['used']->multiply(100)->ratioOf($postTable['footer']['out']);
        }

        return view('livewire.project.show', compact('project', 'postTable'));
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
