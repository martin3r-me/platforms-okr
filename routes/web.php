<?php

use Illuminate\Support\Facades\Route;
use Platform\Okr\Livewire\Dashboard;
use Platform\Okr\Livewire\OkrManagement;
use Platform\Okr\Livewire\OkrShow;
use Illuminate\Http\Middleware\FrameGuard;

// Dashboard
Route::get('/', Dashboard::class)->name('okr.dashboard');

// OKR Management
Route::get('/okrs', OkrManagement::class)->name('okr.okrs.index');
Route::get('/okrs/{okr}', OkrShow::class)->name('okr.okrs.show');

// Cycle Management
Route::get('/cycles/{cycle}', \Platform\Okr\Livewire\CycleShow::class)->name('okr.cycles.show');

// Objective Management
Route::get('/objectives/{objective}', \Platform\Okr\Livewire\ObjectiveShow::class)->name('okr.objectives.show');

// Embedded Teams Config (OKR) – Platzhalter
Route::get('/embedded/teams/config', function() {
    $user = auth()->user();
    $teamIds = collect();
    $teams = collect();

    if ($user) {
        try {
            if (method_exists($user, 'teams')) {
                $teams = $user->teams()->select(['teams.id','teams.name'])->orderBy('name')->get();
                $teamIds = $teams->pluck('id');
            }
            if ($user->currentTeam && !$teamIds->contains($user->currentTeam->id)) {
                $teamIds->push($user->currentTeam->id);
                $teams = $teams->push($user->currentTeam)->unique('id');
            }
        } catch (\Throwable $e) {}
    }

    $cycles = \Platform\Okr\Models\Cycle::query()
        ->leftJoin('okr_cycle_templates', 'okr_cycle_templates.id', '=', 'okr_cycles.cycle_template_id')
        ->when($teamIds->isNotEmpty(), function($q) use ($teamIds){ $q->whereIn('okr_cycles.team_id', $teamIds); }, function($q){ $q->whereRaw('1=0'); })
        ->orderByDesc('okr_cycles.id')
        ->get(['okr_cycles.id','okr_cycles.team_id','okr_cycle_templates.label as template_label'])
        ->map(function($c){ return [ 'id' => $c->id, 'team_id' => $c->team_id, 'template_label' => $c->template_label ]; });

    $response = response()->view('okr::embedded.teams-config', [
        'teams' => $teams,
        'cycles' => $cycles,
    ]);
    $response->headers->set('Content-Security-Policy', "frame-ancestors https://*.teams.microsoft.com https://teams.microsoft.com https://*.skype.com");
    return $response;
})->name('okr.embedded.teams.config');

// Embedded Cycle View (Teams/iFrame)
Route::middleware([\Platform\Core\Middleware\EmbeddedHeaderAuth::class])->group(function () {
    Route::get('/embedded/okr/cycles/{cycle}', \Platform\Okr\Livewire\Embedded\Cycle::class)
        ->withoutMiddleware([FrameGuard::class])
        ->name('okr.embedded.cycle');
});
