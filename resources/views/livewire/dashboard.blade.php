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

    {{-- Left Sidebar - Leer für später --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Sidebar" width="w-80" :defaultOpen="false">
            <div class="p-6">
                {{-- Leer für später --}}
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar - Leer für später --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                {{-- Leer für später --}}
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>