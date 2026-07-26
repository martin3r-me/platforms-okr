<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zielsteuerungen', 'href' => route('okr.okrs.index')],
            ['label' => $okr->title],
        ]">
            <x-slot name="left">
                <x-nx-button variant="ghost" size="sm" wire:click="$set('okrSettingsModalShow', true)">
                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                    <span>Einstellungen</span>
                </x-nx-button>
            </x-slot>
            <x-nx-button variant="primary" size="sm" wire:click="openCycleCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Zyklus hinzufügen</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)] font-medium">Fehler:</p>
                <p class="text-[var(--nx-muted)]">{{ session('error') }}</p>
            </div>
        @endif

        {{-- OKR Header --}}
        <div class="bg-gradient-to-r from-[var(--nx-bg)] to-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-flag', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--nx-text)] tracking-tight">{{ $okr->title }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)] mt-1">
                                @if($okr->team)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-building-office', 'w-4 h-4')
                                        {{ $okr->team->name }}
                                    </span>
                                @endif
                                @if($okr->manager)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user', 'w-4 h-4')
                                        {{ $okr->manager->name }}
                                    </span>
                                @endif
                                @php
                                    $okrPerformance = $okr->performance;
                                @endphp
                                @if($okrPerformance)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span class="font-medium {{ $okrPerformance->performance_score >= 80 ? 'text-[color:var(--nx-success)]' : ($okrPerformance->performance_score >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                            {{ $okrPerformance->performance_score }}%
                                        </span>
                                    </span>
                                @elseif($okr->performance_score)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span class="font-medium text-[var(--nx-muted)]">
                                            {{ round($okr->performance_score * 100) }}%
                                        </span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- OKR Details --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Zielsteuerung Details</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Grundinformationen und Einstellungen</p>
                    </div>
                </div>
                <x-nx-button variant="secondary" size="sm" wire:click="$set('okrSettingsModalShow', true)">
                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                    <span class="ml-1">Einstellungen</span>
                </x-nx-button>
            </div>
            
            <div class="text-sm text-[var(--nx-muted)]">Alle Einstellungen findest du über den Button oben rechts.</div>
        </div>

        {{-- Strategic Documents Section (Read-Only) --}}
        @if($this->mission || $this->vision)
            <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-document-text', 'w-4 h-4')
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-[var(--nx-text)]">Strategische Orientierung</h3>
                            <p class="text-sm text-[var(--nx-muted)]">Mission & Vision (Read-Only)</p>
                        </div>
                    </div>
                    <x-nx-button variant="secondary" size="sm" :href="route('okr.strategic-documents.index')" wire:navigate>
                        @svg('heroicon-o-pencil', 'w-4 h-4')
                        <span class="ml-1">Verwalten</span>
                    </x-nx-button>
                </div>

                <div class="space-y-6">
                    {{-- Mission --}}
                    @if($this->mission)
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-6 h-6 bg-[color:var(--nx-info)] text-white rounded flex items-center justify-center">
                                    @svg('heroicon-o-document-text', 'w-4 h-4')
                                </div>
                                <h4 class="font-semibold text-[var(--nx-text)]">🧭 Mission</h4>
                                <span class="text-xs text-[var(--nx-muted)] ml-auto">
                                    Version {{ $this->mission->version }} • Aktiv seit {{ $this->mission->valid_from->format('d.m.Y') }}
                                </span>
                            </div>
                            <p class="text-xs text-[var(--nx-muted)] mb-3 italic">
                                Die Mission beschreibt, warum die Organisation heute existiert und welchen übergeordneten Zweck sie erfüllt. 
                                Zeitlich stabil, selten geändert, keine KPIs/Zielsteuerungen, Referenz für Entscheidungen.
                            </p>
                            <div class="prose prose-sm max-w-none text-[var(--nx-text)]">
                                {!! \Illuminate\Support\Str::markdown($this->mission->content ?? '') !!}
                            </div>
                        </div>
                    @endif

                    {{-- Vision --}}
                    @if($this->vision)
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-6 h-6 bg-[color:var(--nx-tone-violet)] text-white rounded flex items-center justify-center">
                                    @svg('heroicon-o-sun', 'w-4 h-4')
                                </div>
                                <h4 class="font-semibold text-[var(--nx-text)]">🌄 Vision</h4>
                                <span class="text-xs text-[var(--nx-muted)] ml-auto">
                                    Version {{ $this->vision->version }} • Aktiv seit {{ $this->vision->valid_from->format('d.m.Y') }}
                                </span>
                            </div>
                            <p class="text-xs text-[var(--nx-muted)] mb-3 italic">
                                Die Vision beschreibt einen bewusst angestrebten zukünftigen Zustand der Organisation. 
                                Normativ (gewollt, nicht prognostiziert), langfristig (5–10 Jahre), keine Erfolgskriterien, dient als "North Star".
                            </p>
                            <div class="prose prose-sm max-w-none text-[var(--nx-text)]">
                                {!! \Illuminate\Support\Str::markdown($this->vision->content ?? '') !!}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- OKR Settings Modal --}}
        <x-nx-modal model="okrSettingsModalShow" size="lg">
            <x-slot name="header">Zielsteuerung Einstellungen</x-slot>
            <div class="space-y-6">
                @can('update', $okr)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-nx-input-text
                            name="okr.title"
                            label="Titel"
                            wire:model.live.debounce.500ms="okr.title"
                            placeholder="Titel eingeben..."
                            :errorKey="'okr.title'"
                        />
                        <x-nx-input-select 
                            name="okr.manager_user_id"
                            label="Manager"
                            :options="$this->users->pluck('name','id')->toArray()"
                            wire:model.live="okr.manager_user_id"
                        />
                        <div class="md:col-span-2">
                            <x-nx-input-textarea
                                name="okr.description"
                                label="Beschreibung"
                                wire:model.live.debounce.500ms="okr.description"
                                placeholder="Detaillierte Beschreibung der Zielsteuerung..."
                                rows="3"
                                :errorKey="'okr.description'"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <x-nx-input-checkbox
                                model="okr.auto_transfer"
                                checked-label="Automatische Übertragung"
                                unchecked-label="Manuelle Übertragung"
                            />
                        </div>
                    </div>
                @endcan
                <div>
                    <h4 class="text-md font-medium text-[var(--nx-text)] mb-3">Teilnehmer</h4>
                    @if($this->members->count() > 0)
                        <div class="space-y-2 mb-4">
                            @foreach($this->members as $member)
                                <div class="flex items-center justify-between p-3 bg-[var(--nx-bg)] rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] rounded-full flex items-center justify-center text-sm font-medium">
                                            {{ substr($member->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ $member->name }}</div>
                                            <div class="text-sm text-[var(--nx-muted)]">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @can('changeRole', $okr)
                                            <x-nx-input-select
                                                name="memberRoleSelect_{{ $member->id }}"
                                                :options="$this->roleOptions"
                                                :nullable="false"
                                                :value="$member->pivot->role"
                                                wire:change="updateMemberRole({{ $member->id }}, $event.target.value)"
                                                size="sm"
                                            />
                                        @endcan
                                        @can('removeMember', $okr)
                                            <x-nx-button variant="danger-ghost" size="xs" wire:click="removeMember({{ $member->id }})">Entfernen</x-nx-button>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] text-center mb-4">
                            <p class="text-sm text-[var(--nx-muted)]">Noch keine Teilnehmer hinzugefügt</p>
                        </div>
                    @endif
                </div>
                @can('invite', $okr)
                    <div class="pt-4 border-t">
                        <h4 class="text-md font-medium text-[var(--nx-text)] mb-3">Teilnehmer hinzufügen</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <x-nx-input-select 
                                name="memberUserId"
                                label="Nutzer"
                                :options="$this->availableUsers->pluck('name','id')->toArray()"
                                wire:model.live="memberUserId"
                            />
                            <x-nx-input-select 
                                name="memberRole"
                                label="Rolle"
                                :options="$this->roleOptions"
                                wire:model.live="memberRole"
                            />
                            <div class="flex items-end">
                                <x-nx-button variant="primary" wire:click="addMember">Hinzufügen</x-nx-button>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
            <x-slot name="footer">
                <div class="flex items-center gap-2">
                    @can('update', $okr)
                        <x-nx-button variant="primary" wire:click="save">
                            @svg('heroicon-o-check', 'w-4 h-4')
                            <span class="ml-1">Speichern</span>
                        </x-nx-button>
                    @endcan
                    <x-nx-button variant="ghost" wire:click="$set('okrSettingsModalShow', false)">Schließen</x-nx-button>
                </div>
            </x-slot>
        </x-nx-modal>

        {{-- Cycles Section --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-calendar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Zyklen</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Verwalte die Zielsteuerung-Zyklen</p>
                    </div>
                </div>
                <x-nx-button variant="primary" size="sm" wire:click="openCycleCreateModal">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Zyklus hinzufügen</span>
                </x-nx-button>
            </div>
            
            @if($okr->cycles->count() > 0)
                <div class="space-y-4">
                    @foreach($okr->cycles as $cycle)
                        @php
                            $cyclePerformance = $cycle->performance;
                            $totalObjectives = $cycle->objectives->count();
                            $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                            $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                            $cycleProgress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                        @endphp
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4 hover:border-[color:var(--nx-line)] transition-colors cursor-pointer" 
                             wire:click="openCycle({{ $cycle->id }})">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded flex items-center justify-center text-xs font-semibold">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-[var(--nx-text)]">{{ $cycle->template->label ?? 'Unbekannter Zyklus' }}</h4>
                                        <p class="text-sm text-[var(--nx-muted)]">
                                            {{ $cycle->starts_at ? $cycle->starts_at->format('d.m.Y') : 'Kein Startdatum' }} - 
                                            {{ $cycle->ends_at ? $cycle->ends_at->format('d.m.Y') : 'Kein Enddatum' }}
                                        </p>
                                        <p class="text-xs text-[var(--nx-muted)]">
                                            {{ $totalObjectives }} Objectives • {{ $totalKeyResults }} Erfolgskriterien
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($cyclePerformance)
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $cyclePerformance->performance_score >= 80 ? 'text-[color:var(--nx-success)]' : ($cyclePerformance->performance_score >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                                {{ $cyclePerformance->performance_score }}%
                                            </div>
                                            <div class="text-xs text-[var(--nx-muted)]">Performance</div>
                                        </div>
                                    @else
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $cycleProgress >= 80 ? 'text-[color:var(--nx-success)]' : ($cycleProgress >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                                {{ $cycleProgress }}%
                                            </div>
                                            <div class="text-xs text-[var(--nx-muted)]">Fortschritt</div>
                                        </div>
                                    @endif
                                    <x-nx-badge variant="neutral">{{ ucfirst($cycle->status) }}</x-nx-badge>
                                    <x-nx-button 
                                        variant="ghost" 
                                        size="sm"
                                        wire:click.stop="editCycle({{ $cycle->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-nx-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-calendar', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h3 class="text-lg font-medium text-[var(--nx-text)] mb-2">Keine Zyklen vorhanden</h3>
                    <p class="text-[var(--nx-muted)] mb-6">Erstelle den ersten Zyklus für diese Zielsteuerung</p>
                    <x-nx-button variant="primary" wire:click="openCycleCreateModal">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Zyklus erstellen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Cycle Create Modal --}}
    <x-nx-modal wire:model="modalShow">
        <x-slot name="header">Zyklus erstellen</x-slot>
        <div class="space-y-4">
            <x-nx-input-text
                name="cycleForm.notes"
                label="Notizen"
                wire:model="cycleForm.notes"
                placeholder="Optionale Notizen zum Zyklus..."
            />
            
            <div>
                <x-nx-input-select
                    name="cycleForm.cycle_template_id"
                    label="Template"
                    :options="$cycleTemplates"
                    optionValue="id"
                    optionLabel="label"
                    :nullable="false"
                    wire:model.live="cycleForm.cycle_template_id"
                />
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="closeCycleCreateModal">
                    Abbrechen
                </x-nx-button>
                <x-nx-button variant="primary" wire:click="createCycle">
                    Zyklus erstellen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zielsteuerung Übersicht" width="w-80" side="left" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- OKR Performance --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Zielsteuerung Performance</h3>
                    <div class="space-y-3">
                        @php
                            $okrPerformance = $okr->performance;
                            $totalCycles = $okr->cycles->count();
                            $totalObjectives = $okr->cycles->sum(fn($cycle) => $cycle->objectives->count());
                            $totalKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()));
                            $completedKeyResults = $okr->cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count()));
                        @endphp
                        
                        @if($okrPerformance)
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Gesamt Performance</span>
                                    <span class="text-sm font-bold {{ $okrPerformance->performance_score >= 80 ? 'text-[color:var(--nx-success)]' : ($okrPerformance->performance_score >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                        {{ $okrPerformance->performance_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $okrPerformance->performance_score >= 80 ? 'bg-[color:var(--nx-success)]' : ($okrPerformance->performance_score >= 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]') }}" 
                                         style="width: {{ $okrPerformance->performance_score }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--nx-muted)]">
                                    {{ $okrPerformance->completed_cycles }}/{{ $okrPerformance->total_cycles }} Cycles • 
                                    {{ $okrPerformance->completed_objectives }}/{{ $okrPerformance->total_objectives }} Objectives
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalCycles }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Cycles</div>
                            </div>
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalObjectives }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Objectives</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalKeyResults }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Erfolgskriterien</div>
                            </div>
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[color:var(--nx-success)]">{{ $completedKeyResults }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Abgeschlossen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- OKR Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Zielsteuerung Details</h3>
                    <div class="space-y-3">
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Team</span>
                                    <span class="text-sm text-[var(--nx-muted)]">{{ $okr->team->name ?? 'Kein Team' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Manager</span>
                                    <span class="text-sm text-[var(--nx-muted)]">{{ $okr->manager->name ?? 'Kein Manager' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Auto Transfer</span>
                                    <span class="text-sm text-[var(--nx-muted)]">{{ $okr->auto_transfer ? 'Ja' : 'Nein' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Erstellt</span>
                                    <span class="text-sm text-[var(--nx-muted)]">{{ $okr->created_at->format('d.m.Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--nx-muted)]">Keine Aktivitäten verfügbar</div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>