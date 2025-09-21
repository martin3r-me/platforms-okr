<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\CycleManagement;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/cycles', CycleManagement::class)->name('cycles');



