<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zukunftsbilder', 'href' => route('okr.forecasts.index')],
            ['label' => $focusArea->forecast->title, 'href' => route('okr.forecasts.show', $focusArea->forecast)],
            ['label' => $focusArea->title],
        ]">
            @if($this->isDirty)
                <x-nx-button variant="primary" size="sm" wire:click="save">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span>Speichern</span>
                </x-nx-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container width="contained" spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Focus Area Header --}}
        <div class="bg-gradient-to-r from-[var(--nx-bg)] to-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[var(--nx-accent)] text-white rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-viewfinder-circle', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--nx-text)] tracking-tight">{{ $focusArea->title }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)] mt-1">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-sparkles', 'w-4 h-4')
                                    <a href="{{ route('okr.forecasts.show', $focusArea->forecast) }}" wire:navigate class="hover:text-[var(--nx-accent)]">
                                        {{ $focusArea->forecast->title }}
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Mini Dashboard --}}
                    @php
                        $totalVisionImages = $focusArea->visionImages->count();
                        $totalObstacles = $focusArea->obstacles->count();
                        $totalMilestones = $focusArea->milestones->count();
                    @endphp
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalVisionImages }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Zielbilder</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalObstacles }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Hindernisse</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalMilestones }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Meilensteine</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Markdown Editor --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Beschreibung</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Markdown-Beschreibung zum Fokusraum</p>
                    </div>
                </div>
            </div>

            {{-- Bear/Obsidian-like Editor --}}
            <div
                x-data="{
                    editor: null,
                    isSaving: false,
                    savedLabel: '—',
                    debounceTimer: null,
                    boot() {
                        const Editor = window.ToastUIEditor;
                        if (!Editor) return false;

                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }

                        this.editor = new Editor({
                            el: this.$refs.editorEl,
                            height: '50vh',
                            initialEditType: 'wysiwyg',
                            previewStyle: 'tab',
                            hideModeSwitch: true,
                            usageStatistics: false,
                            placeholder: 'Schreibe die Beschreibung…  😀  / Überschriften, Listen, Checklists, Links, Code',
                            toolbarItems: [
                                ['heading', 'bold', 'italic', 'strike'],
                                ['ul', 'ol', 'task', 'quote'],
                                ['link', 'code', 'codeblock', 'hr'],
                            ],
                            initialValue: @js($content ?? ''),
                        });

                        // Sync Editor -> Livewire state (debounced, ohne DB-write)
                        this.editor.on('change', () => {
                            const md = this.editor.getMarkdown();
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                $wire.set('content', md, false);
                                this.savedLabel = 'Ungespeichert';
                            }, 900);
                        });

                        // Ctrl/Cmd + S
                        if (window.__focusAreaKeydownHandler) {
                            window.removeEventListener('keydown', window.__focusAreaKeydownHandler);
                        }
                        window.__focusAreaKeydownHandler = (e) => {
                            const isSave = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's';
                            if (!isSave) return;
                            e.preventDefault();
                            this.saveNow();
                        };
                        window.addEventListener('keydown', window.__focusAreaKeydownHandler);

                        return true;
                    },
                    init() {
                        if (!this.boot()) {
                            window.addEventListener('toastui:ready', () => this.boot(), { once: true });
                        }
                    },
                    saveNow() {
                        if (!this.editor) return;
                        this.isSaving = true;
                        const md = this.editor.getMarkdown();
                        $wire.set('content', md, false);
                        $wire.save();
                    },
                }"
                class="min-h-[50vh]"
            >
                <div class="flex items-center justify-end gap-3 mb-4">
                    <div class="text-xs text-[var(--nx-muted)]">
                        <span x-text="savedLabel"></span>
                        <span class="mx-1">·</span>
                        <span>⌘S</span>
                    </div>
                    <button
                        type="button"
                        @click="saveNow()"
                        class="px-3 py-1.5 text-sm rounded-lg border border-[color:var(--nx-line)] hover:bg-[var(--nx-bg)] transition-colors"
                    >
                        Speichern
                    </button>
                </div>

                <div class="focus-area-editor-shell">
                    <div wire:ignore x-ref="editorEl"></div>
                </div>
            </div>
        </div>

        {{-- Zielbilder --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[color:var(--nx-info)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-photo', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Zielbilder</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Vision Images für diesen Fokusraum</p>
                    </div>
                </div>
                <x-nx-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="addVisionImage"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Zielbild hinzufügen</span>
                </x-nx-button>
            </div>

            {{-- Zentrale Frage zu Zielbildern --}}
            <div class="mb-6 p-4 bg-[var(--nx-info)]/10 border border-[var(--nx-info)]/30 rounded-lg">
                <label class="block text-sm font-medium text-[color:var(--nx-info)] mb-2">
                    Zentrale Frage zu Zielbildern
                </label>
                <x-nx-input-textarea
                    name="centralQuestionVisionImages"
                    wire:model.live="centralQuestionVisionImages"
                    placeholder="Welche zentrale Frage soll bei den Zielbildern beantwortet werden?"
                    rows="2"
                    class="bg-[color:var(--nx-surface)]"
                />
            </div>

            @if($focusArea->visionImages->count() > 0)
                <div wire:sortable="updateVisionImageOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($focusArea->visionImages->sortBy('order') as $visionImage)
                        <div wire:sortable.item="{{ $visionImage->id }}" wire:key="visionimage-{{ $visionImage->id }}" class="mb-4 p-6 border border-[color:var(--nx-line)] rounded-lg bg-[var(--nx-bg)] hover:border-[color:var(--nx-line)] transition-colors">
                            <div class="flex justify-between items-center">
                                <div class="flex-grow-1">
                                    <div class="flex items-center gap-3">
                                        <div class="font-medium text-lg text-[var(--nx-text)]">{{ $visionImage->title }}</div>
                                        <x-nx-badge variant="neutral" size="sm">Order: {{ $visionImage->order }}</x-nx-badge>
                                    </div>
                                    @if($visionImage->description)
                                        <div class="text-sm text-[var(--nx-muted)] mt-2">{{ Str::limit($visionImage->description, 200) }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <x-nx-button 
                                        size="sm" 
                                        variant="ghost" 
                                        wire:click="editVisionImage({{ $visionImage->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-nx-button>
                                    <x-nx-button variant="ghost" size="sm" wire:click="deleteVisionImage({{ $visionImage->id }})" wire:confirm="Zielbild wirklich löschen?">Löschen</x-nx-button>
                                    <div wire:sortable.handle class="cursor-move p-2 text-[var(--nx-muted)] hover:text-[var(--nx-accent)]">
                                        @svg('heroicon-o-bars-3', 'w-4 h-4')
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-photo', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Zielbilder vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Klicken Sie auf \"Zielbild hinzufügen\" um zu beginnen</p>
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="addVisionImage"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erstes Zielbild hinzufügen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>

        {{-- Hindernisse --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[color:var(--nx-danger)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-exclamation-triangle', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Hindernisse</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Obstacles für diesen Fokusraum</p>
                    </div>
                </div>
                <x-nx-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="addObstacle"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Hindernis hinzufügen</span>
                </x-nx-button>
            </div>

            {{-- Zentrale Frage zu Hindernissen --}}
            <div class="mb-6 p-4 bg-[var(--nx-danger)]/10 border border-[var(--nx-danger)]/30 rounded-lg">
                <label class="block text-sm font-medium text-[color:var(--nx-danger)] mb-2">
                    Zentrale Frage zu Hindernissen
                </label>
                <x-nx-input-textarea
                    name="centralQuestionObstacles"
                    wire:model.live="centralQuestionObstacles"
                    placeholder="Welche zentrale Frage soll bei den Hindernissen beantwortet werden?"
                    rows="2"
                    class="bg-[color:var(--nx-surface)]"
                />
            </div>

            @if($focusArea->obstacles->count() > 0)
                <div wire:sortable="updateObstacleOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($focusArea->obstacles->sortBy('order') as $obstacle)
                        <div wire:sortable.item="{{ $obstacle->id }}" wire:key="obstacle-{{ $obstacle->id }}" class="mb-4 p-6 border border-[color:var(--nx-line)] rounded-lg bg-[var(--nx-bg)] hover:border-[color:var(--nx-line)] transition-colors">
                            <div class="flex justify-between items-center">
                                <div class="flex-grow-1">
                                    <div class="flex items-center gap-3">
                                        <div class="font-medium text-lg text-[var(--nx-text)]">{{ $obstacle->title }}</div>
                                        <x-nx-badge variant="neutral" size="sm">Order: {{ $obstacle->order }}</x-nx-badge>
                                    </div>
                                    @if($obstacle->description)
                                        <div class="text-sm text-[var(--nx-muted)] mt-2">{{ Str::limit($obstacle->description, 200) }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <x-nx-button 
                                        size="sm" 
                                        variant="ghost" 
                                        wire:click="editObstacle({{ $obstacle->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-nx-button>
                                    <x-nx-button variant="ghost" size="sm" wire:click="deleteObstacle({{ $obstacle->id }})" wire:confirm="Hindernis wirklich löschen?">Löschen</x-nx-button>
                                    <div wire:sortable.handle class="cursor-move p-2 text-[var(--nx-muted)] hover:text-[var(--nx-accent)]">
                                        @svg('heroicon-o-bars-3', 'w-4 h-4')
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-exclamation-triangle', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Hindernisse vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Klicken Sie auf \"Hindernis hinzufügen\" um zu beginnen</p>
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="addObstacle"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erstes Hindernis hinzufügen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>

        {{-- Meilensteine --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[color:var(--nx-success)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Meilensteine</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Milestones für diesen Fokusraum</p>
                    </div>
                </div>
                <x-nx-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="addMilestone"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Meilenstein hinzufügen</span>
                </x-nx-button>
            </div>

            {{-- Zentrale Frage zu Meilensteinen --}}
            <div class="mb-6 p-4 bg-[var(--nx-success)]/10 border border-[var(--nx-success)]/30 rounded-lg">
                <label class="block text-sm font-medium text-[color:var(--nx-success)] mb-2">
                    Zentrale Frage zu Meilensteinen
                </label>
                <x-nx-input-textarea
                    name="centralQuestionMilestones"
                    wire:model.live="centralQuestionMilestones"
                    placeholder="Welche zentrale Frage soll bei den Meilensteinen beantwortet werden?"
                    rows="2"
                    class="bg-[color:var(--nx-surface)]"
                />
            </div>

            @if($focusArea->milestones->count() > 0)
                <div wire:sortable="updateMilestoneOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($focusArea->milestones->sortBy('order') as $milestone)
                        <div wire:sortable.item="{{ $milestone->id }}" wire:key="milestone-{{ $milestone->id }}" class="mb-4 p-6 border border-[color:var(--nx-line)] rounded-lg bg-[var(--nx-bg)] hover:border-[color:var(--nx-line)] transition-colors">
                            <div class="flex justify-between items-center">
                                <div class="flex-grow-1">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <div class="font-medium text-lg text-[var(--nx-text)]">{{ $milestone->title }}</div>
                                        <x-nx-badge variant="neutral" size="sm">Order: {{ $milestone->order }}</x-nx-badge>
                                        @if($milestone->target_year)
                                            <x-nx-badge variant="primary" size="sm">
                                                {{ $milestone->target_year }}
                                                @if($milestone->target_quarter)
                                                    Q{{ $milestone->target_quarter }}
                                                @endif
                                            </x-nx-badge>
                                        @endif
                                    </div>
                                    @if($milestone->description)
                                        <div class="text-sm text-[var(--nx-muted)] mt-2">{{ Str::limit($milestone->description, 200) }}</div>
                                    @endif
                                    @if($milestone->objectives->count() > 0)
                                        <div class="mt-3 pt-3 border-t border-[color:var(--nx-line)]">
                                            <div class="text-xs font-medium text-[var(--nx-muted)] mb-1.5">Einzahlende Objectives:</div>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($milestone->objectives as $objective)
                                                    <a
                                                        href="{{ route('okr.objectives.show', $objective) }}"
                                                        wire:navigate
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[var(--nx-info)]/10 border border-[var(--nx-info)]/30 text-xs text-[color:var(--nx-info)] hover:bg-[var(--nx-info)]/10 transition-colors"
                                                    >
                                                        @svg('heroicon-o-flag', 'w-3 h-3')
                                                        <span>{{ Str::limit($objective->title, 40) }}</span>
                                                        @if($objective->cycle)
                                                            <span class="text-[color:var(--nx-info)]">{{ $objective->cycle->template?->label ?? '' }}</span>
                                                        @endif
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <x-nx-button 
                                        size="sm" 
                                        variant="ghost" 
                                        wire:click="editMilestone({{ $milestone->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-nx-button>
                                    <x-nx-button variant="ghost" size="sm" wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="Meilenstein wirklich löschen?">Löschen</x-nx-button>
                                    <div wire:sortable.handle class="cursor-move p-2 text-[var(--nx-muted)] hover:text-[var(--nx-accent)]">
                                        @svg('heroicon-o-bars-3', 'w-4 h-4')
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Meilensteine vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Klicken Sie auf \"Meilenstein hinzufügen\" um zu beginnen</p>
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="addMilestone"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Meilenstein hinzufügen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Fokusraum Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Focus Area Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Zukunftsbild</span>
                            <a href="{{ route('okr.forecasts.show', $focusArea->forecast) }}" wire:navigate class="text-sm text-[var(--nx-accent)] hover:underline">
                                {{ $focusArea->forecast->title }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Zielbilder</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $focusArea->visionImages->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Hindernisse</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $focusArea->obstacles->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Meilensteine</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $focusArea->milestones->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten & Timeline" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 space-y-6">
                {{-- Recent Activities --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Letzte Aktivitäten</h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                            <div class="w-8 h-8 bg-[var(--nx-accent)] text-white rounded-full flex items-center justify-center text-xs font-semibold">
                                @svg('heroicon-o-viewfinder-circle', 'w-4 h-4')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-[var(--nx-text)] text-sm">Fokusraum erstellt</div>
                                <div class="text-xs text-[var(--nx-muted)]">{{ $focusArea->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        @if($focusArea->visionImages->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-photo', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $focusArea->visionImages->count() }} Zielbilder hinzugefügt</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Letzte Änderung: {{ $focusArea->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endif

                        @if($focusArea->obstacles->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-danger)]/10 text-[color:var(--nx-danger)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-exclamation-triangle', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $focusArea->obstacles->count() }} Hindernisse definiert</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Potenzielle Blockaden identifiziert</div>
                                </div>
                            </div>
                        @endif

                        @if($focusArea->milestones->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-success)]/10 text-[color:var(--nx-success)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-flag', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $focusArea->milestones->count() }} Meilensteine gesetzt</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Wichtige Zwischenziele</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Schnellübersicht</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Zielbilder</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $focusArea->visionImages->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Hindernisse</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $focusArea->obstacles->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Meilensteine</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $focusArea->milestones->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Erstellt</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $focusArea->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <!-- VisionImage Create Modal -->
    <x-nx-modal size="lg" model="visionImageCreateModalShow">
        <x-slot name="header">Zielbild hinzufügen</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_vision_images)
                <div class="p-4 bg-[var(--nx-info)]/10 border border-[var(--nx-info)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-info)] mb-2">
                        Zentrale Frage zu Zielbildern
                    </label>
                    <p class="text-sm text-[color:var(--nx-info)]">{{ $focusArea->central_question_vision_images }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveVisionImage" class="space-y-4">
                <x-nx-input-text
                    name="visionImageForm.title"
                    label="Titel"
                    wire:model.live="visionImageForm.title"
                    placeholder="Titel des Zielbilds eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="visionImageForm.description"
                    label="Beschreibung"
                    wire:model.live="visionImageForm.description"
                    placeholder="Beschreibung des Zielbilds (optional)"
                    rows="3"
                />
                <x-nx-input-number
                    name="visionImageForm.order"
                    label="Reihenfolge"
                    wire:model.live="visionImageForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button type="button" variant="ghost" wire:click="closeVisionImageCreateModal">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="saveVisionImage">Hinzufügen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- VisionImage Edit Modal -->
    <x-nx-modal size="lg" model="visionImageEditModalShow">
        <x-slot name="header">Zielbild bearbeiten</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_vision_images)
                <div class="p-4 bg-[var(--nx-info)]/10 border border-[var(--nx-info)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-info)] mb-2">
                        Zentrale Frage zu Zielbildern
                    </label>
                    <p class="text-sm text-[color:var(--nx-info)]">{{ $focusArea->central_question_vision_images }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveVisionImage" class="space-y-4">
                <x-nx-input-text
                    name="visionImageForm.title"
                    label="Titel"
                    wire:model.live="visionImageForm.title"
                    placeholder="Titel des Zielbilds eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="visionImageForm.description"
                    label="Beschreibung"
                    wire:model.live="visionImageForm.description"
                    placeholder="Beschreibung des Zielbilds (optional)"
                    rows="3"
                />
                <x-nx-input-number
                    name="visionImageForm.order"
                    label="Reihenfolge"
                    wire:model.live="visionImageForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-nx-button variant="ghost" size="sm" wire:click="deleteVisionImage({{ $editingVisionImageId }})" wire:confirm="Zielbild wirklich löschen?">Löschen</x-nx-button>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <x-nx-button type="button" variant="ghost" wire:click="closeVisionImageEditModal">Abbrechen</x-nx-button>
                    <x-nx-button type="button" variant="secondary" wire:click="saveVisionImage">Speichern</x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Obstacle Create Modal -->
    <x-nx-modal size="lg" model="obstacleCreateModalShow">
        <x-slot name="header">Hindernis hinzufügen</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_obstacles)
                <div class="p-4 bg-[var(--nx-danger)]/10 border border-[var(--nx-danger)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-danger)] mb-2">
                        Zentrale Frage zu Hindernissen
                    </label>
                    <p class="text-sm text-[color:var(--nx-danger)]">{{ $focusArea->central_question_obstacles }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveObstacle" class="space-y-4">
                <x-nx-input-text
                    name="obstacleForm.title"
                    label="Titel"
                    wire:model.live="obstacleForm.title"
                    placeholder="Titel des Hindernisses eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="obstacleForm.description"
                    label="Beschreibung"
                    wire:model.live="obstacleForm.description"
                    placeholder="Beschreibung des Hindernisses (optional)"
                    rows="3"
                />
                <x-nx-input-number
                    name="obstacleForm.order"
                    label="Reihenfolge"
                    wire:model.live="obstacleForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button type="button" variant="ghost" wire:click="closeObstacleCreateModal">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="saveObstacle">Hinzufügen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Obstacle Edit Modal -->
    <x-nx-modal size="lg" model="obstacleEditModalShow">
        <x-slot name="header">Hindernis bearbeiten</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_obstacles)
                <div class="p-4 bg-[var(--nx-danger)]/10 border border-[var(--nx-danger)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-danger)] mb-2">
                        Zentrale Frage zu Hindernissen
                    </label>
                    <p class="text-sm text-[color:var(--nx-danger)]">{{ $focusArea->central_question_obstacles }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveObstacle" class="space-y-4">
                <x-nx-input-text
                    name="obstacleForm.title"
                    label="Titel"
                    wire:model.live="obstacleForm.title"
                    placeholder="Titel des Hindernisses eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="obstacleForm.description"
                    label="Beschreibung"
                    wire:model.live="obstacleForm.description"
                    placeholder="Beschreibung des Hindernisses (optional)"
                    rows="3"
                />
                <x-nx-input-number
                    name="obstacleForm.order"
                    label="Reihenfolge"
                    wire:model.live="obstacleForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-nx-button variant="ghost" size="sm" wire:click="deleteObstacle({{ $editingObstacleId }})" wire:confirm="Hindernis wirklich löschen?">Löschen</x-nx-button>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <x-nx-button type="button" variant="ghost" wire:click="closeObstacleEditModal">Abbrechen</x-nx-button>
                    <x-nx-button type="button" variant="secondary" wire:click="saveObstacle">Speichern</x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Milestone Create Modal -->
    <x-nx-modal size="lg" model="milestoneCreateModalShow">
        <x-slot name="header">Meilenstein hinzufügen</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_milestones)
                <div class="p-4 bg-[var(--nx-success)]/10 border border-[var(--nx-success)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-success)] mb-2">
                        Zentrale Frage zu Meilensteinen
                    </label>
                    <p class="text-sm text-[color:var(--nx-success)]">{{ $focusArea->central_question_milestones }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveMilestone" class="space-y-4">
                <x-nx-input-text
                    name="milestoneForm.title"
                    label="Titel"
                    wire:model.live="milestoneForm.title"
                    placeholder="Titel des Meilensteins eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="milestoneForm.description"
                    label="Beschreibung"
                    wire:model.live="milestoneForm.description"
                    placeholder="Beschreibung des Meilensteins (optional)"
                    rows="3"
                />
                <div class="grid grid-cols-2 gap-4">
                    <x-nx-input-select
                        name="milestoneForm.target_year"
                        label="Zieljahr"
                        :options="$this->availableYears"
                        optionValue="key"
                        optionLabel="value"
                        :nullable="true"
                        nullLabel="– Jahr auswählen –"
                        wire:model.live="milestoneForm.target_year"
                    />
                    <x-nx-input-select
                        name="milestoneForm.target_quarter"
                        label="Zielquartal (optional)"
                        :options="$this->availableQuarters"
                        optionValue="key"
                        optionLabel="value"
                        :nullable="true"
                        nullLabel="– Quartal auswählen –"
                        wire:model.live="milestoneForm.target_quarter"
                        :disabled="empty($milestoneForm['target_year'])"
                    />
                </div>
                <x-nx-input-number
                    name="milestoneForm.order"
                    label="Reihenfolge"
                    wire:model.live="milestoneForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button type="button" variant="ghost" wire:click="closeMilestoneCreateModal">Abbrechen</x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="saveMilestone">Hinzufügen</x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Milestone Edit Modal -->
    <x-nx-modal size="lg" model="milestoneEditModalShow">
        <x-slot name="header">Meilenstein bearbeiten</x-slot>
        <div class="space-y-4">
            @if($focusArea->central_question_milestones)
                <div class="p-4 bg-[var(--nx-success)]/10 border border-[var(--nx-success)]/30 rounded-lg">
                    <label class="block text-sm font-medium text-[color:var(--nx-success)] mb-2">
                        Zentrale Frage zu Meilensteinen
                    </label>
                    <p class="text-sm text-[color:var(--nx-success)]">{{ $focusArea->central_question_milestones }}</p>
                </div>
            @endif
            <form wire:submit.prevent="saveMilestone" class="space-y-4">
                <x-nx-input-text
                    name="milestoneForm.title"
                    label="Titel"
                    wire:model.live="milestoneForm.title"
                    placeholder="Titel des Meilensteins eingeben..."
                    required
                />
                <x-nx-input-textarea
                    name="milestoneForm.description"
                    label="Beschreibung"
                    wire:model.live="milestoneForm.description"
                    placeholder="Beschreibung des Meilensteins (optional)"
                    rows="3"
                />
                <div class="grid grid-cols-2 gap-4">
                    <x-nx-input-select
                        name="milestoneForm.target_year"
                        label="Zieljahr"
                        :options="$this->availableYears"
                        optionValue="key"
                        optionLabel="value"
                        :nullable="true"
                        nullLabel="– Jahr auswählen –"
                        wire:model.live="milestoneForm.target_year"
                    />
                    <x-nx-input-select
                        name="milestoneForm.target_quarter"
                        label="Zielquartal (optional)"
                        :options="$this->availableQuarters"
                        optionValue="key"
                        optionLabel="value"
                        :nullable="true"
                        nullLabel="– Quartal auswählen –"
                        wire:model.live="milestoneForm.target_quarter"
                        :disabled="empty($milestoneForm['target_year'])"
                    />
                </div>
                <x-nx-input-number
                    name="milestoneForm.order"
                    label="Reihenfolge"
                    wire:model.live="milestoneForm.order"
                    min="0"
                    required
                />
            </form>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-nx-button variant="ghost" size="sm" wire:click="deleteMilestone({{ $editingMilestoneId }})" wire:confirm="Meilenstein wirklich löschen?">Löschen</x-nx-button>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <x-nx-button type="button" variant="ghost" wire:click="closeMilestoneEditModal">Abbrechen</x-nx-button>
                    <x-nx-button type="button" variant="secondary" wire:click="saveMilestone">Speichern</x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    @push('styles')
    <style>
        /* Toast UI Editor: make it feel like Bear/Obsidian (clean, minimal) */
        .focus-area-editor-shell .toastui-editor-defaultUI {
            border: 1px solid var(--nx-line);
            border-radius: 12px;
            overflow: hidden;
        }
        .focus-area-editor-shell .toastui-editor-toolbar {
            background: color-mix(in srgb, var(--nx-bg) 70%, transparent);
            border-bottom: 1px solid var(--nx-line);
        }
        .focus-area-editor-shell .toastui-editor-contents {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 17px;
            line-height: 1.7;
        }
        .focus-area-editor-shell .toastui-editor-defaultUI-toolbar button {
            border-radius: 8px;
        }
        .focus-area-editor-shell .toastui-editor-mode-switch {
            display: none !important;
        }
    </style>
    @endpush
</x-ui-page>
