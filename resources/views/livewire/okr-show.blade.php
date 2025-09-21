<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('okr.okrs.index') }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        OKRs
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $okr->title }}</span>
                    @if($this->isDirty)
                        <x-ui-button 
                            variant="primary" 
                            size="sm"
                            wire:click="save"
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-check', 'w-4 h-4')
                                Speichern
                            </div>
                        </x-ui-button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
        <div class="flex-grow-1 overflow-y-auto p-4">
            
            {{-- OKR Grunddaten --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">OKR Grunddaten</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="okr.title"
                        label="Titel"
                        wire:model.live.debounce.500ms="okr.title"
                        placeholder="OKR Titel eingeben..."
                        required
                        :errorKey="'okr.title'"
                    />
                    <x-ui-input-number
                        name="okr.performance_score"
                        label="Performance Score (%)"
                        wire:model.live.debounce.500ms="okr.performance_score"
                        min="0"
                        max="100"
                        :errorKey="'okr.performance_score'"
                    />
                </div>
                <div class="mt-4">
                    <x-ui-input-textarea 
                        name="okr.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="okr.description"
                        placeholder="Detaillierte Beschreibung des OKR (optional)"
                        rows="4"
                        :errorKey="'okr.description'"
                    />
                </div>
            </div>

            {{-- Verantwortlichkeiten --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Verantwortlichkeiten</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select
                        name="okr.user_id"
                        label="Verantwortlicher"
                        :options="$users"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="false"
                        wire:model.live="okr.user_id"
                        required
                    />
                    <x-ui-input-select
                        name="okr.manager_user_id"
                        label="Manager"
                        :options="$users"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Manager auswählen –"
                        wire:model.live="okr.manager_user_id"
                    />
                </div>
            </div>

            {{-- Einstellungen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Einstellungen</h3>
                <div class="d-flex items-center gap-4">
                    <x-ui-input-checkbox
                        name="okr.auto_transfer"
                        label="Automatisch übertragen"
                        wire:model.live="okr.auto_transfer"
                    />
                    <x-ui-input-checkbox
                        name="okr.is_template"
                        label="Als Template speichern"
                        wire:model.live="okr.is_template"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">

        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-cog-6-tooth class="w-6 h-6"/>
            Cycles
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">

            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('okr.okrs.index')" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu OKRs
                    </div>
                </x-ui-button>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">OKR-Übersicht</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Titel:</strong> {{ $okr->title }}</div>
                    @if($okr->performance_score !== null)
                        <div><strong>Score:</strong> {{ $okr->performance_score }}%</div>
                    @endif
                    <div><strong>Verantwortlicher:</strong> {{ $okr->user?->name ?? 'Unbekannt' }}</div>
                    @if($okr->manager)
                        <div><strong>Manager:</strong> {{ $okr->manager->name }}</div>
                    @endif
                    <div><strong>Cycles:</strong> {{ $okr->cycles->count() }}</div>
                </div>
            </div>

            {{-- Cycles --}}
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Cycles</h4>
                <div class="space-y-2">
                    @foreach($okr->cycles as $cycle)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer" wire:click="editCycle({{ $cycle->id }})">
                            <div class="flex-grow-1">
                                <div class="text-sm font-medium">
                                    {{ $cycle->template?->label ?? 'Unbekanntes Template' }}
                                </div>
                                <div class="text-xs text-muted">
                                    {{ $cycle->template?->starts_at?->format('d.m.Y') }} - 
                                    {{ $cycle->template?->ends_at?->format('d.m.Y') }}
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <x-ui-badge 
                                    variant="@if($cycle->status === 'current') success @elseif($cycle->status === 'draft') secondary @elseif($cycle->status === 'ending_soon') warning @else danger @endif" 
                                    size="xs"
                                >
                                    {{ ucfirst($cycle->status) }}
                                </x-ui-badge>
                            </div>
                        </div>
                    @endforeach
                    @if($okr->cycles->count() === 0)
                        <p class="text-sm text-muted">Noch keine Cycles vorhanden.</p>
                    @endif
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="addCycle">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Cycle hinzufügen
                        </div>
                    </x-ui-button>
                </div>
            </div>

            <hr>

        </div>
    </div>

    <!-- Cycle Create Modal -->
    <x-ui-modal
        size="md"
        model="cycleCreateModalShow"
    >
        <x-slot name="header">
            Cycle hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="cycleForm.cycle_template_id"
                label="Cycle Template"
                :options="$cycleTemplates"
                optionValue="id"
                optionLabel="label"
                :nullable="false"
                wire:model.live="cycleForm.cycle_template_id"
                required
            />

            <x-ui-input-select
                name="cycleForm.status"
                label="Status"
                :options="[
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'current', 'name' => 'Current'],
                    ['id' => 'ending_soon', 'name' => 'Ending Soon'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'archived', 'name' => 'Archived']
                ]"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="cycleForm.status"
            />

            <x-ui-input-textarea
                name="cycleForm.notes"
                label="Notizen"
                wire:model.live="cycleForm.notes"
                placeholder="Zusätzliche Notizen zum Cycle (optional)"
                rows="3"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeCycleCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveCycle">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Cycle Edit Modal -->
    <x-ui-modal
        size="md"
        model="cycleEditModalShow"
    >
        <x-slot name="header">
            Cycle bearbeiten
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="cycleForm.cycle_template_id"
                label="Cycle Template"
                :options="$cycleTemplates"
                optionValue="id"
                optionLabel="label"
                :nullable="false"
                wire:model.live="cycleForm.cycle_template_id"
                required
            />

            <x-ui-input-select
                name="cycleForm.status"
                label="Status"
                :options="[
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'current', 'name' => 'Current'],
                    ['id' => 'ending_soon', 'name' => 'Ending Soon'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'archived', 'name' => 'Archived']
                ]"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="cycleForm.status"
            />

            <x-ui-input-textarea
                name="cycleForm.notes"
                label="Notizen"
                wire:model.live="cycleForm.notes"
                placeholder="Zusätzliche Notizen zum Cycle (optional)"
                rows="3"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteCycleAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closeCycleEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveCycle">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</div>
