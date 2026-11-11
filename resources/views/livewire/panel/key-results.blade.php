{{-- OKR-Reiter im Kontext-Panel: Datensatz mit Key Results verknüpfen / lösen --}}
<div class="flex flex-col h-full text-[var(--t-text)]">
    <div class="px-3 py-2 border-b border-white/10 text-[11px] text-[var(--t-text-muted)] truncate">
        Zahlt ein auf:
        <span class="text-[var(--t-text)] font-medium">{{ $contextSubject ?? ('#'.$contextId) }}</span>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-3 space-y-4">

        {{-- Direkt verknüpft --}}
        <section class="space-y-1">
            <h4 class="text-[10px] uppercase tracking-wide text-[var(--t-text-muted)]">Verknüpft</h4>
            @forelse($this->linked as $kr)
                <div wire:key="okr-linked-{{ $kr['id'] }}" class="group flex items-start gap-2 px-2 py-1.5 rounded text-[12px] bg-[var(--t-accent)]/10">
                    @include('okr::livewire.panel.partials.key-result-line', ['kr' => $kr])
                    <button type="button" wire:click="unlink({{ $kr['id'] }})" wire:loading.attr="disabled"
                            class="shrink-0 p-0.5 rounded text-[var(--t-text-muted)] hover:text-[var(--t-text)] transition" title="Verknüpfung lösen">
                        @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                    </button>
                </div>
            @empty
                <p class="text-[11px] text-[var(--t-text-muted)]">Noch kein Key Result verknüpft.</p>
            @endforelse
        </section>

        {{-- Über das Projekt abgedeckt --}}
        @if(count($this->covered))
            <section class="space-y-1">
                <h4 class="text-[10px] uppercase tracking-wide text-[var(--t-text-muted)]">Über übergeordneten Kontext</h4>
                @foreach($this->covered as $kr)
                    <div wire:key="okr-covered-{{ $kr['id'] }}" class="flex items-start gap-2 px-2 py-1.5 rounded text-[12px] bg-white/5"
                         title="Abgedeckt über {{ $kr['via'] }}">
                        @include('okr::livewire.panel.partials.key-result-line', ['kr' => $kr, 'via' => $kr['via']])
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Hinzufügen --}}
        <section class="space-y-1.5">
            <h4 class="text-[10px] uppercase tracking-wide text-[var(--t-text-muted)]">Key Result hinzufügen</h4>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Key Result oder Objective suchen …"
                   class="w-full px-2 py-1.5 text-[12px] rounded border border-[var(--t-border)]/60 bg-transparent text-[var(--t-text)] placeholder:text-[var(--t-text-muted)]/50 focus:outline-none focus:ring-1 focus:ring-[var(--t-accent)]">
            @forelse($this->candidates as $kr)
                <button type="button" wire:key="okr-cand-{{ $kr['id'] }}" wire:click="link({{ $kr['id'] }})" wire:loading.attr="disabled"
                        class="w-full flex items-start gap-2 px-2 py-1.5 rounded text-[12px] text-left bg-white/5 hover:bg-white/10 transition" title="Verknüpfen">
                    @include('okr::livewire.panel.partials.key-result-line', ['kr' => $kr, 'noLink' => true])
                    <span class="shrink-0 p-0.5 text-[var(--t-text-muted)]">@svg('heroicon-o-plus', 'w-3.5 h-3.5')</span>
                </button>
            @empty
                <p class="text-[11px] text-[var(--t-text-muted)]">
                    {{ $search !== '' ? 'Nichts gefunden.' : 'Keine Key Results in laufenden Zyklen — über die Suche findest du alle.' }}
                </p>
            @endforelse
        </section>
    </div>
</div>
