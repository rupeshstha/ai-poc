<?php

namespace App\Livewire;

use App\Models\RagDocument;
use App\Services\RagService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'RAG Demo'])]
class RagDemo extends Component
{
    public string $query = '';

    /** @var array<int, array{id: int, title: string, content: string, similarity: float}> */
    public array $retrievedDocs = [];

    public string $augmentedPrompt = '';

    public string $llmResponse = '';

    public bool $isLoading = false;

    public bool $showPrompt = false;

    public string $stage = 'idle'; // idle | retrieved | answered

    public function search(RagService $ragService): void
    {
        $this->validate(['query' => 'required|string|min:5']);

        $this->isLoading = true;
        $this->stage = 'idle';
        $this->retrievedDocs = [];
        $this->augmentedPrompt = '';
        $this->llmResponse = '';

        // Step 1: Retrieve relevant documents
        $retrieved = $ragService->retrieve($this->query, topK: 3);
        $this->retrievedDocs = $retrieved->toArray();

        // Step 2: Build the augmented prompt
        $this->augmentedPrompt = $ragService->buildAugmentedPrompt($this->query, $retrieved);

        $this->stage = 'retrieved';

        // Step 3: Generate LLM response using the augmented prompt
        $this->llmResponse = $ragService->generate($this->augmentedPrompt);

        $this->stage = 'answered';
        $this->isLoading = false;
    }

    #[Computed]
    public function allDocuments(): \Illuminate\Database\Eloquent\Collection
    {
        return RagDocument::select('id', 'title', 'content')->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rag-demo');
    }
}
