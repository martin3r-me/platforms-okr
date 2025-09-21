<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Okr\Models\Okr;

class CycleManagement extends Component
{
    public $cycles;
    public $templates;
    public $okrs;
    public $selectedOkr;
    public $selectedTemplate;
    public $showCreateModal = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->cycles = Cycle::where('team_id', auth()->user()->current_team_id)
            ->with(['template', 'okr'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->templates = CycleTemplate::orderBy('starts_at')->get();
        $this->okrs = Okr::where('team_id', auth()->user()->current_team_id)->get();
    }

    public function createCycle()
    {
        $this->validate([
            'selectedOkr' => 'required|exists:okrs,id',
            'selectedTemplate' => 'required|exists:cycle_templates,id',
        ]);

        Cycle::create([
            'okr_id' => $this->selectedOkr,
            'cycle_template_id' => $this->selectedTemplate,
            'team_id' => auth()->user()->current_team_id,
            'user_id' => auth()->id(),
            'status' => 'draft',
        ]);

        $this->showCreateModal = false;
        $this->selectedOkr = null;
        $this->selectedTemplate = null;
        $this->loadData();
        
        session()->flash('message', 'Cycle erfolgreich erstellt!');
    }

    public function updateCycleStatus($cycleId, $status)
    {
        $cycle = Cycle::findOrFail($cycleId);
        $cycle->update(['status' => $status]);
        $this->loadData();
        
        session()->flash('message', 'Cycle-Status aktualisiert!');
    }

    public function render()
    {
        return view('okr::livewire.cycle-management')
            ->layout('platform::layouts.app');
    }

    public function index()
    {
        return $this->render();
    }

    public function create()
    {
        $this->showCreateModal = true;
        return $this->render();
    }

    public function show($cycle)
    {
        // TODO: Implement Cycle detail view
        return redirect()->route('okr.cycles.index');
    }
}
