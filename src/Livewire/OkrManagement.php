<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Okr\Models\Okr;
use Platform\Core\Models\User;

class OkrManagement extends Component
{
    use WithPagination;

    // Modal State
    public $modalShow = false;
    
    // Sorting
    public $sortField = 'title';
    public $sortDirection = 'asc';
    
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

    public function mount()
    {
        // Prüfe, ob das Modal geöffnet werden soll
        if (request()->get('create') || str_contains(request()->url(), '#create')) {
            $this->modalShow = true;
        }
    }

    public function render()
    {
        $teamId = auth()->user()->current_team_id;
        
        $okrs = Okr::with(['user', 'manager', 'cycles'])
            ->where('team_id', $teamId)
            ->when($this->sortField === 'user_name', function($query) {
                $query->join('users', 'okr_okrs.user_id', '=', 'users.id')
                      ->orderBy('users.name', $this->sortDirection);
            })
            ->when($this->sortField === 'manager_name', function($query) {
                $query->join('users as managers', 'okr_okrs.manager_user_id', '=', 'managers.id')
                      ->orderBy('managers.name', $this->sortDirection);
            })
            ->when(!in_array($this->sortField, ['user_name', 'manager_name']), function($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->paginate(10);
            
        $users = User::where('current_team_id', $teamId)->get();
        
        // Team-basierte Statistiken
        $allOkrs = Okr::where('team_id', $teamId)->get();
        $totalOkrs = $allOkrs->count();
        $activeOkrs = $allOkrs->where('status', 'active')->count();
        $templateOkrs = $allOkrs->where('is_template', true)->count();
        $averageScore = $allOkrs->avg('performance_score') ?? 0;
        $successfulOkrs = $allOkrs->where('performance_score', '>=', 80)->count();
        $autoTransferOkrs = $allOkrs->where('auto_transfer', true)->count();

        return view('okr::livewire.okr-management', [
            'okrs' => $okrs,
            'users' => $users,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'totalOkrs' => $totalOkrs,
            'activeOkrs' => $activeOkrs,
            'templateOkrs' => $templateOkrs,
            'averageScore' => $averageScore,
            'successfulOkrs' => $successfulOkrs,
            'autoTransferOkrs' => $autoTransferOkrs,
        ])->layout('platform::layouts.app');
    }

    public function createOkr()
    {
        $this->validate();
        
        $okr = Okr::create([
            'title' => $this->title,
            'description' => $this->description ?: null,
            'performance_score' => $this->performance_score ?: 0,
            'auto_transfer' => $this->auto_transfer,
            'is_template' => $this->is_template,
            'manager_user_id' => $this->manager_user_id ?: null,
            'team_id' => auth()->user()->current_team_id,
            'user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'OKR erfolgreich erstellt!');
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'description', 'performance_score', 'auto_transfer', 
            'is_template', 'manager_user_id'
        ]);
        $this->auto_transfer = false;
        $this->is_template = false;
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

    public function deleteOkr($okrId)
    {
        $okr = Okr::findOrFail($okrId);
        $okr->delete();
        
        session()->flash('message', 'OKR erfolgreich gelöscht!');
    }
}