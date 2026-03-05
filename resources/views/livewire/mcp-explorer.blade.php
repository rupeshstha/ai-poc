<div class="flex h-full w-full flex-1">

    {{-- Sidebar: Server List --}}
    <div class="w-72 flex-shrink-0 border-e border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4 space-y-2 overflow-y-auto">
        <div class="mb-4">
            <flux:heading size="sm">MCP Servers</flux:heading>
            <flux:text size="xs" class="text-zinc-500 mt-1">{{ count($this->servers) }} server(s) registered</flux:text>
        </div>

        @forelse ($this->servers as $server)
            <button
                wire:click="selectServer('{{ $server['handle'] }}')"
                class="w-full text-left rounded-lg border p-3 transition-all duration-150 cursor-pointer
                    {{ $selectedHandle === $server['handle']
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/50 dark:border-blue-500'
                        : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 hover:bg-white dark:hover:bg-zinc-800' }}"
            >
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $server['name'] }}</span>
                </div>
                <div class="flex items-center gap-2 ms-4">
                    <flux:badge size="sm" color="zinc">v{{ $server['version'] }}</flux:badge>
                    <span class="text-xs text-zinc-500 truncate font-mono">{{ $server['handle'] }}</span>
                </div>
                <div class="mt-2 ms-4 flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-500">
                    @if (count($server['tools']) > 0)
                        <span>{{ count($server['tools']) }} tool(s)</span>
                    @endif
                    @if (count($server['resources']) > 0)
                        <span>&middot; {{ count($server['resources']) }} resource(s)</span>
                    @endif
                    @if (count($server['prompts']) > 0)
                        <span>&middot; {{ count($server['prompts']) }} prompt(s)</span>
                    @endif
                </div>
            </button>
        @empty
            <div class="text-center py-8 text-zinc-500">
                <flux:icon.cpu-chip class="w-8 h-8 mx-auto mb-2 opacity-40" />
                <flux:text size="sm">No MCP servers found.</flux:text>
                <flux:text size="xs" class="mt-1">Register servers in <code class="font-mono">routes/ai.php</code></flux:text>
            </div>
        @endforelse
    </div>

    {{-- Main Panel: Server Details --}}
    <div class="flex-1 overflow-y-auto p-6">
        @if ($this->selectedServer)
            @php $server = $this->selectedServer; @endphp

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <flux:icon.cpu-chip class="w-6 h-6 text-blue-500" />
                        <flux:heading size="xl">{{ $server['name'] }}</flux:heading>
                        <flux:badge color="zinc">v{{ $server['version'] }}</flux:badge>
                    </div>
                    <flux:text size="sm" class="text-zinc-500 font-mono">{{ url($server['handle']) }}</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:modal.trigger name="mcp-inspector">
                        <flux:button icon="magnifying-glass" size="sm" variant="filled" x-data="" x-on:click.prevent="$dispatch('open-modal', 'mcp-inspector')">
                            Inspect
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:button wire:click="toggleConnectGuide" icon="link" size="sm">
                        Connect Guide
                    </flux:button>
                </div>
            </div>

            {{-- Instructions --}}
            @if ($server['instructions'])
                <flux:card class="mb-6">
                    <flux:heading size="sm" class="mb-2">Server Instructions</flux:heading>
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap leading-relaxed">{{ $server['instructions'] }}</flux:text>
                </flux:card>
            @endif

            {{-- Connect Guide --}}
            @if ($showConnectGuide)
                <flux:card class="mb-6 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="sm">Connect to an LLM Client</flux:heading>
                        <flux:button wire:click="toggleConnectGuide" icon="x-mark" size="xs" variant="ghost" />
                    </div>

                    <div class="space-y-4">
                        {{-- Transport type --}}
                        <div class="flex items-center gap-2">
                            <flux:badge color="blue">Streamable HTTP</flux:badge>
                            <flux:text size="sm" class="text-zinc-500">Transport Type</flux:text>
                        </div>

                        {{-- Claude Desktop --}}
                        <div>
                            <flux:heading size="xs" class="mb-2 flex items-center gap-2">
                                Claude Desktop
                                <flux:badge size="sm" color="zinc">~/Library/Application Support/Claude/claude_desktop_config.json</flux:badge>
                            </flux:heading>
                            <div class="relative rounded-lg bg-zinc-900 dark:bg-zinc-950 p-4 font-mono text-sm text-zinc-100 overflow-x-auto">
                                @php
                                    $claudeConfig = json_encode([
                                        'mcpServers' => [
                                            str_replace('/', '-', $server['handle']) => [
                                                'type' => 'http',
                                                'url' => url($server['handle']),
                                            ],
                                        ],
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                @endphp
                                <button
                                    x-data=""
                                    x-on:click="navigator.clipboard.writeText({{ json_encode($claudeConfig) }})"
                                    class="absolute top-2 right-2 text-xs text-zinc-400 hover:text-white transition px-2 py-1 rounded bg-zinc-700 hover:bg-zinc-600"
                                >Copy</button>
                                <pre class="whitespace-pre-wrap break-all">{{ $claudeConfig }}</pre>
                            </div>
                        </div>

                        {{-- Cursor --}}
                        <div>
                            <flux:heading size="xs" class="mb-2 flex items-center gap-2">
                                Cursor
                                <flux:badge size="sm" color="zinc">.cursor/mcp.json</flux:badge>
                            </flux:heading>
                            <div class="relative rounded-lg bg-zinc-900 dark:bg-zinc-950 p-4 font-mono text-sm text-zinc-100 overflow-x-auto">
                                @php
                                    $cursorConfig = json_encode([
                                        'mcpServers' => [
                                            str_replace('/', '-', $server['handle']) => [
                                                'url' => url($server['handle']),
                                            ],
                                        ],
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                @endphp
                                <button
                                    x-data=""
                                    x-on:click="navigator.clipboard.writeText({{ json_encode($cursorConfig) }})"
                                    class="absolute top-2 right-2 text-xs text-zinc-400 hover:text-white transition px-2 py-1 rounded bg-zinc-700 hover:bg-zinc-600"
                                >Copy</button>
                                <pre class="whitespace-pre-wrap break-all">{{ $cursorConfig }}</pre>
                            </div>
                        </div>

                        {{-- VS Code --}}
                        <div>
                            <flux:heading size="xs" class="mb-2 flex items-center gap-2">
                                VS Code (Copilot)
                                <flux:badge size="sm" color="zinc">.vscode/mcp.json</flux:badge>
                            </flux:heading>
                            <div class="relative rounded-lg bg-zinc-900 dark:bg-zinc-950 p-4 font-mono text-sm text-zinc-100 overflow-x-auto">
                                @php
                                    $vscodeConfig = json_encode([
                                        'servers' => [
                                            str_replace('/', '-', $server['handle']) => [
                                                'type' => 'http',
                                                'url' => url($server['handle']),
                                            ],
                                        ],
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                @endphp
                                <button
                                    x-data=""
                                    x-on:click="navigator.clipboard.writeText({{ json_encode($vscodeConfig) }})"
                                    class="absolute top-2 right-2 text-xs text-zinc-400 hover:text-white transition px-2 py-1 rounded bg-zinc-700 hover:bg-zinc-600"
                                >Copy</button>
                                <pre class="whitespace-pre-wrap break-all">{{ $vscodeConfig }}</pre>
                            </div>
                        </div>

                    </div>
                </flux:card>
            @endif

            {{-- Tools, Resources, Prompts --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Tools --}}
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon.wrench-screwdriver class="w-4 h-4 text-amber-500" />
                        <flux:heading size="sm">Tools</flux:heading>
                        <flux:badge size="sm" color="amber">{{ count($server['tools']) }}</flux:badge>
                    </div>
                    @forelse ($server['tools'] as $tool)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 mb-2 last:mb-0">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $tool['name'] }}</div>
                            <div class="text-xs font-mono text-zinc-400 mb-1">{{ $tool['class'] }}</div>
                            @if ($tool['description'])
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $tool['description'] }}</div>
                            @endif
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-400">No tools registered.</flux:text>
                    @endforelse
                </flux:card>

                {{-- Resources --}}
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon.document-text class="w-4 h-4 text-blue-500" />
                        <flux:heading size="sm">Resources</flux:heading>
                        <flux:badge size="sm" color="blue">{{ count($server['resources']) }}</flux:badge>
                    </div>
                    @forelse ($server['resources'] as $resource)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 mb-2 last:mb-0">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $resource['name'] }}</div>
                            <div class="text-xs font-mono text-zinc-400 mb-1">{{ $resource['class'] }}</div>
                            @if ($resource['description'])
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $resource['description'] }}</div>
                            @endif
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-400">No resources registered.</flux:text>
                    @endforelse
                </flux:card>

                {{-- Prompts --}}
                <flux:card>
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon.chat-bubble-left-ellipsis class="w-4 h-4 text-purple-500" />
                        <flux:heading size="sm">Prompts</flux:heading>
                        <flux:badge size="sm" color="purple">{{ count($server['prompts']) }}</flux:badge>
                    </div>
                    @forelse ($server['prompts'] as $prompt)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 mb-2 last:mb-0">
                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $prompt['name'] }}</div>
                            <div class="text-xs font-mono text-zinc-400 mb-1">{{ $prompt['class'] }}</div>
                            @if ($prompt['description'])
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $prompt['description'] }}</div>
                            @endif
                        </div>
                    @empty
                        <flux:text size="sm" class="text-zinc-400">No prompts registered.</flux:text>
                    @endforelse
                </flux:card>

            </div>

        @else
            <div class="flex flex-col items-center justify-center h-full text-center py-20">
                <flux:icon.cpu-chip class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-4" />
                <flux:heading size="lg" class="text-zinc-400">No server selected</flux:heading>
                <flux:text size="sm" class="text-zinc-400 mt-2">Select an MCP server from the list to view its details.</flux:text>
            </div>
        @endif
    </div>

    {{-- Inspect Modal --}}
    <flux:modal name="mcp-inspector" class="w-full max-w-2xl">
        @if ($this->selectedServer)
            @php $server = $this->selectedServer; @endphp
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Inspect: {{ $server['name'] }}</flux:heading>
                    <flux:subheading>Use the MCP Inspector to debug and test this server interactively.</flux:subheading>
                </div>

                {{-- Transport Info --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:badge color="blue">Streamable HTTP</flux:badge>
                        <span class="text-sm text-zinc-500">Transport</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-zinc-400 w-8">URL</span>
                        <code class="text-sm font-mono text-zinc-700 dark:text-zinc-300 break-all">{{ url($server['handle']) }}</code>
                    </div>
                </div>

                {{-- Artisan Command --}}
                <div>
                    <flux:heading size="xs" class="mb-2">Run the inspector in your terminal</flux:heading>
                    <div class="relative rounded-lg bg-zinc-900 dark:bg-zinc-950 p-4">
                        @php $inspectCmd = 'php artisan mcp:inspector ' . $server['handle']; @endphp
                        <button
                            x-data=""
                            x-on:click="navigator.clipboard.writeText({{ json_encode($inspectCmd) }})"
                            class="absolute top-2 right-2 text-xs text-zinc-400 hover:text-white transition px-2 py-1 rounded bg-zinc-700 hover:bg-zinc-600"
                        >Copy</button>
                        <code class="font-mono text-sm text-green-400">{{ $inspectCmd }}</code>
                    </div>
                    <flux:text size="xs" class="text-zinc-400 mt-2">
                        Starts a local MCP Inspector UI via <code class="font-mono">npx @modelcontextprotocol/inspector</code>.
                        Requires Node.js installed.
                    </flux:text>
                </div>

                {{-- What happens --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-4 space-y-2">
                    <flux:heading size="xs">What happens when you run this?</flux:heading>
                    <ol class="space-y-1 list-decimal list-inside">
                        <li class="text-sm text-zinc-600 dark:text-zinc-400">
                            The command launches the MCP Inspector via <code class="font-mono text-xs">npx</code>
                        </li>
                        <li class="text-sm text-zinc-600 dark:text-zinc-400">
                            A local web UI opens at <code class="font-mono text-xs">http://localhost:5173</code> (or similar)
                        </li>
                        <li class="text-sm text-zinc-600 dark:text-zinc-400">
                            The inspector connects to <code class="font-mono text-xs">{{ url($server['handle']) }}</code>
                        </li>
                        <li class="text-sm text-zinc-600 dark:text-zinc-400">
                            Browse tools, send requests, and view structured responses live
                        </li>
                    </ol>
                </div>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

</div>
