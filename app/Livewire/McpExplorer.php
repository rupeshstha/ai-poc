<?php

namespace App\Livewire;

use App\Services\McpDiscoveryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'MCP Explorer'])]
class McpExplorer extends Component
{
    public string $selectedHandle = '';

    public bool $showConnectGuide = false;

    public function mount(McpDiscoveryService $discovery): void
    {
        $servers = $discovery->discover();

        if ($servers !== []) {
            $this->selectedHandle = $servers[0]['handle'];
        }
    }

    #[Computed]
    public function servers(): array
    {
        return app(McpDiscoveryService::class)->discover();
    }

    #[Computed]
    public function selectedServer(): ?array
    {
        foreach ($this->servers as $server) {
            if ($server['handle'] === $this->selectedHandle) {
                return $server;
            }
        }

        return null;
    }

    public function selectServer(string $handle): void
    {
        $this->selectedHandle = $handle;
        $this->showConnectGuide = false;
    }

    public function toggleConnectGuide(): void
    {
        $this->showConnectGuide = ! $this->showConnectGuide;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.mcp-explorer');
    }
}
