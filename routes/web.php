<?php

use App\Livewire\AiChat;
use App\Livewire\McpExplorer;
use App\Livewire\RagAi;
use App\Livewire\RagDemo;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/rag-demo', RagDemo::class)
    ->middleware(['auth', 'verified'])
    ->name('rag.demo');

Route::get('/rag-ai', RagAi::class)
    ->middleware(['auth', 'verified'])
    ->name('rag.ai');

Route::get('/ai-chat', AiChat::class)
    ->middleware(['auth', 'verified'])
    ->name('ai.chat');

Route::get('/mcp-explorer', McpExplorer::class)
    ->middleware(['auth', 'verified'])
    ->name('mcp.explorer');

require __DIR__.'/settings.php';
