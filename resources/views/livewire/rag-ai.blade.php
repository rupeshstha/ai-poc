<div class="max-w-[1400px] mx-auto space-y-4 p-6">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <flux:heading size="xl">RAG + Laravel AI Agent</flux:heading>
                <flux:badge color="violet">laravel/ai</flux:badge>
            </div>
            <flux:subheading>
                Same retrieval pipeline as the RAG Demo — but generation uses a
                <code class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">RagAgent</code>
                (laravel/ai Agent API) and every conversation is persisted automatically.
            </flux:subheading>
        </div>
        <flux:button size="sm" wire:click="startFresh" variant="ghost">+ New conversation</flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr_260px] gap-4">

        {{-- Column 1: Knowledge Base --}}
        <div>
            <flux:card class="h-full">
                <flux:heading size="sm" class="mb-1">Knowledge Base</flux:heading>
                <flux:text size="sm" class="text-zinc-400 mb-3">{{ $this->allDocuments->count() }} documents — highlighted when retrieved</flux:text>
                <div class="space-y-1.5">
                    @foreach ($this->allDocuments as $doc)
                        @php
                            $retrievedIds = collect($retrievedDocs)->pluck('id')->toArray();
                            $isRetrieved = in_array($doc->id, $retrievedIds);
                            $retrievedDoc = collect($retrievedDocs)->firstWhere('id', $doc->id);
                        @endphp
                        <div class="rounded-lg border p-2.5 text-sm transition-all duration-300 {{ $isRetrieved ? 'border-violet-400 bg-violet-50 dark:bg-violet-950 dark:border-violet-600' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="flex items-start justify-between gap-1">
                                <span class="font-medium text-xs text-zinc-800 dark:text-zinc-200 leading-tight">{{ $doc->title }}</span>
                                @if ($isRetrieved)
                                    <flux:badge color="violet" size="sm" class="shrink-0">
                                        {{ number_format($retrievedDoc['similarity'] * 100, 0) }}%
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Column 2: Pipeline --}}
        <div class="space-y-3">

            {{-- Step 1: Query --}}
            <flux:card>
                <div class="flex items-center gap-2 mb-3">
                    <flux:badge color="zinc">Step 1</flux:badge>
                    <flux:heading size="sm">Ask a question</flux:heading>
                </div>
                <form wire:submit="search" class="flex gap-2">
                    <flux:input
                        wire:model="query"
                        placeholder="e.g. Explain about internet, What is ocean?"
                        class="flex-1"
                    />
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Ask Agent</span>
                        <span wire:loading>Running...</span>
                    </flux:button>
                </form>
                @error('query')
                    <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                @enderror
                @if ($conversationId)
                    <div class="mt-2 flex items-center gap-2">
                        <flux:text size="sm" class="text-zinc-400">Continuing conversation:</flux:text>
                        <code class="text-xs font-mono text-violet-600 dark:text-violet-400">{{ $conversationId }}</code>
                    </div>
                @endif
            </flux:card>

            {{-- Step 2: Retrieval --}}
            @if ($stage !== 'idle')
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:badge color="blue">Step 2</flux:badge>
                        <flux:heading size="sm">Similarity search — retrieved context</flux:heading>
                        <flux:text size="sm" class="text-zinc-400 ml-auto">TF-IDF cosine similarity, top 3</flux:text>
                    </div>
                    <div class="space-y-2">
                        @foreach ($retrievedDocs as $index => $doc)
                            <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950 p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-medium text-sm text-blue-900 dark:text-blue-100">#{{ $index + 1 }} — {{ $doc['title'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 rounded-full bg-blue-200 dark:bg-blue-900 overflow-hidden">
                                            <div class="h-full rounded-full bg-blue-500" style="width: {{ number_format($doc['similarity'] * 100, 0) }}%"></div>
                                        </div>
                                        <span class="text-xs text-blue-600 dark:text-blue-400 font-mono">{{ number_format($doc['similarity'] * 100, 1) }}%</span>
                                    </div>
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ $doc['content'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- Step 3: Agent Instructions (Augmented System Prompt) --}}
            @if ($agentInstructions)
                <flux:card>
                    <button wire:click="$toggle('showInstructions')" class="w-full flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:badge color="violet">Step 3</flux:badge>
                            <flux:heading size="sm">Agent system instructions</flux:heading>
                            <flux:badge color="amber" size="sm">laravel/ai</flux:badge>
                        </div>
                        <flux:text size="sm" class="text-zinc-400">{{ $showInstructions ? 'Hide' : 'Show' }}</flux:text>
                    </button>
                    <flux:text size="sm" class="text-zinc-500 mt-2">
                        The retrieved documents are injected into <code class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">RagAgent::instructions()</code>
                        — the agent's system prompt. This happens before the LLM sees the user's question.
                        <span class="text-violet-600 dark:text-violet-400 cursor-pointer" wire:click="$toggle('showInstructions')">{{ $showInstructions ? 'Hide' : 'View full instructions.' }}</span>
                    </flux:text>
                    @if ($showInstructions)
                        <pre class="mt-3 text-xs bg-zinc-100 dark:bg-zinc-900 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap text-zinc-700 dark:text-zinc-300 font-mono">{{ $agentInstructions }}</pre>
                    @endif
                </flux:card>
            @endif

            {{-- Step 4: LLM Response via laravel/ai Agent --}}
            @if ($llmResponse)
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:badge color="green">Step 4</flux:badge>
                        <flux:heading size="sm">Agent response</flux:heading>
                        <flux:badge color="violet" size="sm">RagAgent::make()->prompt()</flux:badge>
                    </div>
                    <div class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $llmResponse }}</div>
                </flux:card>
            @endif

            {{-- Step 5: Conversation Persisted --}}
            @if ($conversationId && $stage === 'answered')
                <flux:card>
                    <div class="flex items-center gap-2 mb-2">
                        <flux:badge color="amber">Step 5</flux:badge>
                        <flux:heading size="sm">Conversation persisted by laravel/ai</flux:heading>
                    </div>
                    <flux:text size="sm" class="text-zinc-500">
                        <code class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">RemembersConversations</code>
                        automatically stored this turn (user message + agent response) in
                        <code class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1 rounded">agent_conversation_messages</code>.
                        Ask a follow-up question — the agent will have full context of prior turns.
                    </flux:text>
                    <div class="mt-2 flex items-center gap-2">
                        <flux:icon.check-circle class="size-4 text-green-500" />
                        <code class="text-xs font-mono text-violet-600 dark:text-violet-400">{{ $conversationId }}</code>
                    </div>
                </flux:card>
            @endif

            {{-- Loading --}}
            @if ($isLoading)
                <flux:card>
                    <div class="flex items-center gap-3 text-zinc-500">
                        <flux:icon.loading class="size-4" />
                        <flux:text size="sm">Retrieving documents and prompting agent...</flux:text>
                    </div>
                </flux:card>
            @endif

        </div>

        {{-- Column 3: Conversation History --}}
        <div>
            <flux:card class="h-full">
                <flux:heading size="sm" class="mb-1">Conversations</flux:heading>
                <flux:text size="sm" class="text-zinc-400 mb-3">Stored by <code class="font-mono text-xs">RemembersConversations</code></flux:text>
                <div class="space-y-1.5">
                    @forelse ($this->ragConversations as $conv)
                        <button
                            wire:click="loadConversation('{{ $conv->id }}')"
                            class="w-full text-left rounded-lg border p-2.5 text-sm transition-colors {{ $conversationId === $conv->id ? 'border-violet-400 bg-violet-50 dark:bg-violet-950' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                        >
                            <div class="font-medium text-xs text-zinc-800 dark:text-zinc-200 truncate">{{ $conv->title }}</div>
                            <div class="text-xs text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($conv->updated_at)->diffForHumans() }}</div>
                        </button>
                    @empty
                        <flux:text size="sm" class="text-zinc-400 text-center mt-4">No conversations yet. Ask a question to start one.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>

    </div>
</div>
