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
