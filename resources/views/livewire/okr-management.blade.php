<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="OKRs" />
    </x-slot>

    <x-ui-page-container>
        {{-- Header mit Suche und Aktionen --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">OKR-Verwaltung</h2>
                    <p class="text-sm text-[var(--ui-muted)] mt-1">Verwalte deine Objectives and Key Results</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui-input-text 
                        name="search" 
                        placeholder="OKRs durchsuchen..." 
                        class="w-80"
                        size="sm"
                    />
                </div>
            </div>
        </div>

        {{-- Statistiken --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 border border-[var(--ui-border)]/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[var(--ui-primary)]/10 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-5 h-5 text-[var(--ui-primary)]')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $totalOkrs }}</div>
                        <div class="text-xs text-[var(--ui-muted)]">Gesamt OKRs</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 border border-[var(--ui-border)]/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-play', 'w-5 h-5 text-green-600')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $activeOkrs }}</div>
                        <div class="text-xs text-[var(--ui-muted)]">Aktiv</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 border border-[var(--ui-border)]/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-5 h-5 text-blue-600')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $templateOkrs }}</div>
                        <div class="text-xs text-[var(--ui-muted)]">Templates</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 border border-[var(--ui-border)]/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-chart-bar', 'w-5 h-5 text-purple-600')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ round($averageScore, 1) }}%</div>
                        <div class="text-xs text-[var(--ui-muted)]">Ø Score</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabelle --}}
        <x-ui-panel title="OKR-Übersicht" subtitle="Alle Objectives and Key Results im Überblick">
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
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-[var(--ui-primary)]/10 rounded-lg flex items-center justify-center">
                                        @svg('heroicon-o-flag', 'w-4 h-4 text-[var(--ui-primary)]')
                                    </div>
                                    <div>
                                        <div class="font-medium text-[var(--ui-secondary)]">{{ $okr->title }}</div>
                                        <div class="flex items-center gap-1 mt-1">
                                            @if($okr->is_template)
                                                <x-ui-badge variant="secondary" size="xs">Template</x-ui-badge>
                                            @endif
                                            @if($okr->auto_transfer)
                                                <x-ui-badge variant="info" size="xs">Auto-Transfer</x-ui-badge>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="text-sm text-[var(--ui-muted)] max-w-xs truncate">{{ Str::limit($okr->description, 60) }}</div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ substr($okr->user?->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div class="text-sm text-[var(--ui-secondary)]">{{ $okr->user?->name ?? 'Unbekannt' }}</div>
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($okr->manager)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ substr($okr->manager->name, 0, 1) }}</span>
                                        </div>
                                        <div class="text-sm text-[var(--ui-secondary)]">{{ $okr->manager->name }}</div>
                                    </div>
                                @else
                                    <span class="text-sm text-[var(--ui-muted)]">–</span>
                                @endif
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
                                    <div class="space-y-2">
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
                                    <span class="text-sm text-[var(--ui-muted)]">–</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-calendar', 'w-3 h-3 text-[var(--ui-muted)]')
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $totalCycles }} Cycles</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-flag', 'w-3 h-3 text-[var(--ui-muted)]')
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $totalObjectives }} Objectives</span>
                                    </div>
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true" align="right">
                                <x-ui-button 
                                    size="sm" 
                                    variant="secondary" 
                                    href="{{ route('okr.okrs.show', ['okr' => $okr->id]) }}" 
                                    wire:navigate
                                >
                                    @svg('heroicon-o-arrow-right', 'w-3 h-3')
                                    <span class="ml-1">Öffnen</span>
                                </x-ui-button>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>
        </x-ui-panel>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $okrs->links() }}
        </div>
    </x-ui-page-container>

    <!-- Create OKR Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
    >
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-plus', 'w-5 h-5')
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Neues OKR anlegen</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Erstelle ein neues Objectives and Key Results System</p>
                </div>
            </div>
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
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $totalOkrs }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Gesamt OKRs</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-green-600">{{ $activeOkrs }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Aktiv</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-blue-600">{{ $templateOkrs }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Templates</div>
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
                                size="sm"
                            />
                        </div>
                        <div>
                            <x-ui-input-select
                                name="managerFilter"
                                label="Manager"
                                :options="$users"
                                optionValue="id"
                                optionLabel="name"
                                :nullable="true"
                                nullLabel="– Alle –"
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
                            <div class="text-lg font-bold text-[var(--ui-primary)]">{{ round($averageScore, 1) }}%</div>
                            <div class="text-xs text-[var(--ui-muted)]">Durchschnitt Score</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-green-600">{{ $successfulOkrs }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Erfolgreich (≥80%)</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3">
                            <div class="text-lg font-bold text-blue-600">{{ $autoTransferOkrs }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Auto-Transfer</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>