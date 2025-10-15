<?php

namespace Platform\Okr\Livewire\Embedded;

use Livewire\Component;
use Platform\Okr\Models\Cycle as OkrCycle;

class Cycle extends Component
{
    public OkrCycle $cycle;

    public function mount($cycle)
    {
        if (!$cycle instanceof OkrCycle) {
            $this->cycle = OkrCycle::with(['template','objectives.keyResults'])->findOrFail($cycle);
        } else {
            $this->cycle = $cycle->load(['template','objectives.keyResults']);
        }
    }

    public function render()
    {
        return view('okr::livewire.embedded.cycle');
    }
}


