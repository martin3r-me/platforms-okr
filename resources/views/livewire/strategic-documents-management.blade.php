<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Strategische Dokumente" />
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                <p class="text-[var(--ui-secondary)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Info Box --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">Über strategische Dokumente</h3>
            <p class="text-sm text-blue-800 mb-4">
                Strategische Dokumente (Mission, Vision, Regnose) dienen der Orientierung und sind nicht Teil der operativen OKR-Messung. 
                Sie können versioniert werden und werden in OKR-Zyklen als Referenz angezeigt.
            </p>
        </div>

        {{-- Mission Section --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-compass', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">🧭 Mission</h3>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $this->getTypeDescription('mission') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui-button variant="secondary-ghost" size="sm" wire:click="openViewVersionsModal('mission')">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="ml-1">Versionen</span>
                    </x-ui-button>
                    <x-ui-button variant="primary" size="sm" wire:click="openCreateModal('mission')">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">{{ $this->mission ? 'Neue Version' : 'Erstellen' }}</span>
                    </x-ui-button>
                </div>
            </div>

            @if($this->mission)
                <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-[var(--ui-secondary)] mb-1">{{ $this->mission->title }}</h4>
                            <p class="text-sm text-[var(--ui-muted)]">
                                Version {{ $this->mission->version }} • Aktiv seit {{ $this->mission->valid_from->format('d.m.Y') }}
                            </p>
                        </div>
                        <x-ui-button variant="secondary-ghost" size="sm" wire:click="openEditModal({{ $this->mission->id }})">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-ui-button>
                    </div>
                    <div class="prose prose-sm max-w-none text-[var(--ui-secondary)]">
                        {!! \Illuminate\Support\Str::markdown($this->mission->content ?? '') !!}
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-[var(--ui-muted)] mb-4">Noch keine Mission definiert</p>
                    <x-ui-button variant="primary" wire:click="openCreateModal('mission')">
                        Mission erstellen
                    </x-ui-button>
                </div>
            @endif
        </div>

        {{-- Vision Section --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-500 text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-sun', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">🌄 Vision</h3>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $this->getTypeDescription('vision') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui-button variant="secondary-ghost" size="sm" wire:click="openViewVersionsModal('vision')">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="ml-1">Versionen</span>
                    </x-ui-button>
                    <x-ui-button variant="primary" size="sm" wire:click="openCreateModal('vision')">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">{{ $this->vision ? 'Neue Version' : 'Erstellen' }}</span>
                    </x-ui-button>
                </div>
            </div>

            @if($this->vision)
                <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-[var(--ui-secondary)] mb-1">{{ $this->vision->title }}</h4>
                            <p class="text-sm text-[var(--ui-muted)]">
                                Version {{ $this->vision->version }} • Aktiv seit {{ $this->vision->valid_from->format('d.m.Y') }}
                            </p>
                        </div>
                        <x-ui-button variant="secondary-ghost" size="sm" wire:click="openEditModal({{ $this->vision->id }})">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-ui-button>
                    </div>
                    <div class="prose prose-sm max-w-none text-[var(--ui-secondary)]">
                        {!! \Illuminate\Support\Str::markdown($this->vision->content ?? '') !!}
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-[var(--ui-muted)] mb-4">Noch keine Vision definiert</p>
                    <x-ui-button variant="primary" wire:click="openCreateModal('vision')">
                        Vision erstellen
                    </x-ui-button>
                </div>
            @endif
        </div>

        {{-- Regnose Section --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500 text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-sparkles', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">🔮 Regnose (Strategic Outlook)</h3>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $this->getTypeDescription('regnose') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui-button variant="secondary-ghost" size="sm" wire:click="openViewVersionsModal('regnose')">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="ml-1">Versionen</span>
                    </x-ui-button>
                    <x-ui-button variant="primary" size="sm" wire:click="openCreateModal('regnose')">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">{{ $this->regnose ? 'Neue Version' : 'Erstellen' }}</span>
                    </x-ui-button>
                </div>
            </div>

            @if($this->regnose)
                <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-[var(--ui-secondary)] mb-1">{{ $this->regnose->title }}</h4>
                            <p class="text-sm text-[var(--ui-muted)]">
                                Version {{ $this->regnose->version }} • Aktiv seit {{ $this->regnose->valid_from->format('d.m.Y') }}
                            </p>
                        </div>
                        <x-ui-button variant="secondary-ghost" size="sm" wire:click="openEditModal({{ $this->regnose->id }})">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-ui-button>
                    </div>
                    <div class="prose prose-sm max-w-none text-[var(--ui-secondary)]">
                        {!! \Illuminate\Support\Str::markdown($this->regnose->content ?? '') !!}
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                    <p class="text-[var(--ui-muted)] mb-4">Noch keine Regnose definiert</p>
                    <x-ui-button variant="primary" wire:click="openCreateModal('regnose')">
                        Regnose erstellen
                    </x-ui-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Create Modal --}}
    <x-ui-modal wire:model="createModalShow">
        <x-slot name="header">Strategisches Dokument erstellen</x-slot>
        <div class="space-y-4">
            <x-ui-input-select
                name="form.type"
                label="Typ"
                :options="['mission' => 'Mission', 'vision' => 'Vision', 'regnose' => 'Regnose']"
                wire:model="form.type"
            />
            <x-ui-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
                placeholder="z.B. Vision 2030"
            />
            <x-ui-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
                placeholder="Beschreibung des strategischen Dokuments..."
            />
            <x-ui-input-text
                name="form.valid_from"
                label="Gültig ab"
                type="date"
                wire:model="form.valid_from"
            />
            <x-ui-input-textarea
                name="form.change_note"
                label="Änderungsgrund (optional)"
                wire:model="form.change_note"
                rows="2"
                placeholder="Kurze Beschreibung der Änderung..."
            />
            <x-ui-input-checkbox
                model="form.is_active"
                checked-label="Als aktive Version setzen"
            />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-ui-button variant="secondary-ghost" wire:click="closeModals">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" wire:click="createDocument">Erstellen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Edit Modal --}}
    <x-ui-modal wire:model="editModalShow">
        <x-slot name="header">Dokument bearbeiten</x-slot>
        <div class="space-y-4">
            <x-ui-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
            />
            <x-ui-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
            />
            <x-ui-input-textarea
                name="form.change_note"
                label="Änderungsgrund (optional)"
                wire:model="form.change_note"
                rows="2"
                placeholder="Kurze Beschreibung der Änderung..."
            />
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-sm text-yellow-800">
                    <strong>Hinweis:</strong> Wenn Sie Titel oder Inhalt ändern, wird automatisch eine neue Version erstellt.
                </p>
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-ui-button variant="secondary-ghost" wire:click="closeModals">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" wire:click="updateDocument">Speichern</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- New Version Modal --}}
    <x-ui-modal wire:model="versionModalShow">
        <x-slot name="header">Neue Version erstellen</x-slot>
        <div class="space-y-4">
            <x-ui-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
            />
            <x-ui-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
            />
            <x-ui-input-text
                name="form.valid_from"
                label="Gültig ab"
                type="date"
                wire:model="form.valid_from"
            />
            <x-ui-input-textarea
                name="form.change_note"
                label="Änderungsgrund"
                wire:model="form.change_note"
                rows="2"
                placeholder="Warum wird diese neue Version erstellt?"
            />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-ui-button variant="secondary-ghost" wire:click="closeModals">Abbrechen</x-ui-button>
                <x-ui-button variant="primary" wire:click="createNewVersion">Neue Version erstellen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- View Versions Modal --}}
    <x-ui-modal wire:model="viewVersionsModalShow" size="lg">
        <x-slot name="header">Versionen: {{ $this->getTypeLabel($viewingVersionsType) }}</x-slot>
        <div class="space-y-4">
            @if(count($versions) > 0)
                @foreach($versions as $version)
                    <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4 {{ $version->is_active ? 'ring-2 ring-blue-500' : '' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-[var(--ui-secondary)]">{{ $version->title }}</h4>
                                <p class="text-sm text-[var(--ui-muted)]">
                                    Version {{ $version->version }} • Gültig ab {{ $version->valid_from->format('d.m.Y') }}
                                    @if($version->is_active)
                                        <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">Aktiv</span>
                                    @endif
                                </p>
                                @if($version->change_note)
                                    <p class="text-sm text-[var(--ui-muted)] mt-1 italic">{{ $version->change_note }}</p>
                                @endif
                            </div>
                            @if(!$version->is_active)
                                <x-ui-button variant="secondary-ghost" size="sm" wire:click="activateVersion({{ $version->id }})">
                                    Aktivieren
                                </x-ui-button>
                            @endif
                        </div>
                        <div class="prose prose-sm max-w-none text-[var(--ui-secondary)] mt-2">
                            {!! \Illuminate\Support\Str::markdown($version->content ?? '') !!}
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8 text-[var(--ui-muted)]">
                    Keine Versionen vorhanden
                </div>
            @endif
        </div>
        <x-slot name="footer">
            <x-ui-button variant="secondary-ghost" wire:click="closeModals">Schließen</x-ui-button>
        </x-slot>
    </x-ui-modal>
</x-ui-page>

