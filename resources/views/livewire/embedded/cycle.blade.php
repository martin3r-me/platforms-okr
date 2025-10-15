<x-ui-page>
  <x-slot name="navbar">
    <x-ui-page-navbar :title="$cycle->template->label ?? 'Zyklus'" icon="heroicon-o-calendar"/>
  </x-slot>

  <x-ui-page-container spacing="space-y-8">
    {{-- Header-Karte --}}
    <div class="bg-gradient-to-r from-[var(--ui-muted-5)] to-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60 p-8">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
              @svg('heroicon-o-calendar','w-6 h-6')
            </div>
            <div>
              <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight">{{ $cycle->template->label ?? 'Zyklus' }}</h1>
              <div class="flex items-center gap-4 text-sm text-[var(--ui-muted)] mt-1">
                <span>Status: <span class="font-medium">{{ ucfirst($cycle->status) }}</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Objectives/Key Results --}}
    <div class="bg-white rounded-lg border border-[var(--ui-border)]/60 p-8">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded-lg flex items-center justify-center">
          @svg('heroicon-o-flag','w-4 h-4')
        </div>
        <div>
          <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Objectives</h3>
          <p class="text-sm text-[var(--ui-muted)]">Ziele und Key Results</p>
        </div>
      </div>

      @if($cycle->objectives->count() > 0)
        <div class="space-y-4">
          @foreach($cycle->objectives as $objective)
            <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4">
              <div class="font-medium text-[var(--ui-secondary)]">{{ $objective->title }}</div>
              @if($objective->description)
                <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $objective->description }}</p>
              @endif
              <div class="mt-3 space-y-2">
                @forelse($objective->keyResults as $kr)
                  <div class="flex items-center justify-between p-2 bg-white rounded border border-[var(--ui-border)]/60">
                    <div class="text-sm text-[var(--ui-secondary)] truncate">{{ $kr->title }}</div>
                    <div class="text-xs text-[var(--ui-muted)]">{{ ($kr->performance->is_completed ?? false) ? 'Erreicht' : 'Offen' }}</div>
                  </div>
                @empty
                  <div class="text-sm text-[var(--ui-muted)]">Keine Key Results vorhanden.</div>
                @endforelse
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-12">
          <div class="w-16 h-16 bg-[var(--ui-muted-5)] rounded-full flex items-center justify-center mx-auto mb-4">
            @svg('heroicon-o-flag', 'w-8 h-8 text-[var(--ui-muted)]')
          </div>
          <h3 class="text-lg font-medium text-[var(--ui-secondary)] mb-2">Keine Objectives vorhanden</h3>
          <p class="text-[var(--ui-muted)] mb-6">Dieser Zyklus hat noch keine Objectives.</p>
        </div>
      @endif
    </div>
  </x-ui-page-container>
</x-ui-page>



