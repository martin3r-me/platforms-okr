<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\StrategicDocument;
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
    public $keyResultManagerUserId = null;

    // Milestone Properties
    public $objectiveSelectedMilestoneIds = [];
    public $keyResultSelectedMilestoneIds = [];

    // Inline Performance Edit
    public $inlineEditKeyResultId = null;
    public $inlineEditValue = '';

    // Delete Modal Properties
    public $deleteModalShow = false;

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
        $this->cycle->load(['okr', 'template', 'objectives.milestones.focusArea', 'objectives.keyResults.performance', 'objectives.keyResults.primaryContexts', 'objectives.keyResults.manager', 'objectives.keyResults.milestones.focusArea', 'okr.members']);
    }

    public function rendered()
    {
        // Context an CursorSidebar senden
        $this->dispatch('comms', [
            'model' => get_class($this->cycle),                                // z. B. 'Platform\Okr\Models\Cycle'
            'modelId' => $this->cycle->id,
            'subject' => trim(($this->cycle->okr->title ?? 'OKR') . ' — ' . ($this->cycle->label ?? $this->cycle->template?->label ?? 'Cycle')),
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

        // Organization-Kontext setzen - nur Zeiten erlauben, keine Entity-Verknüpfung, keine Dimensionen
        $this->dispatch('organization', [
            'context_type' => get_class($this->cycle),
            'context_id' => $this->cycle->id,
            'linked_contexts' => $this->cycle->okr ? [['type' => get_class($this->cycle->okr), 'id' => $this->cycle->okr->id]] : [],
            'allow_time_entry' => true,
            'allow_entities' => false,
            'allow_dimensions' => false,
        ]);

        // KeyResult-Kontext setzen - ermöglicht Verknüpfung von KeyResults mit diesem Cycle
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->cycle),
            'context_id' => $this->cycle->id,
        ]);
    }

    #[Computed]
    public function users()
    {
        return User::where('current_team_id', auth()->user()->current_team_id)->get();
    }

    #[Computed]
    public function okrMembers()
    {
        if (!$this->cycle->okr) {
            return collect();
        }
        
        // Lade OKR-Mitglieder + OKR-Manager (dürfen als Verantwortliche ausgewählt werden)
        $members = $this->cycle->okr->members()->orderBy('name')->get();
        
        // Füge OKR-Manager hinzu, falls vorhanden und noch nicht in der Liste
        if ($this->cycle->okr->manager_user_id && $this->cycle->okr->manager) {
            $manager = $this->cycle->okr->manager;
            // Prüfe ob Manager bereits in Mitgliedern ist
            if (!$members->contains('id', $manager->id)) {
                $members->push($manager);
            }
        }
        
        return $members->sortBy('name')->values();
    }

    #[Computed]
    public function mission()
    {
        if (!$this->cycle->okr) {
            return null;
        }
        return StrategicDocument::active('mission')
            ->forTeam($this->cycle->okr->team_id)
            ->first();
    }

    #[Computed]
    public function vision()
    {
        if (!$this->cycle->okr) {
            return null;
        }
        return StrategicDocument::active('vision')
            ->forTeam($this->cycle->okr->team_id)
            ->first();
    }


    #[Computed]
    public function availableMilestones()
    {
        if (!$this->cycle->okr) {
            return collect();
        }
        return Milestone::where('team_id', $this->cycle->okr->team_id)
            ->with('focusArea')
            ->orderBy('title')
            ->get()
            ->mapWithKeys(fn($m) => [
                $m->id => ($m->focusArea ? $m->focusArea->title . ' > ' : '') . $m->title,
            ]);
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
        $objective = $this->cycle->objectives()->with('milestones')->findOrFail($objectiveId);
        $this->editingObjectiveId = $objective->id;
        $this->objectiveForm = [
            'title' => $objective->title,
            'description' => $objective->description,
            'order' => $objective->order,
        ];
        $this->objectiveSelectedMilestoneIds = $objective->milestones->pluck('id')->map(fn($id) => (string) $id)->toArray();
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
            $objective->milestones()->sync(array_map('intval', array_filter($this->objectiveSelectedMilestoneIds)));
            session()->flash('message', 'Objective erfolgreich aktualisiert!');
        } else {
            // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
            $user = auth()->user();
            $baseTeam = $user->currentTeamRelation;
            $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
            $teamId = ($okrModule && $okrModule->isRootScoped()) 
                ? $baseTeam->getRootTeam()->id 
                : $baseTeam->id;
            
            $this->cycle->objectives()->create([
                'title' => $this->objectiveForm['title'],
                'description' => $this->objectiveForm['description'],
                'order' => $this->objectiveForm['order'],
                'okr_id' => $this->cycle->okr_id,
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Objective erfolgreich hinzugefügt!');
        }

        $this->cycle->load(['objectives.keyResults', 'objectives.milestones.focusArea', 'objectives.keyResults.milestones.focusArea']); // Refresh objectives
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
        $this->keyResultSelectedMilestoneIds = [];
        $this->keyResultCreateModalShow = true;
    }

    public function closeKeyResultCreateModal()
    {
        $this->keyResultCreateModalShow = false;
        $this->editingKeyResultObjectiveId = null;
        $this->keyResultTitle = '';
        $this->keyResultDescription = '';
        $this->keyResultManagerUserId = null;
        $this->keyResultValueType = 'absolute';
        $this->keyResultTargetValue = '';
        $this->keyResultCurrentValue = '';
        $this->keyResultUnit = '';
        $this->keyResultSelectedMilestoneIds = [];
    }

    public function editKeyResult($keyResultId)
    {
        // Find the key result directly from the database
        $keyResult = \Platform\Okr\Models\KeyResult::with(['performance', 'milestones'])->find($keyResultId);
        
        if ($keyResult) {
            $this->editingKeyResultId = $keyResult->id;
            $this->editingKeyResultObjectiveId = $keyResult->objective_id;
            $this->keyResultTitle = $keyResult->title;
            $this->keyResultDescription = $keyResult->description ?? '';
            $this->keyResultManagerUserId = $keyResult->manager_user_id;
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
            
            $this->keyResultSelectedMilestoneIds = $keyResult->milestones->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->keyResultEditModalShow = true;
        }
    }

    public function closeKeyResultEditModal()
    {
        $this->keyResultEditModalShow = false;
        $this->editingKeyResultId = null;
        $this->keyResultTitle = '';
        $this->keyResultDescription = '';
        $this->keyResultManagerUserId = null;
        $this->keyResultValueType = 'absolute';
        $this->keyResultTargetValue = '';
        $this->keyResultCurrentValue = '';
        $this->keyResultUnit = '';
        $this->keyResultSelectedMilestoneIds = [];
    }

    public function deleteKeyResultAndCloseModal()
    {
        if ($this->editingKeyResultId) {
            try {
                $keyResult = \Platform\Okr\Models\KeyResult::findOrFail($this->editingKeyResultId);
                $keyResult->delete();
                
                $this->cycle->load('objectives.keyResults.performance');
                session()->flash('message', 'Erfolgskriterium erfolgreich gelöscht!');
            } catch (\Exception $e) {
                session()->flash('error', 'Fehler beim Löschen: ' . $e->getMessage());
            }
        }
        
        $this->closeKeyResultEditModal();
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
        
        // Zielwert nur erzwingen, wenn Typ nicht boolean ist
        if ($this->keyResultValueType !== 'boolean') {
            if ($this->keyResultTargetValue === '' || $this->keyResultTargetValue === null) {
                session()->flash('error', 'Zielwert ist erforderlich!');
                return;
            }
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
                    'manager_user_id' => $this->keyResultManagerUserId ?: null,
                ]);

                // Immer eine neue Performance-Version erstellen (Versionierung)
                $targetValue = $this->keyResultValueType === 'boolean' ? 1.0 : (float) $this->keyResultTargetValue;
                $currentValue = $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : (float) ($this->keyResultCurrentValue ?: 0);

                if ($this->keyResultValueType === 'boolean') {
                    $isCompleted = (bool) $this->keyResultCurrentValue;
                    $performanceScore = $isCompleted ? 1.0 : 0.0;
                } else {
                    $isCompleted = $targetValue > 0 && $currentValue >= $targetValue;
                    $performanceScore = $targetValue > 0 ? min(1.0, max(0.0, $currentValue / $targetValue)) : 0.0;
                }

                $keyResult->performances()->create([
                    'type' => $this->keyResultValueType,
                    'target_value' => $targetValue,
                    'current_value' => $currentValue,
                    'is_completed' => $isCompleted,
                    'performance_score' => $performanceScore,
                    'team_id' => $keyResult->team_id,
                    'user_id' => auth()->id(),
                ]);
                
                $keyResult->milestones()->sync(array_map('intval', array_filter($this->keyResultSelectedMilestoneIds)));
                $this->closeKeyResultEditModal();
                session()->flash('message', 'Erfolgskriterium erfolgreich aktualisiert!');
            } else {
                // Create new Key Result
                $nextOrder = $objective->keyResults()->max('order') + 1;
                
                // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
                $user = auth()->user();
                $baseTeam = $user->currentTeamRelation;
                $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                $teamId = ($okrModule && $okrModule->isRootScoped()) 
                    ? $baseTeam->getRootTeam()->id 
                    : $baseTeam->id;
                
                $keyResult = $objective->keyResults()->create([
                    'title' => $this->keyResultTitle,
                    'description' => $this->keyResultDescription,
                    'order' => $nextOrder,
                    'manager_user_id' => $this->keyResultManagerUserId ?: null,
                    'team_id' => $teamId,
                    'user_id' => $user->id,
                ]);

                // Create initial performance record (erste Version)
                $initTarget = $this->keyResultValueType === 'boolean' ? 1.0 : (float) $this->keyResultTargetValue;
                $initCurrent = $this->keyResultValueType === 'boolean' ? ($this->keyResultCurrentValue ? 1.0 : 0.0) : (float) ($this->keyResultCurrentValue ?: 0);

                if ($this->keyResultValueType === 'boolean') {
                    $initCompleted = (bool) $this->keyResultCurrentValue;
                    $initScore = $initCompleted ? 1.0 : 0.0;
                } else {
                    $initCompleted = $initTarget > 0 && $initCurrent >= $initTarget;
                    $initScore = $initTarget > 0 ? min(1.0, max(0.0, $initCurrent / $initTarget)) : 0.0;
                }

                $keyResult->performances()->create([
                    'type' => $this->keyResultValueType,
                    'target_value' => $initTarget,
                    'current_value' => $initCurrent,
                    'is_completed' => $initCompleted,
                    'performance_score' => $initScore,
                    'team_id' => $keyResult->team_id,
                    'user_id' => auth()->id(),
                ]);
                
                $keyResult->milestones()->sync(array_map('intval', array_filter($this->keyResultSelectedMilestoneIds)));
                $this->closeKeyResultCreateModal();
                session()->flash('message', 'Erfolgskriterium erfolgreich hinzugefügt!');
            }
            
            $this->cycle->load(['objectives.keyResults.performance', 'objectives.milestones.focusArea', 'objectives.keyResults.milestones.focusArea']);

        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    /**
     * Toggle Boolean Key Result Status (Erledigt/Offen)
     */
    public function toggleBooleanKeyResult($keyResultId)
    {
        try {
            $keyResult = \Platform\Okr\Models\KeyResult::findOrFail($keyResultId);
            
            if (!$keyResult->performance) {
                session()->flash('error', 'Keine Performance-Daten gefunden!');
                return;
            }
            
            if ($keyResult->performance->type !== 'boolean') {
                session()->flash('error', 'Diese Funktion ist nur für Boolean-Erfolgskriterien verfügbar!');
                return;
            }
            
            // Neuen Status (gegenteil von aktuell)
            $newStatus = !$keyResult->performance->is_completed;
            
            // Neue Performance-Version erstellen
            // Verwende die Team-ID des KeyResults (bereits korrekt gesetzt)
            $keyResult->performances()->create([
                'type' => 'boolean',
                'target_value' => 1.0, // Boolean Ziel ist immer 1
                'current_value' => $newStatus ? 1.0 : 0.0,
                'is_completed' => $newStatus,
                'performance_score' => $newStatus ? 1.0 : 0.0,
                'team_id' => $keyResult->team_id,
                'user_id' => auth()->id(),
            ]);
            
            $this->cycle->load('objectives.keyResults.performance');
            session()->flash('message', $newStatus ? 'Erfolgskriterium als erledigt markiert!' : 'Erfolgskriterium als offen markiert!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Umschalten: ' . $e->getMessage());
        }
    }

    /**
     * Update nur den aktuellen Wert (Performance-Update ohne Zielwert-Änderung)
     */
    public function updateKeyResultPerformance($keyResultId, $newCurrentValue)
    {
        try {
            $keyResult = \Platform\Okr\Models\KeyResult::findOrFail($keyResultId);

            $type = $keyResult->performance->type;
            $targetValue = $keyResult->performance->target_value;
            $currentValue = (float) $newCurrentValue;

            if ($type === 'boolean') {
                $isCompleted = (bool) $newCurrentValue;
                $score = $isCompleted ? 1.0 : 0.0;
            } else {
                $isCompleted = $targetValue > 0 && $currentValue >= $targetValue;
                $score = $targetValue > 0 ? min(1.0, max(0.0, $currentValue / $targetValue)) : 0.0;
            }

            $keyResult->performances()->create([
                'type' => $type,
                'target_value' => $targetValue,
                'current_value' => $currentValue,
                'is_completed' => $isCompleted,
                'performance_score' => $score,
                'team_id' => $keyResult->team_id,
                'user_id' => auth()->id(),
            ]);

            $this->cycle->load('objectives.keyResults.performance');

        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    /**
     * Inline-Editing: KR-Wert direkt in der Zeile bearbeiten
     */
    public function startInlineEdit($keyResultId)
    {
        $keyResult = \Platform\Okr\Models\KeyResult::find($keyResultId);
        if (!$keyResult || !$keyResult->performance) return;

        $this->inlineEditKeyResultId = $keyResultId;
        $this->inlineEditValue = (string) $keyResult->performance->current_value;
    }

    public function cancelInlineEdit()
    {
        $this->inlineEditKeyResultId = null;
        $this->inlineEditValue = '';
    }

    public function saveInlineEdit()
    {
        if (!$this->inlineEditKeyResultId) return;

        $this->updateKeyResultPerformance($this->inlineEditKeyResultId, $this->inlineEditValue);
        $this->inlineEditKeyResultId = null;
        $this->inlineEditValue = '';
    }

    protected function resetObjectiveForm()
    {
        $this->editingObjectiveId = null;
        $this->objectiveForm = [
            'title' => '',
            'description' => '',
            'order' => 0,
        ];
        $this->objectiveSelectedMilestoneIds = [];
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
        session()->flash('message', 'Erfolgskriterien-Reihenfolge aktualisiert!');
    }

    /**
     * Open Delete Modal
     */
    public function openDeleteModal()
    {
        $this->deleteModalShow = true;
    }

    /**
     * Close Delete Modal
     */
    public function closeDeleteModal()
    {
        $this->deleteModalShow = false;
    }

    /**
     * Delete Cycle
     */
    public function deleteCycle()
    {
        try {
            $okrId = $this->cycle->okr_id;
            $cycleTitle = $this->cycle->template?->label ?? 'Unbekannter Cycle';
            
            // Delete the cycle (cascade will handle related data)
            $this->cycle->delete();
            
            // Redirect to OKR show page
            return redirect()->route('okr.okrs.show', ['okr' => $okrId])
                ->with('message', "Zyklus '{$cycleTitle}' wurde erfolgreich gelöscht.");
                
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Löschen des Zyklus: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('okr::livewire.cycle-show')
            ->layout('platform::layouts.app');
    }
}
