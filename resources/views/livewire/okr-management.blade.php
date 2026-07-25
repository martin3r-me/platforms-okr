<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zielsteuerungen'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Zielsteuerung</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Header mit Suche und Aktionen --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-[var(--nx-text)]">Zielsteuerung</h2>
                    <p class="text-sm text-[var(--nx-muted)] mt-1">Verwalte deine Ziele & Erfolgskriterien</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-nx-input-text
                        name="search"
                        placeholder="Zielsteuerungen durchsuchen..." 
                        class="w-80"
                        size="sm"
                    />
                </div>
            </div>
        </div>

        {{-- Statistiken --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-[var(--nx-bg)] rounded-lg p-4 border border-[color:var(--nx-line)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[var(--nx-accent)]/10 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-5 h-5 text-[var(--nx-accent)]')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--nx-text)]">{{ $totalOkrs }}</div>
                        <div class="text-xs text-[var(--nx-muted)]">Gesamt Zielsteuerungen</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--nx-bg)] rounded-lg p-4 border border-[color:var(--nx-line)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[var(--nx-success)]/10 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-play', 'w-5 h-5 text-[color:var(--nx-success)]')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--nx-text)]">{{ $activeOkrs }}</div>
                        <div class="text-xs text-[var(--nx-muted)]">Aktiv</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--nx-bg)] rounded-lg p-4 border border-[color:var(--nx-line)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[var(--nx-info)]/10 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-5 h-5 text-[color:var(--nx-info)]')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--nx-text)]">{{ $templateOkrs }}</div>
                        <div class="text-xs text-[var(--nx-muted)]">Templates</div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--nx-bg)] rounded-lg p-4 border border-[color:var(--nx-line)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[var(--nx-tone-violet)]/10 rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-chart-bar', 'w-5 h-5 text-[color:var(--nx-tone-violet)]')
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-[var(--nx-text)]">{{ round($averageScore, 1) }}%</div>
                        <div class="text-xs text-[var(--nx-muted)]">Ø Score</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabelle --}}
        <x-nx-section title="Zielsteuerung-Übersicht" hint="Alle Ziele & Erfolgskriterien im Überblick">
        <x-nx-card flush>
                <x-nx-table compact="true">
                <x-nx-table-header>
                    <x-nx-table-header-cell compact="true" sortable="true" sortField="title" :currentSort="$sortField" :sortDirection="$sortDirection">Titel</x-nx-table-header-cell>
                    <x-nx-table-header-cell compact="true">Beschreibung</x-nx-table-header-cell>
                    <x-nx-table-header-cell compact="true">Verantwortlicher</x-nx-table-header-cell>
                    <x-nx-table-header-cell compact="true">Manager</x-nx-table-header-cell>
                    <x-nx-table-header-cell compact="true" sortable="true" sortField="performance_score" :currentSort="$sortField" :sortDirection="$sortDirection">Score</x-nx-table-header-cell>
                    <x-nx-table-header-cell compact="true">Cycles</x-nx-table-header-cell>
                </x-nx-table-header>
                
                <x-nx-table-body>
                    @foreach($okrs as $okr)
                        <x-nx-table-row 
                            compact="true"
                            clickable="true" 
                            :href="route('okr.okrs.show', ['okr' => $okr->id])"
                        >
                            <x-nx-table-cell compact="true">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-[var(--nx-accent)]/10 rounded-lg flex items-center justify-center">
                                        @svg('heroicon-o-flag', 'w-4 h-4 text-[var(--nx-accent)]')
                                    </div>
                                    <div>
                                        <div class="font-medium text-[var(--nx-text)]">{{ $okr->title }}</div>
                                        <div class="flex items-center gap-1 mt-1">
                                            @if($okr->is_template)
                                                <x-nx-badge variant="neutral" size="xs">Template</x-nx-badge>
                                            @endif
                                            @if($okr->auto_transfer)
                                                <x-nx-badge variant="info" size="xs">Auto-Transfer</x-nx-badge>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </x-nx-table-cell>
                            <x-nx-table-cell compact="true">
                                <div class="text-sm text-[var(--nx-muted)] max-w-xs truncate">{{ Str::limit($okr->description, 60) }}</div>
                            </x-nx-table-cell>
                            <x-nx-table-cell compact="true">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-[var(--nx-bg)] rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium text-[var(--nx-text)]">{{ substr($okr->user?->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div class="text-sm text-[var(--nx-text)]">{{ $okr->user?->name ?? 'Unbekannt' }}</div>
                                </div>
                            </x-nx-table-cell>
                            <x-nx-table-cell compact="true">
                                @if($okr->manager)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-[var(--nx-bg)] rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-[var(--nx-text)]">{{ substr($okr->manager->name, 0, 1) }}</span>
                                        </div>
                                        <div class="text-sm text-[var(--nx-text)]">{{ $okr->manager->name }}</div>
                                    </div>
                                @else
                                    <span class="text-sm text-[var(--nx-muted)]">–</span>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell compact="true">
                                @php
                                    $okrPerformance = $okr->performance;
                                    $totalCycles = $okr->cycles->count();
                                    $totalObjectives = $okr->cycles->sum(fn($cycle) => $cycle->objectives->count());
                                    $totalKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()));
                                    $completedKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count()));
                                @endphp
                                
                                @if($okrPerformance)
                                    <div class="flex items-center gap-2">
                                        <x-nx-badge variant="{{ $okrPerformance->performance_score >= 80 ? 'success' : ($okrPerformance->performance_score >= 50 ? 'warning' : 'secondary') }}" size="sm">
                                            {{ $okrPerformance->performance_score }}%
                                        </x-nx-badge>
                                    </div>
                                @else
                                    <span class="text-sm text-[var(--nx-muted)]">–</span>
                                @endif
                            </x-nx-table-cell>
                            <x-nx-table-cell compact="true">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-calendar', 'w-3 h-3 text-[var(--nx-muted)]')
                                        <span class="text-xs text-[var(--nx-muted)]">{{ $totalCycles }} Cycles</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-flag', 'w-3 h-3 text-[var(--nx-muted)]')
                                        <span class="text-xs text-[var(--nx-muted)]">{{ $totalObjectives }} Objectives</span>
                                    </div>
                                </div>
                            </x-nx-table-cell>
                            
                        </x-nx-table-row>
                    @endforeach
                </x-nx-table-body>
            </x-nx-table>
        </x-nx-card>
        </x-nx-section>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $okrs->links() }}
        </div>
    </x-ui-page-container>

    <!-- Create OKR Modal -->
    <x-nx-modal
        wire:model="modalShow"
        size="lg"
    >
        <x-slot name="header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-plus', 'w-5 h-5')
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-[var(--nx-text)]">Neue Zielsteuerung anlegen</h3>
                    <p class="text-sm text-[var(--nx-muted)]">Erstelle ein neues Ziele & Erfolgskriterien System</p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createOkr" class="space-y-4">
                <x-nx-input-text
                    name="title"
                    label="Titel"
                    wire:model.live="title"
                    required
                    placeholder="Titel der Zielsteuerung eingeben"
                />

                <x-nx-input-textarea
                    name="description"
                    label="Beschreibung"
                    wire:model.live="description"
                    placeholder="Detaillierte Beschreibung der Zielsteuerung (optional)"
                    rows="3"
                />

                {{-- Performance-Score wird automatisch hochgerollt (KR→Objective→Cycle→OKR), nicht manuell gesetzt. --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-nx-input-select
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
                            class="w-4 h-4 text-[var(--nx-accent)] bg-[var(--nx-bg)] border-[color:var(--nx-line)] rounded focus:ring-[var(--nx-accent)] focus:ring-2"
                        >
                        <label for="auto_transfer" class="text-sm font-medium text-[var(--nx-text)]">
                            Automatisch übertragen
                        </label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            id="is_template"
                            wire:model.live="is_template"
                            class="w-4 h-4 text-[var(--nx-accent)] bg-[var(--nx-bg)] border-[color:var(--nx-line)] rounded focus:ring-[var(--nx-accent)] focus:ring-2"
                        >
                        <label for="is_template" class="text-sm font-medium text-[var(--nx-text)]">
                            Als Template speichern
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button 
                    type="button" 
                    variant="secondary-ghost" 
                    wire:click="closeCreateModal"
                >
                    Abbrechen
                </x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="createOkr">
                    Zielsteuerung anlegen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zielsteuerung Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalOkrs }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Gesamt Zielsteuerungen</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[color:var(--nx-success)]">{{ $activeOkrs }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Aktiv</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-2xl font-bold text-[color:var(--nx-info)]">{{ $templateOkrs }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Templates</div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-muted)] mb-3">Filter</h3>
                    <div class="space-y-3">
                        <div>
                            <x-nx-input-select
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
                            <x-nx-input-select
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
                            <div class="text-lg font-bold text-[var(--nx-accent)]">{{ round($averageScore, 1) }}%</div>
                            <div class="text-xs text-[var(--nx-muted)]">Durchschnitt Score</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[color:var(--nx-success)]">{{ $successfulOkrs }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Erfolgreich (≥80%)</div>
                        </div>
                        <div class="bg-[var(--nx-bg)] rounded-lg p-3">
                            <div class="text-lg font-bold text-[color:var(--nx-info)]">{{ $autoTransferOkrs }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Auto-Transfer</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>