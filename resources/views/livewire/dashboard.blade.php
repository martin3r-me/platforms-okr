<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKR Dashboard" />
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Stats - Nur die wichtigsten 4 Metriken --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-ui-dashboard-tile
                title="Performance Score"
                :count="round($averageScore ?? 0, 1)"
                subtitle="Team Durchschnitt"
                icon="chart-bar"
                variant="primary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Aktive OKRs"
                :count="$activeOkrsCount"
                subtitle="laufende Ziele"
                icon="flag"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Erreichte Ziele"
                :count="$achievedObjectivesCount"
                subtitle="von {{ $activeObjectivesCount }}"
                icon="check-circle"
                variant="success"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Aktive Zyklen"
                :count="$activeCyclesCount"
                subtitle="laufende Zeiträume"
                icon="calendar"
                variant="info"
                size="lg"
            />
        </div>

        {{-- Aktive Zyklen - Vereinfacht und fokussiert --}}
        <x-ui-panel title="Aktive Zyklen" subtitle="Laufende OKR-Zyklen">
            @if($activeCycles && $activeCycles->count() > 0)
                <div class="space-y-4">
                    @foreach($activeCycles as $cycle)
                        @php
                            $cyclePerformance = $cycle->performance;
                            $cycleScore = $cyclePerformance ? $cyclePerformance->performance_score : 0;
                            $cycleScoreColor = $cycleScore >= 80 ? 'text-green-600' : ($cycleScore >= 60 ? 'text-yellow-600' : 'text-red-600');
                            $cycleProgressColor = $cycleScore >= 80 ? 'bg-green-500' : ($cycleScore >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                        @endphp
                        <div class="bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40 p-6 hover:border-[var(--ui-border)]/60 transition-colors">
                            {{-- Cycle Header --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                                        @svg('heroicon-o-calendar', 'w-5 h-5')
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $cycle->okr?->title ?? 'OKR' }}</h3>
                                        <div class="text-sm text-[var(--ui-muted)]">{{ $cycle->template?->label }} • {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    {{-- Performance Score --}}
                                    <div class="text-center">
                                        <div class="text-2xl font-bold {{ $cycleScoreColor }}">{{ round($cycleScore, 1) }}%</div>
                                        <div class="text-xs text-[var(--ui-muted)]">Performance</div>
                                    </div>
                                    <div class="w-20">
                                        <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $cycleProgressColor }}" style="width: {{ $cycleScore }}%"></div>
                                        </div>
                                    </div>
                                    
                                    <x-ui-badge variant="secondary" size="sm">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                    <x-ui-button 
                                        size="sm" 
                                        variant="primary" 
                                        :href="route('okr.cycles.show', ['cycle' => $cycle->id])" 
                                        wire:navigate
                                    >
                                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                        Öffnen
                                    </x-ui-button>
                                </div>
                            </div>

                            {{-- Objectives Summary - Vereinfacht --}}
                            @if($cycle->objectives->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($cycle->objectives->take(6) as $objective)
                                        @php
                                            $objectivePerformance = $objective->performance;
                                            $objectiveScore = $objectivePerformance ? $objectivePerformance->performance_score : 0;
                                            $objectiveScoreColor = $objectiveScore >= 80 ? 'text-green-600' : ($objectiveScore >= 60 ? 'text-yellow-600' : 'text-red-600');
                                        @endphp
                                        <div class="bg-white rounded-lg border border-[var(--ui-border)]/40 p-4 hover:border-[var(--ui-border)]/60 transition-colors">
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $objective->title }}</h5>
                                                <x-ui-badge variant="secondary" size="xs">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-bold {{ $objectiveScoreColor }}">{{ round($objectiveScore, 1) }}%</div>
                                                <div class="flex-1 bg-[var(--ui-border)]/40 rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full {{ $objectiveScore >= 80 ? 'bg-green-500' : ($objectiveScore >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $objectiveScore }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                @if($cycle->objectives->count() > 6)
                                    <div class="text-center mt-4">
                                        <span class="text-sm text-[var(--ui-muted)]">+{{ $cycle->objectives->count() - 6 }} weitere Objectives</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-[var(--ui-muted)]">
                        @svg('heroicon-o-calendar')
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-[var(--ui-secondary)]">Kein aktiver Zyklus</h3>
                    <p class="mt-1 text-sm text-[var(--ui-muted)]">Es ist aktuell kein OKR-Zyklus aktiv.</p>
                </div>
            @endif
        </x-ui-panel>
    </x-ui-page-container>

    {{-- Left Sidebar - Dashboard Übersicht --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Dashboard Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Performance Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Performance</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">Team Performance</span>
                                <span class="text-sm font-bold {{ ($averageScore ?? 0) >= 80 ? 'text-green-600' : (($averageScore ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ round($averageScore ?? 0, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2 mb-2">
                                <div class="h-2 rounded-full {{ ($averageScore ?? 0) >= 80 ? 'bg-green-500' : (($averageScore ?? 0) >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                     style="width: {{ $averageScore ?? 0 }}%"></div>
                            </div>
                            <div class="text-xs text-[var(--ui-muted)]">
                                Durchschnitt aller aktiven OKRs
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $activeOkrsCount ?? 0 }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Aktive OKRs</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $activeCyclesCount ?? 0 }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Aktive Zyklen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ziele Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Ziele</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Erreichte Ziele</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $achievedObjectivesCount ?? 0 }}/{{ $activeObjectivesCount ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Erreichte Key Results</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $achievedKeyResultsCount ?? 0 }}/{{ $activeKeyResultsCount ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Offene Key Results</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $openKeyResultsCount ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                {{-- Schnellzugriff --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Schnellzugriff</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary" size="sm" :href="route('okr.okrs.index')" wire:navigate class="w-full justify-start">
                            @svg('heroicon-o-flag', 'w-4 h-4')
                            <span class="ml-2">Alle OKRs</span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span class="ml-2">Neues OKR</span>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar - Aktivitäten --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten & Timeline" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                {{-- Recent Activities --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-3 text-sm">
                        <div class="text-[var(--ui-muted)]">Keine Aktivitäten verfügbar</div>
                    </div>
                </div>

                {{-- Performance Übersicht --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Performance</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[var(--ui-primary)]">{{ round($averageScore ?? 0, 1) }}%</div>
                            <div class="text-xs text-[var(--ui-muted)]">Durchschnitt Score</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-green-600">{{ $achievedObjectivesCount ?? 0 }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Erreichte Ziele</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-blue-600">{{ $achievedKeyResultsCount ?? 0 }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Erreichte KR</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>