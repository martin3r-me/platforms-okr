<div class="min-h-screen bg-white">
  <div class="max-w-3xl mx-auto p-6 space-y-6">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-[var(--ui-primary)] text-[var(--ui-on-primary)] flex items-center justify-center">
        @svg('heroicon-o-calendar','w-4 h-4')
      </div>
      <div>
        <h1 class="text-xl font-semibold text-[var(--ui-secondary)]">OKR Zyklus</h1>
        <div class="text-xs text-[var(--ui-muted)]">{{ $cycle->template->label ?? 'Zyklus' }}</div>
      </div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <div class="text-sm text-[var(--ui-secondary)] font-medium mb-2">Übersicht</div>
      <div class="text-sm text-[var(--ui-muted)]">{{ $cycle->notes ?: 'Kein Beschreibungstext' }}</div>
    </div>

    <div class="bg-white rounded-lg border p-4">
      <div class="text-sm text-[var(--ui-secondary)] font-medium mb-3">Objectives</div>
      @forelse($cycle->objectives as $objective)
        <div class="border border-[var(--ui-border)]/60 rounded p-3 mb-3">
          <div class="font-medium text-[var(--ui-secondary)]">{{ $objective->title }}</div>
          <div class="text-xs text-[var(--ui-muted)] mb-2">{{ $objective->description }}</div>
          <div class="space-y-1">
            @foreach($objective->keyResults as $kr)
              <div class="flex items-center justify-between text-sm">
                <div class="text-[var(--ui-secondary)] truncate">{{ $kr->title }}</div>
                <div class="text-[var(--ui-muted)]">{{ $kr->performance->is_completed ?? false ? 'Erreicht' : 'Offen' }}</div>
              </div>
            @endforeach
          </div>
        </div>
      @empty
        <div class="text-sm text-[var(--ui-muted)]">Keine Objectives vorhanden.</div>
      @endforelse
    </div>
  </div>
</div>



