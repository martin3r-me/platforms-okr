<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Strategische Dokumente'],
        ]" />
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Info Box --}}
        <div class="bg-[var(--nx-info)]/10 border border-[var(--nx-info)]/30 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-[color:var(--nx-info)] mb-2">Über strategische Dokumente</h3>
            <p class="text-sm text-[color:var(--nx-info)] mb-4">
                Strategische Dokumente (Mission, Vision) dienen der Orientierung und sind nicht Teil der operativen Zielsteuerung-Messung.
                Sie können versioniert werden und werden in Zielsteuerung-Zyklen als Referenz angezeigt.
            </p>
        </div>

        {{-- Mission Section --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[color:var(--nx-info)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">🧭 Mission</h3>
                        <p class="text-sm text-[var(--nx-muted)]">{{ $this->getTypeDescription('mission') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-nx-button variant="ghost" size="sm" wire:click="openViewVersionsModal('mission')">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="ml-1">Versionen</span>
                    </x-nx-button>
                    <x-nx-button variant="primary" size="sm" wire:click="openCreateModal('mission')">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">{{ $this->mission ? 'Neue Version' : 'Erstellen' }}</span>
                    </x-nx-button>
                </div>
            </div>

            @if($this->mission)
                <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-[var(--nx-text)] mb-1">{{ $this->mission->title }}</h4>
                            <p class="text-sm text-[var(--nx-muted)]">
                                Version {{ $this->mission->version }} • Aktiv seit {{ $this->mission->valid_from->format('d.m.Y') }}
                            </p>
                        </div>
                        <x-nx-button variant="ghost" size="sm" wire:click="openEditModal({{ $this->mission->id }})">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-nx-button>
                    </div>
                    <div class="prose prose-sm max-w-none text-[var(--nx-text)]">
                        {!! \Illuminate\Support\Str::markdown($this->mission->content ?? '') !!}
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                    <p class="text-[var(--nx-muted)] mb-4">Noch keine Mission definiert</p>
                    <x-nx-button variant="primary" wire:click="openCreateModal('mission')">
                        Mission erstellen
                    </x-nx-button>
                </div>
            @endif
        </div>

        {{-- Vision Section --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[color:var(--nx-tone-violet)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-sun', 'w-6 h-6')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">🌄 Vision</h3>
                        <p class="text-sm text-[var(--nx-muted)]">{{ $this->getTypeDescription('vision') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-nx-button variant="ghost" size="sm" wire:click="openViewVersionsModal('vision')">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span class="ml-1">Versionen</span>
                    </x-nx-button>
                    <x-nx-button variant="primary" size="sm" wire:click="openCreateModal('vision')">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">{{ $this->vision ? 'Neue Version' : 'Erstellen' }}</span>
                    </x-nx-button>
                </div>
            </div>

            @if($this->vision)
                <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-[var(--nx-text)] mb-1">{{ $this->vision->title }}</h4>
                            <p class="text-sm text-[var(--nx-muted)]">
                                Version {{ $this->vision->version }} • Aktiv seit {{ $this->vision->valid_from->format('d.m.Y') }}
                            </p>
                        </div>
                        <x-nx-button variant="ghost" size="sm" wire:click="openEditModal({{ $this->vision->id }})">
                            @svg('heroicon-o-pencil', 'w-4 h-4')
                        </x-nx-button>
                    </div>
                    <div class="prose prose-sm max-w-none text-[var(--nx-text)]">
                        {!! \Illuminate\Support\Str::markdown($this->vision->content ?? '') !!}
                    </div>
                </div>
            @else
                <div class="text-center py-12 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                    <p class="text-[var(--nx-muted)] mb-4">Noch keine Vision definiert</p>
                    <x-nx-button variant="primary" wire:click="openCreateModal('vision')">
                        Vision erstellen
                    </x-nx-button>
                </div>
            @endif
        </div>

    </x-ui-page-container>

    {{-- Create Modal --}}
    <x-nx-modal wire:model="createModalShow">
        <x-slot name="header">Strategisches Dokument erstellen</x-slot>
        <div class="space-y-4">
            <x-nx-input-select
                name="form.type"
                label="Typ"
                :options="['mission' => 'Mission', 'vision' => 'Vision']"
                wire:model="form.type"
            />
            <x-nx-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
                placeholder="z.B. Vision 2030"
            />
            <x-nx-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
                placeholder="Beschreibung des strategischen Dokuments..."
            />
            <x-nx-input-text
                name="form.valid_from"
                label="Gültig ab"
                type="date"
                wire:model="form.valid_from"
            />
            <x-nx-input-textarea
                name="form.change_note"
                label="Änderungsgrund (optional)"
                wire:model="form.change_note"
                rows="2"
                placeholder="Kurze Beschreibung der Änderung..."
            />
            <x-nx-input-checkbox
                model="form.is_active"
                checked-label="Als aktive Version setzen"
            />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="closeModals">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="createDocument">Erstellen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- Edit Modal --}}
    <x-nx-modal wire:model="editModalShow">
        <x-slot name="header">Dokument bearbeiten</x-slot>
        <div class="space-y-4">
            <x-nx-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
            />
            <x-nx-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
            />
            <x-nx-input-textarea
                name="form.change_note"
                label="Änderungsgrund (optional)"
                wire:model="form.change_note"
                rows="2"
                placeholder="Kurze Beschreibung der Änderung..."
            />
            <div class="bg-[var(--nx-warning)]/10 border border-[var(--nx-warning)]/30 rounded-lg p-4">
                <p class="text-sm text-[color:var(--nx-warning)]">
                    <strong>Hinweis:</strong> Wenn Sie Titel oder Inhalt ändern, wird automatisch eine neue Version erstellt.
                </p>
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="closeModals">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="updateDocument">Speichern</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- New Version Modal --}}
    <x-nx-modal wire:model="versionModalShow">
        <x-slot name="header">Neue Version erstellen</x-slot>
        <div class="space-y-4">
            <x-nx-input-text
                name="form.title"
                label="Titel"
                wire:model="form.title"
            />
            <x-nx-input-textarea
                name="form.content"
                label="Inhalt (Markdown)"
                wire:model="form.content"
                rows="8"
            />
            <x-nx-input-text
                name="form.valid_from"
                label="Gültig ab"
                type="date"
                wire:model="form.valid_from"
            />
            <x-nx-input-textarea
                name="form.change_note"
                label="Änderungsgrund"
                wire:model="form.change_note"
                rows="2"
                placeholder="Warum wird diese neue Version erstellt?"
            />
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-nx-button variant="ghost" wire:click="closeModals">Abbrechen</x-nx-button>
                <x-nx-button variant="primary" wire:click="createNewVersion">Neue Version erstellen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- View Versions Modal --}}
    <x-nx-modal wire:model="viewVersionsModalShow" size="lg">
        <x-slot name="header">Versionen: {{ $this->getTypeLabel($viewingVersionsType) }}</x-slot>
        <div class="space-y-4">
            @if(count($versions) > 0)
                @foreach($versions as $version)
                    <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4 {{ $version->is_active ? 'ring-2 ring-[var(--nx-info)]/30' : '' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-[var(--nx-text)]">{{ $version->title }}</h4>
                                <p class="text-sm text-[var(--nx-muted)]">
                                    Version {{ $version->version }} • Gültig ab {{ $version->valid_from->format('d.m.Y') }}
                                    @if($version->is_active)
                                        <span class="ml-2 px-2 py-1 bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded text-xs font-medium">Aktiv</span>
                                    @endif
                                </p>
                                @if($version->change_note)
                                    <p class="text-sm text-[var(--nx-muted)] mt-1 italic">{{ $version->change_note }}</p>
                                @endif
                            </div>
                            @if(!$version->is_active)
                                <x-nx-button variant="ghost" size="sm" wire:click="activateVersion({{ $version->id }})">
                                    Aktivieren
                                </x-nx-button>
                            @endif
                        </div>
                        <div class="prose prose-sm max-w-none text-[var(--nx-text)] mt-2">
                            {!! \Illuminate\Support\Str::markdown($version->content ?? '') !!}
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8 text-[var(--nx-muted)]">
                    Keine Versionen vorhanden
                </div>
            @endif
        </div>
        <x-slot name="footer">
            <x-nx-button variant="ghost" wire:click="closeModals">Schließen</x-nx-button>
        </x-slot>
    </x-nx-modal>
</x-ui-page>

