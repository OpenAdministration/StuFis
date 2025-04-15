<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    public string $title = 'StuFiS - Finanzen';

    public string $version;

    public array $profileSkeleton = [
        [
            'text' => 'Mein Profil',
            'link' => '',
        ],
        [
            'text' => 'Logout',
            'route' => ['name' => 'logout'],
        ],
    ];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(/**
     * @var string if not empty, the legacy iframe will be shown with this content, instead of the slot
     */
        public string $legacyContent = '')
    {
        $this->version = config('stufis.version');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.layouts.index', [
            'version' => config('stufis.version'),
        ]);
    }
}
