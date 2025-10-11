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
    public $activeCycles; // aktive Zyklen
    public $objectives;
    public $keyResults;
    public $availableTemplates;

    // Filter
    public $statusFilter = 'all';
    public $managerFilter = '';

    // Perspektive
    public $perspective = 'personal';

    // Manager-Liste (Team)
    public $managers = [];

    // Kennzahlen (aktiv)
    public $activeCyclesCount = 0;
    public $activeObjectivesCount = 0;
    public $activeKeyResultsCount = 0;
    public $activeOkrsCount = 0;
    
    // Dashboard-Variablen
    public $totalOkrsCount = 0;
    public $draftOkrsCount = 0;
    public $completedOkrsCount = 0;
    public $endingSoonOkrsCount = 0;
    public $averageScore = 0;
    public $achievedObjectivesCount = 0;
    public $openKeyResultsCount = 0;
    public $achievedKeyResultsCount = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function updatedStatusFilter() { $this->loadData(); }
    public function updatedManagerFilter() { $this->loadData(); }
    public function updatedPerspective() { $this->loadData(); }

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

        if ($this->perspective === 'personal') {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('manager_user_id', $user->id)
                  ->orWhereHas('members', function ($m) use ($user) {
                      $m->where('users.id', $user->id);
                  });
            });
        }

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
            ->with(['template', 'objectives.keyResults.performance'])
            ->first();

        if ($this->currentCycle) {
            $this->objectives = $this->currentCycle->objectives()->with('keyResults.performance')->get();
            $this->keyResults = $this->currentCycle->keyResults()->with('performance')->get();
        } else {
            $this->objectives = collect();
            $this->keyResults = collect();
        }

        // Aktive Zyklen (inkl. current) teamweit
        $this->activeCycles = Cycle::where('team_id', $teamId)
            ->whereIn('status', ['current', 'active'])
            ->with(['template', 'okr', 'objectives.keyResults.performance'])
            ->orderByRaw("FIELD(status, 'current','active')")
            ->orderByDesc('updated_at')
            ->get();

        // Kennzahlen auf Basis aktiver Zyklen
        $this->activeCyclesCount = $this->activeCycles->count();
        $this->activeObjectivesCount = $this->activeCycles->sum(fn ($c) => $c->objectives->count());
        $this->activeKeyResultsCount = $this->activeCycles->sum(fn ($c) => $c->objectives->sum(fn ($o) => $o->keyResults->count()));
        $this->activeOkrsCount = $this->activeCycles->pluck('okr_id')->unique()->count();

        // Dashboard-Statistiken berechnen
        $this->totalOkrsCount = $this->okrs->count();
        $this->draftOkrsCount = $this->okrs->where('status', 'draft')->count();
        $this->completedOkrsCount = $this->okrs->where('status', 'completed')->count();
        $this->endingSoonOkrsCount = $this->okrs->where('status', 'ending_soon')->count();
        
        // Performance-Statistiken
        $this->averageScore = $this->okrs->where('performance_score', '!=', null)->avg('performance_score') ?? 0;
        
        // Objectives und Key Results Statistiken
        $allObjectives = $this->activeCycles->flatMap->objectives;
        $allKeyResults = $allObjectives->flatMap->keyResults;
        
        $this->achievedObjectivesCount = $allObjectives->where('status', 'completed')->count();
        $this->openKeyResultsCount = $allKeyResults->where('status', '!=', 'completed')->count();
        $this->achievedKeyResultsCount = $allKeyResults->where('status', 'completed')->count();

        // Verfügbare Vorlagen (derzeit nicht mehr im Dashboard sichtbar)
        $this->availableTemplates = collect();
    }

    public function render()
    {
        return view('okr::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}