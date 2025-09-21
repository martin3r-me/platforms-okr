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

        'keyResultTitle' => 'required|string|max:255',
        'keyResultDescription' => 'nullable|string',
        'keyResultValueType' => 'required|in:absolute,percentage,boolean',
        'keyResultTargetValue' => 'required|string',
        'keyResultCurrentValue' => 'nullable|string',
        'keyResultUnit' => 'nullable|string|max:50',
    ];

    public function mount(Cycle $cycle)
    {
        $this->cycle = $cycle;
        $this->cycle->load(['okr', 'template', 'objectives.keyResults.performance']);
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
            $this->keyResultTargetValue = $keyResult->target_value ?? '';
            $this->keyResultUnit = $keyResult->unit ?? '';
            
            // Load performance data
            if ($keyResult->performance) {
                $this->keyResultValueType = $keyResult->performance->type ?? 'absolute';
                $this->keyResultCurrentValue = $keyResult->performance->current_value ?? '0';
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

    public function saveKeyResult()
    {
        try {
            // Debug: Log current values
            \Log::info('Key Result Save Debug', [
                'editingKeyResultId' => $this->editingKeyResultId,
                'editingKeyResultObjectiveId' => $this->editingKeyResultObjectiveId,
                'keyResultTitle' => $this->keyResultTitle,
                'keyResultTargetValue' => $this->keyResultTargetValue,
                'keyResultCurrentValue' => $this->keyResultCurrentValue,
                'keyResultValueType' => $this->keyResultValueType,
                'keyResultUnit' => $this->keyResultUnit,
            ]);

            $this->validate([
                'keyResultTitle' => 'required|string|max:255',
                'keyResultDescription' => 'nullable|string',
                'keyResultValueType' => 'required|in:absolute,percentage,boolean',
                'keyResultTargetValue' => 'required|string',
                'keyResultCurrentValue' => 'nullable|string',
                'keyResultUnit' => 'nullable|string|max:50',
            ]);

            if (!$this->editingKeyResultObjectiveId) {
                session()->flash('error', 'Fehler: Objective ID fehlt!');
                return;
            }
            
            $objective = $this->cycle->objectives()->findOrFail($this->editingKeyResultObjectiveId);
            
            // Set default unit based on value type
            $unit = $this->keyResultUnit;
            if (empty($unit)) {
                $unit = match($this->keyResultValueType) {
                    'percentage' => '%',
                    'boolean' => '',
                    'absolute' => 'Stück',
                    default => ''
                };
            }

            if ($this->editingKeyResultId) {
                // Update existing Key Result
                $keyResult = $objective->keyResults()->findOrFail($this->editingKeyResultId);
                $keyResult->update([
                    'title' => $this->keyResultTitle,
                    'description' => $this->keyResultDescription,
                    'target_value' => $this->keyResultTargetValue,
                    'unit' => $unit,
                ]);

                // Update performance record
                if ($keyResult->performance) {
                    $keyResult->performance->update([
                        'type' => $this->keyResultValueType,
                        'target_value' => $this->keyResultTargetValue,
                        'current_value' => $this->keyResultCurrentValue ?: 0,
                        'is_completed' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue === 'Ja' || $this->keyResultCurrentValue === 'Erledigt') : false,
                    ]);
                } else {
                    $keyResult->performances()->create([
                        'type' => $this->keyResultValueType,
                        'target_value' => $this->keyResultTargetValue,
                        'current_value' => $this->keyResultCurrentValue ?: 0,
                        'is_completed' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue === 'Ja' || $this->keyResultCurrentValue === 'Erledigt') : false,
                        'performance_score' => 0.0,
                        'team_id' => auth()->user()->current_team_id,
                        'user_id' => auth()->id(),
                    ]);
                }
                
                $this->closeKeyResultEditModal();
                session()->flash('message', 'Key Result erfolgreich aktualisiert!');
            } else {
                // Create new Key Result
                $nextOrder = $objective->keyResults()->max('order') + 1;
                
                $keyResult = $objective->keyResults()->create([
                    'title' => $this->keyResultTitle,
                    'description' => $this->keyResultDescription,
                    'target_value' => $this->keyResultTargetValue,
                    'current_value' => '0', // Default, wird über Performance verwaltet
                    'unit' => $unit,
                    'order' => $nextOrder,
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);

                // Create initial performance record
                $keyResult->performances()->create([
                    'type' => $this->keyResultValueType,
                    'target_value' => $this->keyResultTargetValue,
                    'current_value' => $this->keyResultCurrentValue ?: 0,
                    'is_completed' => $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue === 'Ja' || $this->keyResultCurrentValue === 'Erledigt') : false,
                    'performance_score' => 0.0,
                    'team_id' => auth()->user()->current_team_id,
                    'user_id' => auth()->id(),
                ]);
                
                $this->closeKeyResultCreateModal();
                session()->flash('message', 'Key Result erfolgreich hinzugefügt!');
            }
            
            $this->cycle->load('objectives.keyResults.performance');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Validierungsfehler: ' . implode(', ', $e->validator->errors()->all()));
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
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
