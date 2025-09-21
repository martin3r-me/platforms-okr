<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Livewire\Attributes\On; 

class Sidebar extends Component
{
    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        
    }

    #[On('create-okr')]
    public function createOkr()
    {
        return redirect()->route('okr.okrs.create');
    }

    #[On('create-cycle')]
    public function createCycle()
    {
        return redirect()->route('okr.cycles.create');
    }


    public function render()
    {
        // Team-basierte OKRs und Cycles holen
        $teamId = auth()->user()?->current_team_id ?? null;
        
        $okrs = Okr::query()
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get();

        $cycles = Cycle::query()
            ->where('team_id', $teamId)
            ->with(['template', 'okr'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('okr::livewire.sidebar', [
            'okrs' => $okrs,
            'cycles' => $cycles,
        ]);
    }
}
