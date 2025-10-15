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
    public $modalShow = false;
    public $cycleEditModalShow = false;
    public $editingCycleId = null;
    public $cycleForm = [
        'cycle_template_id' => '',
        'status' => 'draft',
        'notes' => '',
    ];

    // Member Management
    public $memberUserId = '';
    public $memberRole = 'contributor'; // contributor|viewer

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

        'memberUserId' => 'nullable|exists:users,id',
        'memberRole' => 'required|in:contributor,viewer',
    ];

    public function mount(Okr $okr)
    {
        $this->okr = $okr;
        $this->okr->load(['user', 'manager', 'cycles.template', 'members']);
        
        // Load cycle templates like in CRM - direct collection
        $this->cycleTemplates = CycleTemplate::orderBy('starts_at')->get();
    }

    #[Computed]
    public function users()
    {
        return User::where('current_team_id', auth()->user()->current_team_id)->orderBy('name')->get();
    }

    public $cycleTemplates = [];

    #[Computed]
    public function activities()
    {
        return $this->okr->activities()->latest()->limit(10)->get();
    }

    #[Computed]
    public function members()
    {
        return $this->okr->members()->withPivot('role')->orderBy('name')->get();
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

    // Member Management
    public function addMember()
    {
        $this->validate([
            'memberUserId' => 'required|exists:users,id',
            'memberRole' => 'required|in:contributor,viewer',
        ]);
        if ($this->okr->members()->where('user_id', $this->memberUserId)->exists()) {
            $this->okr->members()->updateExistingPivot($this->memberUserId, ['role' => $this->memberRole]);
        } else {
            $this->okr->members()->attach($this->memberUserId, ['role' => $this->memberRole]);
        }
        $this->okr->load('members');
        $this->memberUserId = '';
        $this->memberRole = 'contributor';
        session()->flash('message', 'Teilnehmer hinzugefügt/aktualisiert.');
    }

    public function removeMember($userId)
    {
        $this->okr->members()->detach($userId);
        $this->okr->load('members');
        session()->flash('message', 'Teilnehmer entfernt.');
    }

    public function updateMemberRole($userId, $role)
    {
        if (!in_array($role, ['contributor','viewer'])) {
            return;
        }
        $this->okr->members()->updateExistingPivot($userId, ['role' => $role]);
        $this->okr->load('members');
        session()->flash('message', 'Teilnehmer-Rolle aktualisiert.');
    }

    // Cycle Management
    public function addCycle()
    {
        $this->resetCycleForm();
        $this->cycleCreateModalShow = true;
    }

    public function manageCycleObjectives($cycleId)
    {
        return redirect()->route('okr.cycles.show', ['cycle' => $cycleId]);
    }

    public function openCycleCreateModal()
    {
        $this->resetCycleForm();
        // Default: erstes verfügbares Template vorwählen
        $firstTemplate = $this->cycleTemplates[0] ?? null;
        if ($firstTemplate) {
            $this->cycleForm['cycle_template_id'] = $firstTemplate->id;
        }
        $this->modalShow = true;
    }

    public function closeCycleCreateModal()
    {
        $this->modalShow = false;
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

        $this->okr->load('cycles.template');
        $this->closeCycleCreateModal();
        $this->closeCycleEditModal();
    }

    public function createCycle()
    {
        $this->validate([
            'cycleForm.cycle_template_id' => 'required|exists:okr_cycle_templates,id',
            'cycleForm.notes' => 'nullable|string',
        ]);

        $this->okr->cycles()->create([
            'cycle_template_id' => $this->cycleForm['cycle_template_id'],
            'status' => 'draft',
            'notes' => $this->cycleForm['notes'],
            'user_id' => auth()->id(),
        ]);

        session()->flash('message', 'Zyklus erfolgreich erstellt!');
        $this->okr->load('cycles.template');
        $this->closeCycleCreateModal();
    }

    public function openCycle($cycleId)
    {
        $this->redirect(route('okr.cycles.show', ['cycle' => $cycleId]), navigate: true);
    }

    public function deleteCycleAndCloseModal()
    {
        $cycle = $this->okr->cycles()->findOrFail($this->editingCycleId);
        $cycle->delete();
        session()->flash('message', 'Cycle erfolgreich gelöscht!');
        $this->okr->load('cycles.template');
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