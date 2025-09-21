<div class="h-full overflow-y-auto p-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">OKR Management</h1>
                <p class="mt-1 text-sm text-gray-500">Verwalte deine OKRs</p>
            </div>
            <button 
                wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                @svg('heroicon-o-plus')
                Neues OKR
            </button>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ session('message') }}</p>
        </div>
    @endif

    @if($okrs && $okrs->count() > 0)
        <div class="space-y-4">
            @foreach($okrs as $okr)
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $okr->title }}
                                </h3>
                                @if($okr->is_template)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Template
                                    </span>
                                @endif
                            </div>
                            
                            @if($okr->description)
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ Str::limit($okr->description, 100) }}
                                </p>
                            @endif
                            
                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                <div class="flex items-center space-x-1">
                                    @svg('heroicon-o-user', 'w-4 h-4')
                                    <span>{{ $okr->user?->name ?? 'Unbekannt' }}</span>
                                </div>
                                
                                @if($okr->manager)
                                    <div class="flex items-center space-x-1">
                                        @svg('heroicon-o-user-group', 'w-4 h-4')
                                        <span>Manager: {{ $okr->manager->name }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex items-center space-x-1">
                                    @svg('heroicon-o-calendar', 'w-4 h-4')
                                    <span>{{ $okr->cycles->count() }} Cycles</span>
                                </div>
                                
                                @if($okr->performance_score !== null)
                                    <div class="flex items-center space-x-1">
                                        @svg('heroicon-o-chart-bar', 'w-4 h-4')
                                        <span>{{ $okr->performance_score }}% Score</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            @if($okr->auto_transfer)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Auto-Transfer
                                </span>
                            @endif
                            
                            <div class="flex space-x-2">
                                <button 
                                    wire:click="show({{ $okr->id }})"
                                    class="text-sm text-blue-600 hover:text-blue-800"
                                >
                                    Anzeigen
                                </button>
                                
                                <button 
                                    wire:click="deleteOkr({{ $okr->id }})"
                                    onclick="return confirm('OKR wirklich löschen?')"
                                    class="text-sm text-red-600 hover:text-red-800"
                                >
                                    Löschen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            {{ $okrs->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                @svg('heroicon-o-flag')
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Keine OKRs</h3>
            <p class="mt-1 text-sm text-gray-500">Erstelle dein erstes OKR.</p>
        </div>
    @endif

    <!-- Create OKR Modal -->
    @if($modalShow)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Neues OKR erstellen</h3>
                    
                    <form wire:submit.prevent="createOkr">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titel *</label>
                            <input 
                                type="text" 
                                wire:model="title"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="OKR Titel eingeben"
                                required
                            >
                            @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                            <textarea 
                                wire:model="description"
                                rows="3"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="OKR Beschreibung (optional)"
                            ></textarea>
                            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Performance Score (%)</label>
                            <input 
                                type="number" 
                                wire:model="performance_score"
                                min="0" 
                                max="100"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="0-100"
                            >
                            @error('performance_score') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Manager</label>
                            <select wire:model="manager_user_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Kein Manager</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('manager_user_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4 space-y-2">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="auto_transfer"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700">Auto-Transfer</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="is_template"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="ml-2 text-sm text-gray-700">Als Template</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button 
                                type="button"
                                wire:click="closeCreateModal"
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
