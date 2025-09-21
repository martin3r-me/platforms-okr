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
    public $editingKeyResultId = null;
    public $editingKeyResultObjectiveId = null;
    public $keyResultForm = [
        'title' => '',
        'description' => '',
        'target_value' => '',
        'current_value' => '',
        'unit' => '',
        'order' => 0,
    ];

    protected $rules = [
        'cycle.status' => 'required|in:draft,active,completed,ending_soon,past',
        'cycle.notes' => 'nullable|string',

        'objectiveForm.title' => 'required|string|max:255',
        'objectiveForm.description' => 'nullable|string',
        'objectiveForm.order' => 'required|integer|min:0',

        'keyResultForm.title' => 'required|string|max:255',
        'keyResultForm.description' => 'nullable|string',
        'keyResultForm.target_value' => 'required|string',
        'keyResultForm.current_value' => 'nullable|string',
        'keyResultForm.unit' => 'nullable|string|max:50',
        'keyResultForm.order' => 'required|integer|min:0',
    ];

    public function mount(Cycle $cycle)
    {
        $this->cycle = $cycle;
        $this->cycle->load(['okr', 'template', 'objectives.keyResults']);
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
        $this->validateOnly($property);
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
        
        // Auto-calculate order for new Key Result
        $nextOrder = 0;
        $objective = $this->cycle->objectives()->find($objectiveId);
        if ($objective) {
            $nextOrder = $objective->keyResults()->max('order') + 1;
        }
        
        $this->keyResultForm = [
            'title' => '',
            'description' => '',
            'target_value' => '0',
            'current_value' => '0',
            'unit' => '',
            'order' => $nextOrder,
        ];
        
        $this->keyResultCreateModalShow = true;
        
        // Debug: Log the objective ID
        \Log::info('Adding Key Result to Objective ID: ' . $objectiveId . ' with order: ' . $nextOrder);
    }

    public function closeKeyResultCreateModal()
    {
        $this->keyResultCreateModalShow = false;
        $this->resetKeyResultForm();
    }

    public function editKeyResult($keyResultId)
    {
        $keyResult = $this->cycle->objectives()->with('keyResults')->get()
            ->pluck('keyResults')->flatten()
            ->find($keyResultId);
        
        if ($keyResult) {
            $this->editingKeyResultId = $keyResult->id;
            $this->editingKeyResultObjectiveId = $keyResult->objective_id;
            $this->keyResultForm = [
                'title' => $keyResult->title,
                'description' => $keyResult->description,
                'target_value' => $keyResult->target_value,
                'current_value' => $keyResult->current_value,
                'unit' => $keyResult->unit,
                'order' => $keyResult->order, // Keep existing order for editing
            ];
            $this->keyResultEditModalShow = true;
        }
    }

    public function closeKeyResultEditModal()
    {
        $this->keyResultEditModalShow = false;
        $this->resetKeyResultForm();
    }

    public function saveKeyResult()
    {
        try {
            $this->validate([
                'keyResultForm.title' => 'required|string|max:255',
                'keyResultForm.description' => 'nullable|string',
                'keyResultForm.target_value' => 'required|string',
                'keyResultForm.current_value' => 'nullable|string',
                'keyResultForm.unit' => 'nullable|string|max:50',
            ]);

            if ($this->editingKeyResultId) {
                $keyResult = $this->cycle->objectives()->with('keyResults')->get()
                    ->pluck('keyResults')->flatten()
                    ->find($this->editingKeyResultId);
                
                if ($keyResult) {
                    $keyResult->update([
                        'title' => $this->keyResultForm['title'],
                        'description' => $this->keyResultForm['description'],
                        'target_value' => $this->keyResultForm['target_value'],
                        'current_value' => $this->keyResultForm['current_value'],
                        'unit' => $this->keyResultForm['unit'],
                        'order' => $this->keyResultForm['order'],
                    ]);
                    session()->flash('message', 'Key Result erfolgreich aktualisiert!');
                }
            } else {
                if (!$this->editingKeyResultObjectiveId) {
                    session()->flash('error', 'Fehler: Objective ID fehlt!');
                    return;
                }
                
                \Log::info('Creating Key Result with data:', $this->keyResultForm);
                
                $objective = $this->cycle->objectives()->findOrFail($this->editingKeyResultObjectiveId);
                $keyResult = $objective->keyResults()->create([
                    'title' => $this->keyResultForm['title'],
                    'description' => $this->keyResultForm['description'],
                    'target_value' => $this->keyResultForm['target_value'],
                    'current_value' => $this->keyResultForm['current_value'],
                    'unit' => $this->keyResultForm['unit'],
                    'order' => $this->keyResultForm['order'],
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);
                
                \Log::info('Key Result created with ID: ' . $keyResult->id);
                session()->flash('message', 'Key Result erfolgreich hinzugefügt!');
            }

            $this->cycle->load('objectives.keyResults'); // Refresh objectives and key results
            $this->closeKeyResultCreateModal();
            $this->closeKeyResultEditModal();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Validierungsfehler: ' . implode(', ', $e->validator->errors()->all()));
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    public function deleteKeyResultAndCloseModal()
    {
        $keyResult = $this->cycle->objectives()->with('keyResults')->get()
            ->pluck('keyResults')->flatten()
            ->find($this->editingKeyResultId);
        
        if ($keyResult) {
            $keyResult->delete();
            session()->flash('message', 'Key Result erfolgreich gelöscht!');
            $this->cycle->load('objectives.keyResults'); // Refresh objectives and key results
        }
        $this->closeKeyResultEditModal();
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

    protected function resetKeyResultForm()
    {
        $this->editingKeyResultId = null;
        $this->editingKeyResultObjectiveId = null;
        
        // Auto-calculate order for new Key Result
        $nextOrder = 0;
        if ($this->editingKeyResultObjectiveId) {
            $objective = $this->cycle->objectives()->find($this->editingKeyResultObjectiveId);
            if ($objective) {
                $nextOrder = $objective->keyResults()->max('order') + 1;
            }
        }
        
        $this->keyResultForm = [
            'title' => '',
            'description' => '',
            'target_value' => '0', // Default value for required field
            'current_value' => '0', // Default value
            'unit' => '',
            'order' => $nextOrder,
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
        
        $this->cycle->load('objectives.keyResults');
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
        
        $this->cycle->load('objectives.keyResults');
        session()->flash('message', 'Key Result-Reihenfolge aktualisiert!');
    }

    public function render()
    {
        return view('okr::livewire.cycle-show')
            ->layout('platform::layouts.app');
    }
}
