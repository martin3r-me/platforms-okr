<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Regnosen" icon="heroicon-o-sparkles" />
    </x-slot>

    <x-ui-page-container>
        {{-- Header mit Aktionen --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Regnose-Verwaltung</h2>
                    <p class="text-sm text-[var(--ui-muted)] mt-1">Strategische Ausrichtung & Transformationssteuerung</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui-button 
                        variant="secondary" 
                        wire:click="openCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Regnose hinzufügen</span>
                    </x-ui-button>
                </div>
            </div>
        </div>

        {{-- Statistiken --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--ui-muted)]">Gesamt Regnosen</p>
                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $totalForecasts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-sparkles', 'w-6 h-6')
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--ui-muted)]">Focus Areas</p>
                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ $totalFocusAreas }}</p>
                    </div>
                    <div class="w-12 h-12 bg-[var(--ui-primary-10)] text-[var(--ui-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-viewfinder-circle', 'w-6 h-6')
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg mb-6">
                <p class="text-[var(--ui-secondary)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Regnosen Liste --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60">
            @if($forecasts->count() > 0)
                <div class="divide-y divide-[var(--ui-border)]/40">
                    @foreach($forecasts as $forecast)
                        <div class="p-6 hover:bg-[var(--ui-muted-5)] transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <a 
                                            href="{{ route('okr.forecasts.show', $forecast) }}" 
                                            wire:navigate
                                            class="text-lg font-semibold text-[var(--ui-primary)] hover:underline"
                                        >
                                            {{ $forecast->title }}
                                        </a>
                                        @if($forecast->currentVersion)
                                            <x-ui-badge variant="secondary" size="sm">v{{ $forecast->currentVersion->version }}</x-ui-badge>
                                        @endif
                                        <x-ui-badge variant="secondary" size="sm">{{ $forecast->focusAreas->count() }} Focus Areas</x-ui-badge>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-[var(--ui-muted)]">
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
                                    <x-ui-confirm-button 
                                        action="deleteForecast({{ $forecast->id }})" 
                                        text="Löschen" 
                                        confirmText="Regnose wirklich löschen?" 
                                        variant="secondary-ghost"
                                        size="sm"
                                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-[var(--ui-border)]/40">
                    {{ $forecasts->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-sparkles', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Noch keine Regnosen vorhanden</h4>
                    <p class="text-[var(--ui-muted)] mb-4">Erstellen Sie eine neue Regnose um zu beginnen</p>
                    <x-ui-button 
                        variant="secondary" 
                        wire:click="openCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erste Regnose erstellen</span>
                    </x-ui-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <!-- Create Modal -->
    <x-ui-modal
        size="lg"
        model="modalShow"
    >
        <x-slot name="header">
            Neue Regnose erstellen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createForecast" class="space-y-4">
                <x-ui-input-text
                    name="title"
                    label="Titel"
                    wire:model.live="title"
                    placeholder="z.B. Regnose 2028"
                    required
                />

                <x-ui-input-date
                    name="target_date"
                    label="Zieldatum"
                    wire:model.live="target_date"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-ghost" 
                    wire:click="closeCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="secondary" wire:click="createForecast">
                    Erstellen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
