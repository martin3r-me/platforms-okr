<x-ui-page>
  <x-slot name="navbar">
    <x-ui-page-navbar :title="$cycle->template->label ?? 'Zyklus'" icon="heroicon-o-calendar"/>
  </x-slot>

  <x-ui-page-container spacing="space-y-8">
    {{-- Header-Karte --}}
    <div class="bg-gradient-to-r from-[var(--nx-bg)] to-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-8">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
              @svg('heroicon-o-calendar','w-6 h-6')
            </div>
            <div>
              <h1 class="text-3xl font-bold text-[var(--nx-text)] tracking-tight">{{ $cycle->template->label ?? 'Zyklus' }}</h1>
              <div class="flex items-center gap-4 text-sm text-[var(--nx-muted)] mt-1">
                <span>Status: <span class="font-medium">{{ ucfirst($cycle->status) }}</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Objectives/Key Results --}}
    <div class="bg-[color:var(--nx-surface)] rounded-lg border border-[color:var(--nx-line)] p-8">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-8 h-8 bg-[var(--nx-accent)] text-[var(--nx-on-accent)] rounded-lg flex items-center justify-center">
          @svg('heroicon-o-flag','w-4 h-4')
        </div>
        <div>
          <h3 class="text-xl font-semibold text-[var(--nx-text)]">Objectives</h3>
          <p class="text-sm text-[var(--nx-muted)]">Ziele und Erfolgskriterien</p>
        </div>
      </div>

      @if($cycle->objectives->count() > 0)
        <div class="space-y-4">
          @foreach($cycle->objectives as $objective)
            <div class="bg-[var(--nx-bg)] rounded-lg border border-[color:var(--nx-line)] p-4">
              <div class="font-medium text-[var(--nx-text)]">{{ $objective->title }}</div>
              @if($objective->description)
                <p class="text-sm text-[var(--nx-muted)] mt-1">{{ $objective->description }}</p>
              @endif
              <div class="mt-3 space-y-2">
                @forelse($objective->keyResults as $kr)
                  <div class="flex items-center justify-between p-2 bg-[color:var(--nx-surface)] rounded border border-[color:var(--nx-line)]">
                    <div class="text-sm text-[var(--nx-text)] truncate">{{ $kr->title }}</div>
                    <div class="text-xs text-[var(--nx-muted)]">{{ ($kr->performance->is_completed ?? false) ? 'Erreicht' : 'Offen' }}</div>
                  </div>
                @empty
                  <div class="text-sm text-[var(--nx-muted)]">Keine Erfolgskriterien vorhanden.</div>
                @endforelse
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-12">
          <div class="w-16 h-16 bg-[var(--nx-bg)] rounded-full flex items-center justify-center mx-auto mb-4">
            @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--nx-muted)]')
          </div>
          <h3 class="text-lg font-medium text-[var(--nx-text)] mb-2">Keine Objectives vorhanden</h3>
          <p class="text-[var(--nx-muted)] mb-6">Dieser Zyklus hat noch keine Objectives.</p>
        </div>
      @endif
    </div>
  </x-ui-page-container>
</x-ui-page>



