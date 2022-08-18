<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    public string $title = "Finanzen";

    /**
     * @var string if not empty, the legacy iframe will be shown with this content, instead of the slot
     */
    public string $legacyContent = '';

    public array $navigationSkeleton = [
        [
            'icon' => 'heroicon-o-home',
            'text' => 'Übersicht',
            'route' => [
                'name' => 'menu',
                'parameters' => ['sub' => 'mygremium'],
            ],
        ],
        [
            'icon' => 'heroicon-o-clipboard-check',
            'text' => 'TODO',
            'route' => [
                'name' => 'menu',
                'parameters' => ['sub' => 'belege'],
            ],
        ],
        [
            'icon' => 'heroicon-o-book-open',
            'text' => 'Buchungen',
            'route' => [
                'name' => 'booking',
            ],
        ],
        [
            'icon' => 'heroicon-o-credit-card',
            'text' => 'Konto',
            'route' => [
                'name' => 'konto',
            ],
        ],
        [
            'icon' => 'heroicon-o-scale',
            'text' => 'Sitzung',
            'route' => [
                'name' => 'menu',
                'parameters' => ['sub' => 'stura'],
            ],
        ],
        [
            'icon' => 'heroicon-o-table',
            'text' => 'Haushalt',
            'route' => [
                'name' => 'hhp',
            ],
        ],

    ];

    public array $profileSkeleton = [

    ];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($legacyContent = '')
    {
        $this->legacyContent = $legacyContent;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.layout');
    }
}
