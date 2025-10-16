<?php

namespace Platform\Okr;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use Platform\Okr\Models\Okr;
use Platform\Okr\Policies\OkrPolicy;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Policies\CyclePolicy;

class OkrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Okr\Console\Commands\GenerateQuarterCycleTemplates::class,
                \Platform\Okr\Console\Commands\GenerateCycleTemplates::class,
                \Platform\Okr\Console\Commands\UpdateCurrentCycle::class,
                \Platform\Okr\Console\Commands\MaintainCycleTemplates::class,
                \Platform\Okr\Console\Commands\SeedOkrData::class,
                \Platform\Okr\Console\Commands\SeedOkrLookupData::class,
                \Platform\Okr\Console\Commands\UpdateOkrPerformance::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Policies
        Gate::policy(Okr::class, OkrPolicy::class);
        Gate::policy(Cycle::class, CyclePolicy::class);

        // Schritt 1: Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/okr.php', 'okr');
        
        // Schritt 2: Existenzprüfung (config jetzt verfügbar)
        if (
            config()->has('okr.routing') &&
            config()->has('okr.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'okr',
                'title'      => 'OKR',
                'routing'    => config('okr.routing'),
                'guard'      => config('okr.guard'),
                'navigation' => config('okr.navigation'),
            ]);
        }

        // Schritt 3: Wenn Modul registriert, Routes laden
        if (PlatformCore::getModule('okr')) {
            ModuleRouter::group('okr', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/guest.php');
            }, requireAuth: false);

            ModuleRouter::group('okr', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Schritt 4: Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Schritt 5: Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/okr.php' => config_path('okr.php'),
        ], 'config');

        // Schritt 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'okr');
        $this->registerLivewireComponents();
        
        // Schritt 7: Scheduler für Performance Updates
        $this->schedulePerformanceUpdates();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('okr.dashboard', \Platform\Okr\Livewire\Dashboard::class);
        Livewire::component('okr.okr-management', \Platform\Okr\Livewire\OkrManagement::class);
        Livewire::component('okr.okr-show', \Platform\Okr\Livewire\OkrShow::class);
        Livewire::component('okr.cycle-show', \Platform\Okr\Livewire\CycleShow::class);
        Livewire::component('okr.objective-show', \Platform\Okr\Livewire\ObjectiveShow::class);
        Livewire::component('okr.sidebar', \Platform\Okr\Livewire\Sidebar::class);

        // Embedded Components
        if (class_exists(\Platform\Okr\Livewire\Embedded\Cycle::class)) {
            Livewire::component('okr.embedded.cycle', \Platform\Okr\Livewire\Embedded\Cycle::class);
            // Fallback Alias falls ein voller Namenspfad referenziert wird
            Livewire::component('platform.okr.livewire.embedded.cycle', \Platform\Okr\Livewire\Embedded\Cycle::class);
        }
    }

    private function schedulePerformanceUpdates(): void
    {
        // Tägliche Performance Updates um 02:00 Uhr (inkl. Team Performance)
        Schedule::command('okr:update-performance')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }
}
