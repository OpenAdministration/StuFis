<?php

namespace App\Livewire;

use App\Models\Enums\ChatMessageType;
use App\Models\Legacy\ChatMessage;
use Illuminate\Support\Collection;
use Livewire\Component;

class ChatPanel extends Component
{
    public $content = "";

    public $targetType;

    public $targetId;

    public function mount($targetType, $targetId): void
    {
        $this->targetType = $targetType;
        $this->targetId = $targetId;
    }

    public function render()
    {
        /** @var Collection<ChatMessage> $messages */
        $messages = ChatMessage::where('target', $this->targetType)
            ->where('target_id', $this->targetId)->get();

        return view('livewire.chat-panel', ['messages' => $messages]);
    }

    public function save() {

        $this->validate(['content' => 'required|min:1']);

        $cleanContent = strip_tags($this->content, '<p><br><strong><em><ul><ol><li><a><h1><h2><h3>');

        ChatMessage::create([
            'text' => $cleanContent,
            'type' => ChatMessageType::PUBLIC,
            'target' => $this->targetType,
            'target_id' => $this->targetId,
            'creator' => Auth()->user()->username,
            'creator_alias' => Auth()->user()->name,
            'timestamp' => now(),
        ]);

        $this->content = "";
    }
}
