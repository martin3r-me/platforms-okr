<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$cycle->template?->label ?? 'Unbekannter Cycle'" icon="heroicon-o-calendar">
            <div class="flex items-center gap-2">
                <x-ui-button 
                    variant="secondary-ghost" 
                    size="sm"
                    :href="route('okr.okrs.show', ['okr' => $cycle->okr_id])" 
                    wire:navigate
                >
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span class="ml-1">{{ $cycle->okr->title }}</span>
                </x-ui-button>
                @if($this->isDirty)
                    <x-ui-button 
                        variant="secondary" 
                        size="sm"
                        wire:click="save"
                    >
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-check', 'w-4 h-4')
                            Speichern
                        </div>
                    </x-ui-button>
                @endif
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="content">
        <div class="flex h-full">
            <!-- Linke Spalte -->
            <div class="flex-grow-1 flex flex-col">

                <!-- Haupt-Content (nimmt Restplatz, scrollt) -->
                <div class="flex-grow-1 overflow-y-auto p-4">
                    
                    @if(session()->has('message'))
                        <div class="mb-4 p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                            <p class="text-[var(--ui-secondary)]">{{ session('message') }}</p>
                        </div>
                    @endif

                    @if(session()->has('error'))
                        <div class="mb-4 p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                            <p class="text-[var(--ui-secondary)] font-medium">Fehler:</p>
                            <p class="text-[var(--ui-muted)]">{{ session('error') }}</p>
                        </div>
                    @endif
                
                {{-- Cycle Details --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-[var(--ui-secondary)]">Cycle Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Template</label>
                            <div class="text-sm text-[var(--ui-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Zeitraum</label>
                            <div class="text-sm text-[var(--ui-muted)]">
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Objectives & Key Results</h3>
                        <div class="flex gap-2">
                            <x-ui-button size="sm" variant="secondary" wire:click="addObjective">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Objective hinzufügen
                                </div>
                            </x-ui-button>
                        </div>
                    </div>

                    @if($cycle->objectives->count() > 0)
                        <div wire:sortable="updateObjectiveOrder" wire:sortable-group="updateKeyResultOrder" wire:sortable.options="{ animation: 150 }">
                            @foreach($cycle->objectives->sortBy('order') as $objective)
                                <div wire:sortable.item="{{ $objective->id }}" wire:key="objective-{{ $objective->id }}" class="mb-4 p-4 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-muted-5)]">
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex-grow-1">
                                            <div class="flex items-center gap-2">
                                                <div class="font-medium text-lg text-[var(--ui-secondary)]">{{ $objective->title }}</div>
                                                <x-ui-badge variant="secondary" size="xs">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                            </div>
                                            @if($objective->description)
                                                <div class="text-sm text-[var(--ui-muted)] mt-1">{{ Str::limit($objective->description, 100) }}</div>
                                            @endif
                                        </div>
                                        <div class="flex gap-2">
                                            <x-ui-button 
                                                size="sm" 
                                                variant="secondary" 
                                                wire:click="addKeyResult({{ $objective->id }})"
                                            >
                                                <div class="flex items-center gap-1">
                                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                                    KR hinzufügen
                                                </div>
                                            </x-ui-button>
                                            <x-ui-button 
                                                size="sm" 
                                                variant="secondary-ghost" 
                                                wire:click="editObjective({{ $objective->id }})"
                                            >
                                                @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                            </x-ui-button>
                                            <div wire:sortable.handle class="cursor-move p-2 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]">
                                                @svg('heroicon-o-bars-3', 'w-4 h-4')
                                            </div>
                                        </div>
                                    </div>

                                    @if($objective->keyResults->count() > 0)
                                        <div wire:sortable-group.item-group="{{ $objective->id }}" wire:sortable-group.options="{ animation: 100 }" class="space-y-2">
                                            @foreach($objective->keyResults->sortBy('order') as $keyResult)
                                                <div wire:sortable-group.item="{{ $keyResult->id }}" wire:key="keyresult-{{ $keyResult->id }}" 
                                                     class="flex items-center justify-between gap-3 p-3 bg-[var(--ui-muted-5)] rounded border border-[var(--ui-border)]">
                                                    <div class="flex items-start gap-2 flex-grow-1">
                                                        <div wire:sortable-group.handle class="cursor-move p-1 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] mt-0.5">
                                                            @svg('heroicon-o-bars-3', 'w-3 h-3')
                                                        </div>
                                                        <div class="flex-grow-1 cursor-pointer" wire:click="editKeyResult({{ $keyResult->id }})">
                                                            <div class="font-medium text-sm text-[var(--ui-secondary)]">{{ $keyResult->title }}</div>
                                                            @if($keyResult->description)
                                                                <div class="text-xs text-[var(--ui-muted)]">{{ Str::limit($keyResult->description, 60) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        @php
                                                            $type = $keyResult->performance?->type;
                                                        @endphp
                                                        <x-ui-badge variant="secondary" size="xs">
                                                            {{ $type ? ucfirst($type) : 'Ohne Typ' }}
                                                        </x-ui-badge>

                                                        <x-ui-badge variant="secondary" size="xs">
                                                            Ziel: 
                                                            @if($keyResult->performance)
                                                                {{ $keyResult->performance->target_value }}@if($keyResult->performance->type === 'percentage') % @endif
                                                            @else
                                                                –
                                                            @endif
                                                        </x-ui-badge>

                                                        <x-ui-badge variant="secondary" size="xs">
                                                            @if($keyResult->performance)
                                                                @if($keyResult->performance->type === 'boolean')
                                                                    {{ $keyResult->performance->is_completed ? 'Erledigt' : 'Offen' }}
                                                                @else
                                                                    Aktuell: {{ $keyResult->performance->current_value }}@if($keyResult->performance->type === 'percentage') % @endif
                                                                @endif
                                                            @else
                                                                Aktuell: –
                                                            @endif
                                                        </x-ui-badge>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center p-8 text-[var(--ui-muted)]">
                                            <div class="text-sm">Keine Key Results</div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center p-8 text-[var(--ui-muted)]">
                            <div class="text-lg mb-2">Noch keine Objectives vorhanden</div>
                            <div class="text-sm">Klicken Sie auf "Objective hinzufügen" um zu beginnen</div>
                        </div>
                    @endif
            </div>
                </div>

                <!-- Aktivitäten (immer unten) -->
                <div x-data="{ open: false }" class="flex-shrink-0 border-t border-[var(--ui-border)]">
                    <div 
                        @click="open = !open" 
                        class="cursor-pointer border-t border-b border-[var(--ui-border)] p-2 text-center flex items-center justify-center gap-1 mx-2 shadow-lg"
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
            <div class="min-w-80 w-80 flex flex-col border-l border-[var(--ui-border)]">

                <div class="flex gap-2 border-t border-b border-[var(--ui-border)] p-2 flex-shrink-0">
                    <x-heroicon-o-cog-6-tooth class="w-6 h-6"/>
                    Einstellungen
                </div>
                <div class="flex-grow-1 overflow-y-auto p-4">

                    {{-- Navigation Buttons --}}
                    <div class="flex flex-col gap-2 mb-4">
                        <x-ui-button 
                            variant="secondary" 
                            size="md" 
                            :href="route('okr.okrs.show', ['okr' => $cycle->okr_id])" 
                            wire:navigate
                            class="w-full"
                        >
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                Zurück zu OKR
                            </div>
                        </x-ui-button>
                    </div>

                    {{-- Kurze Übersicht --}}
                    <div class="mb-4 p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                        <h4 class="font-semibold mb-2 text-[var(--ui-secondary)]">Cycle-Übersicht</h4>
                        <div class="space-y-1 text-sm">
                            <div><strong class="text-[var(--ui-secondary)]">Template:</strong> <span class="text-[var(--ui-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</span></div>
                            @if($cycle->template)
                                <div><strong class="text-[var(--ui-secondary)]">Zeitraum:</strong> <span class="text-[var(--ui-muted)]">{{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}</span></div>
                            @endif
                            <div><strong class="text-[var(--ui-secondary)]">Objectives:</strong> <span class="text-[var(--ui-muted)]">{{ $cycle->objectives->count() }}</span></div>
                            <div><strong class="text-[var(--ui-secondary)]">Key Results:</strong> <span class="text-[var(--ui-muted)]">{{ $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()) }}</span></div>
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
                <div class="flex justify-end gap-2">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-ghost" 
                        wire:click="closeObjectiveCreateModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="secondary" wire:click="saveObjective">
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
                <div class="flex justify-between items-center gap-4">
                    <div class="flex-shrink-0">
                        <x-ui-confirm-button 
                            action="deleteObjectiveAndCloseModal" 
                            text="Löschen" 
                            confirmText="Wirklich löschen?" 
                            variant="secondary-ghost"
                            :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                        />
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <x-ui-button 
                            type="button" 
                            variant="secondary-ghost" 
                            wire:click="closeObjectiveEditModal"
                        >
                            Abbrechen
                        </x-ui-button>
                        <x-ui-button type="button" variant="secondary" wire:click="saveObjective">
                            Speichern
                        </x-ui-button>
                    </div>
                </div>
            </x-slot>
        </x-ui-modal>

    <!-- Key Result Create Modal -->
    <x-ui-modal
        size="lg"
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

            <x-ui-input-select
                name="keyResultValueType"
                label="Wert-Typ"
                :options="[
                    'absolute' => 'Absolut (z.B. 100 Stück, 50.000€)',
                    'percentage' => 'Prozent (z.B. 80%, 15%)',
                    'boolean' => 'Ja/Nein (z.B. Erledigt, Implementiert)'
                ]"
                :nullable="false"
                wire:model.live="keyResultValueType"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="keyResultTargetValue"
                    label="Zielwert"
                    wire:model.live="keyResultTargetValue"
                    :placeholder="match($keyResultValueType) {
                        'percentage' => 'z.B. 80',
                        'boolean' => 'z.B. Ja oder Nein',
                        'absolute' => 'z.B. 100',
                        default => 'Zielwert eingeben...'
                    }"
                    required
                />

                <x-ui-input-text
                    name="keyResultCurrentValue"
                    label="Aktueller Wert"
                    wire:model.live="keyResultCurrentValue"
                    :placeholder="match($keyResultValueType) {
                        'percentage' => 'z.B. 45',
                        'boolean' => 'z.B. Nein',
                        'absolute' => 'z.B. 60',
                        default => 'Aktueller Wert (optional)'
                    }"
                />
            </div>

            @if($keyResultValueType === 'absolute')
                <x-ui-input-text
                    name="keyResultUnit"
                    label="Einheit"
                    wire:model.live="keyResultUnit"
                    placeholder="z.B. Stück, €, Kunden, etc."
                />
            @endif

            @if($keyResultValueType === 'boolean')
                <div class="p-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                    <div class="text-sm text-[var(--ui-secondary)]">
                        <strong>Boolean-Werte:</strong> Verwende "Ja", "Nein", "Erledigt", "Nicht erledigt", "Implementiert", etc.
                    </div>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-ghost" 
                    wire:click="closeKeyResultCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button 
                    type="button" 
                    variant="secondary" 
                    wire:click="saveKeyResult"
                >
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

            <x-ui-input-select
                name="keyResultValueType"
                label="Wert-Typ"
                :options="[
                    'absolute' => 'Absolut (z.B. 100 Stück, 50.000€)',
                    'percentage' => 'Prozent (z.B. 80%, 15%)',
                    'boolean' => 'Ja/Nein (z.B. Erledigt, Implementiert)'
                ]"
                :nullable="false"
                wire:model.live="keyResultValueType"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="keyResultTargetValue"
                    label="Zielwert"
                    wire:model.live="keyResultTargetValue"
                    :placeholder="match($keyResultValueType) {
                        'percentage' => 'z.B. 80',
                        'boolean' => 'z.B. Ja oder Nein',
                        'absolute' => 'z.B. 100',
                        default => 'Zielwert eingeben...'
                    }"
                    required
                />

                <x-ui-input-text
                    name="keyResultCurrentValue"
                    label="Aktueller Wert"
                    wire:model.live="keyResultCurrentValue"
                    :placeholder="match($keyResultValueType) {
                        'percentage' => 'z.B. 45',
                        'boolean' => 'z.B. Nein',
                        'absolute' => 'z.B. 60',
                        default => 'Aktueller Wert (optional)'
                    }"
                />
            </div>

            @if($keyResultValueType === 'absolute')
                <x-ui-input-text
                    name="keyResultUnit"
                    label="Einheit"
                    wire:model.live="keyResultUnit"
                    placeholder="z.B. Stück, €, Kunden, etc."
                />
            @endif

            @if($keyResultValueType === 'boolean')
                <div class="p-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                    <div class="text-sm text-[var(--ui-secondary)]">
                        <strong>Boolean-Werte:</strong> Verwende "Ja", "Nein", "Erledigt", "Nicht erledigt", "Implementiert", etc.
                    </div>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-ghost" 
                    wire:click="closeKeyResultEditModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button 
                    type="button" 
                    variant="secondary" 
                    wire:click="saveKeyResult"
                >
                    Speichern
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
    </x-slot>
</x-ui-page>
