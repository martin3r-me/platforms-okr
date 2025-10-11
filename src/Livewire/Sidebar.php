<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Okr\Models\Okr;
use Livewire\Attributes\On; 

class Sidebar extends Component
{
    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        
    }

    public function openCreateModal()
    {
        return redirect()->route('okr.okrs.index');
    }



    public function render()
    {
        // Team-basierte OKRs holen
        $teamId = auth()->user()?->current_team_id ?? null;
        
        $okrs = Okr::query()
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get();

        return view('okr::livewire.sidebar', [
            'okrs' => $okrs,
        ]);
    }
}
