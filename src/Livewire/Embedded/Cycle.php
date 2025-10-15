<?php

namespace Platform\Okr\Livewire\Embedded;

use Platform\Okr\Livewire\CycleShow as BaseCycleShow;

class Cycle extends BaseCycleShow
{
    public function render()
    {
        // Verwende die normale Cycle-View mit vollständiger UI, nur mit Embedded-Layout
        return view('okr::livewire.cycle-show')
            ->layout('platform::layouts.embedded');
    }
}


