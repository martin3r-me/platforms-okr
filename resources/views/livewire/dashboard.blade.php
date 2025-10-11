<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKR Dashboard" icon="heroicon-o-chart-bar">
            <x-slot name="titleActions">
                <x-ui-segmented-toggle 
                    model="perspective"
                    :current="$perspective"
                    :options="[
                        ['value' => 'personal', 'label' => 'Persönlich', 'icon' => 'heroicon-o-user'],
                        ['value' => 'team', 'label' => 'Team', 'icon' => 'heroicon-o-users'],
                    ]"
                    active-variant="secondary"
                    size="sm"
                />
            </x-slot>
            <div class="text-sm text-[var(--ui-muted)]">{{ now()->translatedFormat('l') }}, {{ now()->format('d.m.Y') }}</div>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Info Banner --}}
        @if($perspective === 'personal')
            <x-ui-info-banner 
                icon="heroicon-o-user"
                title="Persönliche OKR-Übersicht"
                message="Deine persönlichen OKRs, Objectives und Key Results im aktuellen Zyklus."
                variant="secondary"
            />
        @else
            <x-ui-info-banner 
                icon="heroicon-o-users"
                title="Team OKR-Übersicht"
                message="Alle OKRs des Teams in aktiven Zyklen und deren Fortschritt."
                variant="secondary"
            />
        @endif

        {{-- Performance Highlight Banner --}}
        <div class="bg-gradient-to-r from-[var(--ui-primary)]/5 to-[var(--ui-secondary)]/5 border border-[var(--ui-primary)]/20 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--ui-secondary)] mb-2">Team Performance</h2>
                    <p class="text-[var(--ui-muted)]">Aktuelle Leistungsübersicht des Teams</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold text-[var(--ui-primary)]">{{ round($averageScore ?? 0, 1) }}%</div>
                    <div class="text-sm text-[var(--ui-muted)]">Durchschnitt Score</div>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $successfulOkrsCount ?? 0 }}</div>
                    <div class="text-xs text-[var(--ui-muted)]">Erfolgreiche OKRs (≥80%)</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $achievedObjectivesCount ?? 0 }}</div>
                    <div class="text-xs text-[var(--ui-muted)]">Erreichte Objectives</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $achievedKeyResultsCount ?? 0 }}</div>
                    <div class="text-xs text-[var(--ui-muted)]">Erreichte Key Results</div>
                </div>
            </div>
        </div>

        {{-- Performance Stats Grid - Wichtigste Metriken zuerst --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <x-ui-dashboard-tile
                title="Durchschnitt Score"
                :count="round($averageScore ?? 0, 1) . '%'"
                subtitle="Team Performance"
                icon="chart-bar"
                variant="primary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Erfolgreiche OKRs"
                :count="$successfulOkrsCount ?? 0"
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
                :count="$achievedObjectivesCount ?? 0"
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
                :count="$openKeyResultsCount ?? 0"
                subtitle="noch zu erreichen"
                icon="clock"
                variant="warning"
                size="md"
            />
            <x-ui-dashboard-tile
                title="Erreichte KR"
                :count="$achievedKeyResultsCount ?? 0"
                subtitle="bereits erreicht"
                icon="check-circle"
                variant="success"
                size="md"
            />
        </div>

        {{-- Detail Stats --}}
        <x-ui-detail-stats-grid cols="2" gap="6">
            <x-slot:left>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">OKR-Übersicht</h3>
                <x-ui-form-grid :cols="2" :gap="3">
                    <x-ui-dashboard-tile title="Entwürfe" :count="$draftOkrsCount ?? 0" icon="document-text" variant="neutral" size="sm" />
                    <x-ui-dashboard-tile title="Aktiv" :count="$activeOkrsCount" icon="play" variant="success" size="sm" />
                    <x-ui-dashboard-tile title="Abgeschlossen" :count="$completedOkrsCount ?? 0" icon="check-circle" variant="success" size="sm" />
                    <x-ui-dashboard-tile title="Endet bald" :count="$endingSoonOkrsCount ?? 0" icon="exclamation-triangle" variant="warning" size="sm" />
                </x-ui-form-grid>
            </x-slot:left>
            <x-slot:right>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Performance-Übersicht</h3>
                <x-ui-form-grid :cols="2" :gap="3">
                    <x-ui-dashboard-tile title="Durchschnitt Score" :count="round($averageScore ?? 0, 1)" icon="chart-bar" variant="info" size="sm" />
                    <x-ui-dashboard-tile title="Erreichte Ziele" :count="$achievedObjectivesCount ?? 0" icon="check-circle" variant="success" size="sm" />
                    <x-ui-dashboard-tile title="Offene KR" :count="$openKeyResultsCount ?? 0" icon="clock" variant="warning" size="sm" />
                    <x-ui-dashboard-tile title="Erreichte KR" :count="$achievedKeyResultsCount ?? 0" icon="check-circle" variant="success" size="sm" />
                </x-ui-form-grid>
            </x-slot:right>
        </x-ui-detail-stats-grid>

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

        <x-ui-panel title="Aktive Zyklen" subtitle="Laufende OKR-Zyklen mit Objectives und Key Results">
            @if($activeCycles && $activeCycles->count() > 0)
                <div class="space-y-4">
                    @foreach($activeCycles as $cycle)
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="font-medium text-[var(--ui-secondary)]">{{ $cycle->okr?->title ?? 'OKR' }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">{{ $cycle->template?->label }} • {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui-badge variant="secondary" size="xs">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                    <x-ui-button 
                                        size="sm" 
                                        variant="secondary" 
                                        :href="route('okr.cycles.show', ['cycle' => $cycle->id])" 
                                        wire:navigate
                                    >
                                        Öffnen
                                    </x-ui-button>
                                </div>
                            </div>

                            @if($cycle->objectives->count() > 0)
                                <div class="space-y-2 mt-2">
                                    @foreach($cycle->objectives as $objective)
                                        <div class="p-2 rounded bg-[var(--ui-muted-4)] border border-[var(--ui-border)]/40">
                                            <div class="flex items-center justify-between">
                                                <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $objective->title }}</div>
                                                <x-ui-badge variant="secondary" size="xs">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                            </div>
                                            @if($objective->keyResults->count() > 0)
                                                <div class="space-y-1 mt-2">
                                                    @foreach($objective->keyResults as $kr)
                                                        @php
                                                            $type = $kr->performance?->type;
                                                        @endphp
                                                        <div class="flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded border border-[var(--ui-border)]/40 text-xs">
                                                            <div class="truncate pr-2 text-[var(--ui-secondary)]">{{ $kr->title }}</div>
                                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                                <x-ui-badge variant="secondary" size="xs">{{ $type ? ucfirst($type) : 'Typ' }}</x-ui-badge>
                                                                <x-ui-badge variant="secondary" size="xs">Ziel: {{ $kr->performance?->target_value ?? '–' }}@if($type === 'percentage') % @endif</x-ui-badge>
                                                                <x-ui-badge variant="secondary" size="xs">
                                                                    @if($type === 'boolean')
                                                                        {{ $kr->performance?->is_completed ? 'Erledigt' : 'Offen' }}
                                                                    @else
                                                                        Aktuell: {{ $kr->performance?->current_value ?? '–' }}@if($type === 'percentage') % @endif
                                                                    @endif
                                                                </x-ui-badge>
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