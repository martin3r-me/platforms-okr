<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'icon' => 'flag'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Stats - Nur die wichtigsten 4 Metriken --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-nx-stat label="Performance Score" :value="round($averageScore ?? 0, 1)" hint="Team Durchschnitt" icon="heroicon-o-chart-bar" />
            <x-nx-stat label="Aktive Zielsteuerungen" :value="$activeOkrsCount" hint="laufende Ziele" icon="heroicon-o-flag" />
            <x-nx-stat label="Erreichte Ziele" :value="$achievedObjectivesCount" hint="von {{ $activeObjectivesCount }}" icon="heroicon-o-check-circle" />
            <x-nx-stat label="Aktive Zyklen" :value="$activeCyclesCount" hint="laufende Zeiträume" icon="heroicon-o-calendar" />
        </div>

        {{-- Aktive Zyklen - Vereinfacht und fokussiert --}}
        <x-nx-section title="Aktive Zyklen" hint="Laufende Zielsteuerung-Zyklen">
        <x-nx-card flush class="p-6">
            @if($activeCycles && $activeCycles->count() > 0)
                <div class="space-y-4">
                    @foreach($activeCycles as $cycle)
                        @php
                            $cyclePerformance = $cycle->performance;
                            $cycleScore = $cyclePerformance ? $cyclePerformance->performance_score : 0;
                            $cycleScoreColor = $cycleScore >= 80 ? 'text-[color:var(--nx-success)]' : ($cycleScore >= 60 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]');
                            $cycleProgressColor = $cycleScore >= 80 ? 'bg-[color:var(--nx-success)]' : ($cycleScore >= 60 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]');
                        @endphp
                        <div class="bg-[color:var(--nx-bg)] rounded-xl border border-[color:var(--nx-line)] p-6 hover:border-[color:var(--nx-line)] transition-colors">
                            {{-- Cycle Header --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                                        @svg('heroicon-o-calendar', 'w-5 h-5')
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-[var(--nx-text)]">{{ $cycle->okr?->title ?? 'Zielsteuerung' }}</h3>
                                        <div class="text-sm text-[var(--nx-muted)]">{{ $cycle->template?->label }} • {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    {{-- Performance Score --}}
                                    <div class="text-center">
                                        <div class="text-2xl font-bold {{ $cycleScoreColor }}">{{ round($cycleScore, 1) }}%</div>
                                        <div class="text-xs text-[var(--nx-muted)]">Performance</div>
                                    </div>
                                    <div class="w-20">
                                        <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $cycleProgressColor }}" style="width: {{ $cycleScore }}%"></div>
                                        </div>
                                    </div>
                                    
                                    <x-nx-badge variant="neutral">{{ ucfirst($cycle->status) }}</x-nx-badge>
                                    <x-nx-button 
                                        size="sm" 
                                        variant="primary" 
                                        :href="route('okr.cycles.show', ['cycle' => $cycle->id])" 
                                        wire:navigate
                                    >
                                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                        Öffnen
                                    </x-nx-button>
                                </div>
                            </div>

                            {{-- Objectives Summary - Vereinfacht --}}
                            @if($cycle->objectives->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($cycle->objectives->take(6) as $objective)
                                        @php
                                            $objectivePerformance = $objective->performance;
                                            $objectiveScore = $objectivePerformance ? $objectivePerformance->performance_score : 0;
                                            $objectiveScoreColor = $objectiveScore >= 80 ? 'text-[color:var(--nx-success)]' : ($objectiveScore >= 60 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]');
                                        @endphp
                                        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-4 hover:border-[color:var(--nx-line)] transition-colors">
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="text-sm font-medium text-[var(--nx-text)] truncate">{{ $objective->title }}</h5>
                                                <x-nx-badge variant="neutral">{{ $objective->keyResults->count() }} KR</x-nx-badge>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-bold {{ $objectiveScoreColor }}">{{ round($objectiveScore, 1) }}%</div>
                                                <div class="flex-1 bg-[color:var(--nx-line)] rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full {{ $objectiveScore >= 80 ? 'bg-[color:var(--nx-success)]' : ($objectiveScore >= 60 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]') }}" style="width: {{ $objectiveScore }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                @if($cycle->objectives->count() > 6)
                                    <div class="text-center mt-4">
                                        <span class="text-sm text-[var(--nx-muted)]">+{{ $cycle->objectives->count() - 6 }} weitere Objectives</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-[var(--nx-muted)]">
                        @svg('heroicon-o-calendar')
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-[var(--nx-text)]">Kein aktiver Zyklus</h3>
                    <p class="mt-1 text-sm text-[var(--nx-muted)]">Es ist aktuell kein Zielsteuerung-Zyklus aktiv.</p>
                </div>
            @endif
        </x-nx-card>
        </x-nx-section>
    </x-ui-page-container>

    {{-- Left Sidebar - Dashboard Übersicht --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Dashboard Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Performance Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Performance</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[var(--nx-text)]">Team Performance</span>
                                <span class="text-sm font-bold {{ ($averageScore ?? 0) >= 80 ? 'text-[color:var(--nx-success)]' : (($averageScore ?? 0) >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                    {{ round($averageScore ?? 0, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2 mb-2">
                                <div class="h-2 rounded-full {{ ($averageScore ?? 0) >= 80 ? 'bg-[color:var(--nx-success)]' : (($averageScore ?? 0) >= 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]') }}" 
                                     style="width: {{ $averageScore ?? 0 }}%"></div>
                            </div>
                            <div class="text-xs text-[var(--nx-muted)]">
                                Durchschnitt aller aktiven Zielsteuerungen
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $activeOkrsCount ?? 0 }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Aktive Zielsteuerungen</div>
                            </div>
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $activeCyclesCount ?? 0 }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Aktive Zyklen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ziele Übersicht --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Ziele</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Erreichte Ziele</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $achievedObjectivesCount ?? 0 }}/{{ $activeObjectivesCount ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Erreichte Erfolgskriterien</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $achievedKeyResultsCount ?? 0 }}/{{ $activeKeyResultsCount ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Offene Erfolgskriterien</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $openKeyResultsCount ?? 0 }}</span>
                        </div>
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
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-3 text-sm">
                        <div class="text-[var(--nx-muted)]">Keine Aktivitäten verfügbar</div>
                    </div>
                </div>

                {{-- Performance Übersicht --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Performance</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[var(--nx-accent)]">{{ round($averageScore ?? 0, 1) }}%</div>
                            <div class="text-xs text-[var(--nx-muted)]">Durchschnitt Score</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[color:var(--nx-success)]">{{ $achievedObjectivesCount ?? 0 }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Erreichte Ziele</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[color:var(--nx-info)]">{{ $achievedKeyResultsCount ?? 0 }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Erreichte KR</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>