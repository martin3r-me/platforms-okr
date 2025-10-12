<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Okr\Models\TeamPerformance;
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
    public $successfulOkrsCount = 0;
    public $achievedObjectivesCount = 0;
    public $openKeyResultsCount = 0;
    public $achievedKeyResultsCount = 0;
    
    // Trend-Variablen
    public $scoreTrend = 0;
    public $okrTrend = 0;
    public $achievementTrend = 0;

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

        // Performance-Snapshot laden (falls vorhanden)
        $performanceSnapshot = TeamPerformance::forTeam($teamId)
            ->today()
            ->first();

        // Debug: Log was geladen wird
        \Log::info("Dashboard Team Performance", [
            'team_id' => $teamId,
            'snapshot_found' => $performanceSnapshot ? 'YES' : 'NO',
            'average_score' => $performanceSnapshot ? $performanceSnapshot->average_score : 'N/A',
            'today' => today()->format('Y-m-d'),
        ]);

        if ($performanceSnapshot) {
            $this->loadFromSnapshot($performanceSnapshot);
        } else {
            // Keine Performance-Daten verfügbar - Standardwerte setzen
            $this->averageScore = 0;
            $this->successfulOkrsCount = 0;
            $this->achievedObjectivesCount = 0;
            $this->achievedKeyResultsCount = 0;
            $this->openKeyResultsCount = 0;
        }

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
        $this->activeCyclesCount = (int) $this->activeCycles->count();
        $this->activeObjectivesCount = (int) $this->activeCycles->sum(fn ($c) => $c->objectives->count());
        $this->activeKeyResultsCount = (int) $this->activeCycles->sum(fn ($c) => $c->objectives->sum(fn ($o) => $o->keyResults->count()));
        $this->activeOkrsCount = (int) $this->activeCycles->pluck('okr_id')->unique()->count();

        // Dashboard-Statistiken berechnen
        $this->totalOkrsCount = (int) $this->okrs->count();
        $this->draftOkrsCount = (int) $this->okrs->where('status', 'draft')->count();
        $this->completedOkrsCount = (int) $this->okrs->where('status', 'completed')->count();
        $this->endingSoonOkrsCount = (int) $this->okrs->where('status', 'ending_soon')->count();
        
        // Performance-Statistiken - NUR wenn nicht bereits aus Snapshot geladen
        if (!isset($this->averageScore) || $this->averageScore === 0) {
            $this->averageScore = (float) ($this->okrs->where('performance_score', '!=', null)->avg('performance_score') ?? 0);
            $this->successfulOkrsCount = (int) $this->okrs->where('performance_score', '>=', 80)->count();
        }
        
        // Debug: Log final averageScore
        \Log::info("Dashboard Final Performance", [
            'averageScore' => $this->averageScore,
            'successfulOkrsCount' => $this->successfulOkrsCount,
        ]);
        
        // Objectives und Key Results Statistiken
        $allObjectives = $this->activeCycles->flatMap->objectives;
        $allKeyResults = $allObjectives->flatMap->keyResults;
        
        $this->achievedObjectivesCount = (int) $allObjectives->where('status', 'completed')->count();
        $this->openKeyResultsCount = (int) $allKeyResults->where('status', '!=', 'completed')->count();
        $this->achievedKeyResultsCount = (int) $allKeyResults->where('status', 'completed')->count();

        // Verfügbare Vorlagen (derzeit nicht mehr im Dashboard sichtbar)
        $this->availableTemplates = collect();
    }

    private function loadFromSnapshot(TeamPerformance $snapshot): void
    {
        // Performance-Metriken aus Snapshot (explizit als Zahlen)
        $this->averageScore = (float) $snapshot->average_score;
        $this->successfulOkrsCount = (int) $snapshot->successful_okrs;
        $this->totalOkrsCount = (int) $snapshot->total_okrs;
        $this->activeOkrsCount = (int) $snapshot->active_okrs;
        $this->draftOkrsCount = (int) $snapshot->draft_okrs;
        $this->completedOkrsCount = (int) $snapshot->completed_okrs;
        
        // Objectives & Key Results
        $this->achievedObjectivesCount = (int) $snapshot->achieved_objectives;
        $this->achievedKeyResultsCount = (int) $snapshot->achieved_key_results;
        $this->openKeyResultsCount = (int) $snapshot->open_key_results;
        
        // Zyklen
        $this->activeCyclesCount = (int) $snapshot->active_cycles;
        
        // Trends (falls verfügbar)
        $this->scoreTrend = (float) $snapshot->score_trend;
        $this->okrTrend = (int) $snapshot->okr_trend;
        $this->achievementTrend = (int) $snapshot->achievement_trend;
    }


    public function render()
    {
        return view('okr::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}