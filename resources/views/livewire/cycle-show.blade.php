<div class="d-flex h-full">
    <!-- Linke Spalte -->
    <div class="flex-grow-1 d-flex flex-col">
        <!-- Header oben (fix) -->
        <div class="border-top-1 border-bottom-1 border-muted border-top-solid border-bottom-solid p-2 flex-shrink-0">
            <div class="d-flex gap-1">
                <div class="d-flex">
                    <a href="{{ route('okr.okrs.show', ['okr' => $cycle->okr_id]) }}" class="d-flex px-3 border-right-solid border-right-1 border-right-muted underline" wire:navigate>
                        {{ $cycle->okr->title }}
                    </a>
                </div>
                <div class="flex-grow-1 text-right d-flex items-center justify-end gap-2">
                    <span>{{ $cycle->template?->label ?? 'Unbekannter Cycle' }}</span>
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
                
                @if(session()->has('message'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800">{{ session('message') }}</p>
                    </div>
                @endif

                @if(session()->has('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800">{{ session('error') }}</p>
                    </div>
                @endif
            
            {{-- Cycle Details --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Cycle Details</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                        <div class="text-sm text-gray-900">{{ $cycle->template?->label ?? 'Kein Template' }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zeitraum</label>
                        <div class="text-sm text-gray-900">
                            @if($cycle->template)
                                {{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}
                            @else
                                Nicht definiert
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <x-ui-input-textarea 
                        name="cycle.notes"
                        label="Notizen"
                        wire:model.live.debounce.500ms="cycle.notes"
                        placeholder="Zusätzliche Notizen zum Cycle (optional)"
                        rows="3"
                        :errorKey="'cycle.notes'"
                    />
                </div>
            </div>

            {{-- Objectives & Key Results --}}
            <div class="mb-6">
                <div class="d-flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-secondary">Objectives & Key Results</h3>
                    <x-ui-button size="sm" variant="secondary-outline" wire:click="addObjective">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Objective hinzufügen
                        </div>
                    </x-ui-button>
                </div>

                @if($cycle->objectives->count() > 0)
                    <div wire:sortable="updateObjectiveOrder" wire:sortable-group="updateKeyResultOrder" wire:sortable.options="{ animation: 150 }">
                        @foreach($cycle->objectives->sortBy('order') as $objective)
                            <div wire:sortable.item="{{ $objective->id }}" wire:key="objective-{{ $objective->id }}" class="mb-4 p-4 border border-muted rounded-lg bg-white">
                                <div class="d-flex justify-between items-center mb-3">
                                    <div class="flex-grow-1">
                                        <div class="font-medium text-lg">{{ $objective->title }}</div>
                                        @if($objective->description)
                                            <div class="text-sm text-muted">{{ Str::limit($objective->description, 100) }}</div>
                                        @endif
                                        <div class="text-xs text-muted">Order: {{ $objective->order }}</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <x-ui-button 
                                            size="sm" 
                                            variant="secondary-outline" 
                                            wire:click="addKeyResult({{ $objective->id }})"
                                        >
                                            <div class="d-flex items-center gap-1">
                                                @svg('heroicon-o-plus', 'w-4 h-4')
                                                KR hinzufügen
                                            </div>
                                        </x-ui-button>
                                        <x-ui-button 
                                            size="sm" 
                                            variant="secondary-outline" 
                                            wire:click="editObjective({{ $objective->id }})"
                                        >
                                            @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                        </x-ui-button>
                                        <div wire:sortable.handle class="cursor-move p-2 text-muted hover:text-primary">
                                            @svg('heroicon-o-bars-3', 'w-4 h-4')
                                        </div>
                                    </div>
                                </div>

                                @if($objective->keyResults->count() > 0)
                                    <div wire:sortable-group.item-group="{{ $objective->id }}" wire:sortable-group.options="{ animation: 100 }" class="space-y-2">
                                        @foreach($objective->keyResults->sortBy('order') as $keyResult)
                                            <div wire:sortable-group.item="{{ $keyResult->id }}" wire:key="keyresult-{{ $keyResult->id }}" class="d-flex items-center gap-2 p-3 bg-muted-5 rounded border">
                                                <div wire:sortable-group.handle class="cursor-move p-1 text-muted hover:text-primary">
                                                    @svg('heroicon-o-bars-3', 'w-3 h-3')
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="font-medium text-sm">{{ $keyResult->title }}</div>
                                                    @if($keyResult->description)
                                                        <div class="text-xs text-muted">{{ Str::limit($keyResult->description, 60) }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center p-4 text-muted border-2 border-dashed border-muted rounded">
                                        <div class="text-sm">Noch keine Key Results</div>
                                        <div class="text-xs">Klicken Sie auf "KR hinzufügen"</div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center p-8 text-muted">
                        <div class="text-lg mb-2">Noch keine Objectives vorhanden</div>
                        <div class="text-sm">Klicken Sie auf "Objective hinzufügen" um zu beginnen</div>
                    </div>
                @endif
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
                    {{$cycle->activities->count()}}
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
                    :model="$cycle"
                    :key="get_class($cycle) . '_' . $cycle->id"
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
                    :href="route('okr.okrs.show', ['okr' => $cycle->okr_id])" 
                    wire:navigate
                    class="w-full"
                >
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        Zurück zu OKR
                    </div>
                </x-ui-button>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="mb-4 p-3 bg-muted-5 rounded-lg">
                <h4 class="font-semibold mb-2 text-secondary">Cycle-Übersicht</h4>
                <div class="space-y-1 text-sm">
                    <div><strong>Template:</strong> {{ $cycle->template?->label ?? 'Kein Template' }}</div>
                    @if($cycle->template)
                        <div><strong>Zeitraum:</strong> {{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}</div>
                    @endif
                    <div><strong>Objectives:</strong> {{ $cycle->objectives->count() }}</div>
                    <div><strong>Key Results:</strong> {{ $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()) }}</div>
                </div>
            </div>

            <hr>

            {{-- Status --}}
            <x-ui-input-select
                name="cycle.status"
                label="Status"
                :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'ending_soon' => 'Endet bald', 'past' => 'Vergangen']"
                :nullable="false"
                wire:model.live="cycle.status"
                required
            />

            <hr>

        </div>
    </div>

    <!-- Objective Create Modal -->
    <x-ui-modal
        size="lg"
        model="objectiveCreateModalShow"
    >
        <x-slot name="header">
            Objective hinzufügen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveObjective" class="space-y-4">
                <x-ui-input-text
                    name="objectiveForm.title"
                    label="Titel"
                    wire:model.live="objectiveForm.title"
                    placeholder="Titel des Objective eingeben..."
                    required
                />

                <x-ui-input-textarea
                    name="objectiveForm.description"
                    label="Beschreibung"
                    wire:model.live="objectiveForm.description"
                    placeholder="Detaillierte Beschreibung des Objective (optional)"
                    rows="3"
                />

                <x-ui-input-number
                    name="objectiveForm.order"
                    label="Reihenfolge"
                    wire:model.live="objectiveForm.order"
                    min="0"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeObjectiveCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveObjective">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Objective Edit Modal -->
    <x-ui-modal
        size="lg"
        model="objectiveEditModalShow"
    >
        <x-slot name="header">
            Objective bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveObjective" class="space-y-4">
                <x-ui-input-text
                    name="objectiveForm.title"
                    label="Titel"
                    wire:model.live="objectiveForm.title"
                    placeholder="Titel des Objective eingeben..."
                    required
                />

                <x-ui-input-textarea
                    name="objectiveForm.description"
                    label="Beschreibung"
                    wire:model.live="objectiveForm.description"
                    placeholder="Detaillierte Beschreibung des Objective (optional)"
                    rows="3"
                />

                <x-ui-input-number
                    name="objectiveForm.order"
                    label="Reihenfolge"
                    wire:model.live="objectiveForm.order"
                    min="0"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteObjectiveAndCloseModal" 
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
                        wire:click="closeObjectiveEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveObjective">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Key Result Create Modal -->
    <x-ui-modal
        size="md"
        model="keyResultCreateModalShow"
    >
        <x-slot name="header">
            Key Result hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-text
                name="keyResultTitle"
                label="Titel"
                wire:model.live="keyResultTitle"
                placeholder="Titel des Key Result eingeben..."
                required
            />

            <x-ui-input-textarea
                name="keyResultDescription"
                label="Beschreibung"
                wire:model.live="keyResultDescription"
                placeholder="Beschreibung des Key Result (optional)"
                rows="3"
            />
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

</div>
