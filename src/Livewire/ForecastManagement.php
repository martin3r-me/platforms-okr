<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Okr\Models\Forecast;

class ForecastManagement extends Component
{
    use WithPagination;

    // Modal State
    public $modalShow = false;
    
    // Sorting
    public $sortField = 'title';
    public $sortDirection = 'asc';
    
    // Form Data
    public $title = '';
    public $target_date = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'target_date' => 'required|date',
    ];

    public function mount()
    {
        // Check if modal should be opened
        if (request()->get('create') || str_contains(request()->url(), '#create')) {
            $this->modalShow = true;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        $forecasts = Forecast::with(['user', 'focusAreas', 'currentVersion'])
            ->where('team_id', $teamId)
            ->when(!in_array($this->sortField, ['user_name']), function($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->paginate(10);

        // Team-based statistics
        $allForecasts = Forecast::where('team_id', $teamId)->get();
        $totalForecasts = $allForecasts->count();
        $totalFocusAreas = $allForecasts->sum(fn($f) => $f->focusAreas->count());

        return view('okr::livewire.forecast-management', [
            'forecasts' => $forecasts,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'totalForecasts' => $totalForecasts,
            'totalFocusAreas' => $totalFocusAreas,
        ])->layout('platform::layouts.app');
    }

    public function createForecast()
    {
        $this->validate();
        
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;

        $forecast = Forecast::create([
            'title' => $this->title,
            'target_date' => $this->target_date,
            'team_id' => $teamId,
            'user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'Forecast successfully created!');
        
        return redirect()->route('okr.forecasts.show', $forecast);
    }

    public function resetForm()
    {
        $this->reset(['title', 'target_date']);
    }

    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteForecast($forecastId)
    {
        $forecast = Forecast::findOrFail($forecastId);
        $forecast->delete();
        
        session()->flash('message', 'Forecast successfully deleted!');
    }
}
