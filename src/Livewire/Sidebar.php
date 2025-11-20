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

    // Modal State
    public $modalShow = false;
    
    // Form Data
    public $title = '';
    public $description = '';
    public $performance_score = 0;
    public $auto_transfer = false;
    public $is_template = false;
    public $manager_user_id = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'performance_score' => 'required|numeric|min:0|max:100',
        'auto_transfer' => 'boolean',
        'is_template' => 'boolean',
        'manager_user_id' => 'nullable|exists:users,id',
    ];

    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function createOkr()
    {
        $this->validate();
        
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        
        // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;
        
        $okr = \Platform\Okr\Models\Okr::create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'performance_score' => $this->performance_score ?: 0,
            'auto_transfer' => $this->auto_transfer,
            'is_template' => $this->is_template,
            'manager_user_id' => $this->manager_user_id ?: null,
            'team_id' => $teamId,
            'user_id' => $user->id,
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'OKR erfolgreich erstellt!');
        
        // Sidebar aktualisieren
        $this->dispatch('updateSidebar');
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'description', 'performance_score', 'auto_transfer', 
            'is_template', 'manager_user_id'
        ]);
        $this->performance_score = 0;
        $this->auto_transfer = false;
        $this->is_template = false;
    }



    public function render()
    {
        if (!Auth::check() || !Auth::user()->currentTeamRelation) {
            return view('okr::livewire.sidebar', [
                'okrs' => collect(),
                'users' => collect(),
            ]);
        }

        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        
        // Für Parent Tools (scope_type = 'parent') das Root-Team verwenden
        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam()->id 
            : $baseTeam->id;
        
        // Team-basierte OKRs holen
        $okrs = Okr::query()
            ->where('team_id', $teamId)
            ->orderBy('title')
            ->get();

        // Users für Manager-Dropdown (vom Root-Team wenn Parent Tool)
        $rootTeam = ($okrModule && $okrModule->isRootScoped()) 
            ? $baseTeam->getRootTeam() 
            : $baseTeam;
        
        $users = $rootTeam->users()
            ->orderBy('name')
            ->get();

        return view('okr::livewire.sidebar', [
            'okrs' => $okrs,
            'users' => $users,
        ]);
    }
}
