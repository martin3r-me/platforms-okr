<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\CycleManagement;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('okr')->name('okr.')->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
        Route::get('/cycles', CycleManagement::class)->name('cycles.index');
    });
});
