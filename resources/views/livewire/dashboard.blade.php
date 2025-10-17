<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKR Dashboard" />
    </x-slot>

    <x-ui-page-container>
        


        {{-- Performance Stats Grid - Wichtigste Metriken zuerst --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
             <x-ui-dashboard-tile
                 title="Durchschnitt Score"
                 :count="round($averageScore ?? 0, 1)"
                 subtitle="Team Performance"
                 icon="chart-bar"
                 variant="primary"
                 size="lg"
             />
            <x-ui-dashboard-tile
                title="Erfolgreiche OKRs"
                :count="$successfulOkrsCount"
                subtitle="≥80% Score"
                icon="check-circle"
                variant="success"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Aktive OKRs"
                :count="$activeOkrsCount"
                subtitle="von {{ $totalOkrsCount ?? $activeOkrsCount }}"
                icon="flag"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Erreichte Ziele"
                :count="$achievedObjectivesCount"
                subtitle="von {{ $activeObjectivesCount }}"
                icon="light-bulb"
                variant="info"
                size="lg"
            />
        </div>

        {{-- Secondary Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-ui-dashboard-tile
                title="Aktive Zyklen"
                :count="$activeCyclesCount"
                subtitle="laufende Zeiträume"
                icon="calendar"
                variant="neutral"
                size="md"
            />
            <x-ui-dashboard-tile
                title="Key Results"
                :count="$activeKeyResultsCount"
                subtitle="messbare Ergebnisse"
                icon="chart-bar"
                variant="neutral"
                size="md"
            />
            <x-ui-dashboard-tile
                title="Offene KR"
                :count="$openKeyResultsCount"
                subtitle="noch zu erreichen"
                icon="clock"
                variant="warning"
                size="md"
            />
            <x-ui-dashboard-tile
                title="Erreichte KR"
                :count="$achievedKeyResultsCount"
                subtitle="bereits erreicht"
                icon="check-circle"
                variant="success"
                size="md"
            />
        </div>

        {{-- OKR Status Übersicht --}}
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">OKR Status</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-ui-dashboard-tile title="Entwürfe" :count="$draftOkrsCount" icon="document-text" variant="neutral" size="sm" />
                <x-ui-dashboard-tile title="Aktiv" :count="$activeOkrsCount" icon="play" variant="success" size="sm" />
                <x-ui-dashboard-tile title="Abgeschlossen" :count="$completedOkrsCount" icon="check-circle" variant="success" size="sm" />
                <x-ui-dashboard-tile title="Endet bald" :count="$endingSoonOkrsCount" icon="exclamation-triangle" variant="warning" size="sm" />
            </div>
        </div>

        <!-- Filterleiste -->
        <div class="mb-4 flex items-end gap-3">
            <div class="min-w-48">
                <x-ui-input-select
                    name="statusFilter"
                    label="Status"
                    :options="[
                        'all' => 'Alle',
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'completed' => 'Abgeschlossen',
                        'ending_soon' => 'Endet bald',
                        'past' => 'Vergangen'
                    ]"
                    :nullable="false"
                    wire:model.live="statusFilter"
                />
            </div>
            <div class="min-w-64">
                <x-ui-input-select
                    name="managerFilter"
                    label="Manager"
                    :options="$managers"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    wire:model.live="managerFilter"
                />
            </div>
        </div>

        <x-ui-panel title="Aktive Zyklen" subtitle="Laufende OKR-Zyklen mit Performance-Übersicht">
            @if($activeCycles && $activeCycles->count() > 0)
                <div class="space-y-6">
                    @foreach($activeCycles as $cycle)
                        @php
                            $cyclePerformance = $cycle->performance;
                            $cycleScore = $cyclePerformance ? $cyclePerformance->performance_score : 0;
                            $cycleScoreColor = $cycleScore >= 80 ? 'text-green-600' : ($cycleScore >= 60 ? 'text-yellow-600' : 'text-red-600');
                            $cycleProgressColor = $cycleScore >= 80 ? 'bg-green-500' : ($cycleScore >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                        @endphp
                        <div class="bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40 p-6 hover:border-[var(--ui-border)]/60 transition-colors">
                            {{-- Cycle Header mit Performance --}}
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                                            @svg('heroicon-o-calendar', 'w-5 h-5')
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $cycle->okr?->title ?? 'OKR' }}</h3>
                                            <div class="text-sm text-[var(--ui-muted)]">{{ $cycle->template?->label }} • {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}</div>
                                        </div>
                                    </div>
                                    
                                    {{-- Performance Score --}}
                                    <div class="flex items-center gap-4">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold {{ $cycleScoreColor }}">{{ round($cycleScore, 1) }}%</div>
                                            <div class="text-xs text-[var(--ui-muted)]">Cycle Performance</div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                                <div class="h-2 rounded-full {{ $cycleProgressColor }}" style="width: {{ $cycleScore }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
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

                            @if($cycle->objectives->count() > 0)
                                <div class="space-y-3 mt-4">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-2">Objectives</h4>
                                    @foreach($cycle->objectives as $objective)
                                        @php
                                            $objectivePerformance = $objective->performance;
                                            $objectiveScore = $objectivePerformance ? $objectivePerformance->performance_score : 0;
                                            $objectiveScoreColor = $objectiveScore >= 80 ? 'text-green-600' : ($objectiveScore >= 60 ? 'text-yellow-600' : 'text-red-600');
                                            $objectiveProgressColor = $objectiveScore >= 80 ? 'bg-green-500' : ($objectiveScore >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                                        @endphp
                                        <div class="bg-white rounded-lg border border-[var(--ui-border)]/40 p-4 hover:border-[var(--ui-border)]/60 transition-colors">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex-1">
                                                    <h5 class="text-sm font-medium text-[var(--ui-secondary)] mb-1">{{ $objective->title }}</h5>
                                                    <div class="flex items-center gap-4">
                                                        <div class="text-center">
                                                            <div class="text-lg font-bold {{ $objectiveScoreColor }}">{{ round($objectiveScore, 1) }}%</div>
                                                            <div class="text-xs text-[var(--ui-muted)]">Objective Performance</div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-1.5">
                                                                <div class="h-1.5 rounded-full {{ $objectiveProgressColor }}" style="width: {{ $objectiveScore }}%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <x-ui-badge variant="secondary" size="sm">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                            </div>
                                            @if($objective->keyResults->count() > 0)
                                                <div class="space-y-2 mt-3">
                                                    <h6 class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wide">Key Results</h6>
                                                    @foreach($objective->keyResults as $kr)
                                                        @php
                                                            $type = $kr->performance?->type;
                                                            $isCompleted = $kr->performance?->is_completed ?? false;
                                                            $currentValue = $kr->performance?->current_value ?? 0;
                                                            $targetValue = $kr->performance?->target_value ?? 0;
                                                            $progress = $type === 'boolean' ? ($isCompleted ? 100 : 0) : ($targetValue > 0 ? min(100, round(($currentValue / $targetValue) * 100)) : 0);
                                                            $progressColor = $progress >= 80 ? 'bg-green-500' : ($progress >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                                                        @endphp
                                                        <div class="flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $kr->title }}</div>
                                                                <div class="flex items-center gap-2 mt-1">
                                                                    <x-ui-badge variant="secondary" size="xs">{{ $type ? ucfirst($type) : 'Typ' }}</x-ui-badge>
                                                                    @if($type === 'boolean')
                                                                        <x-ui-badge variant="{{ $isCompleted ? 'success' : 'warning' }}" size="xs">
                                                                            {{ $isCompleted ? 'Erledigt' : 'Offen' }}
                                                                        </x-ui-badge>
                                                                    @else
                                                                        <x-ui-badge variant="secondary" size="xs">
                                                                            {{ $currentValue }}{{ $type === 'percentage' ? '%' : '' }} / {{ $targetValue }}{{ $type === 'percentage' ? '%' : '' }}
                                                                        </x-ui-badge>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                                <div class="text-right">
                                                                    <div class="text-sm font-bold {{ $progress >= 80 ? 'text-green-600' : ($progress >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                                        {{ $progress }}%
                                                                    </div>
                                                                </div>
                                                                <div class="w-16 bg-[var(--ui-border)]/40 rounded-full h-1.5">
                                                                    <div class="h-1.5 rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
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

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Schnellzugriff</h3>
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

                {{-- Aktuelle Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $activeOkrsCount }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Aktive OKRs</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-green-600">{{ $activeCyclesCount }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Aktive Zyklen</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-blue-600">{{ $activeObjectivesCount }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Objectives</div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Filter</h3>
                    <div class="space-y-3">
                        <div>
                            <x-ui-input-select
                                name="statusFilter"
                                label="Status"
                                :options="[
                                    'all' => 'Alle',
                                    'draft' => 'Entwurf',
                                    'active' => 'Aktiv',
                                    'completed' => 'Abgeschlossen'
                                ]"
                                :nullable="false"
                                wire:model.live="statusFilter"
                                size="sm"
                            />
                        </div>
                        <div>
                            <x-ui-input-select
                                name="managerFilter"
                                label="Manager"
                                :options="$managers"
                                optionValue="id"
                                optionLabel="name"
                                :nullable="true"
                                nullLabel="– Alle –"
                                wire:model.live="managerFilter"
                                size="sm"
                            />
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