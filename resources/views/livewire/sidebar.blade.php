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
        <button type="button" wire:click="openCreateModal" class="w-full flex items-center px-3 py-2 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] rounded-md">
            @svg('heroicon-o-plus', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">OKR anlegen</span>
        </button>
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
</div>
