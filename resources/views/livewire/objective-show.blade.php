<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('okr.cycles.show', ['cycle' => $objective->cycle_id]) }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        {{ $objective->cycle->template?->label ?? 'Cycle' }}
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $objective->title }}</span>
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
            
            {{-- Objective Details --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Objective Details</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="objective.title"
                        label="Titel"
                        wire:model.live.debounce.500ms="objective.title"
                        placeholder="Titel des Objective eingeben..."
                        required
                        :errorKey="'objective.title'"
                    />
                    <x-ui-input-number
                        name="objective.order"
                        label="Reihenfolge"
                        wire:model.live.debounce.500ms="objective.order"
                        min="0"
                        required
                        :errorKey="'objective.order'"
                    />
                </div>
                <div class="mt-4">
                    <x-ui-input-textarea 
                        name="objective.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="objective.description"
                        placeholder="Detaillierte Beschreibung des Objective (optional)"
                        rows="4"
                        :errorKey="'objective.description'"
                    />
                </div>
            </div>

            {{-- Key Results --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Key Results</h3>
                <div class="space-y-2">
                    @foreach($objective->keyResults as $keyResult)
                        <div class="d-flex items-center gap-2 p-2 bg-muted-5 rounded cursor-pointer">
                            <div class="flex-grow-1">
                                <div class="text-sm font-medium">
                                    {{ $keyResult->title }}
                                </div>
                                <div class="text-xs text-muted">
                                    @if($keyResult->description)
                                        {{ Str::limit($keyResult->description, 50) }}
                                    @endif
                                    @if($keyResult->target_value)
                                        • Ziel: {{ $keyResult->target_value }}{{ $keyResult->unit ? ' ' . $keyResult->unit : '' }}
                                    @endif
                                    @if($keyResult->current_value)
                                        • Aktuell: {{ $keyResult->current_value }}{{ $keyResult->unit ? ' ' . $keyResult->unit : '' }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Order: {{ $keyResult->order }}
                                </span>
                                <x-ui-button 
                                    size="xs" 
                                    variant="secondary-outline" 
                                    wire:click="editKeyResult({{ $keyResult->id }})"
                                >
                                    @svg('heroicon-o-cog-6-tooth', 'w-3 h-3')
                                </x-ui-button>
                            </div>
                        </div>
                    @endforeach
                    @if($objective->keyResults->count() === 0)
                        <p class="text-sm text-muted">Noch keine Key Results vorhanden.</p>
                    @endif
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="addKeyResult">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Key Result hinzufügen
                        </div>
                    </x-ui-button>
                </div>
            </div>
        </div>

        <!-- Aktivitäten (immer unten) -->
        <div x-data="{ open: false }" class="flex-shrink-0 border-t border-muted">
            <div 
                @click="open = !open" 
                class="cursor-pointer border-top-1 border-top-solid border-top-muted border-bottom-1 border-bottom-solid border-bottom-muted p-2 text-center d-flex items-center justify-center gap-1 mx-2 shadow-lg"
            >
                AKTIVITÄTEN 
                <span class="text-xs">
                    {{$objective->activities->count()}}
                </span>
                <x-heroicon-o-chevron-double-down 
                    class="w-3 h-3" 
                    x-show="!open"
                />
                <x-heroicon-o-chevron-double-up 
                    class="w-3 h-3" 
                    x-show="open"
                />
            </div>
            <div x-show="open" class="p-2 max-h-xs overflow-y-auto">
                {{-- <livewire:activity-log.index
                    :model="$objective"
                    :key="get_class($objective) . '_' . $objective->id"
                /> --}}
            </div>
        </div>
    </div>

    <!-- Rechte Spalte -->
    <div class="min-w-80 w-80 d-flex flex-col border-left-1 border-left-solid border-left-muted">

        <div class="d-flex gap-2 border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <x-heroicon-o-cog-6-tooth class="w-6 h-6"/>
            Einstellungen
        </div>
        <div class="flex-grow-1 overflow-y-auto p-4">

            {{-- Navigation Buttons --}}
            <div class="d-flex flex-col gap-2 mb-4">
                <x-ui-button 
                    variant="secondary-outline" 
                    size="md" 
                    :href="route('okr.cycles.show', ['cycle' => $objective->cycle_id])" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu Cycle
                    </div>
                </x-ui-button>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Objective-Übersicht</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Titel:</strong> {{ $objective->title }}</div>
                    <div><strong>Reihenfolge:</strong> {{ $objective->order }}</div>
                    <div><strong>Key Results:</strong> {{ $objective->keyResults->count() }}</div>
                    <div><strong>Cycle:</strong> {{ $objective->cycle->template?->label ?? 'Unbekannt' }}</div>
                    <div><strong>OKR:</strong> {{ $objective->okr->title }}</div>
                </div>
            </div>

            <hr>

        </div>
    </div>

    <!-- Key Result Create Modal -->
    <x-ui-modal
        size="lg"
        model="keyResultCreateModalShow"
    >
        <x-slot name="header">
            Key Result hinzufügen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveKeyResult" class="space-y-4">
                <x-ui-input-text
                    name="keyResultForm.title"
                    label="Titel"
                    wire:model.live="keyResultForm.title"
                    placeholder="Titel des Key Result eingeben..."
                    required
                />

                <x-ui-input-textarea
                    name="keyResultForm.description"
                    label="Beschreibung"
                    wire:model.live="keyResultForm.description"
                    placeholder="Detaillierte Beschreibung des Key Result (optional)"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="keyResultForm.target_value"
                        label="Zielwert"
                        wire:model.live="keyResultForm.target_value"
                        placeholder="Zielwert eingeben..."
                        required
                    />
                    <x-ui-input-text
                        name="keyResultForm.current_value"
                        label="Aktueller Wert"
                        wire:model.live="keyResultForm.current_value"
                        placeholder="Aktueller Wert (optional)"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="keyResultForm.unit"
                        label="Einheit"
                        wire:model.live="keyResultForm.unit"
                        placeholder="z.B. %, €, Stück"
                    />
                    <x-ui-input-number
                        name="keyResultForm.order"
                        label="Reihenfolge"
                        wire:model.live="keyResultForm.order"
                        min="0"
                        required
                    />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeKeyResultCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveKeyResult">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Key Result Edit Modal -->
    <x-ui-modal
        size="lg"
        model="keyResultEditModalShow"
    >
        <x-slot name="header">
            Key Result bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveKeyResult" class="space-y-4">
                <x-ui-input-text
                    name="keyResultForm.title"
                    label="Titel"
                    wire:model.live="keyResultForm.title"
                    placeholder="Titel des Key Result eingeben..."
                    required
                />

                <x-ui-input-textarea
                    name="keyResultForm.description"
                    label="Beschreibung"
                    wire:model.live="keyResultForm.description"
                    placeholder="Detaillierte Beschreibung des Key Result (optional)"
                    rows="3"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="keyResultForm.target_value"
                        label="Zielwert"
                        wire:model.live="keyResultForm.target_value"
                        placeholder="Zielwert eingeben..."
                        required
                    />
                    <x-ui-input-text
                        name="keyResultForm.current_value"
                        label="Aktueller Wert"
                        wire:model.live="keyResultForm.current_value"
                        placeholder="Aktueller Wert (optional)"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="keyResultForm.unit"
                        label="Einheit"
                        wire:model.live="keyResultForm.unit"
                        placeholder="z.B. %, €, Stück"
                    />
                    <x-ui-input-number
                        name="keyResultForm.order"
                        label="Reihenfolge"
                        wire:model.live="keyResultForm.order"
                        min="0"
                        required
                    />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteKeyResultAndCloseModal" 
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
                        wire:click="closeKeyResultEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveKeyResult">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>
</div>
