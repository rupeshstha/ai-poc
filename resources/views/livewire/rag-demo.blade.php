<div class="max-w-6xl mx-auto space-y-6 p-6">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">RAG Demo — Retrieval-Augmented Generation</flux:heading>
        <flux:subheading>
            Watch how RAG works: your question retrieves relevant documents, which are injected into the LLM's prompt.
        </flux:subheading>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Knowledge Base --}}
        <div class="lg:col-span-1">
            <flux:card class="h-full">
                <flux:heading size="sm" class="mb-3">Knowledge Base ({{ $this->allDocuments->count() }} documents)</flux:heading>
                <flux:text size="sm" class="text-zinc-500 mb-4">These are all the documents stored. RAG will find the most relevant ones for your question.</flux:text>
                <div class="space-y-2">
                    @foreach ($this->allDocuments as $doc)
                        @php
                            $retrievedIds = collect($retrievedDocs)->pluck('id')->toArray();
                            $isRetrieved = in_array($doc->id, $retrievedIds);
                            $retrievedDoc = collect($retrievedDocs)->firstWhere('id', $doc->id);
                        @endphp
                        <div class="rounded-lg border p-3 text-sm transition-all duration-300 {{ $isRetrieved ? 'border-blue-400 bg-blue-50 dark:bg-blue-950 dark:border-blue-600' : 'border-zinc-200 dark:border-zinc-700' }}">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $doc->title }}</span>
                                @if ($isRetrieved)
                                    <flux:badge color="blue" size="sm">
                                        {{ number_format($retrievedDoc['similarity'] * 100, 0) }}% match
                                    </flux:badge>
                                @endif
                            </div>
                            @if ($isRetrieved)
                                <p class="mt-1 text-xs text-zinc-500 line-clamp-2">{{ $doc->content }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        {{-- Right: Pipeline --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Step 1: Query --}}
            <flux:card>
                <div class="flex items-center gap-2 mb-3">
                    <flux:badge color="zinc">Step 1</flux:badge>
                    <flux:heading size="sm">Ask a question</flux:heading>
                </div>
                <form wire:submit="search" class="flex gap-2">
                    <flux:input
                        wire:model="query"
                        placeholder="e.g. How do black holes form? Why is the ocean salty?"
                        class="flex-1"
                    />
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Search</span>
                        <span wire:loading>Searching...</span>
                    </flux:button>
                </form>
                @error('query')
                    <flux:text class="text-red-500 text-sm mt-1">{{ $message }}</flux:text>
                @enderror
            </flux:card>

            {{-- Step 2: Retrieval --}}
            @if ($stage !== 'idle')
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:badge color="blue">Step 2</flux:badge>
                        <flux:heading size="sm">Retrieved context</flux:heading>
                        <flux:text size="sm" class="text-zinc-500 ml-auto">Top 3 most similar documents</flux:text>
                    </div>
                    <div class="space-y-3">
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
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $doc['content'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- Step 3: Augmented Prompt --}}
            @if ($augmentedPrompt)
                <flux:card>
                    <button
                        wire:click="$toggle('showPrompt')"
                        class="w-full flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <flux:badge color="amber">Step 3</flux:badge>
                            <flux:heading size="sm">Augmented prompt sent to LLM</flux:heading>
                        </div>
                        <flux:text size="sm" class="text-zinc-400">{{ $showPrompt ? 'Hide' : 'Show' }}</flux:text>
                    </button>
                    @if ($showPrompt)
                        <pre class="mt-3 text-xs bg-zinc-100 dark:bg-zinc-900 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap text-zinc-700 dark:text-zinc-300 font-mono">{{ $augmentedPrompt }}</pre>
                    @else
                        <flux:text size="sm" class="text-zinc-500 mt-2">
                            The retrieved documents above were injected here as context before the LLM saw your question.
                            <span class="text-amber-600 dark:text-amber-400 cursor-pointer" wire:click="$toggle('showPrompt')">Click to view the full prompt.</span>
                        </flux:text>
                    @endif
                </flux:card>
            @endif

            {{-- Step 4: LLM Response --}}
            @if ($llmResponse)
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:badge color="green">Step 4</flux:badge>
                        <flux:heading size="sm">LLM response (grounded in retrieved context)</flux:heading>
                    </div>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300">
                        {{ $llmResponse }}
                    </div>
                </flux:card>
            @endif

            {{-- Loading state --}}
            @if ($isLoading)
                <flux:card>
                    <div class="flex items-center gap-3 text-zinc-500">
                        <flux:icon.loading class="size-4" />
                        <flux:text size="sm">Retrieving documents and generating response...</flux:text>
                    </div>
                </flux:card>
            @endif

        </div>
    </div>
</div>
