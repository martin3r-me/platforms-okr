<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        return view('okr::livewire.sidebar')
            ->layout('platform::layouts.app');
    }
}