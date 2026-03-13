<?php

namespace App\Livewire;

use App\Ai\Agents\RagChatAgent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'AI Chat'])]
class AiChat extends Component
{
    public string $message = '';

    public ?string $conversationId = null;

    public function startNewConversation(): void
    {
        $this->conversationId = null;
        $this->message = '';
    }

    public function loadConversation(string $conversationId): void
    {
        $this->conversationId = $conversationId;
        $this->message = '';
    }

    public function send(): void
    {
        $this->validate(['message' => 'required|string|min:1']);

        $user = Auth::user();
        $prompt = $this->message;
        $this->message = '';

        $agent = RagChatAgent::make();

        if ($this->conversationId) {
            $agent->continue($this->conversationId, $user);
        } else {
            $agent->forUser($user);
        }

        $response = $agent->prompt($prompt);

        // After the first message, capture the new conversation ID from the agent
        if (! $this->conversationId) {
            $this->conversationId = $agent->currentConversation();
        }
    }

    #[Computed]
    public function conversations(): \Illuminate\Support\Collection
    {
        return DB::table('agent_conversations')
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);
    }

    #[Computed]
    public function chatMessages(): \Illuminate\Support\Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.ai-chat');
    }
}
