{{-- Eine KR-Zeile: Titel, Objective/Zyklus, Fortschritt --}}
<span class="w-4 h-4 mt-0.5 shrink-0 flex items-center justify-center {{ $kr['done'] ? 'text-[var(--t-success,#16a34a)]' : 'text-[var(--t-text-muted)]' }}">
    @svg($kr['done'] ? 'heroicon-s-check-circle' : 'heroicon-o-flag', 'w-3.5 h-3.5')
</span>
<span class="flex-1 min-w-0">
    @if(($kr['url'] ?? null) && empty($noLink))
        <a href="{{ $kr['url'] }}" wire:navigate class="block truncate font-medium hover:underline">{{ $kr['title'] }}</a>
    @else
        <span class="block truncate font-medium">{{ $kr['title'] }}</span>
    @endif
    <span class="block truncate text-[10px] text-[var(--t-text-muted)]">
        {{ collect([$kr['objective'], $kr['cycle'], isset($via) ? 'über '.$via : null])->filter()->implode(' · ') }}
    </span>
</span>
@if($kr['progress'] !== null)
    <span class="shrink-0 mt-0.5 text-[10px] tabular-nums text-[var(--t-text-muted)]">{{ $kr['progress'] }} %</span>
@endif
