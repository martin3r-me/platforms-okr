<div class="h-full overflow-y-auto p-6">
    <div class="mb-6 d-flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">OKR Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Übersicht über deine Objectives und Key Results</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xs text-muted">OKRs gesamt</div>
                <div class="text-xl font-semibold">{{ $totalOkrs }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xs text-muted">Aktiv</div>
                <div class="text-xl font-semibold text-green-600">{{ $activeOkrs }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xs text-muted">Endet bald</div>
                <div class="text-xl font-semibold text-yellow-600">{{ $endingSoonOkrs }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-center">
                <div class="text-xs text-muted">Abgeschlossen</div>
                <div class="text-xl font-semibold text-blue-600">{{ $completedOkrs }}</div>
            </div>
        </div>
    </div>

    <div class="mb-4 d-flex items-end gap-3">
        <div class="min-w-48">
            <x-ui-input-select
                name="statusFilter"
                label="Status"
                :options="[
                    'all' => 'Alle',
                    'draft' => 'Entwurf',
                    'active' => 'Aktiv',
                    'completed' => 'Abgeschlossen',
                    'ending_soon' => 'Endet bald',
                    'past' => 'Vergangen'
                ]"
                :nullable="false"
                wire:model.live="statusFilter"
            />
        </div>
        <div class="min-w-64">
            <x-ui-input-select
                name="managerFilter"
                label="Manager"
                :options="$managers"
                optionValue="id"
                optionLabel="name"
                :nullable="true"
                nullLabel="– Alle –"
                wire:model.live="managerFilter"
            />
        </div>
    </div>

    @if($currentCycle)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-blue-900">Aktueller Zyklus</h2>
                    <p class="text-blue-700">{{ $currentCycle->template?->label ?? 'Unbenannter Zyklus' }}</p>
                    <p class="text-sm text-blue-600">
                        {{ $currentCycle->template?->starts_at?->format('d.m.Y') }} - 
                        {{ $currentCycle->template?->ends_at?->format('d.m.Y') }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ ucfirst($currentCycle->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($objectives && $objectives->count() > 0)
            <div class="space-y-6">
                @foreach($objectives as $objective)
                    <div class="bg-white rounded-lg border p-4">
                        <div class="d-flex items-center justify-between mb-2">
                            <div class="d-flex items-center gap-2">
                                <h3 class="text-base font-semibold text-gray-900 m-0">{{ $objective->title }}</h3>
                                <x-ui-badge variant="secondary" size="xs">{{ $objective->keyResults->count() }} KR</x-ui-badge>
                            </div>
                        </div>
                        @if($objective->description)
                            <p class="text-xs text-muted mb-3">{{ Str::limit($objective->description, 120) }}</p>
                        @endif

                        @if($objective->keyResults && $objective->keyResults->count() > 0)
                            <div class="space-y-2">
                                @foreach($objective->keyResults as $keyResult)
                                    <div class="d-flex items-center justify-between p-2 bg-muted-5 rounded border">
                                        <div class="text-sm font-medium">{{ $keyResult->title }}</div>
                                        <div class="d-flex items-center gap-2">
                                            @php
                                                $type = $keyResult->performance?->type;
                                                $typeVariant = $type === 'boolean' ? 'purple' : ($type === 'percentage' ? 'info' : 'success');
                                            @endphp
                                            <x-ui-badge :variant="$type ? $typeVariant : 'secondary'" size="xs">
                                                {{ $type ? ucfirst($type) : 'Ohne Typ' }}
                                            </x-ui-badge>
                                            <x-ui-badge variant="secondary" size="xs">
                                                Ziel: {{ $keyResult->performance?->target_value ?? '–' }}@if($type === 'percentage') % @endif
                                            </x-ui-badge>
                                            <x-ui-badge :variant="$type === 'boolean' ? ($keyResult->performance?->is_completed ? 'success' : 'danger') : 'secondary'" size="xs">
                                                @if($type === 'boolean')
                                                    {{ $keyResult->performance?->is_completed ? 'Erledigt' : 'Offen' }}
                                                @else
                                                    Aktuell: {{ $keyResult->performance?->current_value ?? '–' }}@if($type === 'percentage') % @endif
                                                @endif
                                            </x-ui-badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                @svg('heroicon-o-calendar')
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Kein aktiver Zyklus</h3>
            <p class="mt-1 text-sm text-gray-500">Es ist aktuell kein OKR-Zyklus aktiv.</p>
            @if($availableTemplates && $availableTemplates->count() > 0)
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Verfügbare Zyklen:</h4>
                    <div class="space-y-2">
                        @foreach($availableTemplates as $template)
                            <div class="text-sm text-gray-600">
                                {{ $template->label }} ({{ $template->starts_at->format('d.m.Y') }} - {{ $template->ends_at->format('d.m.Y') }})
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>