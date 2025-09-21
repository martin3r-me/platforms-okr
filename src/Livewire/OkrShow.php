<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Core\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class OkrShow extends Component
{
    public Okr $okr;
    public $isDirty = false;

    // Cycle Modal Properties
    public $cycleCreateModalShow = false;
    public $cycleEditModalShow = false;
    public $editingCycleId = null;
    public $cycleForm = [
        'cycle_template_id' => '',
        'status' => 'draft',
        'notes' => '',
    ];

    protected $rules = [
        'okr.title' => 'required|string|max:255',
        'okr.description' => 'nullable|string',
        'okr.performance_score' => 'nullable|numeric|min:0|max:100',
        'okr.auto_transfer' => 'boolean',
        'okr.is_template' => 'boolean',
        'okr.manager_user_id' => 'nullable|exists:users,id',

        'cycleForm.cycle_template_id' => 'required|exists:okr_cycle_templates,id',
        'cycleForm.status' => 'required|in:draft,active,completed,ending_soon,past',
        'cycleForm.notes' => 'nullable|string',
    ];

    public function mount(Okr $okr)
    {
        $this->okr = $okr;
        $this->okr->load(['user', 'manager', 'cycles.template']);
    }

    #[Computed]
    public function users()
    {
        return User::where('current_team_id', auth()->user()->current_team_id)->get();
    }

    #[Computed]
    public function cycleTemplates()
    {
        return CycleTemplate::orderBy('starts_at')->get();
    }

    public function updated($property)
    {
        if (str($property)->startsWith('okr.')) {
            $this->isDirty = true;
        }
        $this->validateOnly($property);
    }

    public function save()
    {
        $this->validate([
            'okr.title' => 'required|string|max:255',
            'okr.description' => 'nullable|string',
            'okr.performance_score' => 'nullable|numeric|min:0|max:100',
            'okr.auto_transfer' => 'boolean',
            'okr.is_template' => 'boolean',
            'okr.manager_user_id' => 'nullable|exists:users,id',
        ]);

        $this->okr->save();
        $this->isDirty = false;
        session()->flash('message', 'OKR erfolgreich aktualisiert!');
    }

    // Cycle Management
    public function addCycle()
    {
        $this->resetCycleForm();
        $this->cycleCreateModalShow = true;
    }

    public function closeCycleCreateModal()
    {
        $this->cycleCreateModalShow = false;
        $this->resetCycleForm();
    }

    public function editCycle($cycleId)
    {
        $cycle = $this->okr->cycles()->findOrFail($cycleId);
        $this->editingCycleId = $cycle->id;
        $this->cycleForm = [
            'cycle_template_id' => $cycle->cycle_template_id,
            'status' => $cycle->status,
            'notes' => $cycle->notes,
        ];
        $this->cycleEditModalShow = true;
    }

    public function closeCycleEditModal()
    {
        $this->cycleEditModalShow = false;
        $this->resetCycleForm();
    }

    public function saveCycle()
    {
        $this->validate([
            'cycleForm.cycle_template_id' => 'required|exists:okr_cycle_templates,id',
            'cycleForm.status' => 'required|in:draft,active,completed,ending_soon,past',
            'cycleForm.notes' => 'nullable|string',
        ]);

        if ($this->editingCycleId) {
            $cycle = $this->okr->cycles()->findOrFail($this->editingCycleId);
            $cycle->update([
                'cycle_template_id' => $this->cycleForm['cycle_template_id'],
                'status' => $this->cycleForm['status'],
                'notes' => $this->cycleForm['notes'],
            ]);
            session()->flash('message', 'Cycle erfolgreich aktualisiert!');
        } else {
            $this->okr->cycles()->create([
                'cycle_template_id' => $this->cycleForm['cycle_template_id'],
                'status' => $this->cycleForm['status'],
                'notes' => $this->cycleForm['notes'],
                'team_id' => auth()->user()->current_team_id,
                'user_id' => auth()->id(),
            ]);
            session()->flash('message', 'Cycle erfolgreich hinzugefügt!');
        }

        $this->okr->load('cycles.template'); // Refresh cycles
        $this->closeCycleCreateModal();
        $this->closeCycleEditModal();
    }

    public function deleteCycleAndCloseModal()
    {
        $cycle = $this->okr->cycles()->findOrFail($this->editingCycleId);
        $cycle->delete();
        session()->flash('message', 'Cycle erfolgreich gelöscht!');
        $this->okr->load('cycles.template'); // Refresh cycles
        $this->closeCycleEditModal();
    }

    protected function resetCycleForm()
    {
        $this->editingCycleId = null;
        $this->cycleForm = [
            'cycle_template_id' => '',
            'status' => 'draft',
            'notes' => '',
        ];
    }

    public function render()
    {
        return view('okr::livewire.okr-show')
            ->layout('platform::layouts.app');
    }
}