<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('okr::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}