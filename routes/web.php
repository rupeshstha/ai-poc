<?php

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

require __DIR__.'/settings.php';
