<div class="h-full overflow-y-auto p-6">
    <!-- Header mit Datum und Perspektive-Toggle -->
    <div class="mb-6">
        <div class="d-flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">OKR Dashboard</h1>
                <p class="text-gray-600">{{ now()->translatedFormat('l') }}, {{ now()->format('d.m.Y') }}</p>
            </div>
            <div class="d-flex items-center gap-4">
                <!-- Perspektive-Toggle -->
                <div class="d-flex bg-gray-100 rounded-lg p-1">
                    <button 
                        wire:click="$set('perspective', 'personal')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'personal' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-4 h-4')
                            <span>Persönlich</span>
                        </div>
                    </button>
                    <button 
                        wire:click="$set('perspective', 'team')"
                        class="px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'team' 
                            ? 'bg-success text-on-success shadow-sm' 
                            : 'text-gray-600 hover:text-gray-900'"
                    >
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4')
                            <span>Team</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kennzahlen-Kacheln: aktive Zyklen -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <x-ui-dashboard-tile
            title="Aktive Zyklen"
            :count="$activeCyclesCount"
            icon="calendar"
            variant="primary"
            size="lg"
        />
        <x-ui-dashboard-tile
            title="Objectives (aktiv)"
            :count="$activeObjectivesCount"
            icon="light-bulb"
            variant="info"
            size="lg"
        />
        <x-ui-dashboard-tile
            title="Key Results (aktiv)"
            :count="$activeKeyResultsCount"
            icon="chart-bar"
            variant="success"
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
    <div class="mb-4 d-flex items-end gap-3">
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
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Aktive Zyklen</h3>
            <div class="space-y-4">
                @foreach($activeCycles as $cycle)
                    <div class="bg-white rounded-lg border p-4">
                        <div class="d-flex items-center justify-between mb-2">
                            <div>
                                <div class="font-medium">{{ $cycle->okr?->title ?? 'OKR' }}</div>
                                <div class="text-xs text-muted">{{ $cycle->template?->label }} • {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}</div>
                            </div>
                            <div class="d-flex items-center gap-2">
                                <x-ui-badge variant="info" size="xs">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                <x-ui-button 
                                    size="sm" 
                                    variant="primary" 
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
                                    <div class="p-2 rounded bg-muted-5 border">
                                        <div class="d-flex items-center justify-between">
                                            <div class="text-sm font-medium">{{ $objective->title }}</div>
                                            <x-ui-badge variant="secondary" size="xs">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                        </div>
                                        @if($objective->keyResults->count() > 0)
                                            <div class="space-y-1 mt-2">
                                                @foreach($objective->keyResults as $kr)
                                                    @php
                                                        $type = $kr->performance?->type;
                                                        $typeVariant = $type === 'boolean' ? 'purple' : ($type === 'percentage' ? 'info' : 'success');
                                                    @endphp
                                                    <div class="d-flex items-center justify-between p-2 bg-white rounded border text-xs">
                                                        <div class="truncate pr-2">{{ $kr->title }}</div>
                                                        <div class="d-flex items-center gap-2 flex-shrink-0">
                                                            <x-ui-badge :variant="$type ? $typeVariant : 'secondary'" size="xs">{{ $type ? ucfirst($type) : 'Typ' }}</x-ui-badge>
                                                            <x-ui-badge variant="secondary" size="xs">Ziel: {{ $kr->performance?->target_value ?? '–' }}@if($type === 'percentage') % @endif</x-ui-badge>
                                                            <x-ui-badge :variant="$type === 'boolean' ? ($kr->performance?->is_completed ? 'success' : 'danger') : 'secondary'" size="xs">
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
            <div class="mx-auto h-12 w-12 text-gray-400">
                @svg('heroicon-o-calendar')
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Kein aktiver Zyklus</h3>
            <p class="mt-1 text-sm text-gray-500">Es ist aktuell kein OKR-Zyklus aktiv.</p>
        </div>
    @endif
</div>