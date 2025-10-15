@extends('platform::layouts.embedded')

@section('content')
<div class="min-h-screen w-full bg-white">
  <div class="max-w-4xl mx-auto p-6 space-y-6">
    <h1 class="text-xl font-semibold text-[var(--ui-secondary)]">Teams – OKR Tab konfigurieren</h1>
    <p class="text-sm text-[var(--ui-muted)]">Wähle ein Team und anschließend den Zyklus.</p>

    <!-- Teams Auswahl -->
    <div class="bg-white rounded-lg border p-4">
      <div class="mb-2">
        <div class="text-sm text-[var(--ui-secondary)]">Team auswählen</div>
        <div class="text-xs text-[var(--ui-muted)]">Nur Teams, denen du angehörst</div>
      </div>
      <div id="teamGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        @forelse(($teams ?? collect()) as $team)
          <button type="button" class="team-tile flex items-center justify-center p-3 rounded-lg border border-[var(--ui-border)] bg-white hover:border-[var(--ui-primary)] text-sm"
                  data-team-id="{{ $team->id }}" data-team-name="{{ $team->name }}">
            <span class="truncate">{{ $team->name }}</span>
          </button>
        @empty
          <div class="text-xs text-[var(--ui-muted)]">Keine Teams gefunden.</div>
        @endforelse
      </div>
    </div>

    <!-- Zyklen -->
    <div class="bg-white rounded-lg border p-4">
      <div class="flex items-center justify-between mb-2">
        <div>
          <div class="text-sm text-[var(--ui-secondary)]">Zyklus auswählen</div>
          <div class="text-xs text-[var(--ui-muted)]">Nur Zyklen aus dem gewählten Team</div>
        </div>
      </div>
      <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Zyklus</label>
      <select id="cycleSelect" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-[var(--ui-primary)]">
        <option value="">– Bitte erst ein Team wählen –</option>
      </select>
      <div class="text-xs text-[var(--ui-muted)] mt-2"><span id="cycleCount">0</span> Zyklen verfügbar</div>
    </div>

    <div class="flex items-center justify-end">
      <x-ui-button id="okrSave" variant="primary" size="sm" disabled>Als Tab hinzufügen</x-ui-button>
    </div>
  </div>
</div>

<script>
(function(){
  const teamTiles = document.querySelectorAll('.team-tile');
  const cycleCountEl = document.getElementById('cycleCount');
  const saveBtn = document.getElementById('okrSave');
  const cycleSelect = document.getElementById('cycleSelect');

  const allCycles = @json($cycles ?? []);
  let selectedTeamId = null;

  function renderCycles(){
    let filtered = allCycles.filter(c => String(c.team_id) === String(selectedTeamId));
    cycleCountEl.textContent = String(filtered.length);
    // rebuild select options
    if (cycleSelect) {
      cycleSelect.innerHTML = '';
      if (!selectedTeamId) {
        cycleSelect.innerHTML = '<option value="">– Bitte erst ein Team wählen –</option>';
      } else if (filtered.length === 0) {
        cycleSelect.innerHTML = '<option value="">Keine Zyklen im Team</option>';
      } else {
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = '– Bitte wählen –';
        cycleSelect.appendChild(ph);
        filtered.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.template_label ? c.template_label : ('Zyklus #' + c.id);
          cycleSelect.appendChild(opt);
        });
      }
    }
    saveBtn.disabled = true;
    if (window.microsoftTeams?.pages?.config) window.microsoftTeams.pages.config.setValidityState(false);
  }

  teamTiles.forEach(tile => {
    tile.addEventListener('click', function(){
      teamTiles.forEach(t => t.classList.remove('ring-2','ring-[var(--ui-primary)]'));
      this.classList.add('ring-2','ring-[var(--ui-primary)]');
      selectedTeamId = this.getAttribute('data-team-id');
      renderCycles();
    });
  });

  cycleSelect?.addEventListener('change', function(){
    const ok = !!(cycleSelect.value && cycleSelect.value !== '');
    saveBtn.disabled = !ok;
    if (window.microsoftTeams?.pages?.config) window.microsoftTeams.pages.config.setValidityState(ok);
  });

  (async function setupTeams(){
    try {
      if (window.microsoftTeams?.app?.initialize) { try { await window.microsoftTeams.app.initialize(); } catch(_) {} }
      if (!window.microsoftTeams?.pages?.config || (window.microsoftTeams.pages.config.isSupported && !window.microsoftTeams.pages.config.isSupported())) return;
      window.microsoftTeams.pages.config.setValidityState(false);
      window.microsoftTeams.pages.config.registerOnSaveHandler(async function (saveEvent) {
        try {
          const cycleId = cycleSelect?.value || '';
          if (!cycleId) { saveEvent.notifyFailure('Bitte Zyklus wählen'); return; }
          // Tab-Anzeigename aus dem ausgewählten Optionstext ableiten (Template-Name)
          let templateName = 'Zyklus ' + cycleId;
          try {
            const sel = cycleSelect.options[cycleSelect.selectedIndex];
            if (sel && sel.text) templateName = sel.text;
          } catch(_) {}
          const contentUrl = 'https://office.martin3r.me/okr/embedded/okr/cycles/' + encodeURIComponent(cycleId);
          await window.microsoftTeams.pages.config.setConfig({
            contentUrl: contentUrl,
            websiteUrl: contentUrl,
            entityId: 'okr-cycle-' + cycleId,
            // suggestedDisplayName wird beim Neu-Anlegen verwendet
            suggestedDisplayName: 'OKR – ' + templateName,
            // displayName wird bei Re-Konfiguration (bestehender Tab) respektiert
            displayName: 'OKR – ' + templateName
          });
          saveEvent.notifySuccess();
        } catch(e) {
          try { saveEvent.notifyFailure('Speichern fehlgeschlagen'); } catch(_) {}
        }
      });
    } catch(_) {}
  })();
})();
</script>
@endsection
