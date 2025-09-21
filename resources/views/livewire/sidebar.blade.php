{{-- OKR Sidebar --}}
<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="OKR" />
    
    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="p-3 text-sm italic text-secondary uppercase">Allgemein</h4>

        {{-- Dashboard --}}
        <a href="{{ route('okr.dashboard') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '/' || 
               window.location.pathname.endsWith('/okr') || 
               window.location.pathname.endsWith('/okr/') ||
               (window.location.pathname.split('/').length === 1 && window.location.pathname === '/')
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-chart-bar class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Dashboard</span>
        </a>

        {{-- OKR anlegen --}}
        <a href="{{ route('okr.okrs.create') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="collapsed ? 'justify-center' : 'gap-3'"
           wire:navigate>
            <x-heroicon-o-plus class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">OKR anlegen</span>
        </a>
    </div>

    {{-- Abschnitt: OKRs --}}
    @if($okrs->count() > 0)
        <div x-show="!collapsed">
            <h4 class="p-3 text-sm italic text-secondary uppercase">OKRs</h4>

            @foreach($okrs as $okr)
                <a href="{{ route('okr.okrs.show', ['okr' => $okr]) }}"
                   class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition gap-3"
                   :class="[
                       window.location.pathname.includes('/okrs/{{ $okr->id }}/') || 
                       window.location.pathname.includes('/okrs/{{ $okr->uuid }}/') ||
                       window.location.pathname.endsWith('/okrs/{{ $okr->id }}') ||
                       window.location.pathname.endsWith('/okrs/{{ $okr->uuid }}') ||
                       (window.location.pathname.split('/').length === 2 && window.location.pathname.endsWith('/{{ $okr->id }}')) ||
                       (window.location.pathname.split('/').length === 2 && window.location.pathname.endsWith('/{{ $okr->uuid }}'))
                           ? 'bg-primary text-on-primary shadow-md'
                           : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md'
                   ]"
                   wire:navigate>
                    <x-heroicon-o-flag class="w-6 h-6 flex-shrink-0"/>
                    <span class="truncate">{{ $okr->title }}</span>
                </a>
            @endforeach
        </div>
    @endif

</div>
