<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <x-slot name="titleActions">
                <x-ui-button variant="secondary-ghost" size="sm" rounded="full" iconOnly="true" x-data @click="$dispatch('open-modal-task-settings', { taskId: {{ $okr->id }} })" title="Einstellungen">
                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                </x-ui-button>
            </x-slot>
            
            {{-- Simple Breadcrumbs --}}
            <div class="flex items-center space-x-2 text-sm">
                <a href="{{ route('okr.dashboard') }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-home', 'w-4 h-4')
                    Dashboard
                </a>
                <span class="text-[var(--ui-muted)]">›</span>
                <a href="{{ route('okr.okrs.index') }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                    OKRs
                </a>
                <span class="text-[var(--ui-muted)]">›</span>
                <span class="text-[var(--ui-muted)] flex items-center gap-1">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                    {{ $okr->title }}
                </span>
            </div>
            
            <x-ui-button variant="secondary" size="sm" wire:click="save">
                <span class="inline-flex items-center gap-2">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span class="hidden sm:inline">Speichern</span>
                </span>
            </x-ui-button>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Modern Header --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight">{{ $okr->title }}</h1>
                    <div class="flex items-center gap-6 text-sm text-[var(--ui-muted)]">
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
                        label="OKR-Titel"
                        wire:model.live.debounce.500ms="okr.title"
                        placeholder="z.B. Kundenbetreuung verbessern"
                        required
                        :errorKey="'okr.title'"
                        class="text-lg"
                    />
                    
                    <x-ui-input-select
                        name="okr.manager_user_id"
                        label="Verantwortlicher Manager"
                        :options="$this->users"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Manager auswählen –"
                        wire:model.live="okr.manager_user_id"
                    />
                </div>
                
                <div class="space-y-4">
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
                                />
                                
                                <x-ui-input-checkbox
                                    model="okr.is_template"
                                    checked-label="Als Vorlage speichern"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cycles --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-calendar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Zyklen</h3>
                        <p class="text-sm text-[var(--ui-muted)]">OKR-Zyklen verwalten</p>
                    </div>
                </div>
                <x-ui-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="openCycleCreateModal"
                >
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
                    <h4 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Zyklen vorhanden</h4>
                    <p class="text-[var(--ui-muted)] mb-4">Erstelle den ersten Zyklus für dieses OKR</p>
                    <x-ui-button 
                        variant="secondary" 
                        wire:click="openCycleCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Zyklus hinzufügen</span>
                    </x-ui-button>
                </div>
            @endif
        </div>

        {{-- Aktivitäten --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-clock', 'w-4 h-4')
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Aktivitäten</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Letzte Änderungen und Aktivitäten</p>
                </div>
            </div>
            
            <div class="text-center py-8">
                <p class="text-[var(--ui-muted)]">Aktivitäten werden später implementiert</p>
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Navigation & Details" width="w-80" :defaultOpen="true">
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
                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300 {{ $okrPerformance->performance_score >= 80 ? 'bg-green-500' : ($okrPerformance->performance_score >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                         style="width: {{ $okrPerformance->performance_score }}%"></div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $totalCycles }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Cycles</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $totalObjectives }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Objectives</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $completedKeyResults }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Erreichte KR</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ $totalKeyResults - $completedKeyResults }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Offene KR</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- OKR Info --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">OKR Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Team</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $okr->team->name ?? 'Kein Team' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Manager</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $okr->manager->name ?? 'Nicht zugewiesen' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $okr->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button 
                            variant="secondary" 
                            wire:click="openCycleCreateModal"
                            class="w-full"
                        >
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span class="ml-1">Zyklus hinzufügen</span>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">OKR erstellt</div>
                        <div class="text-[var(--ui-muted)]">{{ $okr->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Cycle Create Modal - Step by step --}}
    <x-ui-modal wire:model="modalShow" title="Zyklus hinzufügen">
        <div class="space-y-4">
            <x-ui-input-text
                name="cycleForm.notes"
                label="Notizen"
                wire:model="cycleForm.notes"
                placeholder="Optionale Notizen zum Zyklus"
            />
            <x-ui-input-select
                name="cycleForm.cycle_template_id"
                label="Zyklus-Vorlage"
                :options="$cycleTemplates"
                optionValue="id"
                optionLabel="label"
                :nullable="true"
                nullLabel="– Vorlage auswählen –"
                wire:model.live="cycleForm.cycle_template_id"
                :errorKey="'cycleForm.cycle_template_id'"
            />
        </div>
        
        <x-slot name="footer">
            <div class="flex justify-end space-x-3">
                <x-ui-button variant="secondary" wire:click="closeCycleCreateModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="createCycle">
                    Zyklus erstellen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>