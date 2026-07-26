<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zukunftsbilder'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Zukunftsbild hinzufügen</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained">
        {{-- Header mit Aktionen --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--nx-text)]">Zukunftsbilder</h2>
                    <p class="text-sm text-[var(--nx-muted)] mt-1">Strategische Ausrichtung & Transformationssteuerung</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="openCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Zukunftsbild hinzufügen</span>
                    </x-nx-button>
                </div>
            </div>
        </div>

        {{-- Statistiken --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--nx-muted)]">Gesamt Zukunftsbilder</p>
                        <p class="text-2xl font-bold text-[var(--nx-text)] mt-1">{{ $totalForecasts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-[color:var(--nx-accent)] text-[color:var(--nx-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-sparkles', 'w-6 h-6')
                    </div>
                </div>
            </div>
            <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--nx-muted)]">Fokusräume</p>
                        <p class="text-2xl font-bold text-[var(--nx-text)] mt-1">{{ $totalFocusAreas }}</p>
                    </div>
                    <div class="w-12 h-12 bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-viewfinder-circle', 'w-6 h-6')
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg mb-6">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Forecasts Liste --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
            @if($forecasts->count() > 0)
                <div class="divide-y divide-[color:var(--nx-line)]">
                    @foreach($forecasts as $forecast)
                        <div class="p-6 hover:bg-[var(--nx-bg)] transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <a 
                                            href="{{ route('okr.forecasts.show', $forecast) }}" 
                                            wire:navigate
                                            class="text-lg font-semibold text-[var(--nx-accent)] hover:underline"
                                        >
                                            {{ $forecast->title }}
                                        </a>
                                        @if($forecast->currentVersion)
                                            <x-nx-badge variant="neutral" size="sm">v{{ $forecast->currentVersion->version }}</x-nx-badge>
                                        @endif
                                        <x-nx-badge variant="neutral" size="sm">{{ $forecast->focusAreas->count() }} Fokusräume</x-nx-badge>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)]">
                                        <span class="flex items-center gap-1">
                                            @svg('heroicon-o-calendar', 'w-4 h-4')
                                            Zieldatum: {{ $forecast->target_date->format('d.m.Y') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            @svg('heroicon-o-user', 'w-4 h-4')
                                            {{ $forecast->user->name ?? 'Unbekannt' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            @svg('heroicon-o-clock', 'w-4 h-4')
                                            {{ $forecast->created_at->format('d.m.Y') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <x-nx-button variant="ghost" size="sm" wire:click="deleteForecast({{ $forecast->id }})" wire:confirm="Zukunftsbild wirklich löschen?">Löschen</x-nx-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-[color:var(--nx-line)]">
                    {{ $forecasts->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-sparkles', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Zukunftsbilder vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Erstelle ein neues Zukunftsbild um zu beginnen</p>
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="openCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erstes Zukunftsbild erstellen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zukunftsbild Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalForecasts }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Gesamt Zukunftsbilder</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[color:var(--nx-accent)]">{{ $totalFocusAreas }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Fokusräume</div>
                        </div>
                    </div>
                </div>

                {{-- Aktuelle Zukunftsbilder --}}
                @if($forecasts->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Aktuelle Zukunftsbilder</h3>
                        <div class="space-y-2">
                            @foreach($forecasts->take(5) as $forecast)
                                <a 
                                    href="{{ route('okr.forecasts.show', $forecast) }}" 
                                    wire:navigate
                                    class="block p-2 rounded-lg border border-[color:var(--nx-line)] hover:bg-[var(--nx-bg)] transition-colors"
                                >
                                    <div class="text-sm font-medium text-[var(--nx-text)]">{{ $forecast->title }}</div>
                                    <div class="text-xs text-[var(--nx-muted)] mt-1">
                                        {{ $forecast->target_date->format('d.m.Y') }} • {{ $forecast->focusAreas->count() }} Fokusräume
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                {{-- Recent Activities --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-3 text-sm">
                        @if($forecasts->count() > 0)
                            @foreach($forecasts->take(5) as $forecast)
                                <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                    <div class="w-8 h-8 bg-[color:var(--nx-accent)] text-white rounded-full flex items-center justify-center text-xs font-semibold">
                                        @svg('heroicon-o-sparkles', 'w-4 h-4')
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-[var(--nx-text)] text-sm">{{ $forecast->title }}</div>
                                        <div class="text-xs text-[var(--nx-muted)]">{{ $forecast->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-[var(--nx-muted)]">Keine Aktivitäten verfügbar</div>
                        @endif
                    </div>
                </div>

                {{-- Übersicht --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Übersicht</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalForecasts }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Gesamt Zukunftsbilder</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[color:var(--nx-accent)]">{{ $totalFocusAreas }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Fokusräume</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <!-- Create Modal -->
    <x-nx-modal
        size="lg"
        model="modalShow"
    >
        <x-slot name="header">
            Neues Zukunftsbild erstellen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createForecast" class="space-y-4">
                <x-nx-input-text
                    name="title"
                    label="Titel"
                    wire:model.live="title"
                    placeholder="z.B. Zukunftsbild 2028"
                    required
                />

                <x-nx-input-date
                    name="target_date"
                    label="Zieldatum"
                    wire:model.live="target_date"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button 
                    type="button" 
                    variant="ghost" 
                    wire:click="closeCreateModal"
                >
                    Abbrechen
                </x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="createForecast">
                    Erstellen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>
</x-ui-page>
