<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <x-slot name="titleActions">
                <x-ui-button variant="secondary-ghost" size="sm" rounded="full" iconOnly="true" x-data @click="$dispatch('open-modal-task-settings', { taskId: {{ $okr->id }} })" title="Einstellungen">
                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                </x-ui-button>
            </x-slot>
            
            {{-- Simple Breadcrumbs --}}
            <div class="flex items-center space-x-2 text-sm">
                <a href="{{ route('okr.dashboard') }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-home', 'w-4 h-4')
                    Dashboard
                </a>
                <span class="text-[var(--ui-muted)]">›</span>
                <a href="{{ route('okr.okrs.index') }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                    OKRs
                </a>
                <span class="text-[var(--ui-muted)]">›</span>
                <span class="text-[var(--ui-muted)] flex items-center gap-1">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                    {{ $okr->title }}
                </span>
            </div>
            
            <x-ui-button variant="secondary" size="sm" wire:click="save">
                <span class="inline-flex items-center gap-2">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span class="hidden sm:inline">Speichern</span>
                </span>
            </x-ui-button>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Modern Header --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight">{{ $okr->title }}</h1>
                    <div class="flex items-center gap-6 text-sm text-[var(--ui-muted)]">
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
                        @if($okr->performance_score)
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                {{ $okr->performance_score }}%
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- OKR Details --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-flag', 'w-4 h-4')
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">OKR Details</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Grundinformationen und Einstellungen</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <x-ui-input-text 
                        name="okr.title"
                        label="OKR-Titel"
                        wire:model.live.debounce.500ms="okr.title"
                        placeholder="z.B. Kundenbetreuung verbessern"
                        required
                        :errorKey="'okr.title'"
                        class="text-lg"
                    />
                    
                    <x-ui-input-select
                        name="okr.manager_user_id"
                        label="Verantwortlicher Manager"
                        :options="$this->users"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Manager auswählen –"
                        wire:model.live="okr.manager_user_id"
                    />
                </div>
                
                <div class="space-y-4">
                    <x-ui-input-textarea
                        name="okr.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="okr.description"
                        placeholder="Detaillierte Beschreibung des OKRs..."
                        rows="4"
                        :errorKey="'okr.description'"
                    />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-number
                            name="okr.performance_score"
                            label="Performance Score"
                            wire:model.live.debounce.500ms="okr.performance_score"
                            placeholder="0"
                            min="0"
                            max="100"
                            step="0.1"
                            :errorKey="'okr.performance_score'"
                        />
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-[var(--ui-secondary)]">Einstellungen</label>
                            <div class="space-y-2">
                                <x-ui-input-checkbox
                                    model="okr.auto_transfer"
                                    checked-label="Automatische Übertragung"
                                />
                                
                                <x-ui-input-checkbox
                                    model="okr.is_template"
                                    checked-label="Als Vorlage speichern"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cycles --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-calendar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Zyklen</h3>
                        <p class="text-sm text-[var(--ui-muted)]">OKR-Zyklen verwalten</p>
                    </div>
                </div>
                <x-ui-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="openCycleCreateModal"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Zyklus hinzufügen</span>
                </x-ui-button>
            </div>
            
            @if($okr->cycles->count() > 0)
                <div class="space-y-4">
                    @foreach($okr->cycles as $cycle)
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4 hover:border-[var(--ui-border)]/60 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center text-xs font-semibold">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-[var(--ui-secondary)]">{{ $cycle->cycleTemplate->name ?? 'Unbekannter Zyklus' }}</h4>
                                        <p class="text-sm text-[var(--ui-muted)]">
                                            {{ $cycle->starts_at ? $cycle->starts_at->format('d.m.Y') : 'Kein Startdatum' }} - 
                                            {{ $cycle->ends_at ? $cycle->ends_at->format('d.m.Y') : 'Kein Enddatum' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui-badge variant="secondary">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                    <x-ui-button 
                                        variant="secondary-ghost" 
                                        size="sm"
                                        wire:click="editCycle({{ $cycle->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-ui-button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-calendar', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Zyklen vorhanden</h4>
                    <p class="text-[var(--ui-muted)] mb-4">Erstelle den ersten Zyklus für dieses OKR</p>
                    <x-ui-button 
                        variant="secondary" 
                        wire:click="openCycleCreateModal"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Zyklus hinzufügen</span>
                    </x-ui-button>
                </div>
            @endif
        </div>

        {{-- Aktivitäten --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-clock', 'w-4 h-4')
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Aktivitäten</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Letzte Änderungen und Aktivitäten</p>
                </div>
            </div>
            
            <div class="text-center py-8">
                <p class="text-[var(--ui-muted)]">Aktivitäten werden später implementiert</p>
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Navigation & Details" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Team Info --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Team</h3>
                    <div class="space-y-2">
                        <div class="text-sm">
                            <span class="text-[var(--ui-muted)]">Name:</span>
                            <span class="text-[var(--ui-secondary)]">{{ $okr->team->name ?? 'Kein Team' }}</span>
                        </div>
                    </div>
                </div>

                {{-- OKR Info --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">OKR Details</h3>
                    <div class="space-y-2">
                        <div class="text-sm">
                            <span class="text-[var(--ui-muted)]">Erstellt von:</span>
                            <span class="text-[var(--ui-secondary)]">{{ $okr->user->name ?? 'Unbekannt' }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-[var(--ui-muted)]">Erstellt am:</span>
                            <span class="text-[var(--ui-secondary)]">{{ $okr->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-[var(--ui-muted)]">Zuletzt geändert:</span>
                            <span class="text-[var(--ui-secondary)]">{{ $okr->updated_at->format('d.m.Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button 
                            variant="secondary" 
                            :href="route('okr.okrs.index')" 
                            wire:navigate
                            class="w-full"
                        >
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span class="ml-1">Zurück zu OKRs</span>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">OKR erstellt</div>
                        <div class="text-[var(--ui-muted)]">{{ $okr->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>