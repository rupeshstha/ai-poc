<?php

namespace App\Livewire;

use App\Ai\Agents\RagAgent;
use App\Services\RagService;
use Exception;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
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

    public function send(RagService $ragService): void
    {
        $this->validate(['message' => 'required|string|min:1']);

        $user = Auth::user();
        $prompt = $this->message;
        $this->message = '';

        // Retrieve the most relevant documents — the LLM will reason about whether
        // the question is within scope; we never block the call based on retrieval alone.
        $retrieved = $ragService->retrieve($prompt, topK: 3);

        $context = $retrieved->map(function (array $doc, int $index): string {
            return '[Document '.($index + 1).': '.$doc['title']."]\n".$doc['content'];
        })->implode("\n\n---\n\n");

        $agent = RagAgent::make()->withContext($context);

        if ($this->conversationId) {
            $agent->continue($this->conversationId, $user);
        } else {
            $agent->forUser($user);
        }

        $prompt = <<<PROMPT
        User's question: {$prompt}

        Answer based on the context above:
        PROMPT;

        try {
            $agent->prompt(
                prompt: $prompt,
            );
        } catch (Exception $exception) {
            Flux::toast(heading: 'LLM call failed', text: $exception->getMessage(), variant: 'danger');
        }

        if (! $this->conversationId) {
            $this->conversationId = $agent->currentConversation();
        }
    }

    #[Computed]
    public function conversations(): Collection
    {
        return DB::table('agent_conversations as c')
            ->join('agent_conversation_messages as m', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', Auth::id())
            ->where('m.agent', RagAgent::class)
            ->select('c.id', 'c.title', 'c.updated_at')
            ->distinct()
            ->orderByDesc('c.updated_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function chatMessages(): Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at']);
    }

    public function render(): View
    {
        return view('livewire.ai-chat');
    }
}
