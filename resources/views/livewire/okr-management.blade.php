<div class="p-3">
    <h1 class="text-2xl font-bold mb-4">OKRs</h1>

    <div class="d-flex justify-between mb-4">
        <x-ui-input-text 
            name="search" 
            placeholder="Suche OKRs..." 
            class="w-64"
        />
        <x-ui-button variant="primary" wire:click="openCreateModal">
            Neues OKR
        </x-ui-button>
    </div>

    @if(session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ session('message') }}</p>
        </div>
    @endif

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
                        <div class="font-medium">{{ $okr->title }}</div>
                        @if($okr->is_template)
                            <x-ui-badge variant="info" size="xs">Template</x-ui-badge>
                        @endif
                        @if($okr->auto_transfer)
                            <x-ui-badge variant="success" size="xs">Auto-Transfer</x-ui-badge>
                        @endif
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-muted">{{ Str::limit($okr->description, 50) }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-muted">{{ $okr->user?->name ?? 'Unbekannt' }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-muted">{{ $okr->manager?->name ?? '–' }}</div>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        @if($okr->performance_score !== null)
                            <x-ui-badge variant="info" size="sm">{{ $okr->performance_score }}%</x-ui-badge>
                        @else
                            <span class="text-xs text-muted">–</span>
                        @endif
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true">
                        <div class="text-xs text-muted">{{ $okr->cycles->count() }} Cycles</div>
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

                <div class="d-flex items-center gap-4">
                    <x-ui-input-checkbox
                        name="auto_transfer"
                        label="Automatisch übertragen"
                        model="auto_transfer"
                    />
                    <x-ui-input-checkbox
                        name="is_template"
                        label="Als Template speichern"
                        model="is_template"
                    />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    @click="$wire.closeCreateModal()"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createOkr">
                    OKR anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
