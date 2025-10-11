<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$okr->title" icon="heroicon-o-flag">
            <x-slot name="titleActions">
                <x-ui-button 
                    variant="secondary-ghost" 
                    size="sm"
                    :href="route('okr.dashboard')" 
                    wire:navigate
                >
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span class="ml-1">Dashboard</span>
                </x-ui-button>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4">{{ $okr->title }}</h1>
            <p class="text-[var(--ui-muted)]">OKR Details</p>
        </div>
    </x-ui-page-container>
</x-ui-page>