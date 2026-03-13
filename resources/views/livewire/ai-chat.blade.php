<div class="flex h-[calc(100vh-4rem)] overflow-hidden">

    {{-- Conversation Sidebar --}}
    <div class="w-64 shrink-0 border-e border-zinc-200 dark:border-zinc-700 flex flex-col bg-zinc-50 dark:bg-zinc-900">
        <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
            <flux:button variant="primary" size="sm" class="w-full" wire:click="startNewConversation">
                + New Conversation
            </flux:button>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            @forelse ($this->conversations as $conv)
                <button
                    wire:click="loadConversation('{{ $conv->id }}')"
                    class="w-full text-left rounded-lg px-3 py-2 text-sm transition-colors {{ $conversationId === $conv->id ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }}"
                >
                    <div class="font-medium truncate">{{ $conv->title }}</div>
                    <div class="text-xs text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($conv->updated_at)->diffForHumans() }}</div>
                </button>
            @empty
                <p class="text-xs text-zinc-400 text-center mt-6 px-3">No conversations yet. Start one!</p>
            @endforelse
        </div>
    </div>

    {{-- Chat Area --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Header --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-3 flex items-center gap-3 bg-white dark:bg-zinc-800">
            <flux:heading size="sm">AI Chat</flux:heading>
            <flux:badge color="blue" size="sm">OpenRouter via laravel/ai</flux:badge>
            @if ($conversationId)
                <flux:text size="sm" class="text-zinc-400 ml-auto font-mono text-xs">{{ $conversationId }}</flux:text>
            @endif
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="messages-container">
            @if ($this->chatMessages->isEmpty() && ! $conversationId)
                <div class="flex items-center justify-center h-full text-center">
                    <div>
                        <div class="text-4xl mb-3">💬</div>
                        <flux:heading size="lg">Start a conversation</flux:heading>
                        <flux:text class="text-zinc-400 mt-1">Messages are stored per-user using laravel/ai's built-in conversation tracking.</flux:text>
                    </div>
                </div>
            @endif

            @foreach ($this->chatMessages as $msg)
                <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] {{ $msg->role === 'user' ? 'order-2' : 'order-1' }}">
                        @if ($msg->role === 'user')
                            <div class="rounded-2xl rounded-tr-sm bg-blue-600 text-white px-4 py-2.5 text-sm">
                                {{ $msg->content }}
                            </div>
                        @else
                            <div class="rounded-2xl rounded-tl-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 px-4 py-2.5 text-sm whitespace-pre-wrap">
                                {{ $msg->content }}
                            </div>
                        @endif
                        <div class="text-xs text-zinc-400 mt-1 {{ $msg->role === 'user' ? 'text-right' : 'text-left' }}">
                            {{ \Carbon\Carbon::parse($msg->created_at)->format('H:i') }}
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Loading indicator --}}
            <div wire:loading wire:target="send" class="flex justify-start">
                <div class="bg-zinc-100 dark:bg-zinc-700 rounded-2xl rounded-tl-sm px-4 py-3">
                    <div class="flex gap-1">
                        <span class="size-2 rounded-full bg-zinc-400 animate-bounce [animation-delay:0ms]"></span>
                        <span class="size-2 rounded-full bg-zinc-400 animate-bounce [animation-delay:150ms]"></span>
                        <span class="size-2 rounded-full bg-zinc-400 animate-bounce [animation-delay:300ms]"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="border-t border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
            <form wire:submit="send" class="flex gap-2 items-end">
                <flux:textarea
                    wire:model="message"
                    placeholder="Type a message..."
                    rows="1"
                    class="flex-1 resize-none"
                    wire:keydown.enter.prevent="send"
                />
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="send">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">...</span>
                </flux:button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:updated', () => {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
