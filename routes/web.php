<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\CycleManagement;
use Platform\Okr\Livewire\OkrManagement;

// Dashboard
Route::get('/', Dashboard::class)->name('dashboard');

// OKR Management
Route::get('/okrs', [OkrManagement::class, 'index'])->name('okrs.index');
Route::get('/okrs/create', [OkrManagement::class, 'create'])->name('okrs.create');
Route::get('/okrs/{okr}', [OkrManagement::class, 'show'])->name('okrs.show');

// Cycle Management
Route::get('/cycles', CycleManagement::class)->name('cycles.index');
Route::get('/cycles/create', [CycleManagement::class, 'create'])->name('cycles.create');
Route::get('/cycles/{cycle}', [CycleManagement::class, 'show'])->name('cycles.show');
