<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Forecast;
use Platform\Okr\Models\FocusArea;

class ForecastShow extends Component
{
    public Forecast $forecast;
    public string $content = '';
    public bool $isDirty = false;

    // FocusArea Modal Properties
    public $focusAreaCreateModalShow = false;
    public $focusAreaEditModalShow = false;
    public $editingFocusAreaId = null;
    public $focusAreaForm = [
        'title' => '',
        'description' => '',
        'order' => 0,
    ];

    protected $rules = [
        'content' => 'nullable|string',
        'focusAreaForm.title' => 'required|string|max:255',
        'focusAreaForm.description' => 'nullable|string',
        'focusAreaForm.order' => 'required|integer|min:0',
    ];

    public function mount(Forecast $forecast)
    {
        $this->forecast = $forecast;
        $this->content = $this->forecast->content ?? '';
        $this->forecast->load(['team', 'user', 'focusAreas', 'versions', 'currentVersion']);
    }

    public function updated($property)
    {
        if ($property === 'content') {
            $this->isDirty = true;
        }
        if (str($property)->startsWith('focusAreaForm.')) {
            $this->validateOnly($property);
        }
    }

    public function save()
    {
        $this->validate(['content' => 'nullable|string']);

        // Create new version if content changed
        if ($this->content !== ($this->forecast->content ?? '')) {
            $this->forecast->createNewVersion($this->content, 'Content updated');
        }

        $this->forecast->refresh();
        $this->isDirty = false;
        session()->flash('message', 'Forecast successfully saved!');
    }

    // FocusArea Management
    public function addFocusArea()
    {
        $this->resetFocusAreaForm();
        $this->focusAreaCreateModalShow = true;
    }

    public function closeFocusAreaCreateModal()
    {
        $this->focusAreaCreateModalShow = false;
        $this->resetFocusAreaForm();
    }

    public function editFocusArea($focusAreaId)
    {
        $focusArea = $this->forecast->focusAreas()->findOrFail($focusAreaId);
        $this->editingFocusAreaId = $focusArea->id;
        $this->focusAreaForm = [
            'title' => $focusArea->title,
            'description' => $focusArea->description ?? '',
            'order' => $focusArea->order,
        ];
        $this->focusAreaEditModalShow = true;
    }

    public function closeFocusAreaEditModal()
    {
        $this->focusAreaEditModalShow = false;
        $this->resetFocusAreaForm();
    }

    public function saveFocusArea()
    {
        $this->validate([
            'focusAreaForm.title' => 'required|string|max:255',
            'focusAreaForm.description' => 'nullable|string',
            'focusAreaForm.order' => 'required|integer|min:0',
        ]);

        // For Parent Tools (scope_type = 'parent') use Root-Team
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        if ($this->editingFocusAreaId) {
            $focusArea = $this->forecast->focusAreas()->findOrFail($this->editingFocusAreaId);
            $focusArea->update([
                'title' => $this->focusAreaForm['title'],
                'description' => $this->focusAreaForm['description'],
                'order' => $this->focusAreaForm['order'],
            ]);
            session()->flash('message', 'Focus Area successfully updated!');
        } else {
            $this->forecast->focusAreas()->create([
                'title' => $this->focusAreaForm['title'],
                'description' => $this->focusAreaForm['description'],
                'order' => $this->focusAreaForm['order'],
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Focus Area successfully added!');
        }

        $this->forecast->refresh();
        $this->forecast->load('focusAreas');
        $this->closeFocusAreaCreateModal();
        $this->closeFocusAreaEditModal();
    }

    public function deleteFocusArea($focusAreaId)
    {
        $focusArea = $this->forecast->focusAreas()->findOrFail($focusAreaId);
        $focusArea->delete();
        
        $this->forecast->refresh();
        $this->forecast->load('focusAreas');
        session()->flash('message', 'Focus Area successfully deleted!');
    }

    protected function resetFocusAreaForm()
    {
        $this->editingFocusAreaId = null;
        $this->focusAreaForm = [
            'title' => '',
            'description' => '',
            'order' => 0,
        ];
    }

    public function render()
    {
        return view('okr::livewire.forecast-show')
            ->layout('platform::layouts.app');
    }
}
