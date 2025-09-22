<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Core\Models\User;

class Dashboard extends Component
{
    public $okrs;
    public $currentCycle;
    public $objectives;
    public $keyResults;
    public $availableTemplates;

    // Filter
    public $statusFilter = 'all'; // all, draft, active, completed, ending_soon, past
    public $managerFilter = '';

    // Perspektive
    public $perspective = 'personal'; // personal|team

    // Manager-Liste (Team)
    public $managers = [];

    // Metriken
    public $totalOkrs = 0;
    public $activeOkrs = 0;
    public $endingSoonOkrs = 0;
    public $completedOkrs = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function updatedStatusFilter()
    {
        $this->loadData();
    }

    public function updatedManagerFilter()
    {
        $this->loadData();
    }

    public function updatedPerspective()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $user = auth()->user();
        $teamId = $user->current_team_id;

        // Manager-Liste team-basiert
        $this->managers = User::where('current_team_id', $teamId)
            ->orderBy('name')
            ->get();

        // Basis: sichtbare OKRs
        $baseQuery = Okr::with(['cycles', 'objectives.keyResults', 'members'])
            ->visibleFor($user);

        // Perspektive anwenden
        if ($this->perspective === 'personal') {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('manager_user_id', $user->id)
                  ->orWhereHas('members', function ($m) use ($user) {
                      $m->where('users.id', $user->id);
                  });
            });
        }

        // Filter anwenden
        if ($this->statusFilter !== 'all') {
            $baseQuery->where('status', $this->statusFilter);
        }
        if (!empty($this->managerFilter)) {
            $baseQuery->where('manager_user_id', $this->managerFilter);
        }

        $this->okrs = $baseQuery->get();

        // Aktueller Zyklus (teamweit)
        $this->currentCycle = Cycle::where('team_id', $teamId)
            ->where('status', 'current')
            ->with(['template', 'objectives.keyResults'])
            ->first();

        if ($this->currentCycle) {
            $this->objectives = $this->currentCycle->objectives()->with('keyResults')->get();
            $this->keyResults = $this->currentCycle->keyResults()->get();
        } else {
            $this->objectives = collect();
            $this->keyResults = collect();
        }

        // Verfügbare Vorlagen
        $this->availableTemplates = CycleTemplate::where('is_current', true)
            ->orWhere('starts_at', '>', now())
            ->orderBy('starts_at')
            ->get();

        // Kennzahlen je Perspektive
        $metricsQuery = Okr::visibleFor($user);
        if ($this->perspective === 'personal') {
            $metricsQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('manager_user_id', $user->id)
                  ->orWhereHas('members', function ($m) use ($user) {
                      $m->where('users.id', $user->id);
                  });
            });
        }
        $allVisible = $metricsQuery->get();
        $this->totalOkrs = $allVisible->count();
        $this->activeOkrs = $allVisible->where('status', 'active')->count();
        $this->endingSoonOkrs = $allVisible->where('status', 'ending_soon')->count();
        $this->completedOkrs = $allVisible->where('status', 'completed')->count();
    }

    public function render()
    {
        return view('okr::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}