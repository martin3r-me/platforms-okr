<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\CycleTemplate;

class Dashboard extends Component
{
    public $okrs;
    public $currentCycle;
    public $objectives;
    public $keyResults;
    public $availableTemplates;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->okrs = Okr::with(['cycles', 'objectives.keyResults'])
            ->where('team_id', auth()->user()->current_team_id)
            ->get();

        $this->currentCycle = Cycle::where('team_id', auth()->user()->current_team_id)
            ->where('status', 'current')
            ->with(['template', 'objectives.keyResults'])
            ->first();

        if ($this->currentCycle) {
            $this->objectives = $this->currentCycle->objectives()->with('keyResults')->get();
            $this->keyResults = $this->currentCycle->keyResults()->get();
        }

        $this->availableTemplates = CycleTemplate::where('is_current', true)
            ->orWhere('starts_at', '>', now())
            ->orderBy('starts_at')
            ->get();
    }

    public function render()
    {
        return view('okr::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}