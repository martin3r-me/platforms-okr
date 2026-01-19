<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Models\VisionImage;
use Platform\Okr\Models\Obstacle;
use Platform\Okr\Models\Milestone;

class FocusAreaShow extends Component
{
    public FocusArea $focusArea;
    public string $content = '';
    public bool $isDirty = false;

    // VisionImage Modal Properties
    public $visionImageCreateModalShow = false;
    public $visionImageEditModalShow = false;
    public $editingVisionImageId = null;
    public $visionImageForm = [
        'title' => '',
        'description' => '',
        'order' => 0,
    ];

    // Obstacle Modal Properties
    public $obstacleCreateModalShow = false;
    public $obstacleEditModalShow = false;
    public $editingObstacleId = null;
    public $obstacleForm = [
        'title' => '',
        'description' => '',
        'order' => 0,
    ];

    // Milestone Modal Properties
    public $milestoneCreateModalShow = false;
    public $milestoneEditModalShow = false;
    public $editingMilestoneId = null;
    public $milestoneForm = [
        'title' => '',
        'description' => '',
        'target_year' => '',
        'target_quarter' => '',
        'order' => 0,
    ];

    protected $rules = [
        'content' => 'nullable|string',
        'visionImageForm.title' => 'required|string|max:255',
        'visionImageForm.description' => 'nullable|string',
        'visionImageForm.order' => 'required|integer|min:0',
        'obstacleForm.title' => 'required|string|max:255',
        'obstacleForm.description' => 'nullable|string',
        'obstacleForm.order' => 'required|integer|min:0',
        'milestoneForm.title' => 'required|string|max:255',
        'milestoneForm.description' => 'nullable|string',
        'milestoneForm.target_year' => 'nullable|integer',
        'milestoneForm.target_quarter' => 'nullable|integer|min:1|max:4',
        'milestoneForm.order' => 'required|integer|min:0',
    ];

    public function mount(FocusArea $focusArea)
    {
        $this->focusArea = $focusArea;
        $this->content = $this->focusArea->content ?? '';
        $this->focusArea->load([
            'forecast', 
            'team', 
            'user', 
            'visionImages', 
            'obstacles', 
            'milestones'
        ]);
    }

    public function updated($property)
    {
        if ($property === 'content') {
            $this->isDirty = true;
        }
        if (str($property)->startsWith('visionImageForm.') || 
            str($property)->startsWith('obstacleForm.') || 
            str($property)->startsWith('milestoneForm.')) {
            $this->validateOnly($property);
        }
    }

    public function save()
    {
        $this->validate(['content' => 'nullable|string']);

        $this->focusArea->update(['content' => $this->content]);
        $this->focusArea->refresh();
        $this->isDirty = false;
        session()->flash('message', 'Focus Area erfolgreich gespeichert!');
    }

    // VisionImage Management
    public function addVisionImage()
    {
        $this->resetVisionImageForm();
        $maxOrder = $this->focusArea->visionImages()->max('order') ?? 0;
        $this->visionImageForm['order'] = $maxOrder + 1;
        $this->visionImageCreateModalShow = true;
    }

    public function closeVisionImageCreateModal()
    {
        $this->visionImageCreateModalShow = false;
        $this->resetVisionImageForm();
    }

    public function editVisionImage($visionImageId)
    {
        $visionImage = $this->focusArea->visionImages()->findOrFail($visionImageId);
        $this->editingVisionImageId = $visionImage->id;
        $this->visionImageForm = [
            'title' => $visionImage->title,
            'description' => $visionImage->description ?? '',
            'order' => $visionImage->order,
        ];
        $this->visionImageEditModalShow = true;
    }

    public function closeVisionImageEditModal()
    {
        $this->visionImageEditModalShow = false;
        $this->resetVisionImageForm();
    }

    public function saveVisionImage()
    {
        $this->validate([
            'visionImageForm.title' => 'required|string|max:255',
            'visionImageForm.description' => 'nullable|string',
            'visionImageForm.order' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        if ($this->editingVisionImageId) {
            $visionImage = $this->focusArea->visionImages()->findOrFail($this->editingVisionImageId);
            $visionImage->update([
                'title' => $this->visionImageForm['title'],
                'description' => $this->visionImageForm['description'],
                'order' => $this->visionImageForm['order'],
            ]);
            session()->flash('message', 'Zielbild erfolgreich aktualisiert!');
        } else {
            $this->focusArea->visionImages()->create([
                'title' => $this->visionImageForm['title'],
                'description' => $this->visionImageForm['description'],
                'order' => $this->visionImageForm['order'],
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Zielbild erfolgreich hinzugefügt!');
        }

        $this->focusArea->refresh();
        $this->focusArea->load('visionImages');
        $this->closeVisionImageCreateModal();
        $this->closeVisionImageEditModal();
    }

    public function deleteVisionImage($visionImageId)
    {
        $visionImage = $this->focusArea->visionImages()->findOrFail($visionImageId);
        $visionImage->delete();
        
        $this->focusArea->refresh();
        $this->focusArea->load('visionImages');
        session()->flash('message', 'Zielbild erfolgreich gelöscht!');
    }

    protected function resetVisionImageForm()
    {
        $this->editingVisionImageId = null;
        $this->visionImageForm = [
            'title' => '',
            'description' => '',
            'order' => 0,
        ];
    }

    // Obstacle Management
    public function addObstacle()
    {
        $this->resetObstacleForm();
        $maxOrder = $this->focusArea->obstacles()->max('order') ?? 0;
        $this->obstacleForm['order'] = $maxOrder + 1;
        $this->obstacleCreateModalShow = true;
    }

    public function closeObstacleCreateModal()
    {
        $this->obstacleCreateModalShow = false;
        $this->resetObstacleForm();
    }

    public function editObstacle($obstacleId)
    {
        $obstacle = $this->focusArea->obstacles()->findOrFail($obstacleId);
        $this->editingObstacleId = $obstacle->id;
        $this->obstacleForm = [
            'title' => $obstacle->title,
            'description' => $obstacle->description ?? '',
            'order' => $obstacle->order,
        ];
        $this->obstacleEditModalShow = true;
    }

    public function closeObstacleEditModal()
    {
        $this->obstacleEditModalShow = false;
        $this->resetObstacleForm();
    }

    public function saveObstacle()
    {
        $this->validate([
            'obstacleForm.title' => 'required|string|max:255',
            'obstacleForm.description' => 'nullable|string',
            'obstacleForm.order' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        if ($this->editingObstacleId) {
            $obstacle = $this->focusArea->obstacles()->findOrFail($this->editingObstacleId);
            $obstacle->update([
                'title' => $this->obstacleForm['title'],
                'description' => $this->obstacleForm['description'],
                'order' => $this->obstacleForm['order'],
            ]);
            session()->flash('message', 'Hindernis erfolgreich aktualisiert!');
        } else {
            $this->focusArea->obstacles()->create([
                'title' => $this->obstacleForm['title'],
                'description' => $this->obstacleForm['description'],
                'order' => $this->obstacleForm['order'],
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Hindernis erfolgreich hinzugefügt!');
        }

        $this->focusArea->refresh();
        $this->focusArea->load('obstacles');
        $this->closeObstacleCreateModal();
        $this->closeObstacleEditModal();
    }

    public function deleteObstacle($obstacleId)
    {
        $obstacle = $this->focusArea->obstacles()->findOrFail($obstacleId);
        $obstacle->delete();
        
        $this->focusArea->refresh();
        $this->focusArea->load('obstacles');
        session()->flash('message', 'Hindernis erfolgreich gelöscht!');
    }

    protected function resetObstacleForm()
    {
        $this->editingObstacleId = null;
        $this->obstacleForm = [
            'title' => '',
            'description' => '',
            'order' => 0,
        ];
    }

    // Milestone Management
    public function addMilestone()
    {
        $this->resetMilestoneForm();
        $maxOrder = $this->focusArea->milestones()->max('order') ?? 0;
        $this->milestoneForm['order'] = $maxOrder + 1;
        $this->milestoneCreateModalShow = true;
    }

    public function closeMilestoneCreateModal()
    {
        $this->milestoneCreateModalShow = false;
        $this->resetMilestoneForm();
    }

    public function editMilestone($milestoneId)
    {
        $milestone = $this->focusArea->milestones()->findOrFail($milestoneId);
        $this->editingMilestoneId = $milestone->id;
        $this->milestoneForm = [
            'title' => $milestone->title,
            'description' => $milestone->description ?? '',
            'target_year' => $milestone->target_year ?? '',
            'target_quarter' => $milestone->target_quarter ?? '',
            'order' => $milestone->order,
        ];
        $this->milestoneEditModalShow = true;
    }

    public function closeMilestoneEditModal()
    {
        $this->milestoneEditModalShow = false;
        $this->resetMilestoneForm();
    }

    public function saveMilestone()
    {
        $this->validate([
            'milestoneForm.title' => 'required|string|max:255',
            'milestoneForm.description' => 'nullable|string',
            'milestoneForm.target_year' => 'nullable|integer',
            'milestoneForm.target_quarter' => 'nullable|integer|min:1|max:4',
            'milestoneForm.order' => 'required|integer|min:0',
        ]);

        // Ensure target_quarter is null if target_year is not set
        if (empty($this->milestoneForm['target_year'])) {
            $this->milestoneForm['target_quarter'] = null;
        }

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        if ($this->editingMilestoneId) {
            $milestone = $this->focusArea->milestones()->findOrFail($this->editingMilestoneId);
            $milestone->update([
                'title' => $this->milestoneForm['title'],
                'description' => $this->milestoneForm['description'],
                'target_year' => $this->milestoneForm['target_year'] ?: null,
                'target_quarter' => $this->milestoneForm['target_quarter'] ?: null,
                'order' => $this->milestoneForm['order'],
            ]);
            session()->flash('message', 'Meilenstein erfolgreich aktualisiert!');
        } else {
            $this->focusArea->milestones()->create([
                'title' => $this->milestoneForm['title'],
                'description' => $this->milestoneForm['description'],
                'target_year' => $this->milestoneForm['target_year'] ?: null,
                'target_quarter' => $this->milestoneForm['target_quarter'] ?: null,
                'order' => $this->milestoneForm['order'],
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Meilenstein erfolgreich hinzugefügt!');
        }

        $this->focusArea->refresh();
        $this->focusArea->load('milestones');
        $this->closeMilestoneCreateModal();
        $this->closeMilestoneEditModal();
    }

    public function deleteMilestone($milestoneId)
    {
        $milestone = $this->focusArea->milestones()->findOrFail($milestoneId);
        $milestone->delete();
        
        $this->focusArea->refresh();
        $this->focusArea->load('milestones');
        session()->flash('message', 'Meilenstein erfolgreich gelöscht!');
    }

    protected function resetMilestoneForm()
    {
        $this->editingMilestoneId = null;
        $this->milestoneForm = [
            'title' => '',
            'description' => '',
            'target_year' => '',
            'target_quarter' => '',
            'order' => 0,
        ];
    }

    #[Computed]
    public function availableYears()
    {
        if (!$this->focusArea->forecast) {
            return [];
        }

        $forecast = $this->focusArea->forecast;
        $startYear = $forecast->created_at->year;
        $endYear = $forecast->target_date->year;

        $years = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[] = [
                'key' => $year,
                'value' => (string)$year,
            ];
        }

        return $years;
    }

    #[Computed]
    public function availableQuarters()
    {
        return [
            ['key' => 1, 'value' => 'Q1'],
            ['key' => 2, 'value' => 'Q2'],
            ['key' => 3, 'value' => 'Q3'],
            ['key' => 4, 'value' => 'Q4'],
        ];
    }

    // Sortable Methods
    public function updateVisionImageOrder($items)
    {
        foreach ($items as $item) {
            $visionImage = $this->focusArea->visionImages()->find($item['value']);
            if ($visionImage) {
                $visionImage->update(['order' => $item['order']]);
            }
        }
        
        $this->focusArea->refresh();
        $this->focusArea->load('visionImages');
        session()->flash('message', 'Zielbild-Reihenfolge aktualisiert!');
    }

    public function updateObstacleOrder($items)
    {
        foreach ($items as $item) {
            $obstacle = $this->focusArea->obstacles()->find($item['value']);
            if ($obstacle) {
                $obstacle->update(['order' => $item['order']]);
            }
        }
        
        $this->focusArea->refresh();
        $this->focusArea->load('obstacles');
        session()->flash('message', 'Hindernis-Reihenfolge aktualisiert!');
    }

    public function updateMilestoneOrder($items)
    {
        foreach ($items as $item) {
            $milestone = $this->focusArea->milestones()->find($item['value']);
            if ($milestone) {
                $milestone->update(['order' => $item['order']]);
            }
        }
        
        $this->focusArea->refresh();
        $this->focusArea->load('milestones');
        session()->flash('message', 'Meilenstein-Reihenfolge aktualisiert!');
    }

    public function render()
    {
        return view('okr::livewire.focus-area-show')
            ->layout('platform::layouts.app');
    }
}
