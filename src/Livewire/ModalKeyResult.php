<?php

namespace Platform\Okr\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;
use Platform\Okr\Services\KeyResultContextResolver;
use Platform\Okr\Services\StoreKeyResultContext;

class ModalKeyResult extends Component
{
    public bool $open = false;

    public ?string $contextType = null;
    public ?int $contextId = null;

    public $availableKeyResults = [];
    public $linkedKeyResults = [];
    public ?int $selectedKeyResultId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->availableKeyResults = collect();
        $this->linkedKeyResults = collect();
    }

    #[On('keyresult')]
    public function setContext(array $payload = []): void
    {
        $this->contextType = $payload['context_type'] ?? null;
        $this->contextId = isset($payload['context_id']) ? (int) $payload['context_id'] : null;

        // Wenn Modal bereits offen ist, Daten neu laden
        if ($this->open && $this->contextType && $this->contextId) {
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
        }
    }

    #[On('keyresult:open')]
    public function open(): void
    {
        if (! Auth::check() || ! Auth::user()->currentTeamRelation) {
            return;
        }

        $this->search = '';
        $this->selectedKeyResultId = null;

        if ($this->contextType && $this->contextId) {
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
        }

        $this->open = true;
    }

    public function close(): void
    {
        $this->resetValidation();
        // Kontext NICHT zurücksetzen, damit er beim erneuten Öffnen erhalten bleibt
        $this->reset('open', 'search', 'selectedKeyResultId');
        $this->availableKeyResults = collect();
        $this->linkedKeyResults = collect();
    }

    public function updatedSearch(): void
    {
        $this->loadKeyResults();
    }

    protected function loadKeyResults(): void
    {
        if (! Auth::check() || ! Auth::user()->currentTeamRelation) {
            $this->availableKeyResults = collect();
            return;
        }

        // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        
        // Wenn OKR ein Parent Tool ist, verwende das Root-Team
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;
        
        $query = KeyResult::with(['objective.cycle.okr', 'performance', 'user'])
            ->where('team_id', $teamId)
            ->orderBy('created_at', 'desc');

        // Suche
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('objective', function ($obj) {
                      $obj->where('title', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $this->availableKeyResults = $query->get();
    }

    protected function loadLinkedKeyResults(): void
    {
        if (! $this->contextType || ! $this->contextId) {
            $this->linkedKeyResults = collect();
            return;
        }

        // Lade alle KeyResults, die bereits mit diesem Kontext verknüpft sind
        $linkedContexts = KeyResultContext::where('context_type', $this->contextType)
            ->where('context_id', $this->contextId)
            ->where('is_primary', true)
            ->with(['keyResult.objective.cycle.okr', 'keyResult.performance', 'keyResult.user'])
            ->get();

        $this->linkedKeyResults = $linkedContexts->map(function ($context) {
            return $context->keyResult;
        });
    }

    public function attachKeyResult(int $keyResultId): void
    {
        if (! $this->contextType || ! $this->contextId) {
            session()->flash('error', 'Kein Kontext gesetzt.');
            return;
        }

        try {
            $storeService = app(StoreKeyResultContext::class);
            $storeService->store($keyResultId, $this->contextType, $this->contextId);

            session()->flash('message', 'KeyResult erfolgreich verknüpft!');
            
            // Daten neu laden
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Verknüpfen: ' . $e->getMessage());
        }
    }

    public function detachKeyResult(int $keyResultId): void
    {
        if (! $this->contextType || ! $this->contextId) {
            session()->flash('error', 'Kein Kontext gesetzt.');
            return;
        }

        try {
            $storeService = app(StoreKeyResultContext::class);
            $storeService->detach($keyResultId, $this->contextType, $this->contextId);

            session()->flash('message', 'Verknüpfung erfolgreich entfernt!');
            
            // Daten neu laden
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Entfernen: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('okr::livewire.modal-key-result');
    }
}

