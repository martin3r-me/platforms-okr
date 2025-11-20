<x-ui-modal size="lg" wire:model="open" :closeButton="true">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gradient-to-br from-[var(--ui-primary-10)] to-[var(--ui-primary-5)] rounded-xl flex items-center justify-center shadow-sm">
                    @svg('heroicon-o-sparkles', 'w-6 h-6 text-[var(--ui-primary)]')
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-xl font-bold text-[var(--ui-secondary)]">KeyResult verknüpfen</h3>
                @if($contextType && $contextId)
                    @php
                        $resolver = app(\Platform\Okr\Services\KeyResultContextResolver::class);
                        $label = $resolver->resolveLabel($contextType, $contextId);
                    @endphp
                    @if($label)
                        <p class="text-sm text-[var(--ui-muted)] mt-1">
                            Kontext: <span class="font-semibold text-[var(--ui-secondary)]">{{ $label }}</span>
                        </p>
                    @endif
                @else
                    <p class="text-sm text-[var(--ui-muted)] mt-1">KeyResult mit Kontext verknüpfen</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($contextType && $contextId)
            <!-- Verknüpfte KeyResults -->
            @if($linkedKeyResults && $linkedKeyResults->count() > 0)
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-3">Verknüpfte KeyResults</h4>
                    <div class="space-y-2">
                        @foreach($linkedKeyResults as $keyResult)
                            <div class="flex items-center justify-between p-4 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-[var(--ui-primary-5)] flex items-center justify-center">
                                                @svg('heroicon-o-sparkles', 'w-5 h-5 text-[var(--ui-primary)]')
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--ui-secondary)] truncate">{{ $keyResult->title }}</div>
                                            @if($keyResult->objective)
                                                <div class="text-xs text-[var(--ui-muted)] mt-0.5">
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
                                    <x-ui-button 
                                        variant="danger-outline" 
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
                                    </x-ui-button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- KeyResult auswählen -->
            <div>
                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-3">KeyResult auswählen</h4>
                
                <!-- Suche -->
                <div class="mb-4">
                    <x-ui-input-text
                        name="search"
                        label="Suchen"
                        wire:model.live.debounce.300ms="search"
                        placeholder="KeyResult, Objective oder Beschreibung suchen..."
                    />
                </div>

                <!-- Verfügbare KeyResults -->
                @if($availableKeyResults && $availableKeyResults->count() > 0)
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($availableKeyResults as $keyResult)
                            @php
                                $isLinked = $linkedKeyResults && $linkedKeyResults->contains('id', $keyResult->id);
                            @endphp
                            <div class="flex items-center justify-between p-4 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:bg-[var(--ui-muted-5)] transition-colors {{ $isLinked ? 'opacity-50' : '' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg bg-[var(--ui-primary-5)] flex items-center justify-center">
                                                @svg('heroicon-o-sparkles', 'w-5 h-5 text-[var(--ui-primary)]')
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--ui-secondary)] truncate">{{ $keyResult->title }}</div>
                                            @if($keyResult->description)
                                                <div class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-2">{{ $keyResult->description }}</div>
                                            @endif
                                            @if($keyResult->objective)
                                                <div class="text-xs text-[var(--ui-muted)] mt-1">
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
                                        <span class="text-xs font-medium text-[var(--ui-muted)]">Bereits verknüpft</span>
                                    @else
                                        <x-ui-button 
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
                                        </x-ui-button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--ui-surface)] flex items-center justify-center">
                            @svg('heroicon-o-sparkles', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)]">Keine KeyResults gefunden</p>
                        <p class="text-xs text-[var(--ui-muted)] mt-1">
                            @if(!empty($search))
                                Keine KeyResults für "{{ $search }}" gefunden.
                            @else
                                Erstellen Sie zuerst ein KeyResult in einem OKR-Cycle.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="p-8 text-center rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--ui-surface)] flex items-center justify-center">
                    @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--ui-muted)]')
                </div>
                <p class="text-sm font-medium text-[var(--ui-secondary)]">Kein Kontext gesetzt</p>
                <p class="text-xs text-[var(--ui-muted)] mt-1">Öffnen Sie eine Aufgabe oder ein Projekt, um KeyResults zu verknüpfen.</p>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <div class="flex justify-end">
            <x-ui-button variant="secondary" wire:click="close">
                Schließen
            </x-ui-button>
        </div>
    </x-slot>
</x-ui-modal>

