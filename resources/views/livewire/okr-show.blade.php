{{-- STEP 3: NUR NAVBAR TESTEN --}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <div class="flex items-center gap-2">
                <x-ui-button 
                    variant="secondary-ghost" 
                    size="sm"
                    :href="route('okr.okrs.index')" 
                    wire:navigate
                >
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span class="ml-1">OKRs</span>
                </x-ui-button>
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="content">
        <div class="p-4">
            <h1 class="text-2xl font-bold text-blue-600">NAVBAR FUNKTIONIERT!</h1>
            <p>Wenn du das siehst, funktioniert die Navbar!</p>
        </div>
    </x-slot>
</x-ui-page>