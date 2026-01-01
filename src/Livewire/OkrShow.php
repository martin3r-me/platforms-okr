<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Okr\Models\StrategicDocument;
use Platform\Core\Models\User;
use Platform\Core\Enums\StandardRole;
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
    public $memberRole = 'member'; // member|viewer
    public $okrSettingsModalShow = false;

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
        'memberRole' => 'required|in:member,viewer',
    ];

    public function mount(Okr $okr)
    {
        $this->okr = $okr;
        $this->okr->load(['user', 'manager', 'cycles.template', 'members']);
        
        // Load cycle templates like in CRM - direct collection
        $this->cycleTemplates = CycleTemplate::orderBy('starts_at')->get();
        
        // Set default member user if available
        $this->setDefaultMemberUser();
    }

    #[Computed]
    public function users()
    {
        $team = $this->okr->team;
        if ($team && method_exists($team, 'users')) {
            return $team->users()->orderBy('name')->get();
        }
        // Fallback auf current_team_id, falls Relation nicht geladen/verfügbar
        return User::where('current_team_id', $this->okr->team_id)->orderBy('name')->get();
    }

    #[Computed]
    public function availableUsers()
    {
        $currentMemberIds = $this->okr->members->pluck('id')->all();
        $excludeIds = array_filter(array_unique(array_merge(
            $currentMemberIds,
            [$this->okr->manager_user_id]
        )));

        $team = $this->okr->team;
        if ($team && method_exists($team, 'users')) {
            $query = $team->users()->orderBy('name');
            if (!empty($excludeIds)) {
                $query->whereNotIn('users.id', $excludeIds);
            }
            return $query->get();
        }

        // Fallback auf current_team_id / team_id
        $query = User::where('current_team_id', $this->okr->team_id)->orderBy('name');
        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }
        return $query->get();
    }

    #[Computed]
    public function roleOptions(): array
    {
        $user = auth()->user();
        $isAdminContext = false;
        if (method_exists($user, 'isTeamOwner') && $user->isTeamOwner($this->okr->team_id)) {
            $isAdminContext = true;
        }
        if (!$isAdminContext && method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $this->okr->team_id)) {
            $isAdminContext = true;
        }
        if (!$isAdminContext && $this->okr->user_id === $user->id) {
            $isAdminContext = true;
        }

        $options = [
            StandardRole::MEMBER->value => 'Member',
            StandardRole::VIEWER->value => 'Viewer',
        ];
        if ($isAdminContext) {
            $options = [StandardRole::ADMIN->value => 'Admin'] + $options;
        }
        return $options;
    }

    protected function setDefaultMemberUser(): void
    {
        if (empty($this->memberUserId) && $this->availableUsers->isNotEmpty()) {
            $this->memberUserId = $this->availableUsers->first()->id;
        }
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->okr),
            'modelId' => $this->okr->id,
            'subject' => $this->okr->title,
            'description' => $this->okr->description ?? '',
            'url' => route('okr.okrs.show', $this->okr),
            'source' => 'okr.okr.view',
            'recipients' => [],
            'meta' => [
                'performance_score' => $this->okr->performance_score,
                'auto_transfer' => $this->okr->auto_transfer,
                'is_template' => $this->okr->is_template,
                'team_id' => $this->okr->team_id ?? null,
            ],
        ]);

        // Organization-Kontext setzen - beides erlauben: Zeiten + Entity-Verknüpfung + Dimensionen
        $this->dispatch('organization', [
            'context_type' => get_class($this->okr),
            'context_id' => $this->okr->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
            // Verfügbare Relations für Children-Cascade (z.B. Cycles mit Objectives/KeyResults)
            'include_children_relations' => ['cycles', 'cycles.objectives', 'cycles.objectives.keyResults'],
        ]);
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

    #[Computed]
    public function mission()
    {
        return StrategicDocument::active('mission')
            ->forTeam($this->okr->team_id)
            ->first();
    }

    #[Computed]
    public function vision()
    {
        return StrategicDocument::active('vision')
            ->forTeam($this->okr->team_id)
            ->first();
    }

    #[Computed]
    public function regnose()
    {
        return StrategicDocument::active('regnose')
            ->forTeam($this->okr->team_id)
            ->first();
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
        $this->authorize('invite', $this->okr);
        $this->validate([
            'memberUserId' => 'required|exists:users,id',
            'memberRole' => 'required|in:member,viewer',
        ]);
        if ($this->okr->members()->where('user_id', $this->memberUserId)->exists()) {
            $this->okr->members()->updateExistingPivot($this->memberUserId, ['role' => $this->memberRole]);
        } else {
            $this->okr->members()->attach($this->memberUserId, ['role' => $this->memberRole]);
        }
        $this->okr->load('members');
        $this->memberUserId = '';
        $this->memberRole = 'member';
        $this->setDefaultMemberUser();
        session()->flash('message', 'Teilnehmer hinzugefügt/aktualisiert.');
    }

    public function removeMember($userId)
    {
        $this->authorize('removeMember', $this->okr);
        $this->okr->members()->detach($userId);
        $this->okr->load('members');
        session()->flash('message', 'Teilnehmer entfernt.');
    }

    public function updateMemberRole($userId, $role)
    {
        $this->authorize('changeRole', $this->okr);
        if (!in_array($role, ['member','viewer'])) {
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
        // Sicherstellen, dass Templates geladen sind
        $this->ensureCycleTemplatesLoaded();
        // Default: erstes verfügbares Template vorwählen
        $firstTemplate = $this->cycleTemplates instanceof \Illuminate\Support\Collection
            ? $this->cycleTemplates->first()
            : (is_array($this->cycleTemplates) ? ($this->cycleTemplates[0] ?? null) : null);
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
                'team_id' => auth()->user()->currentTeam?->id,
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

    protected function ensureCycleTemplatesLoaded(): void
    {
        $needLoad = false;
        if ($this->cycleTemplates instanceof \Illuminate\Support\Collection) {
            $needLoad = $this->cycleTemplates->isEmpty();
        } elseif (is_array($this->cycleTemplates)) {
            $needLoad = count($this->cycleTemplates) === 0;
        } else {
            $needLoad = empty($this->cycleTemplates);
        }
        if ($needLoad) {
            $this->cycleTemplates = CycleTemplate::orderBy('starts_at')->get();
        }
    }


    public function render()
    {
        return view('okr::livewire.okr-show')
            ->layout('platform::layouts.app');
    }
}