{{-- OKR Sidebar --}}
<div>
    {{-- Debug Info --}}
    <div class="p-2 bg-red-100 text-red-800 text-xs">
        OKR Sidebar geladen! OKRs: {{ $okrs->count() }}
    </div>
    
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="OKR" />
    
    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-muted)] uppercase">Allgemein</h4>

        {{-- Dashboard --}}
        <x-ui-sidebar-item 
            :href="route('okr.dashboard')"
            icon="heroicon-o-chart-bar"
            label="Dashboard"
            :active="request()->routeIs('okr.dashboard')"
        />

        {{-- OKRs --}}
        <x-ui-sidebar-item 
            :href="route('okr.okrs.index')"
            icon="heroicon-o-flag"
            label="OKRs"
            :active="request()->routeIs('okr.okrs.*')"
        />

        {{-- OKR anlegen --}}
        <x-ui-sidebar-item 
            :href="route('okr.okrs.index')"
            icon="heroicon-o-plus"
            label="OKR anlegen"
            :active="false"
        />
    </div>

    {{-- Abschnitt: OKRs --}}
    @if($okrs->count() > 0)
        <div x-show="!collapsed">
            <h4 class="p-3 text-sm italic text-[var(--ui-muted)] uppercase">OKRs</h4>

            @foreach($okrs as $okr)
                <x-ui-sidebar-item 
                    :href="route('okr.okrs.show', ['okr' => $okr])"
                    icon="heroicon-o-flag"
                    :label="$okr->title"
                    :active="request()->routeIs('okr.okrs.show', ['okr' => $okr->id]) || request()->routeIs('okr.okrs.show', ['okr' => $okr->uuid])"
                />
            @endforeach
        </div>
    @endif

</div>
