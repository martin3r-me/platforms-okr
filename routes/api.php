<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Http\Controllers\Api\CycleDatawarehouseController;
use Platform\Okr\Http\Controllers\Api\OkrAgentController;

/**
 * OKR API Routes
 * 
 * Datawarehouse-Endpunkte für OKR Cycles
 */
Route::get('/cycles/datawarehouse', [CycleDatawarehouseController::class, 'index']);
Route::get('/cycles/datawarehouse/health', [CycleDatawarehouseController::class, 'health']);

/*
 * Agent-API: ein Agent pflegt sein persönliches OKR selbst (Entwurf → Freigabe → Check-in).
 * Fester Vertrag für die Firmware statt freier MCP-Aufrufe.
 */
Route::prefix('agent')->group(function () {
    Route::get('/context', [OkrAgentController::class, 'context'])->name('okr.api.agent.context');
    Route::post('/drafts', [OkrAgentController::class, 'storeDraft'])->name('okr.api.agent.drafts.store');
    Route::post('/drafts/{cycle}/activate', [OkrAgentController::class, 'activateDraft'])->whereNumber('cycle')->name('okr.api.agent.drafts.activate');
    Route::post('/checkins', [OkrAgentController::class, 'checkin'])->name('okr.api.agent.checkins');
});

