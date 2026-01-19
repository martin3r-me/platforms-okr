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
        session()->flash('message', 'Regnose erfolgreich gespeichert!');
    }

    // FocusArea Management
    public function addFocusArea()
    {
        $this->resetFocusAreaForm();
        // Auto-set order to next available
        $maxOrder = $this->forecast->focusAreas()->max('order') ?? 0;
        $this->focusAreaForm['order'] = $maxOrder + 1;
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
            session()->flash('message', 'Focus Area erfolgreich aktualisiert!');
        } else {
            // Auto-set order if not provided
            $order = $this->focusAreaForm['order'] ?? ($this->forecast->focusAreas()->max('order') ?? 0) + 1;
            
            $this->forecast->focusAreas()->create([
                'title' => $this->focusAreaForm['title'],
                'description' => $this->focusAreaForm['description'],
                'order' => $order,
                'team_id' => $teamId,
                'user_id' => $user->id,
            ]);
            session()->flash('message', 'Focus Area erfolgreich hinzugefügt!');
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
        session()->flash('message', 'Focus Area erfolgreich gelöscht!');
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

    // Sortable Methods
    public function updateFocusAreaOrder($items)
    {
        foreach ($items as $item) {
            $focusArea = $this->forecast->focusAreas()->find($item['value']);
            if ($focusArea) {
                $focusArea->update(['order' => $item['order']]);
            }
        }
        
        $this->forecast->refresh();
        $this->forecast->load('focusAreas');
        session()->flash('message', 'Focus Area-Reihenfolge aktualisiert!');
    }

    public function render()
    {
        return view('okr::livewire.forecast-show')
            ->layout('platform::layouts.app');
    }
}
