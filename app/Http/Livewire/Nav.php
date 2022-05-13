<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Nav extends Component
{

    public function render()
    {
        return view('livewire.nav');
    }

    public function getNavigationAttribute()
    {
        return [
            'Main' => [
                [
                    'name' => 'Dashboard',
                    'icon' => 'home',
                    'route' => '/menu',
                    'label' => '',
                    'label-color' => '',
                ],
            ]

        ];
    }
}
