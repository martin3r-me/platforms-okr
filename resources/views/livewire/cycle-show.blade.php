<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$cycle->template?->label ?? 'Unbekannter Cycle'" icon="heroicon-o-calendar">
            <x-slot name="titleActions">
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
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                <p class="text-[var(--ui-secondary)]">{{ session('message') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                <p class="text-[var(--ui-secondary)] font-medium">Fehler:</p>
                <p class="text-[var(--ui-muted)]">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Cycle Header --}}
        <div class="bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-calendar', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight">{{ $cycle->template?->label ?? 'Unbekannter Cycle' }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--ui-muted)] mt-1">
                                @if($cycle->template)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-calendar', 'w-4 h-4')
                                        {{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}
                                    </span>
                                @endif
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-flag', 'w-4 h-4')
                                    {{ $cycle->okr->title }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Quick Stats --}}
                    @php
                        $cyclePerformance = $cycle->performance;
                        $totalObjectives = $cycle->objectives->count();
                        $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                        $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                        $progress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                        $performanceScore = $cyclePerformance ? $cyclePerformance->performance_score : $progress;
                    @endphp
                    <div class="grid grid-cols-4 gap-4 mt-6">
                        <div class="text-center p-4 bg-white rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $totalObjectives }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Objectives</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $totalKeyResults }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Key Results</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-2xl font-bold text-[var(--ui-primary)]">{{ $completedKeyResults }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">Abgeschlossen</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-2xl font-bold {{ $performanceScore >= 80 ? 'text-green-600' : ($performanceScore >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $performanceScore }}%</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $cyclePerformance ? 'Performance' : 'Fortschritt' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cycle Details --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Cycle Details</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Grundinformationen und Einstellungen</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
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
                
                <div class="space-y-4">
                    <x-ui-input-textarea 
                        name="cycle.notes"
                        label="Notizen"
                        wire:model.live.debounce.500ms="cycle.notes"
                        placeholder="Zusätzliche Notizen zum Cycle (optional)"
                        rows="4"
                        :errorKey="'cycle.notes'"
                    />
                </div>
            </div>
        </div>

        {{-- Objectives & Key Results --}}
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Objectives & Key Results</h3>
                        <p class="text-sm text-[var(--ui-muted)]">Ziele und Messgrößen verwalten</p>
                    </div>
                </div>
                <x-ui-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="addObjective"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Objective hinzufügen</span>
                </x-ui-button>
            </div>

            @if($cycle->objectives->count() > 0)
                <div wire:sortable="updateObjectiveOrder" wire:sortable-group="updateKeyResultOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($cycle->objectives->sortBy('order') as $objective)
                        <div wire:sortable.item="{{ $objective->id }}" wire:key="objective-{{ $objective->id }}" class="mb-6 p-6 border border-[var(--ui-border)]/60 rounded-lg bg-[var(--ui-muted-5)] hover:border-[var(--ui-border)]/80 transition-colors">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex-grow-1">
                                    <div class="flex items-center gap-3">
                                        <div class="font-medium text-lg text-[var(--ui-secondary)]">{{ $objective->title }}</div>
                                        <x-ui-badge variant="secondary" size="sm">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                                        @php
                                            $objKeyResults = $objective->keyResults;
                                            $objCompleted = $objKeyResults->where('performance.is_completed', true)->count();
                                            $objTotal = $objKeyResults->count();
                                            $objProgress = $objTotal > 0 ? round(($objCompleted / $objTotal) * 100) : 0;
                                        @endphp
                                        <x-ui-badge 
                                            variant="{{ $objProgress >= 80 ? 'success' : ($objProgress >= 50 ? 'warning' : 'secondary') }}" 
                                            size="sm"
                                        >
                                            {{ $objProgress }}%
                                        </x-ui-badge>
                                    </div>
                                    @if($objective->description)
                                        <div class="text-sm text-[var(--ui-muted)] mt-2">{{ Str::limit($objective->description, 100) }}</div>
                                    @endif
                                    
                                    {{-- Objective Performance Bar --}}
                                    @if($objTotal > 0)
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs text-[var(--ui-muted)]">Objective Performance</span>
                                                <span class="text-xs font-medium {{ $objProgress >= 80 ? 'text-green-600' : ($objProgress >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                    {{ $objCompleted }}/{{ $objTotal }} erreicht
                                                </span>
                                            </div>
                                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full transition-all duration-300 {{ $objProgress >= 80 ? 'bg-green-500' : ($objProgress >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                     style="width: {{ $objProgress }}%"></div>
                                            </div>
                                        </div>
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
                                <div wire:sortable-group.item-group="{{ $objective->id }}" wire:sortable-group.options="{ animation: 100 }" class="space-y-3">
                                    @foreach($objective->keyResults->sortBy('order') as $keyResult)
                                        <div wire:sortable-group.item="{{ $keyResult->id }}" wire:key="keyresult-{{ $keyResult->id }}" 
                                             class="flex items-center gap-3 p-4 bg-white rounded border border-[var(--ui-border)]/40 hover:border-[var(--ui-border)]/60 transition-colors cursor-pointer" 
                                             wire:click="editKeyResult({{ $keyResult->id }})">
                                            <div wire:sortable-group.handle class="cursor-move p-1 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] flex-shrink-0" wire:click.stop>
                                                @svg('heroicon-o-bars-3', 'w-3 h-3')
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="font-medium text-sm text-[var(--ui-secondary)]">{{ $keyResult->title }}</div>
                                                @if($keyResult->description)
                                                    <div class="text-xs text-[var(--ui-muted)] mt-1">{{ Str::limit($keyResult->description, 60) }}</div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 flex-shrink-0" wire:click.stop>
                                                @php
                                                    $type = $keyResult->performance?->type;
                                                    $target = $keyResult->performance?->target_value ?? 0;
                                                    $current = $keyResult->performance?->current_value ?? 0;
                                                    $isCompleted = $keyResult->performance?->is_completed ?? false;
                                                    
                                                    // Hole den ersten Performance-Wert als Ausgangswert
                                                    $firstPerformance = $keyResult->performances()->orderBy('created_at', 'asc')->first();
                                                    $startValue = $firstPerformance?->current_value ?? 0;
                                                    
                                                    // Berechne Fortschritt in Prozent basierend auf Ausgangswert
                                                    $progressPercent = 0;
                                                    if ($type === 'boolean') {
                                                        $progressPercent = $isCompleted ? 100 : 0;
                                                    } elseif ($type === 'percentage' || $type === 'absolute') {
                                                        if ($target > $startValue) {
                                                            $progressRange = $target - $startValue;
                                                            $currentProgress = $current - $startValue;
                                                            $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                                                        } elseif ($target < $startValue) {
                                                            $progressRange = $startValue - $target;
                                                            $currentProgress = $startValue - $current;
                                                            $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                                                        } else {
                                                            $progressPercent = $current >= $target ? 100 : 0;
                                                        }
                                                    }
                                                @endphp
                                                
                                                @if($keyResult->performance)
                                                    {{-- Boolean Key Results --}}
                                                    @if($type === 'boolean')
                                                        <x-ui-button 
                                                            type="button" 
                                                            variant="{{ $isCompleted ? 'success' : 'secondary-outline' }}" 
                                                            size="sm"
                                                            wire:click="toggleBooleanKeyResult({{ $keyResult->id }})"
                                                        >
                                                            @svg('heroicon-o-check', 'w-4 h-4')
                                                            {{ $isCompleted ? 'Erledigt' : 'Erledigen' }}
                                                        </x-ui-button>
                                                    @else
                                                        {{-- Andere Key Results --}}
                                                        <div class="flex items-center gap-3">
                                                            {{-- Progress Bar --}}
                                                            <div class="w-20">
                                                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                                                    <div class="bg-[var(--ui-primary)] h-2 rounded-full transition-all duration-300" 
                                                                         style="width: {{ $progressPercent }}%"></div>
                                                                </div>
                                                                <div class="text-xs text-[var(--ui-muted)] text-center mt-1">{{ $progressPercent }}%</div>
                                                            </div>
                                                            
                                                            {{-- Status Badge --}}
                                                            <x-ui-badge variant="{{ $isCompleted ? 'success' : ($progressPercent >= 80 ? 'warning' : 'secondary') }}" size="sm">
                                                                {{ $isCompleted ? 'Erreicht' : ($progressPercent >= 80 ? 'Fast erreicht' : 'In Arbeit') }}
                                                            </x-ui-badge>
                                                        </div>
                                                    @endif
                                                @else
                                                    <x-ui-badge variant="secondary" size="sm">Keine Performance</x-ui-badge>
                                                @endif
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
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Noch keine Objectives vorhanden</h4>
                    <p class="text-[var(--ui-muted)] mb-4">Klicken Sie auf "Objective hinzufügen" um zu beginnen</p>
                    <x-ui-button 
                        variant="secondary" 
                        wire:click="addObjective"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erstes Objective hinzufügen</span>
                    </x-ui-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Cycle Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Navigation</h3>
                    <div class="space-y-2">
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            :href="route('okr.dashboard')"
                            wire:navigate
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-home', 'w-4 h-4')
                                Zum Dashboard
                            </span>
                        </x-ui-button>
                        <x-ui-button
                            variant="secondary-outline"
                            size="sm"
                            :href="route('okr.okrs.show', ['okr' => $cycle->okr_id])"
                            wire:navigate
                            class="w-full"
                        >
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-flag', 'w-4 h-4')
                                Zurück zu OKR
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Cycle Performance --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Cycle Performance</h3>
                    <div class="space-y-3">
                        @php
                            $cyclePerformance = $cycle->performance;
                            $totalObjectives = $cycle->objectives->count();
                            $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                            $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                            $progress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                            $performanceScore = $cyclePerformance ? $cyclePerformance->performance_score : $progress;
                        @endphp
                        
                        @if($cyclePerformance)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Gesamt Performance</span>
                                    <span class="text-sm font-bold {{ $cyclePerformance->performance_score >= 80 ? 'text-green-600' : ($cyclePerformance->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $cyclePerformance->performance_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $cyclePerformance->performance_score >= 80 ? 'bg-green-500' : ($cyclePerformance->performance_score >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                         style="width: {{ $cyclePerformance->performance_score }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--ui-muted)]">
                                    {{ $cyclePerformance->completed_objectives }}/{{ $cyclePerformance->total_objectives }} Objectives • 
                                    {{ $cyclePerformance->completed_key_results }}/{{ $cyclePerformance->total_key_results }} Key Results
                                </div>
                            </div>
                        @else
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Fortschritt</span>
                                    <span class="text-sm font-bold {{ $progress >= 80 ? 'text-green-600' : ($progress >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $progress >= 80 ? 'bg-green-500' : ($progress >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--ui-muted)]">
                                    {{ $completedKeyResults }}/{{ $totalKeyResults }} Key Results abgeschlossen
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalObjectives }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Objectives</div>
                            </div>
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-3 text-center">
                                <div class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalKeyResults }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">Key Results</div>
                            </div>
                        </div>
                        

                        {{-- Objective Performance --}}
                        @if($cycle->objectives->count() > 0)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">Objective Performance</span>
                                    <span class="text-xs text-[var(--ui-muted)]">Durchschnitt</span>
                                </div>
                                <div class="space-y-2">
                                    @foreach($cycle->objectives as $objective)
                                        @php
                                            $objKeyResults = $objective->keyResults;
                                            $objCompleted = $objKeyResults->where('performance.is_completed', true)->count();
                                            $objTotal = $objKeyResults->count();
                                            $objProgress = $objTotal > 0 ? round(($objCompleted / $objTotal) * 100) : 0;
                                        @endphp
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2 flex-1">
                                                <div class="w-2 h-2 rounded-full {{ $objProgress >= 80 ? 'bg-green-500' : ($objProgress >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                                                <span class="text-xs text-[var(--ui-secondary)] truncate">{{ Str::limit($objective->title, 20) }}</span>
                                            </div>
                                            <span class="text-xs font-medium {{ $objProgress >= 80 ? 'text-green-600' : ($objProgress >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ $objProgress }}%
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- OKR Performance (falls verfügbar) --}}
                        @if($cycle->okr)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">OKR Performance</span>
                                    <span class="text-sm text-[var(--ui-muted)]">
                                        @php
                                            $okrScore = $cycle->okr->performance_score ?? 0;
                                        @endphp
                                        {{ $okrScore }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                    <div class="bg-[var(--ui-primary)] h-2 rounded-full transition-all duration-300" style="width: {{ $okrScore }}%"></div>
                                </div>
                                <div class="flex items-center justify-between mt-2 text-xs text-[var(--ui-muted)]">
                                    <span>{{ $cycle->okr->title }}</span>
                                    <span>{{ $cycle->okr->status ?? 'Aktiv' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cycle Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Cycle Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Template</span>
                            <span class="text-sm text-[var(--ui-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</span>
                        </div>
                        @if($cycle->template)
                            <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">Zeitraum</span>
                                <span class="text-sm text-[var(--ui-muted)]">{{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">Status</span>
                            <x-ui-badge variant="secondary" size="sm">{{ ucfirst($cycle->status) }}</x-ui-badge>
                        </div>
                    </div>
                </div>

                {{-- Status Control --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Status</h3>
                    <x-ui-input-select
                        name="cycle.status"
                        label="Cycle Status"
                        :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'ending_soon' => 'Endet bald', 'past' => 'Vergangen']"
                        :nullable="false"
                        wire:model.live="cycle.status"
                        required
                    />
                </div>

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button 
                            variant="secondary" 
                            wire:click="addObjective"
                            class="w-full"
                        >
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span class="ml-1">Objective hinzufügen</span>
                        </x-ui-button>
                        
                        <x-ui-confirm-button 
                            action="deleteCycle" 
                            text="Zyklus löschen" 
                            confirmText="Wirklich löschen?" 
                            variant="danger"
                            class="w-full"
                            :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                        />
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
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Letzte Aktivitäten</h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-full flex items-center justify-center text-xs font-semibold">
                                @svg('heroicon-o-calendar', 'w-4 h-4')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-[var(--ui-secondary)] text-sm">Cycle erstellt</div>
                                <div class="text-xs text-[var(--ui-muted)]">{{ $cycle->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        @if($cycle->objectives->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                                <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-flag', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] text-sm">{{ $cycle->objectives->count() }} Objectives hinzugefügt</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Letzte Änderung: {{ $cycle->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endif

                        @php
                            $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                        @endphp
                        @if($totalKeyResults > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] text-sm">{{ $totalKeyResults }} Key Results definiert</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Messbare Ziele gesetzt</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Performance Overview --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Performance</h3>
                    <div class="space-y-3">
                        @php
                            $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                            $progress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                        @endphp
                        
                        <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">Gesamtfortschritt</span>
                                <span class="text-sm font-bold text-[var(--ui-primary)]">{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-2">
                                <div class="bg-[var(--ui-primary)] h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="text-center p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="text-lg font-bold text-green-600">{{ $completedKeyResults }}</div>
                                <div class="text-xs text-green-600">Erreicht</div>
                            </div>
                            <div class="text-center p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="text-lg font-bold text-orange-600">{{ $totalKeyResults - $completedKeyResults }}</div>
                                <div class="text-xs text-orange-600">Offen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Schnellübersicht</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-sm text-[var(--ui-secondary)]">Template</span>
                            <span class="text-sm font-medium text-[var(--ui-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-sm text-[var(--ui-secondary)]">Status</span>
                            <x-ui-badge variant="secondary" size="xs">{{ ucfirst($cycle->status) }}</x-ui-badge>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-sm text-[var(--ui-secondary)]">Erstellt</span>
                            <span class="text-sm font-medium text-[var(--ui-muted)]">{{ $cycle->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

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

            @if($keyResultValueType === 'boolean')
                {{-- Boolean: Einfach Checkbox für aktuellen Zustand --}}
                <div class="space-y-4">
                    <div class="p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)] rounded-lg">
                        <div class="text-sm text-[var(--ui-secondary)] font-medium mb-2">Boolean Key Result</div>
                        <div class="text-xs text-[var(--ui-muted)]">Ziel: Immer erreicht (1) | Aktuell: Wird durch Checkbox gesetzt</div>
                    </div>
                    
                    <x-ui-input-checkbox
                        model="keyResultCurrentValue"
                        label="Erreicht"
                        wire:model.live="keyResultCurrentValue"
                    />
                </div>
            @else
                {{-- Andere Typen: Normale Eingabefelder --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="keyResultTargetValue"
                        label="Zielwert"
                        wire:model.live="keyResultTargetValue"
                        :placeholder="match($keyResultValueType) {
                            'percentage' => 'z.B. 80',
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
                            'absolute' => 'z.B. 60',
                            default => 'Aktueller Wert (optional)'
                        }"
                    />
                </div>
            @endif

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

        <div class="space-y-6">
            {{-- Titel und Beschreibung --}}
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

            {{-- Performance Info und Update --}}
            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Performance Update</h3>
                        <p class="text-sm text-[var(--ui-muted)]">Neuen aktuellen Wert hinzufügen</p>
                    </div>
                </div>

                {{-- Aktuelle Performance Info --}}
                @php
                    $editingKeyResult = null;
                    if($this->editingKeyResultId) {
                        $editingKeyResult = \Platform\Okr\Models\KeyResult::with('performance')->find($this->editingKeyResultId);
                    }
                    $currentPerformance = $editingKeyResult?->performance;
                @endphp
                @if($currentPerformance)
                    @php
                        // Hole den ersten Performance-Wert als Startwert
                        $firstPerformance = $editingKeyResult->performances()->orderBy('created_at', 'asc')->first();
                        $startValue = $firstPerformance?->current_value ?? 0;
                    @endphp
                    
                    <div class="bg-white rounded-lg border border-[var(--ui-border)]/40 p-3 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-[var(--ui-muted)]">Startwert:</div>
                            <div class="text-sm font-medium text-blue-600">
                                {{ $startValue }}@if($currentPerformance->type === 'percentage')% @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-sm text-[var(--ui-muted)]">Zielwert:</div>
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">
                                {{ $currentPerformance->target_value }}@if($currentPerformance->type === 'percentage')% @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-sm text-[var(--ui-muted)]">Aktueller Wert:</div>
                            <div class="text-sm font-medium text-[var(--ui-primary)]">
                                {{ $currentPerformance->current_value }}@if($currentPerformance->type === 'percentage')% @endif
                            </div>
                        </div>
                        
                        {{-- Fortschrittsbalken --}}
                        @php
                            $target = $currentPerformance->target_value ?? 0;
                            $current = $currentPerformance->current_value ?? 0;
                            $type = $currentPerformance->type;
                            
                            // Berechne Fortschritt basierend auf Startwert
                            $progressPercent = 0;
                            $isNegativeProgress = false;
                            $isRueckschritt = false;
                            
                            if ($type === 'boolean') {
                                $progressPercent = $currentPerformance->is_completed ? 100 : 0;
                            } elseif ($type === 'percentage' || $type === 'absolute') {
                                if ($target > $startValue) {
                                    // Positive Entwicklung: Start → Ziel
                                    $progressPercent = min(100, max(-100, round((($current - $startValue) / ($target - $startValue)) * 100)));
                                    if ($progressPercent < 0) {
                                        $isRueckschritt = true;
                                    }
                                } elseif ($target < $startValue) {
                                    // Negative Entwicklung: Start → Ziel (z.B. 100 → 50)
                                    $progressPercent = min(100, max(-100, round((($startValue - $current) / ($startValue - $target)) * 100)));
                                    $isNegativeProgress = true;
                                    if ($progressPercent < 0) {
                                        $isRueckschritt = true;
                                    }
                                } else {
                                    // Start = Ziel
                                    $progressPercent = $current >= $target ? 100 : 0;
                                }
                            }
                            
                            // Bestimme Fortschrittsfarbe
                            $progressColor = 'bg-[var(--ui-primary)]';
                            $progressTextColor = 'text-[var(--ui-primary)]';
                            
                            if ($isRueckschritt) {
                                // Rückschritt: Rot
                                $progressColor = 'bg-red-500';
                                $progressTextColor = 'text-red-600';
                            } elseif ($isNegativeProgress) {
                                // Negative Entwicklung (Reduktion)
                                if ($progressPercent >= 80) {
                                    $progressColor = 'bg-green-500';
                                    $progressTextColor = 'text-green-600';
                                } elseif ($progressPercent >= 50) {
                                    $progressColor = 'bg-yellow-500';
                                    $progressTextColor = 'text-yellow-600';
                                } else {
                                    $progressColor = 'bg-red-500';
                                    $progressTextColor = 'text-red-600';
                                }
                            } else {
                                // Positive Entwicklung
                                if ($progressPercent >= 80) {
                                    $progressColor = 'bg-green-500';
                                    $progressTextColor = 'text-green-600';
                                } elseif ($progressPercent >= 50) {
                                    $progressColor = 'bg-yellow-500';
                                    $progressTextColor = 'text-yellow-600';
                                } else {
                                    $progressColor = 'bg-red-500';
                                    $progressTextColor = 'text-red-600';
                                }
                            }
                        @endphp
                        
                        <div class="mt-3 pt-3 border-t border-[var(--ui-border)]/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[var(--ui-muted)]">
                                    @if($isRueckschritt)
                                        Rückschritt
                                    @elseif($isNegativeProgress)
                                        Reduktion
                                    @else
                                        Fortschritt
                                    @endif
                                </span>
                                <span class="text-xs font-medium {{ $progressTextColor }}">
                                    @if($progressPercent < 0)
                                        {{ $progressPercent }}%
                                    @else
                                        {{ $progressPercent }}%
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-[var(--ui-border)]/40 rounded-full h-1.5">
                                <div class="{{ $progressColor }} h-1.5 rounded-full transition-all duration-300" style="width: {{ abs($progressPercent) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Neuer Wert Eingabe --}}
                @if($keyResultValueType === 'boolean')
                    {{-- Boolean: Toggle für neuen Status --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-[var(--ui-secondary)]">Neuer Status:</div>
                        <x-ui-input-checkbox
                            model="keyResultCurrentValue"
                            label="Erreicht"
                            wire:model.live="keyResultCurrentValue"
                        />
                    </div>
                @else
                    {{-- Andere Typen: Neuer aktueller Wert --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-[var(--ui-secondary)]">Neuer aktueller Wert:</div>
                        <x-ui-input-text
                            name="keyResultCurrentValue"
                            label=""
                            wire:model.live="keyResultCurrentValue"
                            :placeholder="match($keyResultValueType) {
                                'percentage' => 'z.B. 45',
                                'absolute' => 'z.B. 60',
                                default => 'Neuen Wert eingeben...'
                            }"
                            required
                        />
                    </div>
                @endif
            </div>

            {{-- Performance Historie --}}
            @php
                // $editingKeyResult ist bereits oben definiert
                if($editingKeyResult) {
                    $editingKeyResult->load('performances');
                }
            @endphp
            
            @if($editingKeyResult && $editingKeyResult->performances->count() > 1)
                <div class="bg-white rounded-lg border border-[var(--ui-border)]/40 p-4">
                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-3">Performance Historie</h4>
                    <div class="space-y-2">
                        @foreach($editingKeyResult->performances->sortByDesc('created_at') as $performance)
                            <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="text-xs text-[var(--ui-muted)]">
                                        {{ $performance->created_at->format('d.m.Y H:i') }}
                                    </div>
                                    <div class="text-sm text-[var(--ui-secondary)]">
                                        {{ $performance->current_value }}@if($performance->type === 'percentage')% @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($performance->is_completed)
                                        <x-ui-badge variant="success" size="xs">Erreicht</x-ui-badge>
                                    @else
                                        <x-ui-badge variant="secondary" size="xs">In Arbeit</x-ui-badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex justify-between items-center">
                <div class="text-xs text-[var(--ui-muted)]">
                    @if($editingKeyResult && $editingKeyResult->performances->count() > 1)
                        {{ $editingKeyResult->performances->count() }} Performance-Updates vorhanden
                    @else
                        Erste Performance-Änderung
                    @endif
                </div>
                <div class="flex gap-3">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-ghost" 
                        size="sm"
                        wire:click="closeKeyResultEditModal"
                    >
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        <span class="ml-1">Abbrechen</span>
                    </x-ui-button>
                    <x-ui-button 
                        type="button" 
                        variant="primary" 
                        size="sm"
                        wire:click="saveKeyResult"
                    >
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span class="ml-1">Performance aktualisieren</span>
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Delete durch x-ui-confirm-button, kein separates Modal notwendig --}}

</x-ui-page>