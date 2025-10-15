<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <x-slot name="titleActions">
                <x-ui-button 
                    variant="secondary-ghost" 
                    size="sm"
                    :href="route('okr.dashboard')" 
                    wire:navigate
                >
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span class="ml-1">Dashboard</span>
                </x-ui-button>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                <p class="text-[var(--ui-secondary)]">{{ session('message') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                <p class="text-[var(--ui-secondary)] font-medium">Fehler:</p>
                <p class="text-[var(--ui-muted)]">{{ session('error') }}</p>
            </div>
        @endif

        {{-- OKR Header --}}
        <div class="bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-flag', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight">{{ $okr->title }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--ui-muted)] mt-1">
                                @if($okr->team)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-building-office', 'w-4 h-4')
                                        {{ $okr->team->name }}
                                    </span>
                                @endif
                                @if($okr->manager)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user', 'w-4 h-4')
                                        {{ $okr->manager->name }}
                                    </span>
                                @endif
                                @php
                                    $okrPerformance = $okr->performance;
                                @endphp
                                @if($okrPerformance)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span class="font-medium {{ $okrPerformance->performance_score >= 80 ? 'text-green-600' : ($okrPerformance->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $okrPerformance->performance_score }}%
                                        </span>
                                    </span>
                                @elseif($okr->performance_score)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span class="font-medium text-[var(--ui-muted)]">
                                            {{ $okr->performance_score }}%
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- OKR Details --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">OKR Details</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Grundinformationen und Einstellungen</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <x-ui-input-text
                        name="okr.title"
                        label="Titel"
                        wire:model.live.debounce.500ms="okr.title"
                        placeholder="OKR Titel eingeben..."
                        :errorKey="'okr.title'"
                    />
                    
                    <x-ui-input-textarea
                        name="okr.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="okr.description"
                        placeholder="Detaillierte Beschreibung des OKRs..."
                        rows="4"
                        :errorKey="'okr.description'"
                    />
                    
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[var(--ui-secondary)]">Einstellungen</label>
                        <div class="space-y-2">
                            <x-ui-input-checkbox
                                model="okr.auto_transfer"
                                checked-label="Automatische Übertragung"
                                unchecked-label="Manuelle Übertragung"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cycles Section --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-calendar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Zyklen</h3>
                        <p class="text-sm text-[var(--ui-muted)]">Verwalte die OKR-Zyklen</p>
                    </div>
                </div>
                <x-ui-button variant="primary" size="sm" wire:click="openCycleCreateModal">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Zyklus hinzufügen</span>
                </x-ui-button>
            </div>
            
            @if($okr->cycles->count() > 0)
                <div class="space-y-4">
                    @foreach($okr->cycles as $cycle)
                        @php
                            $cyclePerformance = $cycle->performance;
                            $totalObjectives = $cycle->objectives->count();
                            $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                            $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                            $cycleProgress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                        @endphp
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4 hover:border-[var(--ui-border)]/60 transition-colors cursor-pointer" 
                             wire:click="openCycle({{ $cycle->id }})">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center text-xs font-semibold">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-[var(--ui-secondary)]">{{ $cycle->template->label ?? 'Unbekannter Zyklus' }}</h4>
                                        <p class="text-sm text-[var(--ui-muted)]">
                                            {{ $cycle->starts_at ? $cycle->starts_at->format('d.m.Y') : 'Kein Startdatum' }} - 
                                            {{ $cycle->ends_at ? $cycle->ends_at->format('d.m.Y') : 'Kein Enddatum' }}
                                        </p>
                                        <p class="text-xs text-[var(--ui-muted)]">
                                            {{ $totalObjectives }} Objectives • {{ $totalKeyResults }} Key Results
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($cyclePerformance)
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $cyclePerformance->performance_score >= 80 ? 'text-green-600' : ($cyclePerformance->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $cyclePerformance->performance_score }}%
                                            </div>
                                            <div class="text-xs text-[var(--ui-muted)]">Performance</div>
                                        </div>
                                    @else
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $cycleProgress >= 80 ? 'text-green-600' : ($cycleProgress >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $cycleProgress }}%
                                            </div>
                                            <div class="text-xs text-[var(--ui-muted)]">Fortschritt</div>
                                        </div>
                                    @endif
                                    <x-ui-badge variant="secondary">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                    <x-ui-button 
                                        variant="secondary-ghost" 
                                        size="sm"
                                        wire:click.stop="editCycle({{ $cycle->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-ui-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-calendar', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Zyklen vorhanden</h3>
                    <p class="text-[var(--ui-muted)] mb-6">Erstelle den ersten Zyklus für dieses OKR</p>
                    <x-ui-button variant="primary" wire:click="openCycleCreateModal">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Zyklus erstellen</span>
                    </x-ui-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Cycle Create Modal --}}
    <x-ui-modal wire:model="modalShow">
        <x-slot name="header">Zyklus erstellen</x-slot>
        <div class="space-y-4">
            <x-ui-input-text
                name="cycleForm.notes"
                label="Notizen"
                wire:model="cycleForm.notes"
                placeholder="Optionale Notizen zum Zyklus..."
            />
            
            <div>
                <x-ui-input-select
                    name="cycleForm.cycle_template_id"
                    label="Template"
                    :options="$cycleTemplates"
                    optionValue="id"
                    optionLabel="label"
                    :nullable="false"
                    wire:model.live="cycleForm.cycle_template_id"
                />
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-ui-button variant="secondary-ghost" wire:click="closeCycleCreateModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="createCycle">
                    Zyklus erstellen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="OKR Übersicht" width="w-80" side="left" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Navigation</h3>
                    <div class="space-y-2">
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            :href="route('okr.dashboard')"
                            wire:navigate
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-home', 'w-4 h-4')
                                Zum Dashboard
                            </span>
                        </x-ui-button>
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            :href="route('okr.okrs.index')"
                            wire:navigate
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-flag', 'w-4 h-4')
                                Zu allen OKRs
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- OKR Performance --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">OKR Performance</h3>
                    <div class="space-y-3">
                        @php
                            $okrPerformance = $okr->performance;
                            $totalCycles = $okr->cycles->count();
                            $totalObjectives = $okr->cycles->sum(fn($cycle) => $cycle->objectives->count());
                            $totalKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()));
                            $completedKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count()));
                        @endphp
                        
                        @if($okrPerformance)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Gesamt Performance</span>
                                    <span class="text-sm font-bold {{ $okrPerformance->performance_score >= 80 ? 'text-green-600' : ($okrPerformance->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $okrPerformance->performance_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $okrPerformance->performance_score >= 80 ? 'bg-green-500' : ($okrPerformance->performance_score >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                         style="width: {{ $okrPerformance->performance_score }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--ui-muted)]">
                                    {{ $okrPerformance->completed_cycles }}/{{ $okrPerformance->total_cycles }} Cycles • 
                                    {{ $okrPerformance->completed_objectives }}/{{ $okrPerformance->total_objectives }} Objectives
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalCycles }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Cycles</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalObjectives }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Objectives</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalKeyResults }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Key Results</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-green-600">{{ $completedKeyResults }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Abgeschlossen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- OKR Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">OKR Details</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Team</span>
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $okr->team->name ?? 'Kein Team' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Manager</span>
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $okr->manager->name ?? 'Kein Manager' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Auto Transfer</span>
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $okr->auto_transfer ? 'Ja' : 'Nein' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Erstellt</span>
                                    <span class="text-sm text-[var(--ui-muted)]">{{ $okr->created_at->format('d.m.Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                {{-- Recent Activities --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Letzte Aktivitäten</h3>
                    <div class="space-y-3">
                        @if($this->activities && $this->activities->count() > 0)
                            @foreach($this->activities as $activity)
                                <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3">
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-full flex items-center justify-center text-xs">
                                            @svg('heroicon-o-flag', 'w-3 h-3')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-[var(--ui-secondary)]">{{ $activity->description ?? 'OKR Aktivität' }}</p>
                                            <p class="text-xs text-[var(--ui-muted)] mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <div class="w-12 h-12 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-3">
                                    @svg('heroicon-o-flag', 'w-6 h-6 text-[var(--ui-muted)]')
                                </div>
                                <p class="text-sm text-[var(--ui-muted)]">Keine Aktivitäten vorhanden</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Schnellaktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            wire:click="openCycleCreateModal"
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Zyklus hinzufügen
                            </span>
                        </x-ui-button>
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            wire:click="save"
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-check', 'w-4 h-4')
                                Speichern
                            </span>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>