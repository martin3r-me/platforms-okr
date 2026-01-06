<?php

namespace Platform\Okr;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Contracts\CounterKeyResultSyncer;
use Platform\Core\Routing\ModuleRouter;
use Platform\Okr\Models\Okr;
use Platform\Okr\Policies\OkrPolicy;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Policies\CyclePolicy;
use Platform\Okr\Services\CounterKeyResultSyncService;

class OkrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Counter→KeyResult Sync: echte Implementierung binden (überschreibt Core-Default No-Op)
        $this->app->singleton(CounterKeyResultSyncer::class, function () {
            return new CounterKeyResultSyncService();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Okr\Console\Commands\GenerateQuarterCycleTemplates::class,
                \Platform\Okr\Console\Commands\GenerateCycleTemplates::class,
                \Platform\Okr\Console\Commands\UpdateCurrentCycle::class,
                \Platform\Okr\Console\Commands\UpdateCycleStatuses::class,
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

            // API-Routen registrieren
            ModuleRouter::apiGroup('okr', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
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

        // Schritt 8: Tools registrieren (für AI/Chat) – analog zum Planner-Modul
        $this->registerTools();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('okr.dashboard', \Platform\Okr\Livewire\Dashboard::class);
        Livewire::component('okr.okr-management', \Platform\Okr\Livewire\OkrManagement::class);
        Livewire::component('okr.okr-show', \Platform\Okr\Livewire\OkrShow::class);
        Livewire::component('okr.cycle-show', \Platform\Okr\Livewire\CycleShow::class);
        Livewire::component('okr.objective-show', \Platform\Okr\Livewire\ObjectiveShow::class);
        Livewire::component('okr.sidebar', \Platform\Okr\Livewire\Sidebar::class);
        Livewire::component('okr.modal-key-result', \Platform\Okr\Livewire\ModalKeyResult::class);
        Livewire::component('okr.strategic-documents-management', \Platform\Okr\Livewire\StrategicDocumentsManagement::class);
        Livewire::component('platform.okr.livewire.strategic-documents-management', \Platform\Okr\Livewire\StrategicDocumentsManagement::class);

        // Embedded Components
        if (class_exists(\Platform\Okr\Livewire\Embedded\Cycle::class)) {
            Livewire::component('okr.embedded.cycle', \Platform\Okr\Livewire\Embedded\Cycle::class);
            // Fallback Alias falls ein voller Namenspfad referenziert wird
            Livewire::component('platform.okr.livewire.embedded.cycle', \Platform\Okr\Livewire\Embedded\Cycle::class);
        }
    }

    private function schedulePerformanceUpdates(): void
    {
        // Monatliche Wartung der Cycle Templates (erstellt neue Templates)
        // Läuft am 1. des Monats um 00:30 Uhr
        Schedule::command('okr:maintain-cycles')
            ->monthlyOn(1, '00:30')
            ->withoutOverlapping()
            ->runInBackground();

        // Monatliche Aktualisierung der aktuellen Cycles (is_current auf CycleTemplates)
        // Läuft am 1. des Monats um 01:00 Uhr, nach der Template-Wartung
        // Da alle Zyklen >= 1 Monat sind, ändert sich is_current nur monatlich
        Schedule::command('okr:update-current-cycle --type=quarter')
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->runInBackground();
        
        Schedule::command('okr:update-current-cycle --type=annual')
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->runInBackground();
        
        Schedule::command('okr:update-current-cycle --type=monthly')
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Tägliche Aktualisierung der Cycle-Status (active, ending_soon, past, draft)
        // Läuft täglich um 01:05 Uhr, nach der Template-Aktualisierung
        // Setzt Status basierend auf Template-Zeitraum: past, ending_soon, active, draft
        // Muss täglich laufen, da sich Status jeden Tag ändern kann (z.B. active → ending_soon → past)
        Schedule::command('okr:update-cycle-statuses')
            ->dailyAt('01:05')
            ->withoutOverlapping()
            ->runInBackground();

        // Tägliche Performance Updates um 02:00 Uhr (inkl. Team Performance)
        Schedule::command('okr:update-performance')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Counter→KeyResult Sync (alle 15 Minuten)
        Schedule::command('core:sync-counter-key-results')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Registriert OKR-Tools für die AI/Chat-Funktionalität.
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Entry / Overview
            $registry->register(new \Platform\Okr\Tools\OkrOverviewTool());

            // Read tools
            $registry->register(new \Platform\Okr\Tools\ListOkrsTool());
            $registry->register(new \Platform\Okr\Tools\GetOkrTool());
            $registry->register(new \Platform\Okr\Tools\ListCycleTemplatesTool());
            $registry->register(new \Platform\Okr\Tools\ListCyclesTool());
            $registry->register(new \Platform\Okr\Tools\GetCycleTool());
            $registry->register(new \Platform\Okr\Tools\ListObjectivesTool());
            $registry->register(new \Platform\Okr\Tools\GetObjectiveTool());
            $registry->register(new \Platform\Okr\Tools\ListKeyResultsTool());
            $registry->register(new \Platform\Okr\Tools\GetKeyResultTool());
            $registry->register(new \Platform\Okr\Tools\ListPerformancesTool());

            // Write tools (Cycles/Objectives/KRs)
            $registry->register(new \Platform\Okr\Tools\CreateCycleTool());
            $registry->register(new \Platform\Okr\Tools\UpdateCycleTool());
            $registry->register(new \Platform\Okr\Tools\DeleteCycleTool());

            $registry->register(new \Platform\Okr\Tools\CreateObjectiveTool());
            $registry->register(new \Platform\Okr\Tools\UpdateObjectiveTool());
            $registry->register(new \Platform\Okr\Tools\DeleteObjectiveTool());

            $registry->register(new \Platform\Okr\Tools\CreateKeyResultTool());
            $registry->register(new \Platform\Okr\Tools\UpdateKeyResultTool());
            $registry->register(new \Platform\Okr\Tools\DeleteKeyResultTool());
        } catch (\Throwable $e) {
            \Log::warning('OKR: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }
}
