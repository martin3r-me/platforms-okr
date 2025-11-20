<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\KeyResult;
use Platform\Core\Models\User;
use Livewire\Attributes\Computed;

class ObjectiveShow extends Component
{
    public Objective $objective;
    public $isDirty = false;

    // Key Result Modal Properties
    public $keyResultCreateModalShow = false;
    public $keyResultEditModalShow = false;
    public $editingKeyResultId = null;
    public $keyResultForm = [
        'title' => '',
        'description' => '',
        'target_value' => '',
        'current_value' => '',
        'unit' => '',
        'order' => 0,
    ];

    protected $rules = [
        'objective.title' => 'required|string|max:255',
        'objective.description' => 'nullable|string',
        'objective.order' => 'required|integer|min:0',

        'keyResultForm.title' => 'required|string|max:255',
        'keyResultForm.description' => 'nullable|string',
        'keyResultForm.target_value' => 'required|string',
        'keyResultForm.current_value' => 'nullable|string',
        'keyResultForm.unit' => 'nullable|string|max:50',
        'keyResultForm.order' => 'required|integer|min:0',
    ];

    public function mount(Objective $objective)
    {
        $this->objective = $objective;
        $this->objective->load(['cycle', 'okr', 'keyResults.performances']);
    }

    #[Computed]
    public function users()
    {
        return User::where('current_team_id', auth()->user()->current_team_id)->get();
    }

    public function updated($property)
    {
        if (str($property)->startsWith('objective.')) {
            $this->isDirty = true;
        }
        if (str($property)->startsWith('keyResultForm.')) {
            $this->isDirty = true;
        }
        $this->validateOnly($property);
    }

    public function save()
    {
        $this->validate([
            'objective.title' => 'required|string|max:255',
            'objective.description' => 'nullable|string',
            'objective.order' => 'required|integer|min:0',
        ]);

        $this->objective->save();
        $this->isDirty = false;
        session()->flash('message', 'Objective erfolgreich aktualisiert!');
    }

    // Key Result Management
    public function addKeyResult()
    {
        $this->resetKeyResultForm();
        $this->keyResultCreateModalShow = true;
    }

    public function closeKeyResultCreateModal()
    {
        $this->keyResultCreateModalShow = false;
        $this->resetKeyResultForm();
    }

    public function editKeyResult($keyResultId)
    {
        $keyResult = $this->objective->keyResults()->findOrFail($keyResultId);
        $this->editingKeyResultId = $keyResult->id;
        $this->keyResultForm = [
            'title' => $keyResult->title,
            'description' => $keyResult->description,
            'target_value' => $keyResult->target_value,
            'current_value' => $keyResult->current_value,
            'unit' => $keyResult->unit,
            'order' => $keyResult->order,
        ];
        $this->keyResultEditModalShow = true;
    }

    public function closeKeyResultEditModal()
    {
        $this->keyResultEditModalShow = false;
        $this->resetKeyResultForm();
    }

    public function saveKeyResult()
    {
        $this->validate([
            'keyResultForm.title' => 'required|string|max:255',
            'keyResultForm.description' => 'nullable|string',
            'keyResultForm.target_value' => 'required|string',
            'keyResultForm.current_value' => 'nullable|string',
            'keyResultForm.unit' => 'nullable|string|max:50',
            'keyResultForm.order' => 'required|integer|min:0',
        ]);

        if ($this->editingKeyResultId) {
            $keyResult = $this->objective->keyResults()->findOrFail($this->editingKeyResultId);
            $keyResult->update([
                'title' => $this->keyResultForm['title'],
                'description' => $this->keyResultForm['description'],
                'target_value' => $this->keyResultForm['target_value'],
                'current_value' => $this->keyResultForm['current_value'],
                'unit' => $this->keyResultForm['unit'],
                'order' => $this->keyResultForm['order'],
            ]);
            session()->flash('message', 'Key Result erfolgreich aktualisiert!');
        } else {
            $this->objective->keyResults()->create([
                'title' => $this->keyResultForm['title'],
                'description' => $this->keyResultForm['description'],
                'target_value' => $this->keyResultForm['target_value'],
                'current_value' => $this->keyResultForm['current_value'],
                'unit' => $this->keyResultForm['unit'],
                'order' => $this->keyResultForm['order'],
                'team_id' => auth()->user()->currentTeam?->id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('message', 'Key Result erfolgreich hinzugefügt!');
        }

        $this->objective->load('keyResults.performances'); // Refresh key results
        $this->closeKeyResultCreateModal();
        $this->closeKeyResultEditModal();
    }

    public function deleteKeyResultAndCloseModal()
    {
        $keyResult = $this->objective->keyResults()->findOrFail($this->editingKeyResultId);
        $keyResult->delete();
        session()->flash('message', 'Key Result erfolgreich gelöscht!');
        $this->objective->load('keyResults.performances'); // Refresh key results
        $this->closeKeyResultEditModal();
    }

    protected function resetKeyResultForm()
    {
        $this->editingKeyResultId = null;
        $this->keyResultForm = [
            'title' => '',
            'description' => '',
            'target_value' => '',
            'current_value' => '',
            'unit' => '',
            'order' => 0,
        ];
    }

    public function render()
    {
        return view('okr::livewire.objective-show')
            ->layout('platform::layouts.app');
    }
}
