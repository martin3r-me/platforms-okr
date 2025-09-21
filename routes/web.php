<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\CycleManagement;
use Platform\Okr\Livewire\OkrManagement;

// Dashboard
Route::get('/', Dashboard::class)->name('okr.dashboard');

// OKR Management
Route::get('/okrs', [OkrManagement::class, 'index'])->name('okr.okrs.index');
Route::get('/okrs/create', [OkrManagement::class, 'create'])->name('okr.okrs.create');
Route::get('/okrs/{okr}', [OkrManagement::class, 'show'])->name('okr.okrs.show');

// Cycle Management
Route::get('/cycles', CycleManagement::class)->name('okr.cycles.index');
Route::get('/cycles/create', [CycleManagement::class, 'create'])->name('okr.cycles.create');
Route::get('/cycles/{cycle}', [CycleManagement::class, 'show'])->name('okr.cycles.show');
