<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKR Dashboard" icon="heroicon-o-chart-bar">
            <div class="flex items-center gap-4">
                <!-- Perspektive-Toggle -->
                <x-ui-segmented-toggle 
                    wire:model.live="perspective"
                    :options="[
                        'personal' => ['label' => 'Persönlich', 'icon' => 'heroicon-o-user'],
                        'team' => ['label' => 'Team', 'icon' => 'heroicon-o-users']
                    ]"
                    active-variant="secondary"
                />
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="content">

        <!-- Kennzahlen-Kacheln: aktive Zyklen -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <x-ui-dashboard-tile
                title="Aktive Zyklen"
                :count="$activeCyclesCount"
                icon="calendar"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Objectives (aktiv)"
                :count="$activeObjectivesCount"
                icon="light-bulb"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Key Results (aktiv)"
                :count="$activeKeyResultsCount"
                icon="chart-bar"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="OKRs (aktiv)"
                :count="$activeOkrsCount"
                icon="flag"
                variant="secondary"
                size="lg"
            />
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

        @if($activeCycles && $activeCycles->count() > 0)
            <div class="mt-2">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-3">Aktive Zyklen</h3>
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
    </x-slot>
</x-ui-page>