{{-- OKR Sidebar --}}
<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        OKR
    </div>
    
    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('okr.dashboard')">
            @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('okr.okrs.index')">
            @svg('heroicon-o-flag', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">OKRs</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item type="button" wire:click="openCreateModal">
            @svg('heroicon-o-plus', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">OKR anlegen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('okr.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-chart-bar', 'w-5 h-5')
            </a>
            <a href="{{ route('okr.okrs.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-flag', 'w-5 h-5')
            </a>
            <button type="button" wire:click="openCreateModal" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-plus', 'w-5 h-5')
            </button>
        </div>
    </div>

    {{-- Abschnitt: OKRs --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
    @if($okrs->count() > 0)
                <x-ui-sidebar-list label="OKRs">
            @foreach($okrs as $okr)
                        <x-ui-sidebar-item :href="route('okr.okrs.show', ['okr' => $okr])">
                            @svg('heroicon-o-flag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <span class="truncate text-sm ml-2">{{ $okr->title }}</span>
                        </x-ui-sidebar-item>
            @endforeach
                </x-ui-sidebar-list>
            @else
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">Keine OKRs</div>
            @endif
        </div>
    </div>

    <!-- Create OKR Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="xl"
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

        <div class="space-y-6">
            <form wire:submit.prevent="createOkr" class="space-y-6">
                <!-- Grundinformationen -->
                <div class="space-y-4">
                    <div class="border-l-4 border-[var(--ui-primary)] pl-4">
                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-2">Grundinformationen</h4>
                        <p class="text-xs text-[var(--ui-muted)]">Titel und Beschreibung des OKR</p>
                    </div>
                    
                    <div class="space-y-4">
                        <x-ui-input-text
                            name="title"
                            label="OKR-Titel"
                            wire:model.live="title"
                            required
                            placeholder="z.B. Kundenbetreuung verbessern"
                            class="text-lg"
                        />

                        <x-ui-input-textarea
                            name="description"
                            label="Beschreibung"
                            wire:model.live="description"
                            placeholder="Detaillierte Beschreibung der Ziele und Erwartungen..."
                            rows="4"
                        />
                    </div>
                </div>

                <!-- Performance & Management -->
                <div class="space-y-4">
                    <div class="border-l-4 border-[var(--ui-primary)] pl-4">
                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-2">Performance & Management</h4>
                        <p class="text-xs text-[var(--ui-muted)]">Score und Verantwortlichkeiten</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui-input-number
                            name="performance_score"
                            label="Performance Score (%)"
                            wire:model.live="performance_score"
                            min="0"
                            max="100"
                            required
                            placeholder="0"
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
                </div>

                <!-- Optionen -->
                <div class="space-y-4">
                    <div class="border-l-4 border-[var(--ui-primary)] pl-4">
                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-2">Erweiterte Optionen</h4>
                        <p class="text-xs text-[var(--ui-muted)]">Zusätzliche Einstellungen für das OKR</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                <input 
                                    type="checkbox" 
                                    id="auto_transfer"
                                    wire:model.live="auto_transfer"
                                    class="w-4 h-4 text-[var(--ui-primary)] bg-[var(--ui-muted-5)] border-[var(--ui-border)] rounded focus:ring-[var(--ui-primary)] focus:ring-2 mt-0.5"
                                >
                                <div>
                                    <label for="auto_transfer" class="text-sm font-medium text-[var(--ui-secondary)] cursor-pointer">
                                        Automatisch übertragen
                                    </label>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">OKR wird automatisch in neue Zyklen übertragen</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                <input 
                                    type="checkbox" 
                                    id="is_template"
                                    wire:model.live="is_template"
                                    class="w-4 h-4 text-[var(--ui-primary)] bg-[var(--ui-muted-5)] border-[var(--ui-border)] rounded focus:ring-[var(--ui-primary)] focus:ring-2 mt-0.5"
                                >
                                <div>
                                    <label for="is_template" class="text-sm font-medium text-[var(--ui-secondary)] cursor-pointer">
                                        Als Template speichern
                                    </label>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">OKR als Vorlage für zukünftige Zyklen verwenden</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-between items-center">
                <div class="text-xs text-[var(--ui-muted)]">
                    Das OKR wird für dein Team erstellt
                </div>
                <div class="flex gap-3">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-ghost" 
                        wire:click="closeCreateModal"
                    >
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button 
                        type="button" 
                        variant="secondary" 
                        wire:click="createOkr"
                        class="min-w-32"
                    >
                        @svg('heroicon-o-check', 'w-4 h-4')
                        OKR anlegen
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
