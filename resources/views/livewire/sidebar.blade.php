{{-- OKR Sidebar --}}
<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        OKR
    </div>
    
    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('okr.dashboard')">
            @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('okr.okrs.index')">
            @svg('heroicon-o-flag', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">OKRs</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('okr.okrs.index')">
            @svg('heroicon-o-plus', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">OKR anlegen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('okr.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-chart-bar', 'w-5 h-5')
            </a>
            <a href="{{ route('okr.okrs.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-flag', 'w-5 h-5')
            </a>
            <a href="{{ route('okr.okrs.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-plus', 'w-5 h-5')
            </a>
        </div>
    </div>

    {{-- Abschnitt: OKRs --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            @if($okrs->count() > 0)
                <x-ui-sidebar-list label="OKRs">
                    @foreach($okrs as $okr)
                        <x-ui-sidebar-item :href="route('okr.okrs.show', ['okr' => $okr])">
                            @svg('heroicon-o-flag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <span class="truncate text-sm ml-2">{{ $okr->title }}</span>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @else
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">Keine OKRs</div>
            @endif
        </div>
    </div>
</div>
