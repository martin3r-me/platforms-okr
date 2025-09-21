<div class="space-y-1">
    <x-ui-sidebar-item 
        href="{{ route('okr.dashboard') }}" 
        :active="request()->routeIs('okr.dashboard')"
        icon="heroicon-o-chart-bar"
    >
        Dashboard
    </x-ui-sidebar-item>
    
    <x-ui-sidebar-item 
        href="{{ route('okr.cycles.index') }}" 
        :active="request()->routeIs('okr.cycles.*')"
        icon="heroicon-o-calendar-days"
    >
        Cycles
    </x-ui-sidebar-item>
    
    <x-ui-sidebar-item 
        href="#" 
        :active="false"
        icon="heroicon-o-target"
    >
        Objectives
    </x-ui-sidebar-item>
    
    <x-ui-sidebar-item 
        href="#" 
        :active="false"
        icon="heroicon-o-chart-pie"
    >
        Key Results
    </x-ui-sidebar-item>
</div>