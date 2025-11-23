<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Http\Controllers\Api\CycleDatawarehouseController;

/**
 * OKR API Routes
 * 
 * Datawarehouse-Endpunkte für OKR Cycles
 */
Route::get('/cycles/datawarehouse', [CycleDatawarehouseController::class, 'index']);
Route::get('/cycles/datawarehouse/health', [CycleDatawarehouseController::class, 'health']);

