<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\OkrManagement;
use Platform\Okr\Livewire\OkrShow;

// Dashboard
Route::get('/', Dashboard::class)->name('okr.dashboard');

// OKR Management
Route::get('/okrs', OkrManagement::class)->name('okr.okrs.index');
Route::get('/okrs/{okr}', OkrShow::class)->name('okr.okrs.show');

// Cycle Management
Route::get('/cycles/{cycle}', \Platform\Okr\Livewire\CycleShow::class)->name('okr.cycles.show');

// Objective Management
Route::get('/objectives/{objective}', \Platform\Okr\Livewire\ObjectiveShow::class)->name('okr.objectives.show');
