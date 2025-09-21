<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Core\Models\User;

class OkrShow extends Component
{
    public Okr $okr;
    public bool $isDirty = false;
    
    // Cycle Modals
    public bool $cycleCreateModalShow = false;
    public bool $cycleEditModalShow = false;
    public ?int $editingCycleId = null;
    
    // Cycle Form
    public array $cycleForm = [
        'cycle_template_id' => '',
        'status' => 'draft',
        'notes' => '',
    ];

    protected $rules = [
        'okr.title' => 'required|string|max:255',
        'okr.description' => 'nullable|string',
        'okr.performance_score' => 'nullable|numeric|min:0|max:100',
        'okr.user_id' => 'required|exists:users,id',
        'okr.manager_user_id' => 'nullable|exists:users,id',
        'okr.auto_transfer' => 'boolean',
        'okr.is_template' => 'boolean',
        'cycleForm.cycle_template_id' => 'required|exists:okr_cycle_templates,id',
        'cycleForm.status' => 'required|in:draft,current,ending_soon,completed,archived',
        'cycleForm.notes' => 'nullable|string',
    ];

    public function mount(Okr $okr)
    {
        $this->okr = $okr;
        $this->okr->load(['cycles.template', 'user', 'manager']);
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'okr.')) {
            $this->isDirty = true;
        }
    }

    public function save()
    {
        $this->validate([
            'okr.title' => 'required|string|max:255',
            'okr.description' => 'nullable|string',
            'okr.performance_score' => 'nullable|numeric|min:0|max:100',
            'okr.user_id' => 'required|exists:users,id',
            'okr.manager_user_id' => 'nullable|exists:users,id',
            'okr.auto_transfer' => 'boolean',
            'okr.is_template' => 'boolean',
        ]);

        $this->okr->save();
        $this->isDirty = false;
        
        session()->flash('message', 'OKR erfolgreich gespeichert!');
    }

    public function addCycle()
    {
        $this->resetCycleForm();
        $this->cycleCreateModalShow = true;
    }

    public function editCycle($cycleId)
    {
        $cycle = Cycle::findOrFail($cycleId);
        $this->editingCycleId = $cycleId;
        $this->cycleForm = [
            'cycle_template_id' => $cycle->cycle_template_id,
            'status' => $cycle->status,
            'notes' => $cycle->notes ?? '',
        ];
        $this->cycleEditModalShow = true;
    }

    public function saveCycle()
    {
        $this->validate([
            'cycleForm.cycle_template_id' => 'required|exists:okr_cycle_templates,id',
            'cycleForm.status' => 'required|in:draft,current,ending_soon,completed,archived',
            'cycleForm.notes' => 'nullable|string',
        ]);

        if ($this->editingCycleId) {
            // Update existing cycle
            $cycle = Cycle::findOrFail($this->editingCycleId);
            $cycle->update([
                'cycle_template_id' => $this->cycleForm['cycle_template_id'],
                'status' => $this->cycleForm['status'],
                'notes' => $this->cycleForm['notes'] ?: null,
            ]);
            $message = 'Cycle erfolgreich aktualisiert!';
        } else {
            // Create new cycle
            Cycle::create([
                'okr_id' => $this->okr->id,
                'cycle_template_id' => $this->cycleForm['cycle_template_id'],
                'status' => $this->cycleForm['status'],
                'notes' => $this->cycleForm['notes'] ?: null,
                'team_id' => auth()->user()->current_team_id,
                'user_id' => auth()->id(),
            ]);
            $message = 'Cycle erfolgreich erstellt!';
        }

        $this->okr->refresh();
        $this->closeCycleCreateModal();
        $this->closeCycleEditModal();
        
        session()->flash('message', $message);
    }

    public function deleteCycleAndCloseModal()
    {
        if ($this->editingCycleId) {
            $cycle = Cycle::findOrFail($this->editingCycleId);
            $cycle->delete();
            
            $this->okr->refresh();
            $this->closeCycleEditModal();
            
            session()->flash('message', 'Cycle erfolgreich gelöscht!');
        }
    }

    public function closeCycleCreateModal()
    {
        $this->cycleCreateModalShow = false;
        $this->resetCycleForm();
    }

    public function closeCycleEditModal()
    {
        $this->cycleEditModalShow = false;
        $this->editingCycleId = null;
        $this->resetCycleForm();
    }

    protected function resetCycleForm()
    {
        $this->cycleForm = [
            'cycle_template_id' => '',
            'status' => 'draft',
            'notes' => '',
        ];
    }

    public function render()
    {
        $users = User::where('current_team_id', auth()->user()->current_team_id)->get();
        $cycleTemplates = CycleTemplate::orderBy('starts_at')->get();

        return view('okr::livewire.okr-show', [
            'users' => $users,
            'cycleTemplates' => $cycleTemplates,
        ])->layout('platform::layouts.app');
    }
}
