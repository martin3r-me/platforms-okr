{{-- STEP 5: NUR CONTENT OHNE SLOT --}}
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

    {{-- CONTENT OHNE SLOT --}}
    <div class="p-4">
        <div style="background: red; color: white; padding: 20px; font-size: 24px; margin-bottom: 20px;">
            <h1>LIVEWIRE FUNKTIONIERT!</h1>
            <p>OKR ID: {{ $okr->id ?? 'KEINE ID' }}</p>
            <p>OKR Title: {{ $okr->title ?? 'KEIN TITLE' }}</p>
            <p>OKR Description: {{ $okr->description ?? 'KEINE DESCRIPTION' }}</p>
        </div>
        
        <h1 class="text-2xl font-bold text-green-600">CONTENT OHNE SLOT FUNKTIONIERT!</h1>
        <p>Wenn du das siehst, funktioniert der Content-Bereich!</p>
    </div>
</x-ui-page>