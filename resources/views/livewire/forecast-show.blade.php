<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zukunftsbilder', 'href' => route('okr.forecasts.index')],
            ['label' => $forecast->title],
        ]">
            <a href="{{ route('okr.forecasts.pdf', $forecast) }}" target="_blank" rel="noopener">
                <x-nx-button variant="ghost" size="sm">
                    @svg('heroicon-o-document-arrow-down', 'w-4 h-4')
                    <span>PDF</span>
                </x-nx-button>
            </a>
            @if($this->isDirty)
                <x-nx-button variant="primary" size="sm" wire:click="save">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span>Speichern</span>
                </x-nx-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        {{-- Forecast Header --}}
        <div class="bg-gradient-to-r from-[var(--nx-bg)] to-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[color:var(--nx-accent)] text-white rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-sparkles', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--nx-text)] tracking-tight">{{ $forecast->title }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)] mt-1">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4')
                                    Zieldatum: {{ $forecast->target_date->format('d.m.Y') }}
                                </span>
                                @if($forecast->currentVersion)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-document-text', 'w-4 h-4')
                                        Version {{ $forecast->currentVersion->version }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Mini Dashboard --}}
                    @php
                        $totalFocusAreas = $forecast->focusAreas->count();
                        $daysUntilTarget = now()->diffInDays($forecast->target_date, false);
                        $isPast = $forecast->target_date->isPast();
                    @endphp
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalFocusAreas }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Fokusräume</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold {{ $isPast ? 'text-[color:var(--nx-danger)]' : ($daysUntilTarget <= 30 ? 'text-[color:var(--nx-warning)]' : 'text-[var(--nx-accent)]') }}">
                                {{ $forecast->target_date->format('d.m.Y') }}
                            </div>
                            <div class="text-xs text-[var(--nx-muted)]">
                                @if($isPast)
                                    Vergangen
                                @elseif($daysUntilTarget <= 30)
                                    In {{ $daysUntilTarget }} Tagen
                                @else
                                    Zieldatum
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fokusräume --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-viewfinder-circle', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Fokusräume</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Fokusräume, die zu diesem Zukunftsbild gehören</p>
                    </div>
                </div>
                <x-nx-button
                    variant="secondary"
                    size="sm"
                    wire:click="addFocusArea"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Fokusraum hinzufügen</span>
                </x-nx-button>
            </div>

            @if($forecast->focusAreas->count() > 0)
                <div wire:sortable="updateFocusAreaOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($forecast->focusAreas->sortBy('order') as $focusArea)
                        @php
                            $visionImagesCount = $focusArea->visionImages->count();
                            $obstaclesCount = $focusArea->obstacles->count();
                            $milestonesCount = $focusArea->milestones->count();
                        @endphp
                        <div wire:sortable.item="{{ $focusArea->id }}" wire:key="focusarea-{{ $focusArea->id }}" class="mb-4 p-4 border border-[color:var(--nx-line)] rounded-lg bg-[color:var(--nx-surface)] hover:border-[color:var(--nx-line)] transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="flex-grow-1 flex items-start gap-3 flex-1 min-w-0">
                                    <div class="w-8 h-8 bg-[var(--nx-bg)] text-[var(--nx-text)] rounded flex items-center justify-center flex-shrink-0 mt-0.5">
                                        @svg('heroicon-o-viewfinder-circle', 'w-4 h-4')
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a
                                            href="{{ route('okr.focus-areas.show', $focusArea) }}"
                                            wire:navigate
                                            class="font-medium text-[var(--nx-accent)] hover:underline block"
                                        >
                                            {{ $focusArea->title }}
                                        </a>
                                        @if($focusArea->description)
                                            <div class="text-xs text-[var(--nx-muted)] mt-0.5 mb-3">{{ Str::limit($focusArea->description, 100) }}</div>
                                        @endif

                                        {{-- Zielbilder, Hindernisse und Meilensteine --}}
                                        @if($visionImagesCount > 0 || $obstaclesCount > 0 || $milestonesCount > 0)
                                            <div class="mt-3 space-y-2">
                                                @if($visionImagesCount > 0)
                                                    <div class="flex flex-wrap gap-1 items-start">
                                                        <div class="text-xs font-medium text-[var(--nx-muted)] mr-1 flex-shrink-0 pt-0.5">
                                                            <span class="flex items-center gap-1">
                                                                @svg('heroicon-o-photo', 'w-3 h-3')
                                                                Zielbilder:
                                                            </span>
                                                        </div>
                                                        @foreach($focusArea->visionImages->take(5) as $visionImage)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-info)]/30">
                                                                <span class="truncate max-w-[150px] leading-tight">{{ $visionImage->title }}</span>
                                                            </div>
                                                        @endforeach
                                                        @if($visionImagesCount > 5)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-info)]/30">
                                                                +{{ $visionImagesCount - 5 }} weitere
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($obstaclesCount > 0)
                                                    <div class="flex flex-wrap gap-1 items-start">
                                                        <div class="text-xs font-medium text-[var(--nx-muted)] mr-1 flex-shrink-0 pt-0.5">
                                                            <span class="flex items-center gap-1">
                                                                @svg('heroicon-o-exclamation-triangle', 'w-3 h-3')
                                                                Hindernisse:
                                                            </span>
                                                        </div>
                                                        @foreach($focusArea->obstacles->take(5) as $obstacle)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-warning)]/10 text-[color:var(--nx-warning)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-warning)]/30">
                                                                <span class="truncate max-w-[150px] leading-tight">{{ $obstacle->title }}</span>
                                                            </div>
                                                        @endforeach
                                                        @if($obstaclesCount > 5)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-warning)]/10 text-[color:var(--nx-warning)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-warning)]/30">
                                                                +{{ $obstaclesCount - 5 }} weitere
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($milestonesCount > 0)
                                                    <div class="flex flex-wrap gap-1 items-start">
                                                        <div class="text-xs font-medium text-[var(--nx-muted)] mr-1 flex-shrink-0 pt-0.5">
                                                            <span class="flex items-center gap-1">
                                                                @svg('heroicon-o-flag', 'w-3 h-3')
                                                                Meilensteine:
                                                            </span>
                                                        </div>
                                                        @foreach($focusArea->milestones->take(5) as $milestone)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-success)]/10 text-[color:var(--nx-success)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-success)]/30">
                                                                <span class="font-medium text-[var(--nx-text)] leading-tight">
                                                                    <span class="truncate max-w-[150px]">{{ $milestone->title }}</span>
                                                                    @if($milestone->target_year || $milestone->target_quarter)
                                                                        <sup class="text-[0.65rem] text-[var(--nx-muted)] ml-0.5">
                                                                            @if($milestone->target_year && $milestone->target_quarter)
                                                                                {{ $milestone->target_year }}/Q{{ $milestone->target_quarter }}
                                                                            @elseif($milestone->target_year)
                                                                                {{ $milestone->target_year }}
                                                                            @elseif($milestone->target_quarter)
                                                                                Q{{ $milestone->target_quarter }}
                                                                            @endif
                                                                        </sup>
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                        @if($milestonesCount > 5)
                                                            <div class="inline-flex items-baseline bg-[var(--nx-success)]/10 text-[color:var(--nx-success)] rounded px-1.5 py-0.5 text-xs border border-[var(--nx-success)]/30">
                                                                +{{ $milestonesCount - 5 }} weitere
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                    <x-nx-button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="editFocusArea({{ $focusArea->id }})"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </x-nx-button>
                                    <x-nx-button variant="ghost" size="sm" wire:click="deleteFocusArea({{ $focusArea->id }})" wire:confirm="Fokusraum wirklich löschen?">Löschen</x-nx-button>
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
                        @svg('heroicon-o-viewfinder-circle', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Fokusräume vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Klicke auf "Fokusraum hinzufügen" um zu beginnen</p>
                    <x-nx-button
                        variant="secondary"
                        wire:click="addFocusArea"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Ersten Fokusraum hinzufügen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>

        {{-- Transformation Map --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[color:var(--nx-tone-violet)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-map', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Transformation Map</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Übersicht der Meilensteine nach Jahren und Fokusräumen</p>
                    </div>
                </div>
            </div>

            @php
                $mapData = $this->transformationMapData;
                $years = $this->transformationMapYears;
                $focusAreas = $forecast->focusAreas->sortBy('order');
            @endphp

            @if(count($years) > 0 && $focusAreas->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="border border-[color:var(--nx-line)] bg-[var(--nx-bg)] p-2 text-left text-xs font-semibold text-[var(--nx-text)] sticky left-0 z-10">
                                    Fokusraum
                                </th>
                                @foreach($years as $year)
                                    <th class="border border-[color:var(--nx-line)] bg-[var(--nx-bg)] p-2 text-center text-xs font-semibold text-[var(--nx-text)] min-w-[150px]">
                                        {{ $year }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($focusAreas as $focusArea)
                                <tr>
                                    <td class="border border-[color:var(--nx-line)] p-2 bg-[color:var(--nx-surface)] sticky left-0 z-10">
                                        <a
                                            href="{{ route('okr.focus-areas.show', $focusArea) }}"
                                            wire:navigate
                                            class="font-medium text-xs text-[var(--nx-accent)] hover:underline"
                                        >
                                            {{ $focusArea->title }}
                                        </a>
                                    </td>
                                    @foreach($years as $year)
                                        <td class="border border-[color:var(--nx-line)] p-1.5 bg-[color:var(--nx-surface)] align-top">
                                            @php
                                                $yearData = $mapData[$year][$focusArea->id] ?? null;
                                                $milestones = $yearData['milestones'] ?? collect();
                                            @endphp
                                            @if($milestones->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($milestones as $milestone)
                                                        <div class="inline-flex items-baseline bg-[var(--nx-bg)] rounded px-1.5 py-0.5 border border-[color:var(--nx-line)]">
                                                            <span class="text-xs font-medium text-[var(--nx-text)] leading-tight">
                                                                {{ $milestone->title }}
                                                                @if($milestone->target_year || $milestone->target_quarter)
                                                                    <sup class="text-[0.65rem] text-[var(--nx-muted)] ml-0.5">
                                                                        @if($milestone->target_year && $milestone->target_quarter)
                                                                            {{ $milestone->target_year }}/Q{{ $milestone->target_quarter }}
                                                                        @elseif($milestone->target_year)
                                                                            {{ $milestone->target_year }}
                                                                        @elseif($milestone->target_quarter)
                                                                            Q{{ $milestone->target_quarter }}
                                                                        @endif
                                                                    </sup>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-[0.65rem] text-[var(--nx-muted)] text-center">—</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-[var(--nx-muted)]">
                    <p>Keine Daten verfügbar. Bitte erstelle Fokusräume und Meilensteine.</p>
                </div>
            @endif
        </div>

        {{-- Zukunftsbild Inhalt --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[color:var(--nx-accent)] text-white rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Inhalt</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Strategische Ausrichtung & Transformationssteuerung</p>
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
                            placeholder: 'Schreibe das Zukunftsbild…  / Überschriften, Listen, Checklists, Links, Code',
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
                        if (window.__forecastKeydownHandler) {
                            window.removeEventListener('keydown', window.__forecastKeydownHandler);
                        }
                        window.__forecastKeydownHandler = (e) => {
                            const isSave = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's';
                            if (!isSave) return;
                            e.preventDefault();
                            this.saveNow();
                        };
                        window.addEventListener('keydown', window.__forecastKeydownHandler);

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

                <div class="forecast-editor-shell">
                    <div wire:ignore x-ref="editorEl"></div>
                </div>
            </div>
        </div>

    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Zukunftsbild Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Forecast Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Zieldatum</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $forecast->target_date->format('d.m.Y') }}</span>
                        </div>
                        @if($forecast->currentVersion)
                            <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                                <span class="text-sm font-medium text-[var(--nx-text)]">Version</span>
                                <span class="text-sm text-[var(--nx-muted)]">v{{ $forecast->currentVersion->version }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Fokusräume</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $forecast->focusAreas->count() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Versions --}}
                @if($forecast->versions->count() > 0)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Versionen</h3>
                        <div class="space-y-2">
                            @foreach($forecast->versions->take(5) as $version)
                                <div class="p-3 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-[var(--nx-text)]">Version {{ $version->version }}</span>
                                        <span class="text-xs text-[var(--nx-muted)]">{{ $version->created_at->format('d.m.Y') }}</span>
                                    </div>
                                    @if($version->change_note)
                                        <p class="text-xs text-[var(--nx-muted)]">{{ $version->change_note }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                            <div class="w-8 h-8 bg-[color:var(--nx-accent)] text-white rounded-full flex items-center justify-center text-xs font-semibold">
                                @svg('heroicon-o-sparkles', 'w-4 h-4')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-[var(--nx-text)] text-sm">Zukunftsbild erstellt</div>
                                <div class="text-xs text-[var(--nx-muted)]">{{ $forecast->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        @if($forecast->focusAreas->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-viewfinder-circle', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $forecast->focusAreas->count() }} Fokusräume hinzugefügt</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Letzte Änderung: {{ $forecast->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endif

                        @if($forecast->versions->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-document-text', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $forecast->versions->count() }} Versionen erstellt</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Content-Versionierung</div>
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
                            <span class="text-sm text-[var(--nx-text)]">Zieldatum</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $forecast->target_date->format('d.m.Y') }}</span>
                        </div>
                        @if($forecast->currentVersion)
                            <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                                <span class="text-sm text-[var(--nx-text)]">Version</span>
                                <span class="text-sm font-medium text-[var(--nx-muted)]">v{{ $forecast->currentVersion->version }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Fokusräume</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $forecast->focusAreas->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Erstellt</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $forecast->created_at->format('d.m.Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Erstellt von</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $forecast->user->name ?? 'Unbekannt' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <!-- FocusArea Create Modal -->
    <x-nx-modal
        size="lg"
        model="focusAreaCreateModalShow"
    >
        <x-slot name="header">
            Fokusraum hinzufügen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveFocusArea" class="space-y-4">
                <x-nx-input-text
                    name="focusAreaForm.title"
                    label="Titel"
                    wire:model.live="focusAreaForm.title"
                    placeholder="Titel des Fokusraums eingeben..."
                    required
                />

                <x-nx-input-textarea
                    name="focusAreaForm.description"
                    label="Beschreibung"
                    wire:model.live="focusAreaForm.description"
                    placeholder="Beschreibung des Fokusraums (optional)"
                    rows="3"
                />

                <x-nx-input-number
                    name="focusAreaForm.order"
                    label="Reihenfolge"
                    wire:model.live="focusAreaForm.order"
                    min="0"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button 
                    type="button" 
                    variant="ghost" 
                    wire:click="closeFocusAreaCreateModal"
                >
                    Abbrechen
                </x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="saveFocusArea">
                    Hinzufügen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- FocusArea Edit Modal -->
    <x-nx-modal
        size="lg"
        model="focusAreaEditModalShow"
    >
        <x-slot name="header">
            Fokusraum bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveFocusArea" class="space-y-4">
                <x-nx-input-text
                    name="focusAreaForm.title"
                    label="Titel"
                    wire:model.live="focusAreaForm.title"
                    placeholder="Titel des Fokusraums eingeben..."
                    required
                />

                <x-nx-input-textarea
                    name="focusAreaForm.description"
                    label="Beschreibung"
                    wire:model.live="focusAreaForm.description"
                    placeholder="Beschreibung des Fokusraums (optional)"
                    rows="3"
                />

                <x-nx-input-number
                    name="focusAreaForm.order"
                    label="Reihenfolge"
                    wire:model.live="focusAreaForm.order"
                    min="0"
                    required
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-nx-button variant="ghost" size="sm" wire:click="deleteFocusArea({{ $editingFocusAreaId }})" wire:confirm="Fokusraum wirklich löschen?">Löschen</x-nx-button>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <x-nx-button 
                        type="button" 
                        variant="ghost" 
                        wire:click="closeFocusAreaEditModal"
                    >
                        Abbrechen
                    </x-nx-button>
                    <x-nx-button type="button" variant="secondary" wire:click="saveFocusArea">
                        Speichern
                    </x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    @push('styles')
    <style>
        /* Toast UI Editor: make it feel like Bear/Obsidian (clean, minimal) */
        .forecast-editor-shell .toastui-editor-defaultUI {
            border: 1px solid var(--nx-line);
            border-radius: 12px;
            overflow: hidden;
        }
        .forecast-editor-shell .toastui-editor-toolbar {
            background: color-mix(in srgb, var(--nx-bg) 70%, transparent);
            border-bottom: 1px solid var(--nx-line);
        }
        .forecast-editor-shell .toastui-editor-contents {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 17px;
            line-height: 1.7;
        }
        .forecast-editor-shell .toastui-editor-defaultUI-toolbar button {
            border-radius: 8px;
        }
        .forecast-editor-shell .toastui-editor-mode-switch {
            display: none !important;
        }
    </style>
    @endpush
</x-ui-page>
