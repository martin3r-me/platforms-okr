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

    public function manageObjectiveKeyResults($objectiveId)
    {
        // Navigate to objective key results management
        return redirect()->route('okr.objectives.show', ['objective' => $objectiveId]);
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

    public function render()
    {
        return view('okr::livewire.cycle-show')
            ->layout('platform::layouts.app');
    }
}
