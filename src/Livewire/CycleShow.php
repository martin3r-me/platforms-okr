<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Objective;
use Platform\Core\Models\User;
use Livewire\Attributes\Computed;

class CycleShow extends Component
{
    public Cycle $cycle;
    public $isDirty = false;

    // Objective Modal Properties
    public $objectiveCreateModalShow = false;
    public $objectiveEditModalShow = false;
    public $editingObjectiveId = null;
    public $objectiveForm = [
        'title' => '',
        'description' => '',
        'order' => 0,
    ];

    // Key Result Modal Properties
    public $keyResultCreateModalShow = false;
    public $keyResultEditModalShow = false;
    public $editingKeyResultObjectiveId = null;
    public $editingKeyResultId = null;
    public $keyResultTitle = '';
    public $keyResultDescription = '';
    public $keyResultValueType = 'absolute'; // absolute, percentage, boolean
    public $keyResultTargetValue = '';
    public $keyResultCurrentValue = '';
    public $keyResultUnit = '';

    protected $rules = [
        'cycle.status' => 'required|in:draft,active,completed,ending_soon,past',
        'cycle.notes' => 'nullable|string',

        'objectiveForm.title' => 'required|string|max:255',
        'objectiveForm.description' => 'nullable|string',
        'objectiveForm.order' => 'required|integer|min:0',
    ];

    public function mount(Cycle $cycle)
    {
        $this->cycle = $cycle;
        $this->cycle->load(['okr', 'template', 'objectives.keyResults.performance']);
    }

    public function rendered()
    {
        // Context an CursorSidebar senden
        $this->dispatch('comms', [
            'model' => get_class($this->cycle),                                // z. B. 'Platform\Okr\Models\Cycle'
            'modelId' => $this->cycle->id,
            'subject' => $this->cycle->okr->name ?? 'OKR Cycle',
            'description' => $this->cycle->okr->description ?? '',
            'url' => route('okr.cycles.show', $this->cycle),                  // absolute URL zum Cycle
            'source' => 'okr.cycle.view',                                     // eindeutiger Quell-Identifier
            'recipients' => [],                                               // falls vorhanden, sonst leer
            'meta' => [
                'status' => $this->cycle->status,
                'objectives_count' => $this->cycle->objectives->count(),
                'key_results_count' => $this->cycle->objectives->sum(function($obj) {
                    return $obj->keyResults->count();
                }),
                'team_id' => $this->cycle->okr->team_id ?? null,
            ],
        ]);
    }

    #[Computed]
    public function users()
    {
        return User::where('current_team_id', auth()->user()->current_team_id)->get();
    }

    public function updated($property)
    {
        if (str($property)->startsWith('cycle.')) {
            $this->isDirty = true;
        }
        if (str($property)->startsWith('objectiveForm.')) {
            $this->isDirty = true;
        }
        
        // Only validate cycle and objective properties, not key result properties
        if (str($property)->startsWith('cycle.') || str($property)->startsWith('objectiveForm.')) {
            $this->validateOnly($property);
        }
    }

    public function save()
    {
        $this->validate([
            'cycle.status' => 'required|in:draft,active,completed,ending_soon,past',
            'cycle.notes' => 'nullable|string',
        ]);

        $this->cycle->save();
        $this->isDirty = false;
        session()->flash('message', 'Cycle erfolgreich aktualisiert!');
    }

    // Objective Management
    public function addObjective()
    {
        $this->resetObjectiveForm();
        $this->objectiveCreateModalShow = true;
    }

    public function closeObjectiveCreateModal()
    {
        $this->objectiveCreateModalShow = false;
        $this->resetObjectiveForm();
    }

    public function editObjective($objectiveId)
    {
        $objective = $this->cycle->objectives()->findOrFail($objectiveId);
        $this->editingObjectiveId = $objective->id;
        $this->objectiveForm = [
            'title' => $objective->title,
            'description' => $objective->description,
            'order' => $objective->order,
        ];
        $this->objectiveEditModalShow = true;
    }

    public function closeObjectiveEditModal()
    {
        $this->objectiveEditModalShow = false;
        $this->resetObjectiveForm();
    }

    public function saveObjective()
    {
        $this->validate([
            'objectiveForm.title' => 'required|string|max:255',
            'objectiveForm.description' => 'nullable|string',
            'objectiveForm.order' => 'required|integer|min:0',
        ]);

        if ($this->editingObjectiveId) {
            $objective = $this->cycle->objectives()->findOrFail($this->editingObjectiveId);
            $objective->update([
                'title' => $this->objectiveForm['title'],
                'description' => $this->objectiveForm['description'],
                'order' => $this->objectiveForm['order'],
            ]);
            session()->flash('message', 'Objective erfolgreich aktualisiert!');
        } else {
            $this->cycle->objectives()->create([
                'title' => $this->objectiveForm['title'],
                'description' => $this->objectiveForm['description'],
                'order' => $this->objectiveForm['order'],
                'okr_id' => $this->cycle->okr_id,
                'team_id' => auth()->user()->current_team_id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('message', 'Objective erfolgreich hinzugefügt!');
        }

        $this->cycle->load('objectives.keyResults'); // Refresh objectives
        $this->closeObjectiveCreateModal();
        $this->closeObjectiveEditModal();
    }

    public function deleteObjectiveAndCloseModal()
    {
        $objective = $this->cycle->objectives()->findOrFail($this->editingObjectiveId);
        $objective->delete();
        session()->flash('message', 'Objective erfolgreich gelöscht!');
        $this->cycle->load('objectives.keyResults'); // Refresh objectives
        $this->closeObjectiveEditModal();
    }

    // Key Result Management
    public function addKeyResult($objectiveId)
    {
        $this->editingKeyResultObjectiveId = $objectiveId;
        $this->keyResultTitle = '';
        $this->keyResultDescription = '';
        $this->keyResultValueType = 'absolute';
        $this->keyResultTargetValue = '';
        $this->keyResultCurrentValue = '';
        $this->keyResultUnit = '';
        $this->keyResultCreateModalShow = true;
    }

    public function closeKeyResultCreateModal()
    {
        $this->keyResultCreateModalShow = false;
        $this->editingKeyResultObjectiveId = null;
        $this->keyResultTitle = '';
        $this->keyResultDescription = '';
        $this->keyResultValueType = 'absolute';
        $this->keyResultTargetValue = '';
        $this->keyResultCurrentValue = '';
        $this->keyResultUnit = '';
    }

    public function editKeyResult($keyResultId)
    {
        // Find the key result directly from the database
        $keyResult = \Platform\Okr\Models\KeyResult::with('performance')->find($keyResultId);
        
        if ($keyResult) {
            $this->editingKeyResultId = $keyResult->id;
            $this->editingKeyResultObjectiveId = $keyResult->objective_id;
            $this->keyResultTitle = $keyResult->title;
            $this->keyResultDescription = $keyResult->description ?? '';
            // Zielwert aus Performance wenn vorhanden
            $this->keyResultTargetValue = $keyResult->performance?->target_value ?? '';
            // Einheit wird aktuell nicht persistiert; leer lassen
            $this->keyResultUnit = '';
            
            // Load performance data
            if ($keyResult->performance) {
                $this->keyResultValueType = $keyResult->performance->type ?? 'absolute';
                $this->keyResultCurrentValue = (string)($keyResult->performance->current_value ?? '0');
            } else {
                $this->keyResultValueType = 'absolute';
                $this->keyResultCurrentValue = '0';
            }
            
            $this->keyResultEditModalShow = true;
        }
    }

    public function closeKeyResultEditModal()
    {
        $this->keyResultEditModalShow = false;
        $this->editingKeyResultId = null;
        $this->keyResultTitle = '';
        $this->keyResultDescription = '';
        $this->keyResultValueType = 'absolute';
        $this->keyResultTargetValue = '';
        $this->keyResultCurrentValue = '';
        $this->keyResultUnit = '';
    }

    public function testMethod()
    {
        \Log::info('testMethod called');
        session()->flash('message', 'Test-Methode funktioniert!');
        $this->dispatch('test-event');
    }

    public function saveKeyResult()
    {
        // Einfache Validierung
        if (empty($this->keyResultTitle)) {
            session()->flash('error', 'Titel ist erforderlich!');
            return;
        }
        
        if (empty($this->keyResultValueType)) {
            session()->flash('error', 'Wert-Typ ist erforderlich!');
            return;
        }
        
        if (empty($this->keyResultTargetValue)) {
            session()->flash('error', 'Zielwert ist erforderlich!');
            return;
        }

        if (!$this->editingKeyResultObjectiveId) {
            session()->flash('error', 'Fehler: Objective ID fehlt!');
            return;
        }
        
        try {
            $objective = $this->cycle->objectives()->findOrFail($this->editingKeyResultObjectiveId);
            
            if ($this->editingKeyResultId) {
                // Update existing Key Result
                $keyResult = $objective->keyResults()->findOrFail($this->editingKeyResultId);
                $keyResult->update([
                    'title' => $this->keyResultTitle,
                    'description' => $this->keyResultDescription,
                ]);

                // Immer eine neue Performance-Version erstellen (Versionierung)
                $keyResult->performances()->create([
                    'type' => $this->keyResultValueType,
                    'target_value' => $this->keyResultValueType === 'boolean' ? 1.0 : (float) $this->keyResultTargetValue,
                    'current_value' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : (float) ($this->keyResultCurrentValue ?: 0),
                    'is_completed' => $this->keyResultValueType === 'boolean' ? (bool) $this->keyResultCurrentValue : false,
                    'performance_score' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : 0.0,
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);
                
                $this->closeKeyResultEditModal();
                session()->flash('message', 'Key Result erfolgreich aktualisiert!');
            } else {
                // Create new Key Result
                $nextOrder = $objective->keyResults()->max('order') + 1;
                
                $keyResult = $objective->keyResults()->create([
                    'title' => $this->keyResultTitle,
                    'description' => $this->keyResultDescription,
                    'order' => $nextOrder,
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);

                // Create initial performance record (erste Version)
                $keyResult->performances()->create([
                    'type' => $this->keyResultValueType,
                    'target_value' => $this->keyResultValueType === 'boolean' ? 1.0 : (float) $this->keyResultTargetValue,
                    'current_value' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : (float) ($this->keyResultCurrentValue ?: 0),
                    'is_completed' => $this->keyResultValueType === 'boolean' ? (bool) $this->keyResultCurrentValue : false,
                    'performance_score' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : 0.0,
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);
                
                $this->closeKeyResultCreateModal();
                session()->flash('message', 'Key Result erfolgreich hinzugefügt!');
            }
            
            $this->cycle->load('objectives.keyResults.performance');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    /**
     * Update nur den aktuellen Wert (Performance-Update ohne Zielwert-Änderung)
     */
    public function updateKeyResultPerformance($keyResultId, $newCurrentValue)
    {
        try {
            $keyResult = \Platform\Okr\Models\KeyResult::findOrFail($keyResultId);
            
            // Neue Performance-Version erstellen
            $keyResult->performances()->create([
                'type' => $keyResult->performance->type,
                'target_value' => $keyResult->performance->target_value, // Zielwert bleibt unverändert
                'current_value' => (float) $newCurrentValue,
                'is_completed' => $keyResult->performance->type === 'boolean' ? (bool) $newCurrentValue : ($newCurrentValue >= $keyResult->performance->target_value),
                'performance_score' => $keyResult->performance->type === 'boolean' ? ($newCurrentValue ? 1.0 : 0.0) : ($newCurrentValue / $keyResult->performance->target_value),
                'team_id' => auth()->user()->current_team_id,
                'user_id' => auth()->id(),
            ]);
            
            $this->cycle->load('objectives.keyResults.performance');
            session()->flash('message', 'Performance erfolgreich aktualisiert!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    protected function resetObjectiveForm()
    {
        $this->editingObjectiveId = null;
        $this->objectiveForm = [
            'title' => '',
            'description' => '',
            'order' => 0,
        ];
    }


    // Sortable Methods
    public function updateObjectiveOrder($items)
    {
        foreach ($items as $item) {
            $objective = $this->cycle->objectives()->find($item['value']);
            if ($objective) {
                $objective->update(['order' => $item['order']]);
            }
        }
        
        $this->cycle->load('objectives.keyResults.performance');
        session()->flash('message', 'Objective-Reihenfolge aktualisiert!');
    }

    public function updateKeyResultOrder($items)
    {
        foreach ($items as $group) {
            $objective = $this->cycle->objectives()->find($group['value']);
            if ($objective && isset($group['items'])) {
                foreach ($group['items'] as $item) {
                    $keyResult = $objective->keyResults()->find($item['value']);
                    if ($keyResult) {
                        $keyResult->update(['order' => $item['order']]);
                    }
                }
            }
        }
        
        $this->cycle->load('objectives.keyResults.performance');
        session()->flash('message', 'Key Result-Reihenfolge aktualisiert!');
    }

    public function render()
    {
        return view('okr::livewire.cycle-show')
            ->layout('platform::layouts.app');
    }
}
