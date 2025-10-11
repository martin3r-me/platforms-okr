<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKRs" icon="heroicon-o-flag">
            <x-ui-button variant="secondary" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span class="ml-2">Neues OKR</span>
            </x-ui-button>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="content">

        <div class="mb-4">
            <x-ui-input-text 
                name="search" 
                placeholder="Suche OKRs..." 
                class="w-64"
            />
        </div>
    
    <x-ui-table compact="true">
        <x-ui-table-header>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="title" :currentSort="$sortField" :sortDirection="$sortDirection">Titel</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Verantwortlicher</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Manager</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="performance_score" :currentSort="$sortField" :sortDirection="$sortDirection">Score</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Cycles</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
        </x-ui-table-header>
        
        <x-ui-table-body>
            @foreach($okrs as $okr)
                <x-ui-table-row 
                    compact="true"
                    clickable="true" 
                    :href="route('okr.okrs.show', ['okr' => $okr->id])"
                >
                    <x-ui-table-cell compact="true">
                        <div class="font-medium text-[var(--ui-secondary)]">{{ $okr->title }}</div>
                        @if($okr->is_template)
                            <x-ui-badge variant="secondary" size="xs">Template</x-ui-badge>
                        @endif
                        @if($okr->auto_transfer)
                            <x-ui-badge variant="secondary" size="xs">Auto-Transfer</x-ui-badge>
                        @endif
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-[var(--ui-muted)]">{{ Str::limit($okr->description, 50) }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-[var(--ui-muted)]">{{ $okr->user?->name ?? 'Unbekannt' }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-[var(--ui-muted)]">{{ $okr->manager?->name ?? '–' }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        @php
                            $okrPerformance = $okr->performance;
                            $totalCycles = $okr->cycles->count();
                            $totalObjectives = $okr->cycles->sum(fn($cycle) => $cycle->objectives->count());
                            $totalKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()));
                            $completedKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count()));
                        @endphp
                        
                        @if($okrPerformance)
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <x-ui-badge variant="{{ $okrPerformance->performance_score >= 80 ? 'success' : ($okrPerformance->performance_score >= 50 ? 'warning' : 'secondary') }}" size="sm">
                                        {{ $okrPerformance->performance_score }}%
                                    </x-ui-badge>
                                </div>
                                <div class="text-xs text-[var(--ui-muted)]">
                                    {{ $completedKeyResults }}/{{ $totalKeyResults }} KR
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-[var(--ui-muted)]">–</span>
                        @endif
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="space-y-1">
                            <div class="text-xs text-[var(--ui-muted)]">{{ $totalCycles }} Cycles</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $totalObjectives }} Objectives</div>
                        </div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true" align="right">
                        <x-ui-button 
                            size="sm" 
                            variant="secondary" 
                            href="{{ route('okr.okrs.show', ['okr' => $okr->id]) }}" 
                            wire:navigate
                        >
                            Bearbeiten
                        </x-ui-button>
                    </x-ui-table-cell>
                </x-ui-table-row>
            @endforeach
        </x-ui-table-body>
    </x-ui-table>

    {{ $okrs->links() }}

    <!-- Create OKR Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
    >
        <x-slot name="header">
            OKR anlegen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createOkr" class="space-y-4">
                <x-ui-input-text
                    name="title"
                    label="Titel"
                    wire:model.live="title"
                    required
                    placeholder="Titel des OKR eingeben"
                />

                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    wire:model.live="description"
                    placeholder="Detaillierte Beschreibung des OKR (optional)"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-number
                        name="performance_score"
                        label="Performance Score (%)"
                        wire:model.live="performance_score"
                        min="0"
                        max="100"
                        required
                    />

                    <x-ui-input-select
                        name="manager_user_id"
                        label="Verantwortlicher Manager"
                        :options="$users"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Manager auswählen –"
                        wire:model.live="manager_user_id"
                    />
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="auto_transfer"
                            wire:model.live="auto_transfer"
                            class="w-4 h-4 text-[var(--ui-primary)] bg-[var(--ui-muted-5)] border-[var(--ui-border)] rounded focus:ring-[var(--ui-primary)] focus:ring-2"
                        >
                        <label for="auto_transfer" class="text-sm font-medium text-[var(--ui-secondary)]">
                            Automatisch übertragen
                        </label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="is_template"
                            wire:model.live="is_template"
                            class="w-4 h-4 text-[var(--ui-primary)] bg-[var(--ui-muted-5)] border-[var(--ui-border)] rounded focus:ring-[var(--ui-primary)] focus:ring-2"
                        >
                        <label for="is_template" class="text-sm font-medium text-[var(--ui-secondary)]">
                            Als Template speichern
                        </label>
                    </div>
                </div>
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
                <x-ui-button type="button" variant="secondary" wire:click="createOkr">
                    OKR anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
    </x-slot>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="OKR Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span class="ml-2">Neues OKR</span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" :href="route('okr.dashboard')" wire:navigate class="w-full justify-start">
                            @svg('heroicon-o-chart-bar', 'w-4 h-4')
                            <span class="ml-2">Dashboard</span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $okrs->total() }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Gesamt OKRs</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-green-600">{{ $okrs->where('status', 'active')->count() }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Aktiv</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-blue-600">{{ $okrs->where('is_template', true)->count() }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Templates</div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Filter</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs font-medium text-[var(--ui-secondary)] mb-1 block">Status</label>
                            <select class="w-full text-xs px-2 py-1 border border-[var(--ui-border)] rounded">
                                <option value="all">Alle</option>
                                <option value="draft">Entwurf</option>
                                <option value="active">Aktiv</option>
                                <option value="completed">Abgeschlossen</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-[var(--ui-secondary)] mb-1 block">Manager</label>
                            <select class="w-full text-xs px-2 py-1 border border-[var(--ui-border)] rounded">
                                <option value="">– Alle –</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
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
                            <div class="text-lg font-bold text-[var(--ui-primary)]">{{ round($okrs->avg('performance_score') ?? 0, 1) }}%</div>
                            <div class="text-xs text-[var(--ui-muted)]">Durchschnitt Score</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-green-600">{{ $okrs->where('performance_score', '>=', 80)->count() }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Erfolgreich (≥80%)</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-blue-600">{{ $okrs->where('auto_transfer', true)->count() }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Auto-Transfer</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>