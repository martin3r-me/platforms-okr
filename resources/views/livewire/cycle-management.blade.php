<div class="h-full overflow-y-auto p-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cycle Management</h1>
                <p class="mt-1 text-sm text-gray-500">Verwalte deine OKR-Zyklen</p>
            </div>
            <button 
                wire:click="$set('showCreateModal', true)"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                @svg('heroicon-o-plus')
                Neuer Cycle
            </button>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ session('message') }}</p>
        </div>
    @endif

    @if($cycles && $cycles->count() > 0)
        <div class="space-y-4">
            @foreach($cycles as $cycle)
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $cycle->okr?->title ?? 'Unbekanntes OKR' }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ $cycle->template?->label ?? 'Kein Template' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $cycle->template?->starts_at?->format('d.m.Y') }} - 
                                {{ $cycle->template?->ends_at?->format('d.m.Y') }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($cycle->status === 'current') bg-green-100 text-green-800
                                @elseif($cycle->status === 'draft') bg-gray-100 text-gray-800
                                @elseif($cycle->status === 'ending_soon') bg-yellow-100 text-yellow-800
                                @elseif($cycle->status === 'past') bg-red-100 text-red-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ ucfirst($cycle->status) }}
                            </span>
                            
                            <div class="flex space-x-2">
                                @if($cycle->status === 'draft')
                                    <button 
                                        wire:click="updateCycleStatus({{ $cycle->id }}, 'current')"
                                        class="text-sm text-green-600 hover:text-green-800"
                                    >
                                        Aktivieren
                                    </button>
                                @elseif($cycle->status === 'current')
                                    <button 
                                        wire:click="updateCycleStatus({{ $cycle->id }}, 'ending_soon')"
                                        class="text-sm text-yellow-600 hover:text-yellow-800"
                                    >
                                        Beenden
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                @svg('heroicon-o-calendar')
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Cycles</h3>
            <p class="mt-1 text-sm text-gray-500">Erstelle deinen ersten OKR-Cycle.</p>
        </div>
    @endif

    <!-- Create Cycle Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Neuen Cycle erstellen</h3>
                    
                    <form wire:submit.prevent="createCycle">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">OKR auswählen</label>
                            <select wire:model="selectedOkr" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">OKR auswählen...</option>
                                @foreach($okrs as $okr)
                                    <option value="{{ $okr->id }}">{{ $okr->title }}</option>
                                @endforeach
                            </select>
                            @error('selectedOkr') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cycle Template auswählen</label>
                            <select wire:model="selectedTemplate" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Template auswählen...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">
                                        {{ $template->label }} ({{ $template->starts_at->format('d.m.Y') }} - {{ $template->ends_at->format('d.m.Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedTemplate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button 
                                type="button"
                                wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Abbrechen
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Erstellen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
