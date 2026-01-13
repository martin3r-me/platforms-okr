<?php

namespace Platform\Okr\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;
use Platform\Okr\Services\KeyResultContextResolver;
use Platform\Okr\Services\StoreKeyResultContext;

class ModalKeyResult extends Component
{
    public bool $open = false;

    public ?string $contextType = null;
    public ?int $contextId = null;

    /**
     * Optionaler Context-Stack, damit temporäre Kontexte (z.B. aus Counter-Modal)
     * den bestehenden Kontext (z.B. Task) nicht dauerhaft überschreiben.
     *
     * Jeder Eintrag: ['type' => string|null, 'id' => int|null]
     */
    public array $contextStack = [];

    public $availableKeyResults = [];
    public $linkedKeyResults = [];
    public $coveredKeyResults = []; // KeyResults, die über Parent-Kontext (z.B. Project) abgedeckt sind
    public ?int $selectedKeyResultId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->availableKeyResults = collect();
        $this->linkedKeyResults = collect();
        $this->coveredKeyResults = collect();
    }

    #[On('keyresult')]
    public function setContext(array $payload = []): void
    {
        $push = (bool) ($payload['push'] ?? false);
        $shouldOpen = (bool) ($payload['open_modal'] ?? false);

        if ($push) {
            // Aktuellen Kontext sichern (falls vorhanden), damit wir nach dem Schließen zurückspringen können
            if ($this->contextType && $this->contextId) {
                $this->contextStack[] = [
                    'type' => $this->contextType,
                    'id' => $this->contextId,
                ];
            }
        }

        $this->contextType = $payload['context_type'] ?? null;
        $this->contextId = isset($payload['context_id']) ? (int) $payload['context_id'] : null;

        // Wenn Modal bereits offen ist, Daten neu laden
        if ($this->open && $this->contextType && $this->contextId) {
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
            $this->loadCoveredKeyResults();
        }

        // Optional: Modal direkt öffnen (verhindert Race Conditions zwischen keyresult und keyresult:open)
        if ($shouldOpen) {
            $this->open();
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
            $this->loadCoveredKeyResults();
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
        $this->coveredKeyResults = collect();

        // Wenn der Kontext temporär überschrieben wurde (push=true), restore den vorherigen Kontext
        if (!empty($this->contextStack)) {
            $prev = array_pop($this->contextStack);
            $this->contextType = $prev['type'] ?? null;
            $this->contextId = isset($prev['id']) ? (int) $prev['id'] : null;
        }
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

        // Lade nur KeyResults, die DIREKT mit diesem Kontext verknüpft sind
        // (loose coupling: keine automatische Verknüpfung über Parent-Kontexte)
        $linkedContexts = KeyResultContext::where('context_type', $this->contextType)
            ->where('context_id', $this->contextId)
            ->where('is_primary', true)
            ->with(['keyResult.objective.cycle.okr', 'keyResult.performance', 'keyResult.user'])
            ->get();

        $this->linkedKeyResults = $linkedContexts->map(function ($context) {
            return $context->keyResult;
        })->filter();
    }

    protected function loadCoveredKeyResults(): void
    {
        if (! $this->contextType || ! $this->contextId) {
            $this->coveredKeyResults = collect();
            return;
        }

        // Prüfe ob dieser Kontext über einen Parent-Kontext (Root-Kontext) abgedeckt ist
        // (loose coupling: z.B. Tasks sind über Project abgedeckt, aber nicht direkt verknüpft)
        if (! class_exists($this->contextType)) {
            $this->coveredKeyResults = collect();
            return;
        }

        $contextModel = $this->contextType::find($this->contextId);
        
        if (! $contextModel || ! $contextModel instanceof HasKeyResultAncestors) {
            $this->coveredKeyResults = collect();
            return;
        }

        // Hole alle Ancestors über das Interface
        $ancestors = $contextModel->keyResultAncestors();
        
        if (empty($ancestors)) {
            $this->coveredKeyResults = collect();
            return;
        }

        // Sammle alle KeyResults, die über Root-Kontexte (is_root = true) abgedeckt sind
        $coveredKeyResultIds = collect();
        
        foreach ($ancestors as $ancestor) {
            // Nur Root-Kontexte berücksichtigen (diese decken alle Child-Kontexte ab)
            if ($ancestor['is_root'] ?? false) {
                $rootLinkedContexts = KeyResultContext::where('context_type', $ancestor['type'])
                    ->where('context_id', $ancestor['id'])
                    ->where('is_primary', true)
                    ->with(['keyResult.objective.cycle.okr', 'keyResult.performance', 'keyResult.user'])
                    ->get();
                
                foreach ($rootLinkedContexts as $context) {
                    if ($context->keyResult) {
                        $coveredKeyResultIds->push($context->keyResult->id);
                    }
                }
            }
        }

        // Lade alle abgedeckten KeyResults mit allen Relations
        if ($coveredKeyResultIds->isNotEmpty()) {
            $this->coveredKeyResults = KeyResult::whereIn('id', $coveredKeyResultIds->unique())
                ->with(['objective.cycle.okr', 'performance', 'user'])
                ->get();
        } else {
            $this->coveredKeyResults = collect();
        }
    }

    public function attachKeyResult(int $keyResultId): void
    {
        if (! $this->contextType || ! $this->contextId) {
            session()->flash('error', 'Kein Kontext gesetzt.');
            return;
        }

        try {
            // Prüfe generisch: Wenn der Kontext Ancestors hat, prüfe ob ein Root-Kontext bereits verknüpft ist
            if (class_exists($this->contextType)) {
                $contextModel = $this->contextType::find($this->contextId);
                
                if ($contextModel && $contextModel instanceof HasKeyResultAncestors) {
                    $ancestors = $contextModel->keyResultAncestors();
                    
                    // Prüfe für jeden Root-Kontext, ob bereits eine Verknüpfung existiert
                    foreach ($ancestors as $ancestor) {
                        if ($ancestor['is_root'] ?? false) {
                            $rootLinked = KeyResultContext::where('key_result_id', $keyResultId)
                                ->where('context_type', $ancestor['type'])
                                ->where('context_id', $ancestor['id'])
                                ->where('is_primary', true)
                                ->exists();
                            
                            if ($rootLinked) {
                                $resolver = app(KeyResultContextResolver::class);
                                $rootLabel = $ancestor['label'] ?? $resolver->resolveLabel($ancestor['type'], $ancestor['id']) ?? 'Root-Kontext';
                                session()->flash('error', "Der übergeordnete Kontext \"{$rootLabel}\" ist bereits mit diesem KeyResult verknüpft. Alle untergeordneten Kontexte sind daher bereits abgedeckt.");
                                return;
                            }
                        }
                    }
                }
            }

            $storeService = app(StoreKeyResultContext::class);
            $storeService->store($keyResultId, $this->contextType, $this->contextId);

            session()->flash('message', 'KeyResult erfolgreich verknüpft!');
            
            // Daten neu laden
            $this->loadKeyResults();
            $this->loadLinkedKeyResults();
            $this->loadCoveredKeyResults();
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
            $this->loadCoveredKeyResults();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Entfernen: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('okr::livewire.modal-key-result');
    }
}

