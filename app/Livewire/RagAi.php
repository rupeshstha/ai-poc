<?php

namespace App\Livewire;

use App\Ai\Agents\RagAgent;
use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'RAG + Laravel AI Agent'])]
class RagAi extends Component
{
    public string $query = '';

    /** @var array<int, array{id: int, title: string, content: string, similarity: float}> */
    public array $retrievedDocs = [];

    public string $agentInstructions = '';

    public string $llmResponse = '';

    public bool $isLoading = false;

    public bool $showInstructions = false;

    public string $stage = 'idle'; // idle | retrieved | answered

    public ?string $conversationId = null;

    public function startFresh(): void
    {
        $this->conversationId = null;
        $this->stage = 'idle';
        $this->retrievedDocs = [];
        $this->agentInstructions = '';
        $this->llmResponse = '';
        $this->query = '';
    }

    public function loadConversation(string $id): void
    {
        $this->conversationId = $id;
        $this->stage = 'idle';
        $this->retrievedDocs = [];
        $this->agentInstructions = '';
        $this->llmResponse = '';
    }

    public function search(RagService $ragService): void
    {
        $this->validate(['query' => 'required|string|min:5']);

        $this->isLoading = true;
        $this->stage = 'idle';
        $this->retrievedDocs = [];
        $this->agentInstructions = '';
        $this->llmResponse = '';

        // Step 2: TF-IDF similarity search — retrieve top-3 relevant documents
        $retrieved = $ragService->retrieve($this->query, topK: 3);
        $this->retrievedDocs = $retrieved->toArray();

        // Build the context string that will be injected into the agent's system instructions
        $context = $retrieved->map(function (array $doc, int $index): string {
            return '[Document '.($index + 1).': '.$doc['title']."]\n".$doc['content'];
        })->implode("\n\n---\n\n");

        // Step 3: Instantiate the agent with the retrieved context baked into its instructions.
        // This is the "augmentation" step — the agent's system prompt is now grounded in the
        // retrieved knowledge before the LLM ever sees the user's question.
        $agent = RagAgent::make()->withContext($context);
        $this->agentInstructions = (string) $agent->instructions();
        $this->stage = 'retrieved';

        // Attach conversation tracking — RemembersConversations will auto-store every turn
        if ($this->conversationId) {
            $agent->continue($this->conversationId, Auth::user());
        } else {
            $agent->forUser(Auth::user());
        }

        // Step 4: Call the LLM via laravel/ai Agent API (not a raw HTTP call)
        $response = $agent->prompt(
            $this->query,
            model: "openai/gpt-4o-mini",
        );
        $this->llmResponse = $response->text;

        // Capture the conversation ID created by RemembersConversations middleware
        if (! $this->conversationId) {
            $this->conversationId = $agent->currentConversation();
        }

        $this->stage = 'answered';
        $this->isLoading = false;
    }

    #[Computed]
    public function allDocuments(): \Illuminate\Database\Eloquent\Collection
    {
        return RagDocument::select('id', 'title', 'content')->get();
    }

    /**
     * Past conversations that used RagAgent, scoped to the current user.
     */
    #[Computed]
    public function ragConversations(): \Illuminate\Support\Collection
    {
        return DB::table('agent_conversations as c')
            ->join('agent_conversation_messages as m', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', Auth::id())
            ->where('m.agent', RagAgent::class)
            ->select('c.id', 'c.title', 'c.updated_at')
            ->distinct()
            ->orderByDesc('c.updated_at')
            ->limit(15)
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rag-ai');
    }
}
