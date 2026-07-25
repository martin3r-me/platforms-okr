<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Zielsteuerung', 'href' => route('okr.dashboard'), 'icon' => 'flag'],
            ['label' => 'Zielsteuerungen', 'href' => route('okr.okrs.index')],
            ['label' => $cycle->okr->title, 'href' => route('okr.okrs.show', ['okr' => $cycle->okr->id])],
            ['label' => $cycle->template?->label ?? 'Zyklus'],
        ]">
            @if($this->isDirty)
                <x-nx-button variant="primary" size="sm" wire:click="save">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    <span>Speichern</span>
                </x-nx-button>
            @endif
            <x-nx-button variant="ghost" size="sm" wire:click="addObjective">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Objective hinzufügen</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Flash Messages --}}
        @if(session()->has('message'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)]">{{ session('message') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                <p class="text-[var(--nx-text)] font-medium">Fehler:</p>
                <p class="text-[var(--nx-muted)]">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Cycle Header --}}
        <div class="bg-gradient-to-r from-[var(--nx-bg)] to-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-calendar', 'w-6 h-6')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--nx-text)] tracking-tight">{{ $cycle->template?->label ?? 'Unbekannter Cycle' }}</h1>
                            <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)] mt-1">
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
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalObjectives }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Objectives</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $totalKeyResults }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Erfolgskriterien</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold text-[var(--nx-accent)]">{{ $completedKeyResults }}</div>
                            <div class="text-xs text-[var(--nx-muted)]">Abgeschlossen</div>
                        </div>
                        <div class="text-center p-4 bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)]">
                            <div class="text-2xl font-bold {{ $performanceScore >= 80 ? 'text-[color:var(--nx-success)]' : ($performanceScore >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">{{ $performanceScore }}%</div>
                            <div class="text-xs text-[var(--nx-muted)]">{{ $cyclePerformance ? 'Performance' : 'Fortschritt' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cycle Details entfernt (Info im Header enthalten) --}}

        {{-- Strategic Documents Section (Read-Only) --}}
        @if($this->mission || $this->vision)
            <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                            @svg('heroicon-o-document-text', 'w-4 h-4')
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-[var(--nx-text)]">Strategische Orientierung</h3>
                            <p class="text-sm text-[var(--nx-muted)]">Mission & Vision (Read-Only)</p>
                        </div>
                    </div>
                    <x-nx-button variant="secondary" size="sm" :href="route('okr.strategic-documents.index')" wire:navigate>
                        @svg('heroicon-o-pencil', 'w-4 h-4')
                        <span class="ml-1">Verwalten</span>
                    </x-nx-button>
                </div>

                <div class="space-y-4">
                    {{-- Mission --}}
                    @if($this->mission)
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 bg-[color:var(--nx-info)] text-white rounded flex items-center justify-center">
                                    @svg('heroicon-o-document-text', 'w-3 h-3')
                                </div>
                                <h4 class="font-semibold text-[var(--nx-text)] text-sm">🧭 Mission</h4>
                                <span class="text-xs text-[var(--nx-muted)] ml-auto">
                                    v{{ $this->mission->version }} • {{ $this->mission->valid_from->format('d.m.Y') }}
                                </span>
                            </div>
                            <div class="prose prose-sm max-w-none text-[var(--nx-text)] text-sm">
                                {!! \Illuminate\Support\Str::markdown(\Illuminate\Support\Str::limit($this->mission->content ?? '', 200)) !!}
                            </div>
                        </div>
                    @endif

                    {{-- Vision --}}
                    @if($this->vision)
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-5 h-5 bg-[color:var(--nx-tone-violet)] text-white rounded flex items-center justify-center">
                                    @svg('heroicon-o-sun', 'w-3 h-3')
                                </div>
                                <h4 class="font-semibold text-[var(--nx-text)] text-sm">🌄 Vision</h4>
                                <span class="text-xs text-[var(--nx-muted)] ml-auto">
                                    v{{ $this->vision->version }} • {{ $this->vision->valid_from->format('d.m.Y') }}
                                </span>
                            </div>
                            <div class="prose prose-sm max-w-none text-[var(--nx-text)] text-sm">
                                {!! \Illuminate\Support\Str::markdown(\Illuminate\Support\Str::limit($this->vision->content ?? '', 200)) !!}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- Objectives & Key Results --}}
        <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-flag', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--nx-text)]">Ziele & Erfolgskriterien</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Ziele und Messgrößen verwalten</p>
                    </div>
                </div>
                <x-nx-button 
                    variant="secondary" 
                    size="sm"
                    wire:click="addObjective"
                >
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span class="ml-1">Objective hinzufügen</span>
                </x-nx-button>
            </div>

            @if($cycle->objectives->count() > 0)
                <div wire:sortable="updateObjectiveOrder" wire:sortable-group="updateKeyResultOrder" wire:sortable.options="{ animation: 150 }">
                    @foreach($cycle->objectives->sortBy('order') as $objective)
                        <div wire:sortable.item="{{ $objective->id }}" wire:key="objective-{{ $objective->id }}" class="mb-6 p-6 border border-[color:var(--nx-line)] rounded-lg bg-[var(--nx-bg)] hover:border-[color:var(--nx-line)] transition-colors">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex-grow-1">
                                    <div class="flex items-center gap-3">
                                        <div class="font-medium text-lg text-[var(--nx-text)]">{{ $objective->title }}</div>
                                        <x-nx-badge variant="neutral" size="sm">{{ $objective->keyResults->count() }} KR</x-nx-badge>
                                        @php
                                            $objKeyResults = $objective->keyResults;
                                            $objCompleted = $objKeyResults->where('performance.is_completed', true)->count();
                                            $objTotal = $objKeyResults->count();
                                            $objProgress = $objTotal > 0 ? round(($objCompleted / $objTotal) * 100) : 0;
                                        @endphp
                                        <x-nx-badge 
                                            variant="{{ $objProgress >= 80 ? 'success' : ($objProgress >= 50 ? 'warning' : 'secondary') }}" 
                                            size="sm"
                                        >
                                            {{ $objProgress }}%
                                        </x-nx-badge>
                                    </div>
                                    @if($objective->description)
                                        <div class="text-sm text-[var(--nx-muted)] mt-2">{{ Str::limit($objective->description, 100) }}</div>
                                    @endif
                                    @if($objective->milestones->count() > 0)
                                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                            @foreach($objective->milestones as $milestone)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[var(--nx-tone-violet)]/10 text-[color:var(--nx-tone-violet)] border border-[var(--nx-tone-violet)]/30">
                                                    @svg('heroicon-o-flag', 'w-3 h-3')
                                                    {{ $milestone->title }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <x-nx-button 
                                        size="sm" 
                                        variant="secondary" 
                                        wire:click="addKeyResult({{ $objective->id }})"
                                    >
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-plus', 'w-4 h-4')
                                            EK hinzufügen
                                        </div>
                                    </x-nx-button>
                                    <x-nx-button 
                                        size="sm" 
                                        variant="ghost" 
                                        wire:click="editObjective({{ $objective->id }})"
                                    >
                                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                    </x-nx-button>
                                    <div wire:sortable.handle class="cursor-move p-2 text-[var(--nx-muted)] hover:text-[var(--nx-accent)]">
                                        @svg('heroicon-o-bars-3', 'w-4 h-4')
                                    </div>
                                </div>
                            </div>

                            @if($objective->keyResults->count() > 0)
                                <div wire:sortable-group.item-group="{{ $objective->id }}" wire:sortable-group.options="{ animation: 100 }" class="space-y-3">
                                    @foreach($objective->keyResults->sortBy('order') as $keyResult)
                                        <div wire:sortable-group.item="{{ $keyResult->id }}" wire:key="keyresult-{{ $keyResult->id }}" 
                                             class="flex items-center gap-3 p-4 bg-[color:var(--nx-surface)] rounded border border-[color:var(--nx-line)] hover:border-[color:var(--nx-line)] transition-colors cursor-pointer" 
                                             wire:click="editKeyResult({{ $keyResult->id }})">
                                            <div wire:sortable-group.handle class="cursor-move p-1 text-[var(--nx-muted)] hover:text-[var(--nx-accent)] flex-shrink-0" wire:click.stop>
                                                @svg('heroicon-o-bars-3', 'w-3 h-3')
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="font-medium text-sm text-[var(--nx-text)]">{{ $keyResult->title }}</div>
                                                @if($keyResult->description)
                                                    <div class="text-xs text-[var(--nx-muted)] mt-1">{{ Str::limit($keyResult->description, 60) }}</div>
                                                @endif
                                                
                                                {{-- Verknüpfungen (Contexts) --}}
                                                @php
                                                    $primaryContexts = $keyResult->primaryContexts()->with('context')->get();
                                                @endphp
                                                @if($primaryContexts->count() > 0 || $keyResult->milestones->count() > 0)
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                        @foreach($primaryContexts as $context)
                                                            @php
                                                                $contextModel = $context->context;
                                                                $isContextDone = $contextModel && !is_null(data_get($contextModel, 'done_at'));
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium border {{ $isContextDone ? 'bg-[var(--nx-success)]/10 text-[var(--nx-success)] border-[var(--nx-success)]/20' : 'bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] border-[var(--nx-accent)]/20' }}">
                                                                @if($isContextDone)
                                                                    @svg('heroicon-o-check-circle', 'w-3 h-3')
                                                                @else
                                                                    @svg('heroicon-o-link', 'w-3 h-3')
                                                                @endif
                                                                <span class="{{ $isContextDone ? 'line-through' : '' }}">
                                                                    {{ $context->context_label ?? class_basename($context->context_type) }}
                                                                </span>
                                                            </span>
                                                        @endforeach
                                                        @foreach($keyResult->milestones as $milestone)
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[var(--nx-tone-violet)]/10 text-[color:var(--nx-tone-violet)] border border-[var(--nx-tone-violet)]/30">
                                                                @svg('heroicon-o-flag', 'w-3 h-3')
                                                                {{ $milestone->title }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                
                                                {{-- Verantwortlicher --}}
                                                @if($keyResult->manager)
                                                    <div class="flex items-center gap-1.5 mt-1.5">
                                                        <span class="text-xs text-[var(--nx-muted)]">Verantwortlich:</span>
                                                        <span class="text-xs font-medium text-[var(--nx-text)]">{{ $keyResult->manager->fullname ?? $keyResult->manager->name }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-4 flex-shrink-0" wire:click.stop>
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
                                                        <x-nx-button
                                                            type="button"
                                                            variant="{{ $isCompleted ? 'success' : 'secondary-outline' }}"
                                                            size="sm"
                                                            wire:click="toggleBooleanKeyResult({{ $keyResult->id }})"
                                                        >
                                                            @svg('heroicon-o-check', 'w-4 h-4')
                                                            {{ $isCompleted ? 'Erledigt' : 'Erledigen' }}
                                                        </x-nx-button>
                                                    @else
                                                        {{-- Inline Edit Mode --}}
                                                        @if($this->inlineEditKeyResultId === $keyResult->id)
                                                            <div class="flex items-center gap-2" wire:click.stop>
                                                                <div class="flex items-center gap-1">
                                                                    <input
                                                                        type="number"
                                                                        step="any"
                                                                        wire:model="inlineEditValue"
                                                                        wire:keydown.enter="saveInlineEdit"
                                                                        wire:keydown.escape="cancelInlineEdit"
                                                                        class="w-20 px-2 py-1 text-sm border border-[var(--nx-accent)] rounded-md bg-[color:var(--nx-surface)] text-[var(--nx-text)] focus:outline-none focus:ring-2 focus:ring-[var(--nx-accent)]/30"
                                                                        autofocus
                                                                    />
                                                                    <span class="text-xs text-[var(--nx-muted)]">/ {{ $target }}@if($type==='percentage')%@endif</span>
                                                                </div>
                                                                <x-nx-button type="button" variant="primary" size="xs" wire:click="saveInlineEdit">
                                                                    @svg('heroicon-o-check', 'w-3.5 h-3.5')
                                                                </x-nx-button>
                                                                <x-nx-button type="button" variant="ghost" size="xs" wire:click="cancelInlineEdit">
                                                                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                                                </x-nx-button>
                                                            </div>
                                                        @else
                                                            {{-- Display Mode (klickbar zum Editieren) --}}
                                                            <div class="flex items-center gap-3 cursor-pointer group" wire:click.stop="startInlineEdit({{ $keyResult->id }})" title="Klicken um Wert zu aktualisieren">
                                                                {{-- Progress Bar --}}
                                                                <div class="w-24">
                                                                    <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2">
                                                                        <div class="{{ $progressPercent >= 80 ? 'bg-[color:var(--nx-success)]' : ($progressPercent >= 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[var(--nx-accent)]') }} h-2 rounded-full transition-all duration-300"
                                                                             style="width: {{ $progressPercent }}%"></div>
                                                                    </div>
                                                                    <div class="text-xs text-[var(--nx-muted)] text-center mt-0.5">{{ $progressPercent }}%</div>
                                                                </div>

                                                                {{-- Ist / Ziel kompakt --}}
                                                                <div class="text-xs text-[var(--nx-muted)] tabular-nums">
                                                                    <span class="font-semibold text-[var(--nx-text)] group-hover:text-[var(--nx-accent)] transition-colors">{{ $current }}</span>
                                                                    <span class="mx-0.5">/</span>
                                                                    <span>{{ $target }}</span>
                                                                    @if($type==='percentage')<span>%</span>@endif
                                                                </div>

                                                                {{-- Edit-Hint bei Hover --}}
                                                                <span class="opacity-0 group-hover:opacity-100 transition-opacity">
                                                                    @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5 text-[var(--nx-muted)]')
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @endif
                                                @else
                                                    <x-nx-badge variant="neutral" size="sm">Keine Performance</x-nx-badge>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center p-8 text-[var(--nx-muted)]">
                                    <div class="text-sm">Keine Erfolgskriterien</div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
                        @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--nx-muted)]')
                    </div>
                    <h4 class="text-lg font-medium text-[var(--nx-text)] mb-2">Noch keine Objectives vorhanden</h4>
                    <p class="text-[var(--nx-muted)] mb-4">Klicken Sie auf "Objective hinzufügen" um zu beginnen</p>
                    <x-nx-button 
                        variant="secondary" 
                        wire:click="addObjective"
                    >
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span class="ml-1">Erstes Objective hinzufügen</span>
                    </x-nx-button>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Cycle Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Embedded: Navigation ausgeblendet --}}

                {{-- Cycle Performance --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Cycle Performance</h3>
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
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Gesamt Performance</span>
                                    <span class="text-sm font-bold {{ $cyclePerformance->performance_score >= 80 ? 'text-[color:var(--nx-success)]' : ($cyclePerformance->performance_score >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                        {{ $cyclePerformance->performance_score }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $cyclePerformance->performance_score >= 80 ? 'bg-[color:var(--nx-success)]' : ($cyclePerformance->performance_score >= 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]') }}" 
                                         style="width: {{ $cyclePerformance->performance_score }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--nx-muted)]">
                                    {{ $cyclePerformance->completed_objectives }}/{{ $cyclePerformance->total_objectives }} Objectives • 
                                    {{ $cyclePerformance->completed_key_results }}/{{ $cyclePerformance->total_key_results }} Erfolgskriterien
                                </div>
                            </div>
                        @else
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Fortschritt</span>
                                    <span class="text-sm font-bold {{ $progress >= 80 ? 'text-[color:var(--nx-success)]' : ($progress >= 50 ? 'text-[color:var(--nx-warning)]' : 'text-[color:var(--nx-danger)]') }}">
                                        {{ $progress }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2 mb-2">
                                    <div class="h-2 rounded-full {{ $progress >= 80 ? 'bg-[color:var(--nx-success)]' : ($progress >= 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-danger)]') }}" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                                <div class="text-xs text-[var(--nx-muted)]">
                                    {{ $completedKeyResults }}/{{ $totalKeyResults }} Erfolgskriterien abgeschlossen
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalObjectives }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Objectives</div>
                            </div>
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-3 text-center">
                                <div class="text-lg font-bold text-[var(--nx-accent)]">{{ $totalKeyResults }}</div>
                                <div class="text-xs text-[var(--nx-muted)]">Erfolgskriterien</div>
                            </div>
                        </div>
                        

                        {{-- Objective Performance Übersicht entfernt --}}

                        {{-- OKR Performance (falls verfügbar) --}}
                        @if($cycle->okr)
                            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-[var(--nx-text)]">Zielsteuerung Performance</span>
                                    <span class="text-sm text-[var(--nx-muted)]">
                                        @php
                                            // Model-Cache ist [0,1] (decimal(4,3)) → für Anzeige auf 0–100.
                                            $okrScore = round(($cycle->okr->performance_score ?? 0) * 100);
                                        @endphp
                                        {{ $okrScore }}%
                                    </span>
                                </div>
                                <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2">
                                    <div class="bg-[var(--nx-accent)] h-2 rounded-full transition-all duration-300" style="width: {{ $okrScore }}%"></div>
                                </div>
                                <div class="flex items-center justify-between mt-2 text-xs text-[var(--nx-muted)]">
                                    <span>{{ $cycle->okr->title }}</span>
                                    <span>{{ $cycle->okr->status ?? 'Aktiv' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cycle Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Cycle Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Template</span>
                            <span class="text-sm text-[var(--nx-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</span>
                        </div>
                        @if($cycle->template)
                            <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                                <span class="text-sm font-medium text-[var(--nx-text)]">Zeitraum</span>
                                <span class="text-sm text-[var(--nx-muted)]">{{ $cycle->template->starts_at?->format('d.m.Y') }} - {{ $cycle->template->ends_at?->format('d.m.Y') }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-3 px-4 bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)]">
                            <span class="text-sm font-medium text-[var(--nx-text)]">Status</span>
                            <x-nx-badge variant="neutral" size="sm">{{ ucfirst($cycle->status) }}</x-nx-badge>
                        </div>
                    </div>
                </div>

                {{-- Status Control --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Status</h3>
                    <x-nx-input-select
                        name="cycle.status"
                        label="Cycle Status"
                        :options="['draft' => 'Entwurf', 'active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'ending_soon' => 'Endet bald', 'past' => 'Vergangen']"
                        :nullable="false"
                        wire:model.live="cycle.status"
                        required
                    />
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
                            <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-full flex items-center justify-center text-xs font-semibold">
                                @svg('heroicon-o-calendar', 'w-4 h-4')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-[var(--nx-text)] text-sm">Cycle erstellt</div>
                                <div class="text-xs text-[var(--nx-muted)]">{{ $cycle->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        @if($cycle->objectives->count() > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-success)]/10 text-[color:var(--nx-success)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-flag', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $cycle->objectives->count() }} Objectives hinzugefügt</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Letzte Änderung: {{ $cycle->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endif

                        @php
                            $totalKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->count());
                        @endphp
                        @if($totalKeyResults > 0)
                            <div class="flex items-start gap-3 p-3 rounded-lg border border-[color:var(--nx-line)] bg-[var(--nx-bg)]">
                                <div class="w-8 h-8 bg-[var(--nx-info)]/10 text-[color:var(--nx-info)] rounded-full flex items-center justify-center text-xs font-semibold">
                                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[var(--nx-text)] text-sm">{{ $totalKeyResults }} Erfolgskriterien definiert</div>
                                    <div class="text-xs text-[var(--nx-muted)]">Messbare Ziele gesetzt</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Performance Overview --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Performance</h3>
                    <div class="space-y-3">
                        @php
                            $completedKeyResults = $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
                            $progress = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
                        @endphp
                        
                        <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-[var(--nx-text)]">Gesamtfortschritt</span>
                                <span class="text-sm font-bold text-[var(--nx-accent)]">{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-[color:var(--nx-line)] rounded-full h-2">
                                <div class="bg-[var(--nx-accent)] h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="text-center p-3 bg-[var(--nx-success)]/10 border border-[var(--nx-success)]/30 rounded-lg">
                                <div class="text-lg font-bold text-[color:var(--nx-success)]">{{ $completedKeyResults }}</div>
                                <div class="text-xs text-[color:var(--nx-success)]">Erreicht</div>
                            </div>
                            <div class="text-center p-3 bg-[var(--nx-warning)]/10 border border-[var(--nx-warning)]/30 rounded-lg">
                                <div class="text-lg font-bold text-[color:var(--nx-warning)]">{{ $totalKeyResults - $completedKeyResults }}</div>
                                <div class="text-xs text-[color:var(--nx-warning)]">Offen</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--nx-text)] uppercase tracking-wider mb-4">Schnellübersicht</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Template</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $cycle->template?->label ?? 'Kein Template' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Status</span>
                            <x-nx-badge variant="neutral" size="xs">{{ ucfirst($cycle->status) }}</x-nx-badge>
                        </div>
                        <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                            <span class="text-sm text-[var(--nx-text)]">Erstellt</span>
                            <span class="text-sm font-medium text-[var(--nx-muted)]">{{ $cycle->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <!-- Objective Create Modal -->
    <x-nx-modal
        size="lg"
        model="objectiveCreateModalShow"
    >
        <x-slot name="header">
            Objective hinzufügen
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveObjective" class="space-y-4">
                <x-nx-input-text
                    name="objectiveForm.title"
                    label="Titel"
                    wire:model.live="objectiveForm.title"
                    placeholder="Titel des Objective eingeben..."
                    required
                />

                <x-nx-input-textarea
                    name="objectiveForm.description"
                    label="Beschreibung"
                    wire:model.live="objectiveForm.description"
                    placeholder="Detaillierte Beschreibung des Objective (optional)"
                    rows="3"
                />

                <x-nx-input-number
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
                <x-nx-button 
                    type="button" 
                    variant="ghost" 
                    wire:click="closeObjectiveCreateModal"
                >
                    Abbrechen
                </x-nx-button>
                <x-nx-button type="button" variant="secondary" wire:click="saveObjective">
                    Hinzufügen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Objective Edit Modal -->
    <x-nx-modal
        size="lg"
        model="objectiveEditModalShow"
    >
        <x-slot name="header">
            Objective bearbeiten
        </x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="saveObjective" class="space-y-4">
                <x-nx-input-text
                    name="objectiveForm.title"
                    label="Titel"
                    wire:model.live="objectiveForm.title"
                    placeholder="Titel des Objective eingeben..."
                    required
                />

                <x-nx-input-textarea
                    name="objectiveForm.description"
                    label="Beschreibung"
                    wire:model.live="objectiveForm.description"
                    placeholder="Detaillierte Beschreibung des Objective (optional)"
                    rows="3"
                />

                <x-nx-input-number
                    name="objectiveForm.order"
                    label="Reihenfolge"
                    wire:model.live="objectiveForm.order"
                    min="0"
                    required
                />

                <x-nx-input-select
                    name="objectiveSelectedMilestoneIds"
                    label="Meilensteine"
                    :options="$this->availableMilestones->toArray()"
                    wire:model="objectiveSelectedMilestoneIds"
                    :nullable="true"
                    :multiple="true"
                    placeholder="Meilensteine auswählen..."
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-nx-button variant="ghost" size="sm" wire:click="deleteObjectiveAndCloseModal" wire:confirm="Wirklich löschen?">Löschen</x-nx-button>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <x-nx-button 
                        type="button" 
                        variant="ghost" 
                        wire:click="closeObjectiveEditModal"
                    >
                        Abbrechen
                    </x-nx-button>
                    <x-nx-button type="button" variant="secondary" wire:click="saveObjective">
                        Speichern
                    </x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Key Result Create Modal -->
    <x-nx-modal
        size="lg"
        model="keyResultCreateModalShow"
    >
        <x-slot name="header">
            Erfolgskriterium hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-nx-input-text
                name="keyResultTitle"
                label="Titel"
                wire:model.live="keyResultTitle"
                placeholder="Titel des Erfolgskriteriums eingeben..."
                required
            />

            <x-nx-input-textarea
                name="keyResultDescription"
                label="Beschreibung"
                wire:model.live="keyResultDescription"
                placeholder="Beschreibung des Erfolgskriteriums (optional)"
                rows="3"
            />

            <x-nx-input-select
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
                    <div class="p-4 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                        <div class="text-sm text-[var(--nx-text)] font-medium mb-2">Boolean Erfolgskriterium</div>
                        <div class="text-xs text-[var(--nx-muted)]">Ziel: Immer erreicht (1) | Aktuell: Wird durch Checkbox gesetzt</div>
                    </div>
                    
                    <x-nx-input-checkbox
                        model="keyResultCurrentValue"
                        label="Erreicht"
                        wire:model.live="keyResultCurrentValue"
                    />
                </div>
            @else
                {{-- Andere Typen: Normale Eingabefelder --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-nx-input-text
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

                    <x-nx-input-text
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
                <x-nx-input-text
                    name="keyResultUnit"
                    label="Einheit"
                    wire:model.live="keyResultUnit"
                    placeholder="z.B. Stück, €, Kunden, etc."
                />
            @endif

            @if($keyResultValueType === 'boolean')
                <div class="p-3 bg-[var(--nx-bg)] border border-[color:var(--nx-line)] rounded-lg">
                    <div class="text-sm text-[var(--nx-text)]">
                        <strong>Boolean-Werte:</strong> Verwende "Ja", "Nein", "Erledigt", "Nicht erledigt", "Implementiert", etc.
                    </div>
                </div>
            @endif
            
            {{-- Verantwortlicher (nur aus OKR-Mitgliedern) --}}
            <x-nx-input-select
                name="keyResultManagerUserId"
                label="Verantwortlicher"
                wire:model.live="keyResultManagerUserId"
                :options="$this->okrMembers->pluck('fullname', 'id')->toArray()"
                nullLabel="– Verantwortlichen auswählen –"
                :nullable="true"
            />

            <x-nx-input-select
                name="keyResultSelectedMilestoneIds"
                label="Meilensteine"
                :options="$this->availableMilestones->toArray()"
                wire:model="keyResultSelectedMilestoneIds"
                :nullable="true"
                :multiple="true"
                placeholder="Meilensteine auswählen..."
            />
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-nx-button
                    type="button"
                    variant="ghost"
                    wire:click="closeKeyResultCreateModal"
                >
                    Abbrechen
                </x-nx-button>
                <x-nx-button
                    type="button"
                    variant="secondary"
                    wire:click="saveKeyResult"
                >
                    Hinzufügen
                </x-nx-button>
            </div>
        </x-slot>
    </x-nx-modal>

    <!-- Key Result Edit Modal -->
    <x-nx-modal
        size="lg"
        model="keyResultEditModalShow"
    >
        <x-slot name="header">
            Erfolgskriterium bearbeiten
        </x-slot>

        <div class="space-y-6">
            {{-- Titel und Beschreibung --}}
            <div class="space-y-4">
                <x-nx-input-text
                    name="keyResultTitle"
                    label="Titel"
                    wire:model.live="keyResultTitle"
                    placeholder="Titel des Erfolgskriteriums eingeben..."
                    required
                />

                <x-nx-input-textarea
                    name="keyResultDescription"
                    label="Beschreibung"
                    wire:model.live="keyResultDescription"
                    placeholder="Beschreibung des Erfolgskriteriums (optional)"
                    rows="3"
                />

                {{-- Verantwortlicher (nur aus OKR-Mitgliedern) --}}
                <x-nx-input-select
                    name="keyResultManagerUserId"
                    label="Verantwortlicher"
                    wire:model.live="keyResultManagerUserId"
                    :options="$this->okrMembers->pluck('fullname', 'id')->toArray()"
                    nullLabel="– Verantwortlichen auswählen –"
                    :nullable="true"
                />

                <x-nx-input-select
                    name="keyResultSelectedMilestoneIds"
                    label="Meilensteine"
                    :options="$this->availableMilestones->toArray()"
                    wire:model="keyResultSelectedMilestoneIds"
                    :nullable="true"
                    :multiple="true"
                    placeholder="Meilensteine auswählen..."
                />
            </div>

            {{-- Verknüpfungen (Contexts) --}}
            @php
                $editingKeyResult = null;
                if($this->editingKeyResultId) {
                    $editingKeyResult = \Platform\Okr\Models\KeyResult::with('primaryContexts.context')->find($this->editingKeyResultId);
                }
                $primaryContexts = $editingKeyResult?->primaryContexts ?? collect();
            @endphp
            @if($primaryContexts->count() > 0)
                <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                    <h3 class="text-sm font-semibold text-[var(--nx-text)] mb-3">Verknüpfungen</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($primaryContexts as $context)
                            @php
                                // Loose coupling: wenn das Kontext-Modell ein done_at hat und es nicht null ist -> als erledigt markieren
                                $contextModel = $context->context;
                                $isContextDone = $contextModel && !is_null(data_get($contextModel, 'done_at'));
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium border {{ $isContextDone ? 'bg-[var(--nx-success)]/10 text-[var(--nx-success)] border-[var(--nx-success)]/20' : 'bg-[var(--nx-accent)]/10 text-[var(--nx-accent)] border-[var(--nx-accent)]/20' }}">
                                @if($isContextDone)
                                    @svg('heroicon-o-check-circle', 'w-4 h-4')
                                @else
                                    @svg('heroicon-o-link', 'w-4 h-4')
                                @endif
                                <span class="{{ $isContextDone ? 'line-through' : '' }}">
                                    {{ $context->context_label ?? class_basename($context->context_type) }}
                                </span>
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-[var(--nx-muted)] mt-2">Dieses Erfolgskriterium ist mit den oben genannten Kontexten verknüpft.</p>
                </div>
            @endif

            {{-- Performance Info und Update --}}
            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--nx-text)]">Performance Update</h3>
                        <p class="text-sm text-[var(--nx-muted)]">Neuen aktuellen Wert hinzufügen</p>
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
                    
                    <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-3 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-[var(--nx-muted)]">Startwert:</div>
                            <div class="text-sm font-medium text-[color:var(--nx-info)]">
                                {{ $startValue }}@if($currentPerformance->type === 'percentage')% @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-sm text-[var(--nx-muted)]">Zielwert:</div>
                            <div class="text-sm font-medium text-[var(--nx-text)]">
                                {{ $currentPerformance->target_value }}@if($currentPerformance->type === 'percentage')% @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-sm text-[var(--nx-muted)]">Aktueller Wert:</div>
                            <div class="text-sm font-medium text-[var(--nx-accent)]">
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
                            $progressColor = 'bg-[var(--nx-accent)]';
                            $progressTextColor = 'text-[var(--nx-accent)]';
                            
                            if ($isRueckschritt) {
                                // Rückschritt: Rot
                                $progressColor = 'bg-[color:var(--nx-danger)]';
                                $progressTextColor = 'text-[color:var(--nx-danger)]';
                            } elseif ($isNegativeProgress) {
                                // Negative Entwicklung (Reduktion)
                                if ($progressPercent >= 80) {
                                    $progressColor = 'bg-[color:var(--nx-success)]';
                                    $progressTextColor = 'text-[color:var(--nx-success)]';
                                } elseif ($progressPercent >= 50) {
                                    $progressColor = 'bg-[color:var(--nx-warning)]';
                                    $progressTextColor = 'text-[color:var(--nx-warning)]';
                                } else {
                                    $progressColor = 'bg-[color:var(--nx-danger)]';
                                    $progressTextColor = 'text-[color:var(--nx-danger)]';
                                }
                            } else {
                                // Positive Entwicklung
                                if ($progressPercent >= 80) {
                                    $progressColor = 'bg-[color:var(--nx-success)]';
                                    $progressTextColor = 'text-[color:var(--nx-success)]';
                                } elseif ($progressPercent >= 50) {
                                    $progressColor = 'bg-[color:var(--nx-warning)]';
                                    $progressTextColor = 'text-[color:var(--nx-warning)]';
                                } else {
                                    $progressColor = 'bg-[color:var(--nx-danger)]';
                                    $progressTextColor = 'text-[color:var(--nx-danger)]';
                                }
                            }
                        @endphp
                        
                        <div class="mt-3 pt-3 border-t border-[color:var(--nx-line)]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-[var(--nx-muted)]">
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
                            <div class="w-full bg-[color:var(--nx-line)] rounded-full h-1.5">
                                <div class="{{ $progressColor }} h-1.5 rounded-full transition-all duration-300" style="width: {{ abs($progressPercent) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Neuer Wert Eingabe --}}
                @if($keyResultValueType === 'boolean')
                    {{-- Boolean: Toggle für neuen Status --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-[var(--nx-text)]">Neuer Status:</div>
                        <x-nx-input-checkbox
                            model="keyResultCurrentValue"
                            label="Erreicht"
                            wire:model.live="keyResultCurrentValue"
                        />
                    </div>
                @else
                    {{-- Andere Typen: Neuer aktueller Wert --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-[var(--nx-text)]">Neuer aktueller Wert:</div>
                        <x-nx-input-text
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
                <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-4">
                    <h4 class="text-sm font-semibold text-[var(--nx-text)] mb-3">Performance Historie</h4>
                    <div class="space-y-2">
                        @foreach($editingKeyResult->performances->sortByDesc('created_at') as $performance)
                            <div class="flex items-center justify-between py-2 px-3 bg-[var(--nx-bg)] rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="text-xs text-[var(--nx-muted)]">
                                        {{ $performance->created_at->format('d.m.Y H:i') }}
                                    </div>
                                    <div class="text-sm text-[var(--nx-text)]">
                                        {{ $performance->current_value }}@if($performance->type === 'percentage')% @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($performance->is_completed)
                                        <x-nx-badge variant="success" size="xs">Erreicht</x-nx-badge>
                                    @else
                                        <x-nx-badge variant="neutral" size="xs">In Arbeit</x-nx-badge>
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
                <div class="text-xs text-[var(--nx-muted)]">
                    @if($editingKeyResult && $editingKeyResult->performances->count() > 1)
                        {{ $editingKeyResult->performances->count() }} Performance-Updates vorhanden
                    @else
                        Erste Performance-Änderung
                    @endif
                </div>
                <div class="flex gap-3">
                    <x-nx-button variant="danger" size="sm" wire:click="deleteKeyResultAndCloseModal" wire:confirm="Erfolgskriterium wirklich löschen?">Löschen</x-nx-button>
                    <x-nx-button 
                        type="button" 
                        variant="ghost" 
                        size="sm"
                        wire:click="closeKeyResultEditModal"
                    >
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        <span class="ml-1">Abbrechen</span>
                    </x-nx-button>
                    <x-nx-button 
                        type="button" 
                        variant="primary" 
                        size="sm"
                        wire:click="saveKeyResult"
                    >
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span class="ml-1">Performance aktualisieren</span>
                    </x-nx-button>
                </div>
            </div>
        </x-slot>
    </x-nx-modal>

    {{-- Delete durch x-nx-button (wire:confirm), kein separates Modal notwendig --}}

</x-ui-page>