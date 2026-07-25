<x-nx-modal size="lg" wire:model="open" :closeButton="true">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-[var(--nx-accent)]/10 rounded-xl flex items-center justify-center shadow-sm">
                    @svg('heroicon-o-chart-bar', 'w-6 h-6 text-[var(--nx-accent)]')
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-bold text-[var(--nx-text)]">Erfolgskriterium verknüpfen</h3>
                @if($contextType && $contextId)
                    @php
                        $resolver = app(\Platform\Okr\Services\KeyResultContextResolver::class);
                        $label = $resolver->resolveLabel($contextType, $contextId);
                    @endphp
                    @if($label)
                        <p class="text-sm text-[var(--nx-muted)] mt-1">
                            Kontext: <span class="font-semibold text-[var(--nx-text)]">{{ $label }}</span>
                            @if($coveredKeyResults && $coveredKeyResults->count() > 0)
                                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[var(--nx-success)]/10 text-[var(--nx-success)] border border-[var(--nx-success)]/20">
                                    @svg('heroicon-o-check-circle', 'w-3 h-3')
                                    {{ $coveredKeyResults->count() }} Erfolgskriterium(e) über Parent-Kontext abgedeckt
                                </span>
                            @endif
                        </p>
                    @endif
                @else
                    <p class="text-sm text-[var(--nx-muted)] mt-1">Erfolgskriterium mit Kontext verknüpfen</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($contextType && $contextId)
            <!-- Über Parent-Kontext abgedeckte KeyResults (z.B. über Project) -->
            @if($coveredKeyResults && $coveredKeyResults->count() > 0)
                <div>
                    <h4 class="text-sm font-semibold text-[var(--nx-text)] mb-3">Über Parent-Kontext abgedeckt</h4>
                    <p class="text-xs text-[var(--nx-muted)] mb-3">Diese Erfolgskriterien sind über einen übergeordneten Kontext (z.B. Project) abgedeckt. Alle Tasks im Project zahlen auf diese Erfolgskriterien ein.</p>
                    <div class="space-y-2">
                        @foreach($coveredKeyResults as $keyResult)
                            <div class="flex items-center justify-between p-4 rounded-lg border border-[var(--nx-success)]/40 bg-[var(--nx-success)]/8">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-[var(--nx-success)]/10 flex items-center justify-center">
                                                @svg('heroicon-o-chart-bar', 'w-5 h-5 text-[var(--nx-success)]')
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--nx-text)] truncate">{{ $keyResult->title }}</div>
                                            @if($keyResult->objective)
                                                <div class="text-xs text-[var(--nx-muted)] mt-0.5">
                                                    Objective: {{ $keyResult->objective->title }}
                                                    @if($keyResult->objective->cycle)
                                                        • Cycle: {{ $keyResult->objective->cycle->template?->label ?? 'Unbekannt' }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-[var(--nx-success)]/10 text-[var(--nx-success)] border border-[var(--nx-success)]/20">
                                        @svg('heroicon-o-check-circle', 'w-3 h-3')
                                        Abgedeckt
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Direkt verknüpfte KeyResults -->
            @if($linkedKeyResults && $linkedKeyResults->count() > 0)
                <div>
                    <h4 class="text-sm font-semibold text-[var(--nx-text)] mb-3">Direkt verknüpfte Erfolgskriterien</h4>
                    <div class="space-y-2">
                        @foreach($linkedKeyResults as $keyResult)
                            <div class="flex items-center justify-between p-4 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-surface)] hover:bg-[var(--nx-bg)] transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-[var(--nx-accent)]/8 flex items-center justify-center">
                                                @svg('heroicon-o-chart-bar', 'w-5 h-5 text-[var(--nx-accent)]')
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--nx-text)] truncate">{{ $keyResult->title }}</div>
                                            @if($keyResult->objective)
                                                <div class="text-xs text-[var(--nx-muted)] mt-0.5">
                                                    Objective: {{ $keyResult->objective->title }}
                                                    @if($keyResult->objective->cycle)
                                                        • Cycle: {{ $keyResult->objective->cycle->template?->label ?? 'Unbekannt' }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-4">
                                    <x-nx-button 
                                        variant="danger" 
                                        size="sm"
                                        wire:click="detachKeyResult({{ $keyResult->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="detachKeyResult({{ $keyResult->id }})"
                                    >
                                        <span wire:loading.remove wire:target="detachKeyResult({{ $keyResult->id }})">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </span>
                                        <span wire:loading wire:target="detachKeyResult({{ $keyResult->id }})">
                                            @svg('heroicon-o-arrow-path', 'w-4 h-4 animate-spin')
                                        </span>
                                    </x-nx-button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- KeyResult auswählen -->
            <div>
                <h4 class="text-sm font-semibold text-[var(--nx-text)] mb-3">Erfolgskriterium auswählen</h4>
                
                <!-- Suche -->
                <div class="mb-4">
                    <x-nx-input-text
                        name="search"
                        label="Suchen"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Erfolgskriterium, Objective oder Beschreibung suchen..."
                    />
                </div>

                <!-- Verfügbare KeyResults -->
                @if($availableKeyResults && $availableKeyResults->count() > 0)
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($availableKeyResults as $keyResult)
                            @php
                                $isLinked = $linkedKeyResults && $linkedKeyResults->contains('id', $keyResult->id);
                                $isCovered = $coveredKeyResults && $coveredKeyResults->contains('id', $keyResult->id);
                            @endphp
                            <div class="flex items-center justify-between p-4 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-surface)] hover:bg-[var(--nx-bg)] transition-colors {{ $isLinked || $isCovered ? 'opacity-50' : '' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-[var(--nx-accent)]/8 flex items-center justify-center">
                                                @svg('heroicon-o-chart-bar', 'w-5 h-5 text-[var(--nx-accent)]')
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--nx-text)] truncate">{{ $keyResult->title }}</div>
                                            @if($keyResult->description)
                                                <div class="text-xs text-[var(--nx-muted)] mt-0.5 line-clamp-2">{{ $keyResult->description }}</div>
                                            @endif
                                            @if($keyResult->objective)
                                                <div class="text-xs text-[var(--nx-muted)] mt-1">
                                                    Objective: {{ $keyResult->objective->title }}
                                                    @if($keyResult->objective->cycle)
                                                        • Cycle: {{ $keyResult->objective->cycle->template?->label ?? 'Unbekannt' }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 ml-4">
                                    @if($isLinked)
                                        <span class="text-xs font-medium text-[var(--nx-muted)]">Bereits verknüpft</span>
                                    @elseif($isCovered)
                                        <span class="text-xs font-medium text-[var(--nx-success)]">Über Parent abgedeckt</span>
                                    @else
                                        <x-nx-button 
                                            variant="primary" 
                                            size="sm"
                                            wire:click="attachKeyResult({{ $keyResult->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="attachKeyResult({{ $keyResult->id }})"
                                        >
                                            <span wire:loading.remove wire:target="attachKeyResult({{ $keyResult->id }})">
                                                Verknüpfen
                                            </span>
                                            <span wire:loading wire:target="attachKeyResult({{ $keyResult->id }})" class="inline-flex items-center gap-2">
                                                @svg('heroicon-o-arrow-path', 'w-4 h-4 animate-spin')
                                            </span>
                                        </x-nx-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--nx-surface)] flex items-center justify-center">
                            @svg('heroicon-o-chart-bar', 'w-8 h-8 text-[var(--nx-muted)]')
                        </div>
                        <p class="text-sm font-medium text-[var(--nx-text)]">Keine Erfolgskriterien gefunden</p>
                        <p class="text-xs text-[var(--nx-muted)] mt-1">
                            @if(!empty($search))
                                Keine Erfolgskriterien für "{{ $search }}" gefunden.
                            @else
                                Erstellen Sie zuerst ein Erfolgskriterium in einem Zielsteuerung-Cycle.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="p-8 text-center rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--nx-surface)] flex items-center justify-center">
                    @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--nx-muted)]')
                </div>
                <p class="text-sm font-medium text-[var(--nx-text)]">Kein Kontext gesetzt</p>
                <p class="text-xs text-[var(--nx-muted)] mt-1">Öffnen Sie eine Aufgabe oder ein Projekt, um Erfolgskriterien zu verknüpfen.</p>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <div class="flex justify-end">
            <x-nx-button variant="secondary" wire:click="close">
                Schließen
            </x-nx-button>
        </div>
    </x-slot>
</x-nx-modal>

