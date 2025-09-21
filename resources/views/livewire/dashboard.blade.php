<div class="h-full overflow-y-auto p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">OKR Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Übersicht über deine Objectives und Key Results</p>
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
                    <div class="bg-white shadow rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $objective->title }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ number_format($objective->performance_score * 100, 1) }}%
                            </span>
                        </div>
                        
                        @if($objective->description)
                            <p class="text-gray-600 mb-4">{{ $objective->description }}</p>
                        @endif

                        @if($objective->keyResults && $objective->keyResults->count() > 0)
                            <div class="space-y-3">
                                <h4 class="font-medium text-gray-900">Key Results</h4>
                                @foreach($objective->keyResults as $keyResult)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $keyResult->title }}</p>
                                            @if($keyResult->description)
                                                <p class="text-sm text-gray-600">{{ $keyResult->description }}</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ number_format($keyResult->performance_score * 100, 1) }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    @svg('heroicon-o-target')
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Objectives</h3>
                <p class="mt-1 text-sm text-gray-500">Erstelle deine ersten Objectives für diesen Zyklus.</p>
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