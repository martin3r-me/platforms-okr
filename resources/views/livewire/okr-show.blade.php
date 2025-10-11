<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <div class="flex items-center gap-2">
                <x-ui-button 
                    variant="secondary-ghost" 
                    size="sm"
                    :href="route('okr.okrs.index')" 
                    wire:navigate
                >
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span class="ml-1">OKRs</span>
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
                    
                    {{-- OKR Details --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-[var(--ui-secondary)]">OKR Details</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="okr.title"
                        label="Titel"
                        wire:model.live.debounce.500ms="okr.title"
                        placeholder="Titel des OKR eingeben..."
                        required
                        :errorKey="'okr.title'"
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
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <x-ui-input-number
                        name="okr.performance_score"
                        label="Performance Score (%)"
                        wire:model.live.debounce.500ms="okr.performance_score"
                        min="0"
                        max="100"
                        :errorKey="'okr.performance_score'"
                    />
                    <div class="d-flex items-center gap-4">
                        <x-ui-input-checkbox
                            model="okr.auto_transfer"
                            checked-label="Automatisch übertragen"
                        />
                        <x-ui-input-checkbox
                            model="okr.is_template"
                            checked-label="Als Template speichern"
                        />
                    </div>
                </div>
            </div>

                    {{-- Cycles --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 text-[var(--ui-secondary)]">Cycles</h3>
                        <div class="space-y-2">
                            @foreach($okr->cycles as $cycle)
                                <div class="flex items-center gap-2 p-2 bg-[var(--ui-muted-5)] rounded cursor-pointer border border-[var(--ui-border)]/40" wire:click="manageCycleObjectives({{ $cycle->id }})">
                                    <div class="flex-grow-1">
                                        <div class="text-sm font-medium text-[var(--ui-secondary)]">
                                            {{ $cycle->template?->label ?? 'Unbekannter Cycle' }}
                                        </div>
                                        <div class="text-xs text-[var(--ui-muted)]">
                                            {{ $cycle->template?->starts_at?->format('d.m.Y') }} - {{ $cycle->template?->ends_at?->format('d.m.Y') }}
                                            @if($cycle->objectives->count() > 0)
                                                • {{ $cycle->objectives->count() }} Objectives
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-ui-badge variant="secondary" size="xs">{{ ucfirst($cycle->status) }}</x-ui-badge>
                                        <x-ui-button 
                                            size="xs" 
                                            variant="secondary-ghost" 
                                            wire:click.stop="editCycle({{ $cycle->id }})"
                                        >
                                            @svg('heroicon-o-cog-6-tooth', 'w-3 h-3')
                                        </x-ui-button>
                                    </div>
                                </div>
                            @endforeach
                            @if($okr->cycles->count() === 0)
                                <p class="text-sm text-[var(--ui-muted)]">Noch keine Cycles vorhanden.</p>
                            @endif
                            <x-ui-button size="sm" variant="secondary" wire:click="addCycle">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Cycle hinzufügen
                                </div>
                            </x-ui-button>
                        </div>
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
                                {{$okr->activities->count()}}
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
                                :model="$okr"
                                :key="get_class($okr) . '_' . $okr->id"
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
                                :href="route('okr.okrs.index')" 
                                wire:navigate
                                class="w-full"
                            >
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                    Zurück zu OKRs
                                </div>
                            </x-ui-button>
                        </div>

                        {{-- Kurze Übersicht --}}
                        <div class="mb-4 p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <h4 class="font-semibold mb-2 text-[var(--ui-secondary)]">OKR-Übersicht</h4>
                            <div class="space-y-1 text-sm">
                                <div><strong class="text-[var(--ui-secondary)]">Titel:</strong> <span class="text-[var(--ui-muted)]">{{ $okr->title }}</span></div>
                                @if($okr->manager)
                                    <div><strong class="text-[var(--ui-secondary)]">Manager:</strong> <span class="text-[var(--ui-muted)]">{{ $okr->manager->name }}</span></div>
                                @endif
                                <div><strong class="text-[var(--ui-secondary)]">Verantwortlicher:</strong> <span class="text-[var(--ui-muted)]">{{ $okr->user->name }}</span></div>
                                @if($okr->performance_score !== null)
                                    <div><strong class="text-[var(--ui-secondary)]">Score:</strong> <span class="text-[var(--ui-muted)]">{{ $okr->performance_score }}%</span></div>
                                @endif
                                <div><strong class="text-[var(--ui-secondary)]">Cycles:</strong> <span class="text-[var(--ui-muted)]">{{ $okr->cycles->count() }}</span></div>
                            </div>
                        </div>

                        <hr>

                        {{-- Teilnehmer verwalten --}}
                        <div class="mt-4">
                            <h4 class="font-semibold mb-2 text-[var(--ui-secondary)]">Teilnehmer</h4>
                            <div class="flex items-end gap-2 mb-3">
                                <div class="flex-grow-1">
                                    <x-ui-input-select
                                        name="memberUserId"
                                        label="Benutzer"
                                        :options="$this->users"
                                        optionValue="id"
                                        optionLabel="name"
                                        :nullable="true"
                                        nullLabel="– Benutzer wählen –"
                                        wire:model.live="memberUserId"
                                    />
                                </div>
                                <div class="min-w-40">
                                    <x-ui-input-select
                                        name="memberRole"
                                        label="Rolle"
                                        :options="['contributor' => 'Mitarbeit', 'viewer' => 'Lesend']"
                                        :nullable="false"
                                        wire:model.live="memberRole"
                                    />
                                </div>
                            </div>
                            <div class="mb-3">
                                <x-ui-button variant="secondary" size="sm" wire:click="addMember">Hinzufügen</x-ui-button>
                            </div>

                            @if($this->members->count() > 0)
                                <div class="space-y-2">
                                    @foreach($this->members as $member)
                                        <div class="flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded border border-[var(--ui-border)]/40 text-xs">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-[var(--ui-primary)] text-[var(--ui-on-primary)] flex items-center justify-center font-semibold">
                                                    {{ strtoupper(Str::substr($member->name, 0, 2)) }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $member->name }}</span>
                                                    <x-ui-badge variant="secondary" size="xs">
                                                        {{ $member->pivot->role === 'contributor' ? 'Mitarbeit' : 'Lesend' }}
                                                    </x-ui-badge>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <select
                                                    class="h-7 text-xs border border-[var(--ui-border)] rounded px-1 py-0.5 bg-[var(--ui-muted-5)]"
                                                    wire:change="updateMemberRole({{ $member->id }}, $event.target.value)"
                                                >
                                                    <option value="contributor" {{ $member->pivot->role === 'contributor' ? 'selected' : '' }}>Mitarbeit</option>
                                                    <option value="viewer" {{ $member->pivot->role === 'viewer' ? 'selected' : '' }}>Lesend</option>
                                                </select>
                                                <x-ui-button variant="secondary-ghost" size="xs" wire:click="removeMember({{ $member->id }})">
                                                    @svg('heroicon-o-x-mark', 'w-3 h-3')
                                                </x-ui-button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-xs text-[var(--ui-muted)]">Noch keine Teilnehmer hinzugefügt.</div>
                            @endif
                        </div>

                        <hr>

                    </div>
                </div>

    <!-- Create Cycle Modal -->
    <x-ui-modal
        size="lg"
        model="cycleCreateModalShow"
    >
        <x-slot name="header">
            Cycle hinzufügen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveCycle" class="space-y-4">
                <x-ui-input-select
                    name="cycleForm.cycle_template_id"
                    label="Cycle Template auswählen"
                    :options="$this->cycleTemplates"
                    optionValue="id"
                    optionLabel="label"
                    :nullable="true"
                    nullLabel="– Template auswählen –"
                    wire:model.live="cycleForm.cycle_template_id"
                    required
                />

                <x-ui-input-select
                    name="cycleForm.status"
                    label="Status"
                    :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'ending_soon' => 'Endet bald', 'past' => 'Vergangen']"
                    :nullable="false"
                    wire:model.live="cycleForm.status"
                    required
                />

                <x-ui-input-textarea
                    name="cycleForm.notes"
                    label="Notizen"
                    wire:model.live="cycleForm.notes"
                    placeholder="Zusätzliche Notizen zum Cycle (optional)"
                    rows="3"
                />
            </form>
        </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-ghost" 
                        wire:click="closeCycleCreateModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="secondary" wire:click="saveCycle">
                        Hinzufügen
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>

        <!-- Edit Cycle Modal -->
        <x-ui-modal
            size="lg"
            model="cycleEditModalShow"
        >
            <x-slot name="header">
                Cycle bearbeiten
            </x-slot>

            <div class="space-y-4">
                <form wire:submit.prevent="saveCycle" class="space-y-4">
                    <x-ui-input-select
                        name="cycleForm.cycle_template_id"
                        label="Cycle Template auswählen"
                        :options="$this->cycleTemplates"
                        optionValue="id"
                        optionLabel="label"
                        :nullable="true"
                        nullLabel="– Template auswählen –"
                        wire:model.live="cycleForm.cycle_template_id"
                        required
                    />

                    <x-ui-input-select
                        name="cycleForm.status"
                        label="Status"
                        :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'ending_soon' => 'Endet bald', 'past' => 'Vergangen']"
                        :nullable="false"
                        wire:model.live="cycleForm.status"
                        required
                    />

                    <x-ui-input-textarea
                        name="cycleForm.notes"
                        label="Notizen"
                        wire:model.live="cycleForm.notes"
                        placeholder="Zusätzliche Notizen zum Cycle (optional)"
                        rows="3"
                    />
                </form>
            </div>

            <x-slot name="footer">
                <div class="flex justify-between items-center gap-4">
                    <div class="flex-shrink-0">
                        <x-ui-confirm-button 
                            action="deleteCycleAndCloseModal" 
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
                            wire:click="closeCycleEditModal"
                        >
                            Abbrechen
                        </x-ui-button>
                        <x-ui-button type="button" variant="secondary" wire:click="saveCycle">
                            Speichern
                        </x-ui-button>
                    </div>
                </div>
            </x-slot>
        </x-ui-modal>
    </x-slot>
</x-ui-page>